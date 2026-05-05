<?php
/**
 * File: api/karyawan/list.php
 * Deskripsi: Mengambil daftar karyawan dari Accurate Online
 */

// 1. Muat konfigurasi dan class utama
require_once __DIR__ . '/../../bootstrap.php';

// 2. Proteksi endpoint (Hanya yang sudah login yang bisa akses)
require_once __DIR__ . '/../../utils/api_auth.php';

// 3. Set header agar output berupa JSON
header('Content-Type: application/json; charset=UTF-8');

// Inisialisasi API
$api = new AccurateAPI();

// Tangkap parameter dari URL (jika ada), beri nilai default jika kosong
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 100;

$params = [
    'sp.page' => $page,
    'sp.pageSize' => $pageSize
];

// Panggil fungsi dari AccurateAPI.php
$result = $api->getEmployeeList($params);

// Format dan kembalikan response
if ($result['success']) {
    echo json_encode([
        'status' => 'success',
        'data' => $result['data']['d']
    ], JSON_PRETTY_PRINT);
} else {
    // Set HTTP code ke 400 (Bad Request) jika gagal
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $result['error'] ? $result['error'] : 'Gagal mengambil data karyawan'
    ], JSON_PRETTY_PRINT);
}
?>