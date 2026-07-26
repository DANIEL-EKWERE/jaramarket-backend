"""Mirrors the catalogue CRUD (categories/products/ingredients/advertisements)
in api/admin_views.py — same fields, same permission slugs."""
from django.contrib import messages
from django.core.paginator import Paginator
from django.shortcuts import get_object_or_404, redirect, render
from django.views.decorators.http import require_POST

from apps.catalogue.models import Category, CategoryProduct, CategoryType, Ingredient, Product
from apps.support.models import Advertisement
from ..decorators import admin_required, perm_required


def _save_uploaded_image(uploaded_file, subfolder):
    """Save an uploaded image to MEDIA_ROOT/<subfolder>/ (same local-disk
    pattern as edit_profile/support_collection in the mobile API) and return
    an absolute URL so ProductSerializer._full_image_url doesn't try to
    re-prefix it with the S3 bucket domain."""
    import os
    import uuid
    from django.conf import settings

    ext = os.path.splitext(uploaded_file.name)[1]
    filename = f"{uuid.uuid4().hex}{ext}"
    upload_dir = os.path.join(settings.MEDIA_ROOT, subfolder)
    os.makedirs(upload_dir, exist_ok=True)
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
    qs = Product.objects.all().order_by("-created_at")
    paginator = Paginator(qs, request.GET.get("per_page", 20))
    return render(request, "webadmin/catalogue/products/list.html",
                  {"page": paginator.get_page(request.GET.get("page"))})


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
            messages.success(request, "Product created successfully.")
            return redirect("webadmin:products_list")
    return render(request, "webadmin/catalogue/products/form.html", {
        "product": None, "categories": Category.objects.filter(category_type_id=FOOD_CATEGORY_TYPE_ID),
        "selected_category_ids": []})


@perm_required("manage_products")
def product_update_view(request, id):
    p = get_object_or_404(Product, id=id)
    if request.method == "POST":
        for field in ["name", "description", "price", "discount_price", "stock",
                      "preparation_steps"]:
            if request.POST.get(field) is not None:
                setattr(p, field, request.POST[field])
        uploaded_image = request.FILES.get("image_file")
        if uploaded_image:
            p.image_url = _save_uploaded_image(uploaded_image, "products")
        p.save()
        category_ids = request.POST.getlist("category_ids")
        CategoryProduct.objects.filter(product=p).exclude(category_id__in=category_ids).delete()
        for cid in category_ids:
            CategoryProduct.objects.get_or_create(product=p, category_id=cid)
        messages.success(request, "Product updated successfully.")
        return redirect("webadmin:products_list")
    return render(request, "webadmin/catalogue/products/form.html", {
        "product": p, "categories": Category.objects.filter(category_type_id=FOOD_CATEGORY_TYPE_ID),
        "selected_category_ids": list(p.categories.values_list("id", flat=True))})


@require_POST
@perm_required("manage_products")
def product_delete_view(request, id):
    get_object_or_404(Product, id=id).delete()
    messages.success(request, "Product deleted.")
    return redirect("webadmin:products_list")


# ── Ingredients ──────────────────────────────────────────────────────────────

@perm_required("view_ingredients")
def ingredients_list_view(request):
    qs = Ingredient.objects.select_related("category").order_by("-created_at")
    paginator = Paginator(qs, request.GET.get("per_page", 20))
    return render(request, "webadmin/catalogue/ingredients/list.html",
                  {"page": paginator.get_page(request.GET.get("page"))})


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
            Ingredient.objects.create(
                name=request.POST["name"], description=request.POST.get("description"),
                price=request.POST.get("price") or 0, discounted_price=request.POST.get("discounted_price") or None,
                unit=request.POST.get("unit", ""), stock=request.POST.get("stock") or 0,
                image_url=image_url, category_id=request.POST.get("category_id") or None)
            messages.success(request, "Ingredient created successfully.")
            return redirect("webadmin:ingredients_list")
    return render(request, "webadmin/catalogue/ingredients/form.html", {
        "ingredient": None, "categories": Category.objects.filter(category_type_id=VENDOR_CATEGORY_TYPE_ID)})


@perm_required("manage_ingredients")
def ingredient_update_view(request, id):
    ing = get_object_or_404(Ingredient, id=id)
    if request.method == "POST":
        for field in ["name", "description", "price", "discounted_price", "unit",
                      "stock", "category_id"]:
            if request.POST.get(field) is not None:
                setattr(ing, field, request.POST[field])
        uploaded_image = request.FILES.get("image_file")
        if uploaded_image:
            ing.image_url = _save_uploaded_image(uploaded_image, "ingredients")
        ing.save()
        messages.success(request, "Ingredient updated successfully.")
        return redirect("webadmin:ingredients_list")
    return render(request, "webadmin/catalogue/ingredients/form.html", {
        "ingredient": ing, "categories": Category.objects.filter(category_type_id=VENDOR_CATEGORY_TYPE_ID)})


@require_POST
@perm_required("manage_ingredients")
def ingredient_delete_view(request, id):
    get_object_or_404(Ingredient, id=id).delete()
    messages.success(request, "Ingredient deleted.")
    return redirect("webadmin:ingredients_list")


# ── Advertisements ───────────────────────────────────────────────────────────

@admin_required
def advertisements_list_view(request):
    qs = Advertisement.objects.all().order_by("-created_at")
    paginator = Paginator(qs, request.GET.get("per_page", 20))
    return render(request, "webadmin/catalogue/advertisements/list.html",
                  {"page": paginator.get_page(request.GET.get("page"))})


@perm_required("manage_settings")
def advertisement_create_view(request):
    if request.method == "POST":
        ids_raw = request.POST.get("ingredient_ids", "")
        ingredient_ids = [int(x) for x in ids_raw.split(",") if x.strip().isdigit()]
        Advertisement.objects.create(
            type=request.POST.get("type", "info"), value=request.POST.get("value") or None,
            ingredient_ids=ingredient_ids, status=request.POST.get("status", "active"),
            image=request.POST.get("image", ""))
        messages.success(request, "Advertisement created successfully.")
        return redirect("webadmin:advertisements_list")
    return render(request, "webadmin/catalogue/advertisements/form.html", {"ad": None})


@perm_required("manage_settings")
def advertisement_update_view(request, id):
    ad = get_object_or_404(Advertisement, id=id)
    if request.method == "POST":
        for field in ["type", "value", "status", "image"]:
            if request.POST.get(field) is not None:
                setattr(ad, field, request.POST[field])
        if request.POST.get("ingredient_ids") is not None:
            ids_raw = request.POST.get("ingredient_ids", "")
            ad.ingredient_ids = [int(x) for x in ids_raw.split(",") if x.strip().isdigit()]
        ad.save()
        messages.success(request, "Advertisement updated successfully.")
        return redirect("webadmin:advertisements_list")
    return render(request, "webadmin/catalogue/advertisements/form.html", {"ad": ad})


@require_POST
@perm_required("manage_settings")
def advertisement_delete_view(request, id):
    get_object_or_404(Advertisement, id=id).delete()
    messages.success(request, "Advertisement deleted.")
    return redirect("webadmin:advertisements_list")
