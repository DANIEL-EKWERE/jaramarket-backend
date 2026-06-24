"""ASGI config — HTTP (Django) + WebSocket (Channels) protocol routing."""
import os

from django.core.asgi import get_asgi_application

os.environ.setdefault("DJANGO_SETTINGS_MODULE", "jaraman.settings")
django_asgi_app = get_asgi_application()  # initialise apps before importing routing

from channels.routing import ProtocolTypeRouter, URLRouter  # noqa: E402

from api.routing import websocket_urlpatterns  # noqa: E402
from api.ws_auth import JWTAuthMiddleware  # noqa: E402

application = ProtocolTypeRouter({
    "http": django_asgi_app,
    "websocket": JWTAuthMiddleware(URLRouter(websocket_urlpatterns)),
})
