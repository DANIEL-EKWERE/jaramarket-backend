"""
Admin site configuration and enhanced UserAdmin.

Domain models are registered in their respective app admin.py files.
This file upgrades the User registration with a tailored UserAdmin.
"""
from django.contrib import admin
from django.contrib.auth.admin import UserAdmin as BaseUserAdmin

from apps.accounts.models import FCMDevice, User


class UserAdmin(BaseUserAdmin):
    ordering = ("id",)
    list_display = ("id", "email", "firstname", "lastname", "role", "is_active", "is_verified", "fcm_token_short", "is_staff")
    list_filter = ("role", "is_active", "is_verified", "is_staff")
    search_fields = ("email", "firstname", "lastname", "phone_number", "business_name", "fcm_token")
    filter_horizontal = ("groups", "user_permissions")
    fieldsets = (
        (None, {"fields": ("email", "password")}),
        ("Profile", {"fields": ("firstname", "lastname", "phone_number", "profile_picture",
                                "role", "referral_code", "referrer", "referral_count")}),
        ("Location", {"fields": ("country", "state", "lga", "latitude", "longitude")}),
        ("Business", {"fields": ("business_name", "business_address", "shop_size",
                                 "payment_method", "bank_name", "bank_code",
                                 "account_number", "account_name", "recipient_code")}),
        ("Push Notifications", {"fields": ("fcm_token",)}),
        ("Status", {"fields": ("is_active", "is_verified", "email_verified_at", "last_login")}),
        ("Permissions", {"fields": ("is_staff", "is_superuser", "groups", "user_permissions")}),
    )
    add_fieldsets = (
        (None, {"classes": ("wide",),
                "fields": ("email", "firstname", "lastname", "role", "password1", "password2")}),
    )

    def fcm_token_short(self, obj):
        if obj.fcm_token:
            return obj.fcm_token[:25] + "…"
        return "—"
    fcm_token_short.short_description = "FCM Device"


# Replace the simple User registration from accounts/admin.py with our richer one.
admin.site.unregister(User)
admin.site.register(User, UserAdmin)

@admin.register(FCMDevice)
class FCMDeviceAdmin(admin.ModelAdmin):
    list_display = ("id", "email", "firstname", "lastname", "role", "fcm_token", "is_active", "updated_at")
    list_filter = ("role", "is_active")
    search_fields = ("email", "firstname", "lastname", "fcm_token")
    readonly_fields = ("email", "firstname", "lastname", "role", "fcm_token", "is_active",
                       "created_at", "updated_at")
    fields = ("email", "firstname", "lastname", "role", "is_active", "fcm_token", "updated_at")
    ordering = ("-updated_at",)

    def get_queryset(self, request):
        return super().get_queryset(request).exclude(fcm_token__isnull=True).exclude(fcm_token="")


admin.site.site_header = "Jara market Admin"
admin.site.site_title = "Jara market Admin"
admin.site.index_title = "Administration"
