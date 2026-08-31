from rest_framework import permissions
from rest_framework.decorators import api_view, permission_classes
from rest_framework.permissions import AllowAny, IsAuthenticated

from api.utils import error, success
from .models import Market, Vendor
from .serializers import MarketSerializer


@api_view(["GET"])
@permission_classes([AllowAny])
def markets_collection(request):
    markets = Market.objects.filter(is_active=True).order_by("name")
    state_id = request.query_params.get("state_id")
    if state_id:
        markets = markets.filter(state_id=state_id)
    lga_id = request.query_params.get("lga_id")
    if lga_id:
        markets = markets.filter(lga_id=lga_id)
    return success("Markets retrieved successfully", MarketSerializer(markets, many=True).data)


class IsVendor(permissions.BasePermission):
    message = "Vendor access required."

    def has_permission(self, request, view):
        return bool(request.user and request.user.is_authenticated and request.user.is_vendor())


@api_view(["GET", "POST", "PATCH"])
@permission_classes([IsAuthenticated, IsVendor])
def vendor_shop_profile(request):
    """The logged-in vendor's market and product categories.

    Onboarding sets both through email-keyed endpoints that accept anyone;
    editing them later has to be authenticated -- otherwise any caller could
    move another vendor's shop or change what they sell. This works off the
    token, so a vendor can only ever change their own.
    """
    from apps.catalogue.models import Category
    from apps.catalogue.serializers import CategorySerializer

    profile, _ = Vendor.objects.get_or_create(user=request.user)

    if request.method == "GET":
        return success("Vendor profile retrieved successfully",
                       _shop_payload(profile, MarketSerializer, CategorySerializer))

    # ── update ──
    changed = []
    if "market_id" in request.data:
        market_id = request.data.get("market_id")
        if market_id in (None, "", "null"):
            profile.market = None
        else:
            market = Market.objects.filter(id=market_id, is_active=True).first()
            if not market:
                return error("That market is not available.", status=422)
            profile.market = market
        profile.save(update_fields=["market"])
        changed.append("market")

    if "category_ids" in request.data:
        raw = request.data.get("category_ids")
        if isinstance(raw, str):
            # Multipart can only carry strings -- accept "1,2,3".
            raw = [part for part in raw.replace(" ", "").split(",") if part]
        try:
            ids = [int(v) for v in (raw or [])]
        except (TypeError, ValueError):
            return error("category_ids must be a list of ids.", status=422)
        if not ids:
            return error("Select at least one category — vendors are matched to "
                         "orders by the categories they carry.", status=422)
        categories = Category.objects.filter(id__in=ids)
        if categories.count() != len(set(ids)):
            return error("One or more categories no longer exist.", status=422)
        request.user.categories.set(categories)
        changed.append("categories")

    if not changed:
        return error("Nothing to update — send market_id and/or category_ids.",
                     status=422)

    profile.refresh_from_db()
    return success(f"{' and '.join(changed).capitalize()} updated successfully",
                   _shop_payload(profile, MarketSerializer, CategorySerializer))


def _shop_payload(profile, MarketSerializer, CategorySerializer):
    return {
        "market": MarketSerializer(profile.market).data if profile.market_id else None,
        "categories": CategorySerializer(profile.user.categories.all().order_by("name"),
                                         many=True).data,
    }
