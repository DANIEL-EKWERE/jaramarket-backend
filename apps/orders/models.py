from django.db import models
from api.base import SoftDeleteModel, TimestampedModel


class Order(SoftDeleteModel):
    order_date = models.DateTimeField()
    user = models.ForeignKey("accounts.User", on_delete=models.CASCADE,
                             db_column="user_id", related_name="orders")
    address = models.ForeignKey("customers.Address", on_delete=models.CASCADE,
                                db_column="address_id", null=True, blank=True)
    delivery_type = models.CharField(max_length=255)
    shipping_fee = models.DecimalField(max_digits=10, decimal_places=2, default=0)
    service_charge = models.DecimalField(max_digits=10, decimal_places=2, default=0)
    vat = models.DecimalField(max_digits=10, decimal_places=2, default=0)
    total = models.DecimalField(max_digits=10, decimal_places=2)
    remarks = models.TextField(null=True, blank=True)
    audio = models.CharField(max_length=255, null=True, blank=True)
    status = models.CharField(max_length=255, default="pending")
    meal_prep = models.TextField(null=True, blank=True)
    reference = models.CharField(max_length=255, null=True, blank=True)

    class Meta:
        db_table = "orders"


class OrderItem(TimestampedModel):
    order = models.ForeignKey(Order, on_delete=models.CASCADE,
                              db_column="order_id", related_name="items")
    product = models.ForeignKey("catalogue.Product", on_delete=models.DO_NOTHING,
                                null=True, blank=True, db_column="product_id")
    ingredient = models.ForeignKey("catalogue.Ingredient", on_delete=models.DO_NOTHING,
                                   null=True, blank=True, db_column="ingredient_id")
    quantity = models.IntegerField()
    price = models.DecimalField(max_digits=10, decimal_places=2)
    unit = models.CharField(max_length=255, null=True, blank=True)
    vendor = models.ForeignKey("accounts.User", on_delete=models.CASCADE, null=True, blank=True,
                               db_column="vendor_id", related_name="vendor_order_items")
    vendor_at = models.DateTimeField(null=True, blank=True)
    status = models.CharField(max_length=255, default="pending")
    assurance_user = models.ForeignKey("accounts.User", on_delete=models.CASCADE, null=True, blank=True,
                                       db_column="assurance_user_id", related_name="qa_order_items")
    assurance_at = models.DateTimeField(null=True, blank=True)
    pass_quality_assurance = models.BooleanField(null=True, blank=True)
    remark = models.TextField(null=True, blank=True)
    re_assigned = models.BooleanField(null=True, blank=True)
    amount = models.DecimalField(max_digits=10, decimal_places=2, default=0)
    vendor_amount = models.DecimalField(max_digits=10, decimal_places=2, default=0)
    commision = models.DecimalField(max_digits=10, decimal_places=2, default=0)
    referral = models.DecimalField(max_digits=10, decimal_places=2, default=0)
    referral_user = models.ForeignKey("accounts.User", on_delete=models.SET_NULL, null=True, blank=True,
                                      db_column="referral_id", related_name="referral_order_items")

    class Meta:
        db_table = "order_items"


class OrderItemLog(TimestampedModel):
    STATUS_CHOICES = [("accepted", "accepted"), ("processing", "processing"),
                      ("completed", "completed"), ("pending", "pending"), ("cancelled", "cancelled")]
    order_item = models.ForeignKey(OrderItem, on_delete=models.CASCADE,
                                   db_column="order_item_id", related_name="logs")
    vendor = models.ForeignKey("accounts.User", on_delete=models.CASCADE, db_column="vendor_id")
    status = models.CharField(max_length=20, choices=STATUS_CHOICES)
    changed_at = models.DateTimeField()

    class Meta:
        db_table = "order_item_logs"
