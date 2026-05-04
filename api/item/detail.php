<?php
/**
 * API ITEM DETAIL (FIXED NESTED STRUCTURE)
 * File: api/item/detail.php
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../utils/api_auth.php';

header('Content-Type: application/json; charset=UTF-8');

$upcNo = isset($_GET['upcno']) ? trim($_GET['upcno']) : (isset($_GET['upc']) ? trim($_GET['upc']) : null);

if (!$upcNo) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Parameter upcno diperlukan']);
    exit;
}

try {
    $api = new AccurateAPI();

    // 1. Cari Barang
    $search = $api->getItemByUPC($upcNo);
    
    /**
     * Berdasarkan error Anda, strukturnya adalah:
     * $search['data']['d']['item']['id']
     */
    if ($search['success'] && isset($search['data']['d']['item']['id'])) {
        $itemId = $search['data']['d']['item']['id'];
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => 'error', 
            'message' => "Barang dengan Barcode $upcNo tidak ditemukan.",
            'accurate_response' => $search['data']['d'] ?? null
        ]);
        exit;
    }

    // 2. Ambil seluruh data mentah (Raw Data) berdasarkan ID
    $detailRes = $api->getItemDetail($itemId);

    if ($detailRes['success'] && isset($detailRes['data']['d'])) {
        echo json_encode([
            'status'  => 'success',
            'data'    => $detailRes['data']['d'],
            'meta'    => [
                'timestamp' => date('c'),
                'target_id' => $itemId,
                'upc_searched' => $upcNo
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        throw new Exception("Gagal mengambil detail dari Accurate untuk ID: $itemId");
    }

} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}