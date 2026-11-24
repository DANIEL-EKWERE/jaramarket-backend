"""Authentication that holds vendors to one signed-in device.

A vendor account is a shop, not a person. Two phones signed into the same
account both watch the same offer queue, so the same item gets accepted twice,
two people go shopping for it, and the order history cannot say which of them
actually has the goods. Payouts follow that history, so it has to be one
device at a time.

The rule is enforced at authentication rather than at login, because a token
already in someone's hands keeps working for its full lifetime (7 days by
default) no matter what happens at the login endpoint. Checking on every
request is the only thing that actually ends the older session.
"""
from rest_framework_simplejwt.authentication import JWTAuthentication
from rest_framework_simplejwt.exceptions import AuthenticationFailed

from api.services._base import SESSION_CLAIM


class SingleSessionJWTAuthentication(JWTAuthentication):
    """JWT auth that additionally pins vendors to their newest session."""

    def get_user(self, validated_token):
        user = super().get_user(validated_token)

        is_vendor = getattr(user, "is_vendor", None)
        if not (callable(is_vendor) and is_vendor()):
            return user

        expected = getattr(user, "active_session_id", None)
        if not expected:
            # A vendor who signed in before this field existed has nothing to
            # compare against. Letting them through means the rule starts
            # applying at their next login instead of signing out every
            # vendor the moment this deploys.
            return user

        if validated_token.get(SESSION_CLAIM) != expected:
            # `code` is what the app matches on to show "signed in elsewhere"
            # rather than a generic session-expired message -- the two call
            # for different things from the person holding the phone.
            raise AuthenticationFailed(
                "You've been signed out because this account was opened on "
                "another device. Only one phone can be signed in at a time.",
                code="session_superseded",
            )

        return user
