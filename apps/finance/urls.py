from django.urls import path
from . import views as v

urlpatterns = [
    path("webhook/paystack", v.paystack_webhook_v2),
    path("verify-transaction/<slug:slug>", v.verify_transaction),
    path("fetch-wallet", v.fetch_wallet),
    path("wallet", v.wallet_balance),
    path("wallet/transactions", v.wallet_transactions),
    path("wallet/transfer-to-bank", v.wallet_transfer),
    path("wallets/fund", v.fund_wallet),
    path("banks", v.banks_index),
    path("payments/initialize-transaction", v.fund_wallet),
    path("payments", v.payments_all),
    path("payments/<int:id>", v.payment_show),
    path("payments/callback", v.verify_transaction),
    path("transfers", v.transfers_index),
]
