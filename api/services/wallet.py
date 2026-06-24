"""Wallet service."""
from apps.finance.models import Transfer, TransactionLog, Wallet
from ._base import USER_TYPE, _d
from .order import TransactionLogService
from .payment import PaymentGateway


class WalletService:
    def balance(self, user):
        wallet = Wallet.objects.filter(user=user).order_by("id").first()
        if wallet is None:
            wallet = Wallet.objects.create(user=user, balance=0)
        return wallet

    def transactions(self, user, tx_type=None):
        qs = TransactionLog.objects.filter(account_owner_type=USER_TYPE, account_owner_id=user.id)
        if tx_type:
            qs = qs.filter(transaction_type=tx_type)
        return qs.order_by("-created_at")

    def credit(self, user, amount, reference=None, comment=""):
        log = TransactionLogService.credit(user.id, USER_TYPE, amount, comment=comment)
        from ..notifications import wallet_notification
        wallet = Wallet.objects.filter(user=user).first()
        wallet_notification(user, "credit", amount,
                            wallet.balance if wallet else 0,
                            reference or log.reference, comment or "Wallet credited")
        return log

    def transfer_to_bank(self, user, amount, bank_code, account_number):
        amount = _d(amount)
        if _d(self.balance(user).balance) < amount:
            raise ValueError("Insufficient balance.")
        TransactionLogService.debit(user.id, USER_TYPE, amount, comment="Bank withdrawal")
        from ..notifications import wallet_notification, payment_notification
        from ..email_templates import transfer_initiated_email
        from ..notifications import send_email
        wallet = Wallet.objects.filter(user=user).first()
        ref = PaymentGateway.gen_ref("TRF")
        wallet_notification(user, "debit", amount,
                            wallet.balance if wallet else 0,
                            ref, f"Withdrawal of ₦{amount}")
        html = transfer_initiated_email(user.firstname or user.email, amount,
                                        user.bank_name, user.account_number)
        send_email(user.email, "Withdrawal Initiated",
                   f"Your withdrawal of ₦{amount} is being processed.", html=html)
        gateway = PaymentGateway.resolve("paystack")
        recipient_code = user.recipient_code or gateway.create_transfer_recipient(
            account_number, bank_code, user.name)
        result = gateway.initiate_transfer(int(amount * 100), recipient_code)
        transfer_ref = result.get("reference", ref)
        Transfer.objects.create(
            reference=transfer_ref,
            recipient_code=recipient_code or "", amount=int(amount * 100),
            owner_type=USER_TYPE, owner_id=user.id, bank_code=bank_code,
            account_number=account_number, bank_name=user.bank_name or "",
            account_name=user.account_name or "", status=result.get("status", "pending"))
        payment_notification(user, amount, result.get("status", "pending"), transfer_ref)
        return result

    def transfers(self, user):
        return Transfer.objects.filter(owner_type=USER_TYPE, owner_id=user.id).order_by("-created_at")
