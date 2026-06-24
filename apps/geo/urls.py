from django.urls import path
from . import views as v

urlpatterns = [
    path("states", v.states_index),
    path("states/<int:state>", v.state_find),
    path("lgas", v.lgas_index),
    path("lgas/<int:lga>", v.lga_find),
    path("country", v.country_index),
    path("country/<int:c>/states", v.country_states),
]
