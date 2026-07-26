# JaraMarket — Installation & Setup Guide

## Requirements
- PHP 8.2+
- MySQL 8.0+
- Composer
- Laravel 11

---

## 1. Install Dependencies

```bash
composer install
```

### Required packages (not in composer.json — add if missing):
```bash
composer require kreait/laravel-firebase
composer require yajra/laravel-datatables-oracle
```

---

## 2. Environment Setup

Copy and configure:
```bash
cp .env.example .env
php artisan key:generate
```

### Key `.env` values:
```env
APP_NAME=JaraMarket
APP_URL=https://yourdomain.com
DB_DATABASE=jaramarket
DB_USERNAME=root
DB_PASSWORD=

# Firebase (required for push notifications)
FIREBASE_PROJECT_ID=your-firebase-project-id
FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json

# Paystack
PAYSTACK_SECRET_KEY=sk_live_xxxx
PAYSTACK_PUBLIC_KEY=pk_live_xxxx
```

---

## 3. Database Setup

### Fresh install (new database):
```bash
php artisan migrate
php artisan db:seed
```

### Existing database (add new columns only):
```bash
php artisan migrate --path=database/migrations/2026_04_11_000001_add_admin_fields_to_users_table.php
php artisan migrate --path=database/migrations/2026_04_11_000002_create_user_permissions_table.php
php artisan db:seed --class=PermissionSeeder
```

---

## 4. Firebase Push Notifications

1. Go to [Firebase Console](https://console.firebase.google.com/) → Project Settings → Service Accounts
2. Click **Generate new private key** → download JSON
3. Save to: `storage/app/firebase/service-account.json`
4. Set in `.env`:
   ```env
   FIREBASE_PROJECT_ID=your-project-id
   FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json
   ```

The mobile app must send the FCM device token after login:
```
POST /api/jaram/fcm-token
Authorization: Bearer {token}
Body: { "token": "device_fcm_token_here" }
```

---

## 5. Default Admin Credentials

After seeding:

| Email | Password | Role |
|-------|----------|------|
| admin@jaramarket.com | Admin@123 | Super Admin |
| admin@gmail.com | admin | Admin (legacy) |

**Change these immediately after first login.**

---

## 6. Admin Role System

All users (admins, vendors, customers) use the **`users` table** with a `role` column.

| Role | Access |
|------|--------|
| `super_admin` | Full access — bypasses all permission checks |
| `state_admin` | All activities in assigned state |
| `vendor_manager` | Vendor DB, orders, logistics (no finance) |
| `accounts` | Wallet, transactions, withdrawals |
| `audit` | Read-only financial data |
| `logistics` | Orders and delivery only |
| `vendor` | Vendor app access |
| `customer` | Customer app access |

---

## 7. Bug Fixes Applied

### Orders (Vendor Side)
- `GET /api/jaram/vendor/orders` — Fixed: now paginated, filters by vendor categories
- `GET /api/jaram/vendor/orders/accepted` — Fixed: returns vendor's own accepted items

### Transaction History
- `GET /api/jaram/wallet/transactions` — **Now works**: queries `TransactionLog` for auth user
- Supports `?type=credit|debit&per_page=20`

### Withdrawals
- `POST /api/jaram/wallet/transfer-to-bank` — Now fires Firebase push + DB notification

---

## 8. Storage Link

```bash
php artisan storage:link
```

---

## 9. Queue Setup (for notifications)

```bash
# Process queued notifications
php artisan queue:work --daemon
```

Or add to supervisor/cron for production.
