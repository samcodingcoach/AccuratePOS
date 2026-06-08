<?php
/**
 * API PENJUALAN DELETE INVOICE
 * File: api/penjualan/delete-invoice.php
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../utils/api_auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

try {
    $api = new AccurateAPI();

    // 1. Ambil input data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }
    
    // Fallback ambil dari $_GET jika request via url (untuk method DELETE tanpa body)
    if (empty($input)) {
        $input = $_GET;
    }

    $number = isset($input['number']) ? trim($input['number']) : '';

    if ($number === '') {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Parameter number wajib diisi'
        ]);
        exit;
    }

    // 2. Eksekusi API
    $result = $api->deleteSalesInvoice($number);

    if (!$result['success']) {
        throw new Exception($result['error'] ?? 'Gagal menghapus faktur penjualan dari Accurate');
    }

    // 3. Output JSON
    echo json_encode([
        'status'  => 'success',
        'message' => 'Faktur penjualan berhasil dihapus',
        'data'    => [
            'number' => $number
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
