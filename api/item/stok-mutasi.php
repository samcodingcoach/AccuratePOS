<?php
/**
 * API ENDPOINT - RIWAYAT MUTASI STOK BARANG
 * File: api/item/stok-mutasi.php
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

try {
    $api = new AccurateAPI();
    
    // Siapkan parameter array
    $params = array();
    
    // Mapping sp.pageSize dan sp.page dari parameter 'limit' dan 'page'
    if (isset($_GET['limit'])) {
        $params['sp.pageSize'] = (int)$_GET['limit'];
    }
    if (isset($_GET['page'])) {
        $params['sp.page'] = (int)$_GET['page'];
    }
    
    if (isset($_GET['type'])) {
        $params['filter.transactionType.op']  = 'EQUAL';
        $params['filter.transactionType.val'] = trim($_GET['type']);
    } elseif (isset($_GET['transactionType'])) {
        $params['filter.transactionType.op']  = 'EQUAL';
        $params['filter.transactionType.val'] = trim($_GET['transactionType']);
    }
    
    // Sisipkan parameter tambahan lainnya dari frontend (filter.no.op, dsb)
    foreach ($_GET as $key => $val) {
        if (!in_array($key, ['limit', 'page', 'type', 'transactionType'])) {
            $params[$key] = $val;
        }
    }
    
    // Panggil fungsi getStockMutationHistory di AccurateAPI
    $result = $api->getStockMutationHistory($params);
    
    if (isset($result['success']) && $result['success']) {
        // Ambil array d (data inti)
        $historyData = $result['data']['d'] ?? $result['data'] ?? [];
        $pagination = $result['data']['sp'] ?? [];
        
        echo json_encode([
            'status'     => 'success',
            'message'    => 'Data riwayat mutasi stok berhasil diambil',
            'data'       => $historyData,
            'pagination' => [
                'current_page' => $pagination['page'] ?? 1,
                'total_page'   => $pagination['pageCount'] ?? 1,
                'total_items'  => $pagination['rowCount'] ?? count($historyData),
                'has_more'     => $pagination['hasMore'] ?? false
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $result['message'] ?? 'Gagal mengambil riwayat mutasi stok dari Accurate',
            'error'   => $result['error'] ?? null
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>
