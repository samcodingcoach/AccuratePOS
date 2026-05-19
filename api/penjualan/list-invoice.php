<?php
/**
 * API ENDPOINT - LIST SALES INVOICE (DENGAN CORE FILTER GLOBAL & PROTEKSI UTILS)
 * File: api/penjualan/list-invoice.php
 */

// 1. Muat konfigurasi dan class utama
require_once __DIR__ . '/../../bootstrap.php'; // [cite: 1]

// 2. Proteksi endpoint menggunakan file utils bawaan sistem Anda (Wajib login)
require_once __DIR__ . '/../../utils/api_auth.php'; // 

// 3. Set header agar output berupa JSON
header('Content-Type: application/json; charset=UTF-8');

try {
    $api = new AccurateAPI();
    
    // Tangkap parameter dari URL Request POS (Paginasi)
    // Kita lakukan maping pageSize dari parameter 'limit' yang dikirim oleh list-faktur.php
    $params = $_GET;
    if (isset($_GET['limit'])) {
        $params['sp.pageSize'] = (int)$_GET['limit'];
    }

    // Panggil fungsi getSalesInvoiceList yang sudah cerdas memilah filter di AccurateAPI.php
    $result = $api->getSalesInvoiceList($params);
    
    // 4. Format dan kembalikan response mengikuti struktur internal Accurate
    // Menggunakan pengecekan key 'success' sesuai standarisasi AccurateAPI Anda
    if (isset($result['success']) && $result['success']) {
        
        // Ambil data payload 'd' dari Accurate Cloud
        $invoiceData = $result['data']['d'] ?? $result['data'] ?? [];

        echo json_encode([
            'status' => 'success',
            'data'   => $invoiceData
        ], JSON_PRETTY_PRINT);

    } else {
        // Set HTTP code ke 400 (Bad Request) jika terjadi error dari Accurate Cloud
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => isset($result['error']) ? $result['error'] : 'Gagal mengambil data Faktur Penjualan'
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