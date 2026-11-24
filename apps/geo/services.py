"""Where we deliver, and where people wish we did.

Kept out of the order service so the app can ask the same question BEFORE a
customer fills a basket -- being told at the payment screen that we don't
serve your city is the worst possible moment to find out.
"""
import math

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


# ── Catalogue visibility ────────────────────────────────────────────────────
# Three gates, in order:
#   1. State -- outside a state we operate in, the customer sees nothing.
#   2. LGA   -- inside an operating state, an operating LGA sees everything.
#   3. Radius -- a neighbouring LGA still qualifies if the customer is within
#              the visibility radius of one of our markets, because LGA
#              borders are administrative and supply isn't.
#
# Coordinates are needed for gate 3 only. Without them the verdict falls back
# to the state/LGA answer, which is what older app builds can still supply --
# a strict coordinate requirement would blank the catalogue for every install
# that predates the location work.
#
# The radius is measured to MARKETS, not to LGAs: an LGA is an area with no
# coordinates in this schema, while a market is a real point the food
# actually comes from -- which is also what the customer cares about.

DEFAULT_VISIBILITY_RADIUS_KM = 50


def visibility_radius_km():
    from api.services._base import _setting
    try:
        return float(_setting("catalogue_visibility_radius_km",
                              DEFAULT_VISIBILITY_RADIUS_KM)
                     or DEFAULT_VISIBILITY_RADIUS_KM)
    except (TypeError, ValueError):
        return DEFAULT_VISIBILITY_RADIUS_KM


def _haversine_km(lat1, lng1, lat2, lng2):
    lat1, lng1, lat2, lng2 = (math.radians(float(v)) for v in (lat1, lng1, lat2, lng2))
    d_lat, d_lng = lat2 - lat1, lng2 - lng1
    a = (math.sin(d_lat / 2) ** 2
         + math.cos(lat1) * math.cos(lat2) * math.sin(d_lng / 2) ** 2)
    return 2 * 6371 * math.asin(math.sqrt(a))


def nearest_operating_market(latitude, longitude):
    """(market, km) for the closest market inside an operating area, or
    (None, None). Only markets in a live service area count -- a market we
    have paused shouldn't make its neighbours visible."""
    from apps.vendors.models import Market

    if latitude is None or longitude is None:
        return None, None
    best, best_km = None, None
    markets = Market.objects.filter(is_active=True).select_related("state", "lga")
    for market in markets:
        if market.latitude is None or market.longitude is None:
            continue
        if not is_serviced(market.state_id, market.lga_id):
            continue
        km = _haversine_km(latitude, longitude, market.latitude, market.longitude)
        if best_km is None or km < best_km:
            best, best_km = market, km
    return best, best_km


def catalogue_access(state_id=None, lga_id=None, latitude=None, longitude=None):
    """Can this customer see the catalogue at all?

    Returns a dict the API can hand straight back:
        visible  -- show items or not
        reason   -- why, for the app and for debugging
        message  -- customer-facing copy when hidden
    """
    if not enforcement_enabled():
        # Dormant until someone declares a service area, so turning this on
        # can't blank the storefront for everybody.
        return {"visible": True, "reason": "not_configured", "message": None,
                "radius_km": visibility_radius_km()}

    radius = visibility_radius_km()
    has_coords = not (latitude in (None, "", "null")
                      or longitude in (None, "", "null"))

    # Gate 0 -- we need SOME idea of where they are. Coordinates are what the
    # radius is measured from, but a saved state/LGA is enough to answer the
    # coarse question, so missing coordinates fall back to that rather than
    # blanking the catalogue. Knowing neither is the only dead end: telling
    # someone we haven't launched in their area when we cannot see where they
    # are would be a lie, and they'd have no way to act on it.
    if not has_coords and not state_id:
        return {"visible": False, "reason": "location_required",
                "message": ("Turn on location so we can show you what's "
                            "available near you."),
                "radius_km": radius}

    # Coordinates but no saved address -- a new install that granted GPS
    # before picking a delivery address. The radius alone can answer this,
    # and it answers better than a state picker would.
    if has_coords and not state_id:
        market, km = nearest_operating_market(latitude, longitude)
        if market is not None and km is not None and km <= radius:
            return {"visible": True, "reason": "within_radius", "message": None,
                    "radius_km": radius, "nearest_market": market.name,
                    "distance_km": round(km, 1)}
        return {"visible": False, "reason": "state_not_served",
                "message": _coming_soon_message(), "radius_km": radius,
                "distance_km": round(km, 1) if km is not None else None}

    # Gate 1 -- the state.
    operating_state = ServiceArea.objects.filter(
        is_active=True, state_id=state_id).exists() if state_id else False
    if not operating_state:
        return {"visible": False, "reason": "state_not_served",
                "message": _coming_soon_message(state_id, lga_id),
                "radius_km": radius}

    # Gate 2 -- the LGA, or close enough to a market in one.
    if is_serviced(state_id, lga_id):
        return {"visible": True, "reason": "in_service_area", "message": None,
                "radius_km": radius}

    # Gate 3 -- the boundary allowance, which only coordinates can settle.
    if has_coords:
        market, km = nearest_operating_market(latitude, longitude)
        if market is not None and km is not None and km <= radius:
            return {"visible": True, "reason": "within_radius", "message": None,
                    "radius_km": radius, "nearest_market": market.name,
                    "distance_km": round(km, 1)}
        return {"visible": False, "reason": "lga_not_served",
                "message": _coming_soon_message(state_id, lga_id),
                "radius_km": radius,
                "distance_km": round(km, 1) if km is not None else None}

    # Operating state, non-operating LGA, no coordinates: this customer may
    # well be inside the radius, so "we're not there yet" would be wrong.
    # Ask for location instead -- it is the one thing that can settle it, and
    # it keeps unprovable demand out of the expansion watchlist.
    return {"visible": False, "reason": "location_required_for_radius",
            "message": ("Turn on location so we can check for markets "
                        "near you."),
            "radius_km": radius}


def _coming_soon_message(state_id=None, lga_id=None):
    from .models import Lga, State

    state = State.objects.filter(id=state_id).first() if state_id else None
    lga = Lga.objects.filter(id=lga_id).first() if lga_id else None
    where = " — ".join(p for p in (lga.name if lga else None,
                                   state.name if state else None) if p) or "your area"
    return (f"Jaramarket isn't available in {where} just yet. We've taken note "
            f"of your location and we'll be there soon.")
