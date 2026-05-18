<?php
/**
 * BACKEND ACTION - UPDATE HARGA DEFAULT LOKAL (Pure MySQLi with config/koneksi.php)
 * File: classes/update_price_default_lokal.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PERBAIKAN PATH: Mengarah ke config/koneksi.php dari posisi folder classes/
// __DIR__ . '/../' berarti naik 1 tingkat ke root project, lalu masuk ke folder config/
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
$price  = isset($_POST['harga']) ? trim($_POST['harga']) : '';

if (empty($itemNo) && empty($price)) {
    $jsonInput = json_decode(file_get_contents('php://input'), true);
    $itemNo = isset($jsonInput['no']) ? trim($jsonInput['no']) : '';
    $price  = isset($jsonInput['harga']) ? trim($jsonInput['harga']) : '';
}

// 2. Validasi Parameter Input
if (empty($itemNo)) {
    echo json_encode(['status' => 'error', 'message' => 'Nomor barang (item_no) wajib diisi.']);
    exit;
}

if ($price === '') {
    echo json_encode(['status' => 'error', 'message' => 'Harga baru tidak boleh kosong.']);
    exit;
}


// 2. PERBAIKAN UTAMA: Hilangkan tanda titik ribuan terlebih dahulu, baru bersihkan karakter non-angka lainnya
$cleanPrice = str_replace('.', '', $price); // Mengubah "350.000" menjadi "350000"
$cleanPrice = preg_replace('/[^0-9]/', '', $cleanPrice); // Memastikan hanya ada karakter angka fisik

// 3. Konversi ke float/double untuk kebutuhan MySQL
$finalPrice = (float)$cleanPrice;
// 3. Pastikan Variabel Koneksi MySQLi dari koneksi.php Tersedia
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error', 
        'message' => 'Koneksi database lokal ($conn) bertipe MySQLi tidak ditemukan di dalam config/koneksi.php.'
    ]);
    exit;
}

try {
    // 4. Proses Eksekusi Query UPDATE Menggunakan Prepared Statement MySQLi
    $sql = "UPDATE item SET price = ? WHERE item_no = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Gagal menyiapkan struktur query UPDATE: " . $conn->error);
    }

    // Bind parameter: 'd' untuk double/float (price), 's' untuk string (item_no)
    $stmt->bind_param("ds", $finalPrice, $itemNo);
    
    $exec = $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    // 5. Kembalikan Response Hasil Akhir ke Frontend AJAX
    if ($exec) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Harga default lokal berhasil diperbarui di database.',
            'data'    => [
                'item_no'       => $itemNo,
                'updated_price' => $finalPrice,
                'rows_affected' => $affectedRows
            ]
        ]);
    } else {
        echo json_encode([
            'status'  => 'error', 
            'message' => 'Gagal memperbarui data harga lokal.'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
}