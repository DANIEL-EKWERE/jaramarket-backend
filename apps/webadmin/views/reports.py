"""Mirrors api/reports_views.py exactly — same querysets, date-range handling,
and permission slugs. CSV export is reimplemented here (rather than linking to
the existing export_orders/export_payments JSON endpoints) because those are
JWT-only; this dashboard authenticates via a Django session, so it needs its
own session-authenticated equivalent producing the identical CSV output."""
import csv
from datetime import timedelta

from django.db.models import Count, F, Sum
from django.http import HttpResponse
from django.shortcuts import render
from django.utils import timezone

from apps.finance.models import PaymentLog, Transfer, Wallet
from apps.orders.models import Order, OrderItem
from ..decorators import perm_required


def _range(request):
    end = request.GET.get("end") or timezone.now().date().isoformat()
    start = request.GET.get("start") or (timezone.now().date() - timedelta(days=30)).isoformat()
    return start, end


def _csv_response(filename, header, rows):
    resp = HttpResponse(content_type="text/csv")
    resp["Content-Disposition"] = f'attachment; filename="{filename}"'
    writer = csv.writer(resp)
    writer.writerow(header)
    for row in rows:
        writer.writerow(row)
    return resp


@perm_required("view_reports")
def orders_report_view(request):
    start, end = _range(request)
    orders = Order.objects.filter(created_at__date__gte=start, created_at__date__lte=end)
    total_revenue = orders.aggregate(s=Sum("total"))["s"] or 0
    daily = list(orders.values("created_at__date")
                 .annotate(count=Count("id"), revenue=Sum("total"))
                 .order_by("created_at__date"))
    by_status = list(orders.values("status").annotate(c=Count("id"), t=Sum("total")))

    if request.GET.get("export") == "csv":
        rows = [[o.id, o.user.name if o.user else "", o.total, o.status,
                 o.created_at.strftime("%Y-%m-%d %H:%M") if o.created_at else ""]
                for o in orders.select_related("user").order_by("-created_at")]
        return _csv_response("orders-report.csv", ["Order ID", "Customer", "Total", "Status", "Date"], rows)

    return render(request, "webadmin/reports/orders.html", {
        "start": start, "end": end, "total_orders": orders.count(),
        "total_revenue": total_revenue, "daily": daily, "by_status": by_status})


@perm_required("view_reports")
def products_report_view(request):
    rows = (OrderItem.objects.filter(product__isnull=False)
            .values("product__name")
            .annotate(total_quantity=Sum("quantity"), total_sales=Sum(F("price") * F("quantity")))
            .order_by("-total_quantity"))
    return render(request, "webadmin/reports/products.html", {"rows": rows})


@perm_required("view_reports", "view_transactions")
def finance_summary_report_view(request):
    start, end = _range(request)
    deposits = (PaymentLog.objects.filter(status="success",
                created_at__date__gte=start, created_at__date__lte=end)
                .aggregate(s=Sum("amount"))["s"] or 0)
    transfers = (Transfer.objects.filter(status__in=["success", "pending"],
                 created_at__date__gte=start, created_at__date__lte=end)
                 .aggregate(s=Sum("amount"))["s"] or 0)
    return render(request, "webadmin/reports/finance_summary.html", {
        "start": start, "end": end,
        "wallet_balance": Wallet.objects.aggregate(s=Sum("balance"))["s"] or 0,
        "total_deposits": deposits, "total_transfers": transfers / 100})


@perm_required("view_reports", "view_transactions")
def payments_report_view(request):
    start, end = _range(request)
    payments = PaymentLog.objects.filter(created_at__date__gte=start, created_at__date__lte=end)
    by_status = list(payments.values("status").annotate(c=Count("id")))

    if request.GET.get("export") == "csv":
        rows = [[p.created_at.strftime("%Y-%m-%d %H:%M") if p.created_at else "", p.txn_ref,
                 p.transaction_owner_id or "", p.amount, p.status]
                for p in payments.order_by("-created_at")]
        return _csv_response("payments-report.csv", ["Date", "Reference", "Customer", "Amount", "Status"], rows)

    return render(request, "webadmin/reports/payments.html", {
        "start": start, "end": end,
        "total_payments": payments.aggregate(s=Sum("amount"))["s"] or 0, "by_status": by_status})
