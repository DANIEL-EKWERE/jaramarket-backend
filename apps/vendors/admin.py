from django.contrib import admin
from .models import Franchise, Market, StateRepresentative, Vendor

admin.site.register(Franchise)
admin.site.register(Vendor)
admin.site.register(StateRepresentative)
admin.site.register(Market)
