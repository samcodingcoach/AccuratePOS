<?php
/**
 * API ITEM CATEGORY LIST - GET DATA
 * File: api/item-category/list.php
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

    // Tangkap parameter
    $payload = [
        'sp.pageSize' => isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 25,
        'sp.page'     => isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1
    ];

    if (isset($_GET['search'])) {
        $payload['search'] = $_GET['search'];
    }

    // Eksekusi API
    $result = $api->getItemCategoryList($payload);

    if (!$result['success']) {
        throw new Exception($result['error'] ?? 'Gagal mengambil data kategori barang dari Accurate');
    }

    // Ambil data mentah
    $rawCategories = $result['data']['d'] ?? [];

    $filteredCategories = [];
    foreach ($rawCategories as $category) {
        $lvl = 1;
        if (isset($category['nameWithIndent'])) {
            // Hitung kemunculan '&nbsp;' (setiap 4 '&nbsp;' berarti tambah 1 level)
            $lvl = (int)(substr_count($category['nameWithIndent'], '&nbsp;') / 4) + 1;
        } elseif (isset($category['lvl'])) {
            $lvl = (int)$category['lvl'];
        }

        $filteredCategories[] = [
            'id'   => $category['id'] ?? null,
            'name' => $category['name'] ?? '',
            'lvl'  => $lvl
        ];
    }

    // Output JSON Standar dengan data yang sudah disaring
    echo json_encode([
        'status'     => 'success',
        'message'    => 'Data kategori barang berhasil dimuat',
        'data'       => $filteredCategories,
        'pagination' => [
            'current_page' => (int)$payload['sp.page'],
            'total_page'   => (int)($result['data']['sp']['pageCount'] ?? 0),
            'has_more'     => (bool)($result['data']['sp']['hasMore'] ?? false)
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
