<?php
/**
 * File: api/profile/detail.php
 * Deskripsi: Mengambil detail database berdasarkan ID
 */

// 1. Muat konfigurasi dan class utama
require_once __DIR__ . '/../../bootstrap.php';

// 2. Proteksi endpoint (Wajib login)
require_once __DIR__ . '/../../utils/api_auth.php';

// 3. Set header agar output berupa JSON
header('Content-Type: application/json; charset=UTF-8');

// Inisialisasi API
$api = new AccurateAPI();

// Tangkap parameter ID dari URL
$id = isset($_GET['id']) ? $_GET['id'] : null;

if (empty($id)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Parameter id wajib diisi.'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Panggil fungsi getDatabaseDetail dari AccurateAPI.php
$result = $api->getDatabaseDetail($id);

// Format dan kembalikan response
if ($result['success']) {
    echo json_encode([
        'status' => 'success',
        'data' => isset($result['data']['d']) ? $result['data']['d'] : $result['data']
    ], JSON_PRETTY_PRINT);
} else {
    // Set HTTP code ke 400 jika terjadi error
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => isset($result['error']) ? $result['error'] : 'Gagal mengambil detail database'
    ], JSON_PRETTY_PRINT);
}
?>
