"""JWT auth middleware for Channels — authenticates the WS handshake via
?token=<access> query param (or Authorization header), mirroring the API's JWT."""
from urllib.parse import parse_qs

from channels.db import database_sync_to_async
from channels.middleware import BaseMiddleware
from django.contrib.auth.models import AnonymousUser


@database_sync_to_async
def _get_user(user_id):
    from .models import User
    try:
        return User.objects.get(id=user_id)
    except User.DoesNotExist:
        return AnonymousUser()


class JWTAuthMiddleware(BaseMiddleware):
    async def __call__(self, scope, receive, send):
        token = None
        qs = parse_qs(scope.get("query_string", b"").decode())
        if "token" in qs:
            token = qs["token"][0]
        if not token:
            for name, value in scope.get("headers", []):
                if name == b"authorization":
                    parts = value.decode().split()
                    if len(parts) == 2 and parts[0].lower() == "bearer":
                        token = parts[1]
        scope["user"] = AnonymousUser()
        if token:
            try:
                from rest_framework_simplejwt.tokens import AccessToken
                user_id = AccessToken(token)["user_id"]
                scope["user"] = await _get_user(user_id)
            except Exception:
                pass
        return await super().__call__(scope, receive, send)
