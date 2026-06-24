"""Shared helpers — Laravel-style JSON envelopes + DRF exception handler."""
from rest_framework.response import Response
from rest_framework.views import exception_handler


def success(message="Success", data=None, status=200):
    """Mirror Laravel's response()->json(['status'=>true, ...])."""
    payload = {"status": True, "message": message}
    if data is not None:
        payload["data"] = data
    return Response(payload, status=status)


def error(message="Error", data=None, status=400):
    payload = {"status": False, "message": message}
    if data is not None:
        payload["errors"] = data
    return Response(payload, status=status)


def api_exception_handler(exc, context):
    """Wrap DRF errors in the {status, message, errors} envelope used by the app."""
    response = exception_handler(exc, context)
    if response is not None:
        detail = response.data
        message = "Request failed"
        errors = detail
        if isinstance(detail, dict) and "detail" in detail:
            message = str(detail["detail"])
            errors = None
        response.data = {"status": False, "message": message}
        if errors is not None:
            response.data["errors"] = errors
    return response
