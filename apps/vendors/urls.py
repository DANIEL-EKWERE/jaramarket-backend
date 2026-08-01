from django.urls import path
from . import views as v

urlpatterns = [
    path("markets", v.markets_collection),
]
