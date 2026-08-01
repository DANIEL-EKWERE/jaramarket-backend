"""Closest-market order dispatch.

Routes each OrderItem to whichever Market is nearest the buyer's delivery
address and actually has a vendor able to fulfil it, broadcasts the offer to
every eligible vendor stationed there, and escalates outward to the next
market when nobody accepts (timeout, explicit decline by everyone, or no
vendor available at all). Falls back to the manual-assignment queue once
every market has been tried.
"""
import math
from datetime import timedelta

from django.utils import timezone

from apps.accounts.models import Roles, User
from apps.orders.models import MarketOfferResponse, OrderItemMarketAttempt
from apps.vendors.models import Market
from ._base import _setting


def _coverage_threshold():
    return float(_setting("market_coverage_threshold", 0.8) or 0.8)


def _offer_timeout_minutes():
    return int(_setting("market_offer_timeout_minutes", 30) or 30)


def haversine_km(lat1, lng1, lat2, lng2):
    lat1, lng1, lat2, lng2 = (math.radians(float(v)) for v in (lat1, lng1, lat2, lng2))
    d_lat, d_lng = lat2 - lat1, lng2 - lng1
    a = math.sin(d_lat / 2) ** 2 + math.cos(lat1) * math.cos(lat2) * math.sin(d_lng / 2) ** 2
    return 2 * 6371 * math.asin(math.sqrt(a))


class MarketDispatchService:
    def rank_markets(self, latitude, longitude, exclude_ids=()):
        """Active markets nearest -> farthest from (latitude, longitude)."""
        markets = Market.objects.filter(is_active=True).exclude(id__in=exclude_ids)
        return sorted(markets, key=lambda m: haversine_km(latitude, longitude, m.latitude, m.longitude))

    def eligible_vendors(self, market, category_id):
        return User.objects.filter(
            role=Roles.VENDOR, is_active=True,
            vendor_profile__market=market, vendor_profile__is_active=True,
            categories__id=category_id,
        ).distinct()

    def resolve(self, items, address):
        """Assign each item in `items` (an iterable of OrderItem, all with an
        ingredient set) to the closest market that can fulfil it. Items a
        market can't cover recurse into their own independent resolution."""
        items = [i for i in items if i.ingredient_id]
        if not items:
            return
        markets = self.rank_markets(address.latitude, address.longitude)
        if not markets:
            self._send_to_manual_queue(items)
            return

        if len(items) > 1:
            threshold = _coverage_threshold()
            for market in markets:
                covered = [i for i in items
                           if self.eligible_vendors(market, i.ingredient.category_id).exists()]
                if covered and len(covered) / len(items) >= threshold:
                    for item in covered:
                        self.offer_to_market(item, market)
                    leftover = [i for i in items if i not in covered]
                    if leftover:
                        self.resolve(leftover, address)
                    return
            # No market clears the group threshold — resolve each item on its own.
            for item in items:
                self.resolve([item], address)
            return

        # Single item: nearest market with at least one eligible vendor.
        item = items[0]
        for market in markets:
            if self.eligible_vendors(market, item.ingredient.category_id).exists():
                self.offer_to_market(item, market)
                return
        self._send_to_manual_queue(items)

    def offer_to_market(self, item, market):
        from api.notifications import order_item_offer_notification

        eligible = list(self.eligible_vendors(market, item.ingredient.category_id))
        attempt = OrderItemMarketAttempt.objects.create(order_item=item, market=market, status="offered")
        MarketOfferResponse.objects.bulk_create(
            [MarketOfferResponse(attempt=attempt, vendor=v) for v in eligible]
        )
        item.market = market
        item.status = "offered"
        item.offer_expires_at = timezone.now() + timedelta(minutes=_offer_timeout_minutes())
        item.re_assigned = False
        item.save(update_fields=["market", "status", "offer_expires_at", "re_assigned"])
        for vendor in eligible:
            order_item_offer_notification(vendor, item, market)
        return attempt

    def escalate(self, item, reason):
        """reason: 'timeout' | 'all_declined' | 'no_vendor'"""
        attempt = item.market_attempts.filter(status="offered").order_by("-offered_at").first()
        if attempt:
            attempt.status = f"escalated_{reason}"
            attempt.resolved_at = timezone.now()
            attempt.save(update_fields=["status", "resolved_at"])

        if not item.ingredient_id or not item.order or not item.order.address:
            self._send_to_manual_queue([item])
            return

        tried_ids = list(item.market_attempts.values_list("market_id", flat=True))
        address = item.order.address
        for market in self.rank_markets(address.latitude, address.longitude, exclude_ids=tried_ids):
            if self.eligible_vendors(market, item.ingredient.category_id).exists():
                self.offer_to_market(item, market)
                return
        self._send_to_manual_queue([item])

    def _send_to_manual_queue(self, items):
        for item in items:
            item.re_assigned = True
            item.status = "pending"
            item.save(update_fields=["re_assigned", "status"])
