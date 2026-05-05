<?php
/**
 * API WAREHOUSE LIST & DETAIL (ENRICHED)
 * File: api/warehouse/list.php
 * Deskripsi: Mengambil daftar gudang dan memperkaya data dengan memanggil getWarehouseDetail
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../utils/api_auth.php';

// 1. Set Standar Header
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Validasi HTTP Method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method tidak diizinkan. Gunakan GET.'
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    $api = new AccurateAPI();

    // 2. Sanitasi & Parameter Paginasi
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 25;
    $page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

    // 3. Request List Utama ke Accurate API
    $result = $api->getWarehouseList($limit, $page);

    if (!$result['success']) {
        throw new Exception($result['error'] ?? 'Gagal mengambil data warehouse dari Accurate');
    }

    $rawWarehouses = $result['data']['d'] ?? [];
    $enrichedWarehouses = [];

    // 4. Loop dan Tarik Detail untuk setiap Warehouse (Eager Loading)
    foreach ($rawWarehouses as $w) {
        $warehouseId = $w['id'];
        
        // Data default dari list
        $warehouseData = [
            'id'            => $w['id'],
            'name'          => $w['name'] ?? 'Tanpa Nama',
            'description'   => $w['description'] ?? '',
            'is_suspended'  => $w['suspended'] ?? false,
            'is_default'    => $w['defaultWarehouse'] ?? false,
            'is_scrap'      => $w['scrapWarehouse'] ?? false,
            'location_id'   => $w['locationId'] ?? null,
            'full_address'  => null, // Akan diisi dari detail
            'pic'           => null  // Akan diisi dari detail
        ];

        // Hit API Detail
        $detailRes = $api->getWarehouseDetail($warehouseId);
        
        if ($detailRes['success'] && isset($detailRes['data']['d'])) {
            $d = $detailRes['data']['d'];
            
            // Perkaya data dengan field yang biasanya hanya ada di detail
            $warehouseData['full_address'] = $d['address'] ?? ($d['description'] ?? '');
            $warehouseData['pic']          = $d['pic'] ?? null;
            
            // Tambahkan field lain jika Accurate menyediakannya di level detail
            if (isset($d['location'])) {
                $warehouseData['location_name'] = $d['location']['name'] ?? null;
            }
        }

        $enrichedWarehouses[] = $warehouseData;
    }

    // 5. Final Response Standar Global
    $response = [
        'status'  => 'success',
        'message' => 'Data warehouse berhasil dimuat dengan detail lengkap',
        'data'    => $enrichedWarehouses,
        'pagination' => [
            'current_page' => (int)$page,
            'per_page'     => (int)$limit,
            'total_page'   => (int)($result['data']['sp']['pageCount'] ?? 0),
            'has_more'     => (bool)($result['data']['sp']['hasMore'] ?? false)
        ],
        'meta' => [
            'timestamp'   => date('c'),
            'api_version' => '1.2',
            'request_id'  => uniqid('wh_det_'),
            'accurate_status' => $result['http_code'] ?? 200
        ]
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    // 6. Error Handling & Logging
    logError("Warehouse List/Detail API Error: " . $e->getMessage(), __FILE__, __LINE__);
    
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'data'    => null
    ], JSON_PRETTY_PRINT);
}