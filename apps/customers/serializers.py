from decimal import Decimal, InvalidOperation

from rest_framework import serializers
from apps.geo.models import Country, Lga, State
from .models import Address, Favorite


class CoordinateField(serializers.DecimalField):
    """A GPS coordinate, accepted at whatever precision the device reports.

    The column stores 7 decimal places (~1cm), but phones hand back far more
    (e.g. 7.9203888123456). DRF checks the digit count before saving, so those
    raw readings were rejected outright with "no more than 10 digits in
    total" and the address could never be saved. Round to the stored
    precision instead of failing the request.
    """

    def __init__(self, **kwargs):
        kwargs.setdefault("max_digits", 10)
        kwargs.setdefault("decimal_places", 7)
        super().__init__(**kwargs)

    def to_internal_value(self, data):
        try:
            data = Decimal(str(data).strip()).quantize(Decimal("0.0000001"))
        except (InvalidOperation, TypeError, ValueError, ArithmeticError):
            pass  # not a number -- let DRF raise its normal error
        return super().to_internal_value(data)


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
    # Required on creation (not on partial updates) — orders can't be routed
    # to the closest market without a location, so checkout is blocked until
    # the app supplies device geolocation for the address.
    latitude = CoordinateField(required=True)
    longitude = CoordinateField(required=True)

    class Meta:
        model = Address
        fields = [
            "id", "user_id", "country_id", "state_id", "lga_id",
            "contact_address", "phone_number", "is_default", "latitude", "longitude",
            "created_at", "updated_at",
        ]
        read_only_fields = ["user_id"]


class FavoriteSerializer(serializers.ModelSerializer):
    from apps.catalogue.serializers import IngredientSerializer
    ingredient = IngredientSerializer(read_only=True)

    class Meta:
        model = Favorite
        fields = ["id", "ingredient", "created_at"]
