from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('catalogue', '0003_ingredient_is_active_ingredientlgasuspension_and_more'),
    ]

    operations = [
        migrations.AddField(
            model_name='ingredientproduct',
            name='price',
            field=models.DecimalField(decimal_places=2, max_digits=10, null=True, blank=True),
        ),
    ]
