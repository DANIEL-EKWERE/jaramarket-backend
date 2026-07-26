"""Mirrors api/admin_views.py::dashboard() exactly — same permission-gated
stat blocks, same querysets, just rendered as HTML instead of JSON."""
from django.db.models import Count, Sum
from django.shortcuts import render
from django.utils import timezone

from apps.accounts.models import User
from apps.orders.models import Order, OrderItem
from ..decorators import admin_required


@admin_required
def dashboard_view(request):
    user = request.user
    state_id = user.state_id if user.is_state_admin() else None
    stats = {}

    def order_qs():
        qs = Order.objects.all()
        return qs.filter(user__state_id=state_id) if state_id else qs

    if user.has_perm_slug("view_orders"):
        oq = order_qs()
        stats.update({
            "total_orders": oq.count(),
            "pending_orders": oq.filter(status="pending").count(),
            "processing_orders": oq.filter(status="processing").count(),
            "completed_orders": oq.filter(status="completed").count(),
            "cancelled_orders": oq.filter(status="cancelled").count(),
        })
    if user.has_perm_slug("view_transactions"):
        stats["total_revenue"] = Order.objects.filter(status="completed").aggregate(s=Sum("total"))["s"] or 0
        stats["today_revenue"] = Order.objects.filter(
            status="completed", created_at__date=timezone.now().date()).aggregate(s=Sum("total"))["s"] or 0
    if user.has_perm_slug("view_users"):
        cq = User.objects.customers()
        stats["total_customers"] = (cq.filter(state_id=state_id) if state_id else cq).count()
    if user.has_perm_slug("view_vendors"):
        vq = User.objects.vendors()
        stats["total_vendors"] = (vq.filter(state_id=state_id) if state_id else vq).count()

    recent_orders = []
    if user.has_perm_slug("view_orders"):
        recent_orders = order_qs().select_related("user").order_by("-created_at")[:8]

    order_status_chart = {}
    if user.has_perm_slug("view_orders"):
        for row in order_qs().values("status").annotate(count=Count("id")):
            order_status_chart[row["status"]] = row["count"]

    hour = timezone.localtime().hour
    greeting = "morning" if hour < 12 else "afternoon" if hour < 17 else "evening"

    return render(request, "webadmin/dashboard.html", {
        "stats": stats,
        "recent_orders": recent_orders,
        "order_status_chart": order_status_chart,
        "greeting": greeting,
    })
