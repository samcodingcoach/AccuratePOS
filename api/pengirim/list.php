<?php
/**
 * API PENGIRIMAN LIST - GET DATA SHIPMENT (Only ID & Name)
 * File: api/pengirim/list.php
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

    // Hanya tangkap parameter paginasi
    $payload = [
        'sp.pageSize' => isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 25,
        'sp.page'     => isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1
    ];

    // Eksekusi API
    $result = $api->getShipmentList($payload);

    if (!$result['success']) {
        throw new Exception($result['error'] ?? 'Gagal mengambil data pengiriman dari Accurate');
    }

    // Ambil data mentah
    $rawShipments = $result['data']['d'] ?? [];

    // ==============================================================
    // FILTER: Hanya ambil 'id' dan 'name'
    // ==============================================================
    $filteredShipments = [];
    foreach ($rawShipments as $shipment) {
        $filteredShipments[] = [
            'id'   => $shipment['id'] ?? null,
            'name' => $shipment['name'] ?? ''
        ];
    }

    // Output JSON Standar dengan data yang sudah disaring
    echo json_encode([
        'status'     => 'success',
        'message'    => 'Data pengiriman berhasil dimuat',
        'data'       => $filteredShipments,
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