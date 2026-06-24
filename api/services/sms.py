"""SMS gateway (Termii)."""
import requests
from django.conf import settings


class Termii:
    def send(self, to, message):
        if not settings.TERMII_API_KEY:
            return {"skipped": True}
        return requests.post(f"{settings.TERMII_BASE_URL}/api/sms/send", json={
            "to": to, "from": settings.TERMII_SENDER_ID, "sms": message,
            "type": "plain", "channel": "generic", "api_key": settings.TERMII_API_KEY},
            timeout=30).json()
