<?php
/**
 * File: api/penerimaan-jual/list-receipt.php
 * Deskripsi: Mengambil daftar Penerimaan Penjualan (Sales Receipt)
 */

// 1. Muat konfigurasi dan class utama
require_once __DIR__ . '/../../bootstrap.php';

// 2. Proteksi endpoint (Wajib login)
require_once __DIR__ . '/../../utils/api_auth.php';

// 3. Set header agar output berupa JSON
header('Content-Type: application/json; charset=UTF-8');

// Inisialisasi API
$api = new AccurateAPI();

// Tangkap parameter filter dan paging dari URL
$params = array();

if (isset($_GET['limit'])) {
    $params['sp.pageSize'] = (int)$_GET['limit'];
}
if (isset($_GET['page'])) {
    $params['sp.page'] = (int)$_GET['page'];
}
if (!empty($_GET['start_date'])) {
    $params['start_date'] = $_GET['start_date'];
}
if (!empty($_GET['end_date'])) {
    $params['end_date'] = $_GET['end_date'];
}
if (!empty($_GET['customerNo'])) {
    $params['customerNo'] = $_GET['customerNo'];
}
if (!empty($_GET['number'])) {
    $params['number'] = $_GET['number'];
}

// Panggil fungsi list penerimaan penjualan dari AccurateAPI.php
$result = $api->getSalesReceiptList($params);

// Format dan kembalikan response
if ($result['success']) {
    echo json_encode([
        'status' => 'success',
        'data' => isset($result['data']['d']) ? $result['data']['d'] : $result['data']
    ], JSON_PRETTY_PRINT);
} else {
    // Set HTTP code ke 400 jika terjadi error
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => isset($result['error']) ? $result['error'] : 'Gagal mengambil daftar penerimaan penjualan'
    ], JSON_PRETTY_PRINT);
}
?>
