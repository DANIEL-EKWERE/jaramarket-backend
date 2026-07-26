"""Mirrors the Finance section of api/admin_views.py — same querysets, same
permission slugs, same filters."""
from decimal import Decimal

from django.core.paginator import Paginator
from django.db.models import Sum
from django.shortcuts import render

from apps.accounts.models import Roles, User
from apps.finance.models import TransactionLog, Transfer, Wallet
from api.services._base import USER_TYPE
from ..decorators import perm_required


def _page(request, qs, per_page=20):
    paginator = Paginator(qs, request.GET.get("per_page", per_page))
    return paginator.get_page(request.GET.get("page"))


@perm_required("view_transactions")
def transactions_view(request):
    qs = TransactionLog.objects.all().order_by("-created_at")
    if request.GET.get("type"):
        qs = qs.filter(transaction_type=request.GET["type"])
    if request.GET.get("start"):
        qs = qs.filter(created_at__date__gte=request.GET["start"])
    if request.GET.get("end"):
        qs = qs.filter(created_at__date__lte=request.GET["end"])

    owner_cache = {}
    page = _page(request, qs)
    for t in page:
        if t.account_owner_type == USER_TYPE and t.account_owner_id not in owner_cache:
            owner_cache[t.account_owner_id] = User.objects.filter(id=t.account_owner_id).first()
        t.owner = owner_cache.get(t.account_owner_id) if t.account_owner_type == USER_TYPE else None

    return render(request, "webadmin/finance/transactions.html", {"page": page})


@perm_required("view_wallets")
def wallets_view(request):
    summary = {
        "total_user_balance": Wallet.objects.filter(user__role=Roles.CUSTOMER).aggregate(s=Sum("balance"))["s"] or 0,
        "total_vendor_balance": Wallet.objects.filter(user__role=Roles.VENDOR).aggregate(s=Sum("balance"))["s"] or 0,
        "total_wallets": Wallet.objects.count(),
    }
    qs = Wallet.objects.select_related("user").all()
    if request.GET.get("role"):
        qs = qs.filter(user__role=request.GET["role"])
    if request.GET.get("min_balance"):
        qs = qs.filter(balance__gte=Decimal(request.GET["min_balance"]))
    return render(request, "webadmin/finance/wallets.html", {"summary": summary, "page": _page(request, qs)})


@perm_required("manage_withdrawals", "view_transactions")
def withdrawals_view(request):
    qs = Transfer.objects.all().order_by("-created_at")
    if request.GET.get("status"):
        qs = qs.filter(status=request.GET["status"])
    page = _page(request, qs)
    owner_cache = {}
    for t in page:
        if t.owner_type == USER_TYPE and t.owner_id not in owner_cache:
            owner_cache[t.owner_id] = User.objects.filter(id=t.owner_id).first()
        t.owner = owner_cache.get(t.owner_id) if t.owner_type == USER_TYPE else None
        t.amount_naira = Decimal(t.amount) / 100
    return render(request, "webadmin/finance/withdrawals.html", {"page": page})


@perm_required("view_transactions", "view_wallets")
def user_transactions_view(request, user_id):
    user = User.objects.filter(id=user_id).first()
    if not user:
        from django.http import Http404
        raise Http404("User not found")
    qs = TransactionLog.objects.filter(account_owner_type=USER_TYPE, account_owner_id=user_id).order_by("-created_at")
    if request.GET.get("type"):
        qs = qs.filter(transaction_type=request.GET["type"])
    credit = qs.filter(transaction_type="credit", is_refund=False).aggregate(s=Sum("amount"))["s"] or 0
    debit = qs.filter(transaction_type="debit").aggregate(s=Sum("amount"))["s"] or 0
    totals = {"total_credit": Decimal(credit) / 100, "total_debit": Decimal(debit) / 100}
    return render(request, "webadmin/finance/user_transactions.html", {
        "target_user": user, "page": _page(request, qs), "totals": totals})
