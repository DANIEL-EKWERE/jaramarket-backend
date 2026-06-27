"""Django settings for the Jaraman port (Laravel -> Django/DRF)."""
from datetime import timedelta
from pathlib import Path
from decouple import config, Csv

BASE_DIR = Path(__file__).resolve().parent.parent

import dj_database_url

SECRET_KEY = config("SECRET_KEY", default="change-me-in-production")
DEBUG = config("DEBUG", default=True, cast=bool)
ALLOWED_HOSTS = config("ALLOWED_HOSTS", default="*", cast=Csv())


INSTALLED_APPS = [
    "daphne",
    "django.contrib.admin",
    "django.contrib.auth",
    "django.contrib.contenttypes",
    "django.contrib.sessions",
    "django.contrib.messages",
    "django.contrib.staticfiles",
    # Third-party
    "anymail",
    "rest_framework",
    "rest_framework_simplejwt",
    "corsheaders",
    "django_filters",
    "channels",
    # Domain apps
    "apps.geo",
    "apps.accounts",
    "apps.catalogue",
    "apps.customers",
    "apps.orders",
    "apps.vendors",
    "apps.finance",
    "apps.support",
    # Routing / utility layer (tasks, consumers, admin views)
    "api",
]

MIDDLEWARE = [
    "corsheaders.middleware.CorsMiddleware",
    "django.middleware.security.SecurityMiddleware",
    "whitenoise.middleware.WhiteNoiseMiddleware",
    "django.contrib.sessions.middleware.SessionMiddleware",
    "django.middleware.common.CommonMiddleware",
    "django.middleware.csrf.CsrfViewMiddleware",
    "django.contrib.auth.middleware.AuthenticationMiddleware",
    "django.contrib.messages.middleware.MessageMiddleware",
    "django.middleware.clickjacking.XFrameOptionsMiddleware",
]

ROOT_URLCONF = "jaraman.urls"

TEMPLATES = [{
    "BACKEND": "django.template.backends.django.DjangoTemplates",
    "DIRS": [BASE_DIR / "templates"],
    "APP_DIRS": True,
    "OPTIONS": {"context_processors": [
        "django.template.context_processors.debug",
        "django.template.context_processors.request",
        "django.contrib.auth.context_processors.auth",
        "django.contrib.messages.context_processors.messages",
    ]},
}]

WSGI_APPLICATION = "jaraman.wsgi.application"
ASGI_APPLICATION = "jaraman.asgi.application"

# Database — defaults to MySQL (matching Laravel). Override via .env.
DB_CONNECTION = config("DB_CONNECTION", default="sqlite")
if DB_CONNECTION == "mysql":
    DATABASES = {"default": {
        "ENGINE": "django.db.backends.mysql",
        "NAME": config("DB_DATABASE"),
        "USER": config("DB_USERNAME"),
        "PASSWORD": config("DB_PASSWORD", default=""),
        "HOST": config("DB_HOST", default="127.0.0.1"),
        "PORT": config("DB_PORT", default="3306"),
    }}
elif DB_CONNECTION == "pgsql":
    DATABASES = {"default": {
        "ENGINE": "django.db.backends.postgresql",
        "NAME": config("DB_DATABASE"),
        "USER": config("DB_USERNAME"),
        "PASSWORD": config("DB_PASSWORD", default=""),
        "HOST": config("DB_HOST", default="127.0.0.1"),
        "PORT": config("DB_PORT", default="5432"),
    }}
else:
    DATABASES = {
    'default': dj_database_url.parse("postgresql://jaramarket_db_user:3W8PsKFhskKtA6GoQb0UxHJvqjAVDVTn@dpg-d8rt7a36sc1c73boctl0-a.oregon-postgres.render.com/jaramarket_db")
        }
    # DATABASES = {"default": {
    #     "ENGINE": "django.db.backends.sqlite3",
    #     "NAME": BASE_DIR / "db.sqlite3",
    # }}

AUTH_USER_MODEL = "accounts.User"

# Laravel hashes passwords with bcrypt; use bcrypt as the default hasher so
# existing password hashes verify directly after a DB import.
PASSWORD_HASHERS = [
    "django.contrib.auth.hashers.BCryptSHA256PasswordHasher",
    "django.contrib.auth.hashers.BCryptPasswordHasher",
    "django.contrib.auth.hashers.PBKDF2PasswordHasher",
]

AUTH_PASSWORD_VALIDATORS = [
    {"NAME": "django.contrib.auth.password_validation.MinimumLengthValidator",
     "OPTIONS": {"min_length": 8}},
]

LANGUAGE_CODE = "en-us"
TIME_ZONE = config("APP_TIMEZONE", default="UTC")
USE_I18N = True
USE_TZ = True

STATIC_URL = "static/"
STATIC_ROOT = BASE_DIR / "staticfiles"
STATICFILES_STORAGE = "whitenoise.storage.CompressedManifestStaticFilesStorage"
MEDIA_URL = "/storage/"            # mirrors Laravel public storage path
MEDIA_ROOT = BASE_DIR / "public"   # drop the contents of public.zip here
DEFAULT_AUTO_FIELD = "django.db.models.BigAutoField"

# ── DRF ──
REST_FRAMEWORK = {
    "DEFAULT_AUTHENTICATION_CLASSES": (
        "rest_framework_simplejwt.authentication.JWTAuthentication",
    ),
    "DEFAULT_PERMISSION_CLASSES": ("rest_framework.permissions.IsAuthenticated",),
    "DEFAULT_FILTER_BACKENDS": (
        "django_filters.rest_framework.DjangoFilterBackend",
        "rest_framework.filters.SearchFilter",
        "rest_framework.filters.OrderingFilter",
    ),
    "DEFAULT_PAGINATION_CLASS": "rest_framework.pagination.PageNumberPagination",
    "PAGE_SIZE": 15,
    "EXCEPTION_HANDLER": "api.utils.api_exception_handler",
}

SIMPLE_JWT = {
    "ACCESS_TOKEN_LIFETIME": timedelta(days=config("JWT_TTL_DAYS", default=7, cast=int)),
    "REFRESH_TOKEN_LIFETIME": timedelta(days=30),
    "AUTH_HEADER_TYPES": ("Bearer",),
    "USER_ID_FIELD": "id",
    "USER_ID_CLAIM": "user_id",
    # Use a dedicated signing key (min 32 chars for HS256). Falls back to SECRET_KEY.
    "SIGNING_KEY": config("JWT_SECRET_KEY", default=None) or SECRET_KEY,
}

# ── CORS ──
CORS_ALLOW_ALL_ORIGINS = config("CORS_ALLOW_ALL", default=True, cast=bool)
CORS_ALLOW_CREDENTIALS = True
# Explicit origin allowlist (used when CORS_ALLOW_ALL_ORIGINS is False)
CORS_ALLOWED_ORIGINS = config(
    "CORS_ALLOWED_ORIGINS",
    default="http://localhost:3000,http://localhost:8000,http://127.0.0.1:3000,http://127.0.0.1:8000",
    cast=Csv(),
)
# Always allow any localhost port (Flutter web dev servers use random ports)
CORS_ALLOWED_ORIGIN_REGEXES = [
    r"^http://localhost:\d+$",
    r"^http://127\.0\.0\.1:\d+$",
]

# ── Third-party integrations (mirror Laravel config/services.php) ──
PAYSTACK_SECRET_KEY = config("PAYSTACK_SECRET_KEY", default="")
PAYSTACK_PUBLIC_KEY = config("PAYSTACK_PUBLIC_KEY", default="")
PAYSTACK_BASE_URL = config("PAYSTACK_BASE_URL", default="https://api.paystack.co")
FLUTTERWAVE_SECRET_KEY = config("FLUTTERWAVE_SECRET_KEY", default="")
FLUTTERWAVE_BASE_URL = config("FLUTTERWAVE_BASE_URL", default="https://api.flutterwave.com/v3")
TERMII_API_KEY = config("TERMII_API_KEY", default="")
TERMII_SENDER_ID = config("TERMII_SENDER_ID", default="Jaramarket")
TERMII_BASE_URL = config("TERMII_BASE_URL", default="https://api.ng.termii.com")
FIREBASE_CREDENTIALS = config("FIREBASE_CREDENTIALS", default="")

PAYMENT_DEFAULT_GATEWAY = config("PAYMENT_DEFAULT_GATEWAY", default="paystack")

# Comma-separated Google OAuth client IDs (Android, iOS, Web). Get from Google Cloud Console.
GOOGLE_CLIENT_IDS = config("GOOGLE_CLIENT_IDS", default="")

# ── Email (console backend by default; set EMAIL_* in .env for real SMTP) ──
EMAIL_BACKEND = config("EMAIL_BACKEND", default="django.core.mail.backends.smtp.EmailBackend")
# "django.core.mail.backends.console.EmailBackend")
# EMAIL_HOST = config("EMAIL_HOST", default="")
# EMAIL_PORT = config("EMAIL_PORT", default=587, cast=int)
# EMAIL_HOST_USER = config("EMAIL_HOST_USER", default="")
# EMAIL_HOST_PASSWORD = config("EMAIL_HOST_PASSWORD", default="")
# EMAIL_USE_TLS = config("EMAIL_USE_TLS", default=True, cast=bool)
# DEFAULT_FROM_EMAIL = config("DEFAULT_FROM_EMAIL", default="Jaraman <no-reply@jaraman.local>")

EMAIL_BACKEND = "anymail.backends.brevo.EmailBackend"

ANYMAIL = {
    "BREVO_API_KEY": config("BREVO_API_KEY", default=""),
}

DEFAULT_FROM_EMAIL = config("DEFAULT_FROM_EMAIL", default="novelux <ekweredaniel8@gmail.com>")

# Optional: Set a reply-to configuration if your business logic demands it
SERVER_EMAIL = DEFAULT_FROM_EMAIL
# # Add these lines anywhere in settings.py
# ACCOUNT_EMAIL_VERIFICATION = 'none'
# ACCOUNT_EMAIL_REQUIRED = False

# ── Celery (queued jobs). EAGER by default so no broker is required. ──
CELERY_BROKER_URL = config("CELERY_BROKER_URL", default="redis://localhost:6379/0")
CELERY_RESULT_BACKEND = config("CELERY_RESULT_BACKEND", default="")
CELERY_TASK_ALWAYS_EAGER = config("CELERY_TASK_ALWAYS_EAGER", default=True, cast=bool)
CELERY_TASK_EAGER_PROPAGATES = True
CELERY_ACCEPT_CONTENT = ["json"]
CELERY_TASK_SERIALIZER = "json"
CELERY_RESULT_SERIALIZER = "json"

# Enforce Paystack webhook signature unless explicitly disabled (off in DEBUG).
PAYSTACK_VERIFY_WEBHOOK = config("PAYSTACK_VERIFY_WEBHOOK", default=not DEBUG, cast=bool)

#https://jaramarket-backend.onrender.com/api/jaram/webhook/paystack

# ── Channels (WebSockets) ──
# In-memory layer needs no broker (single-process). For production use
# channels_redis: CHANNEL_LAYERS backend channels_redis.core.RedisChannelLayer.
CHANNEL_REDIS_URL = config("CHANNEL_REDIS_URL", default="")
if CHANNEL_REDIS_URL:
    CHANNEL_LAYERS = {"default": {
        "BACKEND": "channels_redis.core.RedisChannelLayer",
        "CONFIG": {"hosts": [CHANNEL_REDIS_URL]},
    }}
else:
    CHANNEL_LAYERS = {"default": {"BACKEND": "channels.layers.InMemoryChannelLayer"}}
