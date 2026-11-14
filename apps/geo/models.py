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


class ServiceArea(TimestampedModel):
    """Where Jaramarket will accept orders.

    Deliberately an ALLOWLIST with a safe empty state: while the table holds
    no active rows the whole country is served, so deploying this cannot
    silently stop every order. Add one row and only those places are served
    from then on.

    A row with no LGA covers the entire state; adding LGA rows narrows it to
    exactly those LGAs.
    """
    state = models.ForeignKey(State, on_delete=models.CASCADE, db_column="state_id",
                              related_name="service_areas")
    lga = models.ForeignKey(Lga, on_delete=models.CASCADE, db_column="lga_id",
                            null=True, blank=True, related_name="service_areas")
    is_active = models.BooleanField(default=True)
    note = models.CharField(max_length=255, null=True, blank=True)

    class Meta:
        db_table = "service_areas"
        unique_together = (("state", "lga"),)

    def __str__(self):
        where = f"{self.state.name} → {self.lga.name}" if self.lga_id else f"{self.state.name} (all LGAs)"
        return where if self.is_active else f"{where} (inactive)"


class ExpansionRequest(TimestampedModel):
    """A customer we had to turn away, and where they were.

    Demand from places we don't serve yet is the most direct signal of where
    to open next, and it is only visible if we record it at the moment of
    refusal -- the customer rarely tells us twice.
    """
    state = models.ForeignKey(State, on_delete=models.SET_NULL, null=True, blank=True,
                              db_column="state_id", related_name="expansion_requests")
    lga = models.ForeignKey(Lga, on_delete=models.SET_NULL, null=True, blank=True,
                            db_column="lga_id", related_name="expansion_requests")
    # Free text from the address, kept for places our geo tables don't name.
    city = models.CharField(max_length=255, null=True, blank=True)
    user = models.ForeignKey("accounts.User", on_delete=models.SET_NULL, null=True, blank=True,
                             db_column="user_id", related_name="expansion_requests")
    request_count = models.IntegerField(default=1)
    last_requested_at = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = "expansion_requests"

    def __str__(self):
        where = self.lga.name if self.lga_id else (self.city or "Unknown")
        state = f", {self.state.name}" if self.state_id else ""
        return f"{where}{state} — {self.request_count} request(s)"


class ServiceArea(TimestampedModel):
    """Where Jaramarket actually operates.

    Enforcement is deliberately OPT-IN: while this table is empty every
    location is treated as serviced, so adding the feature cannot silently
    lock out the whole customer base. The moment an admin adds the first
    row, ordering is restricted to what's listed.

    A row with no LGA covers the whole state; add LGA rows to narrow it.
    """
    state = models.ForeignKey(State, on_delete=models.CASCADE, db_column="state_id",
                              related_name="service_areas")
    lga = models.ForeignKey(Lga, on_delete=models.CASCADE, db_column="lga_id",
                            null=True, blank=True, related_name="service_areas")
    is_active = models.BooleanField(default=True)
    note = models.CharField(max_length=255, null=True, blank=True)

    class Meta:
        db_table = "service_areas"
        unique_together = (("state", "lga"),)
        ordering = ["state__name", "lga__name"]

    def __str__(self):
        where = f"{self.state.name} — {self.lga.name}" if self.lga_id else f"{self.state.name} (all LGAs)"
        return where if self.is_active else f"{where} (paused)"


class ExpansionRequest(TimestampedModel):
    """A customer who tried to order from somewhere we don't serve yet.

    Demand evidence for deciding where to open next: one row per location,
    with a counter rather than a row per attempt, so a single determined
    user can't look like a crowd.
    """
    state = models.ForeignKey(State, on_delete=models.SET_NULL, null=True, blank=True,
                              db_column="state_id", related_name="expansion_requests")
    lga = models.ForeignKey(Lga, on_delete=models.SET_NULL, null=True, blank=True,
                            db_column="lga_id", related_name="expansion_requests")
    # Free text from the address, for when the location isn't a known LGA.
    city = models.CharField(max_length=255, null=True, blank=True)
    request_count = models.PositiveIntegerField(default=1)
    unique_users = models.PositiveIntegerField(default=1)
    last_requested_at = models.DateTimeField(null=True, blank=True)

    class Meta:
        db_table = "expansion_requests"
        unique_together = (("state", "lga", "city"),)
        ordering = ["-request_count", "-last_requested_at"]

    def __str__(self):
        parts = [p for p in (self.lga.name if self.lga_id else None,
                             self.state.name if self.state_id else None,
                             self.city) if p]
        return f"{' — '.join(parts) or 'Unknown'} ({self.request_count} request(s))"


class ExpansionRequestUser(TimestampedModel):
    """Who asked, so the unique-user count is real rather than a guess."""
    request = models.ForeignKey(ExpansionRequest, on_delete=models.CASCADE,
                                db_column="request_id", related_name="requesters")
    user = models.ForeignKey("accounts.User", on_delete=models.CASCADE,
                             db_column="user_id", related_name="expansion_requests")

    class Meta:
        db_table = "expansion_request_users"
        unique_together = (("request", "user"),)

    def __str__(self):
        return f"{self.user} wants {self.request_id}"
