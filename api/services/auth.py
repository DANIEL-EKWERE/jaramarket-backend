"""Authentication and user registration services."""
import random
import string
from datetime import timedelta

from django.utils import timezone

from apps.accounts.models import Roles, User, UserOtp
from apps.finance.models import Wallet
from ._base import OTP_TTL_MINUTES, issue_tokens


class UserRegistrationService:
    def register(self, data):
        email = data["email"].lower()
        if User.objects.filter(email=email).exists():
            raise ValueError("An account with this email already exists.")
        referrer = None
        if data.get("referral_code"):
            referrer = User.objects.filter(referral_code=data["referral_code"]).first()
        user = User(
            firstname=data["firstname"], lastname=data["lastname"],
            email=email, phone_number=data.get("phone_number"),
            role=data.get("role", Roles.CUSTOMER),
            country_id=data.get("country_id"),
            referral_code=self._generate_referral_code(), referrer=referrer,
            is_active=False)
        user.set_password(data["password"])
        user.save()
        if referrer:
            referrer.referral_count = (referrer.referral_count or 0) + 1
            referrer.save(update_fields=["referral_count"])
        Wallet.objects.get_or_create(user=user, defaults={"balance": 0})
        self.send_otp(user.email, user=user)
        from api.notifications import welcome_notification
        welcome_notification(user)
        return user

    def _generate_referral_code(self):
        while True:
            code = "".join(random.choices(string.ascii_uppercase + string.digits, k=8))
            if not User.objects.filter(referral_code=code).exists():
                return code

    def send_otp(self, email, user=None):
        otp = f"{random.randint(0, 9999):04d}"
        UserOtp.objects.create(email=email, otp=otp)
        from api.notifications import otp_notification
        otp_notification(email, otp)
        # Vendors also get OTP via SMS
        if user is None:
            user = User.objects.filter(email=email).first()
        if user and user.role == Roles.VENDOR and user.phone_number:
            from .sms import Termii
            Termii().send(user.phone_number, f"Your Jaramarket OTP is {otp}. It expires in 15 minutes.")
        return otp

    def validate_otp(self, email, otp):
        cutoff = timezone.now() - timedelta(minutes=OTP_TTL_MINUTES)
        rec = (UserOtp.objects.filter(email=email, otp=otp, created_at__gte=cutoff)
               .order_by("-created_at").first())
        if not rec:
            raise ValueError("Invalid or expired OTP")
        user = User.objects.filter(email=email).first()
        if not user:
            raise ValueError("User not found")
        rec.delete()
        return user

    def validate_email(self, user):
        user.email_verified_at = timezone.now()
        user.is_active = True
        user.save(update_fields=["email_verified_at", "is_active"])
        return user

    def reset_password(self, email, otp, new_password):
        user = self.validate_otp(email, otp)
        user.set_password(new_password)
        user.save(update_fields=["password"])
        return user

    def update_profile(self, user, data):
        editable = ["firstname", "lastname", "phone_number", "profile_picture",
                    "country_id", "state_id", "lga_id", "business_name",
                    "business_address", "shop_size", "payment_method", "latitude",
                    "longitude", "bank_name", "bank_code", "account_number", "account_name"]
        for f in editable:
            if f in data and data[f] is not None:
                setattr(user, f, data[f])
        user.save()
        return user


class LoginService:
    def login_user(self, email, password):
        user = User.objects.filter(email=email.lower()).first()
        if not user or not user.check_password(password):
            raise ValueError("Invalid credentials")
        if not user.is_active:
            raise ValueError("Account is not active. Please verify your email.")
        user.last_login = timezone.now()
        user.save(update_fields=["last_login"])
        return {"user": user, **issue_tokens(user)}
