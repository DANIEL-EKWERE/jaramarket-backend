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
from ..scoping import scope


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
    # A state rep's report must only cover their own state.
    orders = scope(orders, request, "address__state_id")
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


# Statuses at which the money has actually moved: vendors have been paid and
# our cut has been retained. Anything earlier is forecast, not income, and
# mixing the two is how a P&L ends up overstated.
EARNED_STATUSES = ("completed", "in_transit", "received")


@perm_required("view_reports")
def earnings_report_view(request):
    """What JaraMarket itself makes, as opposed to what passes through it.

    Every other report here sums Order.total, which is gross order value --
    mostly the vendors' money plus VAT we owe onward. This one reports only
    the three streams we keep:

      commission   a banded % of each item line, deducted from the vendor's
                   payout (vendor_amount = amount - commision), so the
                   customer pays one price and we keep the margin
      service fee  banded on the subtotal, added on top, entirely ours
      delivery     charged to the customer; ours before rider costs

    less referral commission, which is paid out of our commission, not on top
    of it. VAT and vendor payouts are reported separately and deliberately
    excluded from the net -- neither is income.
    """
    start, end = _range(request)

    orders = Order.objects.filter(created_at__date__gte=start,
                                  created_at__date__lte=end)
    orders = scope(orders, request, "address__state_id")

    # Cancelled lines were refunded and are out of the order; counting their
    # commission would book revenue on money we gave back.
    items = (OrderItem.objects
             .exclude(status="cancelled")
             .filter(order__in=orders))

    def total(qs, field):
        return qs.aggregate(v=Sum(field))["v"] or 0

    def block(order_qs, item_qs):
        commission = total(item_qs, "commision")
        service = total(order_qs, "service_charge")
        delivery = total(order_qs, "shipping_fee")
        referral = total(item_qs, "referral")
        return {
            "commission": commission,
            "service": service,
            "delivery": delivery,
            "referral": referral,
            "net": commission + service + delivery - referral,
            "gmv": total(order_qs, "total"),
            "vendor_payouts": total(item_qs, "vendor_amount"),
            "vat": total(order_qs, "vat"),
            "orders": order_qs.count(),
        }

    earned_orders = orders.filter(status__in=EARNED_STATUSES)
    all_block = block(orders, items)
    earned_block = block(earned_orders,
                         items.filter(order__status__in=EARNED_STATUSES))

    # Pending = placed but not yet approved, so not yet ours.
    pending_net = all_block["net"] - earned_block["net"]

    daily = list(
        orders.filter(status__in=EARNED_STATUSES)
        .values("created_at__date")
        .annotate(orders=Count("id"), gmv=Sum("total"),
                  service=Sum("service_charge"), delivery=Sum("shipping_fee"))
        .order_by("created_at__date"))
    # Commission lives on the item, so it can't join the aggregate above
    # without multiplying the order-level sums by the item count.
    per_day_commission = dict(
        items.filter(order__status__in=EARNED_STATUSES)
        .values_list("order__created_at__date")
        .annotate(c=Sum("commision")))
    per_day_referral = dict(
        items.filter(order__status__in=EARNED_STATUSES)
        .values_list("order__created_at__date")
        .annotate(r=Sum("referral")))
    for row in daily:
        day = row["created_at__date"]
        row["commission"] = per_day_commission.get(day, 0) or 0
        row["referral"] = per_day_referral.get(day, 0) or 0
        row["net"] = ((row["service"] or 0) + (row["delivery"] or 0)
                      + row["commission"] - row["referral"])

    if request.GET.get("export") == "csv":
        rows = [[r["created_at__date"], r["orders"], r["gmv"], r["commission"],
                 r["service"], r["delivery"], r["referral"], r["net"]]
                for r in daily]
        return _csv_response(
            "earnings-report.csv",
            ["Date", "Orders", "Gross order value", "Commission",
             "Service fee", "Delivery", "Referral paid", "Net earnings"],
            rows)

    return render(request, "webadmin/reports/earnings.html", {
        "start": start, "end": end,
        "earned": earned_block, "all": all_block,
        "pending_net": pending_net, "daily": daily,
    })


@perm_required("view_reports")
def vendor_commissions_view(request):
    """Commission earned from each vendor, and at what rate.

    This is the drill-down behind the dashboard's commission card. Commission
    is invisible to everyone else in the system -- the customer pays one
    price and the vendor is credited the remainder -- so this page is the
    only place the split is actually legible.

    The effective rate is computed per vendor rather than read off a band,
    because one vendor's lines span several bands: a vendor shifting ten
    small items is on a different rate from one shifting a single large one.
    """
    from apps.finance.models import Commission

    start, end = _range(request)

    orders = Order.objects.filter(created_at__date__gte=start,
                                  created_at__date__lte=end,
                                  status__in=EARNED_STATUSES)
    orders = scope(orders, request, "address__state_id")

    # Cancelled lines were refunded, so no commission was ever earned on them.
    items = (OrderItem.objects
             .filter(order__in=orders)
             .exclude(status="cancelled"))

    rows = list(items.filter(vendor__isnull=False)
                .values("vendor_id", "vendor__firstname", "vendor__lastname",
                        "vendor__email")
                .annotate(lines=Count("id"), gross=Sum("amount"),
                          commission=Sum("commision"),
                          paid_out=Sum("vendor_amount"))
                .order_by("-commission"))
    for row in rows:
        row["name"] = (f"{row['vendor__firstname'] or ''} "
                       f"{row['vendor__lastname'] or ''}").strip() or \
                      row["vendor__email"]
        gross = row["gross"] or 0
        row["rate"] = (row["commission"] or 0) / gross * 100 if gross else 0

    totals = {
        "lines": sum(r["lines"] for r in rows),
        "gross": sum(r["gross"] or 0 for r in rows),
        "commission": sum(r["commission"] or 0 for r in rows),
        "paid_out": sum(r["paid_out"] or 0 for r in rows),
    }
    totals["rate"] = (totals["commission"] / totals["gross"] * 100
                      if totals["gross"] else 0)

    # Which band each pound of commission came from. Derived from the line
    # amount because the rate itself isn't stored on the item -- only the
    # money. Mirrors get_commission()'s banding, including its rule that a
    # line below the lowest band still pays the lowest band's rate.
    bands = []
    ladder = list(Commission.objects.order_by("min_amount"))
    if ladder:
        lowest = ladder[0]
        below = items.filter(amount__lt=lowest.min_amount)
        if below.exists():
            bands.append({
                "label": f"Under ₦{lowest.min_amount:,.0f}",
                "percentage": lowest.percentage,
                "lines": below.count(),
                "gross": below.aggregate(v=Sum("amount"))["v"] or 0,
                "commission": below.aggregate(v=Sum("commision"))["v"] or 0,
                "note": "charged at the lowest band's rate",
            })
        for band in ladder:
            q = items.filter(amount__gte=band.min_amount)
            if band.max_amount:
                q = q.filter(amount__lte=band.max_amount)
            top = f"₦{band.max_amount:,.0f}" if band.max_amount else "and above"
            joiner = " – " if band.max_amount else " "
            bands.append({
                "label": f"₦{band.min_amount:,.0f}{joiner}{top}",
                "percentage": band.percentage,
                "lines": q.count(),
                "gross": q.aggregate(v=Sum("amount"))["v"] or 0,
                "commission": q.aggregate(v=Sum("commision"))["v"] or 0,
                "note": "",
            })

    if request.GET.get("export") == "csv":
        return _csv_response(
            "vendor-commissions.csv",
            ["Vendor", "Email", "Lines", "Gross line value",
             "Commission earned", "Paid to vendor", "Effective rate %"],
            [[r["name"], r["vendor__email"], r["lines"], r["gross"],
              r["commission"], r["paid_out"], f"{r['rate']:.2f}"]
             for r in rows])

    return render(request, "webadmin/reports/vendor_commissions.html", {
        "start": start, "end": end, "rows": rows, "totals": totals,
        "bands": bands,
    })


@perm_required("view_reports")
def products_report_view(request):
    items = scope(OrderItem.objects.filter(product__isnull=False),
                  request, "order__address__state_id")
    rows = (items
            .values("product__name")
            .annotate(total_quantity=Sum("quantity"), total_sales=Sum(F("price") * F("quantity")))
            .order_by("-total_quantity"))
    return render(request, "webadmin/reports/products.html", {"rows": rows})


@perm_required("view_transactions")
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


@perm_required("view_transactions")
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
