from django.conf import settings
from django.contrib.auth.hashers import check_password, make_password
from django.db import transaction
from django.utils import timezone
from rest_framework.decorators import api_view, permission_classes
from rest_framework.permissions import AllowAny

from api.utils import error, success
from api.services import LoginService, UserRegistrationService
from api.services._base import USER_TYPE
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
def check_otp(request):
    """Non-consuming OTP check -- for the forgot-password flow's 'enter OTP'
    step, so the user gets immediate feedback on a wrong/expired code
    without burning the OTP before the actual reset-password step (which
    validates and consumes it for real, exactly once)."""
    ser = OtpSerializer(data=request.data)
    ser.is_valid(raise_exception=True)
    try:
        _reg.validate_otp(ser.validated_data["email"], ser.validated_data["otp"], consume=False)
    except ValueError as e:
        return error(str(e), status=400)
    return success("OTP is valid")


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


# Orders a vendor/rider is still working on -- they need the customer's
# name, phone and delivery address until it is delivered or cancelled.
OPEN_ORDER_STATUSES = ("pending", "processing", "in_transit")

# A vendor's item with money or work outstanding: "delivered" items are only
# paid out once QA completes the order.
VENDOR_OPEN_ITEM_STATUSES = ("processing", "delivered")

# A transfer in any other state can still fail, which refunds the wallet.
FINISHED_TRANSFER_STATUSES = ("success", "failed", "reversed")


def _vendor_deletion_blocker(user):
    """Why this vendor can't be deleted yet, or None. Earnings are never
    forfeited: everything owed to them has to be paid out first."""
    from apps.finance.models import Transfer, Wallet
    from apps.orders.models import OrderItem

    if OrderItem.objects.filter(vendor=user, status__in=VENDOR_OPEN_ITEM_STATUSES).exists():
        return ("You have orders in progress or awaiting payout. You can delete "
                "your account once they have been completed.")
    if Wallet.objects.filter(user=user, balance__gt=0).exists():
        return "Please withdraw your wallet balance before deleting your account."
    if Transfer.objects.filter(owner_type=USER_TYPE, owner_id=user.id).exclude(
            status__in=FINISHED_TRANSFER_STATUSES).exists():
        return ("You have a withdrawal still processing. You can delete your "
                "account once it has finished.")
    return None


@api_view(["POST", "DELETE"])
def delete_account(request):
    """Permanently delete the caller's customer or vendor account (App Store 5.1.1(v)).

    Personal data is erased: profile, credentials, PIN, device token, bank
    details, addresses, cart, favorites and support tickets. Orders, payments
    and wallet/transaction rows stay, attached to the scrubbed user, because
    vendors and finance still have to account for them. The row can never be
    signed into again, and the email is freed so the person can sign up afresh.
    """
    from apps.finance.models import BankAccount
    from apps.orders.models import MarketOfferResponse
    from apps.support.models import HelpTicket, Support
    from apps.vendors.models import Vendor

    user = request.user
    if not (user.is_customer() or user.is_vendor()):
        return error("Staff accounts can't be deleted from the app.", status=403)
    if user.is_vendor():
        blocker = _vendor_deletion_blocker(user)
        if blocker:
            return error(blocker, status=422)
    if user.orders.filter(status__in=OPEN_ORDER_STATUSES).exists():
        return error("You have an order in progress. You can delete your account "
                     "once it has been delivered or cancelled.", status=422)

    with transaction.atomic():
        # Addresses on past orders can't be removed (the order points at
        # them), so those are blanked; unused ones are deleted outright.
        user.addresses.filter(order__isnull=True).delete()
        user.addresses.update(contact_address=None, phone_number=None,
                              latitude=None, longitude=None, is_default=False)
        user.carts.all().delete()
        user.favorites.all().delete()
        Support.objects.filter(user=user).delete()
        HelpTicket.objects.filter(user=user).delete()

        if user.is_vendor():
            user.categories.clear()
            Vendor.objects.filter(user=user).update(is_active=False, market=None)
            BankAccount.objects.filter(owner_type=USER_TYPE, owner_id=user.id).delete()
            # A live offer no longer waits on them; if nobody else is left it
            # moves on when escalate_stale_offers next sweeps it.
            MarketOfferResponse.objects.filter(vendor=user, decision="pending").update(
                decision="declined", decided_at=timezone.now())

        user.firstname = "Deleted"
        user.lastname = "User"
        user.email = f"deleted-{user.id}-{int(timezone.now().timestamp())}@deleted.invalid"
        for field in ("phone_number", "profile_picture", "referral_code", "pin",
                      "fcm_token", "business_name", "business_address", "shop_size",
                      "payment_method", "bank_name",
                      "bank_code", "recipient_code", "account_number", "account_name",
                      "latitude", "longitude"):
            setattr(user, field, None)
        user.set_unusable_password()
        # is_active=False also makes simplejwt reject any token already issued.
        user.is_active = False
        user.deleted_at = timezone.now()
        user.save()

    return success("Your account has been deleted.")


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


# Tolerance for server/provider clock drift when validating social ID tokens.
# Without it a server a few seconds slow rejects every login outright.
SOCIAL_CLOCK_SKEW_SECONDS = 60

APPLE_KEYS_URL = "https://appleid.apple.com/auth/keys"
INVALID_SIGNUP_ROLE_MESSAGE = "You can only sign up as a customer or a vendor."
_apple_jwk_client = None


def _get_apple_jwk_client():
    """Apple's signing keys, fetched once and cached.

    A fresh client per request re-downloaded the key set on every single
    login (~2s each) and turned any blip in outbound network access into a
    failed sign-in. Apple rotates these keys rarely, so cache them.
    """
    global _apple_jwk_client
    if _apple_jwk_client is None:
        from jwt import PyJWKClient
        _apple_jwk_client = PyJWKClient(
            APPLE_KEYS_URL, cache_keys=True, lifespan=3600, timeout=10)
    return _apple_jwk_client


def _social_signin_response(email, firstname, lastname, role, picture, provider):
    """Shared upsert + token issue for social providers (Google/Apple):
    the provider has already verified the email, so the account is created
    pre-verified, and an existing account with that email just signs in."""
    from apps.finance.models import Wallet

    user, created = User.objects.get_or_create(
        email=email,
        defaults={
            "firstname": firstname or "",
            "lastname": lastname or "",
            "role": role,
            "is_active": True,
            "is_verified": True,
            "email_verified_at": timezone.now(),
            "profile_picture": picture or "",
        },
    )

    blocker = user.sign_in_blocker()
    if blocker:
        return error(blocker, status=403)
    if not user.is_active:
        # A signup that never finished OTP; the provider has now verified the
        # email, so complete it rather than leaving it looking unverified
        # (which would let a later suspension be mistaken for one).
        user.is_active = True
        user.email_verified_at = timezone.now()
        user.save(update_fields=["is_active", "email_verified_at"])

    if created:
        Wallet.objects.get_or_create(user=user, defaults={"balance": 0})

    from api.services._base import issue_tokens
    tokens = issue_tokens(user)
    payload = auth_user_payload(user, token=tokens["access_token"], refresh=tokens["refresh_token"])
    payload["is_new_user"] = created
    return success(f"{provider} sign-in successful", payload, status=201 if created else 200)


@api_view(["POST"])
@permission_classes([AllowAny])
def google_signin(request):
    """Verify a Google ID token and sign in or create the user (upsert)."""
    id_token = request.data.get("id_token")
    role = request.data.get("role", Roles.CUSTOMER)
    if role not in Roles.SELF_SIGNUP_ROLES:
        return error(INVALID_SIGNUP_ROLE_MESSAGE, status=422)
    if not id_token:
        return error("id_token is required", status=422)

    try:
        from google.oauth2 import id_token as google_id_token
        from google.auth.transport import requests as google_requests

        client_ids = [c.strip() for c in settings.GOOGLE_CLIENT_IDS.split(",") if c.strip()]
        if not client_ids:
            return error("Google sign-in is not configured on this server.", status=503)
        idinfo = None
        last_exc = None
        for client_id in client_ids:
            try:
                idinfo = google_id_token.verify_oauth2_token(
                    id_token, google_requests.Request(), client_id,
                    # google-auth allows zero clock skew by default, so a
                    # server running even a few seconds behind rejects every
                    # token with "Token used too early". Shared hosting
                    # drifts, so allow a small window.
                    clock_skew_in_seconds=SOCIAL_CLOCK_SKEW_SECONDS,
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

    return _social_signin_response(
        email, idinfo.get("given_name", ""), idinfo.get("family_name", ""),
        role, idinfo.get("picture", ""), "Google")


@api_view(["POST"])
@permission_classes([AllowAny])
def apple_signin(request):
    """Verify an Apple identity token and sign in or create the user (upsert).

    Apple only includes the user's name in the FIRST authorization on the
    device, and never inside the identity token itself -- so the apps pass
    firstname/lastname alongside the token and they're used only when the
    account is being created.
    """
    identity_token = request.data.get("identity_token") or request.data.get("id_token")
    role = request.data.get("role", Roles.CUSTOMER)
    if role not in Roles.SELF_SIGNUP_ROLES:
        return error(INVALID_SIGNUP_ROLE_MESSAGE, status=422)
    if not identity_token:
        return error("identity_token is required", status=422)

    client_ids = [c.strip() for c in settings.APPLE_CLIENT_IDS.split(",") if c.strip()]
    if not client_ids:
        return error("Apple sign-in is not configured on this server.", status=503)

    import jwt as pyjwt

    # Fetching Apple's keys is a separate failure mode from a bad token --
    # reporting them the same way makes a network problem look like a
    # rejected login, so keep them apart.
    try:
        signing_key = _get_apple_jwk_client().get_signing_key_from_jwt(identity_token)
    except pyjwt.exceptions.InvalidTokenError as exc:
        # Unparseable token -- that's the caller's problem, not Apple's.
        return error(f"Invalid Apple token: {exc}", status=401)
    except Exception as exc:
        return error(f"Could not verify with Apple right now: {exc}", status=503)

    try:
        idinfo = None
        last_exc = None
        for client_id in client_ids:
            try:
                idinfo = pyjwt.decode(
                    identity_token, signing_key.key, algorithms=["RS256"],
                    audience=client_id, issuer="https://appleid.apple.com",
                    # Same clock-drift tolerance as Google (see above).
                    leeway=SOCIAL_CLOCK_SKEW_SECONDS,
                )
                break
            except Exception as exc:
                last_exc = exc
        if idinfo is None:
            raise last_exc
    except Exception as exc:
        return error(f"Invalid Apple token: {exc}", status=401)

    email = idinfo.get("email")
    if not email:
        return error("Apple token missing email", status=400)

    return _social_signin_response(
        email, request.data.get("firstname", ""), request.data.get("lastname", ""),
        role, "", "Apple")
