<?php
/**
 * Konfigurasi terpusat untuk aplikasi Accurate API
 * File ini berisi semua konfigurasi yang dibutuhkan aplikasi
 */

// Konfigurasi OAuth
define('OAUTH_CLIENT_ID', '49fb8a46-7571-483e-b9db-38ed89d5b144');
define('OAUTH_CLIENT_SECRET', '65e34d55759c2810fc31a9188f735474');
define('OAUTH_REDIRECT_URI', 'https://perdurably-defunctive-gauge.ngrok-free.dev/nuansa/callback.php');

// Konfigurasi API Accurate
define('ACCURATE_API_HOST', 'https://odin.accurate.id');
define('ACCURATE_AUTH_HOST', 'https://account.accurate.id');
define('ACCURATE_ACCESS_TOKEN', '7b401235-3ac7-41c4-8dd3-e59e0cea5a55');
define('ACCURATE_TOKEN_SCOPE', 'item_view branch_view vendor_view sales_order_delete warehouse_view sales_order_view sales_receipt_view sales_order_save customer_view glaccount_view');
define('ACCURATE_REFRESH_TOKEN', 'acdf802d-680c-45c1-9578-b3bc0d0d928f');
define('ACCURATE_SESSION_ID', '8e86344a-6f07-4ded-984f-71d712d467fb');
define('ACCURATE_DATABASE_ID', '2555193');

// Konfigurasi aplikasi
define('APP_NAME', 'Nuansa Accurate API');
define('APP_VERSION', '1.0.0');
define('DEFAULT_TIMEZONE', 'Asia/Jakarta');

// Set timezone default
date_default_timezone_set(DEFAULT_TIMEZONE);

// Konfigurasi error reporting untuk development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
