from rest_framework import serializers
from .models import Market


class MarketSerializer(serializers.ModelSerializer):
    state_name = serializers.CharField(source="state.name", read_only=True, default=None)
    lga_name = serializers.CharField(source="lga.name", read_only=True, default=None)

    class Meta:
        model = Market
        fields = ["id", "name", "address", "state_id", "state_name", "lga_id", "lga_name",
                  "latitude", "longitude", "is_active"]
