from django import template

register = template.Library()


@register.filter
def full_image_url(path):
    """Resolve a stored image_url (often just a bare relative path/S3 key,
    e.g. "food/photo.jpg") into a browser-loadable URL, same as
    ProductSerializer/IngredientSerializer do for the mobile apps. Templates
    must never render image_url directly as an <img src> -- it isn't
    guaranteed to be absolute."""
    from apps.catalogue.serializers import _full_image_url
    return _full_image_url(path)


@register.filter
def has_perm_slug(user, slug):
    """Template-usable wrapper around User.has_perm_slug(), since Django
    templates can't call methods with arguments directly."""
    if not user or not user.is_authenticated:
        return False
    return user.has_perm_slug(slug)


@register.filter
def has_any_perm(user, slugs_csv):
    """Same as has_perm_slug but accepts a comma-separated list of slugs,
    mirroring require_perms()'s "any of these" semantics."""
    if not user or not user.is_authenticated:
        return False
    slugs = [s.strip() for s in slugs_csv.split(",")]
    return user.has_any_permission(slugs)


# ── Order status presentation ───────────────────────────────────────────────
# The orders screen polls for status changes, so the JavaScript has to write
# exactly the same badge and text the server rendered. Keeping both in one
# place is what stops a polled update from quietly disagreeing with the page.

ORDER_STATUS_BADGES = {
    "pending": "badge-warning",
    "processing": "badge-info",
    "delivered": "badge-info",
    "completed": "badge-info",
    "in_transit": "badge-info",
    "received": "badge-success",
}
ITEM_STATUS_BADGES = {
    "pending": "badge-warning",
    "processing": "badge-info",
    "accepted": "badge-info",
    "offered": "badge-info",
    "delivered": "badge-info",
    "completed": "badge-success",
}


def order_badge_class(status):
    return ORDER_STATUS_BADGES.get(status or "", "badge-danger")


def item_badge_class(status):
    return ITEM_STATUS_BADGES.get(status or "", "badge-danger")


def status_label(status):
    """"in_transit" -> "In Transit". Django's |title would leave the
    underscore in place ("In_Transit")."""
    return str(status or "").replace("_", " ").title()


@register.filter(name="order_badge")
def order_badge(status):
    return order_badge_class(status)


@register.filter(name="item_badge")
def item_badge(status):
    return item_badge_class(status)


@register.filter(name="status_label")
def status_label_filter(status):
    return status_label(status)
