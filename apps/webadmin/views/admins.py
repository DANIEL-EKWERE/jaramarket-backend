"""Mirrors admins_collection/admin_update in api/admin_views.py, plus
toggle-status and reset-permissions (AdminManagementController in the PHP
admin has these; api/admin_views.py never got equivalents)."""
import random
import string

from django.contrib import messages
from django.core.paginator import Paginator
from django.db.models import Q
from django.shortcuts import get_object_or_404, redirect, render
from django.utils import timezone
from django.views.decorators.http import require_POST

from apps.accounts.models import Roles, User
from apps.finance.models import Wallet
from ..decorators import perm_required
from ..permissions_ui import apply_permissions, permission_groups

# Internal staff roles manageable from this screen. State representatives are
# excluded on purpose: they have their own form because they also need a
# state/LGA/address. Customers and vendors are never created here.
STAFF_ROLES = [
    Roles.ADMIN, Roles.SUPER_ADMIN, Roles.STATE_ADMIN, Roles.VENDOR_MANAGER,
    Roles.ACCOUNTS, Roles.AUDIT, Roles.LOGISTICS, Roles.QA,
]
ROLE_LABELS = dict(Roles.CHOICES)


def _role_options():
    return [{"value": r, "label": ROLE_LABELS.get(r, r).title()} for r in STAFF_ROLES]


@perm_required("manage_admins")
def admins_list_view(request):
    qs = User.objects.filter(role__in=STAFF_ROLES, deleted_at__isnull=True).order_by("-created_at")
    if request.GET.get("role") in STAFF_ROLES:
        qs = qs.filter(role=request.GET["role"])
    if request.GET.get("search"):
        term = request.GET["search"]
        qs = qs.filter(Q(firstname__icontains=term) | Q(lastname__icontains=term)
                       | Q(email__icontains=term))
    paginator = Paginator(qs, request.GET.get("per_page", 10))
    page = paginator.get_page(request.GET.get("page"))
    for u in page:
        u.role_label = ROLE_LABELS.get(u.role, u.role).title()
    return render(request, "webadmin/admins/list.html", {
        "page": page, "roles": _role_options()})


@perm_required("manage_admins")
def admin_create_view(request):
    if request.method == "POST":
        data = request.POST
        if not all([data.get("firstname"), data.get("email"), data.get("password")]):
            messages.error(request, "First name, email and password are required.")
        elif User.all_objects.filter(email=data["email"]).exists():
            messages.error(request, "Email already in use.")
        elif data.get("role") not in STAFF_ROLES:
            messages.error(request, "Select a valid role.")
        else:
            admin = User(firstname=data["firstname"], lastname=data.get("lastname", ""),
                         email=data["email"], role=data["role"], phone_number=data.get("phone"),
                         is_active=True, email_verified_at=timezone.now(),
                         referral_code="".join(random.choices(string.ascii_uppercase + string.digits, k=10)))
            admin.set_password(data["password"])
            admin.save()
            Wallet.objects.get_or_create(user=admin, defaults={"balance": 0})
            # Ticked permissions win; otherwise fall back to the role's
            # defaults -- without either, the account logs in to 403s.
            apply_permissions(admin, request)
            messages.success(request,
                             f"{ROLE_LABELS.get(admin.role, admin.role).title()} created successfully.")
            return redirect("webadmin:admins_list")
    return render(request, "webadmin/admins/form.html",
                  {"admin_obj": None, "roles": _role_options(),
                   "permission_groups": permission_groups()})


@perm_required("manage_admins")
def admin_update_view(request, admin_id):
    admin = get_object_or_404(User, id=admin_id, role__in=STAFF_ROLES)
    if request.method == "POST":
        for field in ["firstname", "lastname", "email"]:
            if request.POST.get(field):
                setattr(admin, field, request.POST[field])
        if request.POST.get("phone"):
            admin.phone_number = request.POST["phone"]
        if request.POST.get("password"):
            admin.set_password(request.POST["password"])
        new_role = request.POST.get("role")
        role_changed = new_role in STAFF_ROLES and new_role != admin.role
        if role_changed:
            admin.role = new_role
        admin.save()
        # An explicit selection always wins. With none ticked we fall back to
        # the role's defaults, which also re-syncs after a role change.
        apply_permissions(admin, request)
        messages.success(request, "Staff member updated successfully.")
        return redirect("webadmin:admins_list")
    return render(request, "webadmin/admins/form.html", {
        "admin_obj": admin, "roles": _role_options(),
        "permission_groups": permission_groups(
            admin.permissions_m2m.values_list("slug", flat=True), admin.role)})


@require_POST
@perm_required("manage_admins")
def admin_toggle_status_view(request, admin_id):
    admin = get_object_or_404(User, id=admin_id, role__in=STAFF_ROLES)
    admin.is_active = not admin.is_active
    admin.save(update_fields=["is_active"])
    messages.success(request, f"{admin.name} is now {'active' if admin.is_active else 'inactive'}.")
    return redirect("webadmin:admins_list")


@require_POST
@perm_required("manage_admins")
def admin_reset_permissions_view(request, admin_id):
    admin = get_object_or_404(User, id=admin_id, role__in=STAFF_ROLES)
    admin.sync_default_permissions()
    messages.success(request, f"{admin.name}'s permissions reset to role defaults.")
    return redirect("webadmin:admins_list")


@require_POST
@perm_required("manage_admins")
def admin_delete_view(request, admin_id):
    admin = get_object_or_404(User, id=admin_id, role__in=STAFF_ROLES)
    if admin.id == request.user.id:
        messages.error(request, "You cannot delete yourself.")
        return redirect("webadmin:admins_list")
    admin.deleted_at = timezone.now()
    admin.is_active = False
    admin.save(update_fields=["deleted_at", "is_active"])
    messages.success(request, f"{admin.name} deleted.")
    return redirect("webadmin:admins_list")
