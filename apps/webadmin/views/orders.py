"""Admin-wide order management -- not in the original plan/mirror set
(OrderService.all() is user-scoped, for the customer-facing API), but the
PHP admin has a full Orders controller (index/show/updateStatus/destroy)
that was missed in the initial port. Reuses OrderService.cancel_order() /
mark_completed() for the actions so business rules aren't duplicated."""
from django.contrib import messages
from django.core.paginator import Paginator
from django.shortcuts import get_object_or_404, redirect, render
from django.views.decorators.http import require_POST

from api.services import OrderService
from apps.accounts.models import Roles
from apps.orders.models import Order
from ..decorators import perm_required

_svc = OrderService()


@perm_required("view_orders")
def orders_list_view(request):
    qs = Order.objects.select_related("user").order_by("-created_at")
    if request.GET.get("status"):
        qs = qs.filter(status=request.GET["status"])
    if request.GET.get("search"):
        s = request.GET["search"]
        qs = qs.filter(reference__icontains=s)
    paginator = Paginator(qs, request.GET.get("per_page", 20))
    return render(request, "webadmin/orders/list.html", {
        "page": paginator.get_page(request.GET.get("page"))})


@perm_required("view_orders")
def order_detail_view(request, order_id):
    order = get_object_or_404(Order.objects.select_related("user", "address"), id=order_id)
    items = order.items.select_related("product", "ingredient", "vendor").all()
    can_manage = request.user.has_perm_slug("manage_orders")
    can_complete = request.user.role in Roles.ADMIN_ROLES or request.user.role == Roles.QA
    return render(request, "webadmin/orders/detail.html", {
        "order": order, "items": items, "can_manage": can_manage, "can_complete": can_complete})


@require_POST
@perm_required("manage_orders")
def order_cancel_view(request, order_id):
    order = get_object_or_404(Order, id=order_id)
    try:
        _svc.cancel_order(order)
        messages.success(request, f"Order #{order.reference} cancelled and refunded.")
    except ValueError as e:
        messages.error(request, str(e))
    return redirect("webadmin:order_detail", order_id=order_id)


@require_POST
def order_complete_view(request, order_id):
    if not (request.user.role in Roles.ADMIN_ROLES or request.user.role == Roles.QA):
        messages.error(request, "QA or admin access required.")
        return redirect("webadmin:order_detail", order_id=order_id)
    try:
        _svc.mark_completed(request.user, order_id)
        messages.success(request, "Order marked as completed.")
    except ValueError as e:
        messages.error(request, str(e))
    return redirect("webadmin:order_detail", order_id=order_id)
