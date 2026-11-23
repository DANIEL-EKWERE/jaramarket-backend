"""Give a referral code to every account that has none.

Google/Apple sign-ups were created without one, which left Refer & Earn
with nothing to share for those users. Codes match the format sign-up uses
(8 characters, A-Z and 0-9).
"""
import random
import string

from django.db import migrations
from django.db.models import Q


def backfill(apps, schema_editor):
    User = apps.get_model("accounts", "User")
    taken = set(User.objects.exclude(referral_code__isnull=True)
                .exclude(referral_code="").values_list("referral_code", flat=True))
    for user in User.objects.filter(Q(referral_code__isnull=True) | Q(referral_code="")):
        while True:
            code = "".join(random.choices(string.ascii_uppercase + string.digits, k=8))
            if code not in taken:
                break
        taken.add(code)
        user.referral_code = code
        user.save(update_fields=["referral_code"])


class Migration(migrations.Migration):

    dependencies = [
        ('accounts', '0003_alter_user_role'),
    ]

    operations = [
        migrations.RunPython(backfill, migrations.RunPython.noop),
    ]
