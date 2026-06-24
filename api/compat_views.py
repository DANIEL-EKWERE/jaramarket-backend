"""
Client-compatibility shims — match the exact paths/methods the Flutter client
(`jara_vendor`) calls that differ from the canonical routes. These wrap the
existing canonical views/logic so behaviour is identical.
"""
from rest_framework.decorators import api_view, permission_classes
from rest_framework.permissions import AllowAny

from . import admin_views as av
from api.utils import error, success
from apps.accounts.models import User
from apps.catalogue.models import Category
from apps.catalogue.serializers import CategorySerializer
from apps.customers.models import Address
from apps.customers.serializers import AddressSerializer
from apps.accounts.serializers import auth_user_payload
from apps.orders.serializers import OrderSerializer


class _S:
    CategorySerializer = CategorySerializer
    AddressSerializer = AddressSerializer
    auth_user_payload = staticmethod(auth_user_payload)
    OrderSerializer = OrderSerializer


S = _S()


@api_view(["GET", "POST"])
def categories_root(request):
    """GET lists categories; POST creates (admin) — client hits /categories."""
    if request.method == "GET":
        return success("Categories retrieved",
                       S.CategorySerializer(Category.objects.all().order_by("sort_by"), many=True).data)
    return av.category_create(request._request if hasattr(request, "_request") else request)


@api_view(["GET"])
def fetch_user_profile_by_email(request, email):
    user = User.objects.filter(email=email).first()
    if not user:
        return error("User not found", status=404)
    return success("User Profile retrieved successfully", S.auth_user_payload(user))


@api_view(["GET", "PUT", "PATCH"])
def address_detail(request, address):
    obj = Address.objects.filter(id=address, user=request.user).first()
    if not obj:
        return error("Address not found", status=404)
    if request.method == "GET":
        return success("Address retrieved", S.AddressSerializer(obj).data)
    ser = S.AddressSerializer(obj, data=request.data, partial=True)
    ser.is_valid(raise_exception=True)
    ser.save()
    return success("Address updated", ser.data)


@api_view(["GET"])
@permission_classes([AllowAny])
def payment_callback(request):
    """Gateway redirect callback — verify by ?reference= / ?trxref=."""
    ref = (request.query_params.get("reference") or request.query_params.get("trxref")
           or request.query_params.get("tx_ref"))
    if not ref:
        return error("reference is required", status=422)
    from api.services import PaymentGateway
    return success("Transaction verified", PaymentGateway.resolve().verify_transaction(ref))


# ── Newly built endpoints referenced by the client ──
from django.utils import timezone as _tz
from apps.vendors.models import Franchise
from apps.orders.models import Order, OrderItem, OrderItemLog


@api_view(["GET", "POST"])
def franchises_collection(request):
    """GET lists franchises; POST creates one."""
    if request.method == "GET":
        qs = Franchise.objects.select_related("owner").all().order_by("name")
        data = [{"id": f.id, "name": f.name, "location": f.location,
                 "owner_id": f.owner_id, "owner_name": f.owner.name if f.owner else None,
                 "created_at": f.created_at} for f in qs]
        return success("Franchises retrieved", data)
    name = request.data.get("name")
    if not name or not request.data.get("location"):
        return error("name and location are required", status=422)
    f = Franchise.objects.create(name=name, location=request.data["location"],
                                 owner_id=request.data.get("owner_id") or request.user.id)
    return success("Franchise created successfully",
                   {"id": f.id, "name": f.name, "location": f.location, "owner_id": f.owner_id},
                   status=201)


def _order_owner_or_admin(request, order_id):
    order = Order.objects.filter(id=order_id).first()
    if not order:
        return None, error("Order not found", status=404)
    if order.user_id != request.user.id and not request.user.is_admin() \
            and not OrderItem.objects.filter(order=order, vendor=request.user).exists():
        return None, error("This action is unauthorized.", status=403)
    return order, None


@api_view(["GET"])
def order_receipt(request, order):
    obj, err = _order_owner_or_admin(request, order)
    if err:
        return err
    items = [{"id": it.id,
              "name": (it.ingredient.name if it.ingredient else (it.product.name if it.product else None)),
              "quantity": it.quantity, "unit": it.unit, "price": it.price, "amount": it.amount}
             for it in obj.items.select_related("product", "ingredient").all()]
    subtotal = sum((it.amount for it in obj.items.all()), 0)
    data = {
        "order_id": obj.id, "reference": obj.reference, "status": obj.status,
        "order_date": obj.order_date, "customer": obj.user.name if obj.user else None,
        "items": items,
        "summary": {"subtotal": subtotal, "shipping_fee": obj.shipping_fee,
                    "service_charge": obj.service_charge, "vat": obj.vat, "total": obj.total},
        "delivery_type": obj.delivery_type,
        "generated_at": _tz.now(),
    }
    return success("Order receipt retrieved", data)


@api_view(["GET"])
def order_track(request, order):
    obj, err = _order_owner_or_admin(request, order)
    if err:
        return err
    # Per-item status + a timeline assembled from order_item_logs
    items = []
    timeline = []
    for it in obj.items.select_related("vendor").all():
        items.append({"item_id": it.id, "status": it.status,
                      "vendor": it.vendor.name if it.vendor else None,
                      "vendor_at": it.vendor_at})
        for log in OrderItemLog.objects.filter(order_item=it).order_by("changed_at"):
            timeline.append({"item_id": it.id, "status": log.status, "at": log.changed_at})
    timeline.sort(key=lambda x: (x["at"] is None, x["at"]))
    data = {"order_id": obj.id, "reference": obj.reference, "status": obj.status,
            "items": items, "timeline": timeline}
    return success("Order tracking retrieved", data)


@api_view(["GET"])
def user_orders(request, user_id):
    """Orders for a specific user (admin, or the user themselves)."""
    if request.user.id != int(user_id) and not request.user.is_admin():
        return error("This action is unauthorized.", status=403)
    qs = Order.objects.filter(user_id=user_id).order_by("-created_at")
    return success("User orders retrieved", S.OrderSerializer(qs, many=True).data)
