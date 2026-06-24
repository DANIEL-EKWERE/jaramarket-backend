"""
Service layer — mirrors app/Services/* and app/Utils/Util.php.

Money-path fidelity notes:
  * Commission resolution follows Util::getCommission (threshold -> lowest slab
    -> banded lookup on the commissions table).
  * Order creation requires wallet balance >= total, debits the wallet, then
    explodes products into their constituent ingredient line-items (saveFood) and
    adds standalone ingredient line-items (saveIngredients); each line records
    amount / commision / vendor_amount / referral / referral_id.
  * transaction_logs.amount is stored in the smallest unit (kobo, x100) to stay
    consistent with TransactionLog.amount_major and the Eloquent `Info` scope.
"""

from ._base import OTP_TTL_MINUTES, ORDER_TYPE, USER_TYPE, _d, _setting, issue_tokens
from .auth import LoginService, UserRegistrationService
from .order import OrderService, TransactionLogService, get_commission, models_q_max
from .payment import Flutterwave, HandlePaystackWebhookService, Paystack, PaymentGateway
from .sms import Termii
from .wallet import WalletService

__all__ = [
    # base helpers
    "issue_tokens", "_setting", "_d", "USER_TYPE", "ORDER_TYPE", "OTP_TTL_MINUTES",
    # auth
    "UserRegistrationService", "LoginService",
    # order
    "TransactionLogService", "OrderService", "get_commission", "models_q_max",
    # payment
    "PaymentGateway", "Paystack", "Flutterwave", "HandlePaystackWebhookService",
    # wallet
    "WalletService",
    # sms
    "Termii",
]
