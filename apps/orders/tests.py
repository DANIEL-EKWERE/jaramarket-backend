from decimal import Decimal

from django.test import TestCase

from api.services import OrderService
from apps.catalogue.models import Ingredient, IngredientProduct, Product
from apps.support.models import Setting


class CorporatePricingTests(TestCase):
    def setUp(self):
        self.svc = OrderService()
        self.rice = Ingredient.objects.create(name="Rice", price=Decimal("2000"), unit="cup")
        self.oil = Ingredient.objects.create(name="Oil", price=Decimal("1500"), unit="bottle")
        self.other = Ingredient.objects.create(name="Salt", price=Decimal("100"), unit="pack")
        self.jollof = Product.objects.create(name="Jollof Rice", price=Decimal("3500"))
        IngredientProduct.objects.create(product=self.jollof, ingredient=self.rice, quantity=1)
        IngredientProduct.objects.create(product=self.jollof, ingredient=self.oil, quantity=1)
        Setting.objects.create(key="corporate_min_price", value="50000")

    def _corporate(self, *lines):
        return {"product_id": self.jollof.id, "quantity": 1, "shopping_type": "corporate",
                "ingredients": [{"ingredient_id": i, "quantity": q, "price": p} for i, q, p in lines]}

    def test_individual_uses_recipe_prices(self):
        data = {"products": [{"product_id": self.jollof.id, "quantity": 2,
                              "shopping_type": "individual"}]}
        self.assertEqual(self.svc._calculate_subtotal(data), Decimal("7000"))

    def test_corporate_uses_customer_prices(self):
        data = {"products": [self._corporate((self.rice.id, 1, 60000), (self.oil.id, 2, 50000))]}
        self.assertEqual(self.svc._calculate_subtotal(data), Decimal("160000"))
        lines = list(self.svc._product_lines(self.jollof, data["products"][0]))
        self.assertEqual({line[4] for line in lines}, {"corporate"})

    def test_corporate_price_below_minimum_is_rejected(self):
        data = {"products": [self._corporate((self.rice.id, 1, 49999))]}
        with self.assertRaisesMessage(ValueError, "at least"):
            self.svc._calculate_subtotal(data)

    def test_corporate_ingredient_outside_recipe_is_rejected(self):
        data = {"products": [self._corporate((self.other.id, 1, 60000))]}
        with self.assertRaisesMessage(ValueError, "not part of"):
            self.svc._calculate_subtotal(data)

    def test_corporate_without_minimum_still_needs_a_positive_price(self):
        Setting.objects.filter(key="corporate_min_price").delete()
        data = {"products": [self._corporate((self.rice.id, 1, 0))]}
        with self.assertRaises(ValueError):
            self.svc._calculate_subtotal(data)

    def test_corporate_without_ingredient_lines_falls_back_to_recipe(self):
        data = {"products": [{"product_id": self.jollof.id, "quantity": 1,
                              "shopping_type": "corporate"}]}
        self.assertEqual(self.svc._calculate_subtotal(data), Decimal("3500"))
