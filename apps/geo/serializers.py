from rest_framework import serializers
from .models import Country, Lga, State


class StateSerializer(serializers.ModelSerializer):
    class Meta:
        model = State
        fields = ["id", "name", "country_id"]


class LgaSerializer(serializers.ModelSerializer):
    class Meta:
        model = Lga
        fields = ["id", "name", "state_id"]


class CountrySerializer(serializers.ModelSerializer):
    class Meta:
        model = Country
        fields = ["id", "name", "code", "currency", "currency_sym", "vat"]
