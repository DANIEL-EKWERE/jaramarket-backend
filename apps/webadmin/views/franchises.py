"""Mirrors FranchiseController (index/create/store/edit/update/destroy) in the
PHP admin -- api/admin_views.py never got a Franchise endpoint."""
from django.contrib import messages
from django.core.paginator import Paginator
from django.shortcuts import get_object_or_404, redirect, render
from django.views.decorators.http import require_POST

from apps.accounts.models import User
from apps.vendors.models import Franchise
from ..decorators import perm_required


@perm_required("view_franchises")
def franchises_list_view(request):
    qs = Franchise.objects.select_related("owner").order_by("-created_at")
    paginator = Paginator(qs, request.GET.get("per_page", 15))
    return render(request, "webadmin/franchises/list.html",
                  {"page": paginator.get_page(request.GET.get("page"))})


@perm_required("manage_franchises")
def franchise_create_view(request):
    if request.method == "POST":
        data = request.POST
        if not all([data.get("name"), data.get("location"), data.get("owner_id")]):
            messages.error(request, "Name, location and owner are required.")
        else:
            Franchise.objects.create(name=data["name"], location=data["location"],
                                      owner_id=data["owner_id"])
            messages.success(request, "Franchise created successfully.")
            return redirect("webadmin:franchises_list")
    return render(request, "webadmin/franchises/form.html",
                  {"franchise": None, "users": User.objects.filter(id__gt=0).order_by("firstname")})


@perm_required("manage_franchises")
def franchise_update_view(request, id):
    franchise = get_object_or_404(Franchise, id=id)
    if request.method == "POST":
        data = request.POST
        if not all([data.get("name"), data.get("location"), data.get("owner_id")]):
            messages.error(request, "Name, location and owner are required.")
        else:
            franchise.name = data["name"]
            franchise.location = data["location"]
            franchise.owner_id = data["owner_id"]
            franchise.save()
            messages.success(request, "Franchise updated successfully.")
            return redirect("webadmin:franchises_list")
    return render(request, "webadmin/franchises/form.html",
                  {"franchise": franchise, "users": User.objects.filter(id__gt=0).order_by("firstname")})


@require_POST
@perm_required("manage_franchises")
def franchise_delete_view(request, id):
    get_object_or_404(Franchise, id=id).delete()
    messages.success(request, "Franchise deleted successfully.")
    return redirect("webadmin:franchises_list")
