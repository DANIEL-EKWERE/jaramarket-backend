from django.db import models
from api.base import SoftDeleteModel, TimestampedModel


class Market(TimestampedModel):
    """A physical market location vendors are stationed at. Orders are routed
    to the market closest to the buyer so delivery stays fast."""
    name = models.CharField(max_length=255)
    address = models.TextField(null=True, blank=True)
    state = models.ForeignKey("geo.State", on_delete=models.SET_NULL, null=True, blank=True,
                              db_column="state_id", related_name="markets")
    lga = models.ForeignKey("geo.Lga", on_delete=models.SET_NULL, null=True, blank=True,
                            db_column="lga_id", related_name="markets")
    latitude = models.DecimalField(max_digits=10, decimal_places=7)
    longitude = models.DecimalField(max_digits=10, decimal_places=7)
    is_active = models.BooleanField(default=True)

    class Meta:
        db_table = "markets"

    def __str__(self):
        return self.name


class Franchise(TimestampedModel):
    name = models.CharField(max_length=255)
    location = models.CharField(max_length=255)
    owner = models.ForeignKey("accounts.User", on_delete=models.CASCADE,
                              db_column="owner_id", null=True, blank=True, related_name="franchises")

    class Meta:
        db_table = "franchises"

    def __str__(self):
        return self.name


class Vendor(SoftDeleteModel):
    user = models.OneToOneField("accounts.User", on_delete=models.CASCADE,
                                db_column="user_id", related_name="vendor_profile")
    franchise = models.ForeignKey(Franchise, on_delete=models.SET_NULL, null=True, blank=True,
                                  db_column="franchise_id", related_name="vendors")
    market = models.ForeignKey(Market, on_delete=models.SET_NULL, null=True, blank=True,
                               db_column="market_id", related_name="vendors")
    is_active = models.BooleanField(default=True)

    class Meta:
        db_table = "vendors"

    def __str__(self):
        return f"{self.user} @ {self.market}" if self.market_id else str(self.user)


class StateRepresentative(TimestampedModel):
    user = models.ForeignKey("accounts.User", on_delete=models.CASCADE,
                             db_column="user_id", related_name="state_representative")
    state = models.ForeignKey("geo.State", on_delete=models.CASCADE,
                              db_column="state_id", related_name="representatives")
    phone = models.CharField(max_length=20, null=True, blank=True)
    lga = models.CharField(max_length=255, null=True, blank=True)
    address = models.TextField(null=True, blank=True)
    is_active = models.BooleanField(default=True)

    class Meta:
        db_table = "state_representatives"

    def __str__(self):
        return f"{self.user} — {self.state}"
