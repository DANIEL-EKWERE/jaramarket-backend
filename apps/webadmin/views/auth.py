from django.contrib import messages
from django.contrib.auth import authenticate, login as auth_login, logout as auth_logout
from django.shortcuts import redirect, render


def login_view(request):
    if request.user.is_authenticated and request.user.is_admin():
        return redirect("webadmin:dashboard")

    if request.method == "POST":
        email = request.POST.get("email", "").strip().lower()
        password = request.POST.get("password", "")
        user = authenticate(request, username=email, password=password)
        if user is None:
            messages.error(request, "Invalid email or password.")
        elif not user.is_admin():
            messages.error(request, "This account does not have admin access.")
        elif not user.is_active:
            messages.error(request, "This account is not active.")
        else:
            auth_login(request, user)
            return redirect("webadmin:dashboard")

    return render(request, "webadmin/login.html")


def logout_view(request):
    auth_logout(request)
    return redirect("webadmin:login")
