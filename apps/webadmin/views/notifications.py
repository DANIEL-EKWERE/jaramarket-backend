"""Self-service notifications panel for the logged-in admin -- simple
server-rendered list + mark-read, reusing the `Notification` model that
api/notifications.py already writes to (database channel)."""
import json

from django.contrib import messages
from django.core.paginator import Paginator
from django.shortcuts import get_object_or_404, redirect, render
from django.utils import timezone
from django.views.decorators.http import require_POST

from api.services._base import USER_TYPE
from apps.accounts.models import Roles, User
from apps.geo.models import State
from apps.support.models import Notification
from ..decorators import admin_required, perm_required
from ..scoping import scoped_states


def _with_parsed_data(notification):
    try:
        notification.parsed = json.loads(notification.data)
    except (TypeError, ValueError):
        notification.parsed = {}
    return notification


@admin_required
def notifications_list_view(request):
    qs = Notification.objects.filter(
        notifiable_type=USER_TYPE, notifiable_id=request.user.id
    ).order_by("-created_at")
    paginator = Paginator(qs, request.GET.get("per_page", 15))
    page = paginator.get_page(request.GET.get("page"))
    for n in page:
        _with_parsed_data(n)
    unread_count = qs.filter(read_at__isnull=True).count()
    return render(request, "webadmin/notifications/list.html", {"page": page, "unread_count": unread_count})


@require_POST
@admin_required
def notification_read_view(request, notification_id):
    n = get_object_or_404(Notification, id=notification_id, notifiable_type=USER_TYPE,
                           notifiable_id=request.user.id)
    if n.read_at is None:
        n.read_at = timezone.now()
        n.save(update_fields=["read_at"])
    return redirect("webadmin:notifications_list")


@require_POST
@admin_required
def notifications_mark_all_read_view(request):
    Notification.objects.filter(notifiable_type=USER_TYPE, notifiable_id=request.user.id,
                                 read_at__isnull=True).update(read_at=timezone.now())
    messages.success(request, "All notifications marked as read.")
    return redirect("webadmin:notifications_list")


# ── Broadcast push ───────────────────────────────────────────────────────────
# Sending to a whole audience from the dashboard. Deliberately separate from
# the inbox above: that one is what THIS admin received, this one is what the
# platform sends out.

AUDIENCES = [
    ("customers", "Customers", Roles.CUSTOMER),
    ("vendors", "Vendors", Roles.VENDOR),
    ("riders", "Logistics riders", Roles.LOGISTICS),
    ("everyone", "Everyone (customers, vendors and riders)", None),
]
_AUDIENCE_ROLES = {a[0]: a[2] for a in AUDIENCES}


def _audience_queryset(request, audience, state_id=None):
    """Recipients for an audience, honouring the sender's own state scope.

    A state representative can only reach the states they cover -- the same
    rule every other dashboard list follows, applied here so a rep can't
    broadcast nationwide.
    """
    qs = User.objects.filter(is_active=True)
    role = _AUDIENCE_ROLES.get(audience, "__missing__")
    if role is None:                       # "everyone"
        qs = qs.filter(role__in=[Roles.CUSTOMER, Roles.VENDOR, Roles.LOGISTICS])
    elif role == "__missing__":
        return User.objects.none()
    else:
        qs = qs.filter(role=role)

    allowed = scoped_states(request)
    if allowed:
        qs = qs.filter(state_id__in=allowed)
    if state_id:
        qs = qs.filter(state_id=state_id)
    return qs


def _with_token(qs):
    return qs.exclude(fcm_token=None).exclude(fcm_token="")


@perm_required("send_notifications")
def push_broadcast_view(request):
    allowed = scoped_states(request)
    states = State.objects.order_by("name")
    if allowed:
        states = states.filter(id__in=allowed)

    if request.method == "POST":
        title = (request.POST.get("title") or "").strip()
        message = (request.POST.get("message") or "").strip()
        audience = request.POST.get("audience") or "customers"
        state_id = request.POST.get("state_id") or None
        email = (request.POST.get("email") or "").strip()

        if not title or not message:
            messages.error(request, "Both a title and a message are required.")
        else:
            if email:
                recipients = User.objects.filter(email__iexact=email, is_active=True)
                if allowed:
                    recipients = recipients.filter(state_id__in=allowed)
                if not recipients.exists():
                    messages.error(
                        request, f"No active account found for {email} within your reach.")
                    recipients = None
            else:
                recipients = _audience_queryset(request, audience, state_id)

            if recipients is not None:
                result = _send_push(request.user, recipients, title, message)
                if result["sent"]:
                    messages.success(
                        request,
                        f"Push sent to {result['sent']} device"
                        f"{'' if result['sent'] == 1 else 's'}.")
                if result["no_token"]:
                    messages.warning(
                        request,
                        f"{result['no_token']} recipient"
                        f"{' has' if result['no_token'] == 1 else 's have'} no device "
                        "registered — they got an in-app notification only.")
                if result["failed"]:
                    messages.error(
                        request,
                        f"{result['failed']} push{'' if result['failed'] == 1 else 'es'} "
                        f"failed to deliver. {result['first_error'] or ''}".strip())
                if not any((result["sent"], result["no_token"], result["failed"])):
                    messages.error(request, "That audience has no active recipients.")
                return redirect("webadmin:push_broadcast")

    # Recipient counts make the blast radius obvious before sending.
    counts = []
    for key, label, _role in AUDIENCES:
        qs = _audience_queryset(request, key)
        total = qs.count()
        counts.append({"key": key, "label": label, "total": total,
                       "reachable": _with_token(qs).count()})
    return render(request, "webadmin/notifications/broadcast.html", {
        "audiences": counts,
        "states": states,
        "scoped": bool(allowed),
    })


def _send_push(sender, recipients, title, message):
    """Push + in-app notification to every recipient.

    Everyone gets the database notification (so it's in their in-app list
    whether or not a device is registered); the push itself only goes to
    accounts that actually have a token.
    """
    from api.notifications import FirebasePush, notify

    pusher = FirebasePush()
    sent = failed = no_token = 0
    first_error = None
    data = {"type": "admin_broadcast", "title": title, "message": message}

    for user in recipients.iterator():
        try:
            notify(user, "AdminBroadcastNotification", data, channels=("database",))
        except Exception:
            pass  # an in-app row failing must not stop the broadcast
        if not user.fcm_token:
            no_token += 1
            continue
        outcome = pusher.send(user.fcm_token, title, message, data)
        if outcome.get("sent"):
            sent += 1
        else:
            failed += 1
            first_error = first_error or outcome.get("error") or outcome.get("reason")

    import logging
    logging.getLogger(__name__).info(
        "Push broadcast by %s: sent=%s failed=%s no_token=%s title=%r",
        sender.email, sent, failed, no_token, title)
    return {"sent": sent, "failed": failed, "no_token": no_token,
            "first_error": first_error}
