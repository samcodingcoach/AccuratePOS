<?php
/**
 * API GET SELLING PRICE (RAW DATA)
 * File: api/item/getprice.php
 */

require_once __DIR__ . '/../../bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// 1. Kumpulkan parameter yang diizinkan sesuai dokumentasi gambar
$allowedParams = [
    'no', 
    'upcNo', 
    'branchName', 
    'currencyCode', 
    'discountCategoryName', 
    'effectiveDate', 
    'priceCategoryName'
];

$params = [];
foreach ($allowedParams as $key) {
    if (isset($_GET[$key]) && $_GET[$key] !== '') {
        $params[$key] = $_GET[$key];
    }
}

// Validasi minimal
if (empty($params['no']) && empty($params['upcNo'])) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Parameter no atau upcNo diperlukan.'
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    $api = new AccurateAPI();

    // 2. Panggil endpoint get-selling-price.do
    $result = $api->getSellingPrice($params);

    if ($result['success']) {
        // Tampilkan data mentah dari Accurate (isi field 'd')
        echo json_encode([
            'status'  => 'success',
            'message' => 'Data harga berhasil diambil',
            'data'    => $result['data']['d'],
            'meta'    => [
                'timestamp' => date('c'),
                'request_params' => $params
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        throw new Exception($result['error'] ?? 'Gagal mengambil harga dari Accurate');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}