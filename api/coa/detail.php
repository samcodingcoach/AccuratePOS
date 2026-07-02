<?php
/**
 * API COA (Chart of Accounts) - DETAIL
 * File: api/coa/detail.php
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../utils/api_auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// 1. Pastikan method adalah GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method tidak diizinkan. Gunakan GET.'
    ]);
    exit;
}

// 2. Ambil parameter id (wajib) dan no (opsional)
$id = isset($_GET['id']) ? trim($_GET['id']) : '';
$no = isset($_GET['no']) ? trim($_GET['no']) : '';

if (empty($id)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Parameter "id" wajib diisi.'
    ]);
    exit;
}

try {
    // 3. Eksekusi request ke AccurateAPI
    $api = new AccurateAPI();
    $result = $api->getGLAccountDetail($id, $no);

    if (isset($result['success']) && $result['success']) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Detail akun perkiraan (COA) berhasil diambil',
            'data'    => $result['data']['d'] ?? null
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $result['error'] ?? 'Gagal mengambil detail akun perkiraan (COA) dari Accurate'
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
