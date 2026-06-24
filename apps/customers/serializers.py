from rest_framework import serializers
from apps.geo.models import Country, Lga, State
from .models import Address, Favorite


class AddressSerializer(serializers.ModelSerializer):
    country_id = serializers.PrimaryKeyRelatedField(
        queryset=Country.objects.all(), source="country"
    )
    state_id = serializers.PrimaryKeyRelatedField(
        queryset=State.objects.all(), source="state"
    )
    lga_id = serializers.PrimaryKeyRelatedField(
        queryset=Lga.objects.all(), source="lga"
    )

    class Meta:
        model = Address
        fields = [
            "id", "user_id", "country_id", "state_id", "lga_id",
            "contact_address", "phone_number", "is_default", "created_at", "updated_at",
        ]
        read_only_fields = ["user_id"]


class FavoriteSerializer(serializers.ModelSerializer):
    from apps.catalogue.serializers import IngredientSerializer
    ingredient = IngredientSerializer(read_only=True)

    class Meta:
        model = Favorite
        fields = ["id", "ingredient", "created_at"]
