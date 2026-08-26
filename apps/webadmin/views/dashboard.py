"""Mirrors api/admin_views.py::dashboard() exactly — same permission-gated
stat blocks, same querysets, just rendered as HTML instead of JSON."""
from django.db.models import Count, Sum
from django.shortcuts import render
from django.utils import timezone

from apps.accounts.models import User
from apps.orders.models import Order, OrderItem
from ..decorators import admin_required
from ..scoping import scoped_states


@admin_required
def dashboard_view(request):
    user = request.user
    # State admins are scoped by their own profile state; state reps by the
    # state(s) they represent (scoped_states). Everyone else sees nationwide.
    state_ids = scoped_states(request)
    if not state_ids and user.is_state_admin() and user.state_id:
        state_ids = [user.state_id]
    stats = {}

    def _scope(qs, path):
        return qs.filter(**{f"{path}__in": state_ids}) if state_ids else qs

    def order_qs():
        # Orders belong to a state via their delivery address.
        return _scope(Order.objects.all(), "address__state_id")

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
        rq = order_qs().filter(status="completed")
        stats["total_revenue"] = rq.aggregate(s=Sum("total"))["s"] or 0
        stats["today_revenue"] = rq.filter(
            created_at__date=timezone.now().date()).aggregate(s=Sum("total"))["s"] or 0
    if user.has_perm_slug("view_users"):
        stats["total_customers"] = _scope(User.objects.customers(), "state_id").count()
    if user.has_perm_slug("view_vendors"):
        stats["total_vendors"] = _scope(User.objects.vendors(), "state_id").count()

    recent_orders = []
    if user.has_perm_slug("view_orders"):
        recent_orders = order_qs().select_related("user").order_by("-created_at")[:8]

    latest_users = []
    if user.has_perm_slug("view_users"):
        latest_users = _scope(User.objects.customers(), "state_id").order_by("-created_at")[:6]

    order_status_chart = {}
    if user.has_perm_slug("view_orders"):
        for row in order_qs().values("status").annotate(count=Count("id")):
            order_status_chart[row["status"]] = row["count"]

    hour = timezone.localtime().hour
    greeting = "morning" if hour < 12 else "afternoon" if hour < 17 else "evening"

    return render(request, "webadmin/dashboard.html", {
        "stats": stats,
        "recent_orders": recent_orders,
        "latest_users": latest_users,
        "order_status_chart": order_status_chart,
        "greeting": greeting,
    })
