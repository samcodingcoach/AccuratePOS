<?php
/**
 * API ITEM CATEGORY - SAVE
 * File: api/item-category/save.php
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../utils/api_auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 1. Pastikan method adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method tidak diizinkan. Gunakan POST.'
    ]);
    exit;
}

try {
    // 2. Ambil payload
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    // 3. Validasi
    $name = isset($input['name']) ? trim($input['name']) : '';
    if ($name === '') {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Parameter "name" wajib diisi.'
        ]);
        exit;
    }

    // 4. Susun payload untuk dikirim ke Accurate
    $payload = [
        'name' => $name
    ];

    if (isset($input['defaultCategory'])) {
        $payload['defaultCategory'] = filter_var($input['defaultCategory'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }

    if (array_key_exists('parentName', $input)) {
        $payload['parentName'] = trim($input['parentName']);
    }

    // 5. Eksekusi request ke AccurateAPI
    $api = new AccurateAPI();
    $result = $api->saveItemCategory($payload);

    if (isset($result['success']) && $result['success']) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Kategori barang berhasil disimpan',
            'data'    => $result['data']['d'] ?? null
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $result['error'] ?? 'Gagal menyimpan kategori barang ke Accurate'
        ], JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
