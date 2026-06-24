"""Order service and transaction ledger."""
import secrets
import string
from decimal import ROUND_HALF_UP, Decimal

from django.db import transaction
from django.db.models import Q
from django.utils import timezone

from apps.accounts.models import Roles
from apps.catalogue.models import Ingredient, IngredientProduct, Product
from apps.customers.models import Address
from apps.finance.models import Commission, TransactionLog, Wallet
from apps.orders.models import Order, OrderItem, OrderItemLog
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


class TransactionLogService:
    @staticmethod
    def _ref():
        return "TXN-" + "".join(secrets.choice(string.ascii_uppercase + string.digits) for _ in range(14))

    @classmethod
    @transaction.atomic
    def debit(cls, account_owner_id, account_owner_type, amount, owner_id=None,
              owner_type=None, currency="NGN", comment=None):
        amount = _d(amount)
        wallet = Wallet.objects.filter(user_id=account_owner_id).first()
        old = _d(wallet.balance) if wallet else Decimal("0")
        new = old - amount
        if wallet:
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
        wallet = Wallet.objects.filter(user_id=account_owner_id).first()
        old = _d(wallet.balance) if wallet else Decimal("0")
        new = old + amount
        if wallet:
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

    @transaction.atomic
    def create_order(self, user, data):
        total = _d(data.get("total"))
        wallet = Wallet.objects.filter(user=user).first()
        if not wallet or _d(wallet.balance) < total:
            raise ValueError("Insufficient wallet balance.")

        address = None
        if data.get("address_id"):
            address = Address.objects.filter(id=data["address_id"], user=user).first()
            if not address:
                raise ValueError("Invalid delivery address")

        order = Order.objects.create(
            order_date=data.get("order_date") or timezone.now(),
            reference=self._reference(), user=user, address=address,
            delivery_type=data.get("delivery_type", "standard"),
            shipping_fee=_d(data.get("shipping_fee", 0)),
            service_charge=_d(data.get("service_charge", 0)),
            vat=_d(data.get("vat", 0)), total=total,
            remarks=data.get("remarks"), meal_prep=data.get("meal_prep"),
            status="pending")

        TransactionLogService.debit(user.id, USER_TYPE, total, order.id, ORDER_TYPE,
                                    "NGN", f"Payment for Order #{order.reference}")

        self._save_food(data, order, user)
        self._save_ingredients(data, order, user)

        from ..notifications import wallet_notification, order_placed_notification, new_order_notification
        wallet = Wallet.objects.filter(user=user).first()
        wallet_notification(user, "debit", total, wallet.balance if wallet else 0,
                            order.reference, f"Payment for Order #{order.reference}")
        order_placed_notification(user, order)

        # Notify vendors whose categories match any ingredient in this order
        from apps.accounts.models import User as _User
        ingredient_cat_ids = set(
            order.items.filter(ingredient__isnull=False)
            .values_list("ingredient__category_id", flat=True)
        )
        if ingredient_cat_ids:
            for vendor in _User.objects.filter(
                role=Roles.VENDOR, is_active=True,
                categories__id__in=ingredient_cat_ids
            ).distinct():
                new_order_notification(vendor, order)
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
        qs = OrderItem.objects.filter(status="pending", ingredient__isnull=False)
        if vendor.role != Roles.ADMIN:
            cat_ids = list(vendor.categories.values_list("id", flat=True))
            qs = qs.filter(ingredient__category_id__in=cat_ids)
        return qs.order_by("-created_at")

    def my_orders(self, vendor):
        qs = OrderItem.objects.all()
        if vendor.role == Roles.ADMIN:
            qs = qs.filter(vendor__isnull=False)
        else:
            qs = qs.filter(vendor=vendor)
        return qs.order_by("-vendor_at")

    def show_item(self, item_id):
        return OrderItem.objects.filter(id=item_id).first()

    @transaction.atomic
    def decide(self, vendor, item_id, data):
        item = OrderItem.objects.filter(id=item_id).first()
        if not item:
            raise ValueError("Order item not found")
        status = "processing" if data.get("status") == "accepted" else "pending"
        vendor_id = vendor.id
        if vendor.role == Roles.ADMIN and data.get("vendor_id"):
            vendor_id = data["vendor_id"]
        item.status = status
        item.vendor_id = vendor_id
        item.vendor_at = timezone.now()
        item.save(update_fields=["status", "vendor_id", "vendor_at"])
        OrderItemLog.objects.create(order_item=item, vendor_id=vendor_id,
                                    status=status, changed_at=timezone.now())
        from ..notifications import order_status_notification, order_item_status_notification
        order_item_status_notification(item.order.user, item, status)
        order = item.order
        if order.items.exclude(status="processing").count() == 0:
            order.status = "processing"
            order.save(update_fields=["status"])
            order_status_notification(order.user, order, "processing")
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
