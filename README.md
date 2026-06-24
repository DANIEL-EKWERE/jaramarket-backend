# Jaraman — Django / DRF port

A Django + Django REST Framework port of the Jaraman Laravel marketplace
(food / recipe ordering + vendor system). Built as a drop-in replacement:
the database tables, column names, password hashing, and API route surface
match the original so it can run against the **same database**.

## What's here

```
jaraman/         project config (settings, urls, wsgi/asgi)
api/
  models.py      all ~45 tables (full schema), custom User, roles/permissions
  serializers.py DRF serializers (≈ Laravel API Resources)
  services.py    business logic: auth/OTP, orders, wallet, Paystack/Flutterwave/Termii
  views.py       endpoints mirroring routes/api.php (jaram/ prefix)
  urls.py        URL map
  admin.py       Django admin = drop-in for the Blade admin panel
  management/commands/seed_permissions.py
```

## Quick start (SQLite, for trying it out)

```bash
python -m venv venv && source venv/bin/activate
pip install -r requirements.txt          # skip mysqlclient/psycopg2 if only using sqlite
cp .env.example .env                      # set a real SECRET_KEY
python manage.py migrate
python manage.py seed_permissions
python manage.py createsuperuser
python manage.py runserver
```

API base: `http://127.0.0.1:8000/jaram/...` (also under `/api/jaram/...`).
Admin panel: `http://127.0.0.1:8000/admin/`.

## Running against the EXISTING Laravel database (drop-in)

1. In `.env` set `DB_CONNECTION=mysql` and point `DB_*` at the live database.
2. **Do not** run `migrate` against it — the tables already exist. Use Django's
   `--fake` if you want migration state recorded:
   `python manage.py migrate --fake`
3. Passwords already work: settings use bcrypt as the default hasher, so existing
   Laravel `bcrypt` hashes verify directly (confirmed in testing).

## Media files

Laravel served uploads from `public/`. Drop the contents of your `public.zip`
into `./public/` — `MEDIA_URL=/storage/` and `MEDIA_ROOT=./public` are configured
to match the original storage paths.

## Auth

JWT via `djangorestframework-simplejwt`. `POST jaram/login` returns
`access_token` + `refresh_token`; send `Authorization: Bearer <access_token>`.
Refresh at `POST jaram/token/refresh`.

## Endpoint coverage

The full `routes/api.php` surface is wired: public geo, guest auth + OTP,
profile, wallet (balance / transactions / transfer-to-bank), payments init +
verify + webhook, settings, customer orders, vendor orders (available / accepted
/ item / decision), support tickets, favourites, addresses, catalogue
(categories, products, ingredients, uom, adverts), and PIN set/verify.

## Status — what is complete vs. needs a further pass

**Complete & verified runnable (end-to-end tested)**
- Entire data model (all tables, soft-deletes, decimal precision, polymorphic
  columns, location-based price resolution LGA->State->Default).
- Custom User + roles/permissions matching `UserPermissionsEnum`.
- JWT auth + OTP register/validate/login + **forgot/reset password**.
- **Full order money-path**: wallet-balance check, wallet debit on order,
  commission resolution (threshold -> lowest slab -> banded lookup over the
  `commissions` table), vendor_amount + referral split per line item, vendor
  accept -> order `processing`, and **QA-complete crediting vendors + referrers**
  via the transaction-log ledger. (Verified: 10% commission on a 4000 order ->
  vendor credited 3600, referrer credited 200.)
- Vendor order flow (available filtered by category, accepted, item, decision).
- Wallet (balance / transactions / transfer-to-bank), **transfers list**,
  **payments list + show**, settings.
- **Notifications** (index / mark-read / unread-count), **vendor categories sync**,
  **foods create** (with ingredient composition), **PIN** set/verify/validate/
  clear/request-reset/reset.
- Paystack webhook wired to a handler that credits the wallet on `charge.success`.
- Django admin registered for every model.

**Structured but needs live credentials + testing**
- Paystack / Flutterwave / Termii are real HTTP integrations but need API keys
  and sandbox testing; webhook **signature verification** is still a stub.

**Admin / back-office API (jaram/admin/..., role + permission gated) — DONE**
- Dashboard stats (orders by status, revenue, customers, vendors, top products).
- Finance: transactions, wallets (+summary), withdrawals, per-user transactions.
- Vendor management: list (state/category/status filters) + detail with stats.
- Admin & user management: list/create admin, update, toggle-status, soft-delete.
- Catalogue CRUD: categories, products, ingredients, advertisements.
- Commission CRUD. (All verified; non-admin requests correctly receive 403.)

**Cart, reports & notifications — DONE**
- Cart: list / add / show / update-item / remove-item (ported to the real
  cart_items schema; line + cart totals computed from product price).
- Admin reports: orders (with daily breakdown + status split), products,
  finance summary, payments — plus **CSV exports** for orders and payments
  (correct text/csv + attachment headers).
- Notification send-side: order placement / processing / completion and wallet
  debits now **write to the notifications table**; OTP + password-reset codes are
  **emailed** (Django console backend by default, SMTP via .env). Firebase push
  and Termii SMS are structured and fire when credentials are set.

**Queued jobs & webhook security — DONE**
- Celery is wired (`jaraman/celery.py`, `api/tasks.py`). Defaults to EAGER so
  jobs run inline with no broker; set `CELERY_TASK_ALWAYS_EAGER=False` + a broker
  and run `celery -A jaraman worker -l info` for real async processing
  (the TransactionStatusUpdateJob equivalent + queued email/push).
- **Paystack webhook signature verification** (HMAC-SHA512 over the raw body,
  constant-time compare) with full event handling: charge.success credits the
  wallet, transfer.success/failed update the Transfer (failed also refunds),
  charge.failed is logged. (All verified.)

**WebSockets & data seeders — DONE**
- Django Channels WebSocket layer: `ws/user?token=<JWT>` authenticates via JWT and
  subscribes to a private per-user channel (`user.{id}`), mirroring Laravel's
  `App.Models.User.{id}` broadcast channel. `notify()` now pushes live over the
  socket in addition to writing the DB row. In-memory layer by default (no broker);
  set `CHANNEL_REDIS_URL` for multi-process. Run with an ASGI server:
  `daphne jaraman.asgi:application`.
- `python manage.py seed_data` seeds Nigeria + 37 states + ~734 LGAs, category
  types, categories, UOMs, ingredients, and products (from the Laravel seeders).

**Remaining**
- PDF (as opposed to CSV) report exports.
- Live payment-gateway / push validation against sandbox credentials + a real DB
  import — code is in place but needs an integration environment to certify.

## Running the Celery worker (optional, for async jobs)

```bash
# 1) start a broker (e.g. redis), then in .env set CELERY_TASK_ALWAYS_EAGER=False
celery -A jaraman worker -l info
```
Without this, everything still works — jobs just execute inline within the request.

**Known fidelity note**
- `transaction_logs.amount` is stored in the smallest unit (kobo, x100) for
  consistency with the model's `amount_major` accessor and the `Info` aggregate
  scope. The original `TransactionLogService` stored raw naira there (a latent
  inconsistency); if importing existing rows, account for this.

## Flutter client (`jara_vendor`) compatibility

Audited the uploaded client against this backend and aligned the contract:

- **Base path** `/(api/)jaram/...` — both mounted, matches the client.
- **Auth** — `Authorization: Bearer <token>`; login returns a **flat** `data`
  object with `token`, `token_type`, `expires_in`, `wallet{id,balance}`,
  `has_pin`, `email_verified`, `referrer_id`, etc. — exactly the keys the client's
  `LoginData`/profile models parse. (Originally returned nested `{user, access_token}`;
  fixed.) Verified by parsing the response with the client's field map.
- **Register** accepts the client's `country_id` + `role` fields.
- **`/lgas?state=<name>`** — accepts a state *name* (the client's convention) or id.
- **Aliases added** for the paths the client calls: `categories`, `categories/{id}`,
  `users`, `users/{id}`, `users/{id}/toggle-status`, `reports/orders`,
  `reports/payments`, `carts/{id}`, `wallets/fund`, `edit-user-profile/{email}`,
  `fetchUserProfile/{email}`, `addresses/{id}` (GET+PUT), `payments/callback`.

**Previously-missing client endpoints — now built & tested:**
- `GET /franchises` (+ POST create), `GET /orders/{id}/receipt` (itemised +
  financial summary), `GET /orders/{id}/track` (per-item status + timeline from
  order_item_logs), `GET /users/{id}/orders` (admin/self), and
  `GET /fetch-ProductCategory` (alias of categories-all-products). All return the
  standard {status, message, data} envelope; receipt/track/user-orders are
  access-controlled (owner / assigned vendor / admin only).
