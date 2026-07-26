"""Self-service "My Profile" page for the logged-in admin -- mirrors
AuthController::profilePage/updateProfile in the PHP admin, which had no
Django equivalent (api/admin_views.py never built a self-profile endpoint)."""
from django.contrib import messages
from django.contrib.auth import update_session_auth_hash
from django.shortcuts import redirect, render

from ..decorators import admin_required


@admin_required
def profile_view(request):
    user = request.user
    if request.method == "POST":
        for field in ["firstname", "lastname", "email"]:
            if request.POST.get(field):
                setattr(user, field, request.POST[field])
        if request.POST.get("phone"):
            user.phone_number = request.POST["phone"]
        if request.POST.get("password"):
            user.set_password(request.POST["password"])
            user.save()
            update_session_auth_hash(request, user)  # keep the current session valid
        else:
            user.save()
        messages.success(request, "Profile updated successfully.")
        return redirect("webadmin:profile")
    return render(request, "webadmin/profile.html")
