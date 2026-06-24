"""
Imports data from the phpMyAdmin MySQL dump into the Django SQLite database.
Run with: python import_sql.py
"""
import re
import sqlite3
import sys
import os

SQL_FILE = "equivuxb_jaramarket.sql"
DB_FILE  = "db.sqlite3"

# Tables that exist in the MySQL dump but have no matching Django table — skip them.
SKIP_TABLES = {
    "migrations", "sessions", "cache", "cache_locks",
    "failed_jobs", "jobs", "job_batches", "job_batches",
    "password_reset_tokens", "personal_access_tokens",
}

def unescape(s):
    """Convert MySQL string escapes to their Python equivalents."""
    s = s.replace("\\'", "''")   # MySQL escaped quote → SQLite escaped quote
    s = s.replace('\\"', '"')
    s = s.replace("\\n", "\n")
    s = s.replace("\\r", "\r")
    s = s.replace("\\t", "\t")
    s = s.replace("\\\\", "\\")
    return s

def parse_values_block(values_str):
    """
    Parse MySQL VALUES (...),(...)  into a list of value-tuples (as strings).
    Handles quoted strings, NULL, numbers, and nested parens in strings.
    """
    rows = []
    i = 0
    n = len(values_str)
    while i < n:
        # Find opening paren
        while i < n and values_str[i] != '(':
            i += 1
        if i >= n:
            break
        i += 1  # skip '('
        fields = []
        current = ""
        in_string = False
        quote_char = None
        while i < n:
            c = values_str[i]
            if in_string:
                if c == '\\':
                    current += c + values_str[i+1]
                    i += 2
                    continue
                elif c == quote_char:
                    in_string = False
                    current += c
                else:
                    current += c
            else:
                if c in ("'", '"'):
                    in_string = True
                    quote_char = c
                    current += c
                elif c == ',':
                    fields.append(current.strip())
                    current = ""
                elif c == ')':
                    fields.append(current.strip())
                    rows.append(fields)
                    i += 1
                    break
                else:
                    current += c
            i += 1
    return rows

def convert_value(v):
    """Convert a MySQL value token to a Python value suitable for sqlite3."""
    if v.upper() == "NULL":
        return None
    if v.startswith("'") and v.endswith("'"):
        inner = v[1:-1]
        return unescape(inner)
    if v.startswith('"') and v.endswith('"'):
        inner = v[1:-1]
        return unescape(inner)
    # Boolean-ish bit literals b'0' / b'1'
    m = re.match(r"b'([01]+)'", v)
    if m:
        return int(m.group(1), 2)
    # Plain number
    try:
        if '.' in v:
            return float(v)
        return int(v)
    except ValueError:
        return v  # fallback: pass as-is

def extract_column_names(line):
    """Extract column names from INSERT INTO `table` (`col1`,`col2`,...) VALUES"""
    m = re.search(r'\(([^)]+)\)\s+VALUES', line)
    if not m:
        return []
    raw = m.group(1)
    return [c.strip().strip('`').strip('"') for c in raw.split(',')]

def main():
    if not os.path.exists(SQL_FILE):
        print(f"ERROR: {SQL_FILE} not found. Run from the project root.")
        sys.exit(1)
    if not os.path.exists(DB_FILE):
        print(f"ERROR: {DB_FILE} not found. Run 'python manage.py migrate' first.")
        sys.exit(1)

    conn = sqlite3.connect(DB_FILE)
    conn.execute("PRAGMA foreign_keys = OFF")
    cur = conn.cursor()

    # Get tables that actually exist in SQLite
    cur.execute("SELECT name FROM sqlite_master WHERE type='table'")
    existing_tables = {row[0] for row in cur.fetchall()}

    with open(SQL_FILE, "r", encoding="utf-8", errors="replace") as f:
        content = f.read()

    # Find all INSERT blocks
    pattern = re.compile(
        r"INSERT INTO `([^`]+)`\s*(\([^)]+\))\s*VALUES\s*([\s\S]+?);",
        re.MULTILINE
    )

    imported = 0
    skipped_tables = set()
    errors = []

    for match in pattern.finditer(content):
        table   = match.group(1)
        col_str = match.group(2)
        vals    = match.group(3)

        if table in SKIP_TABLES:
            skipped_tables.add(table)
            continue
        if table not in existing_tables:
            skipped_tables.add(table)
            continue

        columns = [c.strip().strip('`').strip('"') for c in col_str.strip('()').split(',')]

        # Get columns that actually exist in the SQLite table
        cur.execute(f'PRAGMA table_info("{table}")')
        sqlite_cols = {row[1] for row in cur.fetchall()}
        usable_cols = [c for c in columns if c in sqlite_cols]
        col_indices  = [i for i, c in enumerate(columns) if c in sqlite_cols]

        if not usable_cols:
            skipped_tables.add(table)
            continue

        rows = parse_values_block(vals)
        placeholders = ", ".join("?" * len(usable_cols))
        quoted_cols = ", ".join(f'"{c}"' for c in usable_cols)
        sql = f'INSERT OR IGNORE INTO "{table}" ({quoted_cols}) VALUES ({placeholders})'

        for row in rows:
            try:
                values = [convert_value(row[i]) for i in col_indices if i < len(row)]
                cur.execute(sql, values)
                imported += 1
            except Exception as e:
                errors.append(f"  [{table}] {e} — row: {row[:3]}...")

    conn.commit()
    conn.close()

    print(f"\nDone. {imported} rows imported.")
    if skipped_tables:
        print(f"Skipped tables (not in Django schema): {sorted(skipped_tables)}")
    if errors:
        print(f"\n{len(errors)} row-level errors (first 10):")
        for e in errors[:10]:
            print(e)

if __name__ == "__main__":
    main()
