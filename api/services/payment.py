"""Payment gateway integrations and webhook handler."""
import secrets
import string

import requests
from django.conf import settings

from ..models import Transfer, Wallet
from ._base import USER_TYPE, _d
from .order import TransactionLogService
from ..notifications import wallet_notification, payment_notification, send_email


class PaymentGateway:
    @staticmethod
    def gen_ref(prefix="JRM"):
        return f"{prefix}-" + "".join(secrets.choice(string.ascii_uppercase + string.digits) for _ in range(16))

    @staticmethod
    def resolve(name=None):
        name = (name or settings.PAYMENT_DEFAULT_GATEWAY).lower()
        return {"paystack": Paystack(), "flutterwave": Flutterwave()}.get(name, Paystack())

    def initialize_transaction(self, email, amount_kobo, reference, metadata=None): ...
    def verify_transaction(self, reference): ...
    def create_transfer_recipient(self, account_number, bank_code, name): ...
    def initiate_transfer(self, amount_kobo, recipient_code): ...


class Paystack(PaymentGateway):
    def __init__(self):
        self.base = settings.PAYSTACK_BASE_URL
        self.secret = settings.PAYSTACK_SECRET_KEY

    @property
    def _h(self):
        return {"Authorization": f"Bearer {self.secret}", "Content-Type": "application/json"}

    def initialize_transaction(self, email, amount_kobo, reference, metadata=None):
        return requests.post(f"{self.base}/transaction/initialize", json={
            "email": email, "amount": amount_kobo, "reference": reference,
            "metadata": metadata or {}}, headers=self._h, timeout=30).json()

    def verify_transaction(self, reference):
        return requests.get(f"{self.base}/transaction/verify/{reference}",
                            headers=self._h, timeout=30).json()

    def create_transfer_recipient(self, account_number, bank_code, name):
        r = requests.post(f"{self.base}/transferrecipient", json={
            "type": "nuban", "name": name, "account_number": account_number,
            "bank_code": bank_code, "currency": "NGN"}, headers=self._h, timeout=30)
        return r.json().get("data", {}).get("recipient_code")

    def initiate_transfer(self, amount_kobo, recipient_code):
        return requests.post(f"{self.base}/transfer", json={
            "source": "balance", "amount": amount_kobo, "recipient": recipient_code,
            "reference": self.gen_ref("TRF")}, headers=self._h, timeout=30).json().get("data", {})

    def list_banks(self):
        return requests.get(f"{self.base}/bank?currency=NGN", headers=self._h, timeout=30).json().get("data", [])


class Flutterwave(PaymentGateway):
    def __init__(self):
        self.base = settings.FLUTTERWAVE_BASE_URL
        self.secret = settings.FLUTTERWAVE_SECRET_KEY

    @property
    def _h(self):
        return {"Authorization": f"Bearer {self.secret}", "Content-Type": "application/json"}

    def initialize_transaction(self, email, amount_kobo, reference, metadata=None):
        return requests.post(f"{self.base}/payments", json={
            "tx_ref": reference, "amount": amount_kobo / 100, "currency": "NGN",
            "customer": {"email": email}, "meta": metadata or {}}, headers=self._h, timeout=30).json()

    def verify_transaction(self, reference):
        return requests.get(f"{self.base}/transactions/verify_by_reference?tx_ref={reference}",
                            headers=self._h, timeout=30).json()


class HandlePaystackWebhookService:
    """
    Mirror app/Services/HandlePaystackWebhookService — dispatch by event:
      charge.success   -> credit the funding user's wallet
      transfer.success -> mark the matching Transfer successful
      transfer.failed  -> mark Transfer failed and refund the wallet
      charge.failed    -> no-op (logged)
    """
    def handle(self, payload):
        event = payload.get("event")
        data = payload.get("data", {}) or {}
        if event == "charge.success":
            return self._charge_success(data)
        if event == "transfer.success":
            return self._transfer_status(data, "success")
        if event == "transfer.failed":
            return self._transfer_status(data, "failed", refund=True)
        if event == "charge.failed":
            return {"event": event, "handled": True, "note": "payment failed"}
        return {"event": event, "handled": False, "note": "unknown event"}

    def _charge_success(self, data):
        from apps.accounts.models import User as _User
        meta = data.get("metadata", {}) or {}
        user_id = meta.get("user_id")
        amount_naira = _d(data.get("amount", 0)) / 100
        reference = data.get("reference", "")
        if user_id and amount_naira > 0:
            TransactionLogService.credit(int(user_id), USER_TYPE, amount_naira,
                                         comment="Wallet funding")
            user = _User.objects.filter(id=user_id).first()
            if user:
                from ..email_templates import wallet_funded_email
                wallet = Wallet.objects.filter(user=user).first()
                wallet_notification(user, "credit", amount_naira,
                                    wallet.balance if wallet else 0,
                                    reference, f"Wallet funded with ₦{amount_naira}")
                html = wallet_funded_email(user.firstname or user.email,
                                          amount_naira,
                                          wallet.balance if wallet else 0,
                                          reference)
                send_email(user.email, "Wallet Funded Successfully",
                           f"Your wallet has been credited with ₦{amount_naira}.", html=html)
        return {"event": "charge.success", "handled": True}

    def _transfer_status(self, data, status, refund=False):
        from apps.accounts.models import User as _User
        reference = data.get("reference")
        transfer = Transfer.objects.filter(reference=reference).first() if reference else None
        if transfer:
            transfer.status = status
            if status == "failed":
                transfer.failures = (transfer.failures or 0) + 1
            transfer.save(update_fields=["status", "failures"])
            user = _User.objects.filter(id=transfer.owner_id).first() if transfer.owner_type == USER_TYPE else None
            if user:
                amount_naira = _d(transfer.amount) / 100
                payment_notification(user, amount_naira, status, reference)
            if refund and transfer.owner_type == USER_TYPE:
                refund_amount = _d(transfer.amount) / 100
                TransactionLogService.credit(transfer.owner_id, USER_TYPE,
                                             refund_amount,
                                             comment="Refund: failed transfer")
                if user:
                    wallet = Wallet.objects.filter(user=user).first()
                    wallet_notification(user, "credit", refund_amount,
                                        wallet.balance if wallet else 0,
                                        reference, "Refund: failed bank transfer")
        return {"event": f"transfer.{status}", "handled": bool(transfer)}
