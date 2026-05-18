<?php
/**
 * API CONTROLLER - AGREGATOR STOK DAN HARGA BARANG (Dengan Jeda Anti-Rate Limit)
 * File: api/item/stokharga.php
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

// 1. Ambil & Validasi Parameter Input dari URL Browser
$itemNo            = isset($_GET['no']) ? trim($_GET['no']) : '';
$priceCategoryName = isset($_GET['priceCategoryName']) ? trim($_GET['priceCategoryName']) : '';

if (empty($itemNo)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Parameter nomor barang (no) wajib diisi.'
    ]);
    exit;
}

try {
    $api = new AccurateAPI();

    // ==========================================
    // HIT 1: AMBIL DATA HARGA (getSellingPrice)
    // ==========================================
    $priceParams = [
        'no' => $itemNo
    ];
    if (!empty($priceCategoryName)) {
        $priceParams['priceCategoryName'] = $priceCategoryName;
    }

    $priceRes = $api->getSellingPrice($priceParams);
    
    $unitPrice = 0; 
    if ($priceRes['success'] && isset($priceRes['data'])) {
        $unitPrice = (float)($priceRes['data']['unitPrice'] ?? 0);
    }


    // ==========================================
    // MEKANISME JEDA WAKTU (ANTI-RATE LIMIT)
    // ==========================================
    // Menunda eksekusi baris kode di bawahnya selama 1 detik setelah respons HIT 1 selesai.
    sleep(1); 


    // ==========================================
    // HIT 2: AMBIL DATA STOK (getListStock)
    // ==========================================
    $stockRes = $api->getListStock($itemNo); 
    
    $availableStock = 0;
    if ($stockRes['success'] && isset($stockRes['data']['d'])) {
        $itemsStockData = $stockRes['data']['d'];
        
        if (is_array($itemsStockData)) {
            foreach ($itemsStockData as $sItem) {
                if (($sItem['no'] ?? '') === $itemNo || ($sItem['item_no'] ?? '') === $itemNo) {
                    $availableStock = (int)($sItem['availableStock'] ?? $sItem['quantity'] ?? 0);
                    break;
                }
            }
        }
    }

    // 4. CETAK OUTPUT FINAL GABUNGAN
    echo json_encode([
        'status'  => 'success',
        'message' => 'Data gabungan harga dan stok berhasil dimuat dengan jeda aman',
        'data'    => [
            'no'             => $itemNo,
            'unitPrice'      => $unitPrice,
            'availableStock' => $availableStock
        ],
        'meta'    => [
            'timestamp'          => date('c'),
            'price_category_used'=> $priceCategoryName ?: 'Default / Umum',
            'delay_applied'      => '1 second'
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Gagal menggabungkan data: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>