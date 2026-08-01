"""Order service and transaction ledger."""
import secrets
import string
from datetime import timedelta
from decimal import ROUND_HALF_UP, Decimal

from django.db import transaction
from django.db.models import Q
from django.utils import timezone

from apps.accounts.models import Roles
from apps.catalogue.models import Ingredient, IngredientProduct, Product
from apps.customers.models import Address
from apps.finance.models import Commission, ServiceFeeTier, TransactionLog, Wallet
from apps.orders.models import MarketOfferResponse, Order, OrderItem, OrderItemLog
from apps.support.models import Setting
from ._base import ORDER_TYPE, USER_TYPE, _d, _setting


def get_commission(amount, total):
    """Return {'percentage', 'commission'} for a line `amount` within `total`."""
    amount = _d(amount)
    total = _d(total)
    min_order = _d(_setting("minimum_order_amount", 0))
    if total <= min_order:
        return {"percentage": Decimal("0"), "commission": Decimal("0")}

    lowest = Commission.objects.order_by("min_amount").first()
    if lowest and amount < lowest.min_amount:
        pct = _d(lowest.percentage)
        fee = (amount * pct / 100).quantize(Decimal("0.01"), ROUND_HALF_UP)
        return {"percentage": pct, "commission": fee}

    band = (Commission.objects
            .filter(min_amount__lte=amount)
            .filter(models_q_max(amount))
            .order_by("-min_amount").first())
    pct = _d(band.percentage) if band else Decimal("0")
    fee = (amount * pct / 100).quantize(Decimal("0.01"), ROUND_HALF_UP)
    return {"percentage": pct, "commission": fee}


def models_q_max(amount):
    return Q(max_amount__isnull=True) | Q(max_amount__gte=amount)


def calculate_service_fee(subtotal):
    """Return the order-level service fee for a given subtotal, per the
    tiered bands configured in ServiceFeeTier. Each tier applies when
    min_amount < subtotal <= max_amount (max_amount null = no upper bound),
    so the lower tier owns its own boundary (e.g. exactly ₦10,000 still
    gets the flat-fee tier below it, not the percentage tier above it)."""
    subtotal = _d(subtotal)
    if subtotal <= 0:
        return Decimal("0")
    tier = (ServiceFeeTier.objects
            .filter(min_amount__lt=subtotal)
            .filter(Q(max_amount__isnull=True) | Q(max_amount__gte=subtotal))
            .order_by("-min_amount").first())
    if not tier:
        return Decimal("0")
    if tier.fee_type == ServiceFeeTier.FLAT:
        return _d(tier.value)
    return (subtotal * _d(tier.value) / 100).quantize(Decimal("0.01"), ROUND_HALF_UP)


def _get_wallet(user_id):
    """Fetch a user's wallet, tolerating pre-existing duplicate rows (some
    accounts have more than one Wallet row with no DB-level uniqueness
    constraint ever enforced, e.g. from race conditions or the legacy data
    import) -- get_or_create() would crash with MultipleObjectsReturned in
    that case. Picks the oldest row deterministically rather than erroring;
    it does not merge/delete the other duplicate rows' balances."""
    wallet = Wallet.objects.filter(user_id=user_id).order_by("id").first()
    if wallet is None:
        wallet = Wallet.objects.create(user_id=user_id, balance=0)
    return wallet


class TransactionLogService:
    @staticmethod
    def _ref():
        return "TXN-" + "".join(secrets.choice(string.ascii_uppercase + string.digits) for _ in range(14))

    @classmethod
    @transaction.atomic
    def debit(cls, account_owner_id, account_owner_type, amount, owner_id=None,
              owner_type=None, currency="NGN", comment=None):
        amount = _d(amount)
        wallet = _get_wallet(account_owner_id)
        old = _d(wallet.balance)
        new = old - amount
        wallet.balance = new
        wallet.save(update_fields=["balance"])
        return TransactionLog.objects.create(
            account_owner_id=account_owner_id, account_owner_type=account_owner_type,
            owner_id=owner_id, owner_type=owner_type,
            amount=float(amount) * TransactionLog.SMALLEST_CURRENCY_UNIT,
            transaction_type="debit", reference=cls._ref(),
            old_balance=float(old), new_balance=float(new),
            wallet=wallet, currency=currency, comment=comment,
            is_refund=False, has_refund=False)

    @classmethod
    @transaction.atomic
    def credit(cls, account_owner_id, account_owner_type, amount, owner_id=None,
               owner_type=None, currency="NGN", comment=None):
        amount = _d(amount)
        wallet = _get_wallet(account_owner_id)
        old = _d(wallet.balance)
        new = old + amount
        wallet.balance = new
        wallet.save(update_fields=["balance"])
        return TransactionLog.objects.create(
            account_owner_id=account_owner_id, account_owner_type=account_owner_type,
            owner_id=owner_id, owner_type=owner_type,
            amount=float(amount) * TransactionLog.SMALLEST_CURRENCY_UNIT,
            transaction_type="credit", reference=cls._ref(),
            old_balance=float(old), new_balance=float(new),
            wallet=wallet, currency=currency, comment=comment,
            is_refund=True, has_refund=False)


class OrderService:
    def all(self, user):
        return Order.objects.filter(user=user).order_by("-created_at")

    def get_order_by_id(self, user, order_id):
        return Order.objects.filter(user=user, id=order_id).first()

    def _calculate_subtotal(self, data):
        """Independently resolve the order's subtotal server-side, mirroring
        how _save_food/_save_ingredients price each line item, so the
        service fee is computed against the real order value rather than
        whatever the client claims."""
        subtotal = Decimal("0")
        for ing in data.get("ingredients", []):
            model = Ingredient.objects.filter(id=ing["ingredient_id"]).first()
            if not model:
                raise ValueError(f"Ingredient {ing['ingredient_id']} not found")
            price = _d(ing.get("price") if ing.get("price") is not None else model.price)
            subtotal += price * _d(ing["quantity"])
        for pd in data.get("products", []):
            product = Product.objects.filter(id=pd["product_id"]).first()
            if not product:
                raise ValueError(f"Product {pd['product_id']} not found")
            for link in IngredientProduct.objects.filter(product=product):
                quantity = link.quantity or 1
                subtotal += _d(link.ingredient.price) * _d(quantity)
        return subtotal

    @transaction.atomic
    def create_order(self, user, data):
        subtotal = self._calculate_subtotal(data)
        shipping_fee = _d(data.get("shipping_fee", 0))
        vat = _d(data.get("vat", 0))
        service_charge = calculate_service_fee(subtotal)
        total = subtotal + shipping_fee + vat + service_charge

        wallet = _get_wallet(user.id)
        if _d(wallet.balance) < total:
            raise ValueError("Insufficient wallet balance.")

        address = None
        if data.get("address_id"):
            address = Address.objects.filter(id=data["address_id"], user=user).first()
            if not address:
                raise ValueError("Invalid delivery address")
        if not address or address.latitude is None or address.longitude is None:
            raise ValueError("A delivery address with a location is required to place an order.")

        order = Order.objects.create(
            order_date=data.get("order_date") or timezone.now(),
            reference=self._reference(), user=user, address=address,
            delivery_type=data.get("delivery_type", "standard"),
            shipping_fee=shipping_fee,
            service_charge=service_charge,
            vat=vat, total=total,
            remarks=data.get("remarks"), meal_prep=data.get("meal_prep"),
            status="pending")

        TransactionLogService.debit(user.id, USER_TYPE, total, order.id, ORDER_TYPE,
                                    "NGN", f"Payment for Order #{order.reference}")

        self._save_food(data, order, user)
        self._save_ingredients(data, order, user)

        from ..notifications import wallet_notification, order_placed_notification
        wallet = Wallet.objects.filter(user=user).first()
        wallet_notification(user, "debit", total, wallet.balance if wallet else 0,
                            order.reference, f"Payment for Order #{order.reference}")
        order_placed_notification(user, order)

        # Route each item to the closest market that can fulfil it, and
        # offer it to the eligible vendors stationed there -- unless the
        # order landed outside the dispatch window (default 09:00-18:30),
        # in which case dispatch is deferred until it reopens.
        from .dispatch import MarketDispatchService, next_dispatch_time
        dispatch_at = next_dispatch_time()
        if dispatch_at is None:
            MarketDispatchService().resolve(list(order.items.filter(ingredient__isnull=False)), address)
        else:
            order.scheduled_dispatch_at = dispatch_at
            order.save(update_fields=["scheduled_dispatch_at"])
        return order

    def _get_bonuses(self, price, quantity, order, user):
        item_total = _d(price) * _d(quantity)
        commission = get_commission(item_total, order.total)["commission"]
        referral_commission = Decimal("0")
        referral_id = None
        if user.referrer_id:
            prev = Order.objects.filter(user=user).exclude(id=order.id).count()
            is_first = prev == 0
            pct = _d(_setting("first_order_bonus", 0) if is_first
                     else _setting("repeat_order_bonus", 0))
            referral_commission = (commission * pct / 100).quantize(Decimal("0.01"), ROUND_HALF_UP)
            referral_id = user.referrer_id
        return {"item_total": item_total, "commission": commission,
                "referral_commission": referral_commission, "referral_id": referral_id}

    def _save_ingredients(self, data, order, user):
        for ing in data.get("ingredients", []):
            model = Ingredient.objects.filter(id=ing["ingredient_id"]).first()
            if not model:
                raise ValueError(f"Ingredient {ing['ingredient_id']} not found")
            price = _d(ing.get("price") if ing.get("price") is not None else model.price)
            b = self._get_bonuses(price, ing["quantity"], order, user)
            OrderItem.objects.create(
                order=order, ingredient=model, quantity=ing["quantity"], price=price,
                unit=ing.get("unit"), amount=b["item_total"], commision=b["commission"],
                vendor_amount=b["item_total"] - b["commission"],
                referral=b["referral_commission"], referral_user_id=b["referral_id"],
                status="pending")

    def _save_food(self, data, order, user):
        for pd in data.get("products", []):
            product = Product.objects.filter(id=pd["product_id"]).first()
            if not product:
                raise ValueError(f"Product {pd['product_id']} not found")
            for link in IngredientProduct.objects.filter(product=product):
                ingredient = link.ingredient
                quantity = link.quantity or 1
                b = self._get_bonuses(ingredient.price, quantity, order, user)
                OrderItem.objects.create(
                    order=order, product=product, ingredient=ingredient,
                    quantity=int(quantity), price=ingredient.price, unit=link.unit,
                    amount=b["item_total"], commision=b["commission"],
                    vendor_amount=b["item_total"] - b["commission"],
                    referral=b["referral_commission"], referral_user_id=b["referral_id"],
                    status="pending")

    @transaction.atomic
    def cancel_order(self, order):
        if order.status != "pending":
            raise ValueError("You cannot cancel this order.")
        TransactionLogService.credit(order.user_id, USER_TYPE, order.total, order.id,
                                     ORDER_TYPE, "NGN", f"Refund from Order #{order.reference}")
        order.status = "cancelled"
        order.save(update_fields=["status"])
        from ..notifications import order_status_notification, wallet_notification
        from apps.finance.models import Wallet as _Wallet
        wallet = _Wallet.objects.filter(user_id=order.user_id).first()
        wallet_notification(order.user, "credit", order.total,
                            wallet.balance if wallet else 0,
                            order.reference, f"Refund for cancelled Order #{order.reference}")
        order_status_notification(order.user, order, "cancelled")
        return order

    def _reference(self):
        return "ORD-" + "".join(secrets.choice(string.ascii_uppercase + string.digits) for _ in range(12))

    # ── Vendor ──
    def available_orders(self, vendor):
        qs = OrderItem.objects.select_related("ingredient", "product", "order__user")
        if vendor.role == Roles.ADMIN:
            return qs.filter(status__in=["pending", "offered"]).order_by("-created_at")
        # Only items currently offered to this vendor's market, where this
        # vendor hasn't already responded — first to accept wins.
        offered_item_ids = MarketOfferResponse.objects.filter(
            vendor=vendor, decision="pending", attempt__status="offered"
        ).values_list("attempt__order_item_id", flat=True)
        qs = qs.filter(status="offered", id__in=offered_item_ids)
        return qs.order_by("-created_at")

    def my_orders(self, vendor):
        qs = OrderItem.objects.select_related("ingredient", "product", "order__user")
        if vendor.role == Roles.ADMIN:
            qs = qs.filter(vendor__isnull=False)
        else:
            qs = qs.filter(vendor=vendor)
        return qs.order_by("-vendor_at")

    def show_item(self, item_id):
        return OrderItem.objects.filter(id=item_id).first()

    @transaction.atomic
    def decide(self, vendor, item_id, data):
        item = OrderItem.objects.select_for_update().filter(id=item_id).first()
        if not item:
            raise ValueError("Order item not found")
        accepted = data.get("status") == "accepted"
        is_admin_override = vendor.is_admin() and data.get("vendor_id")
        vendor_id = data["vendor_id"] if is_admin_override else vendor.id

        from ..notifications import order_status_notification, order_item_status_notification

        # Self-service vendor offers are exclusive to whoever the market
        # broadcast is currently live for. An admin manually assigning from
        # the fallback queue bypasses this (there's no live offer by then).
        attempt = item.market_attempts.filter(status="offered").order_by("-offered_at").first()
        response = None
        if not is_admin_override:
            if item.vendor_id is not None or item.status != "offered":
                raise ValueError("This order is no longer available.")
            response = attempt.responses.filter(vendor_id=vendor_id).first() if attempt else None

        if not accepted:
            OrderItemLog.objects.create(order_item=item, vendor_id=vendor_id,
                                        status="rejected", changed_at=timezone.now())
            if item.vendor_id == vendor_id:
                item.vendor_id = None
                item.save(update_fields=["vendor_id"])
            if response:
                response.decision = "declined"
                response.decided_at = timezone.now()
                response.save(update_fields=["decision", "decided_at"])
            if attempt and not attempt.responses.filter(decision__in=["pending", "accepted"]).exists():
                from .dispatch import MarketDispatchService
                MarketDispatchService().escalate(item, "all_declined")
            return item

        from .dispatch import _delivery_timeout_minutes
        item.status = "processing"
        item.vendor_id = vendor_id
        item.vendor_at = timezone.now()
        item.delivery_deadline = item.vendor_at + timedelta(minutes=_delivery_timeout_minutes())
        item.save(update_fields=["status", "vendor_id", "vendor_at", "delivery_deadline"])
        OrderItemLog.objects.create(order_item=item, vendor_id=vendor_id,
                                    status="processing", changed_at=timezone.now())
        if response:
            response.decision = "accepted"
            response.decided_at = timezone.now()
            response.save(update_fields=["decision", "decided_at"])
        if attempt:
            attempt.status = "accepted"
            attempt.resolved_at = timezone.now()
            attempt.save(update_fields=["status", "resolved_at"])
        order_item_status_notification(item.order.user, item, "processing")
        order = item.order
        if order.items.exclude(status="processing").count() == 0:
            order.status = "processing"
            order.save(update_fields=["status"])
            order_status_notification(order.user, order, "processing")
        return item

    @transaction.atomic
    def mark_delivered(self, vendor, item_id):
        """Vendor-facing: mark just their own line item as delivered. Does
        not pay anyone out or complete the order — that's still a separate
        QA/admin step via mark_completed, since one order can span multiple
        vendors and paying everyone out the moment one of them delivers
        would be wrong."""
        item = OrderItem.objects.filter(id=item_id).first()
        if not item:
            raise ValueError("Order item not found")
        if item.vendor_id != vendor.id and vendor.role not in Roles.ADMIN_ROLES:
            raise ValueError("This item is not assigned to you.")
        if item.status != "processing":
            raise ValueError(f"Item cannot be marked delivered from status '{item.status}'.")

        item.status = "delivered"
        item.delivery_deadline = None
        item.save(update_fields=["status", "delivery_deadline"])
        OrderItemLog.objects.create(order_item=item, vendor_id=item.vendor_id,
                                    status="delivered", changed_at=timezone.now())

        from ..notifications import order_status_notification, order_item_status_notification
        order_item_status_notification(item.order.user, item, "delivered")
        order = item.order
        if order.items.exclude(status__in=("delivered", "completed")).count() == 0:
            order.status = "delivered"
            order.save(update_fields=["status"])
            order_status_notification(order.user, order, "delivered")
        return item

    @transaction.atomic
    def mark_completed(self, qa_user, order_id):
        from django.db.models import Sum
        order = Order.objects.filter(id=order_id).first()
        if not order:
            raise ValueError("Order not found")
        if order.status in ("completed", "cancelled"):
            raise ValueError(f"Order #{order.reference} cannot be marked as completed again.")
        order.status = "completed"
        order.save(update_fields=["status"])
        order.items.all().update(status="completed", assurance_user=qa_user,
                                 assurance_at=timezone.now(), pass_quality_assurance=True)
        from ..notifications import order_status_notification
        order_status_notification(order.user, order, "completed")

        from ..notifications import wallet_notification
        from apps.accounts.models import User as _User
        from apps.finance.models import Wallet as _Wallet

        vendor_credits = (order.items.filter(vendor__isnull=False)
                          .values("vendor_id").annotate(total=Sum("vendor_amount")))
        for row in vendor_credits:
            if _d(row["total"]) <= 0:
                continue
            TransactionLogService.credit(row["vendor_id"], USER_TYPE, row["total"],
                                         order.id, ORDER_TYPE, "NGN",
                                         f"Payment from Order #{order.reference}")
            vendor_user = _User.objects.filter(id=row["vendor_id"]).first()
            if vendor_user:
                w = _Wallet.objects.filter(user_id=row["vendor_id"]).first()
                wallet_notification(vendor_user, "credit", row["total"],
                                    w.balance if w else 0, order.reference,
                                    f"Payment from Order #{order.reference}")

        referral_credits = (order.items.filter(referral_user__isnull=False)
                            .values("referral_user_id").annotate(total=Sum("referral")))
        for row in referral_credits:
            if _d(row["total"]) <= 0:
                continue
            TransactionLogService.credit(row["referral_user_id"], USER_TYPE, row["total"],
                                         order.id, ORDER_TYPE, "NGN", "Referral commission")
            ref_user = _User.objects.filter(id=row["referral_user_id"]).first()
            if ref_user:
                w = _Wallet.objects.filter(user_id=row["referral_user_id"]).first()
                wallet_notification(ref_user, "credit", row["total"],
                                    w.balance if w else 0, order.reference,
                                    "Referral commission earned")
        return order
