<?php
/**
 * BACKEND ACTION - UPDATE STOK/BALANCE LOKAL (Pure MySQLi with config/koneksi.php)
 * File: classes/update_stock_available_lokal.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hubungkan ke file koneksi database lokal Anda
require_once __DIR__ . '/../config/koneksi.php'; 

header('Content-Type: application/json; charset=UTF-8');

// Proteksi Method: Hanya izinkan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan. Gunakan POST.']);
    exit;
}

// 1. Tangkap Data POST (Mendukung standard form data dan JSON RAW input)
$itemNo = isset($_POST['no']) ? trim($_POST['no']) : '';
$stock  = isset($_POST['stock']) ? trim($_POST['stock']) : '';

if (empty($itemNo) && empty($stock)) {
    $jsonInput = json_decode(file_get_contents('php://input'), true);
    $itemNo = isset($jsonInput['no']) ? trim($jsonInput['no']) : '';
    $stock  = isset($jsonInput['stock']) ? trim($jsonInput['stock']) : '';
}

// 2. Validasi Parameter Input
if (empty($itemNo)) {
    echo json_encode(['status' => 'error', 'message' => 'Nomor barang (item_no) wajib diisi.']);
    exit;
}

if ($stock === '') {
    echo json_encode(['status' => 'error', 'message' => 'Jumlah stok baru tidak boleh kosong.']);
    exit;
}

// Bersihkan format angka (menghilangkan tanda titik ribuan jika ada, misal "1.250" -> "1250")
$cleanStock = str_replace('.', '', $stock);
$finalStock = (int)preg_replace('/[^0-9]/', '', $cleanStock);

// 3. Pastikan Variabel Koneksi MySQLi Tersedia
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error', 
        'message' => 'Koneksi database lokal ($conn) bertipe MySQLi tidak ditemukan di dalam config/koneksi.php.'
    ]);
    exit;
}

try {
    // 4. Proses Eksekusi Query UPDATE pada kolom 'balance'
    $sql = "UPDATE item SET balance = ? WHERE item_no = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Gagal menyiapkan struktur query UPDATE Stok: " . $conn->error);
    }

    // Bind parameter: 'i' untuk integer (balance/stock), 's' untuk string (item_no)
    $stmt->bind_param("is", $finalStock, $itemNo);
    
    $exec = $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    // 5. Kembalikan Response Hasil Akhir ke Frontend AJAX
    if ($exec) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Stok (balance) lokal berhasil diperbarui di database.',
            'data'    => [
                'item_no'       => $itemNo,
                'updated_stock' => $finalStock,
                'rows_affected' => $affectedRows
            ]
        ]);
    } else {
        echo json_encode([
            'status'  => 'error', 
            'message' => 'Gagal memperbarui data stok lokal.'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
}
?>