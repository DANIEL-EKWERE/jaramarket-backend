from django.conf import settings
from django.contrib.auth.hashers import check_password, make_password
from django.utils import timezone
from rest_framework.decorators import api_view, permission_classes
from rest_framework.permissions import AllowAny

from api.utils import error, success
from api.services import LoginService, UserRegistrationService
from .models import Roles, User
from .serializers import (
    ChangePasswordSerializer, LoginSerializer, OtpSerializer,
    PinSerializer, RegisterSerializer, ReferralSerializer,
    UserSerializer, auth_user_payload,
)

_reg = UserRegistrationService()
_login = LoginService()


@api_view(["POST"])
@permission_classes([AllowAny])
def register(request):
    ser = RegisterSerializer(data=request.data)
    ser.is_valid(raise_exception=True)
    try:
        user = _reg.register(ser.validated_data)
    except ValueError as e:
        return error(str(e), status=422)
    except Exception as e:
        if "UNIQUE constraint" in str(e) or "unique constraint" in str(e).lower() or "Duplicate entry" in str(e):
            return error("An account with this email already exists.", status=422)
        raise
    return success("An OTP has been sent to your email address. It expires after 15 minutes.",
                   UserSerializer(user).data, status=201)


@api_view(["POST"])
@permission_classes([AllowAny])
def resend_otp(request):
    email = request.data.get("email")
    if not email:
        return error("Email is required", status=422)
    _reg.send_otp(email)
    return success("An OTP has been sent to your email address. It expires after 15 minutes.", None, status=201)


@api_view(["POST"])
@permission_classes([AllowAny])
def validate_otp(request):
    ser = OtpSerializer(data=request.data)
    ser.is_valid(raise_exception=True)
    try:
        user = _reg.validate_otp(ser.validated_data["email"], ser.validated_data["otp"])
        _reg.validate_email(user)
    except ValueError as e:
        return error(str(e), status=400)
    return success("OTP validated successfully", auth_user_payload(user), status=201)


@api_view(["POST"])
@permission_classes([AllowAny])
def validate_email(request):
    ser = OtpSerializer(data=request.data)
    ser.is_valid(raise_exception=True)
    try:
        user = _reg.validate_otp(ser.validated_data["email"], ser.validated_data["otp"])
        _reg.validate_email(user)
    except ValueError as e:
        return error(str(e), status=400)
    return success("Email verified successfully and registration complete",
                   auth_user_payload(user), status=201)


@api_view(["POST"])
@permission_classes([AllowAny])
def login(request):
    ser = LoginSerializer(data=request.data)
    ser.is_valid(raise_exception=True)
    try:
        result = _login.login_user(ser.validated_data["email"], ser.validated_data["password"])
    except ValueError as e:
        return error("User Authenticated failed", data=str(e), status=500)
    data = auth_user_payload(result["user"], token=result["access_token"],
                             refresh=result["refresh_token"])
    return success("User Authenticated successfully", data, status=200)


@api_view(["POST"])
def logout(request):
    return success("Session ended! Logout was successful.")


@api_view(["POST"])
@permission_classes([AllowAny])
def profile_update_by_email(request, email):
    user = User.objects.filter(email=email).first()
    if not user:
        return error("User not found", status=404)
    user = _reg.update_profile(user, request.data)
    return success("Profile Updated Successfully", auth_user_payload(user), status=201)


@api_view(["POST"])
@permission_classes([AllowAny])
def forgot_password(request):
    email = request.data.get("email")
    user = User.objects.filter(email=email).first() if email else None
    if not user or not user.is_active:
        return error("User account not found or inactive", status=500)
    _reg.send_otp(user.email)
    return success("An OTP has been sent to your email address. It expires after 15 minutes.", [], status=201)


@api_view(["POST"])
@permission_classes([AllowAny])
def reset_password(request):
    email = request.data.get("email")
    otp = request.data.get("otp")
    password = request.data.get("password")
    if not all([email, otp, password]):
        return error("email, otp and password are required", status=422)
    try:
        user = _reg.reset_password(email, otp, password)
    except ValueError as e:
        return error(str(e), status=400)
    return success("Password reset successful", UserSerializer(user).data)


@api_view(["GET"])
def fetch_user(request):
    return success("User Profile retrieved successfully", auth_user_payload(request.user), status=200)


@api_view(["POST"])
def edit_profile(request):
    import os
    from django.conf import settings as _s

    data = request.data.dict() if hasattr(request.data, "dict") else dict(request.data)
    uploaded_file = request.FILES.get("profile_picture")
    if uploaded_file:
        upload_dir = os.path.join(_s.MEDIA_ROOT, "profiles")
        os.makedirs(upload_dir, exist_ok=True)
        ext = os.path.splitext(uploaded_file.name)[1]
        filename = f"profile_{request.user.id}{ext}"
        filepath = os.path.join(upload_dir, filename)
        with open(filepath, "wb") as f:
            for chunk in uploaded_file.chunks():
                f.write(chunk)
        data["profile_picture"] = filename

    user = _reg.update_profile(request.user, data)
    return success("Profile Updated Successfully", auth_user_payload(user))


@api_view(["PATCH"])
def change_password(request):
    ser = ChangePasswordSerializer(data=request.data)
    ser.is_valid(raise_exception=True)
    user = request.user
    if not user.check_password(ser.validated_data["old_password"]):
        return error("Old password is incorrect", status=422)
    user.set_password(ser.validated_data["password"])
    user.save()
    return success("Password changed successfully", UserSerializer(user).data, status=201)


@api_view(["GET"])
def my_referrals(request):
    qs = request.user.referrals.order_by("-created_at")
    return success("Refferals retrieved successfully", ReferralSerializer(qs, many=True).data, status=201)


@api_view(["POST"])
def fcm_token(request):
    token = request.data.get("token") or request.data.get("fcm_token")
    if not token:
        return error("token is required", status=422)
    request.user.fcm_token = token
    request.user.save(update_fields=["fcm_token"])
    return success("FCM token saved")


@api_view(["POST"])
@permission_classes([AllowAny])
def update_vendor_categories(request, email):
    from apps.catalogue.models import Category
    vendor = User.objects.filter(email=email).first()
    if not vendor:
        return error("Vendor not found", status=404)
    category_ids = request.data.get("category_ids", [])
    vendor.categories.set(Category.objects.filter(id__in=category_ids))
    from apps.catalogue.serializers import CategorySerializer
    return success("Category added successfully",
                   CategorySerializer(vendor.categories.all(), many=True).data, status=201)


# ── PIN ──
@api_view(["POST"])
def pin_set(request):
    ser = PinSerializer(data=request.data)
    ser.is_valid(raise_exception=True)
    request.user.pin = make_password(ser.validated_data["pin"])
    request.user.save(update_fields=["pin"])
    return success("Transaction PIN set successfully.")


@api_view(["POST"])
def pin_verify(request):
    pin = request.data.get("pin")
    if not request.user.pin or not check_password(pin, request.user.pin):
        return error("Invalid PIN.", status=422)
    return success("PIN verified successfully.")


@api_view(["GET"])
def pin_validate(request):
    return success("PIN status", {"has_pin": bool(request.user.pin)})


@api_view(["POST"])
def pin_clear(request):
    request.user.pin = None
    request.user.save(update_fields=["pin"])
    return success("PIN cleared")


@api_view(["POST"])
def pin_request_reset(request):
    _reg.send_otp(request.user.email)
    return success("An OTP has been sent to your email to reset your PIN.")


@api_view(["POST"])
def pin_reset(request):
    otp = request.data.get("otp")
    new_pin = request.data.get("pin")
    if not all([otp, new_pin]):
        return error("otp and pin are required", status=422)
    try:
        _reg.validate_otp(request.user.email, otp)
    except ValueError as e:
        return error(str(e), status=400)
    request.user.pin = make_password(new_pin)
    request.user.save(update_fields=["pin"])
    return success("PIN reset successfully")


@api_view(["POST"])
@permission_classes([AllowAny])
def google_signin(request):
    """Verify a Google ID token and sign in or create the user (upsert)."""
    id_token = request.data.get("id_token")
    role = request.data.get("role", Roles.CUSTOMER)
    if not id_token:
        return error("id_token is required", status=422)

    try:
        from google.oauth2 import id_token as google_id_token
        from google.auth.transport import requests as google_requests

        client_ids = [c.strip() for c in settings.GOOGLE_CLIENT_IDS.split(",") if c.strip()]
        idinfo = None
        last_exc = None
        for client_id in client_ids:
            try:
                idinfo = google_id_token.verify_oauth2_token(
                    id_token, google_requests.Request(), client_id
                )
                break
            except Exception as exc:
                last_exc = exc

        if idinfo is None:
            raise last_exc

    except Exception as exc:
        return error(f"Invalid Google token: {exc}", status=401)

    email = idinfo.get("email")
    if not email:
        return error("Google token missing email", status=400)

    user, created = User.objects.get_or_create(
        email=email,
        defaults={
            "firstname": idinfo.get("given_name", ""),
            "lastname": idinfo.get("family_name", ""),
            "role": role,
            "is_active": True,
            "is_verified": True,
            "email_verified_at": timezone.now(),
            "profile_picture": idinfo.get("picture", ""),
        },
    )

    if not user.is_active:
        user.is_active = True
        user.save(update_fields=["is_active"])

    payload = auth_user_payload(user)
    code = 201 if created else 200
    return success("Google sign-in successful", payload, status=code)
