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

    class Meta:
        db_table = "addresses"


class Cart(TimestampedModel):
    user = models.ForeignKey("accounts.User", on_delete=models.CASCADE,
                             db_column="user_id", related_name="carts")

    class Meta:
        db_table = "carts"


class CartItem(TimestampedModel):
    cart = models.ForeignKey(Cart, on_delete=models.CASCADE, db_column="cart_id", related_name="items")
    product = models.ForeignKey("catalogue.Product", on_delete=models.CASCADE,
                                db_column="product_id", related_name="cart_items")
    quantity = models.IntegerField(default=1)

    class Meta:
        db_table = "cart_items"


class Favorite(TimestampedModel):
    user = models.ForeignKey("accounts.User", on_delete=models.CASCADE,
                             db_column="user_id", related_name="favorites")
    ingredient = models.ForeignKey("catalogue.Ingredient", on_delete=models.CASCADE,
                                   null=True, blank=True, db_column="ingredient_id")
    product = models.ForeignKey("catalogue.Product", on_delete=models.CASCADE,
                                null=True, blank=True, db_column="product_id")

    class Meta:
        db_table = "favorites"
