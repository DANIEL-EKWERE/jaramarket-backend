"""State-level data scoping for the dashboard.

Most admin roles see the whole country. A state representative is a State
Admin for their own patch only, so every list they can reach is filtered to
the state(s) they represent. Keeping that in one place means a new list view
can opt in with a single call instead of re-deriving the rule.
"""


def scoped_states(request):
    """State ids this request is limited to. Empty list = unrestricted."""
    user = getattr(request, "user", None)
    return list(getattr(user, "scoped_state_ids", []) or [])


def scope(qs, request, state_path):
    """Filter `qs` to the request's states via `state_path`.

    `state_path` is the ORM path from the queryset's model to a state id,
    e.g. "address__state_id" for Order or "state_id" for User.
    """
    ids = scoped_states(request)
    if not ids:
        return qs
    return qs.filter(**{f"{state_path}__in": ids})


def is_scoped(request):
    return bool(scoped_states(request))


def ensure_in_scope(request, state_id):
    """404 a detail page whose record sits outside the request's states.

    List filtering alone isn't enough -- without this a scoped user could
    still open any record by guessing its URL.
    """
    from django.http import Http404

    ids = scoped_states(request)
    if ids and state_id not in ids:
        raise Http404("Not found")
