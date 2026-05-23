<?php
/**
 * API CUSTOMER LIST - CLEAN, CENTRALIZED & UNIFORM OUTPUT
 * File: api/customer/list.php
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

    // Cukup kumpulkan parameter URL dan kirimkan langsung ke AccurateAPI
    $payload = [
        'sp.pageSize' => isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 25,
        'sp.page'     => isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1,
        'search'      => isset($_GET['search']) ? trim($_GET['search']) : '',
        'customerNo'  => isset($_GET['customerNo']) ? trim($_GET['customerNo']) : '',
        'name'        => isset($_GET['name']) ? trim($_GET['name']) : ''
    ];

    $result = $api->getCustomerList($payload);

    if (!$result['success']) {
        throw new Exception($result['error'] ?? 'Gagal mengambil data dari Accurate');
    }

    // Hasil dijamin seragam berisi: id, name, customerNo
    $rawCustomers = $result['data']['d'] ?? [];

    echo json_encode([
        'status'     => 'success',
        'message'    => 'Data pelanggan berhasil dimuat',
        'data'       => $rawCustomers,
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