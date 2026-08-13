from django.db import models
from api.base import TimestampedModel


class Address(TimestampedModel):
    user = models.ForeignKey("accounts.User", on_delete=models.CASCADE,
                             db_column="user_id", related_name="addresses")
    country = models.ForeignKey("geo.Country", on_delete=models.SET_NULL, null=True, blank=True,
                                db_column="country_id", related_name="addresses")
    state = models.ForeignKey("geo.State", on_delete=models.SET_NULL, null=True, blank=True,
                              db_column="state_id", related_name="addresses")
    lga = models.ForeignKey("geo.Lga", on_delete=models.SET_NULL, null=True, blank=True,
                            db_column="lga_id", related_name="addresses")
    contact_address = models.TextField(null=True, blank=True)
    phone_number = models.CharField(max_length=255, null=True, blank=True)
    is_default = models.BooleanField(default=False)
    latitude = models.DecimalField(max_digits=10, decimal_places=7, null=True, blank=True)
    longitude = models.DecimalField(max_digits=10, decimal_places=7, null=True, blank=True)

    class Meta:
        db_table = "addresses"

    def __str__(self):
        where = self.contact_address or ", ".join(
            p.name for p in (self.lga, self.state) if p) or "no address"
        return f"{self.user} — {where}"


class Cart(TimestampedModel):
    user = models.ForeignKey("accounts.User", on_delete=models.CASCADE,
                             db_column="user_id", related_name="carts")

    class Meta:
        db_table = "carts"

    def __str__(self):
        return f"Cart of {self.user}"


class CartItem(TimestampedModel):
    cart = models.ForeignKey(Cart, on_delete=models.CASCADE, db_column="cart_id", related_name="items")
    product = models.ForeignKey("catalogue.Product", on_delete=models.CASCADE,
                                db_column="product_id", related_name="cart_items")
    quantity = models.IntegerField(default=1)

    class Meta:
        db_table = "cart_items"

    def __str__(self):
        return f"{self.product} x{self.quantity}"


class Favorite(TimestampedModel):
    user = models.ForeignKey("accounts.User", on_delete=models.CASCADE,
                             db_column="user_id", related_name="favorites")
    ingredient = models.ForeignKey("catalogue.Ingredient", on_delete=models.CASCADE,
                                   null=True, blank=True, db_column="ingredient_id")
    product = models.ForeignKey("catalogue.Product", on_delete=models.CASCADE,
                                null=True, blank=True, db_column="product_id")

    class Meta:
        db_table = "favorites"

    def __str__(self):
        return f"{self.user} ♥ {self.product or self.ingredient}"
