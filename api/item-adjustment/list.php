<?php
/**
 * API ITEM ADJUSTMENT - LIST
 * File: api/item-adjustment/list.php
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

$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$startDate = isset($_GET['startDate']) ? trim($_GET['startDate']) : (isset($_GET['start_date']) ? trim($_GET['start_date']) : '');
$endDate = isset($_GET['endDate']) ? trim($_GET['endDate']) : (isset($_GET['end_date']) ? trim($_GET['end_date']) : '');

$params = [];

if (!empty($search)) {
    $params['filter.keywords.op'] = 'EQUAL';
    $params['filter.keywords.val'] = $search;
}

if (!empty($startDate) && !empty($endDate)) {
    $params['filter.transDate.op'] = 'BETWEEN';
    $params['filter.transDate.val[0]'] = $startDate;
    $params['filter.transDate.val[1]'] = $endDate;
}

try {
    $api = new AccurateAPI();
    $result = $api->getItemAdjustmentList($params, $page, $limit);
    
    if (isset($result['success']) && $result['success']) {
        $data = $result['data']['d'] ?? [];
        $pagination = $result['data']['sp'] ?? [];
        
        echo json_encode([
            'status'  => 'success',
            'message' => 'Data item adjustment berhasil diambil',
            'data'    => $data,
            'pagination' => [
                'page'      => $pagination['page'] ?? $page,
                'pageSize'  => $pagination['pageSize'] ?? $limit,
                'pageCount' => $pagination['pageCount'] ?? 1,
                'rowCount'  => $pagination['rowCount'] ?? count($data)
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $result['message'] ?? 'Gagal mengambil data item adjustment',
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
