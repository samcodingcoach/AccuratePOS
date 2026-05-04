<?php
/**
 * API ITEM LIST - LIGHT VERSION (Optimized)
 * File: api/item/list.php
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

try {
    $api = new AccurateAPI();

    $limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 20;
    $page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

    $result = $api->getItemList($limit, $page);

    if (!$result['success']) {
        throw new Exception($result['error'] ?? 'Gagal fetch list barang');
    }

    $rawItems = $result['data']['d'] ?? [];
    $cleanItems = [];

    foreach ($rawItems as $i) {
        // Ambil detail hanya untuk mengekstrak data yang dibutuhkan
        $detailRes = $api->getItemDetail($i['id']);
        
        if ($detailRes['success'] && isset($detailRes['data']['d'])) {
            $d = $detailRes['data']['d'];
            
            // Ekstraksi data spesifik
            $balance = isset($d['detailWarehouseData'][0]['balance']) 
                       ? (float)$d['detailWarehouseData'][0]['balance'] 
                       : 0;

            $image = isset($d['detailItemImage'][0]['thumbnailPath']) 
                     ? $d['detailItemImage'][0]['thumbnailPath'] 
                     : null;

            // Susun struktur tanpa raw_detail
            $cleanItems[] = [
                'id'         => $d['id'],
                'item_no'    => $d['no'] ?? null,
                'name'       => $d['name'] ?? null,
                'barcode'    => $d['upcNo'] ?? null,
                'balance'    => $balance,
                'unit'       => $d['unit1Name'] ?? null,
                'price'      => (float)($d['unitPrice'] ?? 0),
                'image'      => $image
            ];
        }
    }

    echo json_encode([
        'status'  => 'success',
        'message' => 'Data barang berhasil dimuat',
        'data'    => $cleanItems,
        'pagination' => [
            'current_page' => (int)$page,
            'total_page'   => (int)($result['data']['sp']['pageCount'] ?? 0),
            'has_more'     => (bool)($result['data']['sp']['hasMore'] ?? false)
        ],
        'meta' => [
            'timestamp'   => date('c'),
            'api_version' => '1.8-light'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    logError("Item List Light Error: " . $e->getMessage(), __FILE__, __LINE__);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}