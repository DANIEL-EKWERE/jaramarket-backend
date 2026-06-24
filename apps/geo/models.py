from django.db import models
from api.base import TimestampedModel


class Country(TimestampedModel):
    name = models.CharField(max_length=255)
    code = models.CharField(max_length=10, null=True, blank=True)
    currency = models.CharField(max_length=50, null=True, blank=True)
    currency_sym = models.CharField(max_length=10, null=True, blank=True)
    vat = models.DecimalField(max_digits=5, decimal_places=2, default=0)

    class Meta:
        db_table = "countries"

    def __str__(self):
        return self.name


class State(TimestampedModel):
    name = models.CharField(max_length=255)
    country = models.ForeignKey(Country, on_delete=models.CASCADE, db_column="country_id",
                                null=True, blank=True, related_name="states")

    class Meta:
        db_table = "states"

    def __str__(self):
        return self.name


class Lga(TimestampedModel):
    name = models.CharField(max_length=255)
    state = models.ForeignKey(State, on_delete=models.CASCADE, db_column="state_id",
                              null=True, blank=True, related_name="lgas")

    class Meta:
        db_table = "lgas"

    def __str__(self):
        return self.name
