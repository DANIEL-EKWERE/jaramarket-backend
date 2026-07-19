"""
Admin / back-office API — JSON equivalents of the Blade admin controllers
(AdminController, DashboardController, Admin/FinanceController,
Admin/VendorManagementController) plus catalogue + commission CRUD.

Mounted under `jaram/admin/...`. Every endpoint is gated by role (admin roles)
and, where the original used `permission:*` middleware, by the matching
permission slug via `require_perms`.
"""
import random
import string
from decimal import Decimal

from django.db.models import Count, Sum
from django.utils import timezone
from rest_framework import permissions
from rest_framework.decorators import api_view, permission_classes

from apps.accounts.models import Roles, User
from apps.catalogue.models import Category, CategoryProduct, Ingredient, IngredientProduct, Product
from apps.finance.models import Commission, ServiceFeeTier, TransactionLog, Transfer, Wallet, Bank
from apps.orders.models import Order, OrderItem
from apps.support.models import Advertisement
from apps.geo.models import State
from apps.accounts.serializers import UserSerializer as _US
from apps.catalogue.serializers import (
    CategorySerializer as _CatS, ProductSerializer as _ProdS,
    IngredientSerializer as _IngS, AdvertisementSerializer as _AdvS,
)


class _S:
    """Thin shim so existing `S.FooSerializer(...)` calls keep working."""
    UserSerializer = _US
    CategorySerializer = _CatS
    ProductSerializer = _ProdS
    IngredientSerializer = _IngS
    AdvertisementSerializer = _AdvS


S = _S()
from .utils import error, success

USER_TYPE = "App\\Models\\User"


# ── Permissions ──
class IsAdmin(permissions.BasePermission):
    message = "Admin access required."

    def has_permission(self, request, view):
        return bool(request.user and request.user.is_authenticated and request.user.is_admin())


def require_perms(user, slugs):
    """Return None if allowed, else an error Response (mirrors hasAnyPermission + abort 403)."""
    if user.has_any_permission(slugs):
        return None
    return error("This action is unauthorized.", status=403)


def _paginate(request, qs, serialize, default=25):
    from rest_framework.pagination import PageNumberPagination
    p = PageNumberPagination()
    p.page_size = int(request.query_params.get("per_page", default))
    page = p.paginate_queryset(qs, request)
    return {"data": [serialize(o) for o in page],
            "current_page": p.page.number, "last_page": p.page.paginator.num_pages,
            "total": p.page.paginator.count, "per_page": p.page_size}


# ─────────────────────────────────────────────────────────────────────────────
#  Dashboard
# ─────────────────────────────────────────────────────────────────────────────
@api_view(["GET"])
@permission_classes([IsAdmin])
def dashboard(request):
    user = request.user
    state_id = user.state_id if user.is_state_admin() else None
    stats = {}

    def order_qs():
        qs = Order.objects.all()
        return qs.filter(user__state_id=state_id) if state_id else qs

    if user.has_perm_slug("view_orders"):
        oq = order_qs()
        stats.update({
            "total_orders": oq.count(),
            "pending_orders": oq.filter(status="pending").count(),
            "processing_orders": oq.filter(status="processing").count(),
            "completed_orders": oq.filter(status="completed").count(),
            "cancelled_orders": oq.filter(status="cancelled").count(),
        })
    if user.has_perm_slug("view_transactions"):
        stats["total_revenue"] = Order.objects.filter(status="completed").aggregate(s=Sum("total"))["s"] or 0
        stats["today_revenue"] = Order.objects.filter(
            status="completed", created_at__date=timezone.now().date()).aggregate(s=Sum("total"))["s"] or 0
    if user.has_perm_slug("view_users"):
        cq = User.objects.customers()
        stats["total_customers"] = (cq.filter(state_id=state_id) if state_id else cq).count()
    if user.has_perm_slug("view_vendors"):
        vq = User.objects.vendors()
        stats["total_vendors"] = (vq.filter(state_id=state_id) if state_id else vq).count()

    recent_orders = []
    if user.has_perm_slug("view_orders"):
        rq = order_qs().order_by("-created_at")[:8]
        recent_orders = [{"id": o.id, "reference": o.reference, "total": o.total,
                          "status": o.status, "user": o.user.name} for o in rq]

    top_products = []
    if user.has_perm_slug("view_reports"):
        top = (OrderItem.objects.filter(product__isnull=False)
               .values("product__name").annotate(total_quantity=Sum("quantity"))
               .order_by("-total_quantity")[:6])
        top_products = {"labels": [t["product__name"] for t in top],
                        "data": [int(t["total_quantity"] or 0) for t in top]}

    order_status_chart = {}
    if user.has_perm_slug("view_orders"):
        for row in order_qs().values("status").annotate(count=Count("id")):
            order_status_chart[row["status"]] = row["count"]

    return success("Dashboard data retrieved", {
        "stats": stats, "recent_orders": recent_orders,
        "top_products": top_products, "order_status_chart": order_status_chart})


# ─────────────────────────────────────────────────────────────────────────────
#  Finance
# ─────────────────────────────────────────────────────────────────────────────
@api_view(["GET"])
@permission_classes([IsAdmin])
def finance_transactions(request):
    if (resp := require_perms(request.user, ["view_transactions"])):
        return resp
    qs = TransactionLog.objects.all().order_by("-created_at")
    if request.query_params.get("type"):
        qs = qs.filter(transaction_type=request.query_params["type"])
    if request.query_params.get("start"):
        qs = qs.filter(created_at__date__gte=request.query_params["start"])
    if request.query_params.get("end"):
        qs = qs.filter(created_at__date__lte=request.query_params["end"])

    def owner_name(t):
        if t.account_owner_type == USER_TYPE:
            u = User.objects.filter(id=t.account_owner_id).first()
            return (u.name if u else None), (u.email if u else None)
        return None, None

    def ser(t):
        name, email = owner_name(t)
        return {"id": t.id, "reference": t.reference, "user_name": name, "user_email": email,
                "type": t.transaction_type, "amount": t.amount_major,
                "old_balance": t.old_balance, "new_balance": t.new_balance,
                "currency": t.currency, "status": t.status, "date": t.created_at}
    return success("Transactions retrieved", _paginate(request, qs, ser))


@api_view(["GET"])
@permission_classes([IsAdmin])
def finance_wallets(request):
    if (resp := require_perms(request.user, ["view_wallets"])):
        return resp
    summary = {
        "total_user_balance": Wallet.objects.filter(user__role=Roles.CUSTOMER).aggregate(s=Sum("balance"))["s"] or 0,
        "total_vendor_balance": Wallet.objects.filter(user__role=Roles.VENDOR).aggregate(s=Sum("balance"))["s"] or 0,
        "total_wallets": Wallet.objects.count(),
    }
    qs = Wallet.objects.select_related("user").all()
    if request.query_params.get("role"):
        qs = qs.filter(user__role=request.query_params["role"])
    if request.query_params.get("min_balance"):
        qs = qs.filter(balance__gte=Decimal(request.query_params["min_balance"]))

    def ser(w):
        return {"id": w.id, "user_id": w.user_id, "user_name": w.user.name,
                "user_email": w.user.email, "user_role": w.user.role, "balance": w.balance}
    return success("Wallets retrieved", {"summary": summary, **_paginate(request, qs, ser)})


@api_view(["GET"])
@permission_classes([IsAdmin])
def finance_withdrawals(request):
    if (resp := require_perms(request.user, ["manage_withdrawals", "view_transactions"])):
        return resp
    qs = Transfer.objects.all().order_by("-created_at")
    if request.query_params.get("status"):
        qs = qs.filter(status=request.query_params["status"])

    def ser(t):
        owner = User.objects.filter(id=t.owner_id).first() if t.owner_type == USER_TYPE else None
        return {"id": t.id, "reference": t.reference, "owner_name": owner.name if owner else None,
                "owner_email": owner.email if owner else None, "amount": Decimal(t.amount) / 100,
                "status": t.status or "pending", "bank_name": t.bank_name,
                "account_number": t.account_number, "date": t.created_at}
    return success("Withdrawals retrieved", _paginate(request, qs, ser))


@api_view(["GET"])
@permission_classes([IsAdmin])
def finance_user_transactions(request, user_id):
    if (resp := require_perms(request.user, ["view_transactions", "view_wallets"])):
        return resp
    user = User.objects.filter(id=user_id).first()
    if not user:
        return error("User not found", status=404)
    qs = TransactionLog.objects.filter(account_owner_type=USER_TYPE, account_owner_id=user_id).order_by("-created_at")
    if request.query_params.get("type"):
        qs = qs.filter(transaction_type=request.query_params["type"])
    credit = qs.filter(transaction_type="credit", is_refund=False).aggregate(s=Sum("amount"))["s"] or 0
    debit = qs.filter(transaction_type="debit").aggregate(s=Sum("amount"))["s"] or 0

    def ser(t):
        return {"id": t.id, "reference": t.reference, "type": t.transaction_type,
                "amount": t.amount_major, "comment": t.comment, "date": t.created_at}
    payload = _paginate(request, qs, ser)
    payload["totals"] = {"total_credit": Decimal(credit) / 100, "total_debit": Decimal(debit) / 100}
    payload["user"] = {"id": user.id, "name": user.name, "email": user.email}
    return success("User transactions retrieved", payload)


# ─────────────────────────────────────────────────────────────────────────────
#  Vendor management
# ─────────────────────────────────────────────────────────────────────────────
@api_view(["GET"])
@permission_classes([IsAdmin])
def vendors_list(request):
    if (resp := require_perms(request.user, ["view_vendors"])):
        return resp
    qs = User.objects.vendors().select_related("state")
    if request.query_params.get("state_id"):
        qs = qs.filter(state_id=request.query_params["state_id"])
    if request.query_params.get("category_id"):
        qs = qs.filter(categories__id=request.query_params["category_id"])
    if request.query_params.get("status"):
        qs = qs.filter(is_active=(request.query_params["status"] == "active"))

    def ser(v):
        wallet = Wallet.objects.filter(user=v).first()
        return {"id": v.id, "vendor_name": v.name, "email": v.email,
                "phone": v.phone_number, "state": v.state.name if v.state else None,
                "categories": list(v.categories.values_list("name", flat=True)),
                "wallet_balance": wallet.balance if wallet else 0, "is_active": v.is_active}
    return success("Vendors retrieved", _paginate(request, qs.distinct(), ser))


@api_view(["GET"])
@permission_classes([IsAdmin])
def vendor_show(request, vendor_id):
    if (resp := require_perms(request.user, ["view_vendors"])):
        return resp
    v = User.objects.filter(id=vendor_id, role=Roles.VENDOR).first()
    if not v:
        return error("Vendor not found", status=404)
    items = OrderItem.objects.filter(vendor=v)
    stats = {
        "total_orders": items.count(),
        "pending_orders": items.filter(status="pending").count(),
        "accepted_orders": items.filter(status="processing").count(),
        "completed_orders": items.filter(status="completed").count(),
        "total_earned": items.filter(status="completed").aggregate(s=Sum("vendor_amount"))["s"] or 0,
    }
    wallet = Wallet.objects.filter(user=v).first()
    return success("Vendor retrieved", {
        "vendor": {**S.UserSerializer(v).data,
                   "categories": list(v.categories.values("id", "name")),
                   "wallet_balance": wallet.balance if wallet else 0},
        "stats": stats})


# ─────────────────────────────────────────────────────────────────────────────
#  Admin & user management
# ─────────────────────────────────────────────────────────────────────────────
@api_view(["GET", "POST"])
@permission_classes([IsAdmin])
def admins_collection(request):
    if (resp := require_perms(request.user, ["manage_admins"])):
        return resp
    if request.method == "GET":
        qs = User.objects.filter(role=Roles.ADMIN).order_by("-created_at")
        return success("Admins retrieved", _paginate(request, qs, lambda u: S.UserSerializer(u).data, default=10))
    data = request.data
    if not all([data.get("firstname"), data.get("email"), data.get("password")]):
        return error("firstname, email and password are required", status=422)
    if User.all_objects.filter(email=data["email"]).exists():
        return error("Email already in use", status=422)
    admin = User(firstname=data["firstname"], lastname=data.get("lastname", ""),
                 email=data["email"], role=Roles.ADMIN, phone_number=data.get("phone"),
                 is_active=True, email_verified_at=timezone.now(),
                 referral_code="".join(random.choices(string.ascii_uppercase + string.digits, k=10)))
    admin.set_password(data["password"])
    admin.save()
    Wallet.objects.get_or_create(user=admin, defaults={"balance": 0})
    return success("Admin created successfully", S.UserSerializer(admin).data, status=201)


@api_view(["PUT", "PATCH"])
@permission_classes([IsAdmin])
def admin_update(request, admin_id):
    if (resp := require_perms(request.user, ["manage_admins"])):
        return resp
    admin = User.objects.filter(id=admin_id).first()
    if not admin:
        return error("Admin not found", status=404)
    for field in ["firstname", "lastname", "email"]:
        if request.data.get(field):
            setattr(admin, field, request.data[field])
    if request.data.get("phone"):
        admin.phone_number = request.data["phone"]
    if request.data.get("password"):
        admin.set_password(request.data["password"])
    admin.save()
    return success("Admin updated successfully", S.UserSerializer(admin).data)


@api_view(["GET"])
@permission_classes([IsAdmin])
def users_list(request):
    if (resp := require_perms(request.user, ["view_users"])):
        return resp
    qs = User.objects.all().order_by("-created_at")
    if request.query_params.get("role"):
        qs = qs.filter(role=request.query_params["role"])
    if request.query_params.get("search"):
        from django.db.models import Q
        s = request.query_params["search"]
        qs = qs.filter(Q(firstname__icontains=s) | Q(lastname__icontains=s) | Q(email__icontains=s))
    return success("Users retrieved", _paginate(request, qs, lambda u: S.UserSerializer(u).data, default=15))


@api_view(["PUT", "PATCH", "DELETE"])
@permission_classes([IsAdmin])
def user_update(request, user_id):
    if (resp := require_perms(request.user, ["manage_users"])):
        return resp
    u = User.objects.filter(id=user_id).first()
    if not u:
        return error("User not found", status=404)
    if request.method == "DELETE":
        u.deleted_at = timezone.now()
        u.save(update_fields=["deleted_at"])
        return success("User deleted successfully")
    for field in ["firstname", "lastname", "email", "role"]:
        if request.data.get(field):
            setattr(u, field, request.data[field])
    u.save()
    if request.data.get("role"):
        u.sync_default_permissions()
    return success("User updated successfully", S.UserSerializer(u).data)


@api_view(["PATCH"])
@permission_classes([IsAdmin])
def user_toggle_status(request, user_id):
    if (resp := require_perms(request.user, ["manage_users", "manage_admins"])):
        return resp
    u = User.objects.filter(id=user_id).first()
    if not u:
        return error("User not found", status=404)
    u.is_active = not u.is_active
    u.save(update_fields=["is_active"])
    return success("User status updated successfully", {"id": u.id, "is_active": u.is_active})


@api_view(["DELETE"])
@permission_classes([IsAdmin])
def user_destroy(request, user_id):
    if (resp := require_perms(request.user, ["manage_users", "manage_admins"])):
        return resp
    u = User.objects.filter(id=user_id).first()
    if not u:
        return error("User not found", status=404)
    u.deleted_at = timezone.now()  # soft delete (Eloquent SoftDeletes on users)
    u.save(update_fields=["deleted_at"])
    return success("User deleted successfully")


# ─────────────────────────────────────────────────────────────────────────────
#  Catalogue CRUD — categories / products / ingredients / advertisements
# ─────────────────────────────────────────────────────────────────────────────
@api_view(["POST"])
@permission_classes([IsAdmin])
def category_create(request):
    if (resp := require_perms(request.user, ["manage_categories"])):
        return resp
    if not request.data.get("name"):
        return error("name is required", status=422)
    cat = Category.objects.create(
        name=request.data["name"], description=request.data.get("description"),
        category_type_id=request.data.get("category_type_id", 1),
        sort_by=request.data.get("sort_by", 100))
    return success("Category created successfully", S.CategorySerializer(cat).data, status=201)


@api_view(["PUT", "PATCH", "DELETE"])
@permission_classes([IsAdmin])
def category_detail(request, id):
    if (resp := require_perms(request.user, ["manage_categories"])):
        return resp
    cat = Category.objects.filter(id=id).first()
    if not cat:
        return error("Category not found", status=404)
    if request.method == "DELETE":
        cat.delete()
        return success("Category deleted successfully")
    for field in ["name", "description", "category_type_id", "sort_by"]:
        if request.data.get(field) is not None:
            setattr(cat, field, request.data[field])
    cat.save()
    return success("Category updated successfully", S.CategorySerializer(cat).data)


@api_view(["POST"])
@permission_classes([IsAdmin])
def product_create(request):
    if (resp := require_perms(request.user, ["manage_products"])):
        return resp
    if not request.data.get("name"):
        return error("name is required", status=422)
    p = Product.objects.create(
        name=request.data["name"], description=request.data.get("description"),
        price=request.data.get("price", 0), discount_price=request.data.get("discount_price"),
        stock=request.data.get("stock", 0), preparation_steps=request.data.get("preparation_steps"),
        image_url=request.data.get("image_url"))
    for cid in request.data.get("category_ids", []):
        CategoryProduct.objects.get_or_create(product=p, category_id=cid)
    for ing in request.data.get("ingredients", []):
        IngredientProduct.objects.create(product=p, ingredient_id=ing["ingredient_id"],
                                         quantity=ing.get("quantity"), unit=ing.get("unit"))
    return success("Product created successfully", S.ProductSerializer(p).data, status=201)


@api_view(["PUT", "PATCH", "DELETE"])
@permission_classes([IsAdmin])
def product_detail(request, id):
    if (resp := require_perms(request.user, ["manage_products"])):
        return resp
    p = Product.objects.filter(id=id).first()
    if not p:
        return error("Product not found", status=404)
    if request.method == "DELETE":
        p.delete()
        return success("Product deleted successfully")
    for field in ["name", "description", "price", "discount_price", "stock",
                  "preparation_steps", "image_url"]:
        if request.data.get(field) is not None:
            setattr(p, field, request.data[field])
    p.save()
    return success("Product updated successfully", S.ProductSerializer(p).data)


@api_view(["POST"])
@permission_classes([IsAdmin])
def ingredient_create(request):
    if (resp := require_perms(request.user, ["manage_ingredients"])):
        return resp
    if not request.data.get("name"):
        return error("name is required", status=422)
    ing = Ingredient.objects.create(
        name=request.data["name"], description=request.data.get("description"),
        price=request.data.get("price", 0), discounted_price=request.data.get("discounted_price"),
        unit=request.data.get("unit", ""), stock=request.data.get("stock", 0),
        image_url=request.data.get("image_url"), category_id=request.data.get("category_id"))
    return success("Ingredient created successfully", S.IngredientSerializer(ing).data, status=201)


@api_view(["PUT", "PATCH", "DELETE"])
@permission_classes([IsAdmin])
def ingredient_detail(request, id):
    if (resp := require_perms(request.user, ["manage_ingredients"])):
        return resp
    ing = Ingredient.objects.filter(id=id).first()
    if not ing:
        return error("Ingredient not found", status=404)
    if request.method == "DELETE":
        ing.delete()
        return success("Ingredient deleted successfully")
    for field in ["name", "description", "price", "discounted_price", "unit",
                  "stock", "image_url", "category_id"]:
        if request.data.get(field) is not None:
            setattr(ing, field, request.data[field])
    ing.save()
    return success("Ingredient updated successfully", S.IngredientSerializer(ing).data)


@api_view(["GET", "POST"])
@permission_classes([IsAdmin])
def advertisements_collection(request):
    if request.method == "GET":
        return success("Advertisements retrieved",
                       S.AdvertisementSerializer(Advertisement.objects.all(), many=True).data)
    if (resp := require_perms(request.user, ["manage_settings"])):
        return resp
    ad = Advertisement.objects.create(
        type=request.data.get("type", "info"), value=request.data.get("value"),
        ingredient_ids=request.data.get("ingredient_ids", []),
        status=request.data.get("status", "active"), image=request.data.get("image", ""))
    return success("Advertisement created successfully", S.AdvertisementSerializer(ad).data, status=201)


@api_view(["PUT", "PATCH", "DELETE"])
@permission_classes([IsAdmin])
def advertisement_detail(request, id):
    if (resp := require_perms(request.user, ["manage_settings"])):
        return resp
    ad = Advertisement.objects.filter(id=id).first()
    if not ad:
        return error("Advertisement not found", status=404)
    if request.method == "DELETE":
        ad.delete()
        return success("Advertisement deleted successfully")
    for field in ["type", "value", "ingredient_ids", "status", "image"]:
        if request.data.get(field) is not None:
            setattr(ad, field, request.data[field])
    ad.save()
    return success("Advertisement updated successfully", S.AdvertisementSerializer(ad).data)


# ─────────────────────────────────────────────────────────────────────────────
#  Commission CRUD
# ─────────────────────────────────────────────────────────────────────────────
def _commission_payload(c):
    return {"id": c.id, "min_amount": c.min_amount, "max_amount": c.max_amount,
            "percentage": c.percentage, "created_at": c.created_at}


@api_view(["GET", "POST"])
@permission_classes([IsAdmin])
def commissions_collection(request):
    if request.method == "GET":
        if (resp := require_perms(request.user, ["view_commissions"])):
            return resp
        qs = Commission.objects.all().order_by("min_amount")
        return success("Commissions retrieved", [_commission_payload(c) for c in qs])
    if (resp := require_perms(request.user, ["manage_commissions"])):
        return resp
    if request.data.get("percentage") is None:
        return error("percentage is required", status=422)
    c = Commission.objects.create(
        min_amount=request.data.get("min_amount", 0),
        max_amount=request.data.get("max_amount"),
        percentage=request.data["percentage"])
    return success("Commission created successfully", _commission_payload(c), status=201)


@api_view(["PUT", "PATCH", "DELETE"])
@permission_classes([IsAdmin])
def commission_detail(request, id):
    if (resp := require_perms(request.user, ["manage_commissions"])):
        return resp
    c = Commission.objects.filter(id=id).first()
    if not c:
        return error("Commission not found", status=404)
    if request.method == "DELETE":
        c.delete()
        return success("Commission deleted successfully")
    for field in ["min_amount", "max_amount", "percentage"]:
        if request.data.get(field) is not None:
            setattr(c, field, request.data[field])
    c.save()
    return success("Commission updated successfully", _commission_payload(c))


# ─────────────────────────────────────────────────────────────────────────────
#  Service Fee Tier CRUD
# ─────────────────────────────────────────────────────────────────────────────
def _service_fee_tier_payload(t):
    return {"id": t.id, "min_amount": t.min_amount, "max_amount": t.max_amount,
            "fee_type": t.fee_type, "value": t.value, "created_at": t.created_at}


@api_view(["GET", "POST"])
@permission_classes([IsAdmin])
def service_fee_tiers_collection(request):
    if request.method == "GET":
        if (resp := require_perms(request.user, ["view_service_fees"])):
            return resp
        qs = ServiceFeeTier.objects.all().order_by("min_amount")
        return success("Service fee tiers retrieved", [_service_fee_tier_payload(t) for t in qs])
    if (resp := require_perms(request.user, ["manage_service_fees"])):
        return resp
    if request.data.get("value") is None:
        return error("value is required", status=422)
    if request.data.get("fee_type") not in (ServiceFeeTier.FLAT, ServiceFeeTier.PERCENTAGE):
        return error("fee_type must be 'flat' or 'percentage'", status=422)
    t = ServiceFeeTier.objects.create(
        min_amount=request.data.get("min_amount", 0),
        max_amount=request.data.get("max_amount"),
        fee_type=request.data["fee_type"],
        value=request.data["value"])
    return success("Service fee tier created successfully", _service_fee_tier_payload(t), status=201)


@api_view(["PUT", "PATCH", "DELETE"])
@permission_classes([IsAdmin])
def service_fee_tier_detail(request, id):
    if (resp := require_perms(request.user, ["manage_service_fees"])):
        return resp
    t = ServiceFeeTier.objects.filter(id=id).first()
    if not t:
        return error("Service fee tier not found", status=404)
    if request.method == "DELETE":
        t.delete()
        return success("Service fee tier deleted successfully")
    for field in ["min_amount", "max_amount", "fee_type", "value"]:
        if request.data.get(field) is not None:
            setattr(t, field, request.data[field])
    t.save()
    return success("Service fee tier updated successfully", _service_fee_tier_payload(t))
