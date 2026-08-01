"""Mirrors StateRepresentativeController in the PHP admin: create provisions a
brand-new user account for the representative (random password, role
STATE_REPRESENTATIVE), edit updates both the user's name/email and the
representative's own fields, toggle-status flips is_active, and delete
removes the representative *and* its linked user -- same behavior as the
PHP version's destroy(). Unlike the PHP schema (which has no real user_id
column despite the model declaring a `user()` relation), the Django
StateRepresentative keeps a genuine FK to both User and State."""
import random
import string

from django.contrib import messages
from django.core.paginator import Paginator
from django.shortcuts import get_object_or_404, redirect, render
from django.utils import timezone
from django.views.decorators.http import require_POST

from apps.accounts.models import Roles, User
from apps.finance.models import Wallet
from apps.geo.models import State
from apps.vendors.models import StateRepresentative
from ..decorators import perm_required


@perm_required("view_representatives")
def representatives_list_view(request):
    qs = StateRepresentative.objects.select_related("user", "state").order_by("-created_at")
    paginator = Paginator(qs, request.GET.get("per_page", 15))
    return render(request, "webadmin/representatives/list.html",
                  {"page": paginator.get_page(request.GET.get("page"))})


@perm_required("manage_representatives")
def representative_create_view(request):
    if request.method == "POST":
        data = request.POST
        required = ["firstname", "lastname", "email", "phone", "state_id", "lga", "address", "password"]
        if not all(data.get(f) for f in required):
            messages.error(request, "All fields are required, including a password.")
        elif User.all_objects.filter(email=data["email"]).exists():
            messages.error(request, "Email already in use.")
        else:
            user = User(firstname=data["firstname"], lastname=data["lastname"], email=data["email"],
                        phone_number=data["phone"], role=Roles.STATE_REPRESENTATIVE, is_active=True,
                        email_verified_at=timezone.now(),
                        referral_code="".join(random.choices(string.ascii_uppercase + string.digits, k=10)))
            user.set_password(data["password"])
            user.save()
            Wallet.objects.get_or_create(user=user, defaults={"balance": 0})
            StateRepresentative.objects.create(user=user, state_id=data["state_id"], phone=data["phone"],
                                                lga=data["lga"], address=data["address"], is_active=True)
            messages.success(request, "State representative created successfully.")
            return redirect("webadmin:representatives_list")
    return render(request, "webadmin/representatives/form.html",
                  {"rep": None, "states": State.objects.order_by("name")})


@perm_required("manage_representatives")
def representative_update_view(request, id):
    rep = get_object_or_404(StateRepresentative, id=id)
    if request.method == "POST":
        data = request.POST
        required = ["firstname", "lastname", "email", "phone", "state_id", "lga", "address"]
        if not all(data.get(f) for f in required):
            messages.error(request, "All fields are required.")
        elif User.all_objects.filter(email=data["email"]).exclude(id=rep.user_id).exists():
            messages.error(request, "Email already in use.")
        else:
            rep.user.firstname = data["firstname"]
            rep.user.lastname = data["lastname"]
            rep.user.email = data["email"]
            rep.user.phone_number = data["phone"]
            if data.get("password"):
                rep.user.set_password(data["password"])
            rep.user.save()
            rep.state_id = data["state_id"]
            rep.phone = data["phone"]
            rep.lga = data["lga"]
            rep.address = data["address"]
            rep.save()
            messages.success(request, "State representative updated successfully.")
            return redirect("webadmin:representatives_list")
    return render(request, "webadmin/representatives/form.html",
                  {"rep": rep, "states": State.objects.order_by("name")})


@require_POST
@perm_required("manage_representatives")
def representative_toggle_status_view(request, id):
    rep = get_object_or_404(StateRepresentative, id=id)
    rep.is_active = not rep.is_active
    rep.save(update_fields=["is_active"])
    messages.success(request, f"{rep.user.name} is now {'active' if rep.is_active else 'inactive'}.")
    return redirect("webadmin:representatives_list")


@require_POST
@perm_required("manage_representatives")
def representative_delete_view(request, id):
    rep = get_object_or_404(StateRepresentative, id=id)
    user = rep.user
    rep.delete()
    if user:
        user.deleted_at = timezone.now()
        user.is_active = False
        user.save(update_fields=["deleted_at", "is_active"])
    messages.success(request, "State representative removed.")
    return redirect("webadmin:representatives_list")
