"""Referral earnings: what an inviter has been paid, and what is still on
its way, from orders placed by the friends they invited.

Commission is fixed on each OrderItem when the order is placed
(OrderItem.referral / referral_user) and credited to the inviter's wallet
when admin approves the order (OrderService.mark_completed, logged as a
"Referral commission" credit).
"""
from decimal import Decimal

from django.db.models import Sum

from apps.finance.models import TransactionLog
from apps.orders.models import OrderItem

REFERRAL_CREDIT_COMMENT = "Referral commission"


def _paid_out_statuses():
    from .order import PAID_OUT_STATUSES
    return PAID_OUT_STATUSES


def paid_referral_items(referrer):
    """Lines whose commission has been credited (same set mark_completed pays)."""
    return OrderItem.objects.filter(referral_user=referrer,
                                    order__status__in=_paid_out_statuses())


def pending_referral_items(referrer):
    """Lines on orders still in progress, so not yet credited."""
    return (OrderItem.objects.filter(referral_user=referrer, referral__gt=0)
            .exclude(order__status__in=_paid_out_statuses() + ("cancelled",))
            .exclude(status="cancelled"))


def referral_summary(user, recent_limit=10):
    credits = TransactionLog.objects.filter(
        account_owner_id=user.id, transaction_type="credit",
        comment=REFERRAL_CREDIT_COMMENT)
    total_minor = credits.aggregate(t=Sum("amount"))["t"] or 0
    pending = pending_referral_items(user).aggregate(t=Sum("referral"))["t"] or Decimal("0")

    from apps.orders.models import Order
    recent = list(credits.order_by("-created_at")[:recent_limit])
    references = dict(Order.objects.filter(id__in=[c.owner_id for c in recent if c.owner_id])
                      .values_list("id", "reference"))
    return {
        "total_earned": f"{total_minor / TransactionLog.SMALLEST_CURRENCY_UNIT:.2f}",
        "pending": f"{pending:.2f}",
        "referrals_count": user.referrals.count(),
        "recent": [{
            "amount": f"{c.amount / TransactionLog.SMALLEST_CURRENCY_UNIT:.2f}",
            "order_reference": references.get(c.owner_id),
            "created_at": c.created_at.isoformat() if c.created_at else None,
        } for c in recent],
    }
