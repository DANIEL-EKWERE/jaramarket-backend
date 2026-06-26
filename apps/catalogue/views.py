from rest_framework.decorators import api_view, permission_classes
from rest_framework.permissions import AllowAny

from api.utils import error, success
from api.services import UserRegistrationService
from apps.geo.serializers import StateSerializer
from apps.support.models import Advertisement
from .models import Category, Ingredient, Product, Uom
from .serializers import (
    AdvertisementSerializer, CategorySerializer,
    IngredientSerializer, ProductSerializer, UomSerializer,
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


@api_view(["GET"])
@permission_classes([AllowAny])
def vendor_categories(request):
    return success("Vendor categories retrieved", CategorySerializer(Category.objects.all(), many=True).data)


@api_view(["GET"])
def categories_all_products(request):
    out = [{**CategorySerializer(cat).data,
            "products": ProductSerializer(cat.products.all(), many=True).data}
           for cat in Category.objects.all().order_by("sort_by")]
    return success("Categories with products retrieved", out)


@api_view(["GET"])
def categories_limit_products(request):
    limit = int(request.query_params.get("limit", 5))
    out = [{**CategorySerializer(cat).data,
            "products": ProductSerializer(cat.products.all()[:limit], many=True).data}
           for cat in Category.objects.all().order_by("sort_by")]
    return success("Categories with limited products retrieved", out)


@api_view(["GET"])
def fetch_ingredients(request):
    qs = Ingredient.objects.all()
    search = request.query_params.get("search")
    if search:
        qs = qs.filter(name__icontains=search)
    return success("Ingredients retrieved", _paginate(request, qs, IngredientSerializer))


@api_view(["GET"])
def fetch_product(request):
    qs = Product.objects.all()
    search = request.query_params.get("search")
    if search:
        qs = qs.filter(name__icontains=search)
    return success("Products retrieved", _paginate(request, qs, ProductSerializer))


@api_view(["GET"])
def fetch_uom(request):
    return success("UOMs retrieved", UomSerializer(Uom.objects.all(), many=True).data)


@api_view(["GET"])
def get_product_by_id(request, id):
    obj = Product.objects.filter(id=id).first()
    return success("Product retrieved", ProductSerializer(obj).data) if obj else error("Product not found", status=404)


@api_view(["GET"])
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
                 "stock", "preparation_steps", "image_url"]
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
