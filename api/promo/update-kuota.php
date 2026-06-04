<?php
/**
 * API PROMO UPDATE KUOTA
 * File: api/promo/update-kuota.php
 * Path Koneksi: ../../config/koneksi.php
 */

// 1. Muat konfigurasi dan class utama
require_once __DIR__ . '/../../bootstrap.php';

// 2. Proteksi endpoint menggunakan file utils bawaan (Wajib login / Token)
require_once __DIR__ . '/../../utils/api_auth.php';
require_once __DIR__ . '/../../config/koneksi.php';

// 3. Set header agar output berupa JSON
header('Content-Type: application/json; charset=UTF-8');

// Proteksi Method HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Method not allowed'
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    // 4. Ambil input data (Mendukung Content-Type: application/json atau form-data)
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $kuota = isset($input['kuota']) ? $input['kuota'] : '';
    $itemNo = isset($input['item_no']) ? trim($input['item_no']) : '';
    $name = isset($input['name']) ? trim($input['name']) : '';
    $categoryUser = isset($input['category_user']) ? trim($input['category_user']) : '';

    // Validasi parameter wajib
    if ($kuota === '' || $itemNo === '' || $name === '' || $categoryUser === '') {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Parameter kuota, item_no, name, dan category_user wajib diisi'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    // Pastikan kuota adalah angka
    if (!is_numeric($kuota)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Parameter kuota harus berupa angka'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    $kuota = (int)$kuota;

    // 5. Query UPDATE
    $sql = "UPDATE promo SET kuota = kuota - ? WHERE item_no = ? AND name = ? AND category_user = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Gagal menyiapkan statement: " . $conn->error);
    }

    $stmt->bind_param("isss", $kuota, $itemNo, $name, $categoryUser);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $message = 'Kuota promo berhasil dikurangi';
    } elseif ($stmt->affected_rows === 0) {
        // Bisa berarti data tidak ditemukan atau nilai pengurangan adalah 0
        $message = 'Tidak ada data yang diupdate (mungkin data tidak ditemukan)';
    } else {
        throw new Exception("Gagal mengupdate kuota promo: " . $stmt->error);
    }

    // 6. Kembalikan response JSON
    echo json_encode([
        'status'  => 'success',
        'message' => $message,
        'data'    => [
            'item_no'          => $itemNo,
            'name'             => $name,
            'category_user'    => $categoryUser,
            'kuota_dikurangi'  => $kuota
        ],
        'meta' => [
            'timestamp'   => date('c'),
            'api_version' => '1.0-local'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error', 
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>
