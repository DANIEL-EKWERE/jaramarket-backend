"""Key-value app settings screen, backed by the same `Setting` model that
`apps/orders/views.py:settings_view` (JSON API) already reads/writes.

Field set mirrors SettingsController::update in the PHP admin (minus the
storage_disk/s3_* fields -- those depend on Laravel's runtime disk
reconfiguration, which has no Django equivalent, and storing S3 secret keys
in a plain settings table is best avoided; AWS credentials stay in env vars).
Text/number fields are free-form (Setting.value is just a TEXT column), but
`minimum_order_amount`, `first_order_bonus` and `repeat_order_bonus` are
actually read by api/services/order.py's order-creation logic -- these three
are the only settings with real backend behavior behind them today.
"""
from django.contrib import messages
from django.shortcuts import redirect, render

from apps.support.models import Setting
from ..decorators import perm_required

TEXT_FIELDS = [
    "site_name", "site_description", "contact_email", "contact_phone",
    "support_email", "address", "currency", "timezone",
    "social_facebook", "social_twitter", "social_instagram", "social_youtube", "social_tiktok",
]
NUMBER_FIELDS = [
    "tax_rate", "shipping_fee", "minimum_order_amount",
    "first_order_bonus", "repeat_order_bonus",
]
PAYMENT_METHOD_CHOICES = [
    ("wallet", "Wallet Payment"), ("credit_card", "Credit Card"), ("paypal", "PayPal"),
    ("bank_transfer", "Bank Transfer"), ("cash_on_delivery", "Cash on Delivery"),
]


def _save_uploaded_image(uploaded_file, subfolder):
    from .catalogue import _save_uploaded_image as _impl
    return _impl(uploaded_file, subfolder)


@perm_required("manage_settings")
def settings_view(request):
    if request.method == "POST":
        for key in TEXT_FIELDS + NUMBER_FIELDS:
            if key in request.POST:
                Setting.objects.update_or_create(key=key, defaults={"value": request.POST[key]})
        Setting.objects.update_or_create(
            key="payment_methods", defaults={"value": ",".join(request.POST.getlist("payment_methods"))})
        if "order_statuses" in request.POST:
            Setting.objects.update_or_create(key="order_statuses", defaults={"value": request.POST["order_statuses"]})
        for key, subfolder in [("company_logo", "logo"), ("favicon_logo", "logo")]:
            uploaded = request.FILES.get(key)
            if uploaded:
                Setting.objects.update_or_create(key=key, defaults={"value": _save_uploaded_image(uploaded, subfolder)})
        messages.success(request, "Settings updated successfully.")
        return redirect("webadmin:settings")

    existing = {s.key: s.value for s in Setting.objects.all()}
    selected_payment_methods = (existing.get("payment_methods") or "credit_card").split(",")
    return render(request, "webadmin/settings/list.html", {
        "s": existing,
        "payment_method_choices": PAYMENT_METHOD_CHOICES,
        "selected_payment_methods": selected_payment_methods,
        "order_statuses": existing.get("order_statuses") or "pending\nprocessing\nshipped\ndelivered\ncancelled",
    })
