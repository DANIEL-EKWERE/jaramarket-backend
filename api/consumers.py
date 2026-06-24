"""WebSocket consumers. The per-user private channel mirrors Laravel's
Broadcast::channel('App.Models.User.{id}') used for live notifications."""
import json

from channels.generic.websocket import AsyncWebsocketConsumer


class UserConsumer(AsyncWebsocketConsumer):
    async def connect(self):
        user = self.scope.get("user")
        if not user or not getattr(user, "is_authenticated", False):
            await self.close(code=4401)
            return
        self.group_name = f"user.{user.id}"
        await self.channel_layer.group_add(self.group_name, self.channel_name)
        await self.accept()
        await self.send(text_data=json.dumps({"type": "connected", "user_id": user.id}))

    async def disconnect(self, code):
        if hasattr(self, "group_name"):
            await self.channel_layer.group_discard(self.group_name, self.channel_name)

    # Server -> client push (called via group_send with type "notify")
    async def notify(self, event):
        await self.send(text_data=json.dumps(event.get("payload", {})))
