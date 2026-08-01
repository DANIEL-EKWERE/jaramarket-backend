from django.urls import path

from .views import (
    auth, dashboard, finance, vendors, users, admins, catalogue, commissions,
    reports, orders, settings_views, profile, notifications, franchises, representatives,
    markets,
)

app_name = "webadmin"

urlpatterns = [
    path("login", auth.login_view, name="login"),
    path("logout", auth.logout_view, name="logout"),
    path("", dashboard.dashboard_view, name="dashboard"),

    # Orders
    path("orders", orders.orders_list_view, name="orders_list"),
    path("orders/<int:order_id>", orders.order_detail_view, name="order_detail"),
    path("orders/<int:order_id>/cancel", orders.order_cancel_view, name="order_cancel"),
    path("orders/<int:order_id>/complete", orders.order_complete_view, name="order_complete"),

    # Finance
    path("finance/transactions", finance.transactions_view, name="finance_transactions"),
    path("finance/wallets", finance.wallets_view, name="finance_wallets"),
    path("finance/withdrawals", finance.withdrawals_view, name="finance_withdrawals"),
    path("finance/users/<int:user_id>", finance.user_transactions_view, name="finance_user_transactions"),

    # Vendors
    path("vendors", vendors.vendors_list_view, name="vendors_list"),
    path("vendors/<int:vendor_id>", vendors.vendor_detail_view, name="vendor_detail"),
    path("vendors/<int:vendor_id>/orders", vendors.vendor_orders_view, name="vendor_orders"),
    path("vendors/<int:vendor_id>/toggle-status", vendors.vendor_toggle_status_view, name="vendor_toggle_status"),
    path("vendors/<int:vendor_id>/toggle-verification", vendors.vendor_toggle_verification_view, name="vendor_toggle_verification"),

    # Users
    path("users", users.users_list_view, name="users_list"),
    path("users/create", users.user_create_view, name="user_create"),
    path("users/<int:user_id>", users.user_detail_view, name="user_detail"),
    path("users/<int:user_id>/toggle-status", users.user_toggle_status_view, name="user_toggle_status"),
    path("users/<int:user_id>/delete", users.user_delete_view, name="user_delete"),

    # Admins
    path("admins", admins.admins_list_view, name="admins_list"),
    path("admins/create", admins.admin_create_view, name="admin_create"),
    path("admins/<int:admin_id>", admins.admin_update_view, name="admin_update"),
    path("admins/<int:admin_id>/toggle-status", admins.admin_toggle_status_view, name="admin_toggle_status"),
    path("admins/<int:admin_id>/reset-permissions", admins.admin_reset_permissions_view, name="admin_reset_permissions"),
    path("admins/<int:admin_id>/delete", admins.admin_delete_view, name="admin_delete"),

    # My profile
    path("profile", profile.profile_view, name="profile"),

    # Notifications
    path("notifications", notifications.notifications_list_view, name="notifications_list"),
    path("notifications/<uuid:notification_id>/read", notifications.notification_read_view, name="notification_read"),
    path("notifications/mark-all-read", notifications.notifications_mark_all_read_view, name="notifications_mark_all_read"),

    # Catalogue: categories
    path("categories", catalogue.categories_list_view, name="categories_list"),
    path("categories/create", catalogue.category_create_view, name="category_create"),
    path("categories/<int:id>", catalogue.category_update_view, name="category_update"),
    path("categories/<int:id>/delete", catalogue.category_delete_view, name="category_delete"),

    # Catalogue: products
    path("products", catalogue.products_list_view, name="products_list"),
    path("products/create", catalogue.product_create_view, name="product_create"),
    path("products/<int:id>", catalogue.product_update_view, name="product_update"),
    path("products/<int:id>/toggle-status", catalogue.product_toggle_status_view, name="product_toggle_status"),
    path("products/<int:id>/delete", catalogue.product_delete_view, name="product_delete"),

    # Catalogue: ingredients
    path("ingredients", catalogue.ingredients_list_view, name="ingredients_list"),
    path("ingredients/create", catalogue.ingredient_create_view, name="ingredient_create"),
    path("ingredients/<int:id>", catalogue.ingredient_update_view, name="ingredient_update"),
    path("ingredients/<int:id>/delete", catalogue.ingredient_delete_view, name="ingredient_delete"),

    # Catalogue: advertisements
    path("advertisements", catalogue.advertisements_list_view, name="advertisements_list"),
    path("advertisements/create", catalogue.advertisement_create_view, name="advertisement_create"),
    path("advertisements/<int:id>", catalogue.advertisement_update_view, name="advertisement_update"),
    path("advertisements/<int:id>/toggle-status", catalogue.advertisement_toggle_status_view, name="advertisement_toggle_status"),
    path("advertisements/<int:id>/delete", catalogue.advertisement_delete_view, name="advertisement_delete"),

    # Commissions
    path("commissions", commissions.commissions_list_view, name="commissions_list"),
    path("commissions/create", commissions.commission_create_view, name="commission_create"),
    path("commissions/<int:id>", commissions.commission_update_view, name="commission_update"),
    path("commissions/<int:id>/delete", commissions.commission_delete_view, name="commission_delete"),

    # Service fee tiers
    path("service-fee-tiers", commissions.service_fee_tiers_list_view, name="service_fee_tiers_list"),
    path("service-fee-tiers/create", commissions.service_fee_tier_create_view, name="service_fee_tier_create"),
    path("service-fee-tiers/<int:id>", commissions.service_fee_tier_update_view, name="service_fee_tier_update"),
    path("service-fee-tiers/<int:id>/delete", commissions.service_fee_tier_delete_view, name="service_fee_tier_delete"),

    # Franchises
    path("franchises", franchises.franchises_list_view, name="franchises_list"),
    path("franchises/create", franchises.franchise_create_view, name="franchise_create"),
    path("franchises/<int:id>", franchises.franchise_update_view, name="franchise_update"),
    path("franchises/<int:id>/delete", franchises.franchise_delete_view, name="franchise_delete"),

    # State representatives
    path("representatives", representatives.representatives_list_view, name="representatives_list"),
    path("representatives/create", representatives.representative_create_view, name="representative_create"),
    path("representatives/<int:id>", representatives.representative_update_view, name="representative_update"),
    path("representatives/<int:id>/toggle-status", representatives.representative_toggle_status_view, name="representative_toggle_status"),
    path("representatives/<int:id>/delete", representatives.representative_delete_view, name="representative_delete"),

    # Markets
    path("markets", markets.markets_list_view, name="markets_list"),
    path("markets/create", markets.market_create_view, name="market_create"),
    path("markets/<int:id>", markets.market_update_view, name="market_update"),
    path("markets/<int:id>/toggle-status", markets.market_toggle_status_view, name="market_toggle_status"),
    path("markets/<int:id>/delete", markets.market_delete_view, name="market_delete"),

    # Manual order-assignment queue (markets exhausted, needs a human)
    path("orders/manual-queue", orders.orders_manual_queue_view, name="orders_manual_queue"),
    path("orders/manual-queue/<int:item_id>/assign", orders.order_item_manual_assign_view, name="order_item_manual_assign"),

    # Settings
    path("settings", settings_views.settings_view, name="settings"),

    # Reports
    path("reports/orders", reports.orders_report_view, name="report_orders"),
    path("reports/products", reports.products_report_view, name="report_products"),
    path("reports/finance-summary", reports.finance_summary_report_view, name="report_finance_summary"),
    path("reports/payments", reports.payments_report_view, name="report_payments"),
]
