"""
Queued jobs (Celery tasks) — equivalents of Laravel's queued jobs/notifications.

Under the default EAGER config these run inline (no broker needed). With a broker
+ worker they run asynchronously, matching the original queue behaviour:
  * process_paystack_webhook  <- TransactionStatusUpdateJob / HandlePaystackWebhookService
  * send_email_task           <- queued Mail
  * send_push_task            <- queued Firebase push
"""
from celery import shared_task


@shared_task(name="api.process_paystack_webhook", bind=True, max_retries=3)
def process_paystack_webhook(self, payload):
    from .services import HandlePaystackWebhookService
    try:
        return HandlePaystackWebhookService().handle(payload)
    except Exception as exc:  # retry with backoff, like a failed queue job
        raise self.retry(exc=exc, countdown=10)


@shared_task(name="api.send_email")
def send_email_task(to, subject, body, html=None):
    from .notifications import send_email
    return send_email(to, subject, body, html)


@shared_task(name="api.send_push")
def send_push_task(token, title, body, data=None):
    from .notifications import FirebasePush
    return FirebasePush().send(token, title, body, data)
