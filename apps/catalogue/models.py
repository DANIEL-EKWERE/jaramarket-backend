from django.db import models
from api.base import SoftDeleteModel, TimestampedModel


class CategoryType(TimestampedModel):
    name = models.CharField(max_length=255)

    class Meta:
        db_table = "category_types"

    def __str__(self):
        return self.name


class Category(SoftDeleteModel):
    name = models.CharField(max_length=255)
    category_type = models.ForeignKey(CategoryType, on_delete=models.CASCADE, db_column="category_type_id",
                                      default=1, db_constraint=False, related_name="categories")
    description = models.TextField(null=True, blank=True)
    sort_by = models.IntegerField(default=100)
    users = models.ManyToManyField("accounts.User", through="CategoryUser", related_name="categories")

    class Meta:
        db_table = "categories"

    def __str__(self):
        return self.name


class CategoryUser(TimestampedModel):
    user = models.ForeignKey("accounts.User", on_delete=models.CASCADE, db_column="user_id")
    category = models.ForeignKey(Category, on_delete=models.CASCADE, db_column="category_id")

    class Meta:
        db_table = "category_user"


class Product(TimestampedModel):
    name = models.CharField(max_length=255)
    description = models.TextField(null=True, blank=True)
    price = models.DecimalField(max_digits=10, decimal_places=2, default=0)
    discount_price = models.DecimalField(max_digits=10, decimal_places=2, null=True, blank=True)
    stock = models.IntegerField(default=0)
    preparation_steps = models.TextField(null=True, blank=True)
    rating = models.FloatField(null=True, blank=True)
    image_url = models.CharField(max_length=255, null=True, blank=True)
    categories = models.ManyToManyField(Category, through="CategoryProduct", related_name="products")

    class Meta:
        db_table = "products"

    def __str__(self):
        return self.name

    def get_price_for_location(self, state_id=None):
        if state_id:
            sp = self.state_prices.filter(state_id=state_id).first()
            if sp:
                return {"price": sp.price, "discount_price": sp.discount_price, "price_source": "state"}
        return {"price": self.price, "discount_price": self.discount_price, "price_source": "default"}


class CategoryProduct(TimestampedModel):
    category = models.ForeignKey(Category, on_delete=models.CASCADE, db_column="category_id")
    product = models.ForeignKey(Product, on_delete=models.CASCADE, db_column="product_id")

    class Meta:
        db_table = "category_product"


class Step(TimestampedModel):
    product = models.ForeignKey(Product, on_delete=models.CASCADE, db_column="product_id", related_name="steps")
    description = models.TextField()

    class Meta:
        db_table = "steps"


class Uom(TimestampedModel):
    code = models.CharField(max_length=255)
    name = models.CharField(max_length=255)

    class Meta:
        db_table = "uoms"

    def __str__(self):
        return self.name


class Ingredient(TimestampedModel):
    category = models.ForeignKey(Category, on_delete=models.CASCADE, null=True, blank=True,
                                 db_column="category_id", db_constraint=False, related_name="ingredients")
    name = models.CharField(max_length=255)
    description = models.TextField(null=True, blank=True)
    price = models.DecimalField(max_digits=10, decimal_places=2)
    discounted_price = models.DecimalField(max_digits=10, decimal_places=2, null=True, blank=True)
    unit = models.CharField(max_length=20)
    stock = models.IntegerField(default=0)
    image_url = models.CharField(max_length=255, null=True, blank=True)
    products = models.ManyToManyField(Product, through="IngredientProduct", related_name="ingredients")

    class Meta:
        db_table = "ingredients"

    def __str__(self):
        return self.name

    @property
    def uom(self):
        return Uom.objects.filter(code=self.unit).first()

    def get_price_for_location(self, lga_id=None, state_id=None):
        if lga_id:
            lp = self.lga_prices.filter(lga_id=lga_id).first()
            if lp:
                return {"price": lp.price, "discounted_price": lp.discounted_price, "price_source": "lga"}
        if state_id:
            sp = self.state_prices.filter(state_id=state_id).first()
            if sp:
                return {"price": sp.price, "discounted_price": sp.discounted_price, "price_source": "state"}
        return {"price": self.price, "discounted_price": self.discounted_price, "price_source": "default"}


class IngredientProduct(TimestampedModel):
    product = models.ForeignKey(Product, on_delete=models.CASCADE, db_column="product_id")
    ingredient = models.ForeignKey(Ingredient, on_delete=models.CASCADE, db_column="ingredient_id")
    quantity = models.DecimalField(max_digits=8, decimal_places=2, null=True, blank=True)
    unit = models.CharField(max_length=255, null=True, blank=True)

    class Meta:
        db_table = "ingredient_product"


class ProductStatePrice(TimestampedModel):
    product = models.ForeignKey(Product, on_delete=models.CASCADE, db_column="product_id", related_name="state_prices")
    state = models.ForeignKey("geo.State", on_delete=models.CASCADE, db_column="state_id")
    price = models.DecimalField(max_digits=10, decimal_places=2)
    discount_price = models.DecimalField(max_digits=10, decimal_places=2, null=True, blank=True)

    class Meta:
        db_table = "product_state_prices"
        unique_together = (("product", "state"),)


class IngredientStatePrice(TimestampedModel):
    ingredient = models.ForeignKey(Ingredient, on_delete=models.CASCADE, db_column="ingredient_id", related_name="state_prices")
    state = models.ForeignKey("geo.State", on_delete=models.CASCADE, db_column="state_id")
    price = models.DecimalField(max_digits=10, decimal_places=2)
    discounted_price = models.DecimalField(max_digits=10, decimal_places=2, null=True, blank=True)

    class Meta:
        db_table = "ingredient_state_prices"
        unique_together = (("ingredient", "state"),)


class IngredientLgaPrice(TimestampedModel):
    ingredient = models.ForeignKey(Ingredient, on_delete=models.CASCADE, db_column="ingredient_id", related_name="lga_prices")
    lga = models.ForeignKey("geo.Lga", on_delete=models.CASCADE, db_column="lga_id")
    price = models.DecimalField(max_digits=10, decimal_places=2)
    discounted_price = models.DecimalField(max_digits=10, decimal_places=2, null=True, blank=True)

    class Meta:
        db_table = "ingredient_lga_prices"
        unique_together = (("ingredient", "lga"),)
