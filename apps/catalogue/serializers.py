from rest_framework import serializers
from .models import Category, Ingredient, IngredientProduct, Product, Uom
from apps.support.models import Advertisement


class CategorySerializer(serializers.ModelSerializer):
    class Meta:
        model = Category
        fields = ["id", "name", "category_type_id", "description", "sort_by"]


class UomSerializer(serializers.ModelSerializer):
    class Meta:
        model = Uom
        fields = ["id", "name", "code"]


class IngredientSerializer(serializers.ModelSerializer):
    category_name = serializers.CharField(source="category.name", read_only=True)

    class Meta:
        model = Ingredient
        fields = [
            "id", "name", "category_id", "category_name", "unit",
            "price", "discounted_price", "image_url", "stock",
        ]


class ProductIngredientSerializer(serializers.ModelSerializer):
    """Ingredient details as seen from a product — includes through-table quantity/unit."""
    id = serializers.IntegerField(source="ingredient.id")
    name = serializers.CharField(source="ingredient.name")
    category_id = serializers.IntegerField(source="ingredient.category_id")
    unit = serializers.CharField(source="ingredient.unit")
    price = serializers.DecimalField(source="ingredient.price", max_digits=10, decimal_places=2)
    discounted_price = serializers.DecimalField(source="ingredient.discounted_price",
                                                max_digits=10, decimal_places=2, allow_null=True)
    image_url = serializers.CharField(source="ingredient.image_url", allow_null=True)
    stock = serializers.IntegerField(source="ingredient.stock")
    quantity = serializers.DecimalField(max_digits=8, decimal_places=2, allow_null=True)
    serving_unit = serializers.CharField(source="unit", allow_null=True)

    class Meta:
        model = IngredientProduct
        fields = ["id", "name", "category_id", "unit", "price", "discounted_price",
                  "image_url", "stock", "quantity", "serving_unit"]


class ProductSerializer(serializers.ModelSerializer):
    ingredients = ProductIngredientSerializer(
        source="ingredientproduct_set", many=True, read_only=True
    )
    category_ids = serializers.SerializerMethodField()

    class Meta:
        model = Product
        fields = ["id", "name", "description", "price", "discount_price",
                  "stock", "image_url", "rating", "preparation_steps",
                  "category_ids", "ingredients"]

    def get_category_ids(self, obj):
        return list(obj.categories.values_list("id", flat=True))


class AdvertisementSerializer(serializers.ModelSerializer):
    class Meta:
        model = Advertisement
        fields = ["id", "type", "value", "status", "image", "ingredient_ids", "created_at"]
