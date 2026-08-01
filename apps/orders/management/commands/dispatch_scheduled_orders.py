"""Dispatch orders that were deferred because they were placed outside the
09:00-18:30 dispatch window (see MarketDispatchService.next_dispatch_time,
called from OrderService.create_order). Same precedent as
escalate_stale_offers.py -- a plain management command meant to be triggered
by an external scheduler (e.g. a Render Cron Job), since this project has no
Celery Beat schedule.

Run every few minutes:
    python manage.py dispatch_scheduled_orders
"""
from django.core.management.base import BaseCommand
from django.utils import timezone

from apps.orders.models import Order
from api.services.dispatch import MarketDispatchService


class Command(BaseCommand):
    help = "Dispatch orders whose scheduled_dispatch_at has arrived to the closest eligible market."

    def handle(self, *args, **options):
        due = Order.objects.filter(
            scheduled_dispatch_at__isnull=False,
            scheduled_dispatch_at__lte=timezone.now(),
        ).select_related("address")
        if not due.exists():
            self.stdout.write("No scheduled orders are due.")
            return

        service = MarketDispatchService()
        dispatched = 0
        for order in due:
            items = list(order.items.filter(ingredient__isnull=False, status="pending"))
            self.stdout.write(f"Order #{order.reference}: dispatching {len(items)} item(s).")
            if items and order.address:
                service.resolve(items, order.address)
            order.scheduled_dispatch_at = None
            order.save(update_fields=["scheduled_dispatch_at"])
            dispatched += 1

        self.stdout.write(self.style.SUCCESS(f"Dispatched {dispatched} scheduled order(s)."))
