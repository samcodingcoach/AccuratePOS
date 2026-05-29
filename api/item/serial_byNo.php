<?php
/**
 * API ENDPOINT - SERIAL NUMBER PER WAREHOUSE
 * File: api/item/serial_byNo.php
 * Endpoint Accurate: /serial-number-per-warehouse.do
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

    // 4. Tangkap parameter dari URL (Mendukung '?itemNo=...' atau '?no=...')
    $itemNo = isset($_GET['itemNo']) ? trim($_GET['itemNo']) : (isset($_GET['no']) ? trim($_GET['no']) : '');

    // Validasi parameter wajib
    if (empty($itemNo)) {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Parameter Nomor Barang (itemNo / no) wajib diisi'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    // 5. Eksekusi request ke server Accurate
    $result = $api->getSerialNumberPerWarehouse($itemNo);

    // 6. Format balasan jika sukses
    if (isset($result['success']) && $result['success']) {
        
        // Tangkap data array dari response Accurate
        $serialData = $result['d'] ?? $result['data'] ?? [];

        echo json_encode([
            'status'  => 'success',
            'message' => 'Data Serial Number per gudang berhasil dimuat',
            'data'    => $serialData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => isset($result['error']) ? $result['error'] : 'Gagal mengambil data Serial Number'
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