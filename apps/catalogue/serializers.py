from django.conf import settings as _settings
from rest_framework import serializers
from .models import Category, Ingredient, IngredientProduct, Product, Uom
from apps.support.models import Advertisement


def _full_image_url(path):
    """Return a fully-qualified URL for an image stored on S3 (or locally)."""
    if not path:
        return None
    if path.startswith(("http://", "https://")):
        return path
    base = getattr(_settings, "MEDIA_BASE_URL", "").rstrip("/")
    return f"{base}/{path}" if base else path


class CategorySerializer(serializers.ModelSerializer):
    class Meta:
        model = Category
        fields = ["id", "name", "category_type_id", "description", "sort_by"]


class UomSerializer(serializers.ModelSerializer):
    class Meta:
        model = Uom
        fields = ["id", "name", "code"]


class LocationPricedSerializer(serializers.ModelSerializer):
    """Resolves price fields against the request's state_id/lga_id instead
    of reading the base model price -- shared by every serializer that can
    be viewed with a location-specific override (state price for products,
    state/LGA price for ingredients). Mirrors the manual price resolution
    categories_all_products does, so every product/ingredient endpoint
    agrees on which price a customer in a given location actually sees."""

    def _request_location(self):
        request = self.context.get("request")
        if request is not None:
            return request.query_params.get("state_id"), request.query_params.get("lga_id")
        return self.context.get("state_id"), self.context.get("lga_id")

    def _location_cache(self, cache_key):
        return self.context.setdefault(cache_key, {})


class IngredientSerializer(LocationPricedSerializer):
    category_name = serializers.CharField(source="category.name", read_only=True)
    image_url = serializers.SerializerMethodField()
    price = serializers.SerializerMethodField()
    discounted_price = serializers.SerializerMethodField()

    class Meta:
        model = Ingredient
        fields = [
            "id", "name", "category_id", "category_name", "unit",
            "price", "discounted_price", "image_url", "stock",
        ]

    def get_image_url(self, obj):
        return _full_image_url(obj.image_url)

    def _location(self, obj):
        cache = self._location_cache("_ingredient_location_cache")
        if obj.pk not in cache:
            state_id, lga_id = self._request_location()
            cache[obj.pk] = obj.get_price_for_location(lga_id=lga_id, state_id=state_id)
        return cache[obj.pk]

    def get_price(self, obj):
        return str(self._location(obj)["price"])

    def get_discounted_price(self, obj):
        dp = self._location(obj)["discounted_price"]
        return str(dp) if dp else None


class ProductIngredientSerializer(LocationPricedSerializer):
    """Ingredient details as seen from a product — includes through-table quantity/unit."""
    id = serializers.IntegerField(source="ingredient.id")
    name = serializers.CharField(source="ingredient.name")
    category_id = serializers.IntegerField(source="ingredient.category_id")
    unit = serializers.CharField(source="ingredient.unit")
    price = serializers.SerializerMethodField()
    discounted_price = serializers.SerializerMethodField()
    image_url = serializers.SerializerMethodField()

    def get_image_url(self, obj):
        return _full_image_url(obj.ingredient.image_url if obj.ingredient else None)
    stock = serializers.IntegerField(source="ingredient.stock")
    quantity = serializers.DecimalField(max_digits=8, decimal_places=2, allow_null=True)
    serving_unit = serializers.CharField(source="unit", allow_null=True)

    class Meta:
        model = IngredientProduct
        fields = ["id", "name", "category_id", "unit", "price", "discounted_price",
                  "image_url", "stock", "quantity", "serving_unit"]

    def _location(self, obj):
        cache = self._location_cache("_ingredient_location_cache")
        if obj.ingredient_id not in cache:
            state_id, lga_id = self._request_location()
            cache[obj.ingredient_id] = obj.ingredient.get_price_for_location(lga_id=lga_id, state_id=state_id)
        return cache[obj.ingredient_id]

    def get_price(self, obj):
        return str(self._location(obj)["price"])

    def get_discounted_price(self, obj):
        dp = self._location(obj)["discounted_price"]
        return str(dp) if dp else None


class ProductSerializer(LocationPricedSerializer):
    ingredients = ProductIngredientSerializer(
        source="ingredientproduct_set", many=True, read_only=True
    )
    category_ids = serializers.SerializerMethodField()
    image_url = serializers.SerializerMethodField()
    price = serializers.SerializerMethodField()
    discount_price = serializers.SerializerMethodField()
    is_state_price = serializers.SerializerMethodField()

    class Meta:
        model = Product
        fields = ["id", "name", "description", "price", "discount_price", "is_state_price",
                  "stock", "image_url", "rating", "preparation_steps",
                  "category_ids", "ingredients"]

    def _location(self, obj):
        cache = self._location_cache("_product_location_cache")
        if obj.pk not in cache:
            state_id, _lga_id = self._request_location()
            cache[obj.pk] = obj.get_price_for_location(state_id=state_id)
        return cache[obj.pk]

    def get_price(self, obj):
        return str(self._location(obj)["price"])

    def get_discount_price(self, obj):
        dp = self._location(obj)["discount_price"]
        return str(dp) if dp else None

    def get_is_state_price(self, obj):
        return self._location(obj)["price_source"] != "default"

    def get_category_ids(self, obj):
        return list(obj.categories.values_list("id", flat=True))

    def get_image_url(self, obj):
        return _full_image_url(obj.image_url)


class AdvertisementSerializer(serializers.ModelSerializer):
    image = serializers.SerializerMethodField()

    class Meta:
        model = Advertisement
        fields = ["id", "type", "value", "status", "image", "ingredient_ids", "created_at"]

    def get_image(self, obj):
        return _full_image_url(obj.image)
