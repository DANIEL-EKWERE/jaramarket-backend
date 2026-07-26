"""Mirrors the Commission and ServiceFeeTier CRUD in api/admin_views.py."""
from django.contrib import messages
from django.core.paginator import Paginator
from django.db.models import Max, Min
from django.shortcuts import get_object_or_404, redirect, render
from django.views.decorators.http import require_POST

from apps.finance.models import Commission, ServiceFeeTier
from ..decorators import perm_required

# ── Commissions ──────────────────────────────────────────────────────────────

@perm_required("view_commissions")
def commissions_list_view(request):
    qs = Commission.objects.all().order_by("min_amount")
    paginator = Paginator(qs, request.GET.get("per_page", 20))
    page = paginator.get_page(request.GET.get("page"))
    agg = Commission.objects.aggregate(lo=Min("percentage"), hi=Max("percentage"))
    max_amount = Commission.objects.aggregate(m=Max("max_amount"))["m"] or 1
    for c in page:
        c.bar_pct = round((c.max_amount / max_amount) * 100) if c.max_amount else 0
    return render(request, "webadmin/commissions/list.html", {
        "page": page, "total": paginator.count, "lowest": agg["lo"], "highest": agg["hi"]})


@perm_required("manage_commissions")
def commission_create_view(request):
    if request.method == "POST":
        if request.POST.get("percentage") in (None, ""):
            messages.error(request, "Percentage is required.")
        else:
            Commission.objects.create(
                min_amount=request.POST.get("min_amount") or 0,
                max_amount=request.POST.get("max_amount") or None,
                percentage=request.POST["percentage"])
            messages.success(request, "Commission tier created successfully.")
            return redirect("webadmin:commissions_list")
    return render(request, "webadmin/commissions/form.html", {"commission": None})


@perm_required("manage_commissions")
def commission_update_view(request, id):
    c = get_object_or_404(Commission, id=id)
    if request.method == "POST":
        for field in ["min_amount", "max_amount", "percentage"]:
            if request.POST.get(field) is not None:
                setattr(c, field, request.POST[field] or None)
        c.save()
        messages.success(request, "Commission tier updated successfully.")
        return redirect("webadmin:commissions_list")
    return render(request, "webadmin/commissions/form.html", {"commission": c})


@require_POST
@perm_required("manage_commissions")
def commission_delete_view(request, id):
    get_object_or_404(Commission, id=id).delete()
    messages.success(request, "Commission tier deleted.")
    return redirect("webadmin:commissions_list")


# ── Service Fee Tiers ────────────────────────────────────────────────────────

@perm_required("view_service_fees")
def service_fee_tiers_list_view(request):
    qs = ServiceFeeTier.objects.all().order_by("min_amount")
    paginator = Paginator(qs, request.GET.get("per_page", 20))
    return render(request, "webadmin/service_fee_tiers/list.html",
                  {"page": paginator.get_page(request.GET.get("page"))})


@perm_required("manage_service_fees")
def service_fee_tier_create_view(request):
    if request.method == "POST":
        if request.POST.get("value") in (None, "") or request.POST.get("fee_type") not in (
                ServiceFeeTier.FLAT, ServiceFeeTier.PERCENTAGE):
            messages.error(request, "Value and a valid fee type are required.")
        else:
            ServiceFeeTier.objects.create(
                min_amount=request.POST.get("min_amount") or 0,
                max_amount=request.POST.get("max_amount") or None,
                fee_type=request.POST["fee_type"], value=request.POST["value"])
            messages.success(request, "Service fee tier created successfully.")
            return redirect("webadmin:service_fee_tiers_list")
    return render(request, "webadmin/service_fee_tiers/form.html", {"tier": None})


@perm_required("manage_service_fees")
def service_fee_tier_update_view(request, id):
    t = get_object_or_404(ServiceFeeTier, id=id)
    if request.method == "POST":
        for field in ["min_amount", "max_amount", "fee_type", "value"]:
            if request.POST.get(field) is not None:
                setattr(t, field, request.POST[field] or None)
        t.save()
        messages.success(request, "Service fee tier updated successfully.")
        return redirect("webadmin:service_fee_tiers_list")
    return render(request, "webadmin/service_fee_tiers/form.html", {"tier": t})


@require_POST
@perm_required("manage_service_fees")
def service_fee_tier_delete_view(request, id):
    get_object_or_404(ServiceFeeTier, id=id).delete()
    messages.success(request, "Service fee tier deleted.")
    return redirect("webadmin:service_fee_tiers_list")
