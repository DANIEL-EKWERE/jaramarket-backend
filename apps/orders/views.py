from rest_framework.decorators import api_view, permission_classes
from rest_framework.permissions import IsAuthenticated
from rest_framework import permissions

from api.utils import error, success
from api.services import OrderService
from apps.support.models import Setting
from .serializers import IngredientOrderSerializer, OrderSerializer


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
                   _paginate(request, _svc.available_orders(request.user), IngredientOrderSerializer))


@api_view(["GET"])
@permission_classes([IsAuthenticated, IsVendor])
def vendor_my_orders(request):
    return success("Accepted orders retrieved successfully",
                   _paginate(request, _svc.my_orders(request.user), IngredientOrderSerializer))


@api_view(["GET"])
@permission_classes([IsAuthenticated, IsVendor])
def vendor_order_item(request, item_id):
    item = _svc.show_item(item_id)
    return success("Order retrieved successfully", IngredientOrderSerializer(item).data) if item else error("Order not found", status=404)


@api_view(["POST"])
@permission_classes([IsAuthenticated, IsVendor])
def vendor_decide(request, item_id):
    try:
        item = _svc.decide(request.user, item_id, request.data)
    except ValueError as e:
        return error(str(e), status=404)
    return success("Action taken successfully", IngredientOrderSerializer(item).data)


@api_view(["GET", "POST"])
def settings_view(request):
    if request.method == "GET":
        return success("Settings retrieved", {s.key: s.value for s in Setting.objects.all()})
    for key, value in request.data.items():
        Setting.objects.update_or_create(key=key, defaults={"value": value})
    return success("Settings saved")
