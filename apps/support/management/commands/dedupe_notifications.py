"""Collapse duplicate rows in the in-app notification list.

The list the apps show is a straight read of this table, so a row stored twice
is shown twice. This command finds rows that are byte-identical for the same
user -- same type, same payload -- and sent within a few seconds of each
other, keeps the earliest and removes the rest.

Deliberately conservative. Two identical notifications minutes apart can be
genuine: a customer really can be debited the same amount twice. Only a tight
window is safe to treat as a double-send, so --window defaults to 10 seconds
and anything wider has to be asked for explicitly.

    python manage.py dedupe_notifications              # report only
    python manage.py dedupe_notifications --apply      # actually delete
    python manage.py dedupe_notifications --window 60  # widen the window
"""
import json
from collections import defaultdict

from django.core.management.base import BaseCommand

from apps.support.models import Notification


class Command(BaseCommand):
    help = "Find (and optionally remove) duplicate in-app notifications."

    def add_arguments(self, parser):
        parser.add_argument("--apply", action="store_true",
                            help="Delete the duplicates. Without this, only report.")
        parser.add_argument("--window", type=int, default=10,
                            help="Seconds within which identical rows count as "
                                 "a double-send (default 10).")
        parser.add_argument("--user", type=int,
                            help="Restrict to one user id.")

    def handle(self, *args, **opts):
        window = opts["window"]
        qs = Notification.objects.all().order_by("created_at")
        if opts.get("user"):
            qs = qs.filter(notifiable_id=opts["user"])

        # Group by the identity of the message, then walk each group in time
        # order so only consecutive near-simultaneous rows are collapsed.
        groups = defaultdict(list)
        for n in qs.only("id", "notifiable_id", "type", "data", "created_at"):
            groups[(n.notifiable_id, n.type, n.data)].append(n)

        doomed = []
        by_type = defaultdict(int)
        for (_user, type_name, data), rows in groups.items():
            if len(rows) < 2:
                continue
            keeper = rows[0]
            for row in rows[1:]:
                if keeper.created_at and row.created_at:
                    gap = (row.created_at - keeper.created_at).total_seconds()
                else:
                    # Undated rows can't be judged on time; leave them alone.
                    keeper = row
                    continue
                if gap <= window:
                    doomed.append((row, keeper, gap))
                    by_type[type_name.rsplit("\\", 1)[-1]] += 1
                else:
                    # Far enough apart to be a genuine repeat; it becomes the
                    # new reference point for anything following it.
                    keeper = row

        total = Notification.objects.count()
        self.stdout.write(f"notifications: {total}")
        self.stdout.write(f"duplicates within {window}s: {len(doomed)}")
        if by_type:
            self.stdout.write("\n  by type:")
            for name, count in sorted(by_type.items(), key=lambda x: -x[1]):
                self.stdout.write(f"    {name:<32} {count}")

        if doomed:
            self.stdout.write("\n  examples:")
            for row, keeper, gap in doomed[:8]:
                try:
                    payload = json.loads(row.data) if row.data else {}
                except (ValueError, TypeError):
                    payload = {}
                msg = str(payload.get("message", ""))[:52]
                self.stdout.write(
                    f"    user={row.notifiable_id:<5} +{gap:>5.1f}s  "
                    f"{row.type.rsplit(chr(92), 1)[-1]:<28} {msg}")

        if not opts["apply"]:
            self.stdout.write(self.style.WARNING(
                "\nDry run. Nothing deleted. Re-run with --apply to remove them."))
            return

        if not doomed:
            self.stdout.write(self.style.SUCCESS("\nNothing to remove."))
            return

        ids = [row.id for row, _, _ in doomed]
        deleted, _ = Notification.objects.filter(id__in=ids).delete()
        self.stdout.write(self.style.SUCCESS(f"\nRemoved {deleted} duplicate(s)."))
