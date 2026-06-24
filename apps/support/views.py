import json

from rest_framework.decorators import api_view

from api.utils import error, success
from .models import HelpTicket, Notification
from .serializers import HelpTicketSerializer


def _notif_qs(user):
    return Notification.objects.filter(
        notifiable_type="App\\Models\\User", notifiable_id=user.id).order_by("-created_at")


@api_view(["GET", "POST"])
def support_collection(request):
    if request.method == "GET":
        qs = HelpTicket.objects.filter(user=request.user).order_by("-created_at")
        return success("Support tickets retrieved", HelpTicketSerializer(qs, many=True).data)

    data = request.data.copy() if hasattr(request.data, "copy") else dict(request.data)
    uploaded_file = request.FILES.get("attachment")
    if uploaded_file:
        import os
        from django.conf import settings as _s
        upload_dir = os.path.join(_s.MEDIA_ROOT, "support")
        os.makedirs(upload_dir, exist_ok=True)
        filename = f"ticket_{request.user.id}_{uploaded_file.name}"
        filepath = os.path.join(upload_dir, filename)
        with open(filepath, "wb") as f:
            for chunk in uploaded_file.chunks():
                f.write(chunk)
        data["attachment"] = f"{_s.MEDIA_URL}support/{filename}"

    ser = HelpTicketSerializer(data=data)
    ser.is_valid(raise_exception=True)
    ticket = ser.save(user=request.user)
    return success("Support ticket created", HelpTicketSerializer(ticket).data, status=201)


@api_view(["GET"])
def support_show(request, id):
    obj = HelpTicket.objects.filter(id=id, user=request.user).first()
    return success("Support ticket retrieved", HelpTicketSerializer(obj).data) if obj else error("Ticket not found", status=404)


@api_view(["PATCH"])
def support_update_status(request, id):
    obj = HelpTicket.objects.filter(id=id).first()
    if not obj:
        return error("Ticket not found", status=404)
    obj.status = request.data.get("status", obj.status)
    obj.save(update_fields=["status"])
    return success("Status updated", HelpTicketSerializer(obj).data)


@api_view(["GET"])
def notifications_index(request):
    qs = _notif_qs(request.user)
    out = []
    for n in qs[:50]:
        try:
            payload = json.loads(n.data)
        except (ValueError, TypeError):
            payload = {}
        out.append({"id": str(n.id), "type": n.type.split("\\")[-1],
                    "data": payload, "read_at": n.read_at, "created_at": n.created_at})
    return success("Notifications retrieved successfully", out)


@api_view(["POST"])
def notification_mark_read(request, id):
    n = _notif_qs(request.user).filter(id=id).first()
    if not n:
        return error("Notification not found", status=404)
    if n.read_at is None:
        from django.utils import timezone as _tz
        n.read_at = _tz.now()
        n.save(update_fields=["read_at"])
    return success("Marked as read")


@api_view(["GET"])
def notifications_unread_count(request):
    count = _notif_qs(request.user).filter(read_at__isnull=True).count()
    return success("Unread count", {"unread": count})
