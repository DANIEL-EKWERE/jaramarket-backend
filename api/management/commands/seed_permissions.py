"""Seed the Permission table from Roles.ALL_PERMISSION_SLUGS.

Mirrors Laravel's database/seeders/PermissionSeeder.php. Run:
    python manage.py seed_permissions
"""
from django.core.management.base import BaseCommand

from api.models import Permission, Roles


class Command(BaseCommand):
    help = "Seed permissions from the role/permission matrix"

    def handle(self, *args, **options):
        created = 0
        for slug in Roles.ALL_PERMISSION_SLUGS:
            name = slug.replace("_", " ").title()
            _, was_created = Permission.objects.get_or_create(
                slug=slug, defaults={"name": name, "group": slug.split("_")[0]})
            created += int(was_created)
        self.stdout.write(self.style.SUCCESS(
            f"Permissions seeded. {created} created, {len(Roles.ALL_PERMISSION_SLUGS)} total."))
