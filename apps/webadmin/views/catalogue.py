"""Mirrors the catalogue CRUD (categories/products/ingredients/advertisements)
in api/admin_views.py — same fields, same permission slugs."""
from decimal import Decimal

from django.contrib import messages
from django.core.paginator import Paginator
from django.shortcuts import get_object_or_404, redirect, render
from django.views.decorators.http import require_POST

from apps.catalogue.models import (
    Category, CategoryProduct, CategoryType, Ingredient, IngredientLgaPrice,
    IngredientStatePrice, Product, ProductStatePrice,
)
from apps.geo.models import Lga, State
from apps.support.models import Advertisement
from ..decorators import admin_required, perm_required


def _extract_price_rows(request, prefix):
    """Reconstruct dynamic add/remove price-override rows from parallel
    getlist()s (one value per row, in DOM order) -- the Django-idiomatic
    equivalent of Laravel's state_prices[i][field] bracket-array form
    fields used by the PHP admin's product/ingredient edit forms."""
    ids = request.POST.getlist(f"{prefix}_id")
    prices = request.POST.getlist(f"{prefix}_price")
    discounts = request.POST.getlist(f"{prefix}_discount")
    return list(zip(ids, prices, discounts))


def _sync_price_overrides(manager, fk_field, discount_field, rows):
    """Mirrors FoodService::syncStatePrices in the PHP admin: delete any
    existing override row whose FK isn't in the incoming set, then
    update_or_create each incoming row keyed by that FK."""
    incoming = {}
    for fk_id, price, discount in rows:
        if not fk_id or price in (None, ""):
            continue
        incoming[int(fk_id)] = {"price": price, discount_field: discount or None}
    manager.exclude(**{f"{fk_field}_id__in": incoming.keys()}).delete()
    for fk_id, data in incoming.items():
        manager.update_or_create(**{f"{fk_field}_id": fk_id}, defaults=data)


def _save_uploaded_image(uploaded_file, subfolder):
    """Upload an image to S3 and return the relative key (e.g.
    "products/<uuid>.jpg") -- the same relative-path convention every
    pre-existing product/ingredient image already uses, so
    ProductSerializer._full_image_url() prepends the S3 bucket URL correctly
    on read (mirrors how those pre-existing images actually got there).

    Falls back to local disk only when AWS credentials aren't configured
    (i.e. local dev) -- writing to local disk in production doesn't survive
    Render's ephemeral filesystem across deploys/instances, which is exactly
    what caused newly-uploaded images to 404 after the first request.
    """
    import os
    import uuid
    from django.conf import settings

    ext = os.path.splitext(uploaded_file.name)[1]
    key = f"{subfolder}/{uuid.uuid4().hex}{ext}"

    if settings.AWS_STORAGE_BUCKET_NAME and settings.AWS_ACCESS_KEY_ID:
        import boto3
        s3 = boto3.client(
            "s3",
            aws_access_key_id=settings.AWS_ACCESS_KEY_ID,
            aws_secret_access_key=settings.AWS_SECRET_ACCESS_KEY,
            region_name=settings.AWS_DEFAULT_REGION,
        )
        uploaded_file.seek(0)
        s3.upload_fileobj(
            uploaded_file, settings.AWS_STORAGE_BUCKET_NAME, key,
            ExtraArgs={"ContentType": uploaded_file.content_type or "application/octet-stream"},
        )
        return key

    # Local dev fallback (no AWS_* configured): write to MEDIA_ROOT and
    # return an absolute URL so _full_image_url() doesn't re-prefix it.
    upload_dir = os.path.join(settings.MEDIA_ROOT, subfolder)
    os.makedirs(upload_dir, exist_ok=True)
    filename = os.path.basename(key)
    with open(os.path.join(upload_dir, filename), "wb") as f:
        for chunk in uploaded_file.chunks():
            f.write(chunk)
    return f"{settings.APP_URL}{settings.MEDIA_URL}{subfolder}/{filename}"

# Mirrors App\Enums\CategoryTypeEnum in the PHP admin (FOOD=1, VENDOR=2), seeded
# identically everywhere by CategoryTypeSeeder / apps/catalogue migrations.
# Food-type categories are the ones shown on the jara-user app and attached to
# Products; Vendor-type categories are the ones a vendor picks during
# onboarding and are shared with Ingredients. They must never be cross-offered.
FOOD_CATEGORY_TYPE_ID = 1
VENDOR_CATEGORY_TYPE_ID = 2

# ── Categories ──────────────────────────────────────────────────────────────

@perm_required("view_categories")
def categories_list_view(request):
    qs = Category.objects.all().order_by("sort_by", "name")
    paginator = Paginator(qs, request.GET.get("per_page", 20))
    return render(request, "webadmin/catalogue/categories/list.html",
                  {"page": paginator.get_page(request.GET.get("page"))})


@perm_required("manage_categories")
def category_create_view(request):
    if request.method == "POST":
        if not request.POST.get("name"):
            messages.error(request, "Name is required.")
        else:
            Category.objects.create(
                name=request.POST["name"], description=request.POST.get("description"),
                category_type_id=request.POST.get("category_type_id") or 1,
                sort_by=request.POST.get("sort_by") or 100)
            messages.success(request, "Category created successfully.")
            return redirect("webadmin:categories_list")
    return render(request, "webadmin/catalogue/categories/form.html", {
        "category": None, "category_types": CategoryType.objects.all()})


@perm_required("manage_categories")
def category_update_view(request, id):
    cat = get_object_or_404(Category, id=id)
    if request.method == "POST":
        for field in ["name", "description"]:
            if request.POST.get(field) is not None:
                setattr(cat, field, request.POST[field])
        if request.POST.get("category_type_id"):
            cat.category_type_id = request.POST["category_type_id"]
        if request.POST.get("sort_by"):
            cat.sort_by = request.POST["sort_by"]
        cat.save()
        messages.success(request, "Category updated successfully.")
        return redirect("webadmin:categories_list")
    return render(request, "webadmin/catalogue/categories/form.html", {
        "category": cat, "category_types": CategoryType.objects.all()})


@require_POST
@perm_required("manage_categories")
def category_delete_view(request, id):
    get_object_or_404(Category, id=id).delete()
    messages.success(request, "Category deleted.")
    return redirect("webadmin:categories_list")


# ── Products ─────────────────────────────────────────────────────────────────

@perm_required("view_products")
def products_list_view(request):
    qs = Product.objects.all()
    search = request.GET.get("search")
    if search:
        qs = qs.filter(name__icontains=search)
    category_id = request.GET.get("category_id")
    if category_id:
        qs = qs.filter(categories__id=category_id)
    if category_id or request.GET.get("sort") == "category":
        qs = qs.prefetch_related("categories").order_by("categories__name", "name")
    else:
        qs = qs.order_by("-created_at")
    qs = qs.distinct()
    paginator = Paginator(qs, request.GET.get("per_page", 20))
    return render(request, "webadmin/catalogue/products/list.html", {
        "page": paginator.get_page(request.GET.get("page")),
        "categories": Category.objects.filter(category_type_id=FOOD_CATEGORY_TYPE_ID).order_by("name")})


@perm_required("manage_products")
def product_create_view(request):
    if request.method == "POST":
        if not request.POST.get("name"):
            messages.error(request, "Name is required.")
        else:
            image_url = None
            uploaded_image = request.FILES.get("image_file")
            if uploaded_image:
                image_url = _save_uploaded_image(uploaded_image, "products")
            p = Product.objects.create(
                name=request.POST["name"], description=request.POST.get("description"),
                price=request.POST.get("price") or 0, discount_price=request.POST.get("discount_price") or None,
                stock=request.POST.get("stock") or 0, preparation_steps=request.POST.get("preparation_steps"),
                image_url=image_url)
            for cid in request.POST.getlist("category_ids"):
                CategoryProduct.objects.get_or_create(product=p, category_id=cid)
            _sync_price_overrides(p.state_prices, "state", "discount_price",
                                  _extract_price_rows(request, "state_price"))
            messages.success(request, "Product created successfully.")
            return redirect("webadmin:products_list")
    return render(request, "webadmin/catalogue/products/form.html", {
        "product": None, "categories": Category.objects.filter(category_type_id=FOOD_CATEGORY_TYPE_ID),
        "selected_category_ids": [], "states": State.objects.order_by("name"), "state_prices": []})


@perm_required("manage_products")
def product_update_view(request, id):
    p = get_object_or_404(Product, id=id)
    if request.method == "POST":
        nullable_fields = {"discount_price"}
        for field in ["name", "description", "price", "discount_price", "stock",
                      "preparation_steps"]:
            value = request.POST.get(field)
            if value is not None:
                setattr(p, field, None if value == "" and field in nullable_fields else value)
        uploaded_image = request.FILES.get("image_file")
        if uploaded_image:
            p.image_url = _save_uploaded_image(uploaded_image, "products")
        p.save()
        category_ids = request.POST.getlist("category_ids")
        CategoryProduct.objects.filter(product=p).exclude(category_id__in=category_ids).delete()
        for cid in category_ids:
            CategoryProduct.objects.get_or_create(product=p, category_id=cid)
        _sync_price_overrides(p.state_prices, "state", "discount_price",
                              _extract_price_rows(request, "state_price"))
        messages.success(request, "Product updated successfully.")
        return redirect("webadmin:products_list")
    return render(request, "webadmin/catalogue/products/form.html", {
        "product": p, "categories": Category.objects.filter(category_type_id=FOOD_CATEGORY_TYPE_ID),
        "selected_category_ids": list(p.categories.values_list("id", flat=True)),
        "states": State.objects.order_by("name"), "state_prices": p.state_prices.select_related("state").all()})


@require_POST
@perm_required("manage_products")
def product_toggle_status_view(request, id):
    p = get_object_or_404(Product, id=id)
    p.is_active = not p.is_active
    p.save(update_fields=["is_active"])
    messages.success(request, f"{p.name} is now {'active' if p.is_active else 'inactive'}.")
    return redirect("webadmin:products_list")


@require_POST
@perm_required("manage_products")
def product_delete_view(request, id):
    get_object_or_404(Product, id=id).delete()
    messages.success(request, "Product deleted.")
    return redirect("webadmin:products_list")


# ── Ingredients ──────────────────────────────────────────────────────────────

@perm_required("view_ingredients")
def ingredients_list_view(request):
    qs = Ingredient.objects.select_related("category")
    search = request.GET.get("search")
    if search:
        qs = qs.filter(name__icontains=search)
    category_id = request.GET.get("category_id")
    if category_id:
        qs = qs.filter(category_id=category_id)
    if category_id or request.GET.get("sort") == "category":
        qs = qs.order_by("category__name", "name")
    else:
        qs = qs.order_by("-created_at")
    paginator = Paginator(qs, request.GET.get("per_page", 20))
    return render(request, "webadmin/catalogue/ingredients/list.html", {
        "page": paginator.get_page(request.GET.get("page")),
        "categories": Category.objects.filter(category_type_id=VENDOR_CATEGORY_TYPE_ID).order_by("name")})


@perm_required("manage_ingredients")
def ingredient_create_view(request):
    if request.method == "POST":
        if not request.POST.get("name"):
            messages.error(request, "Name is required.")
        else:
            image_url = None
            uploaded_image = request.FILES.get("image_file")
            if uploaded_image:
                image_url = _save_uploaded_image(uploaded_image, "ingredients")
            ing = Ingredient.objects.create(
                name=request.POST["name"], description=request.POST.get("description"),
                price=request.POST.get("price") or 0, discounted_price=request.POST.get("discounted_price") or None,
                unit=request.POST.get("unit", ""), stock=request.POST.get("stock") or 0,
                image_url=image_url, category_id=request.POST.get("category_id") or None)
            _sync_price_overrides(ing.state_prices, "state", "discounted_price",
                                  _extract_price_rows(request, "state_price"))
            _sync_price_overrides(ing.lga_prices, "lga", "discounted_price",
                                  _extract_price_rows(request, "lga_price"))
            messages.success(request, "Ingredient created successfully.")
            return redirect("webadmin:ingredients_list")
    return render(request, "webadmin/catalogue/ingredients/form.html", {
        "ingredient": None, "categories": Category.objects.filter(category_type_id=VENDOR_CATEGORY_TYPE_ID),
        "states": State.objects.order_by("name"), "lgas": Lga.objects.select_related("state").order_by("name"),
        "state_prices": [], "lga_prices": []})


@perm_required("manage_ingredients")
def ingredient_update_view(request, id):
    ing = get_object_or_404(Ingredient, id=id)
    if request.method == "POST":
        nullable_fields = {"discounted_price", "category_id"}
        for field in ["name", "description", "price", "discounted_price", "unit",
                      "stock", "category_id"]:
            value = request.POST.get(field)
            if value is not None:
                setattr(ing, field, None if value == "" and field in nullable_fields else value)
        uploaded_image = request.FILES.get("image_file")
        if uploaded_image:
            ing.image_url = _save_uploaded_image(uploaded_image, "ingredients")
        ing.save()
        _sync_price_overrides(ing.state_prices, "state", "discounted_price",
                              _extract_price_rows(request, "state_price"))
        _sync_price_overrides(ing.lga_prices, "lga", "discounted_price",
                              _extract_price_rows(request, "lga_price"))
        messages.success(request, "Ingredient updated successfully.")
        return redirect("webadmin:ingredients_list")
    return render(request, "webadmin/catalogue/ingredients/form.html", {
        "ingredient": ing, "categories": Category.objects.filter(category_type_id=VENDOR_CATEGORY_TYPE_ID),
        "states": State.objects.order_by("name"), "lgas": Lga.objects.select_related("state").order_by("name"),
        "state_prices": ing.state_prices.select_related("state").all(),
        "lga_prices": ing.lga_prices.select_related("lga", "lga__state").all()})


@require_POST
@perm_required("manage_ingredients")
def ingredient_delete_view(request, id):
    get_object_or_404(Ingredient, id=id).delete()
    messages.success(request, "Ingredient deleted.")
    return redirect("webadmin:ingredients_list")


# ── Advertisements ───────────────────────────────────────────────────────────
# Mirrors AdvertisementController::store/update/destroy in the PHP admin: a
# "discount"/"off" advertisement isn't just a banner -- creating/editing one
# actually recomputes and writes each linked ingredient's discounted_price,
# and "destroy" doesn't delete the row at all, it toggles active/stop,
# clearing the discount when stopped and reapplying it when reactivated.

def _apply_ingredient_discounts(ad):
    if ad.type not in ("discount", "off") or not ad.ingredient_ids:
        return
    value = Decimal(str(ad.value or 0))
    for ing in Ingredient.objects.filter(id__in=ad.ingredient_ids):
        if ad.type == "discount":
            discounted = ing.price - (ing.price * value / 100)
        else:
            discounted = ing.price - value
        ing.discounted_price = max(0, discounted)
        ing.save(update_fields=["discounted_price"])


def _clear_ingredient_discounts(ad):
    if ad.type not in ("discount", "off") or not ad.ingredient_ids:
        return
    Ingredient.objects.filter(id__in=ad.ingredient_ids).update(discounted_price=0)


@admin_required
def advertisements_list_view(request):
    qs = Advertisement.objects.all().order_by("-created_at")
    paginator = Paginator(qs, request.GET.get("per_page", 20))
    return render(request, "webadmin/catalogue/advertisements/list.html",
                  {"page": paginator.get_page(request.GET.get("page"))})


@perm_required("manage_settings")
def advertisement_create_view(request):
    if request.method == "POST":
        ingredient_ids = [int(x) for x in request.POST.getlist("ingredient_ids") if x.isdigit()]
        ad = Advertisement.objects.create(
            type=request.POST.get("type", "info"), value=request.POST.get("value") or None,
            ingredient_ids=ingredient_ids, status=request.POST.get("status", "active"),
            image=request.POST.get("image", ""))
        _apply_ingredient_discounts(ad)
        messages.success(request, "Advertisement created successfully.")
        return redirect("webadmin:advertisements_list")
    return render(request, "webadmin/catalogue/advertisements/form.html", {
        "ad": None, "ingredients": Ingredient.objects.order_by("name")[:300], "selected_ingredient_ids": []})


@perm_required("manage_settings")
def advertisement_update_view(request, id):
    ad = get_object_or_404(Advertisement, id=id)
    if request.method == "POST":
        for field in ["type", "value", "status", "image"]:
            if request.POST.get(field) is not None:
                setattr(ad, field, request.POST[field])
        ad.ingredient_ids = [int(x) for x in request.POST.getlist("ingredient_ids") if x.isdigit()]
        ad.save()
        _apply_ingredient_discounts(ad)
        messages.success(request, "Advertisement updated successfully.")
        return redirect("webadmin:advertisements_list")
    return render(request, "webadmin/catalogue/advertisements/form.html", {
        "ad": ad, "ingredients": Ingredient.objects.order_by("name")[:300],
        "selected_ingredient_ids": ad.ingredient_ids or []})


@require_POST
@perm_required("manage_settings")
def advertisement_toggle_status_view(request, id):
    ad = get_object_or_404(Advertisement, id=id)
    if ad.status == "active":
        _clear_ingredient_discounts(ad)
        ad.status = "stop"
    else:
        ad.status = "active"
        _apply_ingredient_discounts(ad)
    ad.save(update_fields=["status"])
    messages.success(request, f"Advertisement {'stopped' if ad.status == 'stop' else 'activated'}.")
    return redirect("webadmin:advertisements_list")


@require_POST
@perm_required("manage_settings")
def advertisement_delete_view(request, id):
    ad = get_object_or_404(Advertisement, id=id)
    if ad.status == "active":
        _clear_ingredient_discounts(ad)
    ad.delete()
    messages.success(request, "Advertisement deleted.")
    return redirect("webadmin:advertisements_list")
