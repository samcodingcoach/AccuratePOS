<?php
/**
 * File: api/profile/list.php
 * Deskripsi: Mengambil daftar database yang dapat diakses oleh API Token
 */

// 1. Muat konfigurasi dan class utama
require_once __DIR__ . '/../../bootstrap.php';

// 2. Proteksi endpoint (Wajib login)
require_once __DIR__ . '/../../utils/api_auth.php';

// 3. Set header agar output berupa JSON
header('Content-Type: application/json; charset=UTF-8');

// Inisialisasi API
$api = new AccurateAPI();

// Panggil fungsi getDatabaseList dari AccurateAPI.php
$result = $api->getDatabaseList();

// Format dan kembalikan response
if ($result['success']) {
    echo json_encode([
        'status' => 'success',
        // Struktur response API db-list.do biasanya data array di dalam 'd'
        'data' => isset($result['data']['d']) ? $result['data']['d'] : $result['data']
    ], JSON_PRETTY_PRINT);
} else {
    // Set HTTP code ke 400 jika terjadi error
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => isset($result['error']) ? $result['error'] : 'Gagal mengambil daftar database'
    ], JSON_PRETTY_PRINT);
}
?>
