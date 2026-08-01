"""Market CRUD -- admin creates the physical market locations vendors are
stationed at, so orders can be routed to whichever is closest to the buyer.
Mirrors franchises.py's structure, with a toggle-status action like
representatives.py since Market also has an is_active flag."""
from django.contrib import messages
from django.core.paginator import Paginator
from django.shortcuts import get_object_or_404, redirect, render
from django.views.decorators.http import require_POST

from apps.geo.models import Lga, State
from apps.vendors.models import Market
from ..decorators import perm_required


@perm_required("view_markets")
def markets_list_view(request):
    qs = Market.objects.select_related("state", "lga").order_by("-created_at")
    paginator = Paginator(qs, request.GET.get("per_page", 15))
    return render(request, "webadmin/markets/list.html",
                  {"page": paginator.get_page(request.GET.get("page"))})


def _market_form_context(market=None):
    return {"market": market, "states": State.objects.order_by("name"),
            "lgas": Lga.objects.order_by("name")}


@perm_required("manage_markets")
def market_create_view(request):
    if request.method == "POST":
        data = request.POST
        required = ["name", "latitude", "longitude"]
        if not all(data.get(f) for f in required):
            messages.error(request, "Name, latitude and longitude are required.")
        else:
            Market.objects.create(
                name=data["name"], address=data.get("address"),
                state_id=data.get("state_id") or None, lga_id=data.get("lga_id") or None,
                latitude=data["latitude"], longitude=data["longitude"],
                is_active=bool(data.get("is_active")))
            messages.success(request, "Market created successfully.")
            return redirect("webadmin:markets_list")
    return render(request, "webadmin/markets/form.html", _market_form_context())


@perm_required("manage_markets")
def market_update_view(request, id):
    market = get_object_or_404(Market, id=id)
    if request.method == "POST":
        data = request.POST
        required = ["name", "latitude", "longitude"]
        if not all(data.get(f) for f in required):
            messages.error(request, "Name, latitude and longitude are required.")
        else:
            market.name = data["name"]
            market.address = data.get("address")
            market.state_id = data.get("state_id") or None
            market.lga_id = data.get("lga_id") or None
            market.latitude = data["latitude"]
            market.longitude = data["longitude"]
            market.is_active = bool(data.get("is_active"))
            market.save()
            messages.success(request, "Market updated successfully.")
            return redirect("webadmin:markets_list")
    return render(request, "webadmin/markets/form.html", _market_form_context(market))


@require_POST
@perm_required("manage_markets")
def market_toggle_status_view(request, id):
    market = get_object_or_404(Market, id=id)
    market.is_active = not market.is_active
    market.save(update_fields=["is_active"])
    messages.success(request, f"{market.name} is now {'active' if market.is_active else 'inactive'}.")
    return redirect("webadmin:markets_list")


@require_POST
@perm_required("manage_markets")
def market_delete_view(request, id):
    get_object_or_404(Market, id=id).delete()
    messages.success(request, "Market deleted successfully.")
    return redirect("webadmin:markets_list")
