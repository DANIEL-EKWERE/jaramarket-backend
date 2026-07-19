from django.db import migrations

TIERS = [
    # (min_amount, max_amount, fee_type, value)
    (0, 10000, "flat", 1000),
    (10000, 100000, "percentage", 10),
    (100000, 500000, "percentage", 8),
    (500000, 1000000, "percentage", 7),
    (1000000, None, "percentage", 5),
]


def seed_tiers(apps, schema_editor):
    ServiceFeeTier = apps.get_model("finance", "ServiceFeeTier")
    if ServiceFeeTier.objects.exists():
        return
    for min_amount, max_amount, fee_type, value in TIERS:
        ServiceFeeTier.objects.create(
            min_amount=min_amount, max_amount=max_amount,
            fee_type=fee_type, value=value)


def remove_tiers(apps, schema_editor):
    ServiceFeeTier = apps.get_model("finance", "ServiceFeeTier")
    ServiceFeeTier.objects.filter(
        min_amount__in=[t[0] for t in TIERS]).delete()


class Migration(migrations.Migration):

    dependencies = [
        ("finance", "0002_servicefeetier"),
    ]

    operations = [
        migrations.RunPython(seed_tiers, remove_tiers),
    ]
