from rest_framework import serializers
from apps.catalogue.serializers import IngredientSerializer, ProductSerializer
from .models import Order, OrderItem


class IngredientOrderSerializer(serializers.Serializer):
    ingredient_id = serializers.IntegerField()
    quantity = serializers.IntegerField(min_value=1)
    price = serializers.DecimalField(max_digits=10, decimal_places=2, required=False)
    unit = serializers.CharField(required=False)


class OrderItemSerializer(serializers.ModelSerializer):
    ingredient_name = serializers.CharField(source="ingredient.name", read_only=True)
    product_name = serializers.CharField(source="product.name", read_only=True)

    class Meta:
        model = OrderItem
        fields = [
            "id", "ingredient_id", "ingredient_name", "product_id", "product_name",
            "quantity", "price", "unit", "amount", "commision", "vendor_amount",
            "status", "vendor_id", "vendor_at", "created_at",
        ]


class OrderSerializer(serializers.ModelSerializer):
    items = OrderItemSerializer(many=True, read_only=True)

    class Meta:
        model = Order
        fields = [
            "id", "reference", "order_date", "delivery_type", "shipping_fee",
            "service_charge", "vat", "total", "remarks", "meal_prep",
            "status", "address_id", "created_at", "items",
        ]
