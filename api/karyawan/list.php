<?php
/**
 * File: api/karyawan/list.php
 * Deskripsi: Mengambil daftar karyawan dari Accurate Online dengan fitur pencarian dinamis
 */

// 1. Muat konfigurasi dan class utama
require_once __DIR__ . '/../../bootstrap.php';

// 2. Proteksi endpoint (Hanya yang sudah login yang bisa akses)
require_once __DIR__ . '/../../utils/api_auth.php';

// 3. Set header agar output berupa JSON
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

try {
    // Inisialisasi API
    $api = new AccurateAPI();

    // Tangkap parameter paginasi (mendukung 'limit' maupun 'pageSize')
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : (isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 100);
    $page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

    // Tangkap parameter filter / pencarian
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $number = isset($_GET['number']) ? trim($_GET['number']) : '';
    $id     = isset($_GET['id']) ? trim($_GET['id']) : '';
    $name   = isset($_GET['name']) ? trim($_GET['name']) : '';

    // Susun payload parameter
    $params = [
        'sp.page'     => $page,
        'sp.pageSize' => $limit,
        'search'      => $search,
        'number'      => $number,
        'id'          => $id,
        'name'        => $name
    ];

    // Panggil fungsi dari AccurateAPI.php
    $result = $api->getEmployeeList($params);

    if (!$result['success']) {
        throw new Exception($result['error'] ?? 'Gagal mengambil data karyawan');
    }

    $rawEmployees = $result['data']['d'] ?? [];

    // Format dan kembalikan response
    echo json_encode([
        'status'     => 'success',
        'message'    => 'Data karyawan berhasil dimuat',
        'data'       => $rawEmployees,
        'pagination' => [
            'current_page' => $page,
            'total_page'   => (int)($result['data']['sp']['pageCount'] ?? 0),
            'has_more'     => (bool)($result['data']['sp']['hasMore'] ?? false)
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(400); // 400 Bad Request
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>