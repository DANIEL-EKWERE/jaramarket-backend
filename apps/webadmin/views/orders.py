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
from apps.orders.models import Order, OrderItem
from ..decorators import perm_required

_svc = OrderService()


@perm_required("view_orders")
def orders_list_view(request):
    qs = (Order.objects.select_related("user")
          .prefetch_related("items__market").order_by("-created_at"))
    if request.GET.get("status"):
        qs = qs.filter(status=request.GET["status"])
    if request.GET.get("search"):
        s = request.GET["search"]
        qs = qs.filter(reference__icontains=s)
    if request.GET.get("market_id"):
        qs = qs.filter(items__market_id=request.GET["market_id"]).distinct()
    paginator = Paginator(qs, request.GET.get("per_page", 20))
    page = paginator.get_page(request.GET.get("page"))
    # Items are routed to markets individually, so an order can span several
    # (or none yet, while it's still awaiting dispatch).
    for order in page:
        items = list(order.items.all())
        order.routed_markets = sorted({i.market.name for i in items if i.market_id})
        order.unrouted_count = sum(1 for i in items if i.ingredient_id and not i.market_id)
    from apps.vendors.models import Market
    return render(request, "webadmin/orders/list.html", {
        "page": page,
        "markets": Market.objects.filter(is_active=True).order_by("name")})


@perm_required("view_orders")
def order_detail_view(request, order_id):
    order = get_object_or_404(Order.objects.select_related("user", "address"), id=order_id)
    items = list(order.items.select_related("product", "ingredient", "vendor", "market").all())
    # Offer counts make a stuck item legible: routed to a market but with no
    # pending offers means no eligible vendor there ever got it.
    from apps.orders.models import MarketOfferResponse
    for item in items:
        offers = MarketOfferResponse.objects.filter(attempt__order_item=item)
        item.total_offers = offers.count()
        item.pending_offers = offers.filter(decision="pending", attempt__status="offered").count()
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


@perm_required("view_orders")
def orders_manual_queue_view(request):
    """Order items every nearby market's vendors declined/timed out on (or
    that had no eligible vendor at all) — flagged by MarketDispatchService
    (re_assigned=True) for a human to assign directly."""
    qs = (OrderItem.objects
          .filter(re_assigned=True, vendor__isnull=True)
          .select_related("ingredient", "product", "order__user")
          .order_by("-created_at"))
    paginator = Paginator(qs, request.GET.get("per_page", 20))
    page = paginator.get_page(request.GET.get("page"))

    from apps.accounts.models import User
    vendors_by_category = {}
    for item in page:
        item.display_name = item.ingredient.name if item.ingredient_id else (
            item.product.name if item.product_id else "—")
        cat_id = item.ingredient.category_id if item.ingredient_id else None
        if cat_id and cat_id not in vendors_by_category:
            vendors_by_category[cat_id] = list(
                User.objects.filter(role=Roles.VENDOR, is_active=True, categories__id=cat_id)
                .distinct().order_by("firstname"))
        item.candidate_vendors = vendors_by_category.get(cat_id, [])

    return render(request, "webadmin/orders/manual_queue.html", {"page": page})


@require_POST
@perm_required("manage_orders")
def order_item_manual_assign_view(request, item_id):
    vendor_id = request.POST.get("vendor_id")
    if not vendor_id:
        messages.error(request, "Select a vendor to assign.")
        return redirect("webadmin:orders_manual_queue")
    try:
        _svc.decide(request.user, item_id, {"status": "accepted", "vendor_id": vendor_id})
        messages.success(request, "Order item assigned successfully.")
    except ValueError as e:
        messages.error(request, str(e))
    return redirect("webadmin:orders_manual_queue")
