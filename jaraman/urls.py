"""Project URLs. The Laravel API lived under the `jaram` prefix; we mount the
api app there so every path matches the original (e.g. POST jaram/login)."""
from django.contrib import admin
from django.http import JsonResponse
from django.urls import include, path
from django.conf import settings
from django.conf.urls.static import static
from rest_framework_simplejwt.views import TokenRefreshView


def health_check(request):
    return JsonResponse({"status": "ok"})


urlpatterns = [
    path("", health_check),                           # Render / load-balancer health probe
    path("admin/", admin.site.urls),                 # Django admin = drop-in for the Blade admin panel
    path("jaram/", include("api.urls")),             # matches Laravel Route::prefix('jaram')
    path("api/jaram/", include("api.urls")),          # also available under /api for gateways/proxies
    path("jaram/token/refresh", TokenRefreshView.as_view()),  # JWT refresh
] + static(settings.MEDIA_URL, document_root=settings.MEDIA_ROOT)
