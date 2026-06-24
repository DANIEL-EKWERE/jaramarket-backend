"""Celery application for Jaraman (queued jobs — the Laravel queue equivalent).

Defaults to EAGER mode (CELERY_TASK_ALWAYS_EAGER=True) so the project runs with
no broker. To run real async workers: set CELERY_TASK_ALWAYS_EAGER=False and a
CELERY_BROKER_URL in .env, then `celery -A jaraman worker -l info`.
"""
import os

from celery import Celery

os.environ.setdefault("DJANGO_SETTINGS_MODULE", "jaraman.settings")

app = Celery("jaraman")
app.config_from_object("django.conf:settings", namespace="CELERY")
app.autodiscover_tasks()
