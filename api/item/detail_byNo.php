<?php
/**
 * API ENDPOINT - DETAIL ITEM BERDASARKAN NO BARANG
 * File: api/item/detail_byNo.php
 * Endpoint Accurate: /detail.do
 */

// 1. Muat konfigurasi dan class utama
require_once __DIR__ . '/../../bootstrap.php';

// 2. Proteksi endpoint (Wajib login / Token Bearer)
require_once __DIR__ . '/../../utils/api_auth.php';

// 3. Set header agar output berupa JSON
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Proteksi Method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_PRETTY_PRINT);
    exit;
}

try {
    $api = new AccurateAPI();

    // 4. Tangkap parameter 'no' dari URL
    $no = isset($_GET['no']) ? trim($_GET['no']) : '';

    // Validasi parameter wajib
    if (empty($no)) {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Parameter Nomor Barang (no) wajib diisi'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    // 5. Eksekusi request ke server Accurate menggunakan fungsi baru
    $result = $api->getItemDetailByNo($no);

    // 6. Format balasan jika sukses
    if (isset($result['success']) && $result['success']) {
        
        // Sesuai standar Accurate, detail item biasanya berada di dalam array 'd'
        // Kita tangkap 'd', atau jika strukturnya berbeda, ambil langsung dari 'data'
        $itemData = $result['d'] ?? $result['data'][0] ?? $result['data'] ?? null;

        echo json_encode([
            'status'  => 'success',
            'message' => 'Detail barang berhasil dimuat',
            'data'    => $itemData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => isset($result['error']) ? $result['error'] : 'Gagal mengambil detail barang'
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