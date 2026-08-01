"""Sweep for order-item market offers that have sat unanswered past their
timeout, and escalate each to the next-closest market (see
MarketDispatchService.escalate). Mirrors reconcile_pending_payments.py's
role: there's no Celery Beat schedule in this project, so periodic work is a
plain management command meant to be triggered by an external scheduler
(e.g. a Render Cron Job) rather than an in-process beat worker.

Run every few minutes:
    python manage.py escalate_stale_offers
"""
from django.core.management.base import BaseCommand
from django.utils import timezone

from apps.orders.models import OrderItem
from api.services.dispatch import MarketDispatchService


class Command(BaseCommand):
    help = "Escalate order items whose current market offer has timed out to the next-closest market."

    def handle(self, *args, **options):
        stale = OrderItem.objects.filter(status="offered", offer_expires_at__lt=timezone.now())
        if not stale.exists():
            self.stdout.write("No stale offers to escalate.")
            return

        service = MarketDispatchService()
        escalated = 0
        for item in stale:
            self.stdout.write(f"Item #{item.id} offer at market_id={item.market_id} timed out — escalating.")
            service.escalate(item, "timeout")
            escalated += 1

        self.stdout.write(self.style.SUCCESS(f"Escalated {escalated} stale offer(s)."))
