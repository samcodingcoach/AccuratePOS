<?php
/**
 * API GUDANG - UPDATE
 * File: api/gudang/update.php
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

    // 3. Validasi id (Wajib untuk update)
    $id = isset($input['id']) ? trim($input['id']) : '';
    if ($id === '') {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Parameter "id" wajib diisi untuk melakukan update.'
        ]);
        exit;
    }

    // 4. Validasi name (Wajib)
    $name = isset($input['name']) ? trim($input['name']) : '';
    if ($name === '') {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Parameter "name" wajib diisi.'
        ]);
        exit;
    }

    // 5. Susun payload untuk dikirim ke Accurate
    $payload = [
        'id'   => $id,
        'name' => $name
    ];

    // Field String Opsional
    $optionalFields = ['pic', 'province', 'street'];
    foreach ($optionalFields as $field) {
        if (array_key_exists($field, $input)) {
            $payload[$field] = trim($input[$field]);
        }
    }

    // Field Boolean: scrapWarehouse (default true)
    if (isset($input['scrapWarehouse'])) {
        $payload['scrapWarehouse'] = filter_var($input['scrapWarehouse'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    } else {
        $payload['scrapWarehouse'] = 'true'; // Default true
    }

    // Field Boolean: suspended (default false)
    if (isset($input['suspended'])) {
        $payload['suspended'] = filter_var($input['suspended'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    } else {
        $payload['suspended'] = 'false'; // Default false
    }

    // 6. Eksekusi request ke AccurateAPI
    $api = new AccurateAPI();
    $result = $api->saveWarehouse($payload);

    if (isset($result['success']) && $result['success']) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Data gudang berhasil diperbarui',
            'data'    => $result['data']['d'] ?? null
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $result['error'] ?? 'Gagal memperbarui data gudang ke Accurate'
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
