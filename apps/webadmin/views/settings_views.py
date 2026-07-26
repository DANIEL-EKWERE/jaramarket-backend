"""Key-value app settings screen, backed by the same `Setting` model that
`apps/orders/views.py:settings_view` (JSON API) already reads/writes."""
from django.contrib import messages
from django.shortcuts import redirect, render

from apps.support.models import Setting
from ..decorators import perm_required

# Keys always shown even if not yet present in the DB, so admins have a
# consistent form to fill in rather than needing to know key names upfront.
KNOWN_KEYS = [
    "app_name", "support_email", "support_phone", "min_withdrawal_amount",
    "maintenance_mode",
]


@perm_required("manage_settings")
def settings_view(request):
    if request.method == "POST":
        for key, value in request.POST.items():
            if key == "csrfmiddlewaretoken":
                continue
            Setting.objects.update_or_create(key=key, defaults={"value": value})
        messages.success(request, "Settings saved successfully.")
        return redirect("webadmin:settings")

    existing = {s.key: s.value for s in Setting.objects.all()}
    keys = list(dict.fromkeys(KNOWN_KEYS + list(existing.keys())))
    rows = [{"key": k, "value": existing.get(k, "")} for k in keys]
    return render(request, "webadmin/settings/list.html", {"rows": rows})
