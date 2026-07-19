from django.contrib.auth.models import AbstractBaseUser, BaseUserManager, PermissionsMixin
from django.db import models

from api.base import AllObjectsManager, TimestampedModel


class Roles:
    ADMIN = "admin"; SUPER_ADMIN = "super_admin"; STATE_ADMIN = "state_admin"
    VENDOR_MANAGER = "vendor_manager"; ACCOUNTS = "accounts"; AUDIT = "audit"
    LOGISTICS = "logistics"; VENDOR = "vendor"; CUSTOMER = "customer"
    QA = "qa"; ACCOUNT = "account"

    CHOICES = [
        (ADMIN, "Super Admin"), (SUPER_ADMIN, "Super Admin"), (STATE_ADMIN, "State Admin"),
        (VENDOR_MANAGER, "Vendor Manager"), (ACCOUNTS, "Accounts"), (AUDIT, "Audit"),
        (LOGISTICS, "Logistics"), (VENDOR, "Vendor"), (CUSTOMER, "Customer"),
        (QA, "Quality Assurance"), (ACCOUNT, "Account"),
    ]
    ADMIN_ROLES = [SUPER_ADMIN, ADMIN, STATE_ADMIN, VENDOR_MANAGER, ACCOUNTS, AUDIT, LOGISTICS]

    ALL_PERMISSION_SLUGS = [
        "view_dashboard", "view_orders", "manage_orders", "view_users", "manage_users",
        "view_vendors", "manage_vendors", "view_transactions", "manage_transactions",
        "view_wallets", "manage_withdrawals", "view_reports", "view_logistics",
        "manage_admins", "manage_roles", "manage_settings", "view_categories", "manage_categories",
        "view_products", "manage_products", "view_ingredients", "manage_ingredients",
        "view_commissions", "manage_commissions",
        "view_service_fees", "manage_service_fees",
    ]
    DEFAULT_PERMISSIONS = {
        STATE_ADMIN: ["view_dashboard", "view_orders", "manage_orders", "view_users", "manage_users",
                      "view_vendors", "manage_vendors", "view_transactions", "view_reports",
                      "view_logistics", "view_categories", "view_products", "view_ingredients"],
        VENDOR_MANAGER: ["view_dashboard", "view_vendors", "manage_vendors", "view_orders", "manage_orders",
                         "view_reports", "view_logistics", "view_categories", "view_products", "view_ingredients"],
        ACCOUNTS: ["view_dashboard", "view_transactions", "view_wallets", "manage_withdrawals", "view_reports"],
        AUDIT: ["view_transactions", "view_wallets", "view_reports", "view_orders"],
        LOGISTICS: ["view_orders", "manage_orders", "view_logistics"],
    }

    @classmethod
    def default_permissions(cls, role):
        if role in (cls.ADMIN, cls.SUPER_ADMIN):
            return cls.ALL_PERMISSION_SLUGS
        return cls.DEFAULT_PERMISSIONS.get(role, [])


class Permission(TimestampedModel):
    name = models.CharField(max_length=255, unique=True)
    slug = models.CharField(max_length=255, unique=True)
    group = models.CharField(max_length=255, default="general")
    description = models.CharField(max_length=255, null=True, blank=True)

    class Meta:
        db_table = "permissions"

    def __str__(self):
        return self.slug


class UserManager(BaseUserManager):
    def create_user(self, email, password=None, **extra):
        if not email:
            raise ValueError("Users must have an email address")
        user = self.model(email=self.normalize_email(email), **extra)
        user.set_password(password)
        user.save(using=self._db)
        return user

    def create_superuser(self, email, password=None, **extra):
        extra.setdefault("role", Roles.SUPER_ADMIN)
        extra.setdefault("is_staff", True)
        extra.setdefault("is_superuser", True)
        extra.setdefault("is_active", True)
        extra.setdefault("firstname", "Super")
        extra.setdefault("lastname", "Admin")
        return self.create_user(email, password, **extra)

    def admins(self):    return self.filter(role__in=Roles.ADMIN_ROLES)
    def vendors(self):   return self.filter(role=Roles.VENDOR)
    def customers(self): return self.filter(role=Roles.CUSTOMER)


class User(AbstractBaseUser, PermissionsMixin):
    firstname = models.CharField(max_length=255)
    lastname = models.CharField(max_length=255)
    email = models.EmailField(max_length=255, unique=True)
    phone_number = models.CharField(max_length=255, null=True, blank=True)
    role = models.CharField(max_length=255, default=Roles.CUSTOMER, choices=Roles.CHOICES)
    profile_picture = models.CharField(max_length=255, null=True, blank=True)
    email_verified_at = models.DateTimeField(null=True, blank=True)

    referral_code = models.CharField(max_length=255, null=True, blank=True)
    referrer = models.ForeignKey("self", on_delete=models.CASCADE, null=True, blank=True,
                                 db_column="referrer_id", related_name="referrals")
    referral_count = models.IntegerField(default=0)

    is_active = models.BooleanField(default=False)
    is_verified = models.BooleanField(default=False)
    pin = models.CharField(max_length=191, null=True, blank=True)
    last_login = models.DateTimeField(null=True, blank=True)

    country = models.ForeignKey("geo.Country", on_delete=models.SET_NULL, null=True, blank=True,
                                db_column="country_id", db_constraint=False, related_name="users")
    state = models.ForeignKey("geo.State", on_delete=models.SET_NULL, null=True, blank=True,
                              db_column="state_id", related_name="users")
    lga = models.ForeignKey("geo.Lga", on_delete=models.SET_NULL, null=True, blank=True,
                            db_column="lga_id", related_name="users")
    business_name = models.CharField(max_length=255, null=True, blank=True)
    business_address = models.TextField(null=True, blank=True)
    shop_size = models.CharField(max_length=255, null=True, blank=True)
    payment_method = models.CharField(max_length=255, null=True, blank=True)
    latitude = models.DecimalField(max_digits=10, decimal_places=7, null=True, blank=True)
    longitude = models.DecimalField(max_digits=10, decimal_places=7, null=True, blank=True)

    bank_name = models.CharField(max_length=255, null=True, blank=True)
    bank_code = models.CharField(max_length=255, null=True, blank=True)
    recipient_code = models.CharField(max_length=255, null=True, blank=True)
    account_number = models.CharField(max_length=255, null=True, blank=True)
    account_name = models.CharField(max_length=255, null=True, blank=True)

    fcm_token = models.CharField(max_length=255, null=True, blank=True)

    is_staff = models.BooleanField(default=False)
    created_at = models.DateTimeField(auto_now_add=True, null=True)
    updated_at = models.DateTimeField(auto_now=True, null=True)
    deleted_at = models.DateTimeField(null=True, blank=True)

    permissions_m2m = models.ManyToManyField(Permission, through="UserPermission",
                                             related_name="users", blank=True)

    objects = UserManager()
    all_objects = AllObjectsManager()

    USERNAME_FIELD = "email"
    REQUIRED_FIELDS = ["firstname", "lastname"]

    class Meta:
        db_table = "users"

    def __str__(self):
        return self.name or self.email

    @property
    def name(self):
        return f"{self.firstname} {self.lastname}".strip()

    def is_admin(self):          return self.role in Roles.ADMIN_ROLES
    def is_super_admin(self):    return self.role in (Roles.SUPER_ADMIN, Roles.ADMIN)
    def is_state_admin(self):    return self.role == Roles.STATE_ADMIN
    def is_vendor_manager(self): return self.role == Roles.VENDOR_MANAGER
    def is_accounts(self):       return self.role == Roles.ACCOUNTS
    def is_audit(self):          return self.role == Roles.AUDIT
    def is_logistics(self):      return self.role == Roles.LOGISTICS
    def is_vendor(self):         return self.role == Roles.VENDOR
    def is_customer(self):       return self.role == Roles.CUSTOMER

    def has_perm_slug(self, slug):
        if self.is_super_admin():
            return True
        return self.permissions_m2m.filter(slug=slug).exists()

    def has_any_permission(self, slugs):
        return any(self.has_perm_slug(s) for s in slugs)

    def has_all_permissions(self, slugs):
        return all(self.has_perm_slug(s) for s in slugs)

    def sync_default_permissions(self):
        slugs = Roles.default_permissions(self.role)
        self.permissions_m2m.set(Permission.objects.filter(slug__in=slugs))


class UserPermission(TimestampedModel):
    user = models.ForeignKey(User, on_delete=models.CASCADE, db_column="user_id")
    permission = models.ForeignKey(Permission, on_delete=models.CASCADE, db_column="permission_id")

    class Meta:
        db_table = "user_permissions"
        unique_together = (("user", "permission"),)


class Admin(TimestampedModel):
    name = models.CharField(max_length=255)
    email = models.EmailField(max_length=255, unique=True)
    password = models.CharField(max_length=255)
    phone = models.CharField(max_length=255)
    is_active = models.BooleanField(default=True)
    email_verified_at = models.DateTimeField(null=True, blank=True)
    last_login_at = models.DateTimeField(null=True, blank=True)
    remember_token = models.CharField(max_length=100, null=True, blank=True)
    permissions = models.ManyToManyField(Permission, through="AdminPermission", related_name="admins")

    class Meta:
        db_table = "admins"


class AdminPermission(TimestampedModel):
    admin = models.ForeignKey(Admin, on_delete=models.CASCADE, db_column="admin_id")
    permission = models.ForeignKey(Permission, on_delete=models.CASCADE, db_column="permission_id")

    class Meta:
        db_table = "admin_permissions"


class UserOtp(TimestampedModel):
    otp = models.CharField(max_length=255)
    email = models.CharField(max_length=255)

    class Meta:
        db_table = "user_otps"


class FCMDevice(User):
    """Proxy model for a dedicated FCM Devices admin section."""
    class Meta:
        proxy = True
        verbose_name = "FCM Device"
        verbose_name_plural = "FCM Devices"
