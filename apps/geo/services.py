"""Where we deliver, and where people wish we did.

Kept out of the order service so the app can ask the same question BEFORE a
customer fills a basket -- being told at the payment screen that we don't
serve your city is the worst possible moment to find out.
"""
from django.db import transaction
from django.db.models import F
from django.utils import timezone

from .models import ExpansionRequest, ExpansionRequestUser, ServiceArea


class LocationNotServiced(ValueError):
    """Raised when an order's address is outside our service network.

    Carries the location because the demand has to be recorded by the caller:
    create_order runs in a transaction that this exception aborts, so a write
    made alongside the raise would be rolled back with it.
    """

    def __init__(self, message, state_id=None, lga_id=None, city=None):
        super().__init__(message)
        self.state_id = state_id
        self.lga_id = lga_id
        self.city = city


def enforcement_enabled():
    """Restriction only begins once an admin has declared a service area.

    An empty table means "not configured yet", not "we serve nowhere" -- the
    alternative would turn deploying this feature into a total outage.
    """
    return ServiceArea.objects.filter(is_active=True).exists()


def is_serviced(state_id, lga_id=None):
    """Can we fulfil an order delivered to this state/LGA?"""
    if not enforcement_enabled():
        return True
    if not state_id:
        return False
    areas = ServiceArea.objects.filter(is_active=True, state_id=state_id)
    if not areas.exists():
        return False
    # A state-wide row (no LGA) covers every LGA within it.
    if areas.filter(lga__isnull=True).exists():
        return True
    if not lga_id:
        # The state is only partly covered and we don't know which part, so
        # we can't promise delivery.
        return False
    return areas.filter(lga_id=lga_id).exists()


def unavailable_message(state_name=None, lga_name=None):
    where = " — ".join(p for p in (lga_name, state_name) if p) or "your area"
    return (f"Jaramarket isn't available in {where} yet. We've noted your "
            f"interest and will let you know as soon as we launch there.")


@transaction.atomic
def record_expansion_interest(user, state_id=None, lga_id=None, city=None):
    """Log demand from a place we don't serve.

    Counts attempts and distinct people separately: ten attempts from one
    determined customer is a very different signal from ten customers.
    """
    city = (city or "").strip()[:255] or None
    row, created = ExpansionRequest.objects.get_or_create(
        state_id=state_id, lga_id=lga_id, city=city,
        defaults={"request_count": 0, "unique_users": 0,
                  "last_requested_at": timezone.now()},
    )
    ExpansionRequest.objects.filter(pk=row.pk).update(
        request_count=F("request_count") + 1, last_requested_at=timezone.now())

    if user is not None and getattr(user, "id", None):
        _, is_new_user = ExpansionRequestUser.objects.get_or_create(
            request=row, user=user)
        if is_new_user:
            ExpansionRequest.objects.filter(pk=row.pk).update(
                unique_users=F("unique_users") + 1)
    row.refresh_from_db()
    return row
