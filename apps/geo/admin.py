from django.contrib import admin
from .models import Country, Lga, State

admin.site.register(Country)
admin.site.register(State)
admin.site.register(Lga)
