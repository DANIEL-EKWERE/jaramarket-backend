"""Mirrors admins_collection/admin_update in api/admin_views.py, plus
toggle-status and reset-permissions (AdminManagementController in the PHP
admin has these; api/admin_views.py never got equivalents)."""
import random
import string

from django.contrib import messages
from django.core.paginator import Paginator
from django.shortcuts import get_object_or_404, redirect, render
from django.utils import timezone
from django.views.decorators.http import require_POST

from apps.accounts.models import Roles, User
from apps.finance.models import Wallet
from ..decorators import perm_required


@perm_required("manage_admins")
def admins_list_view(request):
    qs = User.objects.filter(role=Roles.ADMIN, deleted_at__isnull=True).order_by("-created_at")
    paginator = Paginator(qs, request.GET.get("per_page", 10))
    return render(request, "webadmin/admins/list.html", {"page": paginator.get_page(request.GET.get("page"))})


@perm_required("manage_admins")
def admin_create_view(request):
    if request.method == "POST":
        data = request.POST
        if not all([data.get("firstname"), data.get("email"), data.get("password")]):
            messages.error(request, "First name, email and password are required.")
        elif User.all_objects.filter(email=data["email"]).exists():
            messages.error(request, "Email already in use.")
        else:
            admin = User(firstname=data["firstname"], lastname=data.get("lastname", ""),
                         email=data["email"], role=Roles.ADMIN, phone_number=data.get("phone"),
                         is_active=True, email_verified_at=timezone.now(),
                         referral_code="".join(random.choices(string.ascii_uppercase + string.digits, k=10)))
            admin.set_password(data["password"])
            admin.save()
            Wallet.objects.get_or_create(user=admin, defaults={"balance": 0})
            messages.success(request, "Admin created successfully.")
            return redirect("webadmin:admins_list")
    return render(request, "webadmin/admins/form.html", {"admin_obj": None})


@perm_required("manage_admins")
def admin_update_view(request, admin_id):
    admin = get_object_or_404(User, id=admin_id, role=Roles.ADMIN)
    if request.method == "POST":
        for field in ["firstname", "lastname", "email"]:
            if request.POST.get(field):
                setattr(admin, field, request.POST[field])
        if request.POST.get("phone"):
            admin.phone_number = request.POST["phone"]
        if request.POST.get("password"):
            admin.set_password(request.POST["password"])
        admin.save()
        messages.success(request, "Admin updated successfully.")
        return redirect("webadmin:admins_list")
    return render(request, "webadmin/admins/form.html", {"admin_obj": admin})


@require_POST
@perm_required("manage_admins")
def admin_toggle_status_view(request, admin_id):
    admin = get_object_or_404(User, id=admin_id, role=Roles.ADMIN)
    admin.is_active = not admin.is_active
    admin.save(update_fields=["is_active"])
    messages.success(request, f"{admin.name} is now {'active' if admin.is_active else 'inactive'}.")
    return redirect("webadmin:admins_list")


@require_POST
@perm_required("manage_admins")
def admin_reset_permissions_view(request, admin_id):
    admin = get_object_or_404(User, id=admin_id, role=Roles.ADMIN)
    admin.sync_default_permissions()
    messages.success(request, f"{admin.name}'s permissions reset to role defaults.")
    return redirect("webadmin:admins_list")


@require_POST
@perm_required("manage_admins")
def admin_delete_view(request, admin_id):
    admin = get_object_or_404(User, id=admin_id, role=Roles.ADMIN)
    if admin.id == request.user.id:
        messages.error(request, "You cannot delete yourself.")
        return redirect("webadmin:admins_list")
    admin.deleted_at = timezone.now()
    admin.is_active = False
    admin.save(update_fields=["deleted_at", "is_active"])
    messages.success(request, f"{admin.name} deleted.")
    return redirect("webadmin:admins_list")
