"""Public legal pages: privacy policy and terms of service.

These are the only unauthenticated HTML pages in the project, and they exist
for a hard requirement rather than for decoration: Google Play and the App
Store both refuse a submission without a publicly reachable privacy policy
URL. A copy of the text inside the app does not satisfy that -- the reviewer
has to be able to open it in a browser without signing in.

Mounted at the project root (/privacy-policy, /terms) rather than under
/dashboard/ so the URL is short enough to paste into a store listing and
carries no implication of being part of the admin area.
"""
from django.conf import settings
from django.shortcuts import render
from django.views.decorators.cache import cache_control
from django.views.decorators.http import require_GET


def _contact():
    """Contact block shown at the foot of both documents.

    Driven by settings so it can be corrected without a code change. The
    defaults matter more than usual here: production runs without a .env, so
    whatever is defaulted IS what gets published.
    """
    return {
        "company": getattr(settings, "LEGAL_COMPANY_NAME", "JaraMarket"),
        "legal_name": getattr(settings, "LEGAL_REGISTERED_NAME", ""),
        "email": getattr(settings, "LEGAL_CONTACT_EMAIL", ""),
        "privacy_email": getattr(settings, "LEGAL_PRIVACY_EMAIL", ""),
        "phone": getattr(settings, "LEGAL_CONTACT_PHONE", ""),
        "address": getattr(settings, "LEGAL_BUSINESS_ADDRESS", ""),
        "website": getattr(settings, "LEGAL_WEBSITE", ""),
        "effective": getattr(settings, "LEGAL_EFFECTIVE_DATE", ""),
    }


# Cached at the edge: the text changes a few times a year, and a store
# reviewer hitting a cold page is a needless risk.
@require_GET
@cache_control(public=True, max_age=3600)
def privacy_policy_view(request):
    # show_contact False: this document carries its own section 24, so the
    # base template must not append a second contact block.
    return render(request, "legal/privacy.html",
                  {"contact": _contact(), "show_contact": False})


@require_GET
@cache_control(public=True, max_age=3600)
def terms_view(request):
    return render(request, "legal/terms.html",
                  {"contact": _contact(), "show_contact": True})
