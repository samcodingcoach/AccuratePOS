<?php
/**
 * API SELLING PRICE ADJUSTMENT DETAIL
 * File: api/sellingprice-adjustment/detail.php
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../utils/api_auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// 1. Ambil Parameter Request (Bisa via id atau number sesuai dokumentasi)
$id     = isset($_GET['id']) ? trim($_GET['id']) : '';
$number = isset($_GET['number']) ? trim($_GET['number']) : '';

if ($id === '' && $number === '') {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Parameter id atau number wajib diisi salah satu.'
    ]);
    exit;
}

try {
    $api = new AccurateAPI();
    
    // 2. Panggil fungsi detail ke Accurate Cloud
    $result = $api->getSellingPriceAdjustmentDetail($id, $number);

    if ($result['success']) {
        // Kembalikan data detail mentah dari field 'd' Accurate Cloud
        echo json_encode([
            'status'  => 'success',
            'message' => 'Detail penyesuaian harga berhasil dimuat',
            'data'    => $result['data']['d'] ?? null,
            'meta'    => [
                'timestamp' => date('c'),
                'lookup_by' => $id !== '' ? 'id' : 'number'
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        throw new Exception($result['error'] ?? 'Gagal mengambil detail penyesuaian harga dari Accurate.');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>