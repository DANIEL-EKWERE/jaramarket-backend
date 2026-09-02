"""Mirrors the catalogue CRUD (categories/products/ingredients/advertisements)
in api/admin_views.py — same fields, same permission slugs."""
from decimal import Decimal

from django.contrib import messages
from django.core.paginator import Paginator
from django.shortcuts import get_object_or_404, redirect, render
from django.views.decorators.http import require_POST

from apps.catalogue.models import (
    Category, CategoryProduct, CategoryType, Ingredient, IngredientLgaPrice,
    IngredientLgaSuspension, IngredientStatePrice, IngredientStateSuspension,
    Product, ProductLgaSuspension, ProductStatePrice, ProductStateSuspension, Uom,
)
from apps.geo.models import Lga, State
from apps.support.models import Advertisement
from apps.vendors.models import Market
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


def _suspension_manager(obj, level):
    """Map a "state"/"lga"/"market" level from the suspend/reactivate forms
    to the matching related manager + FK field name on a product or
    ingredient. Deactivate/Reactivate are instant single-row actions (not
    bundled into the main Save), so this is a plain lookup rather than the
    full set-sync _sync_price_overrides-style helpers use."""
    return {
        "state": (obj.state_suspensions, "state"),
        "lga": (obj.lga_suspensions, "lga"),
        "market": (obj.market_suspensions, "market"),
    }.get(level, (None, None))


def _suspension_rows(state_qs, lga_qs, market_qs):
    """Build a single reactivate/deactivate list for display, merging the
    three separate suspension tables (state, LGA, market) into one set of
    {level, id, label} rows the "Suspend for specific locations" UI can
    render generically regardless of which table a row actually lives in."""
    rows = []
    for row in state_qs.select_related("state"):
        rows.append({"level": "state", "id": row.state_id, "label": row.state.name})
    for row in lga_qs.select_related("lga__state"):
        state_name = row.lga.state.name if row.lga.state_id else None
        label = f"{state_name} → {row.lga.name}" if state_name else row.lga.name
        rows.append({"level": "lga", "id": row.lga_id, "label": label})
    for row in market_qs.select_related("market__state", "market__lga"):
        market = row.market
        parts = [p.name for p in (market.state, market.lga) if p] + [market.name]
        rows.append({"level": "market", "id": row.market_id, "label": " → ".join(parts)})
    return rows


def _extract_ingredient_rows(request):
    """Reconstruct dynamic add/remove ingredient rows from parallel
    getlist()s (one value per row, in DOM order) -- same pattern as
    _extract_price_rows."""
    ids = request.POST.getlist("ingredient_id")
    quantities = request.POST.getlist("ingredient_quantity")
    units = request.POST.getlist("ingredient_unit")
    prices = request.POST.getlist("ingredient_price")
    return list(zip(ids, quantities, units, prices))


def _sync_ingredient_links(product, rows):
    """Replace a product's linked-ingredients set (each link carrying its
    own quantity/unit/price) with the incoming rows -- mirrors
    _sync_price_overrides but keyed on ingredient_id. Returns the synced
    rows so the caller can total up the product's price from them."""
    incoming = {}
    for ing_id, quantity, unit, price in rows:
        if not ing_id:
            continue
        incoming[int(ing_id)] = {"quantity": quantity or None, "unit": unit or None, "price": price or None}
    product.ingredientproduct_set.exclude(ingredient_id__in=incoming.keys()).delete()
    for ing_id, data in incoming.items():
        product.ingredientproduct_set.update_or_create(ingredient_id=ing_id, defaults=data)
    return incoming


def _extract_ingredient_state_rows(request):
    """State-price rows for the ingredients shown on the product form.

    Nested repeating groups don't fit the flat getlist() pattern on their own,
    so every row carries its own ingredient id alongside the state and price.
    """
    return list(zip(
        request.POST.getlist("ing_state_ingredient"),
        request.POST.getlist("ing_state_state"),
        request.POST.getlist("ing_state_price"),
        request.POST.getlist("ing_state_discount"),
    ))


def _sync_ingredient_state_prices(request, rows):
    """Replace state overrides for ONLY the ingredients this form rendered.

    The form posts every existing override as a (possibly hidden) row, so an
    untouched save round-trips unchanged. The `ing_state_sync` list is what
    makes deletion safe: an ingredient whose panel was never rendered -- a row
    the admin just added, say -- is absent from it and so is left completely
    alone, instead of having its real overrides wiped by an empty set.

    These prices belong to the ingredient itself, not to this recipe, so the
    change is global. The form says so.
    """
    from apps.catalogue.models import IngredientStatePrice

    syncable = {int(i) for i in request.POST.getlist("ing_state_sync") if i}
    if not syncable:
        return 0

    grouped = {}
    for ing_id, state_id, price, discount in rows:
        if not ing_id or not state_id or price in (None, ""):
            continue
        ing_id = int(ing_id)
        if ing_id not in syncable:
            continue
        grouped.setdefault(ing_id, {})[int(state_id)] = {
            "price": price, "discounted_price": discount or None}

    touched = 0
    for ing_id in syncable:
        incoming = grouped.get(ing_id, {})
        existing = IngredientStatePrice.objects.filter(ingredient_id=ing_id)
        touched += existing.exclude(state_id__in=incoming.keys()).delete()[0]
        for state_id, data in incoming.items():
            _, created = IngredientStatePrice.objects.update_or_create(
                ingredient_id=ing_id, state_id=state_id, defaults=data)
            touched += 1
    return touched


def _ingredient_rows_total(incoming):
    """A food item's price is the accumulated cost of its recipe -- sum
    each linked ingredient's (editable) row price, once any are linked."""
    total = Decimal("0")
    for data in incoming.values():
        if data["price"] is not None:
            total += Decimal(str(data["price"]))
    return total


def _save_uploaded_image(uploaded_file, subfolder):
    """Thin alias -- the actual upload logic is shared with the customer
    API (e.g. order voice notes) in api.utils.save_uploaded_file."""
    from api.utils import save_uploaded_file
    return save_uploaded_file(uploaded_file, subfolder)

# Mirrors App\Enums\CategoryTypeEnum in the PHP admin (FOOD=1, VENDOR=2), seeded
# identically everywhere by CategoryTypeSeeder / apps/catalogue migrations.
# Food-type categories are the ones shown on the jara-user app and attached to
# Products; Vendor-type categories are the ones a vendor picks during
# onboarding and are shared with Ingredients. They must never be cross-offered.
def _normalise_youtube_url(raw):
    """Return (canonical_watch_url, error) for a pasted YouTube link.

    Admins paste whatever the YouTube share sheet gave them -- youtu.be
    short links, /shorts/, /embed/, /live/, or a watch URL trailing a
    playlist and timestamp. Store one canonical form so the app never has to
    guess, and reject anything that isn't YouTube rather than shipping a
    dead button to customers. Empty input clears the field.
    """
    import re

    raw = (raw or "").strip()
    if not raw:
        return None, None
    match = re.search(
        r"(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)"
        r"|youtu\.be/)([A-Za-z0-9_-]{11})",
        raw)
    if not match:
        return None, "That doesn't look like a YouTube link — the recipe video was not saved."
    return f"https://www.youtube.com/watch?v={match.group(1)}", None


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
    qs = qs.distinct().prefetch_related(
        "ingredientproduct_set__ingredient",
        "ingredientproduct_set__ingredient__state_prices__state",
        "state_prices__state")
    paginator = Paginator(qs, request.GET.get("per_page", 20))
    page = paginator.get_page(request.GET.get("page"))
    return render(request, "webadmin/catalogue/products/list.html", {
        "page": page,
        "states": State.objects.order_by("name"),
        "can_manage": request.user.has_perm_slug("manage_products"),
        "categories": Category.objects.filter(category_type_id=FOOD_CATEGORY_TYPE_ID).order_by("name")})


@require_POST
@perm_required("manage_products")
def product_state_prices_view(request, id):
    """Save just the state-price overrides, from the products list.

    Deliberately narrow: posting the full edit form from a list row would
    have to carry every other product field and would blank whatever it
    omitted.
    """
    p = get_object_or_404(Product, id=id)
    _sync_price_overrides(p.state_prices, "state", "discount_price",
                          _extract_price_rows(request, "state_price"))
    messages.success(request, f"State prices updated for {p.name}.")
    return redirect(request.POST.get("next") or "webadmin:products_list")


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
            youtube_url, youtube_error = _normalise_youtube_url(request.POST.get("youtube_url"))
            if youtube_error:
                messages.warning(request, youtube_error)
            p = Product.objects.create(
                name=request.POST["name"], description=request.POST.get("description"),
                price=request.POST.get("price") or 0, discount_price=request.POST.get("discount_price") or None,
                stock=request.POST.get("stock") or 0, preparation_steps=request.POST.get("preparation_steps"),
                image_url=image_url, youtube_url=youtube_url)
            for cid in request.POST.getlist("category_ids"):
                CategoryProduct.objects.get_or_create(product=p, category_id=cid)
            _sync_price_overrides(p.state_prices, "state", "discount_price",
                                  _extract_price_rows(request, "state_price"))
            linked_ingredients = _sync_ingredient_links(p, _extract_ingredient_rows(request))
            if linked_ingredients:
                p.price = _ingredient_rows_total(linked_ingredients)
                p.save(update_fields=["price"])
            messages.success(request, "Product created successfully.")
            return redirect("webadmin:products_list")
    return render(request, "webadmin/catalogue/products/form.html", {
        "product": None, "categories": Category.objects.filter(category_type_id=FOOD_CATEGORY_TYPE_ID),
        "selected_category_ids": [], "states": State.objects.order_by("name"),
        "lgas": Lga.objects.select_related("state").order_by("state__name", "name"), "state_prices": [],
        "ingredients": Ingredient.objects.order_by("name"), "ingredient_links": [],
        "uoms": Uom.objects.order_by("name")})


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
        if "youtube_url" in request.POST:
            youtube_url, youtube_error = _normalise_youtube_url(request.POST["youtube_url"])
            if youtube_error:
                # Keep whatever was already saved rather than replacing a
                # working link with a broken one.
                messages.warning(request, youtube_error)
            else:
                p.youtube_url = youtube_url
        p.save()
        category_ids = request.POST.getlist("category_ids")
        CategoryProduct.objects.filter(product=p).exclude(category_id__in=category_ids).delete()
        for cid in category_ids:
            CategoryProduct.objects.get_or_create(product=p, category_id=cid)
        _sync_price_overrides(p.state_prices, "state", "discount_price",
                              _extract_price_rows(request, "state_price"))
        linked_ingredients = _sync_ingredient_links(p, _extract_ingredient_rows(request))
        if linked_ingredients:
            p.price = _ingredient_rows_total(linked_ingredients)
            p.save(update_fields=["price"])
        _sync_ingredient_state_prices(request, _extract_ingredient_state_rows(request))
        messages.success(request, "Product updated successfully.")
        return redirect("webadmin:products_list")
    return render(request, "webadmin/catalogue/products/form.html", {
        "product": p, "categories": Category.objects.filter(category_type_id=FOOD_CATEGORY_TYPE_ID),
        "selected_category_ids": list(p.categories.values_list("id", flat=True)),
        "states": State.objects.order_by("name"),
        "lgas": Lga.objects.select_related("state").order_by("state__name", "name"),
        "state_prices": p.state_prices.select_related("state").all(),
        "markets": Market.objects.filter(is_active=True).select_related("state", "lga").order_by("name"),
        "suspensions": _suspension_rows(p.state_suspensions, p.lga_suspensions, p.market_suspensions),
        "ingredients": Ingredient.objects.order_by("name"),
        "ingredient_links": p.ingredientproduct_set.select_related("ingredient").all(),
        "uoms": Uom.objects.order_by("name")})


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


@require_POST
@perm_required("manage_products")
def product_suspend_view(request, id):
    p = get_object_or_404(Product, id=id)
    manager, fk_field = _suspension_manager(p, request.POST.get("level"))
    location_id = request.POST.get("location_id")
    if manager is not None and location_id:
        manager.get_or_create(**{f"{fk_field}_id": location_id})
        messages.success(request, "Deactivated for that location.")
    return redirect("webadmin:product_update", id=id)


@require_POST
@perm_required("manage_products")
def product_reactivate_view(request, id):
    p = get_object_or_404(Product, id=id)
    manager, fk_field = _suspension_manager(p, request.POST.get("level"))
    location_id = request.POST.get("location_id")
    if manager is not None and location_id:
        manager.filter(**{f"{fk_field}_id": location_id}).delete()
        messages.success(request, "Reactivated for that location.")
    return redirect("webadmin:product_update", id=id)


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
    # Order dispatch matches items to vendors purely by the ingredient's
    # category, so an uncategorised ingredient can never reach a vendor --
    # make those findable (see MarketDispatchService.eligible_vendors).
    if request.GET.get("uncategorised"):
        qs = qs.filter(category__isnull=True)
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
        "states": State.objects.order_by("name"), "lgas": Lga.objects.select_related("state").order_by("state__name", "name"),
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
        "states": State.objects.order_by("name"), "lgas": Lga.objects.select_related("state").order_by("state__name", "name"),
        "markets": Market.objects.filter(is_active=True).select_related("state", "lga").order_by("name"),
        "state_prices": ing.state_prices.select_related("state").all(),
        "lga_prices": ing.lga_prices.select_related("lga", "lga__state").all(),
        "suspensions": _suspension_rows(ing.state_suspensions, ing.lga_suspensions, ing.market_suspensions)})


@require_POST
@perm_required("manage_ingredients")
def ingredient_toggle_status_view(request, id):
    ing = get_object_or_404(Ingredient, id=id)
    ing.is_active = not ing.is_active
    ing.save(update_fields=["is_active"])
    messages.success(request, f"{ing.name} is now {'active' if ing.is_active else 'inactive'}.")
    return redirect("webadmin:ingredients_list")


@require_POST
@perm_required("manage_ingredients")
def ingredient_delete_view(request, id):
    get_object_or_404(Ingredient, id=id).delete()
    messages.success(request, "Ingredient deleted.")
    return redirect("webadmin:ingredients_list")


@require_POST
@perm_required("manage_ingredients")
def ingredient_suspend_view(request, id):
    ing = get_object_or_404(Ingredient, id=id)
    manager, fk_field = _suspension_manager(ing, request.POST.get("level"))
    location_id = request.POST.get("location_id")
    if manager is not None and location_id:
        manager.get_or_create(**{f"{fk_field}_id": location_id})
        messages.success(request, "Deactivated for that location.")
    return redirect("webadmin:ingredient_update", id=id)


@require_POST
@perm_required("manage_ingredients")
def ingredient_reactivate_view(request, id):
    ing = get_object_or_404(Ingredient, id=id)
    manager, fk_field = _suspension_manager(ing, request.POST.get("level"))
    location_id = request.POST.get("location_id")
    if manager is not None and location_id:
        manager.filter(**{f"{fk_field}_id": location_id}).delete()
        messages.success(request, "Reactivated for that location.")
    return redirect("webadmin:ingredient_update", id=id)


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
        image_url = ""
        uploaded_image = request.FILES.get("image_file")
        if uploaded_image:
            image_url = _save_uploaded_image(uploaded_image, "advertisements")
        ad = Advertisement.objects.create(
            type=request.POST.get("type", "info"), value=request.POST.get("value") or None,
            ingredient_ids=ingredient_ids, status=request.POST.get("status", "active"),
            image=image_url)
        _apply_ingredient_discounts(ad)
        messages.success(request, "Advertisement created successfully.")
        return redirect("webadmin:advertisements_list")
    return render(request, "webadmin/catalogue/advertisements/form.html", {
        "ad": None, "ingredients": Ingredient.objects.order_by("name")[:300], "selected_ingredient_ids": []})


@perm_required("manage_settings")
def advertisement_update_view(request, id):
    ad = get_object_or_404(Advertisement, id=id)
    if request.method == "POST":
        for field in ["type", "value", "status"]:
            if request.POST.get(field) is not None:
                setattr(ad, field, request.POST[field])
        uploaded_image = request.FILES.get("image_file")
        if uploaded_image:
            ad.image = _save_uploaded_image(uploaded_image, "advertisements")
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
