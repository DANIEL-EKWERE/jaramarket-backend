"""Access control for the server-rendered admin dashboard.

Mirrors IsAdmin + require_perms in api/admin_views.py exactly, just redirecting
/ rendering a 403 page instead of returning a JSON error response — same roles,
same permission slugs, same rules as the JSON admin API.
"""
from functools import wraps

from django.contrib import messages
from django.contrib.auth.decorators import login_required
from django.shortcuts import redirect, render


def admin_required(view_func):
    """Require a logged-in user whose role is one of Roles.ADMIN_ROLES."""
    @wraps(view_func)
    @login_required(login_url="webadmin:login")
    def wrapper(request, *args, **kwargs):
        if not request.user.is_admin():
            messages.error(request, "Admin access required.")
            return redirect("webadmin:login")
        return view_func(request, *args, **kwargs)
    return wrapper


def perm_required(*slugs):
    """Require the logged-in admin to hold at least one of the given
    permission slugs (mirrors require_perms() in api/admin_views.py)."""
    def decorator(view_func):
        @wraps(view_func)
        @admin_required
        def wrapper(request, *args, **kwargs):
            if not request.user.has_any_permission(slugs):
                return render(request, "webadmin/403.html", status=403)
            return view_func(request, *args, **kwargs)
        return wrapper
    return decorator
