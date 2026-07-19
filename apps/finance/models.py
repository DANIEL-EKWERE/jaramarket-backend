from django.db import models
from api.base import SoftDeleteModel, TimestampedModel


class Wallet(TimestampedModel):
    user = models.ForeignKey("accounts.User", on_delete=models.CASCADE,
                             db_column="user_id", related_name="wallet_set")
    balance = models.DecimalField(max_digits=15, decimal_places=2, default=0)

    class Meta:
        db_table = "wallets"


class TransactionLog(TimestampedModel):
    SMALLEST_CURRENCY_UNIT = 100
    account_owner_type = models.CharField(max_length=255)
    account_owner_id = models.BigIntegerField()
    owner_type = models.CharField(max_length=255, null=True, blank=True)
    owner_id = models.BigIntegerField(null=True, blank=True)
    reference = models.CharField(max_length=255)
    transaction_type = models.CharField(max_length=255)
    amount = models.FloatField()
    old_balance = models.FloatField()
    new_balance = models.FloatField()
    status = models.CharField(max_length=255, null=True, blank=True)
    currency = models.CharField(max_length=255, null=True, blank=True)
    is_refund = models.BooleanField(default=False)
    has_refund = models.BooleanField(null=True, blank=True)
    wallet = models.ForeignKey(Wallet, on_delete=models.DO_NOTHING, null=True, blank=True,
                               db_column="wallet_id")
    comment = models.TextField(null=True, blank=True)

    class Meta:
        db_table = "transaction_logs"

    @property
    def amount_major(self):
        return self.amount / self.SMALLEST_CURRENCY_UNIT


class PaymentLog(TimestampedModel):
    STATUS_CHOICES = [("pending", "pending"), ("success", "success"), ("failed", "failed")]
    txn_ref = models.CharField(max_length=255, unique=True, db_index=True)
    authorization_url = models.CharField(max_length=255, null=True, blank=True)
    amount = models.IntegerField()
    meta = models.JSONField(null=True, blank=True)
    gateway_response = models.CharField(max_length=255, null=True, blank=True)
    status = models.CharField(max_length=20, choices=STATUS_CHOICES, default="pending")
    transaction_mode = models.CharField(max_length=255, null=True, blank=True)
    transaction_owner_id = models.BigIntegerField(null=True, blank=True)
    transaction_owner_type = models.CharField(max_length=255, null=True, blank=True)
    transaction_initiator_id = models.BigIntegerField(null=True, blank=True)
    transaction_initiator_type = models.CharField(max_length=255, null=True, blank=True)
    approved_by = models.BigIntegerField(null=True, blank=True)
    provider = models.CharField(max_length=255, null=True, blank=True)
    plan = models.CharField(max_length=255, null=True, blank=True)

    class Meta:
        db_table = "payment_logs"


class PaymentNow(SoftDeleteModel):
    user = models.ForeignKey("accounts.User", on_delete=models.CASCADE,
                             db_column="user_id", related_name="payments")
    order = models.ForeignKey("orders.Order", on_delete=models.SET_NULL,
                              null=True, blank=True, db_column="order_id")
    amount = models.DecimalField(max_digits=10, decimal_places=2)
    payment_method = models.CharField(max_length=255)
    transaction_id = models.CharField(max_length=255, null=True, blank=True)
    status = models.CharField(max_length=255, default="pending")
    notes = models.TextField(null=True, blank=True)
    metadata = models.JSONField(null=True, blank=True)

    class Meta:
        db_table = "payments"


class Bank(SoftDeleteModel):
    name = models.CharField(max_length=255)
    code = models.CharField(max_length=255, unique=True)
    slug = models.CharField(max_length=255, null=True, blank=True)

    class Meta:
        db_table = "banks"

    def __str__(self):
        return self.name


class BankAccount(TimestampedModel):
    owner_type = models.CharField(max_length=255)
    owner_id = models.BigIntegerField()
    bank_code = models.CharField(max_length=255)
    account_number = models.CharField(max_length=255)
    bank_name = models.CharField(max_length=255)
    account_name = models.CharField(max_length=255)

    class Meta:
        db_table = "bank_accounts"


class Transfer(TimestampedModel):
    reference = models.CharField(max_length=255)
    recipient_code = models.CharField(max_length=255)
    amount = models.IntegerField()
    owner_type = models.CharField(max_length=255)
    owner_id = models.BigIntegerField()
    bank_code = models.CharField(max_length=255)
    account_number = models.CharField(max_length=255)
    bank_name = models.CharField(max_length=255)
    account_name = models.CharField(max_length=255)
    status = models.CharField(max_length=255, default="pending")
    failures = models.IntegerField(default=0)

    class Meta:
        db_table = "transfers"


class Commission(SoftDeleteModel):
    min_amount = models.DecimalField(max_digits=15, decimal_places=2, default=0)
    max_amount = models.DecimalField(max_digits=15, decimal_places=2, null=True, blank=True)
    percentage = models.DecimalField(max_digits=5, decimal_places=2)

    class Meta:
        db_table = "commissions"


class ServiceFeeTier(SoftDeleteModel):
    """Customer-facing order service fee bands. A tier applies when
    min_amount < order_subtotal <= max_amount (max_amount null = no upper
    bound, i.e. the top tier). The lower tier owns its own boundary value."""
    FLAT = "flat"
    PERCENTAGE = "percentage"
    FEE_TYPE_CHOICES = [(FLAT, "flat"), (PERCENTAGE, "percentage")]

    min_amount = models.DecimalField(max_digits=15, decimal_places=2, default=0)
    max_amount = models.DecimalField(max_digits=15, decimal_places=2, null=True, blank=True)
    fee_type = models.CharField(max_length=20, choices=FEE_TYPE_CHOICES, default=PERCENTAGE)
    value = models.DecimalField(max_digits=10, decimal_places=2)

    class Meta:
        db_table = "service_fee_tiers"
