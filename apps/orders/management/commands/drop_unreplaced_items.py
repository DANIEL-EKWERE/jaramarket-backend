"""Drop unavailable items the customer never replaced in time.

An item nobody could source sits in the order waiting on the customer. If they
neither replace nor drop it, the order can never complete and their money stays
tied up -- so after the replace window (15 minutes by default, see
`unavailable_replace_window_minutes`) it is removed and refunded for them.

Runs from the same cron entry as the rest (see run_periodic_jobs):
    python manage.py drop_unreplaced_items
    python manage.py drop_unreplaced_items --dry-run
"""
from django.core.management.base import BaseCommand
from django.utils import timezone

from api.services import OrderService
from apps.orders.models import OrderItem


class Command(BaseCommand):
    help = "Refund and remove unavailable items past their replace deadline."

    def add_arguments(self, parser):
        parser.add_argument("--dry-run", action="store_true",
                            help="Show what would be dropped without touching anything.")

    def handle(self, *args, **opts):
        due = (OrderItem.objects
               .filter(status="unavailable",
                       replace_deadline__isnull=False,
                       replace_deadline__lte=timezone.now())
               .select_related("order", "order__user", "ingredient", "product"))

        if not due.exists():
            self.stdout.write("No items are past their replace window.")
            return

        service = OrderService()
        dropped = skipped = 0
        for item in due:
            label = (item.ingredient.name if item.ingredient_id
                     else (item.product.name if item.product_id else f"item {item.id}"))
            ref = item.order.reference if item.order_id else "?"
            if opts["dry_run"]:
                self.stdout.write(f"  would drop {label} from #{ref}")
                dropped += 1
                continue
            try:
                _item, refund, _order = service.drop_item(item, automatic=True)
                self.stdout.write(f"  dropped {label} from #{ref} — refunded ₦{refund}")
                dropped += 1
            except ValueError as exc:
                # The commonest case: it was the last item left, so dropping it
                # would empty the order. That's a cancellation decision, not
                # ours to make -- leave it for the customer or an admin.
                self.stdout.write(self.style.WARNING(
                    f"  skipped {label} from #{ref}: {exc}"))
                skipped += 1

        verb = "would be dropped" if opts["dry_run"] else "dropped"
        summary = f"{dropped} item(s) {verb}"
        if skipped:
            summary += f", {skipped} skipped"
        self.stdout.write(self.style.SUCCESS(summary + "."))
