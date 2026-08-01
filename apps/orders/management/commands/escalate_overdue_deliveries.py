"""Sweep for order items a vendor accepted but didn't mark delivered within
the delivery window (20 minutes by default, see
MarketDispatchService.reopen_after_delivery_timeout) -- pulls the item back
from that vendor and puts it back in front of other vendors. Same precedent
as escalate_stale_offers.py / dispatch_scheduled_orders.py: a plain
management command meant to be triggered by an external scheduler (e.g. a
Render Cron Job), since this project has no Celery Beat schedule.

Run every few minutes:
    python manage.py escalate_overdue_deliveries
"""
from django.core.management.base import BaseCommand
from django.utils import timezone

from apps.orders.models import OrderItem
from api.services.dispatch import MarketDispatchService


class Command(BaseCommand):
    help = "Pull order items back into the pool when the accepting vendor missed the delivery deadline."

    def handle(self, *args, **options):
        overdue = OrderItem.objects.filter(status="processing", delivery_deadline__lt=timezone.now())
        if not overdue.exists():
            self.stdout.write("No overdue deliveries.")
            return

        service = MarketDispatchService()
        reopened = 0
        for item in overdue:
            self.stdout.write(f"Item #{item.id}: vendor_id={item.vendor_id} missed the delivery deadline — reopening.")
            service.reopen_after_delivery_timeout(item)
            reopened += 1

        self.stdout.write(self.style.SUCCESS(f"Reopened {reopened} overdue delivery item(s)."))
