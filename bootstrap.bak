<?php
/**
 * Bootstrap file untuk autoload dan inisialisasi aplikasi
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Autoload classes
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load config dan utils
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/utils.php';

// Contoh di bootstrap.php line 25
$logPath = __DIR__ . '/logs';
if (!is_dir($logPath)) {
    // Tambahkan parameter true untuk recursive mkdir
    mkdir($logPath, 0775, true); 
}
?>
