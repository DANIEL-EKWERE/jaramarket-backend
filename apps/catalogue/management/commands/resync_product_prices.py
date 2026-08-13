"""Realign each product's price with the cost of its recipe.

A food item's price is meant to be the accumulated cost of its ingredient
rows (that's how the admin product form computes it). Products created
before that rule -- or edited directly -- can drift, and then the customer
app shows the stored price while the order is charged the recipe cost, so
the app total and the confirmation email disagree.

    python manage.py resync_product_prices --dry-run
    python manage.py resync_product_prices
"""
from decimal import ROUND_HALF_UP, Decimal

from django.core.management.base import BaseCommand

from apps.catalogue.models import IngredientProduct, Product


class Command(BaseCommand):
    help = "Set each product's price to the sum of its ingredient rows."

    def add_arguments(self, parser):
        parser.add_argument("--dry-run", action="store_true",
                            help="Report the drift without writing anything")
        parser.add_argument("--product", type=int, help="Only this product id")

    def handle(self, *args, **options):
        products = Product.objects.all()
        if options.get("product"):
            products = products.filter(id=options["product"])

        changed = in_sync = no_recipe = 0
        for product in products:
            links = list(IngredientProduct.objects.filter(product=product)
                         .select_related("ingredient"))
            if not links:
                no_recipe += 1
                continue

            recipe_total = sum(
                (Decimal(str(link.price)) if link.price is not None
                 else Decimal(str(link.ingredient.price)) * Decimal(str(link.quantity or 1)))
                for link in links
            ).quantize(Decimal("0.01"), ROUND_HALF_UP)

            if recipe_total == Decimal(str(product.price)):
                in_sync += 1
                continue

            self.stdout.write(
                f"  {product.name[:34]:34} {product.price:>12} -> {recipe_total:>12}")
            if not options["dry_run"]:
                product.price = recipe_total
                product.save(update_fields=["price"])
            changed += 1

        verb = "would be updated" if options["dry_run"] else "updated"
        self.stdout.write(self.style.SUCCESS(
            f"\n{changed} product(s) {verb}; {in_sync} already in sync; "
            f"{no_recipe} without a recipe (left alone)."))
