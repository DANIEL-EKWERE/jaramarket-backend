from rest_framework import serializers
from .models import User


class UserSerializer(serializers.ModelSerializer):
    name = serializers.CharField(read_only=True)

    class Meta:
        model = User
        fields = [
            "id", "firstname", "lastname", "name", "email", "phone_number", "role",
            "profile_picture", "referral_code", "referral_count", "is_active",
            "is_verified", "email_verified_at", "country_id", "state_id", "lga_id",
            "business_name", "business_address", "shop_size", "payment_method",
            "latitude", "longitude", "bank_name", "bank_code", "account_number",
            "account_name", "fcm_token", "created_at", "updated_at",
        ]


class ReferralSerializer(serializers.ModelSerializer):
    name = serializers.CharField(read_only=True)

    class Meta:
        model = User
        fields = ["id", "name", "email", "phone_number", "created_at"]


class RegisterSerializer(serializers.Serializer):
    firstname = serializers.CharField()
    lastname = serializers.CharField()
    email = serializers.EmailField()
    phone_number = serializers.CharField(required=False, allow_blank=True)
    password = serializers.CharField(write_only=True, min_length=8)
    referral_code = serializers.CharField(required=False, allow_blank=True)
    role = serializers.CharField(required=False)
    country_id = serializers.IntegerField(required=False)


class LoginSerializer(serializers.Serializer):
    email = serializers.EmailField()
    password = serializers.CharField(write_only=True)


class OtpSerializer(serializers.Serializer):
    email = serializers.EmailField()
    otp = serializers.CharField()


class ChangePasswordSerializer(serializers.Serializer):
    old_password = serializers.CharField(write_only=True)
    password = serializers.CharField(write_only=True, min_length=8)


class PinSerializer(serializers.Serializer):
    pin = serializers.CharField(min_length=4)
    remember = serializers.BooleanField(required=False, default=False)


def auth_user_payload(user, token=None, refresh=None):
    from django.conf import settings as _st
    from apps.customers.models import Address, Favorite
    from apps.finance.models import Wallet

    wallet = Wallet.objects.filter(user=user).first()

    addresses = Address.objects.filter(user=user).select_related("country", "state", "lga").order_by("-is_default", "id")
    contact_address = [
        {
            "id": addr.id,
            "country": addr.country.name if addr.country else None,
            "state": addr.state.name if addr.state else None,
            "lga": addr.lga.name if addr.lga else None,
            "contact_address": addr.contact_address,
            "phone_number": addr.phone_number,
            "is_default": addr.is_default,
            "created_at": addr.created_at.isoformat() if addr.created_at else None,
        }
        for addr in addresses
    ]

    payload = {
        "id": user.id,
        "name": user.name,
        "firstname": user.firstname,
        "lastname": user.lastname,
        "email": user.email,
        "phone_number": user.phone_number,
        "email_verified": user.email_verified_at is not None,
        "role": user.role,
        "referral_code": user.referral_code,
        "referrer_id": user.referrer_id,
        "referral_count": user.referral_count,
        "has_pin": bool(user.pin),
        "is_active": user.is_active,
        "profile_picture": user.profile_picture,
        "country_id": user.country_id,
        "state_id": user.state_id,
        "lga_id": user.lga_id,
        "business_name": user.business_name,
        "business_address": user.business_address,
        "created_at": user.created_at.isoformat() if user.created_at else None,
        "last_login": user.last_login.isoformat() if user.last_login else None,
        "wallet": {"id": wallet.id, "balance": f"{wallet.balance:.2f}"} if wallet else None,
        "favorites": Favorite.objects.filter(user=user).count(),
        "contact_address": contact_address,
    }
    if token:
        lifetime = _st.SIMPLE_JWT.get("ACCESS_TOKEN_LIFETIME")
        payload["token"] = token
        payload["token_type"] = "Bearer"
        payload["expires_in"] = int(lifetime.total_seconds()) if lifetime else None
        if refresh:
            payload["refresh_token"] = refresh
    return payload
