from rest_framework.decorators import api_view, permission_classes
from rest_framework.permissions import AllowAny

from api.utils import success
from .models import Market
from .serializers import MarketSerializer


@api_view(["GET"])
@permission_classes([AllowAny])
def markets_collection(request):
    markets = Market.objects.filter(is_active=True).order_by("name")
    return success("Markets retrieved successfully", MarketSerializer(markets, many=True).data)
