"""Mirrors vendors_list/vendor_show in api/admin_views.py, plus
toggle-status/toggle-verification which the PHP VendorManagementController
has but api/admin_views.py never got ported over."""
from django.contrib import messages
from django.core.paginator import Paginator
from django.db.models import Sum
from django.http import Http404
from django.shortcuts import get_object_or_404, redirect, render
from django.views.decorators.http import require_POST

from apps.accounts.models import Roles, User
from apps.finance.models import Wallet
from apps.orders.models import OrderItem
from ..decorators import perm_required


@perm_required("view_vendors")
def vendors_list_view(request):
    qs = User.objects.vendors().select_related("state")
    if request.GET.get("state_id"):
        qs = qs.filter(state_id=request.GET["state_id"])
    if request.GET.get("category_id"):
        qs = qs.filter(categories__id=request.GET["category_id"])
    if request.GET.get("status"):
        qs = qs.filter(is_active=(request.GET["status"] == "active"))

    qs = qs.distinct()
    paginator = Paginator(qs, request.GET.get("per_page", 20))
    page = paginator.get_page(request.GET.get("page"))
    for v in page:
        wallet = Wallet.objects.filter(user=v).first()
        v.wallet_balance = wallet.balance if wallet else 0
    return render(request, "webadmin/vendors/list.html", {"page": page})


@perm_required("view_vendors")
def vendor_detail_view(request, vendor_id):
    v = User.objects.filter(id=vendor_id, role=Roles.VENDOR).first()
    if not v:
        raise Http404("Vendor not found")
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
