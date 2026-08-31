"""Shared plumbing for the optional per-user permission picker.

Roles carry sensible defaults, so the picker is deliberately optional: tick
nothing and the account simply follows its role. Tick something and that
exact set is stored, which is what makes an account "custom" -- the only
signal we need, since it's just a set comparison against the role defaults
(no extra column to keep in sync).
"""
from apps.accounts.models import Permission, Roles

# Friendlier headings than the raw `group` column, in the order they appear.
GROUP_LABELS = [
    ("dashboard", "Dashboard"),
    ("orders", "Orders"),
    ("logistics", "Logistics"),
    ("users", "Customers"),
    ("vendors", "Vendors"),
    ("catalogue", "Catalogue"),
    ("finance", "Finance"),
    ("reports", "Reports"),
    ("send", "Notifications"),
    ("view", "Other — view"),
    ("manage", "Other — manage"),
    ("admin", "System"),
]


def permission_groups(selected_slugs=(), role=None):
    """Grouped permissions for the template, flagging which are ticked and
    which the role would grant on its own."""
    selected = set(selected_slugs or [])
    defaults = set(Roles.default_permissions(role)) if role else set()
    by_group = {}
    for perm in Permission.objects.all().order_by("id"):
        by_group.setdefault(perm.group, []).append({
            "slug": perm.slug,
            "name": perm.name,
            "checked": perm.slug in selected,
            "is_default": perm.slug in defaults,
        })

    ordered, seen = [], set()
    for key, label in GROUP_LABELS:
        if by_group.get(key):
            ordered.append({"key": key, "label": label, "items": by_group[key]})
            seen.add(key)
    for key, items in by_group.items():          # any group we didn't name
        if key not in seen:
            ordered.append({"key": key, "label": key.title(), "items": items})
    return ordered


def apply_permissions(user, request):
    """Store the ticked permissions, or fall back to the role's defaults.

    Returns True when a custom set was applied.
    """
    slugs = request.POST.getlist("permissions")
    if not slugs:
        user.sync_default_permissions()
        return False
    valid = Permission.objects.filter(slug__in=slugs)
    user.permissions_m2m.set(valid)
    return True


def is_customised(user):
    """True when the stored set differs from the role's defaults."""
    current = set(user.permissions_m2m.values_list("slug", flat=True))
    return current != set(Roles.default_permissions(user.role))
