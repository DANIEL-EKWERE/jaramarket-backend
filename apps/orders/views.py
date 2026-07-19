from rest_framework.decorators import api_view, permission_classes
from rest_framework.permissions import IsAuthenticated
from rest_framework import permissions

from api.utils import error, success
from api.services import OrderService
from apps.support.models import Setting
from .models import OrderItem
from .serializers import IngredientOrderSerializer, OrderSerializer, VendorOrderItemSerializer


def _paginate(request, qs, serializer_cls):
    from rest_framework.pagination import PageNumberPagination
    p = PageNumberPagination()
    p.page_size = int(request.query_params.get("per_page", 15))
    page = p.paginate_queryset(qs, request)
    data = serializer_cls(page, many=True, context={"request": request}).data
    return {"data": data, "current_page": p.page.number,
            "last_page": p.page.paginator.num_pages, "total": p.page.paginator.count,
            "per_page": p.page_size}


class IsVendor(permissions.BasePermission):
    message = "Vendor access required."

    def has_permission(self, request, view):
        return bool(request.user and request.user.is_authenticated and request.user.is_vendor())


class IsQAOrAdmin(permissions.BasePermission):
    message = "QA or admin access required."

    def has_permission(self, request, view):
        from apps.accounts.models import Roles
        user = request.user
        return bool(user and user.is_authenticated and
                    (user.role == Roles.QA or user.role in Roles.ADMIN_ROLES))


_svc = OrderService()


@api_view(["GET", "POST"])
def orders_collection(request):
    if request.method == "GET":
        return success("Orders retrieved successfully",
                       _paginate(request, _svc.all(request.user), OrderSerializer))
    try:
        order = _svc.create_order(request.user, request.data)
    except ValueError as e:
        return error(str(e), status=422)
    return success("Order created successfully", OrderSerializer(order).data, status=201)


@api_view(["GET", "PUT", "DELETE"])
def order_show(request, order):
    obj = _svc.get_order_by_id(request.user, order)
    if not obj:
        return error("Order not found", status=404)
    if request.method == "GET":
        return success("Order retrieved successfully", OrderSerializer(obj).data)
    if request.method == "PUT":
        ser = OrderSerializer(obj, data=request.data, partial=True)
        ser.is_valid(raise_exception=True)
        ser.save()
        return success("Order updated successfully", ser.data)
    # DELETE
    obj.delete()
    return success("Order deleted successfully")


@api_view(["POST"])
def order_cancel(request, order):
    obj = _svc.get_order_by_id(request.user, order)
    if not obj:
        return error("Order not found", status=404)
    return success("Order cancelled successfully", OrderSerializer(_svc.cancel_order(obj)).data)


@api_view(["POST"])
@permission_classes([IsAuthenticated, IsQAOrAdmin])
def order_mark_completed(request, order):
    try:
        obj = _svc.mark_completed(request.user, order)
    except ValueError as e:
        return error(str(e), status=422)
    return success("Order marked as completed", OrderSerializer(obj).data)


@api_view(["GET"])
@permission_classes([IsAuthenticated, IsVendor])
def vendor_available_orders(request):
    return success("Available orders retrieved successfully",
                   _paginate(request, _svc.available_orders(request.user), VendorOrderItemSerializer))


@api_view(["GET"])
@permission_classes([IsAuthenticated, IsVendor])
def vendor_my_orders(request):
    return success("Accepted orders retrieved successfully",
                   _paginate(request, _svc.my_orders(request.user), VendorOrderItemSerializer))


@api_view(["GET"])
@permission_classes([IsAuthenticated, IsVendor])
def vendor_order_item(request, item_id):
    item = _svc.show_item(item_id)
    if not item:
        return error("Order not found", status=404)
    return success("Order retrieved successfully", VendorOrderItemSerializer(item).data)


@api_view(["POST"])
@permission_classes([IsAuthenticated, IsVendor])
def vendor_decide(request, item_id):
    try:
        item = _svc.decide(request.user, item_id, request.data)
    except ValueError as e:
        return error(str(e), status=404)
    return success("Action taken successfully", VendorOrderItemSerializer(item).data)


@api_view(["POST"])
@permission_classes([IsAuthenticated, IsVendor])
def vendor_deliver(request, item_id):
    try:
        item = _svc.mark_delivered(request.user, item_id)
    except ValueError as e:
        return error(str(e), status=422)
    return success("Item marked as delivered", VendorOrderItemSerializer(item).data)


@api_view(["GET"])
@permission_classes([IsAuthenticated, IsVendor])
def vendor_dashboard(request):
    from django.db.models import Sum
    from django.utils import timezone
    from datetime import timedelta
    from apps.finance.models import Wallet

    period = request.query_params.get("period", "all")
    qs = OrderItem.objects.filter(vendor=request.user)

    if period == "week":
        qs = qs.filter(created_at__gte=timezone.now() - timedelta(days=7))
    elif period == "month":
        qs = qs.filter(created_at__gte=timezone.now() - timedelta(days=30))

    total_orders     = qs.count()
    pending_orders   = qs.filter(status="pending").count()
    completed_orders = qs.filter(status="completed").count()
    cancelled_orders = qs.filter(status="cancelled").count()
    total_revenue    = qs.filter(status="completed").aggregate(t=Sum("vendor_amount"))["t"] or 0

    wallet = Wallet.objects.filter(user=request.user).first()

    recent = qs.select_related("ingredient", "product", "order__user").order_by("-created_at")[:10]
    recent_data = [
        {
            "id": item.id,
            "status": item.status,
            "amount": str(item.amount),
            "created_at": item.created_at,
            "customer_name": item.order.user.name if item.order and item.order.user else None,
        }
        for item in recent
    ]

    return success("Vendor dashboard retrieved", {
        "period": period,
        "total_orders": total_orders,
        "pending_orders": pending_orders,
        "completed_orders": completed_orders,
        "cancelled_orders": cancelled_orders,
        "total_revenue": str(total_revenue),
        "wallet_balance": str(wallet.balance) if wallet else "0.00",
        "recent_orders": recent_data,
    })


@api_view(["GET", "POST"])
def settings_view(request):
    if request.method == "GET":
        return success("Settings retrieved", {s.key: s.value for s in Setting.objects.all()})
    for key, value in request.data.items():
        Setting.objects.update_or_create(key=key, defaults={"value": value})
    return success("Settings saved")
