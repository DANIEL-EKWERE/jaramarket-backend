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
from apps.support.models import Notification
from ..decorators import admin_required


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
