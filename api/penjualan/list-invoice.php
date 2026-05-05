<?php
/**
 * File: api/sales-invoice/list.php
 * Deskripsi: Mengambil daftar Faktur Penjualan dari Accurate Online
 */

// 1. Muat konfigurasi dan class utama
require_once __DIR__ . '/../../bootstrap.php';

// 2. Proteksi endpoint (Wajib login)
require_once __DIR__ . '/../../utils/api_auth.php';

// 3. Set header agar output berupa JSON
header('Content-Type: application/json; charset=UTF-8');

// Inisialisasi API
$api = new AccurateAPI();

// Tangkap parameter dari URL (Paginasi)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 100;

// Tangkap filter tambahan jika Anda ingin mencari berdasarkan tanggal atau kata kunci
$keywords = isset($_GET['keywords']) ? $_GET['keywords'] : '';

$params = [
    'sp.page' => $page,
    'sp.pageSize' => $pageSize
];

// Jika ada pencarian kata kunci (misal: mencari nomor faktur atau nama pelanggan)
if (!empty($keywords)) {
    $params['keywords'] = $keywords;
}

// Panggil fungsi dari AccurateAPI.php
$result = $api->getSalesInvoiceList($params);

// Format dan kembalikan response
if ($result['success']) {
    echo json_encode([
        'status' => 'success',
        'data' => $result['data']['d']
    ], JSON_PRETTY_PRINT);
} else {
    // Set HTTP code ke 400 (Bad Request) jika terjadi error dari Accurate
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $result['error'] ? $result['error'] : 'Gagal mengambil data Faktur Penjualan'
    ], JSON_PRETTY_PRINT);
}
?>