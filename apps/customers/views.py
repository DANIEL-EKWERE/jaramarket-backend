from decimal import Decimal

from rest_framework.decorators import api_view

from api.utils import error, success
from apps.catalogue.models import Product
from .models import Address, Cart, CartItem, Favorite
from .serializers import AddressSerializer, FavoriteSerializer
from apps.catalogue.serializers import ProductSerializer


@api_view(["GET", "POST"])
def favorites_collection(request):
    if request.method == "GET":
        return success("Favorites retrieved",
                       FavoriteSerializer(Favorite.objects.filter(user=request.user), many=True).data)
    product_id = request.data.get("product_id")
    if not product_id:
        return error("product_id is required", status=422)
    fav, created = Favorite.objects.get_or_create(user=request.user, product_id=product_id)
    return success("Added to favorites", FavoriteSerializer(fav).data, status=201 if created else 200)


@api_view(["DELETE"])
def favorite_delete(request, id):
    Favorite.objects.filter(id=id, user=request.user).delete()
    return success("Removed from favorites")


@api_view(["GET", "POST"])
def addresses_collection(request):
    if request.method == "GET":
        return success("Addresses retrieved",
                       AddressSerializer(Address.objects.filter(user=request.user), many=True).data)
    ser = AddressSerializer(data=request.data)
    ser.is_valid(raise_exception=True)
    addr = ser.save(user=request.user)
    return success("Address created", AddressSerializer(addr).data, status=201)


def _serialize_cart(cart, request):
    """Prices each line against the caller's state_id (same resolution
    ProductSerializer/get_price_for_location use elsewhere) instead of the
    product's base price, so a cart total matches what the customer was
    actually shown while browsing in their location."""
    state_id = request.query_params.get("state_id")
    items, total = [], Decimal("0")
    for it in cart.items.select_related("product").all():
        unit_price = Decimal(str(it.product.get_price_for_location(state_id=state_id)["price"]))
        line_total = unit_price * it.quantity
        total += line_total
        items.append({"id": it.id, "product": ProductSerializer(it.product, context={"request": request}).data,
                      "quantity": it.quantity, "line_total": line_total})
    return {"id": cart.id, "user_id": cart.user_id, "items": items, "total": total}


@api_view(["GET", "POST"])
def cart_collection(request):
    if request.method == "GET":
        carts = Cart.objects.filter(user=request.user)
        return success("success", [_serialize_cart(c, request) for c in carts])
    product_id = request.data.get("product_id")
    quantity = int(request.data.get("quantity", 1))
    if not product_id or quantity < 1:
        return error("product_id and quantity (>=1) are required", status=422)
    if not Product.objects.filter(id=product_id).exists():
        return error("Product not found", status=404)
    cart, _ = Cart.objects.get_or_create(user=request.user)
    item = CartItem.objects.filter(cart=cart, product_id=product_id).first()
    if item:
        item.quantity += quantity
        item.save(update_fields=["quantity"])
    else:
        CartItem.objects.create(cart=cart, product_id=product_id, quantity=quantity)
    return success("Product added to cart successfully", _serialize_cart(cart, request), status=201)


@api_view(["GET"])
def cart_show(request, id):
    cart = Cart.objects.filter(id=id, user=request.user).first()
    if not cart:
        return error("Cart not found", status=404)
    return success("success", _serialize_cart(cart, request))


@api_view(["PUT", "PATCH"])
def cart_update_item(request, id):
    cart = Cart.objects.filter(id=id, user=request.user).first()
    if not cart:
        return error("Cart not found", status=404)
    item = CartItem.objects.filter(cart=cart, id=request.data.get("item_id")).first()
    if not item:
        return error("Cart item not found", status=404)
    quantity = int(request.data.get("quantity", 1))
    if quantity < 1:
        return error("quantity must be >= 1", status=422)
    item.quantity = quantity
    item.save(update_fields=["quantity"])
    return success("Cart item updated successfully", _serialize_cart(cart, request))


@api_view(["DELETE"])
def cart_remove_item(request, id):
    cart = Cart.objects.filter(id=id, user=request.user).first()
    if not cart:
        return error("Cart not found", status=404)
    CartItem.objects.filter(cart=cart, id=request.data.get("item_id")).delete()
    return success("Cart item removed successfully")
