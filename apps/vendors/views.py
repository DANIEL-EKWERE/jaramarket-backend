from rest_framework.decorators import api_view, permission_classes
from rest_framework.permissions import AllowAny

from api.utils import success
from .models import Market
from .serializers import MarketSerializer


@api_view(["GET"])
@permission_classes([AllowAny])
def markets_collection(request):
    markets = Market.objects.filter(is_active=True).order_by("name")
    state_id = request.query_params.get("state_id")
    if state_id:
        markets = markets.filter(state_id=state_id)
    lga_id = request.query_params.get("lga_id")
    if lga_id:
        markets = markets.filter(lga_id=lga_id)
    return success("Markets retrieved successfully", MarketSerializer(markets, many=True).data)
