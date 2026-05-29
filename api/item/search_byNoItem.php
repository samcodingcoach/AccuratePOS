<?php
/**
 * API ENDPOINT - DETAIL ITEM BERDASARKAN NO / SN
 * File: api/item/detail_NoItem.php
 * Endpoint Accurate: /search-by-item-or-sn.do
 */

// 1. Muat konfigurasi dan class utama
require_once __DIR__ . '/../../bootstrap.php';

// 2. Proteksi endpoint menggunakan utils bawaan (Wajib login / Token)
require_once __DIR__ . '/../../utils/api_auth.php';

// 3. Set header agar output berupa JSON
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Tolak selain GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_PRETTY_PRINT);
    exit;
}

try {
    $api = new AccurateAPI();

    // 4. Tangkap parameter 'no' dari URL (Misal: ?no=100012)
    $no = isset($_GET['no']) ? trim($_GET['no']) : '';

    // Validasi input
    if (empty($no)) {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Parameter Nomor/Kode Barang (no) wajib diisi'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    // 5. Panggil fungsi dari AccurateAPI.php
    $result = $api->searchItemOrSN($no);

    // 6. Format balasan jika sukses
    if (isset($result['success']) && $result['success']) {
        
        // Karena endpoint ini biasanya mengembalikan 1 object spesifik, kita langsung ambil 'data'
        $itemData = $result['data'] ?? null;

        echo json_encode([
            'status'  => 'success',
            'message' => 'Data detail barang berhasil dimuat',
            'data'    => $itemData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    } else {
        // Balasan jika Accurate menolak atau barang tidak ditemukan
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => isset($result['error']) ? $result['error'] : 'Gagal mengambil detail barang atau barang tidak ditemukan'
        ], JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>