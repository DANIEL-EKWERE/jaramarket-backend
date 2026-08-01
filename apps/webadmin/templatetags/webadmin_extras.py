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
