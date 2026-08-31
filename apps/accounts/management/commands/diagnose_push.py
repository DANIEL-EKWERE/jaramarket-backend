"""Why aren't push notifications arriving?

Push has several places to die quietly -- no credentials, credentials that
don't resolve to a real file, or simply no device token stored against the
account -- and none of them raise. This reports each one, and can send a
real test push to prove the whole chain end to end.

    python manage.py diagnose_push
    python manage.py diagnose_push --email someone@example.com
    python manage.py diagnose_push --email someone@example.com --send
"""
import os

from django.conf import settings
from django.core.management.base import BaseCommand

from apps.accounts.models import Roles, User


class Command(BaseCommand):
    help = "Diagnose push notification delivery (credentials, tokens, and an optional live test send)."

    def add_arguments(self, parser):
        parser.add_argument("--email", help="Check this account specifically.")
        parser.add_argument("--send", action="store_true",
                            help="Actually send a test push to --email (delivers to their device).")

    def handle(self, *args, **opts):
        ok = self.style.SUCCESS
        bad = self.style.ERROR
        warn = self.style.WARNING

        self.stdout.write(self.style.MIGRATE_HEADING("1. Firebase credentials"))
        creds = getattr(settings, "FIREBASE_CREDENTIALS", "")
        if not creds:
            self.stdout.write(bad("   NOT CONFIGURED — every push is a no-op."))
            self.stdout.write("   Put the service-account JSON in the project root "
                              "(next to manage.py) or set FIREBASE_CREDENTIALS.")
        elif creds.lstrip().startswith("{"):
            self.stdout.write(ok("   raw JSON supplied inline"))
        else:
            self.stdout.write(f"   path: {creds}")
            if os.path.isfile(creds):
                self.stdout.write(ok("   file exists"))
            else:
                self.stdout.write(bad("   FILE NOT FOUND at that path — push will be skipped."))

        self.stdout.write(self.style.MIGRATE_HEADING("2. Firebase SDK initialisation"))
        from api.notifications import FirebasePush
        app = FirebasePush._get_app()
        self.stdout.write(ok("   initialised") if app else
                          bad("   FAILED — see the 'Firebase init failed' entry in the log."))

        self.stdout.write(self.style.MIGRATE_HEADING("3. Registered device tokens"))
        total = User.objects.count()
        registered = User.objects.exclude(fcm_token=None).exclude(fcm_token="")
        self.stdout.write(f"   {registered.count()} of {total} accounts have an fcm_token")
        for role in (Roles.CUSTOMER, Roles.VENDOR, Roles.LOGISTICS):
            in_role = User.objects.filter(role=role)
            have = in_role.exclude(fcm_token=None).exclude(fcm_token="").count()
            line = f"     {role:<10} {have}/{in_role.count()}"
            self.stdout.write(ok(line) if have else warn(line))
        if not registered.exists():
            self.stdout.write(warn(
                "   No account has a token. The apps register one only AFTER login "
                "(POST /fcm-token); a device that has not signed in since that call "
                "was added will never receive a push."))

        if not opts["email"]:
            self.stdout.write("\nRun with --email <address> to check one account, "
                              "and add --send to deliver a real test push.")
            return

        self.stdout.write(self.style.MIGRATE_HEADING(f"4. Account {opts['email']}"))
        user = User.objects.filter(email=opts["email"]).first()
        if not user:
            self.stdout.write(bad("   No such user."))
            return
        self.stdout.write(f"   {user.name} — role={user.role} active={user.is_active}")
        if not user.fcm_token:
            self.stdout.write(bad("   No fcm_token — this account cannot receive push. "
                                  "Have them sign out and back in on the device."))
            return
        self.stdout.write(ok(f"   token: {user.fcm_token[:24]}…{user.fcm_token[-8:]}"))

        if not opts["send"]:
            self.stdout.write("\n   Add --send to deliver a real test push to this device.")
            return

        self.stdout.write(self.style.MIGRATE_HEADING("5. Live test send"))
        result = FirebasePush().send(
            user.fcm_token, "JaraMarket test",
            "Push notifications are working.", {"type": "diagnostic"})
        if result.get("sent"):
            self.stdout.write(ok(f"   delivered to FCM (id {result.get('message_id')})"))
            self.stdout.write("   If nothing appeared on the device, the token is stale "
                              "or notifications are disabled in the OS settings.")
        else:
            self.stdout.write(bad(f"   FAILED: {result}"))
