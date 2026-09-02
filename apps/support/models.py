import uuid
from django.db import models
from api.base import TimestampedModel


class Support(TimestampedModel):
    STATUS_CHOICES = [("pending", "pending"), ("answered", "answered"), ("cancelled", "cancelled")]
    user = models.ForeignKey("accounts.User", on_delete=models.CASCADE,
                             db_column="user_id", related_name="supports")
    message = models.TextField()
    attachment = models.CharField(max_length=255, null=True, blank=True)
    status = models.CharField(max_length=20, choices=STATUS_CHOICES, default="pending")

    class Meta:
        db_table = "supports"

    def __str__(self):
        return f"{self.user} — {self.status}"


class HelpTicket(TimestampedModel):
    STATUS_CHOICES = [("open", "open"), ("in_progress", "in_progress"),
                      ("resolved", "resolved"), ("closed", "closed")]
    user = models.ForeignKey("accounts.User", on_delete=models.SET_NULL, null=True, blank=True,
                             db_column="user_id", related_name="help_tickets")
    subject = models.CharField(max_length=255)
    message = models.TextField()
    attachment = models.CharField(max_length=255, null=True, blank=True)
    status = models.CharField(max_length=20, choices=STATUS_CHOICES, default="open")

    class Meta:
        db_table = "help_tickets"

    def __str__(self):
        return f"{self.subject} ({self.status})"


class TicketReply(TimestampedModel):
    """One message in a support conversation.

    The ticket itself carries only the customer's opening message, so without
    this there is nowhere for an answer to live -- an admin could change the
    status but never actually say anything.
    """
    ticket = models.ForeignKey(HelpTicket, on_delete=models.CASCADE,
                               db_column="ticket_id", related_name="replies")
    # Null when the staff account is later deleted -- the reply itself must
    # survive, it is part of the customer's record.
    author = models.ForeignKey("accounts.User", on_delete=models.SET_NULL, null=True, blank=True,
                               db_column="author_id", related_name="ticket_replies")
    message = models.TextField()
    # Distinguishes support's answers from the customer's own follow-ups
    # without having to reason about who the author happened to be.
    is_staff = models.BooleanField(default=False)

    class Meta:
        db_table = "help_ticket_replies"
        ordering = ["created_at"]
        verbose_name_plural = "Ticket replies"

    def __str__(self):
        who = "Support" if self.is_staff else (self.author or "Customer")
        return f"{who} on #{self.ticket_id}: {self.message[:40]}"


class Advertisement(TimestampedModel):
    TYPE_CHOICES = [("discount", "discount"), ("off", "off"), ("info", "info")]
    STATUS_CHOICES = [("active", "active"), ("stop", "stop")]
    type = models.CharField(max_length=20, choices=TYPE_CHOICES)
    value = models.DecimalField(max_digits=10, decimal_places=2, null=True, blank=True)
    ingredient_ids = models.JSONField(null=True, blank=True)
    status = models.CharField(max_length=20, choices=STATUS_CHOICES, default="active")
    image = models.CharField(max_length=255)

    class Meta:
        db_table = "advertisements"

    def __str__(self):
        return f"{self.type} {self.value or ''} ({self.status})".replace("  ", " ")


class Setting(TimestampedModel):
    key = models.CharField(max_length=255, unique=True)
    value = models.TextField(null=True, blank=True)

    class Meta:
        db_table = "settings"

    def __str__(self):
        return self.key


class Notification(models.Model):
    id = models.UUIDField(primary_key=True, default=uuid.uuid4)
    type = models.CharField(max_length=255)
    notifiable_type = models.CharField(max_length=255)
    notifiable_id = models.BigIntegerField()
    data = models.TextField()
    read_at = models.DateTimeField(null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True, null=True)
    updated_at = models.DateTimeField(auto_now=True, null=True)

    class Meta:
        db_table = "notifications"

    def __str__(self):
        return f"{self.type} -> {self.notifiable_type} #{self.notifiable_id}"
