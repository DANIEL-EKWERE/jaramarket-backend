from django.urls import path
from . import views as v

urlpatterns = [
    path("favorites", v.favorites_collection),
    path("favorites/<int:id>", v.favorite_delete),
    path("addresses", v.addresses_collection),
    path("cart", v.cart_collection),
    path("cart/<int:id>", v.cart_show),
    path("carts/<int:id>", v.cart_show),
    path("cart/<int:id>/update", v.cart_update_item),
    path("cart/<int:id>/remove", v.cart_remove_item),
]
