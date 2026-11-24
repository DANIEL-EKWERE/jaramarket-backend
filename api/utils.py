"""Shared helpers — Laravel-style JSON envelopes + DRF exception handler."""
from rest_framework.response import Response
from rest_framework.views import exception_handler


def save_uploaded_file(uploaded_file, subfolder):
    """Upload a file to S3 and return the relative key (e.g.
    "products/<uuid>.jpg", "orders/<uuid>.aac") -- the same relative-path
    convention every pre-existing product/ingredient/advertisement image
    already uses, so _full_image_url() prepends the S3 bucket URL correctly
    on read.

    Falls back to local disk only when AWS credentials aren't configured
    (i.e. local dev) -- writing to local disk in production doesn't survive
    Render's ephemeral filesystem across deploys/instances.
    """
    import os
    import uuid
    from django.conf import settings

    ext = os.path.splitext(uploaded_file.name)[1]
    key = f"{subfolder}/{uuid.uuid4().hex}{ext}"

    if settings.AWS_STORAGE_BUCKET_NAME and settings.AWS_ACCESS_KEY_ID:
        import boto3
        s3 = boto3.client(
            "s3",
            aws_access_key_id=settings.AWS_ACCESS_KEY_ID,
            aws_secret_access_key=settings.AWS_SECRET_ACCESS_KEY,
            region_name=settings.AWS_DEFAULT_REGION,
        )
        uploaded_file.seek(0)
        s3.upload_fileobj(
            uploaded_file, settings.AWS_STORAGE_BUCKET_NAME, key,
            ExtraArgs={"ContentType": uploaded_file.content_type or "application/octet-stream"},
        )
        return key

    # Local dev fallback (no AWS_* configured): write to MEDIA_ROOT and
    # return an absolute URL so _full_image_url() doesn't re-prefix it.
    upload_dir = os.path.join(settings.MEDIA_ROOT, subfolder)
    os.makedirs(upload_dir, exist_ok=True)
    filename = os.path.basename(key)
    with open(os.path.join(upload_dir, filename), "wb") as f:
        for chunk in uploaded_file.chunks():
            f.write(chunk)
    return f"{settings.APP_URL}{settings.MEDIA_URL}{subfolder}/{filename}"


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
        code = None
        if isinstance(detail, dict) and "detail" in detail:
            message = str(detail["detail"])
            # DRF carries a machine-readable code on the ErrorDetail but drops
            # it when rendering. Pass it through: the apps need to tell
            # "signed in on another device" apart from "session expired",
            # because only one of them is worth explaining to the user.
            # Two shapes reach here: DRF puts the code on the ErrorDetail,
            # while SimpleJWT's DetailDictMixin lifts it to its own key. The
            # explicit key wins, since the ErrorDetail's code then holds only
            # the generic class default.
            code = detail.get("code") or getattr(detail["detail"], "code", None)
            errors = None
        response.data = {"status": False, "message": message}
        if code:
            response.data["code"] = str(code)
        if errors is not None:
            response.data["errors"] = errors
    return response
