<?php
/**
 * API STOK OPNAME ORDER - DETAIL
 * File: api/stokopname-order/detail.php
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../utils/api_auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method tidak diizinkan. Gunakan GET.'
    ]);
    exit;
}

$id = isset($_GET['id']) ? trim($_GET['id']) : null;
$number = isset($_GET['number']) ? trim($_GET['number']) : null;

if (empty($id) && empty($number)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Parameter id atau number wajib diisi'
    ]);
    exit;
}

try {
    $api = new AccurateAPI();
    $result = $api->getStockOpnameOrderDetail($id, $number);
    
    if (isset($result['success']) && $result['success']) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Detail stok opname order berhasil diambil',
            'data'    => $result['data']['d'] ?? ($result['data'] ?? [])
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $result['message'] ?? 'Gagal mengambil detail stok opname order',
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
