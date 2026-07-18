-- Post-import verification for equivuxb_jaramarket_pg.sql
-- Run on the cPanel server after the import:
--   psql -U jewmqoeq_jaramarket-user -d jewmqoeq_jaramarket-db -f verify_import.sql
--
-- Every check below should show ok = true / MATCH / OK. Anything else
-- means something didn't land the way convert_to_postgres.py intended.

-- ============================================================
-- 1) Row counts: expected (from the converter's own output) vs actual
-- ============================================================
WITH expected(table_name, expected_count) AS (VALUES
    ('addresses', 8), ('admins', 1), ('advertisements', 1), ('banks', 205),
    ('bank_accounts', 1), ('categories', 18), ('category_product', 143),
    ('category_types', 2), ('category_user', 10), ('commissions', 6),
    ('countries', 238), ('help_tickets', 5), ('ingredients', 141),
    ('ingredient_product', 552), ('ingredient_state_prices', 3),
    ('lgas', 768), ('notifications', 142), ('orders', 31),
    ('order_items', 94), ('order_item_logs', 2), ('payment_logs', 26),
    ('permissions', 24), ('products', 145), ('product_state_prices', 61),
    ('settings', 29), ('states', 37), ('supports', 2),
    ('transaction_logs', 41), ('transfers', 3), ('uoms', 9),
    ('users', 18), ('user_otps', 7), ('user_permissions', 48),
    ('wallets', 27)
    -- users: 17 real + 1 synthetic placeholder "Deleted Account" (id=-1)
    -- category_user: intentionally 10, not 14 -- see category_user note below
),
actual(table_name, actual_count) AS (
    SELECT 'addresses', count(*) FROM addresses
    UNION ALL SELECT 'admins', count(*) FROM admins
    UNION ALL SELECT 'advertisements', count(*) FROM advertisements
    UNION ALL SELECT 'banks', count(*) FROM banks
    UNION ALL SELECT 'bank_accounts', count(*) FROM bank_accounts
    UNION ALL SELECT 'categories', count(*) FROM categories
    UNION ALL SELECT 'category_product', count(*) FROM category_product
    UNION ALL SELECT 'category_types', count(*) FROM category_types
    UNION ALL SELECT 'category_user', count(*) FROM category_user
    UNION ALL SELECT 'commissions', count(*) FROM commissions
    UNION ALL SELECT 'countries', count(*) FROM countries
    UNION ALL SELECT 'help_tickets', count(*) FROM help_tickets
    UNION ALL SELECT 'ingredients', count(*) FROM ingredients
    UNION ALL SELECT 'ingredient_product', count(*) FROM ingredient_product
    UNION ALL SELECT 'ingredient_state_prices', count(*) FROM ingredient_state_prices
    UNION ALL SELECT 'lgas', count(*) FROM lgas
    UNION ALL SELECT 'notifications', count(*) FROM notifications
    UNION ALL SELECT 'orders', count(*) FROM orders
    UNION ALL SELECT 'order_items', count(*) FROM order_items
    UNION ALL SELECT 'order_item_logs', count(*) FROM order_item_logs
    UNION ALL SELECT 'payment_logs', count(*) FROM payment_logs
    UNION ALL SELECT 'permissions', count(*) FROM permissions
    UNION ALL SELECT 'products', count(*) FROM products
    UNION ALL SELECT 'product_state_prices', count(*) FROM product_state_prices
    UNION ALL SELECT 'settings', count(*) FROM settings
    UNION ALL SELECT 'states', count(*) FROM states
    UNION ALL SELECT 'supports', count(*) FROM supports
    UNION ALL SELECT 'transaction_logs', count(*) FROM transaction_logs
    UNION ALL SELECT 'transfers', count(*) FROM transfers
    UNION ALL SELECT 'uoms', count(*) FROM uoms
    UNION ALL SELECT 'users', count(*) FROM users
    UNION ALL SELECT 'user_otps', count(*) FROM user_otps
    UNION ALL SELECT 'user_permissions', count(*) FROM user_permissions
    UNION ALL SELECT 'wallets', count(*) FROM wallets
)
SELECT e.table_name,
       e.expected_count,
       a.actual_count,
       CASE WHEN e.expected_count = a.actual_count THEN 'MATCH' ELSE '*** MISMATCH ***' END AS status
FROM expected e
JOIN actual a USING (table_name)
ORDER BY status DESC, e.table_name;

-- ============================================================
-- 1b) Placeholder "Deleted Account" user (id=-1) exists and is exactly
--     what 29 dangling FK references (orders/wallets/addresses/etc. whose
--     real owner was hard-deleted upstream, absent from this dump) now
--     point to instead of a nonexistent id.
-- ============================================================
SELECT id, firstname, lastname, email, is_active
FROM users WHERE id = -1;

SELECT 'orders -> placeholder' AS relation, count(*) AS rows_pointing_at_placeholder
FROM orders WHERE user_id = -1
UNION ALL
SELECT 'wallets -> placeholder', count(*) FROM wallets WHERE user_id = -1
UNION ALL
SELECT 'addresses -> placeholder', count(*) FROM addresses WHERE user_id = -1;

-- ============================================================
-- 2) Leading-zero phone/account numbers preserved as text
--    (the bug that used to silently strip these -- must show the
--    full string, not a shortened number with the 0 dropped)
-- ============================================================
SELECT 'admins.phone' AS check_name, phone AS value, phone = '07068628887' AS ok
FROM admins WHERE id = 1
UNION ALL
SELECT 'users.phone_number', phone_number, phone_number = '07068628887'
FROM users WHERE id = 1
UNION ALL
SELECT 'users.account_number', account_number, account_number = '7068628887'
FROM users WHERE id = 1;

-- ============================================================
-- 3) Booleans landed as real boolean true/false, not 0/1 ints
-- ============================================================
SELECT 'admins.is_active' AS check_name, is_active::text AS value, pg_typeof(is_active)::text AS pg_type
FROM admins WHERE id = 1
UNION ALL
SELECT 'users.is_active', is_active::text, pg_typeof(is_active)::text
FROM users WHERE id = 1
UNION ALL
SELECT 'users.is_verified', is_verified::text, pg_typeof(is_verified)::text
FROM users WHERE id = 1;

-- ============================================================
-- 4) No orphaned foreign keys (should all return 0)
-- ============================================================
SELECT 'addresses -> users' AS relation, count(*) AS orphans
FROM addresses a LEFT JOIN users u ON u.id = a.user_id WHERE a.user_id IS NOT NULL AND u.id IS NULL
UNION ALL
SELECT 'orders -> users', count(*)
FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.user_id IS NOT NULL AND u.id IS NULL
UNION ALL
SELECT 'order_items -> orders', count(*)
FROM order_items oi LEFT JOIN orders o ON o.id = oi.order_id WHERE oi.order_id IS NOT NULL AND o.id IS NULL
UNION ALL
SELECT 'wallets -> users', count(*)
FROM wallets w LEFT JOIN users u ON u.id = w.user_id WHERE w.user_id IS NOT NULL AND u.id IS NULL
UNION ALL
SELECT 'user_permissions -> users', count(*)
FROM user_permissions up LEFT JOIN users u ON u.id = up.user_id WHERE up.user_id IS NOT NULL AND u.id IS NULL
UNION ALL
SELECT 'category_product -> products', count(*)
FROM category_product cp LEFT JOIN products p ON p.id = cp.product_id WHERE cp.product_id IS NOT NULL AND p.id IS NULL
UNION ALL
SELECT 'ingredient_product -> ingredients', count(*)
FROM ingredient_product ip LEFT JOIN ingredients i ON i.id = ip.ingredient_id WHERE ip.ingredient_id IS NOT NULL AND i.id IS NULL;

-- ============================================================
-- 5) Identity sequences correctly resynced after explicit-id inserts
--    (must be >= current max(id), else the next Django .save() will
--    collide with an imported row and raise a duplicate-key error)
-- ============================================================
SELECT 'users' AS table_name,
       (SELECT max(id) FROM users) AS max_id,
       (SELECT last_value FROM pg_sequences WHERE schemaname || '.' || sequencename = pg_get_serial_sequence('users', 'id')) AS seq_last_value
UNION ALL
SELECT 'products',
       (SELECT max(id) FROM products),
       (SELECT last_value FROM pg_sequences WHERE schemaname || '.' || sequencename = pg_get_serial_sequence('products', 'id'))
UNION ALL
SELECT 'orders',
       (SELECT max(id) FROM orders),
       (SELECT last_value FROM pg_sequences WHERE schemaname || '.' || sequencename = pg_get_serial_sequence('orders', 'id'));
