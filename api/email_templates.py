"""HTML email builder for all transactional emails."""

BRAND_COLOR = "#2E7D32"
BRAND_NAME = "Jaramarket"


def _base(title, content_html):
    return f"""<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>{title}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;max-width:600px;width:100%;">
      <!-- Header -->
      <tr>
        <td style="background:{BRAND_COLOR};padding:24px 32px;text-align:center;">
          <h1 style="color:#ffffff;margin:0;font-size:24px;letter-spacing:1px;">{BRAND_NAME}</h1>
        </td>
      </tr>
      <!-- Body -->
      <tr>
        <td style="padding:32px;">
          {content_html}
        </td>
      </tr>
      <!-- Footer -->
      <tr>
        <td style="background:#f9f9f9;padding:16px 32px;text-align:center;border-top:1px solid #eeeeee;">
          <p style="color:#999999;font-size:12px;margin:0;">
            &copy; {BRAND_NAME}. You received this email because you have an account with us.<br/>
            If you did not request this, please ignore or contact support.
          </p>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body>
</html>"""


def _h2(text):
    return f'<h2 style="color:#333333;margin:0 0 16px 0;font-size:20px;">{text}</h2>'


def _p(text):
    return f'<p style="color:#555555;font-size:15px;line-height:1.6;margin:0 0 12px 0;">{text}</p>'


def _box(content, color="#f0f7f0", border="#2E7D32"):
    return f"""
<table width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;">
  <tr>
    <td style="background:{color};border-left:4px solid {border};padding:16px 20px;border-radius:4px;">
      {content}
    </td>
  </tr>
</table>"""


def _button(text, url="#"):
    return f"""
<table cellpadding="0" cellspacing="0" style="margin:24px 0;">
  <tr>
    <td style="background:{BRAND_COLOR};border-radius:6px;padding:12px 28px;">
      <a href="{url}" style="color:#ffffff;text-decoration:none;font-size:15px;font-weight:bold;">{text}</a>
    </td>
  </tr>
</table>"""


def _row(label, value):
    return f"""
<tr>
  <td style="padding:8px 0;color:#888888;font-size:14px;width:40%;">{label}</td>
  <td style="padding:8px 0;color:#333333;font-size:14px;font-weight:bold;">{value}</td>
</tr>"""


def _table(*rows):
    inner = "".join(rows)
    return f'<table width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;border-top:1px solid #eeeeee;border-bottom:1px solid #eeeeee;">{inner}</table>'


# ── Welcome / Registration ────────────────────────────────────────────────────

def welcome_email(firstname):
    body = (
        _h2(f"Welcome to {BRAND_NAME}, {firstname}! 🎉")
        + _p("Your account has been created successfully. We're excited to have you on board.")
        + _p("You can now browse fresh ingredients, place orders, and enjoy seamless delivery right to your doorstep.")
        + _p("If you have any questions, our support team is always here to help.")
        + _p(f"Welcome to the {BRAND_NAME} family!")
    )
    return _base("Welcome to Jaramarket", body)


# ── OTP ───────────────────────────────────────────────────────────────────────

def otp_email(otp, purpose="verify your email"):
    body = (
        _h2("Your One-Time Password")
        + _p(f"Use the OTP below to {purpose}. It expires in <strong>15 minutes</strong>.")
        + _box(
            f'<p style="margin:0;font-size:36px;font-weight:bold;color:{BRAND_COLOR};letter-spacing:8px;text-align:center;">{otp}</p>',
            color="#f0f7f0", border=BRAND_COLOR
        )
        + _p("If you did not request this OTP, please ignore this email and secure your account.")
    )
    return _base("Your Jaramarket OTP", body)


# ── Orders ────────────────────────────────────────────────────────────────────

def order_placed_email(firstname, order_ref, total, items_count):
    body = (
        _h2("Order Placed Successfully!")
        + _p(f"Hi {firstname}, your order has been received and is being processed.")
        + _table(
            _row("Order Reference", f"#{order_ref}"),
            _row("Total Amount", f"₦{total:,.2f}"),
            _row("Items", str(items_count)),
            _row("Status", "Pending"),
        )
        + _p("You will receive a notification once a vendor picks up your order.")
    )
    return _base("Order Placed", body)


def order_status_email(firstname, order_ref, status, message=None):
    status_colors = {
        "processing": ("#1565C0", "#e3f2fd"),
        "completed":  (BRAND_COLOR, "#f0f7f0"),
        "cancelled":  ("#c62828", "#ffebee"),
        "pending":    ("#e65100", "#fff3e0"),
    }
    color, bg = status_colors.get(status, ("#333333", "#f9f9f9"))
    body = (
        _h2(f"Order {status.title()}")
        + _p(f"Hi {firstname}, your order status has been updated.")
        + _table(
            _row("Order Reference", f"#{order_ref}"),
            _row("New Status", f'<span style="color:{color};font-weight:bold;">{status.upper()}</span>'),
        )
        + (f'<p style="color:#555555;font-size:15px;margin:12px 0;">{message}</p>' if message else "")
        + _p("Open the Jaramarket app to view your order details.")
    )
    return _base(f"Order {status.title()}", body)


def order_cancelled_refund_email(firstname, order_ref, amount):
    body = (
        _h2("Order Cancelled — Refund Processed")
        + _p(f"Hi {firstname}, your order has been cancelled and a refund has been credited to your wallet.")
        + _table(
            _row("Order Reference", f"#{order_ref}"),
            _row("Refund Amount", f"₦{amount:,.2f}"),
            _row("Refund Destination", "Jaramarket Wallet"),
        )
        + _p("Your wallet balance has been updated. You can use it for future orders.")
    )
    return _base("Order Cancelled — Refund", body)


def new_order_vendor_email(vendor_name, order_ref, items_count):
    body = (
        _h2("You Have a New Order!")
        + _p(f"Hi {vendor_name}, a customer just placed an order that matches your categories.")
        + _table(
            _row("Order Reference", f"#{order_ref}"),
            _row("Items", str(items_count)),
        )
        + _p("Open the Jaramarket vendor app to view and accept the order before another vendor picks it up.")
    )
    return _base("New Order Available", body)


# ── Wallet ────────────────────────────────────────────────────────────────────

def wallet_funded_email(firstname, amount, balance, reference):
    body = (
        _h2("Wallet Funded Successfully")
        + _p(f"Hi {firstname}, your Jaramarket wallet has been credited.")
        + _table(
            _row("Amount Credited", f"₦{float(amount):,.2f}"),
            _row("New Balance", f"₦{float(balance):,.2f}"),
            _row("Reference", str(reference)),
        )
        + _p("You can now use your wallet balance to place orders on Jaramarket.")
    )
    return _base("Wallet Funded", body)


def wallet_debit_email(firstname, amount, balance, reference, reason):
    body = (
        _h2("Wallet Debited")
        + _p(f"Hi {firstname}, your Jaramarket wallet has been debited.")
        + _table(
            _row("Amount Debited", f"₦{float(amount):,.2f}"),
            _row("New Balance", f"₦{float(balance):,.2f}"),
            _row("Reason", reason),
            _row("Reference", str(reference)),
        )
        + _p("If you did not authorise this transaction, please contact support immediately.")
    )
    return _base("Wallet Debited", body)


def wallet_credit_email(firstname, amount, balance, reference, reason):
    body = (
        _h2("Wallet Credited")
        + _p(f"Hi {firstname}, your Jaramarket wallet has been credited.")
        + _table(
            _row("Amount Credited", f"₦{float(amount):,.2f}"),
            _row("New Balance", f"₦{float(balance):,.2f}"),
            _row("Reason", reason),
            _row("Reference", str(reference)),
        )
    )
    return _base("Wallet Credited", body)


# ── Payments / Transfers ──────────────────────────────────────────────────────

def transfer_initiated_email(firstname, amount, bank_name, account_number):
    body = (
        _h2("Withdrawal Initiated")
        + _p(f"Hi {firstname}, your withdrawal request is being processed.")
        + _table(
            _row("Amount", f"₦{float(amount):,.2f}"),
            _row("Bank", str(bank_name or "—")),
            _row("Account Number", str(account_number)),
            _row("Status", "Pending"),
        )
        + _p("You will be notified once the transfer is completed.")
    )
    return _base("Withdrawal Initiated", body)


def transfer_success_email(firstname, amount, reference):
    body = (
        _h2("Withdrawal Successful ✓")
        + _p(f"Hi {firstname}, your withdrawal has been completed successfully.")
        + _table(
            _row("Amount", f"₦{float(amount):,.2f}"),
            _row("Reference", str(reference)),
            _row("Status", '<span style="color:#2E7D32;font-weight:bold;">SUCCESS</span>'),
        )
    )
    return _base("Withdrawal Successful", body)


def transfer_failed_email(firstname, amount, reference):
    body = (
        _h2("Withdrawal Failed")
        + _p(f"Hi {firstname}, your withdrawal could not be completed. Your wallet has been refunded.")
        + _table(
            _row("Amount", f"₦{float(amount):,.2f}"),
            _row("Reference", str(reference)),
            _row("Status", '<span style="color:#c62828;font-weight:bold;">FAILED</span>'),
        )
        + _p("The amount has been returned to your Jaramarket wallet. Please try again or contact support.")
    )
    return _base("Withdrawal Failed — Refunded", body)
