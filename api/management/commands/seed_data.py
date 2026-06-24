"""
Seed reference + catalogue data — Django port of the Laravel seeders
(CountriesSeeder, StateAndLgaSeeder, CategoryTypeSeeder, CategorySeeder,
UomSeeder, IngredientsSeeder, FoodSeeder). Idempotent: safe to re-run.

Run:  python manage.py seed_data
"""
import json
from pathlib import Path

from django.core.management.base import BaseCommand
from django.db import transaction

from api.models import (Category, CategoryType, Country, Ingredient, Lga,
                        Product, State, Uom)

UOMS = [("piece", "Piece"), ("kg", "Kilogram"), ("g", "Gram"), ("l", "Liter"),
        ("ml", "Milliliter"), ("cup", "Cup"), ("tbsp", "Tablespoon"),
        ("tsp", "Teaspoon"), ("por", "Portion")]

CATEGORIES = [(1, "Cabohydrate"), (2, "Protein"), (3, "Vitamin")]  # spelling kept from source

INGREDIENTS = [
    {"id": 1, "name": "Garri", "price": 1800, "discounted_price": 1600, "unit": "kg", "stock": 100,
     "description": "High quality garri for making eba"},
    {"id": 2, "name": "Obu", "price": 800, "discounted_price": None, "unit": "kg", "stock": 50,
     "description": "Fresh black-eyed peas"},
    {"id": 3, "name": "Mango", "price": 300, "discounted_price": 250, "unit": "piece", "stock": 200,
     "description": "Fresh ripe mangoes"},
    {"id": 4, "name": "Apple", "price": 500, "discounted_price": None, "unit": "piece", "stock": 150,
     "description": "Fresh red apples"},
]

# FoodSeeder data -> seeded into the live `products` table (the `foods` table has
# no migration in the original; products is the table the API actually serves).
PRODUCTS = [
    {"id": 1, "name": "Afang", "price": 6800},
    {"id": 2, "name": "Egusi", "price": 9800},
    {"id": 3, "name": "White Soup", "price": 10800},
    {"id": 4, "name": "Rice", "price": 11800},
]


class Command(BaseCommand):
    help = "Seed countries, states/LGAs, category types, categories, UOMs, ingredients, products"

    @transaction.atomic
    def handle(self, *args, **options):
        country, _ = Country.objects.get_or_create(name="Nigeria", defaults={"code": "NG"})

        geo = json.loads((Path(__file__).resolve().parents[2] / "data_geo.json").read_text())
        states_created = lgas_created = 0
        for state_name, lgas in geo.items():
            state, s_new = State.objects.get_or_create(name=state_name, defaults={"country": country})
            states_created += int(s_new)
            for lga_name in lgas:
                _, l_new = Lga.objects.get_or_create(name=lga_name, state=state)
                lgas_created += int(l_new)

        for cid, name in [(1, "Food"), (2, "Vendor")]:
            CategoryType.objects.get_or_create(id=cid, defaults={"name": name})

        for cid, name in CATEGORIES:
            Category.objects.get_or_create(id=cid, defaults={"name": name, "description": "nil"})

        for code, name in UOMS:
            Uom.objects.get_or_create(code=code, defaults={"name": name})

        for ing in INGREDIENTS:
            Ingredient.objects.get_or_create(id=ing["id"], defaults={
                "name": ing["name"], "price": ing["price"],
                "discounted_price": ing["discounted_price"], "unit": ing["unit"],
                "stock": ing["stock"], "description": ing["description"]})

        for pr in PRODUCTS:
            Product.objects.get_or_create(id=pr["id"], defaults={
                "name": pr["name"], "price": pr["price"], "description": "nil"})

        self.stdout.write(self.style.SUCCESS(
            f"Seeded: 1 country, {State.objects.count()} states, {Lga.objects.count()} LGAs, "
            f"{CategoryType.objects.count()} category types, {Category.objects.count()} categories, "
            f"{Uom.objects.count()} UOMs, {Ingredient.objects.count()} ingredients, "
            f"{Product.objects.count()} products."))
