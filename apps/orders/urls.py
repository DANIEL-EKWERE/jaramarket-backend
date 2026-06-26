from django.urls import path
from . import views as v

urlpatterns = [
    path("orders", v.orders_collection),
    path("orders/<int:order>", v.order_show),
    path("orders/<int:order>/cancel", v.order_cancel),
    path("orders/<int:order>/complete", v.order_mark_completed),
    path("vendor/dashboard", v.vendor_dashboard),
    path("vendor/orders", v.vendor_available_orders),
    path("vendor/orders/accepted", v.vendor_my_orders),
    path("vendor/orders/<int:item_id>", v.vendor_order_item),
    path("vendor/orders/item/<int:item_id>/decision", v.vendor_decide),
    path("settings", v.settings_view),
]
