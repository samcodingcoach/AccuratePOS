<?php
/**
 * API ITEM DETAIL (RAW DATA VERSION)
 * File: api/item/detail.php
 * Deskripsi: Mencari barang berdasarkan UPC (Barcode) dan menampilkan seluruh data mentah dari Accurate.
 */

require_once __DIR__ . '/../../bootstrap.php';

// 1. Set Header JSON & CORS
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

/**
 * 2. Ambil Parameter UPC secara fleksibel
 * Mendukung ?upc=... atau ?upcNo=... atau ?upcno=...
 */
$upcNo = null;
if (isset($_GET['upcno'])) {
    $upcNo = trim($_GET['upcno']);
} elseif (isset($_GET['upcNo'])) {
    $upcNo = trim($_GET['upcNo']);
} elseif (isset($_GET['upc'])) {
    $upcNo = trim($_GET['upc']);
}

// Validasi jika parameter tidak ada
if (!$upcNo) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Parameter UPC No diperlukan. Contoh penggunaan: item/detail.php?upcno=6666'
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    $api = new AccurateAPI();

    /**
     * 3. Tahap 1: Cari ID Barang berdasarkan UPC No
     * Menggunakan fungsi getItemByUPC yang ada di AccurateAPI.php
     */
    $search = $api->getItemByUPC($upcNo);
    
    if (!$search['success'] || empty($search['data']['d'])) {
        http_response_code(404);
        echo json_encode([
            'status'  => 'error',
            'message' => "Barang dengan Barcode: $upcNo tidak ditemukan di database Accurate."
        ], JSON_PRETTY_PRINT);
        exit;
    }

    // Ambil ID dari hasil pencarian pertama (index 0)
    $itemId = $search['data']['d'][0]['id'];

    /**
     * 4. Tahap 2: Ambil Detail Lengkap (Raw Data)
     * Pastikan fungsi getItemDetail() di AccurateAPI.php TIDAK memiliki filter 'fields'
     */
    $detailRes = $api->getItemDetail($itemId);

    if ($detailRes['success'] && isset($detailRes['data']['d'])) {
        // Ambil data d (data utama Accurate)
        $rawData = $detailRes['data']['d'];

        // 5. Kirim Output JSON Mentah
        echo json_encode([
            'status'  => 'success',
            'message' => 'Seluruh data mentah berhasil diambil',
            'data'    => $rawData, // Menampilkan isi 'd' secara utuh
            'meta'    => [
                'timestamp'   => date('c'),
                'target_id'   => $itemId,
                'target_upc'  => $upcNo,
                'api_version' => '2.1-raw'
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        throw new Exception("Gagal menarik detail mentah dari server Accurate.");
    }

} catch (Exception $e) {
    // 6. Error Handling & Logging
    if (function_exists('logError')) {
        logError("Detail Raw Error: " . $e->getMessage(), __FILE__, __LINE__);
    }
    
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}