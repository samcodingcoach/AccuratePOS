<?php
/**
 * API STOK OPNAME ORDER - LIST
 * File: api/stokopname-order/list.php
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

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$transDate = isset($_GET['transDate']) ? trim($_GET['transDate']) : '';

$params = [];

if (!empty($search)) {
    $params['filter.keywords.op'] = 'EQUAL';
    $params['filter.keywords.val'] = [$search];
}

if (!empty($transDate)) {
    $params['filter.transDate.op'] = 'EQUAL';
    $params['filter.transDate.val'] = $transDate;
}

try {
    $api = new AccurateAPI();
    $result = $api->getStockOpnameOrderList($params, $page, $limit);
    
    if (isset($result['success']) && $result['success']) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Data stok opname order berhasil diambil',
            'data'    => $result['data']['d'] ?? [],
            'pagination' => $result['data']['sp'] ?? []
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $result['message'] ?? 'Gagal mengambil data stok opname',
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
