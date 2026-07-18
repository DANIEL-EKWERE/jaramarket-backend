#!/usr/bin/env python3
"""
MySQL (phpMyAdmin) dump -> PostgreSQL DATA-ONLY import script, targeted at
tables already created by Django migrations (`python manage.py migrate`).

Rebuilt from scratch after auditing the dump against the real Django schema
(introspected via `django.setup()`, and the actual DDL via `sqlmigrate`).
Key differences from a naive line-regex port:

  * MySQL backslash-escaped strings (`\\'`, `\\n`, `\\r`, `\\"`, `\\\\`) are
    properly unescaped and re-emitted as PostgreSQL '' -quoted literals.
    (The old script left backslash escapes untouched, which is silently
    wrong once `standard_conforming_strings = on` -- broken strings /
    truncated inserts on ~thousands of rows containing quotes or newlines.)
  * Per-table column lists are diffed against the real Django schema
    (apps/*/models.py via introspection). Columns that only exist in the
    dump (Laravel leftovers) are dropped; NOT NULL columns that only exist
    in Django (no DB-level default) are filled with an explicit safe value.
  * DELETE/INSERT statements are ordered by a real topological sort of
    Django's FK graph (parents before children on insert, reverse on
    delete) instead of disabling triggers. `SET session_replication_role
    = replica` needs superuser; so does `ALTER TABLE ... DISABLE TRIGGER
    ALL` for the internal RI (foreign-key) triggers specifically -- table
    ownership alone is not enough for those, confirmed failing with
    "permission denied: ... is a system trigger" on cPanel hosting.
  * No CREATE TABLE / ADD CONSTRAINT statements are emitted -- the schema
    already exists via Django migrations; re-adding constraints would just
    create duplicates.
  * Emits `setval()` calls at the end to resync identity sequences after
    inserting explicit ids.

Usage: python3 convert_to_postgres.py
Output: equivuxb_jaramarket_pg.sql

KNOWN DATA LOSS / MANUAL FOLLOW-UP (see WARNINGS printed at the end too):
  - state_representatives: the dump's columns (name, phone, email, address,
    lga, state, is_active) do not correspond at all to the Django model
    (user_id, state_id FKs only). There is no reliable automatic mapping.
    This table's data is SKIPPED. Handle it manually if needed.
  - vendors: business/banking columns in the dump (business_name,
    business_address, bank_name, account_number, account_name,
    tax_identification_number, is_verified, ...) have no home in the new
    `vendors` table (they seem to have moved to `bank_accounts` /
    `users` in the new schema). These columns are DROPPED from the import.
  - countries.vat has no source column in the dump and is NOT NULL in
    Django; it is imported as 0.00 for every country. Update real VAT
    rates after import.
  - users.is_staff / users.is_superuser have no dump equivalent; imported
    as false for every row (nobody becomes a Django admin automatically).
"""

import json
import re
import subprocess
import sys

INPUT_FILE = "equivuxb_jaramarket.sql"
OUTPUT_FILE = "equivuxb_jaramarket_pg.sql"

# Laravel-only tables that don't exist in Django models — skip entirely
SKIP_TABLES = {
    "cache", "cache_locks", "failed_jobs", "job_batches", "jobs",
    "migrations", "password_reset_tokens", "personal_access_tokens", "sessions",
}

# Tables we know we cannot safely map automatically -- see module docstring.
UNMAPPABLE_TABLES = {"state_representatives"}

# Rename tables from dump name -> Django model db_table name
TABLE_RENAMES = {"favourites": "favorites"}

# (table, column) tinyint(1) pairs that are Django BooleanFields -- convert
# 0/1 to false/true. Verified against apps/*/models.py BooleanField usage.
BOOLEAN_COLUMNS = {
    ("addresses", "is_default"),
    ("admins", "is_active"),
    ("order_items", "pass_quality_assurance"),
    ("order_items", "re_assigned"),
    ("transaction_logs", "is_refund"),
    ("transaction_logs", "has_refund"),
    ("users", "is_active"),
    ("users", "is_verified"),
    ("vendors", "is_active"),
}

# Extra columns to inject per (dump-name) table: dj_column -> literal SQL value.
# Only for NOT NULL Django columns that have no source column in the dump.
EXTRA_CONSTANT_COLUMNS = {
    "users": {"is_staff": "false", "is_superuser": "false"},
    "countries": {"vat": "0.00"},
}

# Negative id (never collides with a real bigserial/BigAutoField id, and is
# ignored by MAX(id)-based setval() resync since it's below every real id)
# for a synthetic placeholder row, used when a dangling FK points at a user
# that simply doesn't exist anywhere in the dump -- rather than dropping
# the referencing row (an order, a wallet, ...), it's repointed here so the
# row and its data survive, clearly marked as belonging to a deleted account.
PLACEHOLDER_USER_ID = -1

# Explicit values for NOT NULL / identifying columns on the placeholder user;
# any other column defaults to NULL. String values are filled in as QuotedStr
# once the module is fully loaded (see build_placeholder_user_row below).
PLACEHOLDER_USER_FIELDS_RAW = {
    "id": "-1",
    "password": "!unusable-password",
    "firstname": "Deleted",
    "lastname": "Account",
    "email": "deleted-user@placeholder.invalid",
    "role": "customer",
    "referral_count": "0",
    "is_active": False,
    "is_verified": False,
}


def get_django_schema():
    """Introspect the real target schema via `django.setup()` (no DB needed)."""
    script = (
        "import os, django, json\n"
        "os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'jaraman.settings')\n"
        "django.setup()\n"
        "from django.apps import apps as django_apps\n"
        "out = {}\n"
        "for model in django_apps.get_models():\n"
        "    table = model._meta.db_table\n"
        "    cols = {}\n"
        "    for f in model._meta.get_fields():\n"
        "        if f.many_to_many or f.one_to_many:\n"
        "            continue\n"
        "        if not hasattr(f, 'column') or f.column is None:\n"
        "            continue\n"
        "        cols[f.column] = f.null if hasattr(f, 'null') else True\n"
        "    out[table] = cols\n"
        "print(json.dumps(out))\n"
    )
    result = subprocess.run(
        [sys.executable, "-c", script],
        capture_output=True, text=True, cwd=".",
        env={"PYTHONPATH": ".", "PATH": subprocess.os.environ.get("PATH", "")},
    )
    if result.returncode != 0:
        print(result.stderr, file=sys.stderr)
        raise RuntimeError("Django schema introspection failed")
    return json.loads(result.stdout)


def get_integer_pk_tables():
    """Tables whose primary key is a real DB sequence (AutoField/
    BigAutoField), as opposed to e.g. notifications.id which is a
    UUIDField -- MAX(id)/setval() on those don't apply."""
    script = (
        "import os, django, json\n"
        "os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'jaraman.settings')\n"
        "django.setup()\n"
        "from django.apps import apps as django_apps\n"
        "from django.db.models import AutoField, BigAutoField, SmallAutoField\n"
        "out = []\n"
        "for model in django_apps.get_models():\n"
        "    pk = model._meta.pk\n"
        "    if isinstance(pk, (AutoField, BigAutoField, SmallAutoField)):\n"
        "        out.append(model._meta.db_table)\n"
        "print(json.dumps(out))\n"
    )
    result = subprocess.run(
        [sys.executable, "-c", script],
        capture_output=True, text=True, cwd=".",
        env={"PYTHONPATH": ".", "PATH": subprocess.os.environ.get("PATH", "")},
    )
    if result.returncode != 0:
        print(result.stderr, file=sys.stderr)
        raise RuntimeError("Django PK-type introspection failed")
    return set(json.loads(result.stdout))


def get_column_fk_map():
    """(table, column) -> target table, for every ForeignKey/OneToOneField
    column (self-references included -- handled separately at use site)."""
    script = (
        "import os, django, json\n"
        "os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'jaraman.settings')\n"
        "django.setup()\n"
        "from django.apps import apps as django_apps\n"
        "from django.db.models import ForeignKey, OneToOneField\n"
        "out = {}\n"
        "for model in django_apps.get_models():\n"
        "    table = model._meta.db_table\n"
        "    for f in model._meta.get_fields():\n"
        "        if isinstance(f, (ForeignKey, OneToOneField)):\n"
        "            out[f'{table}.{f.column}'] = f.remote_field.model._meta.db_table\n"
        "print(json.dumps(out))\n"
    )
    result = subprocess.run(
        [sys.executable, "-c", script],
        capture_output=True, text=True, cwd=".",
        env={"PYTHONPATH": ".", "PATH": subprocess.os.environ.get("PATH", "")},
    )
    if result.returncode != 0:
        print(result.stderr, file=sys.stderr)
        raise RuntimeError("Django column FK map introspection failed")
    raw = json.loads(result.stdout)
    return {tuple(k.split(".", 1)): v for k, v in raw.items()}


def get_fk_graph():
    """table -> set of tables it has a FK to (self-references excluded),
    introspected from real Django model fields (no DB connection needed)."""
    script = (
        "import os, django, json\n"
        "os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'jaraman.settings')\n"
        "django.setup()\n"
        "from django.apps import apps as django_apps\n"
        "from django.db.models import ForeignKey, OneToOneField\n"
        "edges = {}\n"
        "for model in django_apps.get_models():\n"
        "    table = model._meta.db_table\n"
        "    edges.setdefault(table, [])\n"
        "    for f in model._meta.get_fields():\n"
        "        if isinstance(f, (ForeignKey, OneToOneField)):\n"
        "            ref = f.remote_field.model._meta.db_table\n"
        "            if ref != table:\n"
        "                edges[table].append(ref)\n"
        "print(json.dumps(edges))\n"
    )
    result = subprocess.run(
        [sys.executable, "-c", script],
        capture_output=True, text=True, cwd=".",
        env={"PYTHONPATH": ".", "PATH": subprocess.os.environ.get("PATH", "")},
    )
    if result.returncode != 0:
        print(result.stderr, file=sys.stderr)
        raise RuntimeError("Django FK graph introspection failed")
    return {k: set(v) for k, v in json.loads(result.stdout).items()}


def topological_order(tables, fk_graph):
    """Return `tables` ordered so each table comes after every table it
    depends on (FK target), restricted to edges within `tables` itself.
    Uses Kahn's algorithm; ties broken alphabetically for determinism."""
    tables = set(tables)
    deps = {t: (fk_graph.get(t, set()) & tables) for t in tables}
    ordered = []
    remaining = set(tables)
    while remaining:
        ready = sorted(t for t in remaining if not (deps[t] & remaining))
        if not ready:
            # genuine cycle (shouldn't happen here) -- break it deterministically
            ready = [sorted(remaining)[0]]
        for t in ready:
            ordered.append(t)
            remaining.discard(t)
    return ordered


def unescape_mysql_string(raw: str) -> str:
    """Unescape a MySQL single-quoted string body (backslash-escapes already
    resolved by the tokenizer's char scan -- this handles the escape map)."""
    out = []
    i = 0
    n = len(raw)
    escape_map = {
        "n": "\n", "r": "\r", "t": "\t", "0": "\0",
        "'": "'", '"': '"', "\\": "\\", "Z": "\x1a", "b": "\b",
    }
    while i < n:
        c = raw[i]
        if c == "\\" and i + 1 < n:
            nxt = raw[i + 1]
            out.append(escape_map.get(nxt, nxt))
            i += 2
        else:
            out.append(c)
            i += 1
    return "".join(out)


def parse_row_tuples(values_text: str):
    """Char-level scan of the text after VALUES, splitting into row tuples
    and then fields, respecting MySQL string/backslash-escape rules."""
    rows = []
    i = 0
    n = len(values_text)
    while i < n:
        while i < n and values_text[i] in " \t\r\n,":
            i += 1
        if i >= n or values_text[i] != "(":
            break
        i += 1  # consume '('
        fields = []
        buf = []
        in_string = False
        while i < n:
            c = values_text[i]
            if in_string:
                if c == "\\" and i + 1 < n:
                    buf.append(c)
                    buf.append(values_text[i + 1])
                    i += 2
                    continue
                if c == "'":
                    in_string = False
                    buf.append(c)
                    i += 1
                    continue
                buf.append(c)
                i += 1
                continue
            else:
                if c == "'":
                    in_string = True
                    buf.append(c)
                    i += 1
                    continue
                if c == ",":
                    fields.append("".join(buf))
                    buf = []
                    i += 1
                    continue
                if c == ")":
                    fields.append("".join(buf))
                    buf = []
                    i += 1
                    break
                buf.append(c)
                i += 1
        rows.append(fields)
    return rows


class QuotedStr(str):
    """A dump field that was single-quoted in the source. Must always be
    re-emitted as a quoted string literal, even if its content happens to
    look numeric -- e.g. a phone number '07068628887' is NOT the integer
    7068628887 (that silently drops the leading zero and corrupts data)."""


def parse_field(raw: str):
    """Return a Python value for a single raw field token from a row tuple."""
    s = raw.strip()
    if s.upper() == "NULL":
        return None
    if s.startswith("'") and s.endswith("'") and len(s) >= 2:
        return QuotedStr(unescape_mysql_string(s[1:-1]))
    return s  # bare numeric literal token from the dump, passed through as-is


def emit_sql_literal(value):
    if value is None:
        return "NULL"
    if isinstance(value, bool):
        return "true" if value else "false"
    if isinstance(value, QuotedStr):
        return "'" + value.replace("'", "''") + "'"
    if isinstance(value, str):
        return value  # already a valid bare SQL literal (int/decimal)
    return str(value)


_PLACEHOLDER_TEXT_FIELDS = {"password", "firstname", "lastname", "email", "role"}


def build_placeholder_user_row(kept_cols):
    """A synthetic 'deleted user' row, in the same column order as the real
    rows, for repointing dangling user references instead of dropping data."""
    row = []
    for c in kept_cols:
        v = PLACEHOLDER_USER_FIELDS_RAW.get(c)
        if c in _PLACEHOLDER_TEXT_FIELDS and v is not None:
            v = QuotedStr(v)
        row.append(v)
    return row


def split_statements(sql: str):
    """Split a MySQL dump into top-level statements, respecting MySQL-style
    backslash-escaped single-quoted strings and backtick identifiers.

    sqlparse.split() assumes ANSI '' escaping and gets confused by `\\'`
    inside dump data, silently merging many statements into one -- this
    does the same job but understands the dump's actual escaping rules.
    """
    stmts = []
    buf = []
    i = 0
    n = len(sql)
    in_string = False
    in_ident = False
    in_line_comment = False
    while i < n:
        c = sql[i]
        if in_line_comment:
            buf.append(c)
            if c == "\n":
                in_line_comment = False
            i += 1
            continue
        if in_string:
            if c == "\\" and i + 1 < n:
                buf.append(c)
                buf.append(sql[i + 1])
                i += 2
                continue
            buf.append(c)
            if c == "'":
                in_string = False
            i += 1
            continue
        if in_ident:
            buf.append(c)
            if c == "`":
                in_ident = False
            i += 1
            continue
        if c == "'":
            in_string = True
            buf.append(c)
            i += 1
            continue
        if c == "`":
            in_ident = True
            buf.append(c)
            i += 1
            continue
        if c == "-" and i + 1 < n and sql[i + 1] == "-":
            in_line_comment = True
            buf.append(c)
            i += 1
            continue
        if c == ";":
            buf.append(c)
            stmts.append("".join(buf))
            buf = []
            i += 1
            continue
        buf.append(c)
        i += 1
    tail = "".join(buf).strip()
    if tail:
        stmts.append(tail)
    return stmts


def parse_create_table_columns(body: str):
    """Extract ordered column names from a CREATE TABLE body (dump order)."""
    cols = []
    for line in body.splitlines():
        m = re.match(r"\s*`(\w+)`", line)
        if m:
            cols.append(m.group(1))
    return cols


def main():
    print(f"Introspecting Django schema...")
    django_schema = get_django_schema()

    print(f"Reading {INPUT_FILE}...")
    with open(INPUT_FILE, "r", encoding="utf-8") as f:
        sql = f.read()

    statements = split_statements(sql)

    create_columns = {}  # dump table name -> [col, ...] in dump order
    output_lines = []
    warnings = []
    tables_touched = []  # dj table names actually written to, in first-seen order

    insert_re = re.compile(r"^\s*INSERT INTO\s+`(\w+)`\s*\(([^)]*)\)\s*VALUES\s*(.*);\s*$", re.DOTALL | re.IGNORECASE)
    create_re = re.compile(r"^\s*CREATE TABLE\s+`(\w+)`\s*\((.*)\)\s*ENGINE", re.DOTALL | re.IGNORECASE)

    table_inserts = {}  # dj_table -> [columns, extra_const_cols, [raw-value rows]]

    for stmt in statements:
        stmt = stmt.strip()
        if not stmt:
            continue
        # Comment lines ("-- Dumping data for table `x`") have no trailing
        # semicolon of their own, so split_statements bundles them onto the
        # front of the next real statement. Strip them before matching.
        stmt = re.sub(r"^(?:\s*--[^\n]*\n)+", "", stmt).strip()
        if not stmt:
            continue

        m_create = create_re.match(stmt)
        if m_create:
            tname, body = m_create.group(1), m_create.group(2)
            create_columns[tname] = parse_create_table_columns(body)
            continue

        m_insert = insert_re.match(stmt)
        if not m_insert:
            continue  # DROP TABLE / ALTER TABLE / etc: not needed, schema exists already

        tname, col_text, values_text = m_insert.groups()
        if tname in SKIP_TABLES:
            continue
        if tname in UNMAPPABLE_TABLES:
            warnings.append(f"SKIPPED table '{tname}': dump schema has no reliable mapping to Django model (see script docstring).")
            continue

        dj_table = TABLE_RENAMES.get(tname, tname)
        if dj_table not in django_schema:
            warnings.append(f"SKIPPED table '{tname}': no matching Django model/table '{dj_table}'.")
            continue

        dump_cols = [c.strip().strip("`") for c in col_text.split(",")]
        dj_cols = django_schema[dj_table]

        keep_idx = [i for i, c in enumerate(dump_cols) if c in dj_cols]
        kept_cols = [dump_cols[i] for i in keep_idx]

        extra_cols = EXTRA_CONSTANT_COLUMNS.get(tname, {})
        extra_cols = {k: v for k, v in extra_cols.items() if k not in kept_cols}

        final_cols = kept_cols + list(extra_cols.keys())

        rows = parse_row_tuples(values_text)
        out_rows = []
        for row in rows:
            values = [parse_field(row[i]) for i in keep_idx]
            for c, v in zip(kept_cols, values):
                if (tname, c) in BOOLEAN_COLUMNS and v is not None:
                    v = bool(int(v))
                    idx = kept_cols.index(c)
                    values[idx] = v
            # kept as raw Python values (not yet stringified) so the FK
            # dangling-reference pass below can inspect/null them; extra
            # constant columns are pre-formatted SQL and appended separately.
            out_rows.append(values)

        table_inserts.setdefault(dj_table, [None, None, []])
        table_inserts[dj_table][0] = final_cols
        table_inserts[dj_table][1] = extra_cols
        table_inserts[dj_table][2].extend(out_rows)
        if dj_table not in tables_touched:
            tables_touched.append(dj_table)

    # ---- Resolve dangling FK references ----
    # The source data isn't referentially clean (MySQL/Laravel never
    # enforced these FKs as strictly) -- e.g. order_items.referral_id can
    # point at a users.id that was hard-deleted upstream and never existed
    # in this dump. Django's migrations mark these constraints DEFERRABLE
    # INITIALLY DEFERRED, so such a row sails through every DELETE/INSERT
    # and only blows up at the final COMMIT.
    #
    # For a *nullable* FK column, null the dangling value. For a NOT NULL
    # FK column (e.g. orders.user_id, wallets.user_id, order_item_logs.
    # vendor_id -- an order/wallet/log cannot exist without its owner),
    # nulling isn't legal, so the whole row is dropped instead. Dropping a
    # row can itself orphan a child row in another table (e.g. dropping a
    # wallet orphans its transaction_logs), so this repeats to a fixed
    # point rather than a single pass.
    print("Checking for dangling FK references in the source data...")
    column_fk_map = get_column_fk_map()

    # Pre-insert the synthetic 'deleted user' placeholder if we're touching
    # users at all, so it's part of valid_ids from the first iteration and
    # every dangling reference TO users gets repointed there instead of
    # dropping the referencing row (order, wallet, order_item, ...).
    placeholder_used = False
    if "users" in table_inserts:
        users_cols, users_extra, users_rows = table_inserts["users"]
        users_kept_cols = [c for c in users_cols if c not in users_extra]
        users_rows.append(build_placeholder_user_row(users_kept_cols))

    def compute_valid_ids():
        result = {}
        for t2 in tables_touched:
            cols2, _extra2, rows2 = table_inserts[t2]
            if "id" in cols2:
                idx2 = cols2.index("id")
                result[t2] = {str(r[idx2]) for r in rows2}
        return result

    dropped_count = 0
    nulled_count = 0
    repointed_count = 0
    changed = True
    while changed:
        changed = False
        valid_ids = compute_valid_ids()
        for t in tables_touched:
            cols, extra_cols, rows = table_inserts[t]
            dj_nullable = django_schema.get(t, {})
            rows_to_drop = set()
            for col_idx, col in enumerate(cols):
                target = column_fk_map.get((t, col))
                if target is None or target not in valid_ids:
                    continue
                nullable = dj_nullable.get(col, True)
                for row_idx, row in enumerate(rows):
                    v = row[col_idx]
                    if v is None or str(v) in valid_ids[target]:
                        continue
                    if target == "users" and t != "users":
                        warnings.append(
                            f"REPOINTED dangling FK: {t}.{col} = {v} -- no such "
                            f"row in 'users' (deleted upstream, not in this "
                            f"dump at all) -- repointed to the placeholder "
                            f"'Deleted Account' user (id={PLACEHOLDER_USER_ID}) "
                            f"instead of dropping this row"
                        )
                        row[col_idx] = int(PLACEHOLDER_USER_ID)
                        repointed_count += 1
                        placeholder_used = True
                        changed = True
                    elif nullable:
                        warnings.append(
                            f"NULLED dangling FK: {t}.{col} = {v} (no such row "
                            f"in '{target}' -- present in the source dump but "
                            f"not in the data being imported)"
                        )
                        row[col_idx] = None
                        nulled_count += 1
                        changed = True
                    else:
                        rows_to_drop.add(row_idx)
            if rows_to_drop:
                id_idx = cols.index("id") if "id" in cols else None
                for row_idx in sorted(rows_to_drop, reverse=True):
                    dropped_id = rows[row_idx][id_idx] if id_idx is not None else "?"
                    warnings.append(
                        f"DROPPED row: {t}.id={dropped_id} -- a NOT NULL FK "
                        f"column on it pointed to a row that doesn't exist in "
                        f"the imported data, so it could not be inserted at all"
                    )
                    del rows[row_idx]
                    dropped_count += 1
                changed = True

    # Drop the placeholder again if nothing ended up pointing at it, so we
    # don't insert an inert extra user for no reason.
    if "users" in table_inserts and not placeholder_used:
        users_cols, users_extra, users_rows = table_inserts["users"]
        users_kept_cols = [c for c in users_cols if c not in users_extra]
        id_idx = users_kept_cols.index("id")
        table_inserts["users"][2] = [
            r for r in users_rows if str(r[id_idx]) != str(PLACEHOLDER_USER_ID)
        ]

    if nulled_count or dropped_count or repointed_count:
        warnings.append(
            f"SUMMARY: {repointed_count} dangling user reference(s) repointed "
            f"to the placeholder 'Deleted Account' user (id={PLACEHOLDER_USER_ID}), "
            f"{nulled_count} other dangling FK value(s) nulled, {dropped_count} "
            f"row(s) dropped entirely due to a NOT NULL FK pointing at a "
            f"non-user table with no matching row. See individual entries above."
        )

    # ---- Order DELETE/INSERT to respect real FK dependencies ----
    # `ALTER TABLE ... DISABLE TRIGGER ALL` was tried first, but disabling
    # the internal RI (foreign-key) triggers specifically requires
    # superuser even for the table owner -- confirmed failing with
    # "permission denied: ... is a system trigger" on cPanel hosting.
    # So instead: never violate a constraint in the first place, by
    # deleting children-before-parents and inserting parents-before-children.
    print("Computing FK-safe statement order...")
    fk_graph = get_fk_graph()
    insert_order = topological_order(tables_touched, fk_graph)
    delete_order = list(reversed(insert_order))
    integer_pk_tables = get_integer_pk_tables()

    header = (
        "-- PostgreSQL data import converted from MySQL dump\n"
        "-- Generated by convert_to_postgres.py -- DATA ONLY, target schema must\n"
        "-- already exist (run `python manage.py migrate` first).\n\n"
        "-- DELETE/INSERT statement order below is a real topological sort of\n"
        "-- the Django FK graph (parents before children for INSERT, reverse\n"
        "-- for DELETE) -- not trigger-disabling, which needs superuser for\n"
        "-- internal RI triggers even when you own the table.\n\n"
        "SET client_encoding = 'UTF8';\n"
        "SET standard_conforming_strings = on;\n\n"
        "BEGIN;\n\n"
    )

    lines = [header]
    lines.append("-- Clear existing rows (children before parents)")
    for t in delete_order:
        lines.append(f'DELETE FROM "{t}";')
    lines.append("")

    for t in insert_order:
        cols, extra_cols, rows = table_inserts[t]
        if not rows:
            continue
        col_list = ", ".join(f'"{c}"' for c in cols)
        extra_literals = [extra_cols[c] for c in extra_cols]
        literal_rows = [
            [emit_sql_literal(v) for v in row] + extra_literals
            for row in rows
        ]
        lines.append(f'-- {t}: {len(rows)} rows')
        # batch in chunks of 500 rows per statement for readability/safety
        for start in range(0, len(literal_rows), 500):
            chunk = literal_rows[start:start + 500]
            values_sql = ",\n".join("  (" + ", ".join(r) + ")" for r in chunk)
            lines.append(f'INSERT INTO "{t}" ({col_list}) VALUES\n{values_sql};')
        lines.append("")

    lines.append("-- Resync identity sequences after inserting explicit ids")
    for t in insert_order:
        cols, extra_cols, rows = table_inserts[t]
        if "id" in cols and rows and t in integer_pk_tables:
            lines.append(
                f"SELECT setval(pg_get_serial_sequence('\"{t}\"', 'id'), "
                f'COALESCE((SELECT MAX(id) FROM "{t}"), 1), true);'
            )
    lines.append("")
    lines.append("COMMIT;")

    with open(OUTPUT_FILE, "w", encoding="utf-8") as f:
        f.write("\n".join(lines) + "\n")

    print(f"\nDone! Output: {OUTPUT_FILE}")
    print(f"Tables imported: {len(tables_touched)}")
    if warnings:
        print("\n--- WARNINGS (need manual attention) ---")
        for w in warnings:
            print(" -", w)
    print("\nAlso hardcoded data-loss/defaults (see script docstring):")
    print(" - state_representatives: SKIPPED entirely (schema mismatch)")
    print(" - vendors: business/banking columns dropped (business_name, bank_name, account_number, ...)")
    print(" - countries.vat: defaulted to 0.00 for all rows")
    print(" - users.is_staff / users.is_superuser: defaulted to false for all rows")


if __name__ == "__main__":
    main()
