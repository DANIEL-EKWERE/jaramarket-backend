from rest_framework.decorators import api_view, permission_classes
from rest_framework.permissions import AllowAny

from api.utils import error, success
from .models import Country, Lga, State
from .serializers import CountrySerializer, LgaSerializer, StateSerializer


@api_view(["GET"])
@permission_classes([AllowAny])
def states_index(request):
    return success("States retrieved", StateSerializer(State.objects.all(), many=True).data)


@api_view(["GET"])
@permission_classes([AllowAny])
def state_find(request, state):
    obj = State.objects.filter(id=state).first()
    return success("State retrieved", StateSerializer(obj).data) if obj else error("State not found", status=404)


@api_view(["GET"])
@permission_classes([AllowAny])
def lgas_index(request):
    qs = Lga.objects.all()
    state = request.query_params.get("state") or request.query_params.get("state_id")
    if state:
        if str(state).isdigit():
            qs = qs.filter(state_id=int(state))
        else:
            qs = qs.filter(state__name__iexact=state)
    return success("LGAs retrieved", LgaSerializer(qs, many=True).data)


@api_view(["GET"])
@permission_classes([AllowAny])
def lga_find(request, lga):
    obj = Lga.objects.filter(id=lga).first()
    return success("LGA retrieved", LgaSerializer(obj).data) if obj else error("LGA not found", status=404)


@api_view(["GET"])
@permission_classes([AllowAny])
def country_index(request):
    return success("Countries retrieved", CountrySerializer(Country.objects.all(), many=True).data)


@api_view(["GET"])
@permission_classes([AllowAny])
def country_states(request, c):
    return success("States retrieved", StateSerializer(State.objects.filter(country_id=c), many=True).data)


@api_view(["GET"])
@permission_classes([AllowAny])
def service_area_check(request):
    """Is an address deliverable? Asked by the app before checkout.

    Also accepts a bare state/LGA so the app can warn while the customer is
    still choosing an address, rather than at the payment screen.
    """
    from .services import is_serviced, enforcement_enabled, unavailable_message
    from .models import Lga, State

    state_id = request.query_params.get("state_id") or None
    lga_id = request.query_params.get("lga_id") or None
    serviced = is_serviced(state_id, lga_id)
    state = State.objects.filter(id=state_id).first() if state_id else None
    lga = Lga.objects.filter(id=lga_id).first() if lga_id else None

    return success("Service availability", {
        "serviced": serviced,
        "enforced": enforcement_enabled(),
        "state_id": int(state_id) if state_id else None,
        "lga_id": int(lga_id) if lga_id else None,
        "message": None if serviced else unavailable_message(
            state.name if state else None, lga.name if lga else None),
    })


@api_view(["GET"])
@permission_classes([AllowAny])
def service_areas_index(request):
    """Everywhere we currently deliver -- lets the app show a picker that only
    offers places that actually work."""
    from .models import ServiceArea

    rows = (ServiceArea.objects.filter(is_active=True)
            .select_related("state", "lga"))
    return success("Service areas retrieved", [{
        "state_id": r.state_id,
        "state_name": r.state.name,
        "lga_id": r.lga_id,
        "lga_name": r.lga.name if r.lga_id else None,
        "covers_whole_state": r.lga_id is None,
    } for r in rows])
