<?php
/**
 * API ITEM LIST STOCK WITH PAGINATION & SORTING
 * File: api/item/list-stok.php
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

// 1. Ambil Parameter Filter, Paginasi Dinamis, dan Set Default Limit ke 100
$warehouse = '';
if (isset($_GET['warehouse'])) {
    $warehouse = trim($_GET['warehouse']);
} elseif (isset($_GET['warehouse_name'])) {
    $warehouse = trim($_GET['warehouse_name']);
} elseif (isset($_GET['warehouseName'])) {
    $warehouse = trim($_GET['warehouseName']);
}

$limit = isset($_GET['limit']) ? max(1, min(250, (int)$_GET['limit'])) : 100;
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

try {
    $api = new AccurateAPI();
    
    // 2. Panggil fungsi dengan parameter paginasi lengkap
    $stockRes = $api->getListStock($warehouse, $limit, $page);

    if ($stockRes['success']) {
        $rawResponse = $stockRes['data'];
        
        // Output data mentah dari Accurate yang sudah otomatis terurut dan terpaginasi
        echo json_encode([
            'status'  => 'success',
            'message' => 'Data list stok berhasil diambil',
            'data'    => $rawResponse['d'] ?? [], 
            'pagination' => [
                'current_page' => (int)$page,
                'total_page'   => (int)($rawResponse['sp']['pageCount'] ?? 1),
                'total_items'  => (int)($rawResponse['sp']['rowCount'] ?? 0),
                'has_more'     => (bool)($rawResponse['sp']['hasMore'] ?? false)
            ],
            'meta'    => [
                'timestamp'      => date('c'),
                'warehouse_name' => $warehouse ?: 'Semua Gudang',
                'sorted_by'      => 'name ASC'
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        throw new Exception($stockRes['error'] ?? 'Gagal mengambil data stok dari Accurate');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}