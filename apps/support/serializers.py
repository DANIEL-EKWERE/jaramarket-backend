from rest_framework import serializers
from .models import HelpTicket, Support


class SupportSerializer(serializers.ModelSerializer):
    class Meta:
        model = Support
        fields = ["id", "message", "attachment", "status", "created_at"]
        read_only_fields = ["status"]


class HelpTicketSerializer(serializers.ModelSerializer):
    class Meta:
        model = HelpTicket
        fields = ["id", "subject", "message", "attachment", "status", "created_at"]
        read_only_fields = ["status"]
