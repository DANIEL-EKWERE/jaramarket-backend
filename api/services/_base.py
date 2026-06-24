"""Shared constants and helpers used across service modules."""
from decimal import Decimal

from django.conf import settings as _settings
from rest_framework_simplejwt.tokens import RefreshToken

from apps.support.models import Setting
from apps.finance.models import Wallet

USER_TYPE = "App\\Models\\User"
ORDER_TYPE = "App\\Models\\Order"
OTP_TTL_MINUTES = 15


def issue_tokens(user):
    refresh = RefreshToken.for_user(user)
    return {"access_token": str(refresh.access_token),
            "refresh_token": str(refresh), "token_type": "Bearer"}


def _setting(key, default=None):
    row = Setting.objects.filter(key=key).first()
    return row.value if row else default


def _d(value):
    return Decimal(str(value or 0))
