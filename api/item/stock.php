<?php
/**
 * API ITEM STOCK (RAW DATA)
 * File: api/item/stock.php
 * Deskripsi: Mengambil data stok mentah berdasarkan nomor barang (no)
 */

require_once __DIR__ . '/../../bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// 1. Ambil Parameter
$itemNo = isset($_GET['no']) ? trim($_GET['no']) : null;
$warehouse = isset($_GET['warehouseName']) ? trim($_GET['warehouseName']) : '';

if (!$itemNo) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Parameter nomor barang (?no=...) diperlukan'
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    $api = new AccurateAPI();

    // 2. Panggil Endpoint /get-stock.do
    $stockRes = $api->getItemStock($itemNo, $warehouse);

    if ($stockRes['success']) {
        // Tampilkan data mentah dari field 'd'
        echo json_encode([
            'status'  => 'success',
            'message' => 'Data stok mentah berhasil diambil',
            'data'    => $stockRes['data']['d'], // Ini adalah nilai mentah dari Accurate
            'meta'    => [
                'timestamp'      => date('c'),
                'item_no'        => $itemNo,
                'warehouse_name' => $warehouse ?: 'Semua Gudang'
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