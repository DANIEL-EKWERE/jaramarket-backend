from rest_framework.decorators import api_view, permission_classes
from rest_framework.permissions import AllowAny

from api.utils import error, success
from api.services import PaymentGateway, WalletService
from api.services._base import USER_TYPE
from .models import Bank, PaymentLog, TransactionLog
from .serializers import BankSerializer, TransactionSerializer, TransferSerializer, WalletSerializer


def _paginate(request, qs, serializer_cls):
    from rest_framework.pagination import PageNumberPagination
    p = PageNumberPagination()
    p.page_size = int(request.query_params.get("per_page", 15))
    page = p.paginate_queryset(qs, request)
    data = serializer_cls(page, many=True, context={"request": request}).data
    return {"data": data, "current_page": p.page.number,
            "last_page": p.page.paginator.num_pages, "total": p.page.paginator.count,
            "per_page": p.page_size}


_wallet_svc = WalletService()


@api_view(["GET"])
def fetch_wallet(request):
    return success("User Wallet retrieved successfully",
                   WalletSerializer(_wallet_svc.balance(request.user)).data, status=201)


@api_view(["GET"])
def wallet_balance(request):
    return success("Wallet balance retrieved",
                   WalletSerializer(_wallet_svc.balance(request.user)).data)


@api_view(["GET"])
def wallet_transactions(request):
    qs = _wallet_svc.transactions(request.user)
    return success("Transactions retrieved", _paginate(request, qs, TransactionSerializer))


@api_view(["POST"])
def wallet_transfer(request):
    from django.contrib.auth.hashers import check_password as _chk
    amount = request.data.get("amount")
    bank_code = request.data.get("bank_code")
    account_number = request.data.get("account_number")
    pin = request.data.get("pin_token") or request.data.get("pin")
    if not all([amount, bank_code, account_number]):
        return error("amount, bank_code and account_number are required", status=422)
    if not request.user.pin:
        return error("Please set a transaction PIN before withdrawing.", status=422)
    if not pin or not _chk(str(pin), request.user.pin):
        return error("Invalid transaction PIN.", status=422)
    try:
        result = _wallet_svc.transfer_to_bank(request.user, amount, bank_code, account_number)
    except ValueError as e:
        return error(str(e), status=422)
    return success("Transfer initiated", result)


@api_view(["GET"])
def banks_index(request):
    return success("Banks retrieved", BankSerializer(Bank.objects.all(), many=True).data)


@api_view(["POST"])
def fund_wallet(request):
    amount = request.data.get("amount")
    if not amount:
        return error("amount is required", status=422)
    gateway_name = request.data.get("gateway") or request.data.get("payment_gateway")
    gateway = PaymentGateway.resolve(gateway_name)
    ref = PaymentGateway.gen_ref("FUND")
    result = gateway.initialize_transaction(
        request.user.email, int(float(amount) * 100), ref,
        metadata={"user_id": request.user.id, "purpose": "wallet_funding"})
    return success("Transaction initialized", result)


@api_view(["GET"])
@permission_classes([AllowAny])
def verify_transaction(request, slug):
    return success("Transaction verified", PaymentGateway.resolve().verify_transaction(slug))


@api_view(["POST"])
@permission_classes([AllowAny])
def paystack_webhook_v2(request):
    import hashlib
    import hmac as _hmac
    from django.conf import settings as _settings
    from api.tasks import process_paystack_webhook

    if getattr(_settings, "PAYSTACK_VERIFY_WEBHOOK", False):
        signature = request.META.get("HTTP_X_PAYSTACK_SIGNATURE", "")
        expected = _hmac.new(_settings.PAYSTACK_SECRET_KEY.encode(),
                             request.body, hashlib.sha512).hexdigest()
        if not _hmac.compare_digest(signature, expected):
            return error("You're not authorized to access this resource.", status=403)

    if getattr(_settings, "CELERY_TASK_ALWAYS_EAGER", True):
        payload = process_paystack_webhook.apply(args=[request.data]).get()
    else:
        process_paystack_webhook.delay(request.data)
        payload = {"queued": True}
    return success("success", payload)


@api_view(["GET"])
def payments_all(request):
    """
    Return the user's full wallet transaction history from TransactionLog.
    Covers every money movement: order payments, wallet funding, withdrawals,
    refunds, vendor credits, referral bonuses — anything recorded by
    TransactionLogService.debit / .credit.
    """
    qs = TransactionLog.objects.filter(
        account_owner_type=USER_TYPE,
        account_owner_id=request.user.id,
    ).order_by("-created_at")

    user_name = request.user.name
    unit = TransactionLog.SMALLEST_CURRENCY_UNIT  # 100 — amounts stored in kobo-equivalent

    data = [
        {
            "id": t.id,
            "txn_ref": t.reference or "",
            "amount": str(round(t.amount / unit, 2)),
            # Flutter wallet screen checks `status == 'success'` to colour-code
            # direction: green + for credits, red - for debits.
            "status": "success" if t.transaction_type == "credit" else "debit",
            "provider": "wallet",
            "gateway_response": t.comment or (
                "Wallet credited" if t.transaction_type == "credit" else "Wallet debited"
            ),
            "transaction_mode": t.transaction_type or "",
            "user_name": user_name,
            "created_at": t.created_at.isoformat() if t.created_at else "",
        }
        for t in qs[:100]
    ]
    return success("Transactions retrieved successfully", data)


@api_view(["GET"])
def payment_show(request, id):
    p = PaymentLog.objects.filter(id=id).first()
    if not p:
        return error("Payment not found", status=404)
    return success("Payment retrieved", {"id": p.id, "txn_ref": p.txn_ref, "amount": p.amount,
                                         "status": p.status, "provider": p.provider,
                                         "meta": p.meta, "created_at": p.created_at})


@api_view(["GET"])
def transfers_index(request):
    qs = _wallet_svc.transfers(request.user)
    total = sum(t.amount for t in qs) / 100
    return success("Transfers fetched successfully", {
        "data": TransferSerializer(qs[:15], many=True).data,
        "total_transfer_amount": f"{total:,.2f}"})
