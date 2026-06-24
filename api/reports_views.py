"""
Reports & exports — JSON + CSV equivalents of ReportController and
PaymentReportController. Admin-gated (view_reports).
"""
import csv
from datetime import timedelta

from django.db.models import Count, F, Sum
from django.http import HttpResponse
from django.utils import timezone
from rest_framework.decorators import api_view, permission_classes

from .admin_views import IsAdmin, require_perms
from apps.finance.models import PaymentLog, Transfer, Wallet
from apps.orders.models import Order, OrderItem
from .utils import error, success


def _range(request):
    end = request.query_params.get("end") or timezone.now().date().isoformat()
    start = request.query_params.get("start") or (timezone.now().date() - timedelta(days=30)).isoformat()
    return start, end


@api_view(["GET"])
@permission_classes([IsAdmin])
def report_orders(request):
    if (resp := require_perms(request.user, ["view_reports"])):
        return resp
    start, end = _range(request)
    orders = Order.objects.filter(created_at__date__gte=start, created_at__date__lte=end)
    total_revenue = orders.aggregate(s=Sum("total"))["s"] or 0

    daily = list(orders.values("created_at__date")
                 .annotate(count=Count("id"), revenue=Sum("total"))
                 .order_by("created_at__date"))
    by_status = {r["status"]: {"count": r["c"], "total": r["t"]}
                 for r in orders.values("status").annotate(c=Count("id"), t=Sum("total"))}
    return success("Orders report", {
        "range": {"start": start, "end": end},
        "total_orders": orders.count(), "total_revenue": total_revenue,
        "daily": [{"date": str(d["created_at__date"]), "count": d["count"],
                   "revenue": d["revenue"]} for d in daily],
        "by_status": by_status})


@api_view(["GET"])
@permission_classes([IsAdmin])
def report_products(request):
    if (resp := require_perms(request.user, ["view_reports"])):
        return resp
    rows = (OrderItem.objects.filter(product__isnull=False)
            .values("product__name")
            .annotate(total_quantity=Sum("quantity"),
                      total_sales=Sum(F("price") * F("quantity")))
            .order_by("-total_quantity"))
    return success("Products report", [
        {"name": r["product__name"], "total_quantity": int(r["total_quantity"] or 0),
         "total_sales": r["total_sales"] or 0} for r in rows])


@api_view(["GET"])
@permission_classes([IsAdmin])
def report_finance_summary(request):
    if (resp := require_perms(request.user, ["view_reports", "view_transactions"])):
        return resp
    start, end = _range(request)
    deposits = (PaymentLog.objects.filter(status="success",
                created_at__date__gte=start, created_at__date__lte=end)
                .aggregate(s=Sum("amount"))["s"] or 0)
    transfers = (Transfer.objects.filter(status__in=["success", "pending"],
                 created_at__date__gte=start, created_at__date__lte=end)
                 .aggregate(s=Sum("amount"))["s"] or 0)
    return success("Finance summary", {
        "range": {"start": start, "end": end},
        "wallet_balance": Wallet.objects.aggregate(s=Sum("balance"))["s"] or 0,
        "total_deposits": deposits,
        "total_transfers": transfers / 100})


@api_view(["GET"])
@permission_classes([IsAdmin])
def report_payments(request):
    if (resp := require_perms(request.user, ["view_reports", "view_transactions"])):
        return resp
    start, end = _range(request)
    payments = PaymentLog.objects.filter(created_at__date__gte=start, created_at__date__lte=end)
    by_status = {r["status"]: r["c"] for r in payments.values("status").annotate(c=Count("id"))}
    return success("Payments report", {
        "range": {"start": start, "end": end},
        "total_payments": payments.aggregate(s=Sum("amount"))["s"] or 0,
        "by_status": by_status})


# ── CSV exports ──
def _csv_response(filename, header, rows):
    resp = HttpResponse(content_type="text/csv")
    resp["Content-Disposition"] = f'attachment; filename="{filename}"'
    writer = csv.writer(resp)
    writer.writerow(header)
    for row in rows:
        writer.writerow(row)
    return resp


@api_view(["GET"])
@permission_classes([IsAdmin])
def export_orders(request):
    if (resp := require_perms(request.user, ["view_reports"])):
        return resp
    start, end = _range(request)
    orders = (Order.objects.filter(created_at__date__gte=start, created_at__date__lte=end)
              .select_related("user").order_by("-created_at"))
    rows = [[o.id, o.user.name if o.user else "", o.total, o.status,
             o.created_at.strftime("%Y-%m-%d %H:%M") if o.created_at else ""] for o in orders]
    return _csv_response("orders-report.csv",
                         ["Order ID", "Customer", "Total", "Status", "Date"], rows)


@api_view(["GET"])
@permission_classes([IsAdmin])
def export_payments(request):
    if (resp := require_perms(request.user, ["view_reports", "view_transactions"])):
        return resp
    start, end = _range(request)
    payments = (PaymentLog.objects.filter(created_at__date__gte=start, created_at__date__lte=end)
                .order_by("-created_at"))
    rows = [[p.created_at.strftime("%Y-%m-%d %H:%M") if p.created_at else "", p.txn_ref,
             p.transaction_owner_id or "", p.amount, p.status] for p in payments]
    return _csv_response("payments-report.csv",
                         ["Date", "Reference", "Customer", "Amount", "Status"], rows)
