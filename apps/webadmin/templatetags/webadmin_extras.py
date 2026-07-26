from django import template

register = template.Library()


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
