from django.urls import path
from . import views as v

urlpatterns = [
    path("support", v.support_collection),
    path("support/<int:id>", v.support_show),
    path("support/<int:id>/status", v.support_update_status),
    path("notifications", v.notifications_index),
    path("notifications/<str:id>/read", v.notification_mark_read),
    path("notifications/unread-count", v.notifications_unread_count),
]
