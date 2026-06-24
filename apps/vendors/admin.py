from django.contrib import admin
from .models import Franchise, StateRepresentative, Vendor

admin.site.register(Franchise)
admin.site.register(Vendor)
admin.site.register(StateRepresentative)
