"""Explain why an order's items did or didn't reach any vendor.

Walks the same chain OrderService.create_order -> MarketDispatchService uses
and reports the first broken link, so "the vendor app shows nothing" can be
traced to a concrete cause (deferred dispatch, no markets with coordinates,
no vendor stationed at a market, vendor missing the item's category, ...).

    python manage.py diagnose_dispatch              # newest order
    python manage.py diagnose_dispatch --order 45   # a specific order id
    python manage.py diagnose_dispatch --reference JARA_ORD_123
"""
from django.core.management.base import BaseCommand

from apps.accounts.models import Roles, User
from apps.orders.models import MarketOfferResponse, Order
from apps.vendors.models import Market
from api.services.dispatch import MarketDispatchService, next_dispatch_time


class Command(BaseCommand):
    help = "Diagnose why an order's items are or aren't visible to vendors."

    def add_arguments(self, parser):
        parser.add_argument("--order", type=int, help="Order id")
        parser.add_argument("--reference", type=str, help="Order reference")

    def handle(self, *args, **options):
        ok, warn, bad = self.style.SUCCESS, self.style.WARNING, self.style.ERROR

        # ── Environment-wide preconditions ───────────────────────────────
        self.stdout.write(self.style.MIGRATE_HEADING("\n1. Markets"))
        active = Market.objects.filter(is_active=True)
        usable = active.exclude(latitude__isnull=True).exclude(longitude__isnull=True)
        self.stdout.write(f"   active markets: {active.count()}")
        self.stdout.write(f"   with coordinates (dispatchable): {usable.count()}")
        if not usable.exists():
            self.stdout.write(bad("   ✗ No dispatchable market — every item goes to the manual queue."))

        self.stdout.write(self.style.MIGRATE_HEADING("\n2. Vendors"))
        vendors = User.objects.filter(role=Roles.VENDOR, is_active=True)
        staffed = vendors.filter(vendor_profile__isnull=False,
                                 vendor_profile__is_active=True,
                                 vendor_profile__market__isnull=False)
        self.stdout.write(f"   active vendor accounts: {vendors.count()}")
        self.stdout.write(f"   with an active profile AND a market: {staffed.count()}")
        no_market = vendors.filter(vendor_profile__market__isnull=True).count()
        if no_market:
            self.stdout.write(warn(f"   ! {no_market} vendor(s) have no market — never eligible."))
        no_cats = [v.id for v in staffed if not v.categories.exists()]
        if no_cats:
            self.stdout.write(warn(f"   ! vendor(s) with no categories (never eligible): {no_cats}"))

        # ── The order itself ─────────────────────────────────────────────
        order = self._get_order(options)
        if not order:
            self.stdout.write(bad("\nNo matching order found."))
            return

        self.stdout.write(self.style.MIGRATE_HEADING(
            f"\n3. Order #{order.id} ({order.reference})"))
        self.stdout.write(f"   status={order.status} created={order.created_at}")

        if order.scheduled_dispatch_at:
            self.stdout.write(bad(
                f"   ✗ DEFERRED until {order.scheduled_dispatch_at} — placed outside the "
                f"dispatch window. It only reaches vendors when the "
                f"`dispatch_scheduled_orders` cron runs."))
        else:
            window = next_dispatch_time()
            self.stdout.write(f"   dispatch window right now: "
                              f"{'OPEN' if window is None else f'CLOSED until {window}'}")

        address = order.address
        if not address:
            self.stdout.write(bad("   ✗ No delivery address — dispatch cannot rank markets."))
            return
        if address.latitude is None or address.longitude is None:
            self.stdout.write(bad("   ✗ Address has no coordinates — dispatch cannot rank markets."))
            return
        self.stdout.write(f"   address #{address.id} at ({address.latitude}, {address.longitude})")

        # ── Per-item outcome ─────────────────────────────────────────────
        self.stdout.write(self.style.MIGRATE_HEADING("\n4. Items"))
        service = MarketDispatchService()
        for item in order.items.all():
            label = f"   item {item.id} [{item.ingredient or item.product}]"
            if not item.ingredient_id:
                self.stdout.write(warn(f"{label}: product-only line — never dispatched to vendors."))
                continue

            offers = MarketOfferResponse.objects.filter(attempt__order_item=item)
            pending = offers.filter(decision="pending", attempt__status="offered").count()
            self.stdout.write(
                f"{label}: status={item.status} market={item.market_id} "
                f"vendor={item.vendor_id} offers={offers.count()} awaiting-response={pending}")

            if item.status == "offered" and pending:
                self.stdout.write(ok(f"      ✓ visible to {pending} vendor(s) right now"))
                continue

            # Not visible — show which market *would* have taken it, if any.
            reachable = [
                m for m in service.rank_markets(address.latitude, address.longitude)
                if service.eligible_vendors(m, item.ingredient.category_id).exists()
            ]
            if reachable:
                names = ", ".join(m.name for m in reachable[:3])
                self.stdout.write(warn(
                    f"      ! not offered, though these market(s) could fulfil it: {names}"))
            else:
                self.stdout.write(bad(
                    f"      ✗ no market has an eligible vendor for category "
                    f"{item.ingredient.category_id} ({item.ingredient.category}) — "
                    f"item falls through to the manual queue."))

    def _get_order(self, options):
        if options.get("order"):
            return Order.objects.filter(id=options["order"]).first()
        if options.get("reference"):
            return Order.objects.filter(reference=options["reference"]).first()
        return Order.objects.order_by("-created_at").first()
