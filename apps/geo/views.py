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
