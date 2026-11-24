"""Shared constants and helpers used across service modules."""
import uuid
from decimal import Decimal

from django.conf import settings as _settings
from rest_framework_simplejwt.tokens import RefreshToken

from apps.support.models import Setting
from apps.finance.models import Wallet

USER_TYPE = "App\\Models\\User"
ORDER_TYPE = "App\\Models\\Order"
OTP_TTL_MINUTES = 15


# Claim carrying the device session, read back by
# apps.accounts.authentication.SingleSessionJWTAuthentication.
SESSION_CLAIM = "sid"


def issue_tokens(user):
    """Mint a token pair.

    For vendors this also rotates their session id, which is what makes a new
    login kick the previous phone off: the old tokens still verify and are
    still unexpired, but they carry a session id that no longer matches the
    one on the account, so authentication refuses them.
    """
    refresh = RefreshToken.for_user(user)

    is_vendor = getattr(user, "is_vendor", None)
    if callable(is_vendor) and is_vendor():
        session_id = uuid.uuid4().hex
        # Written with update() so this cannot clobber unsaved edits a caller
        # is holding on the same user instance mid-login.
        type(user).all_objects.filter(pk=user.pk).update(
            active_session_id=session_id)
        user.active_session_id = session_id
        # Set BEFORE reading .access_token: the access token is derived from
        # this payload, so a claim added afterwards would not reach it.
        refresh[SESSION_CLAIM] = session_id

    return {"access_token": str(refresh.access_token),
            "refresh_token": str(refresh), "token_type": "Bearer"}


def _setting(key, default=None):
    row = Setting.objects.filter(key=key).first()
    return row.value if row else default


def _d(value):
    return Decimal(str(value or 0))
