"""Support tickets on the dashboard.

Customers raise tickets from the app (POST /jaram/support) and, until now,
nothing on the admin side ever read them -- they simply accumulated as `open`
rows nobody saw. This is the other half: read them, answer them, close them.
"""
from django.contrib import messages
from django.core.paginator import Paginator
from django.db.models import Count, Max, Q
from django.shortcuts import get_object_or_404, redirect, render
from django.views.decorators.http import require_POST

from apps.support.models import HelpTicket, Support, TicketReply
from ..decorators import perm_required
from ..scoping import scope

STATUSES = [("open", "Open"), ("in_progress", "In progress"),
            ("resolved", "Resolved"), ("closed", "Closed")]


def _scoped(request):
    """Tickets are scoped by the customer's own state, like every other list,
    so a state representative only sees their patch."""
    return scope(HelpTicket.objects.all(), request, "user__state_id")


@perm_required("view_support")
def tickets_list_view(request):
    qs = _scoped(request).select_related("user").prefetch_related("replies")

    status = request.GET.get("status")
    if status in dict(STATUSES):
        qs = qs.filter(status=status)
    search = request.GET.get("search")
    if search:
        qs = qs.filter(Q(subject__icontains=search) | Q(message__icontains=search) |
                       Q(user__email__icontains=search))

    # Waiting-longest first: a ticket nobody has answered is the one that
    # matters, and "newest first" buries exactly those.
    qs = qs.annotate(reply_count=Count("replies"),
                     last_activity=Max("replies__created_at"))
    if request.GET.get("sort") == "newest":
        qs = qs.order_by("-created_at")
    else:
        qs = qs.order_by("status", "created_at")

    paginator = Paginator(qs, request.GET.get("per_page", 20))
    page = paginator.get_page(request.GET.get("page"))
    for t in page:
        t.awaiting_reply = not any(r.is_staff for r in t.replies.all())

    # A list rather than a dict: templates can't look a dict up by a loop
    # variable without a custom filter.
    cards = [{"key": key, "label": label,
              "count": _scoped(request).filter(status=key).count()}
             for key, label in STATUSES]
    return render(request, "webadmin/support/list.html", {
        "page": page, "statuses": STATUSES, "cards": cards,
        "total": sum(c["count"] for c in cards),
        "can_manage": request.user.has_perm_slug("manage_support"),
        "legacy_count": Support.objects.count(),
    })


@perm_required("view_support")
def ticket_detail_view(request, id):
    ticket = get_object_or_404(
        _scoped(request).select_related("user").prefetch_related("replies__author"), id=id)
    # Most tickets are about an order; having them to hand saves a search.
    recent_orders = []
    if ticket.user_id:
        from apps.orders.models import Order
        recent_orders = list(Order.objects.filter(user_id=ticket.user_id)
                             .order_by("-created_at")[:5])
    return render(request, "webadmin/support/detail.html", {
        "ticket": ticket, "statuses": STATUSES, "recent_orders": recent_orders,
        "can_manage": request.user.has_perm_slug("manage_support"),
    })


@require_POST
@perm_required("manage_support")
def ticket_reply_view(request, id):
    ticket = get_object_or_404(_scoped(request), id=id)
    message = (request.POST.get("message") or "").strip()
    if not message:
        messages.error(request, "Write a reply first.")
        return redirect("webadmin:ticket_detail", id=ticket.id)

    TicketReply.objects.create(ticket=ticket, author=request.user,
                               message=message, is_staff=True)

    # Answering moves it along on its own -- an admin shouldn't have to
    # remember a second click to take it off the open pile.
    new_status = request.POST.get("status") or (
        "in_progress" if ticket.status == "open" else ticket.status)
    if new_status in dict(STATUSES) and new_status != ticket.status:
        ticket.status = new_status
        ticket.save(update_fields=["status"])

    if ticket.user_id:
        try:
            from api.notifications import ticket_reply_notification
            ticket_reply_notification(ticket.user, ticket, message)
            messages.success(request, "Reply sent — the customer has been notified.")
        except Exception:
            import logging
            logging.getLogger(__name__).exception(
                "Ticket %s: reply saved but notification failed", ticket.id)
            messages.warning(
                request, "Reply saved, but we could not notify the customer.")
    else:
        messages.warning(request, "Reply saved. This ticket has no account attached, "
                                  "so nobody was notified.")
    return redirect("webadmin:ticket_detail", id=ticket.id)


@require_POST
@perm_required("manage_support")
def ticket_status_view(request, id):
    ticket = get_object_or_404(_scoped(request), id=id)
    status = request.POST.get("status")
    if status not in dict(STATUSES):
        messages.error(request, "Unknown status.")
    else:
        ticket.status = status
        ticket.save(update_fields=["status"])
        messages.success(request, f"Ticket marked {dict(STATUSES)[status].lower()}.")
    return redirect(request.POST.get("next") or "webadmin:tickets_list")
