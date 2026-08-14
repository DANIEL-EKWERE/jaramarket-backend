"""Mirrors vendors_list/vendor_show in api/admin_views.py, plus
toggle-status/toggle-verification which the PHP VendorManagementController
has but api/admin_views.py never got ported over."""
from django.contrib import messages
from django.core.paginator import Paginator
from django.db.models import Q, Sum
from django.http import Http404
from django.shortcuts import get_object_or_404, redirect, render
from django.views.decorators.http import require_POST

from apps.accounts.models import Roles, User
from apps.finance.models import Wallet
from apps.orders.models import OrderItem, OrderItemLog
from ..decorators import perm_required
from ..scoping import ensure_in_scope, scope


@perm_required("view_vendors")
def vendors_list_view(request):
    qs = (User.objects.vendors()
          .select_related("state", "vendor_profile", "vendor_profile__market")
          .prefetch_related("categories"))
    qs = scope(qs, request, "state_id")
    if request.GET.get("state_id"):
        qs = qs.filter(state_id=request.GET["state_id"])
    if request.GET.get("category_id"):
        qs = qs.filter(categories__id=request.GET["category_id"])
    if request.GET.get("status"):
        qs = qs.filter(is_active=(request.GET["status"] == "active"))
    # Order dispatch only ever offers an item to a vendor who is stationed at
    # a market AND holds the item's category, so surface the ones that can
    # never receive work (see MarketDispatchService.eligible_vendors).
    if request.GET.get("readiness") == "not_ready":
        qs = qs.filter(Q(vendor_profile__isnull=True)
                       | Q(vendor_profile__market__isnull=True)
                       | Q(vendor_profile__is_active=False)
                       | Q(categories__isnull=True))

    qs = qs.distinct().order_by("-created_at", "id")
    paginator = Paginator(qs, request.GET.get("per_page", 20))
    page = paginator.get_page(request.GET.get("page"))
    for v in page:
        wallet = Wallet.objects.filter(user=v).first()
        v.wallet_balance = wallet.balance if wallet else 0
        profile = getattr(v, "vendor_profile", None)
        v.market = profile.market if profile else None
        reasons = []
        if profile is None:
            reasons.append("no vendor profile")
        else:
            if profile.market_id is None:
                reasons.append("no market")
            if not profile.is_active:
                reasons.append("profile inactive")
        if not v.categories.all():
            reasons.append("no categories")
        if not v.is_active:
            reasons.append("account inactive")
        v.dispatch_blockers = reasons
    return render(request, "webadmin/vendors/list.html", {"page": page})


@perm_required("view_vendors")
def vendor_detail_view(request, vendor_id):
    v = User.objects.filter(id=vendor_id, role=Roles.VENDOR).first()
    if not v:
        raise Http404("Vendor not found")
    ensure_in_scope(request, v.state_id)
    items = OrderItem.objects.filter(vendor=v)
    stats = {
        "total_orders": items.count(),
        "pending_orders": items.filter(status="pending").count(),
        "accepted_orders": items.filter(status="processing").count(),
        "completed_orders": items.filter(status="completed").count(),
        "total_earned": items.filter(status="completed").aggregate(s=Sum("vendor_amount"))["s"] or 0,
    }
    wallet = Wallet.objects.filter(user=v).first()
    return render(request, "webadmin/vendors/detail.html", {
        "vendor": v, "wallet_balance": wallet.balance if wallet else 0, "stats": stats,
        "categories": v.categories.all()})


@perm_required("view_vendors")
def vendor_orders_view(request, vendor_id):
    """Mirrors VendorManagementController::vendorOrders in the PHP admin --
    a per-vendor order-items list (not shown anywhere on vendor_detail_view,
    which only has aggregate stats)."""
    v = get_object_or_404(User, id=vendor_id, role=Roles.VENDOR)
    qs = (OrderItem.objects.filter(vendor=v)
          .select_related("order", "order__user", "order__address", "ingredient", "ingredient__category")
          .order_by("-created_at"))
    status = request.GET.get("status")
    if status:
        qs = qs.filter(status=status)
    paginator = Paginator(qs, request.GET.get("per_page", 20))
    return render(request, "webadmin/vendors/orders.html", {
        "vendor": v, "page": paginator.get_page(request.GET.get("page")),
        "status": status, "statuses": [s[0] for s in OrderItemLog.STATUS_CHOICES]})


@require_POST
@perm_required("manage_vendors")
def vendor_toggle_status_view(request, vendor_id):
    v = get_object_or_404(User, id=vendor_id, role=Roles.VENDOR)
    v.is_active = not v.is_active
    v.save(update_fields=["is_active"])
    messages.success(request, f"{v.name} is now {'active' if v.is_active else 'inactive'}.")
    return redirect("webadmin:vendor_detail", vendor_id=vendor_id)


@require_POST
@perm_required("manage_vendors")
def vendor_toggle_verification_view(request, vendor_id):
    v = get_object_or_404(User, id=vendor_id, role=Roles.VENDOR)
    v.is_verified = not v.is_verified
    v.save(update_fields=["is_verified"])
    messages.success(request, f"{v.name} is now {'verified' if v.is_verified else 'unverified'}.")
    return redirect("webadmin:vendor_detail", vendor_id=vendor_id)
