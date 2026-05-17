<?php
/**
 * PROSES IMPORT DATA FROM ACCURATE API TO LOCAL DATABASE
 * File: admin/item/import-accurate.php
 */

// 1. Jalankan dan amankan session agar hanya yang sudah login bisa memproses
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sessionCookie = isset($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : '';
session_write_close(); // Cegah session locking / deadlock

// Load koneksi database
require_once __DIR__ . '/../../config/koneksi.php';

// 2. Tangkap filter tanggal dari URL (jika ada) untuk disamakan ke API Accurate
$startDate  = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$endDate    = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

// 3. Tembak API Accurate (api/item/list.php)
$apiBaseUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/../../api/item/list.php";
$queryParams = http_build_query([
    'start_date' => $startDate,
    'end_date'   => $endDate,
    'limit'      => 50 // Sesuaikan atau hapus limit jika ingin menarik semua data sekaligus
]);
$apiUrlWithParams = $apiBaseUrl . "?" . $queryParams;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrlWithParams);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Beri waktu lebih lama karena memproses data luar

// Kirim session login agar api/item/list.php tidak memblokir cURL
if ($sessionCookie !== '') {
    curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . $sessionCookie);
}

$response = curl_exec($ch);
curl_close($ch);

// Tambahkan counter untuk laporan hasil akhir
$insertedCount = 0;
$skippedCount  = 0;
$errorMessages = [];

if ($response) {
    $decodes = json_decode($response, true);
    
    if (isset($decodes['status']) && $decodes['status'] === 'success') {
        $accurateItems = $decodes['data'] ?? [];

        // 4. Looping data dari Accurate untuk dimasukkan ke database lokal
        foreach ($accurateItems as $item) {
            $accId       = (int)$item['id'];
            $accItemNo   = trim($item['item_no']);
            $accName     = $item['name'] ?? null;
            $accBarcode  = $item['barcode'] ?? null;
            $accBalance  = (int)($item['balance'] ?? 0);
            $accPrice    = (float)($item['price'] ?? 0);
            $accImage    = $item['image'] ?? null;
            $accIdUsers  = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1; // Default ke 1 jika session kosong

            // 5. Cek eksistensi data berdasarkan 'id' ATAU 'item_no'
            $checkSql = "SELECT COUNT(*) as total FROM item WHERE id = ? OR item_no = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param('is', $accId, $accItemNo);
            $checkStmt->execute();
            $isExist = $checkStmt->get_result()->fetch_assoc()['total'];
            $checkStmt->close();

            if ($isExist > 0) {
                // Data sudah ada, lewati proses insert
                $skippedCount++;
            } else {
                // 6. Jalankan INSERT data baru karena belum ada di database lokal
                $insertSql = "INSERT INTO item (id, item_no, name, barcode, price, balance, image, id_users) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $insertStmt = $conn->prepare($insertSql);
                if ($insertStmt) {
                    $insertStmt->bind_param(
                        'isssddsi', 
                        $accId, 
                        $accItemNo, 
                        $accName, 
                        $accBarcode, 
                        $accPrice, 
                        $accBalance, 
                        $accImage, 
                        $accIdUsers
                    );
                    
                    if ($insertStmt->execute()) {
                        $insertedCount++;
                    } else {
                        $errorMessages[] = "Gagal simpan Item No {$accItemNo}: " . $insertStmt->error;
                    }
                    $insertStmt->close();
                } else {
                    $errorMessages[] = "Gagal menyiapkan insert statement: " . $conn->error;
                }
            }
        }
        
        // Simpan laporan hasil ke session flash message
        session_start();
        $_SESSION['import_flash'] = [
            'type'    => 'success',
            'message' => "Proses Selesai! Berhasil menambahkan <strong>{$insertedCount}</strong> data baru. Melewati <strong>{$skippedCount}</strong> data lama."
        ];
        if (!empty($errorMessages)) {
            $_SESSION['import_flash']['errors'] = $errorMessages;
        }

    } else {
        session_start();
        $_SESSION['import_flash'] = [
            'type'    => 'error',
            'message' => 'API Accurate mengembalikan error: ' . ($decodes['message'] ?? 'Unknown Error')
        ];
    }
} else {
    session_start();
    $_SESSION['import_flash'] = [
        'type'    => 'error',
        'message' => 'Gagal terhubung dengan endpoint API Accurate.'
    ];
}

// Tutup koneksi database
$conn->close();

// 7. Kembalikan halaman pengguna ke tampilan list item utama
header("Location: item.php?start_date={$startDate}&end_date={$endDate}");
exit;