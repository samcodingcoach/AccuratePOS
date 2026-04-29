<?php
/**
 * Konfigurasi terpusat untuk aplikasi Accurate API
 * File ini berisi semua konfigurasi yang dibutuhkan aplikasi
 */

// Konfigurasi OAuth
define('OAUTH_CLIENT_ID', 'c0f130ce-1e13-42a8-97a5-714d8e492b08');
define('OAUTH_CLIENT_SECRET', 'cc001a5f04678825fec7a52d553944a6');
define('OAUTH_REDIRECT_URI', 'https://perdurably-defunctive-gauge.ngrok-free.dev/nuansa/callback.php');

// Konfigurasi API Accurate
define('ACCURATE_API_HOST', 'https://odin.accurate.id');
define('ACCURATE_AUTH_HOST', 'https://account.accurate.id');
define('ACCURATE_ACCESS_TOKEN', 'cc0979c6-769e-4301-9d24-7eb870e554a2');
define('ACCURATE_TOKEN_SCOPE', 'item_view branch_view item_category_view vendor_view sales_order_delete warehouse_view sales_order_view sales_receipt_view purchase_order_view sales_order_save customer_view glaccount_view');
define('ACCURATE_REFRESH_TOKEN', '9ff95ac6-faab-42ad-b880-22b3ead27598');
define('ACCURATE_SESSION_ID', '0617b2a5-0b52-4995-8c72-fa2fb8ceefa2');
define('ACCURATE_DATABASE_ID', '2555193');

// Konfigurasi aplikasi
define('APP_NAME', 'POS Accurate API');
define('APP_VERSION', '1.0.0');
define('DEFAULT_TIMEZONE', 'Asia/Jakarta');

// Set timezone default
date_default_timezone_set(DEFAULT_TIMEZONE);

// Konfigurasi error reporting untuk development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
