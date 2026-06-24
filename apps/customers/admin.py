from django.contrib import admin
from .models import Address, Cart, CartItem, Favorite

admin.site.register(Address)
admin.site.register(Cart)
admin.site.register(Favorite)
