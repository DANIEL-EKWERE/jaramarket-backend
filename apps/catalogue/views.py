from collections import defaultdict
from decimal import Decimal, InvalidOperation

from django.db.models import Prefetch
from rest_framework.decorators import api_view, permission_classes
from rest_framework.permissions import AllowAny

from api.utils import error, success
from api.services import UserRegistrationService
from apps.geo.serializers import StateSerializer
from apps.support.models import Advertisement
from .models import (
    Category, CategoryProduct, Ingredient, IngredientLgaPrice,
    IngredientProduct, IngredientStatePrice, Product, ProductStatePrice, Uom,
)
from .serializers import (
    AdvertisementSerializer, CategorySerializer,
    IngredientSerializer, ProductSerializer, UomSerializer, _full_image_url,
)


def _paginate(request, qs, serializer_cls):
    from rest_framework.pagination import PageNumberPagination
    p = PageNumberPagination()
    p.page_size = int(request.query_params.get("per_page", 15))
    page = p.paginate_queryset(qs, request)
    data = serializer_cls(page, many=True, context={"request": request}).data
    return {"data": data, "current_page": p.page.number,
            "last_page": p.page.paginator.num_pages, "total": p.page.paginator.count,
            "per_page": p.page_size}


# Mirrors App\Enums\CategoryTypeEnum in the PHP source (FOOD=1, VENDOR=2,
# seeded identically everywhere). Vendor-type categories are the ones a
# vendor picks during onboarding, shared with Ingredients; Food-type
# categories are the jara-user app's recipe/product listings. Each endpoint
# below must only ever offer the type it actually owns.
FOOD_CATEGORY_TYPE_ID = 1
VENDOR_CATEGORY_TYPE_ID = 2


@api_view(["GET"])
@permission_classes([AllowAny])
def vendor_categories(request):
    cats = (Category.objects.filter(category_type_id=VENDOR_CATEGORY_TYPE_ID)
            .order_by("sort_by").prefetch_related("ingredients"))
    out = [{
        **CategorySerializer(cat).data,
        "ingredients": [{
            "id": ing.id, "name": ing.name, "description": ing.description,
            "price": str(ing.price),
            "discount_price": str(ing.discounted_price) if ing.discounted_price is not None else None,
            "unit": ing.unit, "stock": ing.stock,
            "image_url": _full_image_url(ing.image_url) or "",
            "created_at": ing.created_at.isoformat() if ing.created_at else None,
        } for ing in cat.ingredients.all()],
    } for cat in cats]
    return success("Vendor categories retrieved", out)


@api_view(["GET"])
@permission_classes([AllowAny])
def categories_all_products(request):
    lga_id   = request.query_params.get("lga_id")
    state_id = request.query_params.get("state_id")
    market_id = request.query_params.get("market_id")
    page     = int(request.query_params.get("page", 1))
    per_page = int(request.query_params.get("per_page", 5))
    min_price = request.query_params.get("min_price")
    max_price = request.query_params.get("max_price")

    qs    = Category.objects.filter(category_type_id=FOOD_CATEGORY_TYPE_ID).order_by("sort_by")
    total = qs.count()
    start = (page - 1) * per_page
    cats  = list(qs[start: start + per_page])
    cat_ids = [c.id for c in cats]

    # Build filtered prefetch querysets for location pricing (one DB hit each)
    prod_state_qs = (ProductStatePrice.objects.filter(state_id=state_id)
                     if state_id else ProductStatePrice.objects.none())
    ing_state_qs  = (IngredientStatePrice.objects.filter(state_id=state_id)
                     if state_id else IngredientStatePrice.objects.none())
    ing_lga_qs    = (IngredientLgaPrice.objects.filter(lga_id=lga_id)
                     if lga_id else IngredientLgaPrice.objects.none())

    # Fetch ALL products for this page of categories in one query with all prefetches
    products_qs = Product.objects.filter(categories__id__in=cat_ids, is_active=True)
    if state_id:
        products_qs = products_qs.exclude(state_suspensions__state_id=state_id)
    if lga_id:
        products_qs = products_qs.exclude(lga_suspensions__lga_id=lga_id)
    if market_id:
        products_qs = products_qs.exclude(market_suspensions__market_id=market_id)
    products_qs = (
        products_qs
        .prefetch_related(
            Prefetch("state_prices", queryset=prod_state_qs, to_attr="_loc_state_prices"),
            Prefetch(
                "ingredientproduct_set",
                queryset=IngredientProduct.objects.select_related("ingredient").prefetch_related(
                    Prefetch("ingredient__state_prices", queryset=ing_state_qs, to_attr="_loc_state_prices"),
                    Prefetch("ingredient__lga_prices",   queryset=ing_lga_qs,   to_attr="_loc_lga_prices"),
                ),
            ),
        )
        .distinct()
    )
    prod_by_id = {p.id: p for p in products_qs}

    # Map category → products using CategoryProduct (one query)
    cat_product_map = defaultdict(list)
    for cp in CategoryProduct.objects.filter(category_id__in=cat_ids, product_id__in=prod_by_id):
        cat_product_map[cp.category_id].append(prod_by_id[cp.product_id])

    def _prod_price(prod):
        prices = getattr(prod, "_loc_state_prices", [])
        if prices:
            return {"price": prices[0].price, "discount_price": prices[0].discount_price, "price_source": "state"}
        return {"price": prod.price, "discount_price": prod.discount_price, "price_source": "default"}

    def _ing_price(ing):
        for p in getattr(ing, "_loc_lga_prices", []):
            return {"price": p.price, "discounted_price": p.discounted_price, "price_source": "lga"}
        for p in getattr(ing, "_loc_state_prices", []):
            return {"price": p.price, "discounted_price": p.discounted_price, "price_source": "state"}
        return {"price": ing.price, "discounted_price": ing.discounted_price, "price_source": "default"}

    def _resolve_product(prod):
        loc = _prod_price(prod)
        ingr_data = []
        for ip in prod.ingredientproduct_set.all():
            ing  = ip.ingredient
            iloc = _ing_price(ing)
            ingr_data.append({
                "id": ing.id, "name": ing.name, "category_id": ing.category_id,
                "unit": ing.unit, "stock": ing.stock,
                "image_url": _full_image_url(ing.image_url),
                "price": str(iloc["price"]),
                "discounted_price": str(iloc["discounted_price"]) if iloc["discounted_price"] else None,
                "is_state_price": iloc["price_source"] != "default",
                "quantity": str(ip.quantity) if ip.quantity else None,
                "serving_unit": ip.unit,
            })
        return {
            "id": prod.id, "name": prod.name, "description": prod.description,
            "price": str(loc["price"]),
            "discount_price": str(loc["discount_price"]) if loc["discount_price"] else None,
            "is_state_price": loc["price_source"] != "default",
            "stock": prod.stock, "image_url": _full_image_url(prod.image_url),
            "rating": prod.rating, "preparation_steps": prod.preparation_steps,
            "youtube_url": prod.youtube_url,
            "ingredients": ingr_data,
            "created_at": prod.created_at.isoformat() if prod.created_at else None,
        }

    out = []
    for cat in cats:
        products = [_resolve_product(p) for p in cat_product_map.get(cat.id, [])]
        if min_price or max_price:
            products = [p for p in products if _price_in_range(Decimal(p["price"]), min_price, max_price)]
        out.append({**CategorySerializer(cat).data, "products": products})

    last_page = (total + per_page - 1) // per_page
    return success("Categories with products retrieved", {
        "data": out,
        "current_page": page,
        "last_page": last_page,
        "total": total,
        "per_page": per_page,
    })


@api_view(["GET"])
def categories_limit_products(request):
    limit = int(request.query_params.get("limit", 5))
    cats = list(Category.objects.filter(category_type_id=FOOD_CATEGORY_TYPE_ID).order_by("sort_by"))
    cat_ids = [c.id for c in cats]

    # Fetch all category→product links with ingredients prefetched (a few queries total)
    cp_qs = (CategoryProduct.objects
             .filter(category_id__in=cat_ids, product__is_active=True)
             .select_related("product")
             .prefetch_related(
                 Prefetch("product__ingredientproduct_set",
                          queryset=IngredientProduct.objects.select_related("ingredient"))
             ))
    cat_product_map = defaultdict(list)
    for cp in cp_qs:
        bucket = cat_product_map[cp.category_id]
        if len(bucket) < limit:
            bucket.append(cp.product)

    out = [{**CategorySerializer(cat).data,
            "products": ProductSerializer(cat_product_map.get(cat.id, []), many=True).data}
           for cat in cats]
    return success("Categories with limited products retrieved", out)


def _exclude_suspended(qs, state_id, lga_id, state_field, lga_field, market_id=None, market_field=None):
    """Drop items suspended in the caller's state/LGA/market -- the item
    stays globally is_active=True, just hidden for these specific locations."""
    if state_id:
        qs = qs.exclude(**{f"{state_field}__state_id": state_id})
    if lga_id:
        qs = qs.exclude(**{f"{lga_field}__lga_id": lga_id})
    if market_id and market_field:
        qs = qs.exclude(**{f"{market_field}__market_id": market_id})
    return qs


def _filter_by_price(qs, request, field="price"):
    """Applies ?min_price=/?max_price= to a Product/Ingredient queryset,
    same query-param names on both endpoints."""
    min_price = request.query_params.get("min_price")
    max_price = request.query_params.get("max_price")
    if min_price:
        qs = qs.filter(**{f"{field}__gte": min_price})
    if max_price:
        qs = qs.filter(**{f"{field}__lte": max_price})
    return qs


def _price_in_range(price, min_price, max_price):
    """Same min_price/max_price semantics as _filter_by_price, but applied to
    an already-resolved price (categories_all_products picks the effective
    state/lga-override price in Python, so it can't be filtered in the DB)."""
    try:
        if min_price and price < Decimal(min_price):
            return False
        if max_price and price > Decimal(max_price):
            return False
    except InvalidOperation:
        pass
    return True


@api_view(["GET"])
def fetch_ingredients(request):
    qs = Ingredient.objects.filter(is_active=True).order_by("name")
    search = request.query_params.get("search")
    if search:
        qs = qs.filter(name__icontains=search)
    qs = _filter_by_price(qs, request)
    qs = _exclude_suspended(qs, request.query_params.get("state_id"), request.query_params.get("lga_id"),
                            "state_suspensions", "lga_suspensions",
                            request.query_params.get("market_id"), "market_suspensions")
    return success("Ingredients retrieved", _paginate(request, qs, IngredientSerializer))


@api_view(["GET"])
def fetch_product(request):
    qs = Product.objects.filter(is_active=True)
    search = request.query_params.get("search")
    if search:
        qs = qs.filter(name__icontains=search)
    qs = _filter_by_price(qs, request)
    qs = _exclude_suspended(qs, request.query_params.get("state_id"), request.query_params.get("lga_id"),
                            "state_suspensions", "lga_suspensions",
                            request.query_params.get("market_id"), "market_suspensions")
    return success("Products retrieved", _paginate(request, qs, ProductSerializer))


@api_view(["GET"])
def fetch_uom(request):
    return success("UOMs retrieved", UomSerializer(Uom.objects.all(), many=True).data)


@api_view(["GET"])
def get_product_by_id(request, id):
    obj = Product.objects.filter(id=id, is_active=True).first()
    if obj and obj.is_suspended_in(request.query_params.get("state_id"), request.query_params.get("lga_id"),
                                   request.query_params.get("market_id")):
        obj = None
    return success("Product retrieved", ProductSerializer(obj, context={"request": request}).data) if obj else error("Product not found", status=404)


@api_view(["GET"])
@permission_classes([AllowAny])
def fetch_adverts(request):
    return success("Advertisements retrieved",
                   AdvertisementSerializer(Advertisement.objects.filter(status="active"), many=True).data)


def _apply_ingredients(product, ingredients_data):
    """Replace the product's ingredients with the supplied list."""
    from .models import IngredientProduct
    product.ingredientproduct_set.all().delete()
    for ing in ingredients_data:
        ingredient_id = ing.get("ingredient_id")
        if not ingredient_id:
            continue
        IngredientProduct.objects.create(
            product=product,
            ingredient_id=ingredient_id,
            quantity=ing.get("quantity"),
            unit=ing.get("unit"),
        )


@api_view(["POST"])
def foods_store(request):
    from .models import CategoryProduct
    name = request.data.get("name")
    if not name:
        return error("name is required", status=422)
    product = Product.objects.create(
        name=name,
        description=request.data.get("description"),
        price=request.data.get("price", 0),
        discount_price=request.data.get("discount_price"),
        stock=request.data.get("stock", 0),
        preparation_steps=request.data.get("preparation_steps"),
        image_url=request.data.get("image_url"),
        youtube_url=request.data.get("youtube_url"),
    )
    for cid in request.data.get("category_ids", []):
        CategoryProduct.objects.get_or_create(product=product, category_id=cid)
    _apply_ingredients(product, request.data.get("ingredients", []))
    return success("Food created successfully", ProductSerializer(product).data, status=201)


@api_view(["PUT", "PATCH"])
def foods_update(request, id):
    from .models import CategoryProduct
    product = Product.objects.filter(id=id).first()
    if not product:
        return error("Product not found", status=404)

    updatable = ["name", "description", "price", "discount_price",
                 "stock", "preparation_steps", "image_url", "youtube_url"]
    for field in updatable:
        if field in request.data:
            setattr(product, field, request.data[field])
    product.save()

    if "category_ids" in request.data:
        product.categoryproduct_set.all().delete()
        for cid in request.data["category_ids"]:
            CategoryProduct.objects.get_or_create(product=product, category_id=cid)

    if "ingredients" in request.data:
        _apply_ingredients(product, request.data["ingredients"])

    return success("Food updated successfully", ProductSerializer(product).data)
