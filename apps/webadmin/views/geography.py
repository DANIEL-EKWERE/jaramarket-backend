"""Where we operate, and where customers wish we did.

Two halves of one decision: the service areas gate ordering, and the
watchlist is the evidence for which area to open next.
"""
from django.contrib import messages
from django.core.paginator import Paginator
from django.db.models import Sum
from django.shortcuts import get_object_or_404, redirect, render
from django.views.decorators.http import require_POST

from apps.geo.models import ExpansionRequest, Lga, ServiceArea, State
from apps.geo.services import enforcement_enabled
from ..decorators import perm_required


@perm_required("manage_settings")
def service_areas_view(request):
    areas = (ServiceArea.objects.select_related("state", "lga")
             .order_by("state__name", "lga__name"))
    return render(request, "webadmin/geo/service_areas.html", {
        "areas": areas,
        "enforced": enforcement_enabled(),
        "states": State.objects.order_by("name"),
        "lgas": Lga.objects.select_related("state").order_by("state__name", "name"),
        "watchlist_count": ExpansionRequest.objects.count(),
    })


@require_POST
@perm_required("manage_settings")
def service_area_add_view(request):
    state_id = request.POST.get("state_id")
    lga_id = request.POST.get("lga_id") or None
    if not state_id:
        messages.error(request, "Pick a state.")
        return redirect("webadmin:service_areas")

    # A state-wide row already covers every LGA in it, so adding one on top
    # is noise that only makes the list harder to read.
    if lga_id and ServiceArea.objects.filter(state_id=state_id, lga__isnull=True,
                                             is_active=True).exists():
        messages.warning(request, "That whole state is already covered — "
                                  "remove the state-wide entry first to narrow it.")
        return redirect("webadmin:service_areas")

    _, created = ServiceArea.objects.get_or_create(
        state_id=state_id, lga_id=lga_id,
        defaults={"note": request.POST.get("note") or None})
    messages.success(request, "Service area added." if created
                     else "That area is already on the list.")
    return redirect("webadmin:service_areas")


@require_POST
@perm_required("manage_settings")
def service_area_toggle_view(request, id):
    area = get_object_or_404(ServiceArea, id=id)
    area.is_active = not area.is_active
    area.save(update_fields=["is_active"])
    messages.success(request, f"{area.state.name} "
                              f"{'resumed' if area.is_active else 'paused'}.")
    return redirect("webadmin:service_areas")


@require_POST
@perm_required("manage_settings")
def service_area_delete_view(request, id):
    area = get_object_or_404(ServiceArea, id=id)
    label = str(area)
    area.delete()
    if not ServiceArea.objects.filter(is_active=True).exists():
        messages.warning(request, f"Removed {label}. No active service areas remain, "
                                  f"so ordering is now open everywhere again.")
    else:
        messages.success(request, f"Removed {label}.")
    return redirect("webadmin:service_areas")


@perm_required("view_reports")
def expansion_watchlist_view(request):
    qs = (ExpansionRequest.objects.select_related("state", "lga")
          .order_by("-unique_users", "-request_count"))
    search = request.GET.get("search")
    if search:
        qs = qs.filter(state__name__icontains=search) | qs.filter(
            lga__name__icontains=search)
    paginator = Paginator(qs, request.GET.get("per_page", 25))
    page = paginator.get_page(request.GET.get("page"))

    totals = ExpansionRequest.objects.aggregate(
        attempts=Sum("request_count"), people=Sum("unique_users"))
    # Which states already have a service area, so the page can say "you can
    # open this one now" instead of making the admin cross-reference.
    covered = set(ServiceArea.objects.filter(is_active=True)
                  .values_list("state_id", flat=True))
    for row in page:
        row.already_covered = row.state_id in covered
    return render(request, "webadmin/geo/expansion_watchlist.html", {
        "page": page,
        "total_attempts": totals["attempts"] or 0,
        "total_people": totals["people"] or 0,
        "locations": ExpansionRequest.objects.count(),
    })
