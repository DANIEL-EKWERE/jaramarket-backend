"""Dashboard-wide template context.

The sidebar badge needs a count on every page, and threading it through each
view would mean touching every one of them (and forgetting some).
"""


def sidebar_counts(request):
    user = getattr(request, "user", None)
    if not (user and user.is_authenticated and getattr(user, "has_perm_slug", None)):
        return {}
    if not user.has_perm_slug("view_support"):
        return {}
    from apps.support.models import HelpTicket
    from .scoping import scope
    qs = scope(HelpTicket.objects.filter(status="open"), request, "user__state_id")
    return {"open_ticket_count": qs.count()}
