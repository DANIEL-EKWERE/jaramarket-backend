"""Re-run dispatch for items that never reached a vendor.

Items fall through to the manual queue when nothing could fulfil them at the
time (no vendor stationed at a market, ingredient had no category, ...).
Once that underlying data is fixed those items stay stuck, because dispatch
only runs at order creation -- this pushes them through again.

    python manage.py redispatch_order --order 53
    python manage.py redispatch_order --reference ORD-28KLHTMHVC9P
    python manage.py redispatch_order --all --dry-run
"""
from django.core.management.base import BaseCommand, CommandError

from apps.orders.models import Order, OrderItem
from api.services.dispatch import MarketDispatchService


class Command(BaseCommand):
    help = "Re-dispatch order items that are still unassigned to any vendor."

    def add_arguments(self, parser):
        parser.add_argument("--order", type=int, help="Order id")
        parser.add_argument("--reference", type=str, help="Order reference")
        parser.add_argument("--all", action="store_true",
                            help="Every order with stuck items")
        parser.add_argument("--dry-run", action="store_true",
                            help="Report what would be dispatched, change nothing")

    def handle(self, *args, **options):
        orders = self._orders(options)
        if not orders:
            self.stdout.write("Nothing to re-dispatch.")
            return

        service = MarketDispatchService()
        moved = stuck = 0
        for order in orders:
            # Only items that never found a vendor; anything already offered
            # or accepted is left alone so live offers aren't disturbed.
            items = list(order.items.filter(ingredient__isnull=False,
                                            vendor__isnull=True,
                                            market__isnull=True,
                                            status__in=("pending", "unavailable")))
            if not items:
                continue
            if not order.address or order.address.latitude is None:
                self.stdout.write(self.style.ERROR(
                    f"Order #{order.reference}: no usable address — skipped."))
                continue

            self.stdout.write(f"\nOrder #{order.reference}: {len(items)} stuck item(s)")
            for item in items:
                category_id = item.ingredient.category_id
                reachable = [m for m in service.rank_markets(order.address.latitude,
                                                             order.address.longitude)
                             if service.eligible_vendors(m, category_id).exists()]
                if not reachable:
                    reason = ("ingredient has no category" if category_id is None
                              else f"no vendor holds category {category_id}")
                    self.stdout.write(self.style.WARNING(
                        f"   - {item.ingredient}: still unroutable ({reason})"))
                    stuck += 1
                    continue
                if options["dry_run"]:
                    self.stdout.write(self.style.SUCCESS(
                        f"   - {item.ingredient}: would go to {reachable[0].name}"))
                else:
                    service.offer_to_market(item, reachable[0])
                    self.stdout.write(self.style.SUCCESS(
                        f"   - {item.ingredient}: offered to {reachable[0].name}"))
                moved += 1

        verb = "would be dispatched" if options["dry_run"] else "dispatched"
        self.stdout.write(self.style.SUCCESS(
            f"\n{moved} item(s) {verb}; {stuck} still unroutable."))

    def _orders(self, options):
        if options.get("order"):
            return Order.objects.filter(id=options["order"]).select_related("address")
        if options.get("reference"):
            return Order.objects.filter(reference=options["reference"]).select_related("address")
        if options.get("all"):
            stuck_ids = (OrderItem.objects
                         .filter(ingredient__isnull=False, vendor__isnull=True,
                                 market__isnull=True, status__in=("pending", "unavailable"))
                         .values_list("order_id", flat=True).distinct())
            return Order.objects.filter(id__in=stuck_ids).select_related("address")
        raise CommandError("Pass --order, --reference, or --all.")
