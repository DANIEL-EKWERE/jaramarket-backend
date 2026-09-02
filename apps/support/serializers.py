from rest_framework import serializers
from .models import HelpTicket, Support, TicketReply


class SupportSerializer(serializers.ModelSerializer):
    class Meta:
        model = Support
        fields = ["id", "message", "attachment", "status", "created_at"]
        read_only_fields = ["status"]


class TicketReplySerializer(serializers.ModelSerializer):
    author_name = serializers.SerializerMethodField()

    class Meta:
        model = TicketReply
        fields = ["id", "message", "is_staff", "author_name", "created_at"]

    def get_author_name(self, obj):
        # Staff replies speak for the platform, not for the individual who
        # happened to type them.
        return "Jara Market Support" if obj.is_staff else (
            obj.author.name if obj.author_id else "You")


class HelpTicketSerializer(serializers.ModelSerializer):
    replies = TicketReplySerializer(many=True, read_only=True)
    last_reply_at = serializers.SerializerMethodField()
    has_staff_reply = serializers.SerializerMethodField()

    class Meta:
        model = HelpTicket
        fields = ["id", "subject", "message", "attachment", "status", "created_at",
                  "replies", "last_reply_at", "has_staff_reply"]
        read_only_fields = ["status"]

    def get_last_reply_at(self, obj):
        last = obj.replies.last()
        return last.created_at if last else None

    def get_has_staff_reply(self, obj):
        return any(r.is_staff for r in obj.replies.all())
