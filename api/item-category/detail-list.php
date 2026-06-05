<?php
/**
 * API ITEM CATEGORY DETAIL - GET DATA
 * File: api/item-category/detail-list.php
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../utils/api_auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

try {
    $api = new AccurateAPI();

    // Wajib ada ID
    $id = isset($_GET['id']) ? trim($_GET['id']) : '';
    
    if (empty($id)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Parameter id wajib diisi'
        ]);
        exit;
    }

    // Eksekusi API
    $result = $api->getItemCategoryDetail($id);

    if (!$result['success']) {
        throw new Exception($result['error'] ?? 'Gagal mengambil detail kategori barang dari Accurate');
    }

    // Ambil data mentah
    $categoryData = $result['data']['d'] ?? null;

    if (!$categoryData) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Data tidak ditemukan',
            'data' => null
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // Output JSON
    echo json_encode([
        'status'  => 'success',
        'message' => 'Detail kategori barang berhasil dimuat',
        'data'    => $categoryData
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
