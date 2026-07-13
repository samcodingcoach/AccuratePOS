<?php
/**
 * API VENDOR - DETAIL
 * File: api/vendor/detail.php
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../utils/api_auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method tidak diizinkan. Gunakan GET.'
    ]);
    exit;
}

$vendorNo = isset($_GET['vendorNo']) ? trim($_GET['vendorNo']) : '';

if (empty($vendorNo)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Parameter vendorNo wajib diisi'
    ]);
    exit;
}

try {
    $api = new AccurateAPI();
    $result = $api->getVendorDetail(null, $vendorNo);
    
    if (isset($result['success']) && $result['success']) {
        $data = $result['data']['d'] ?? [];
        
        echo json_encode([
            'status'  => 'success',
            'message' => 'Detail vendor berhasil diambil',
            'data'    => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $result['message'] ?? 'Gagal mengambil detail vendor',
            'error'   => $result['error'] ?? null
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan internal server.',
        'error'   => $e->getMessage()
    ]);
}
?>
