"""Re-apply each staff account's role default permissions.

Permissions are stored per user, so an account created before its role had
defaults (or before the role existed) ends up with none -- it can log in but
every dashboard block is gated on has_perm_slug(), so the page renders empty.

    python manage.py sync_staff_permissions --dry-run
    python manage.py sync_staff_permissions
    python manage.py sync_staff_permissions --role state_representative
"""
from django.core.management.base import BaseCommand

from apps.accounts.models import Roles, User


class Command(BaseCommand):
    help = "Sync staff users' permissions to their role defaults."

    def add_arguments(self, parser):
        parser.add_argument("--role", type=str, help="Only this role")
        parser.add_argument("--only-empty", action="store_true",
                            help="Only accounts that currently have no permissions")
        parser.add_argument("--dry-run", action="store_true",
                            help="Report what would change, write nothing")
        parser.add_argument("--force", action="store_true",
                            help="Also reset accounts whose permissions were "
                                 "deliberately customised in the dashboard")

    def handle(self, *args, **options):
        qs = User.objects.filter(role__in=Roles.ADMIN_ROLES + [Roles.QA], deleted_at__isnull=True)
        if options.get("role"):
            qs = qs.filter(role=options["role"])

        changed = ok = skipped = 0
        for user in qs.order_by("role", "email"):
            current = set(user.permissions_m2m.values_list("slug", flat=True))
            expected = set(Roles.default_permissions(user.role))
            if options["only_empty"] and current:
                continue
            if current == expected:
                ok += 1
                continue
            # A non-empty set that differs from the defaults was chosen by
            # hand in the permission picker -- don't silently undo that.
            if current and not options["force"]:
                skipped += 1
                self.stdout.write(self.style.WARNING(
                    f"  {user.email:38} {user.role:22} customised — skipped (use --force)"))
                continue

            missing = sorted(expected - current)
            extra = sorted(current - expected)
            detail = []
            if missing:
                detail.append(f"+{len(missing)}")
            if extra:
                detail.append(f"-{len(extra)}")
            self.stdout.write(
                f"  {user.email:38} {user.role:22} {' '.join(detail)}")
            if not options["dry_run"]:
                user.sync_default_permissions()
            changed += 1

        verb = "would be updated" if options["dry_run"] else "updated"
        summary = f"\n{changed} account(s) {verb}; {ok} already correct."
        if skipped:
            summary += f" {skipped} customised and left alone."
        self.stdout.write(self.style.SUCCESS(summary))
