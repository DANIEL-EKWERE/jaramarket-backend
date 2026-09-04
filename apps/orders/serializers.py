from rest_framework import serializers
from apps.catalogue.serializers import IngredientSerializer, ProductSerializer, _full_image_url
from .models import Order, OrderItem


class IngredientOrderSerializer(serializers.Serializer):
    ingredient_id = serializers.IntegerField()
    quantity = serializers.IntegerField(min_value=1)
    price = serializers.DecimalField(max_digits=10, decimal_places=2, required=False)
    unit = serializers.CharField(required=False)


class VendorOrderItemSerializer(serializers.ModelSerializer):
    ingredient_name = serializers.CharField(source="ingredient.name", read_only=True, default=None)
    product_name = serializers.CharField(source="product.name", read_only=True, default=None)
    order_reference = serializers.CharField(source="order.reference", read_only=True)
    customer_name = serializers.SerializerMethodField()
    image_url = serializers.SerializerMethodField()
    # The shopper needs the buyer's own instructions, not just the line item.
    order_remarks = serializers.CharField(source="order.remarks", read_only=True, default=None)
    order_audio = serializers.SerializerMethodField()

    class Meta:
        model = OrderItem
        fields = [
            "id", "status", "quantity", "price", "unit", "amount", "vendor_amount",
            "commision", "ingredient_id", "ingredient_name", "product_id", "product_name",
            "order_reference", "customer_name", "image_url", "vendor_id", "vendor_at", "created_at",
            "order_remarks", "order_audio",
        ]

    def get_order_audio(self, obj):
        return _full_image_url(obj.order.audio) if obj.order else None

    def get_customer_name(self, obj):
        user = obj.order.user if obj.order else None
        if not user:
            return None
        full = f"{user.firstname or ''} {user.lastname or ''}".strip()
        return full or user.name or user.email

    def get_image_url(self, obj):
        path = obj.ingredient.image_url if obj.ingredient else (obj.product.image_url if obj.product else None)
        return _full_image_url(path)


class OrderItemSerializer(serializers.ModelSerializer):
    ingredient_name = serializers.CharField(source="ingredient.name", read_only=True)
    product_name = serializers.CharField(source="product.name", read_only=True)
    image_url = serializers.SerializerMethodField()
    is_unavailable = serializers.SerializerMethodField()
    is_forgone = serializers.SerializerMethodField()

    class Meta:
        model = OrderItem
        fields = [
            "id", "ingredient_id", "ingredient_name", "product_id", "product_name",
            "quantity", "price", "unit", "amount", "commision", "vendor_amount",
            "status", "vendor_id", "vendor_at", "created_at", "image_url",
            "is_unavailable", "is_forgone",
        ]

    def get_is_unavailable(self, obj):
        return obj.status == "unavailable"

    def get_is_forgone(self, obj):
        """Dropped by the customer and refunded -- shown struck through rather
        than hidden, so the order history still explains the price change."""
        return obj.status == "cancelled"

    def get_image_url(self, obj):
        """A food order's line item is an ingredient, with the dish kept on
        `product` -- fall back to the dish's image when the ingredient has
        none so the app never has to show a placeholder icon."""
        path = obj.ingredient.image_url if obj.ingredient else None
        if not path and obj.product:
            path = obj.product.image_url
        return _full_image_url(path)


class OrderSerializer(serializers.ModelSerializer):
    items = OrderItemSerializer(many=True, read_only=True)
    audio = serializers.SerializerMethodField()
    progress = serializers.SerializerMethodField()

    class Meta:
        model = Order
        fields = [
            "id", "reference", "order_date", "delivery_type", "shipping_fee",
            "service_charge", "vat", "total", "remarks", "meal_prep", "audio",
            "status", "address_id", "created_at", "items", "progress",
            "scheduled_dispatch_at",
        ]

    def get_audio(self, obj):
        return _full_image_url(obj.audio)

    def get_progress(self, obj):
        """Customer-facing fulfilment stage, resolved server-side so the app
        never has to infer it from raw statuses.

        Items are dispatched and accepted individually, so shopping starts as
        soon as ONE vendor accepts and stays active (with a remaining count)
        until every item is delivered. Admin approval is the QA step that
        completes the order and pays vendors out.
        """
        STAGES = ["placed", "shopping", "vendor_delivered", "admin_approved",
                  "logistics", "delivered"]
        # An order taken after the dispatch cutoff sits at "placed" until
        # markets reopen. Without saying so, that looks identical to an order
        # nobody has picked up.
        scheduled_for = obj.scheduled_dispatch_at
        # A dropped item is refunded and out of the order -- counting it would
        # leave "remaining" permanently above zero and the order never done.
        items = [i for i in obj.items.all() if i.status != "cancelled"]
        total = len(items)
        # 'completed' means QA passed; those items are shopped and delivered too.
        accepted = sum(1 for i in items if i.status in ("processing", "delivered", "completed"))
        delivered = sum(1 for i in items if i.status in ("delivered", "completed"))

        if obj.status == "cancelled":
            stage = "cancelled"
        elif obj.status == "received":
            # The customer confirmed delivery -- terminal.
            stage = "delivered"
        elif obj.status == "in_transit":
            # A rider has the goods and is on the way.
            stage = "logistics"
        elif obj.status == "completed":
            # mark_completed IS the admin approval: vendors are paid and the
            # order is waiting to be assigned/dispatched to a rider.
            stage = "admin_approved"
        elif total and delivered == total:
            stage = "vendor_delivered"
        elif accepted:
            stage = "shopping"
        else:
            stage = "placed"

        return {
            "stage": stage,
            "stage_index": STAGES.index(stage) if stage in STAGES else -1,
            "stages": STAGES,
            "total_items": total,
            "accepted_items": accepted,
            "delivered_items": delivered,
            # What the Shopping badge counts down: still being sourced.
            "remaining_items": max(total - delivered, 0),
            "is_active": obj.status not in ("received", "cancelled"),
            # Drives the customer's "Mark as Received" button: only once
            # admin approval has released the order to logistics.
            "can_mark_received": obj.status in ("completed", "in_transit"),
            # Paused until markets reopen -- the app shows this instead of a
            # stalled "Placed" step with no explanation.
            "is_scheduled": bool(scheduled_for) and obj.status == "pending",
            "resumes_at": scheduled_for,
        }
