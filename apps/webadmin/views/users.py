"""Mirrors users_list/user_update/user_toggle_status/user_destroy in
api/admin_views.py."""
from django.contrib import messages
from django.core.paginator import Paginator
from django.db.models import Q
from django.shortcuts import get_object_or_404, redirect, render
from django.utils import timezone
from django.views.decorators.http import require_POST

from apps.accounts.models import Roles, User
from ..decorators import perm_required


@perm_required("view_users")
def users_list_view(request):
    # Exclude synthetic placeholder rows (e.g. id=-1 "deleted-user@placeholder.invalid",
    # kept only to satisfy FK integrity on old orders/wallets after a real user was
    # deleted) -- there's nothing meaningful to edit/toggle/delete on those.
    qs = User.objects.filter(id__gt=0).order_by("-created_at")
    if request.GET.get("role"):
        qs = qs.filter(role=request.GET["role"])
    if request.GET.get("search"):
        s = request.GET["search"]
        qs = qs.filter(Q(firstname__icontains=s) | Q(lastname__icontains=s) | Q(email__icontains=s))
    paginator = Paginator(qs, request.GET.get("per_page", 20))
    return render(request, "webadmin/users/list.html", {
        "page": paginator.get_page(request.GET.get("page")), "roles": Roles.CHOICES})


@perm_required("manage_users")
def user_detail_view(request, user_id):
    user = get_object_or_404(User, id=user_id)
    if request.method == "POST":
        for field in ["firstname", "lastname", "email", "role"]:
            if request.POST.get(field):
                setattr(user, field, request.POST[field])
        user.save()
        if request.POST.get("role"):
            user.sync_default_permissions()
        messages.success(request, "User updated successfully.")
        return redirect("webadmin:user_detail", user_id=user.id)
    return render(request, "webadmin/users/detail.html", {"target_user": user, "roles": Roles.CHOICES})


@require_POST
@perm_required("manage_users", "manage_admins")
def user_toggle_status_view(request, user_id):
    user = get_object_or_404(User, id=user_id)
    user.is_active = not user.is_active
    user.save(update_fields=["is_active"])
    messages.success(request, f"{user.name} is now {'active' if user.is_active else 'inactive'}.")
    return redirect(request.META.get("HTTP_REFERER") or "webadmin:users_list")


@require_POST
@perm_required("manage_users", "manage_admins")
def user_delete_view(request, user_id):
    user = get_object_or_404(User, id=user_id)
    user.deleted_at = timezone.now()
    user.save(update_fields=["deleted_at"])
    messages.success(request, f"{user.name} deleted.")
    return redirect("webadmin:users_list")
