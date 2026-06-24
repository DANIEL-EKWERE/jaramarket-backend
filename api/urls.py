"""
Central URL router — mirrors routes/api.php (prefix `jaram`).

Domain routes are delegated to each Django app. Admin/report/compat routes
remain here, handled by the thin api/ layer.
"""
from django.urls import include, path

from . import admin_views as av
from . import compat_views as cv
from . import reports_views as rv

urlpatterns = [
    # ── Domain apps ──
    path("", include("apps.geo.urls")),
    path("", include("apps.accounts.urls")),
    path("", include("apps.catalogue.urls")),
    path("", include("apps.customers.urls")),
    path("", include("apps.orders.urls")),
    path("", include("apps.finance.urls")),
    path("", include("apps.support.urls")),

    # ── Admin / back-office ──
    path("admin/dashboard", av.dashboard),
    path("admin/finance/transactions", av.finance_transactions),
    path("admin/finance/wallets", av.finance_wallets),
    path("admin/finance/withdrawals", av.finance_withdrawals),
    path("admin/finance/users/<int:user_id>/transactions", av.finance_user_transactions),
    path("admin/vendors", av.vendors_list),
    path("admin/vendors/<int:vendor_id>", av.vendor_show),
    path("admin/admins", av.admins_collection),
    path("admin/admins/<int:admin_id>", av.admin_update),
    path("admin/users", av.users_list),
    path("admin/users/<int:user_id>", av.user_update),
    path("admin/users/<int:user_id>/toggle-status", av.user_toggle_status),
    path("admin/users/<int:user_id>/delete", av.user_destroy),
    path("admin/categories", av.category_create),
    path("admin/categories/<int:id>", av.category_detail),
    path("admin/products", av.product_create),
    path("admin/products/<int:id>", av.product_detail),
    path("admin/ingredients", av.ingredient_create),
    path("admin/ingredients/<int:id>", av.ingredient_detail),
    path("admin/advertisements", av.advertisements_collection),
    path("admin/advertisements/<int:id>", av.advertisement_detail),
    path("admin/commissions", av.commissions_collection),
    path("admin/commissions/<int:id>", av.commission_detail),

    # ── Admin reports & exports ──
    path("admin/reports/orders", rv.report_orders),
    path("admin/reports/products", rv.report_products),
    path("admin/reports/finance-summary", rv.report_finance_summary),
    path("admin/reports/payments", rv.report_payments),
    path("admin/reports/export/orders", rv.export_orders),
    path("admin/reports/export/payments", rv.export_payments),
    path("reports/orders", rv.report_orders),
    path("reports/payments", rv.report_payments),

    # ── Compat aliases ──
    path("payment/callback", cv.payment_callback),
    path("categories", cv.categories_root),
    path("categories/<int:id>", av.category_detail),
    path("users", av.users_list),
    path("users/<int:user_id>", av.user_update),
    path("users/<int:user_id>/toggle-status", av.user_toggle_status),
    path("fetchUserProfile/<str:email>", cv.fetch_user_profile_by_email),
    path("addresses/<int:address>", cv.address_detail),
    path("franchises", cv.franchises_collection),
    path("orders/<int:order>/receipt", cv.order_receipt),
    path("orders/<int:order>/track", cv.order_track),
    path("users/<int:user_id>/orders", cv.user_orders),
]
