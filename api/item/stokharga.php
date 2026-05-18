<?php
/**
 * API CONTROLLER - AGREGATOR STOK DAN HARGA BARANG (Fixed Warehouse Param)
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
    $priceParams = ['no' => $itemNo];
    if (!empty($priceCategoryName)) {
        $priceParams['priceCategoryName'] = $priceCategoryName;
    }

    $priceRes = $api->getSellingPrice($priceParams);
    
    $unitPrice = 0; 
    if ($priceRes['success'] && isset($priceRes['data'])) {
        $pData = $priceRes['data'];
        if (isset($pData['unitPrice'])) {
            $unitPrice = (float)$pData['unitPrice'];
        } elseif (isset($pData['d']['unitPrice'])) {
            $unitPrice = (float)$pData['d']['unitPrice'];
        }
    }

    // ==========================================
    // MEKANISME JEDA WAKTU 1 DETIK (ANTI-RATE LIMIT)
    // ==========================================
    sleep(1); 

    // ==========================================
    // HIT 2: AMBIL DATA STOK (getListStock)
    // ==========================================
    // PERBAIKAN: Parameter pertama dikosongkan ('') agar mencari di Semua Gudang, bukan diisi $itemNo!
    $stockRes = $api->getListStock(''); 
    
    $availableStock = 0;
    if ($stockRes['success'] && isset($stockRes['data'])) {
        $sData = $stockRes['data'];
        
        // Cek jika response Accurate berupa list array data ('d')
        if (isset($sData['d']) && is_array($sData['d'])) {
            foreach ($sData['d'] as $sItem) {
                // Cari data barang di dalam list yang nomornya cocok dengan $itemNo
                if (($sItem['no'] ?? '') === $itemNo || ($sItem['item_no'] ?? '') === $itemNo) {
                    $availableStock = (int)($sItem['availableStock'] ?? $sItem['quantity'] ?? 0);
                    break;
                }
            }
        } 
        // Jika response ternyata berupa satu objek langsung (fallback)
        elseif (isset($sData['availableStock'])) {
            $availableStock = (int)$sData['availableStock'];
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