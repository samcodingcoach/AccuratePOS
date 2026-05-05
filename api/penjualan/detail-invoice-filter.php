<?php
/**
 * File: api/sales-invoice/detail-invoice.php
 * Deskripsi: Menampilkan faktur penjualan berdasarkan filter, dan memotong field agar ringan
 */

// 1. Muat konfigurasi dan class utama
require_once __DIR__ . '/../../bootstrap.php';

// 2. Proteksi endpoint (Wajib login)
require_once __DIR__ . '/../../utils/api_auth.php';

// 3. Set header agar output berupa JSON
header('Content-Type: application/json; charset=UTF-8');

// Inisialisasi API
$api = new AccurateAPI();

// Tangkap parameter filter dari URL
$customerNo = isset($_GET['customerNo']) ? trim($_GET['customerNo']) : '';
$fromDate   = isset($_GET['fromDate']) ? trim($_GET['fromDate']) : '';
$toDate     = isset($_GET['toDate']) ? trim($_GET['toDate']) : '';
$page       = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$pageSize   = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 100;

// Susun parameter yang akan dikirim ke Accurate
$params = array(
    'sp.page' => $page,
    'sp.pageSize' => $pageSize
);

if (!empty($customerNo)) {
    $params['customerNo'] = $customerNo;
}
if (!empty($fromDate)) {
    $params['fromDate'] = $fromDate; 
}
if (!empty($toDate)) {
    $params['toDate'] = $toDate; 
}

// Panggil fungsi dari AccurateAPI.php
$result = $api->getSalesInvoiceDetailFiltered($params);

// Format dan kembalikan response
if ($result['success']) {
    $rawData = isset($result['data']['d']) ? $result['data']['d'] : [];
    
    // ========================================================================
    // PROSES FILTERING: Buang data yang tidak diperlukan agar JSON ringan
    // ========================================================================
    
    // Tentukan field apa saja yang ingin dipertahankan
    $allowedFields = [
        'transDate', 'invoiceTime', 'dueDate', 'paymentTermId', 
        'number', 'subTotal', 'salesAmountBase', 'status'
    ];
    
    $filteredData = [];
    
    // Cek apakah data berupa array multi-dimensi (list)
    if (is_array($rawData)) {
        // Jika rawData berupa array of objects (list faktur)
        if (isset($rawData[0]) || empty($rawData)) {
            foreach ($rawData as $row) {
                $cleanRow = [];
                foreach ($allowedFields as $field) {
                    $cleanRow[$field] = isset($row[$field]) ? $row[$field] : null;
                }
                $filteredData[] = $cleanRow;
            }
        } 
        // Jika rawData hanya 1 objek (1 faktur tunggal)
        else {
            $cleanRow = [];
            foreach ($allowedFields as $field) {
                $cleanRow[$field] = isset($rawData[$field]) ? $rawData[$field] : null;
            }
            $filteredData = $cleanRow;
        }
    }

    echo json_encode([
        'status' => 'success',
        'data'   => $filteredData
    ], JSON_PRETTY_PRINT);

} else {
    // Set HTTP code ke 400 jika terjadi error
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => $result['error'] ? $result['error'] : 'Gagal mengambil data filter Faktur Penjualan'
    ], JSON_PRETTY_PRINT);
}
?>