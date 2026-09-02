"""
Notification send-side — the dispatch half of the Laravel Notification / Mail /
Firebase stack.

  * notify()           writes a row to the `notifications` table (uuid PK, morphs
                       notifiable, JSON data) exactly like Laravel's database channel.
  * send_email()       uses Django's mail backend (console by default; SMTP via env).
  * FirebasePush.send  structured FCM HTTP v1 call (no-op without credentials).
  * Termii (in services.py) covers SMS.

These mirror App\\Notifications\\* (OrderStatusNotification, WalletNotification,
OtpNotification, ...) and App\\Services\\Firebase\\FirebaseNotificationService.
"""
import json
import uuid

import requests
from django.conf import settings
from django.core.mail import send_mail
from django.utils import timezone

from apps.support.models import Notification

USER_TYPE = "App\\Models\\User"


def notify(user, type_name, data, channels=("database",)):
    """
    Create a notification for `user`. `type_name` mirrors the Laravel
    notification class name (stored in the `type` column); `data` is a dict
    serialised to the JSON `data` column.
    """
    results = {}
    if "database" in channels:
        results["notification"] = Notification.objects.create(
            id=uuid.uuid4(),
            type=f"App\\Notifications\\{type_name}",
            notifiable_type=USER_TYPE,
            notifiable_id=user.id,
            data=json.dumps(data),
            read_at=None,
        )
    if "mail" in channels and user.email:
        subject = data.get("title", "Jaramarket notification")
        body = data.get("message", "")
        results["mail"] = send_email(user.email, subject, body)
    if "fcm" in channels:
        # A push that goes nowhere used to leave no trace at all, which is
        # how a misconfigured server looked identical to a working one. Say
        # why it was dropped.
        if getattr(user, "fcm_token", None):
            results["fcm"] = FirebasePush().send(user.fcm_token, data.get("title", ""),
                                                 data.get("message", ""), data)
        else:
            import logging
            logging.getLogger(__name__).warning(
                "Push skipped for user %s (%s): no fcm_token registered on the "
                "account -- the app has not called /fcm-token since login.",
                user.id, user.email)
            results["fcm"] = {"skipped": True, "reason": "no fcm_token on user"}
    # Live WebSocket push (mirrors Laravel broadcast on the private user channel)
    results["broadcast"] = broadcast_to_user(user.id, data)
    return results


def send_email(to, subject, body, html=None):
    # EMAIL_TEST_REDIRECT reroutes every outbound mail to one inbox so
    # testing against real data can't spam real customers. Unset (the
    # production default) means normal delivery.
    redirect_to = getattr(settings, "EMAIL_TEST_REDIRECT", "")
    if redirect_to:
        subject = f"[to: {to}] {subject}"
        to = redirect_to
    try:
        send_mail(subject, body,
                  getattr(settings, "DEFAULT_FROM_EMAIL", "no-reply@jaraman.local"),
                  [to], html_message=html, fail_silently=False)
        return {"sent": True}
    except Exception as exc:
        import logging
        logging.getLogger(__name__).error("Email send failed to %s: %s", to, exc)
        return {"sent": False, "error": str(exc)}


# ── Convenience builders mirroring the Laravel notification classes ──

def order_status_notification(user, order, status):
    from .email_templates import order_status_email, order_cancelled_refund_email
    messages = {
        "pending":    f"Your order #{order.reference} has been placed successfully.",
        "processing": f"Your order #{order.reference} is now being prepared.",
        "completed":  f"Your order #{order.reference} has been delivered. Enjoy!",
        "cancelled":  f"Your order #{order.reference} has been cancelled.",
    }
    msg = messages.get(status, f"Your order #{order.reference} is now {status}.")
    result = notify(user, "OrderStatusNotification", {
        "type": "order_status", "title": f"Order {status.title()}",
        "message": msg, "order_id": str(order.id), "status": status,
    }, channels=("database", "fcm"))
    if status == "cancelled":
        html = order_cancelled_refund_email(user.firstname or user.email, order.reference, order.total)
        send_email(user.email, f"Order #{order.reference} Cancelled — Refund Processed", msg, html=html)
    else:
        html = order_status_email(user.firstname or user.email, order.reference, status, msg)
        send_email(user.email, f"Order #{order.reference} — {status.title()}", msg, html=html)
    return result


def order_item_status_notification(user, order_item, status):
    msg = f"An item in your order is now {status}."
    return notify(user, "OrderItemStatusNotification", {
        "type": "order_item_status", "title": "Item Update",
        "message": msg, "order_item_id": str(order_item.id), "status": status,
    }, channels=("database", "fcm"))


def _order_item_label(order_item):
    if order_item.ingredient_id and order_item.ingredient:
        return order_item.ingredient.name
    if order_item.product_id and order_item.product:
        return order_item.product.name
    return "An item"


def order_items_unavailable_notification(user, order, order_items):
    """Tell the customer which items can't be sourced so they can replace them.

    Deliberately ONE notification (and one email) covering every affected
    item in the order. Dispatch resolves items individually and recurses, so
    notifying per item meant a customer with a 9-item order got 9 pushes --
    several naming the same ingredient, because a food order repeats an
    ingredient across dishes.
    """
    from .email_templates import order_item_unavailable_email

    order_items = list(order_items)
    if not order_items:
        return None
    names = list(dict.fromkeys(_order_item_label(i) for i in order_items))

    if len(names) == 1:
        msg = f"{names[0]} is unavailable. Tap to choose a replacement."
    elif len(names) == 2:
        msg = f"{names[0]} and {names[1]} are unavailable. Tap to choose replacements."
    else:
        msg = (f"{names[0]}, {names[1]} and {len(names) - 2} more "
               f"{'item is' if len(names) - 2 == 1 else 'items are'} unavailable. "
               f"Tap to choose replacements.")

    result = notify(user, "OrderItemUnavailableNotification", {
        "type": "order_item_unavailable",
        "title": "Item Unavailable" if len(names) == 1 else "Items Unavailable",
        "message": msg, "order_id": str(order.id),
        # Kept for the single-item case so the app can deep-link straight to
        # the item; the app falls back to the order for a batch.
        "order_item_id": str(order_items[0].id) if len(order_items) == 1 else "",
        "order_item_ids": ",".join(str(i.id) for i in order_items),
        "item_count": str(len(names)),
        "status": "unavailable",
    }, channels=("database", "fcm"))

    if user.email:
        html = order_item_unavailable_email(user.firstname or user.email,
                                            order.reference, names)
        subject = ("Action needed: an item in order" if len(names) == 1
                   else "Action needed: items in order")
        send_email(user.email, f"{subject} #{order.reference}", msg, html=html)
    return result


def order_item_unavailable_notification(user, order_item):
    """Single-item convenience wrapper."""
    return order_items_unavailable_notification(user, order_item.order, [order_item])


def ticket_reply_notification(user, ticket, reply_text):
    """Support answered a ticket -- reach the customer wherever they are."""
    from .email_templates import ticket_reply_email

    preview = reply_text if len(reply_text) <= 120 else reply_text[:117] + "..."
    result = notify(user, "TicketReplyNotification", {
        "type": "ticket_reply", "title": "Support replied",
        "message": preview, "ticket_id": str(ticket.id),
        "subject": ticket.subject, "status": ticket.status,
    }, channels=("database", "fcm"))
    if user.email:
        html = ticket_reply_email(user.firstname or user.email, ticket.subject, reply_text)
        send_email(user.email, f"Re: {ticket.subject}", reply_text, html=html)
    return result


def wallet_notification(user, tx_type, amount, balance, reference, comment):
    from .email_templates import wallet_funded_email, wallet_debit_email, wallet_credit_email
    result = notify(user, "WalletNotification", {
        "type": f"wallet_{tx_type}", "title": "Wallet Update",
        "message": comment, "amount": str(amount), "balance": str(balance),
        "reference": reference,
    }, channels=("database", "fcm"))
    name = user.firstname or user.email
    if tx_type == "credit":
        subject = "Wallet Credited"
        html = wallet_credit_email(name, amount, balance, reference, comment)
    else:
        subject = "Wallet Debited"
        html = wallet_debit_email(name, amount, balance, reference, comment)
    send_email(user.email, subject, comment, html=html)
    return result


def payment_notification(user, amount, status, reference):
    from .email_templates import transfer_success_email, transfer_failed_email
    msg = f"Payment of ₦{amount} is {status}."
    result = notify(user, "PaymentNotification", {
        "type": "payment", "title": "Payment Update",
        "message": msg, "reference": reference, "status": status,
    }, channels=("database", "fcm"))
    name = user.firstname or user.email
    if status == "success":
        html = transfer_success_email(name, amount, reference)
        send_email(user.email, "Withdrawal Successful", msg, html=html)
    elif status == "failed":
        html = transfer_failed_email(name, amount, reference)
        send_email(user.email, "Withdrawal Failed — Refunded", msg, html=html)
    return result


def new_order_notification(vendor_user, order):
    from .email_templates import new_order_vendor_email
    items_count = order.items.count()
    msg = f"You have a new order #{order.reference} waiting for you."
    result = notify(vendor_user, "NewOrderNotification", {
        "type": "new_order", "title": "New Order!",
        "message": msg, "order_id": str(order.id),
    }, channels=("database", "fcm"))
    html = new_order_vendor_email(vendor_user.firstname or vendor_user.email,
                                  order.reference, items_count)
    send_email(vendor_user.email, f"New Order #{order.reference} Available", msg, html=html)
    if vendor_user.phone_number:
        from .services.sms import Termii
        Termii().send(vendor_user.phone_number,
                      f"Jaramarket: New order #{order.reference} is available. Open the app to accept it.")
    return result


def order_item_offer_notification(vendor_user, order_item, market):
    msg = f"A new order item is available for pickup at {market.name}. First to accept gets it."
    result = notify(vendor_user, "MarketOfferNotification", {
        "type": "market_offer", "title": "New Order Available!",
        "message": msg, "order_item_id": str(order_item.id), "market_id": str(market.id),
    }, channels=("database", "fcm"))
    if vendor_user.phone_number:
        from .services.sms import Termii
        Termii().send(vendor_user.phone_number,
                      f"Jaramarket: a new order is available at {market.name}. Open the app to accept it.")
    return result


def otp_notification(user_email, otp, purpose="verify your email"):
    from .email_templates import otp_email
    html = otp_email(otp, purpose)
    return send_email(user_email, "Your Jaramarket OTP", f"Your OTP is {otp}. It expires in 15 minutes.", html=html)


def welcome_notification(user):
    from .email_templates import welcome_email
    html = welcome_email(user.firstname or user.email)
    send_email(user.email, f"Welcome to Jaramarket, {user.firstname or ''}!".strip(),
               "Your account has been created successfully.", html=html)


def order_placed_notification(user, order):
    from .email_templates import order_placed_email
    items_count = order.items.count()
    msg = f"Your order #{order.reference} has been placed successfully."
    notify(user, "OrderPlacedNotification", {
        "type": "order_placed", "title": "Order Placed",
        "message": msg, "order_id": str(order.id), "status": "pending",
    }, channels=("database", "fcm"))
    html = order_placed_email(user.firstname or user.email, order.reference,
                              float(order.total), items_count)
    send_email(user.email, f"Order #{order.reference} Placed Successfully", msg, html=html)


class FirebasePush:
    """FCM sender using Firebase Admin SDK. No-op if FIREBASE_CREDENTIALS is not set."""
    _app = None

    @classmethod
    def _get_app(cls):
        if cls._app is not None:
            return cls._app
        creds_value = getattr(settings, "FIREBASE_CREDENTIALS", "")
        if not creds_value:
            return None
        try:
            import firebase_admin
            from firebase_admin import credentials as fb_creds
            import json, os
            # Accept either a file path or raw JSON string
            if os.path.isfile(creds_value):
                cred = fb_creds.Certificate(creds_value)
            else:
                cred = fb_creds.Certificate(json.loads(creds_value))
            try:
                cls._app = firebase_admin.get_app()
            except ValueError:
                cls._app = firebase_admin.initialize_app(cred)
            return cls._app
        except Exception as exc:
            import logging
            logging.getLogger(__name__).error("Firebase init failed: %s", exc)
            return None

    def send(self, token, title, body, data=None):
        if not token:
            return {"skipped": True}
        app = self._get_app()
        if app is None:
            import logging
            logging.getLogger(__name__).warning(
                "Push dropped: Firebase is not initialised (FIREBASE_CREDENTIALS "
                "= %r). Every notification is a no-op until this is fixed.",
                getattr(settings, "FIREBASE_CREDENTIALS", ""))
            return {"skipped": True, "reason": "FIREBASE_CREDENTIALS not configured"}
        try:
            from firebase_admin import messaging
            message = messaging.Message(
                notification=messaging.Notification(title=title, body=body),
                data={k: str(v) for k, v in (data or {}).items()},
                android=messaging.AndroidConfig(
                    priority="high",
                    notification=messaging.AndroidNotification(
                        channel_id="high_importance_channel",
                        priority="max",
                        default_sound=True,
                        default_vibrate_timings=True,
                    ),
                ),
                apns=messaging.APNSConfig(
                    payload=messaging.APNSPayload(
                        aps=messaging.Aps(sound="default", badge=1),
                    ),
                ),
                token=token,
            )
            result = messaging.send(message)
            return {"sent": True, "message_id": result}
        except Exception as exc:
            import logging
            logging.getLogger(__name__).error("FCM send failed: %s", exc)
            return {"sent": False, "error": str(exc)}

    def send_to_many(self, users, title, body, data=None):
        return [self.send(u.fcm_token, title, body, data) for u in users if getattr(u, "fcm_token", None)]


def broadcast_to_user(user_id, payload):
    """Push a payload to a user's private WS channel (user.{id}). No-op if the
    channel layer isn't configured/running, so it never breaks the request flow."""
    try:
        from asgiref.sync import async_to_sync
        from channels.layers import get_channel_layer
        layer = get_channel_layer()
        if layer is None:
            return {"skipped": True}
        async_to_sync(layer.group_send)(f"user.{user_id}", {"type": "notify", "payload": payload})
        return {"sent": True}
    except Exception as exc:  # pragma: no cover
        return {"sent": False, "error": str(exc)}
