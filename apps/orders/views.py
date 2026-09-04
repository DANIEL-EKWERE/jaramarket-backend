import json

from rest_framework.decorators import api_view, permission_classes
from rest_framework.permissions import IsAuthenticated
from rest_framework import permissions

from api.utils import error, success
from api.services import OrderService
from apps.support.models import Setting
from .models import OrderItem
from .serializers import IngredientOrderSerializer, OrderSerializer, VendorOrderItemSerializer


def _normalize_multipart_lists(data):
    """A regular JSON order-creation POST already has real lists for
    `products`/`ingredients`. But a multipart POST (used when a voice note
    file is attached) can only carry plain string fields, so the client
    JSON-encodes those two fields as strings in that case -- decode them
    back here so the rest of create_order sees the same shape either way."""
    normalized = data.copy() if hasattr(data, "copy") else dict(data)
    for key in ("products", "ingredients"):
        value = normalized.get(key)
        if isinstance(value, str):
            try:
                normalized[key] = json.loads(value)
            except ValueError:
                pass
    return normalized


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
        data = _normalize_multipart_lists(request.data)
        order = _svc.create_order(request.user, data, audio_file=request.FILES.get("audio"))
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
def order_item_replacements(request, item_id):
    """Alternatives the customer can actually be served for an unavailable
    item -- filtered to categories a vendor near their address covers."""
    from apps.catalogue.serializers import IngredientSerializer
    try:
        item, options = _svc.replacement_options(request.user, item_id)
    except ValueError as e:
        return error(str(e), status=422)
    return success("Replacement options retrieved", {
        "order_item_id": item.id,
        "current": {
            "ingredient_id": item.ingredient_id,
            "name": item.ingredient.name if item.ingredient_id else None,
            "quantity": item.quantity,
            "amount": str(item.amount),
        },
        "options": IngredientSerializer(options, many=True,
                                        context={"request": request}).data,
    })


@api_view(["POST"])
def order_item_replace(request, item_id):
    """Swap an unavailable item for one the customer chose. Any price
    difference is settled against their wallet rather than refunded."""
    ingredient_id = request.data.get("ingredient_id")
    if not ingredient_id:
        return error("ingredient_id is required", status=422)
    try:
        item, difference = _svc.replace_item(
            request.user, item_id, ingredient_id, request.data.get("quantity"))
    except ValueError as e:
        return error(str(e), status=422)
    return success("Item replaced successfully", {
        "order": OrderSerializer(item.order).data,
        "price_difference": str(difference),
    })


@api_view(["POST"])
def order_item_forgo(request, item_id):
    """Drop an unavailable item and refund it."""
    try:
        item, refund, order = _svc.forgo_item(request.user, item_id)
    except ValueError as e:
        return error(str(e), status=422)
    return success(f"Item removed — ₦{refund:,.2f} refunded to your wallet.", {
        "order": OrderSerializer(order).data,
        "refund": str(refund),
    })


@api_view(["POST"])
def order_mark_received(request, order):
    """Customer confirms the order actually reached them — the final step
    after admin approval has paid vendors and released it to logistics."""
    try:
        obj = _svc.mark_received(request.user, order)
    except ValueError as e:
        return error(str(e), status=422)
    return success("Order marked as received", OrderSerializer(obj).data)


@api_view(["GET"])
def order_reorder(request, order):
    """Rebuild a past order as a fresh cart payload.

    The app's cart is keyed on products and standalone ingredients, but a
    food order is stored exploded into one row per recipe ingredient scaled
    by how many of the dish were bought -- so the ordered quantity of the
    dish itself is only recoverable against the recipe, which lives here.
    Resolving it server-side also means the customer gets TODAY's prices and
    today's location rules rather than a replay of what they paid before.

    Read-only: it returns what to put in the cart and lets the customer
    review and check out, rather than silently charging the wallet again.
    """
    from apps.catalogue.serializers import IngredientSerializer, ProductSerializer
    from apps.catalogue.models import IngredientProduct

    obj = _svc.get_order_by_id(request.user, order)
    if not obj:
        return error("Order not found", status=404)

    state_id = request.query_params.get("state_id")
    lga_id = request.query_params.get("lga_id")
    # Default to where the original order was delivered, so a reorder is
    # priced for the same place unless the app says otherwise.
    if state_id is None and obj.address_id:
        state_id = obj.address.state_id
        lga_id = obj.address.lga_id
    context = {"request": request, "state_id": state_id, "lga_id": lga_id}

    products, ingredients, unavailable = {}, {}, []

    def _drop(name):
        if name and name not in unavailable:
            unavailable.append(name)

    product_rows_by_id = {}
    for item in obj.items.select_related("product", "ingredient").all():
        if item.product_id:
            product = item.product
            if not product.is_active or product.is_suspended_in(
                    state_id=state_id, lga_id=lga_id):
                _drop(product.name)
                continue
            # Every recipe row of a dish carries the same ordered quantity,
            # so collect them all and decide from the set -- one row alone
            # can be missing its recipe link and give the wrong answer.
            products[product.id] = (product, None)
            product_rows_by_id.setdefault(product.id, []).append(item)
        elif item.ingredient_id:
            ingredient = item.ingredient
            if not ingredient.is_active or ingredient.is_suspended_in(
                    state_id=state_id, lga_id=lga_id):
                _drop(ingredient.name)
                continue
            if ingredient.id in ingredients:
                continue
            ingredients[ingredient.id] = (ingredient, max(1, int(item.quantity or 1)))

    products = {pid: (product, _ordered_quantity(product_rows_by_id[pid], IngredientProduct))
                for pid, (product, _) in products.items()}

    product_rows = []
    for product, quantity in products.values():
        row = ProductSerializer(product, context=context).data
        row["order_quantity"] = quantity
        product_rows.append(row)

    ingredient_rows = []
    for ingredient, quantity in ingredients.values():
        row = IngredientSerializer(ingredient, context=context).data
        row["order_quantity"] = quantity
        ingredient_rows.append(row)

    if not product_rows and not ingredient_rows:
        return error("None of the items on this order can be ordered again "
                     "right now.", status=422)

    return success("Reorder items retrieved successfully", {
        "order_id": obj.id,
        "reference": obj.reference,
        "products": product_rows,
        "ingredients": ingredient_rows,
        # Named so the app can tell the customer what it could not bring back
        # instead of quietly handing them a shorter cart.
        "unavailable": unavailable,
    })


def _ordered_quantity(items, IngredientProduct):
    """How many of the dish this set of exploded recipe rows represents.

    _save_food writes quantity = recipe_quantity * ordered_quantity, so
    dividing by the recipe quantity recovers the original. The default of 1
    for a blank recipe quantity mirrors _recipe_lines (`link.quantity or 1`),
    which is what wrote these rows in the first place.

    The recipe can have been edited since the order was placed, so rows are
    read as a set and the most common answer wins rather than trusting
    whichever row happened to come first -- a single row whose link has since
    been deleted would otherwise decide it.
    """
    votes = {}
    for item in items:
        row_quantity = float(item.quantity or 1)
        if not item.ingredient_id:
            # Not an exploded recipe row -- already the dish count.
            votes[max(1, round(row_quantity))] = votes.get(max(1, round(row_quantity)), 0) + 1
            continue
        link = IngredientProduct.objects.filter(
            product_id=item.product_id, ingredient_id=item.ingredient_id).first()
        if link is None:
            continue  # recipe row is gone; it can't tell us anything
        recipe_quantity = float(link.quantity) if link.quantity else 1.0
        if recipe_quantity <= 0:
            continue
        derived = max(1, round(row_quantity / recipe_quantity))
        votes[derived] = votes.get(derived, 0) + 1
    if not votes:
        return 1
    # Most agreement wins; the larger quantity breaks a tie.
    return max(votes, key=lambda q: (votes[q], q))


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
