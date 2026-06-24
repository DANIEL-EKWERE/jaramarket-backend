from django.urls import path
from . import views as v

urlpatterns = [
    # Guest auth
    path("register", v.register),
    path("google-signin", v.google_signin),
    path("validate-otp", v.validate_otp),
    path("validate-email", v.validate_email),
    path("resend-otp", v.resend_otp),
    path("login", v.login),
    path("logout", v.logout),
    path("profile-update/<str:email>", v.profile_update_by_email),
    path("edit-user-profile/<str:email>", v.profile_update_by_email),
    path("forgot-password", v.forgot_password),
    path("reset-password", v.reset_password),
    path("update-vendor-categories/<str:email>", v.update_vendor_categories),
    # Authenticated: profile
    path("fetch-user", v.fetch_user),
    path("update-profile", v.edit_profile),
    path("user/change-password", v.change_password),
    path("my-referrals", v.my_referrals),
    path("fcm-token", v.fcm_token),
    path("api/notifications/token", v.fcm_token),  # vendor app alias
    # PIN
    path("pin/set", v.pin_set),
    path("pin/verify", v.pin_verify),
    path("pin/validate", v.pin_validate),
    path("pin/clear", v.pin_clear),
    path("pin/request-reset", v.pin_request_reset),
    path("pin/reset", v.pin_reset),
]
