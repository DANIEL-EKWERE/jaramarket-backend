"""Reconcile PaymentLog rows stuck at status="pending" against Paystack directly.

Use this to backfill wallet credits that were missed because the configured
Paystack webhook URL was wrong (pointing at the wrong domain) so
`charge.success` events never reached `paystack_webhook_v2`. It calls
Paystack's own /transaction/verify/{reference} for each pending row and only
credits a wallet if Paystack confirms the charge actually succeeded — it
never guesses or trusts local state alone.

Crediting reuses HandlePaystackWebhookService._charge_success(), the exact
same code path the webhook itself uses, including its built-in
already-processed guard (skips rows already marked "success"), so running
this after the webhook is fixed and has caught up is safe — nothing gets
credited twice.

Defaults to a dry run. Pass --apply to actually write changes.

Examples:
    python manage.py reconcile_pending_payments
    python manage.py reconcile_pending_payments --reference FUND-GMSWZNT3DYE2MVJY --apply
    python manage.py reconcile_pending_payments --apply
"""
from django.core.management.base import BaseCommand

from apps.finance.models import PaymentLog
from api.services.payment import PaymentGateway, HandlePaystackWebhookService


class Command(BaseCommand):
    help = "Verify pending Paystack payment_logs against Paystack and credit wallets for any confirmed successful but never credited (e.g. due to a broken webhook URL)."

    def add_arguments(self, parser):
        parser.add_argument("--apply", action="store_true",
                             help="Actually credit wallets / update rows. Without this, only reports what would happen.")
        parser.add_argument("--reference", type=str, default=None,
                             help="Only reconcile this single txn_ref instead of all pending rows.")

    def handle(self, *args, **options):
        apply_changes = options["apply"]
        ref_filter = options.get("reference")

        qs = PaymentLog.objects.filter(status="pending", provider="paystack")
        if ref_filter:
            qs = qs.filter(txn_ref=ref_filter)
        qs = qs.order_by("created_at")

        if not qs.exists():
            self.stdout.write("No matching pending payment_logs found.")
            return

        if not apply_changes:
            self.stdout.write(self.style.WARNING(
                "DRY RUN — no wallets will be credited. Pass --apply to actually credit.\n"))

        gateway = PaymentGateway.resolve("paystack")
        webhook_service = HandlePaystackWebhookService()

        credited = skipped = errored = 0

        for log in qs:
            naira = log.amount / 100
            self.stdout.write(f"{log.txn_ref}  ₦{naira:,.2f}  user_id={log.transaction_owner_id}  "
                               f"created={log.created_at}")
            try:
                result = gateway.verify_transaction(log.txn_ref)
            except Exception as exc:
                self.stderr.write(self.style.ERROR(f"  ERROR verifying with Paystack: {exc}"))
                errored += 1
                continue

            data = (result or {}).get("data") or {}
            paystack_status = data.get("status")

            if paystack_status == "success":
                if apply_changes:
                    outcome = webhook_service._charge_success(data)
                    if outcome.get("note") == "already processed":
                        self.stdout.write(f"  already credited previously — skipped")
                        skipped += 1
                    else:
                        self.stdout.write(self.style.SUCCESS(f"  CREDITED ₦{naira:,.2f}"))
                        credited += 1
                else:
                    self.stdout.write(self.style.SUCCESS(
                        f"  Paystack confirms SUCCESS — would credit ₦{naira:,.2f}"))
                    credited += 1
            elif paystack_status in ("failed", "abandoned", "reversed"):
                self.stdout.write(f"  Paystack says '{paystack_status}' — never actually paid, leaving as-is")
                skipped += 1
            else:
                self.stdout.write(f"  Paystack status: {paystack_status!r} — leaving as-is for manual review")
                skipped += 1

        self.stdout.write("")
        verb = "Credited" if apply_changes else "Would credit"
        self.stdout.write(self.style.SUCCESS(
            f"{verb}: {credited}   Skipped/left alone: {skipped}   Errors: {errored}"))
