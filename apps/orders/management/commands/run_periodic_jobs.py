"""Every recurring job in one command, so the server needs one cron entry
instead of four.

Each job is isolated: one failing (a bad Paystack key, say) must not stop the
others from running, because the others are what keep paid-for orders moving.

    python manage.py run_periodic_jobs            # the every-few-minutes set
    python manage.py run_periodic_jobs --slow     # add the half-hourly set
    python manage.py run_periodic_jobs --list     # show what would run
"""
import time
import traceback

from django.core.management import call_command
from django.core.management.base import BaseCommand

# (command, description). FREQUENT runs every few minutes; SLOW is heavier and
# talks to a payment provider, so it runs less often.
FREQUENT = [
    ("dispatch_scheduled_orders",
     "Release orders paused outside the dispatch window"),
    ("escalate_stale_offers",
     "Move offers no vendor answered on to the next market"),
    ("escalate_overdue_deliveries",
     "Reclaim items a vendor accepted but never delivered"),
]
SLOW = [
    ("reconcile_pending_payments",
     "Re-check pending Paystack charges and credit wallets"),
]


class Command(BaseCommand):
    help = "Run the scheduled background jobs (intended for a single cron entry)."

    def add_arguments(self, parser):
        parser.add_argument("--slow", action="store_true",
                            help="Also run the less frequent jobs.")
        parser.add_argument("--only", help="Run just this one job by name.")
        parser.add_argument("--list", action="store_true",
                            help="List the jobs without running them.")

    def handle(self, *args, **opts):
        jobs = FREQUENT + (SLOW if opts["slow"] else [])
        if opts["only"]:
            jobs = [j for j in FREQUENT + SLOW if j[0] == opts["only"]]
            if not jobs:
                self.stderr.write(self.style.ERROR(f"No job named {opts['only']!r}."))
                return

        if opts["list"]:
            for name, description in FREQUENT + SLOW:
                tier = "frequent" if (name, description) in FREQUENT else "slow"
                self.stdout.write(f"  {name:32} [{tier}] {description}")
            return

        started = time.time()
        failures = []
        for name, description in jobs:
            self.stdout.write(self.style.MIGRATE_HEADING(f"→ {name}"))
            try:
                call_command(name, verbosity=opts.get("verbosity", 1))
            except Exception as exc:
                failures.append(name)
                self.stderr.write(self.style.ERROR(f"  {name} FAILED: {exc}"))
                self.stderr.write(traceback.format_exc())

        elapsed = time.time() - started
        if failures:
            # Non-zero exit so cPanel's cron email actually flags it.
            self.stderr.write(self.style.ERROR(
                f"{len(failures)} job(s) failed: {', '.join(failures)} "
                f"({elapsed:.1f}s)"))
            raise SystemExit(1)
        self.stdout.write(self.style.SUCCESS(
            f"All {len(jobs)} job(s) completed in {elapsed:.1f}s."))
