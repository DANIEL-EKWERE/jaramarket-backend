from django.db import models
from api.base import SoftDeleteModel, TimestampedModel


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
    is_active = models.BooleanField(default=True)

    class Meta:
        db_table = "vendors"


class StateRepresentative(TimestampedModel):
    user = models.ForeignKey("accounts.User", on_delete=models.CASCADE,
                             db_column="user_id", related_name="state_representative")
    state = models.ForeignKey("geo.State", on_delete=models.CASCADE,
                              db_column="state_id", related_name="representatives")

    class Meta:
        db_table = "state_representatives"
