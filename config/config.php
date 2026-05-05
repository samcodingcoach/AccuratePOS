<?php
/**
 * File: config/config.php
 * Deskripsi: Konfigurasi sistem dan pengambilan kredensial API dari database
 */

// 1. Panggil file koneksi database
// Karena koneksi.php ada di luar folder config, kita gunakan __DIR__ . '/../'
require_once __DIR__ . '/koneksi.php';

// Pastikan kita mengambil variabel $conn dari file koneksi.php
global $conn; 

if (isset($conn)) {
    // 2. Query Mengambil Konfigurasi Accurate yang Aktif (aktif = 1)
    $query_config = "SELECT app_key, signature_secret, api_token FROM configs WHERE aktif = 1 ORDER BY id_config DESC LIMIT 1";
    
    // Menjalankan query menggunakan $conn
    $result_config = $conn->query($query_config);

    if ($result_config && $result_config->num_rows > 0) {
        // Jika data ditemukan di database
        $row = $result_config->fetch_assoc();
        
        define('ACCURATE_APP_KEY', $row['app_key']);
        define('ACCURATE_SIGNATURE_SECRET', $row['signature_secret']);
        define('ACCURATE_API_TOKEN', $row['api_token']);
    } else {
        // Fallback keamanan jika tabel kosong atau tidak ada yang aktif
        define('ACCURATE_APP_KEY', '');
        define('ACCURATE_SIGNATURE_SECRET', '');
        define('ACCURATE_API_TOKEN', '');
    }
} else {
    // Fallback jika file koneksi.php tidak ditemukan atau gagal dimuat
    define('ACCURATE_APP_KEY', '');
    define('ACCURATE_SIGNATURE_SECRET', '');
    define('ACCURATE_API_TOKEN', '');
    
    // (Opsional) Catat error ke sistem log Anda
    if (function_exists('logError')) {
        logError("File konfigurasi gagal dijalankan karena variabel \$conn dari database tidak ditemukan.", __FILE__, __LINE__);
    }
}
?>