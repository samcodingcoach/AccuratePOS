<?php
/**
 * API AKSES - DETAIL (Access Privilege Detail)
 * File: api/akses/detail.php
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../utils/api_auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method tidak diizinkan. Gunakan GET.'
    ]);
    exit;
}

$id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($id)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Parameter ID wajib diisi.'
    ]);
    exit;
}

try {
    $api = new AccurateAPI();
    $result = $api->getAccessPrivilegeDetail($id);
    
    if (isset($result['success']) && $result['success']) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Detail hak akses berhasil diambil',
            'data'    => $result['data']['d'] ?? null
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $result['message'] ?? 'Gagal mengambil detail hak akses',
            'error'   => $result['error'] ?? null
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan internal server.',
        'error'   => $e->getMessage()
    ]);
}
?>
