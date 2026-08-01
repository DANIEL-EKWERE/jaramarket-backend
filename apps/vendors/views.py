from rest_framework.decorators import api_view

from api.utils import success
from .models import Market
from .serializers import MarketSerializer


@api_view(["GET"])
def markets_collection(request):
    markets = Market.objects.filter(is_active=True).order_by("name")
    return success("Markets retrieved successfully", MarketSerializer(markets, many=True).data)
