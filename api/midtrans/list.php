<?php
/**
 * API MIDTRANS LIST
 * File: api/midtrans/list.php
 * Path Koneksi: ../../config/koneksi.php
 */

// 1. Muat konfigurasi dan class utama
require_once __DIR__ . '/../../bootstrap.php';

// 2. Proteksi endpoint menggunakan file utils bawaan (Wajib login / Token)
require_once __DIR__ . '/../../utils/api_auth.php';
require_once __DIR__ . '/../../config/koneksi.php';

// 3. Set header agar output berupa JSON
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Proteksi Method HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Method not allowed'
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    // 4. Query Database
    $sql = "SELECT id_midtrans, MerchantID, ClientKey, ServerKey, UpdateAt FROM midtrans";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Gagal menjalankan query: " . $conn->error);
    }

    $midtransList = [];
    while ($row = $result->fetch_assoc()) {
        $midtransList[] = [
            'id_midtrans' => (int)$row['id_midtrans'],
            'MerchantID'  => $row['MerchantID'],
            'ClientKey'   => $row['ClientKey'],
            'ServerKey'   => $row['ServerKey'],
            'UpdateAt'    => $row['UpdateAt']
        ];
    }

    // 5. Kembalikan response JSON
    echo json_encode([
        'status'  => 'success',
        'message' => 'Data Midtrans berhasil dimuat',
        'data'    => $midtransList
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error', 
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
} finally {
    if (isset($conn)) $conn->close();
}
?>
