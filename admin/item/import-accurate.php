<?php
/**
 * PROSES IMPORT DATA FROM ACCURATE API TO LOCAL DATABASE (Optimized & Auto-Pagination)
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

// 2. Tangkap filter tanggal dari URL. JIKA KOSONG, BERLAKU TANGGAL HARI INI
$startDate  = isset($_GET['start_date']) && trim($_GET['start_date']) !== '' ? trim($_GET['start_date']) : date('Y-m-d');
$endDate    = isset($_GET['end_date']) && trim($_GET['end_date']) !== '' ? trim($_GET['end_date']) : date('Y-m-d');

// 3. Tembak API Accurate (api/item/list.php)
$apiBaseUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/../../api/item/list.php";

$insertedCount = 0;
$skippedCount  = 0;
$errorMessages = [];

$page = 1;
$hasMore = true;
$limit = 250; // Naikkan limit ke 250 (maksimal API baru) agar proses download data lebih cepat

// 4. MEKANISME LOOPING AUTO-PAGINATION: Ambil semua data sampai 'has_more' = false
while ($hasMore) {
    
    $queryParams = http_build_query([
        'start_date' => $startDate,
        'end_date'   => $endDate,
        'limit'      => $limit,
        'page'       => $page
    ]);
    
    $apiUrlWithParams = $apiBaseUrl . "?" . $queryParams;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrlWithParams);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Waktu diperpanjang demi kestabilan mass-import

    // Kirim session login agar api/item/list.php tidak memblokir cURL
    if ($sessionCookie !== '') {
        curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . $sessionCookie);
    }

    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) {
        $errorMessages[] = "Gagal terhubung dengan endpoint API Accurate pada halaman ke-{$page}.";
        break; // Stop loop jika jaringan/API lokal terputus
    }

    $decodes = json_decode($response, true);
    
    if (isset($decodes['status']) && $decodes['status'] === 'success') {
        $accurateItems = $decodes['data'] ?? [];
        
        // Jika respons data kosong pada halaman ini, hentikan perulangan
        if (empty($accurateItems)) {
            break;
        }

        // 5. Looping data untuk dimasukkan ke database lokal
        foreach ($accurateItems as $item) {
            $accId       = (int)$item['id'];
            $accItemNo   = trim($item['item_no']);
            $accName     = $item['name'] ?? null;
            $accBarcode  = $item['barcode'] ?? null;
            $accBalance  = (int)($item['balance'] ?? 0);
            $accPrice    = (float)($item['price'] ?? 0);
            $accImage    = $item['image'] ?? null;
            
            // Re-open session sebentar hanya untuk membaca user_id dengan aman
            if (session_status() === PHP_SESSION_NONE) session_start();
            $accIdUsers  = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
            session_write_close();

            // 6. Cek eksistensi data berdasarkan 'id' ATAU 'item_no'
            $checkSql = "SELECT COUNT(*) as total FROM item WHERE id = ? OR item_no = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param('is', $accId, $accItemNo);
            $checkStmt->execute();
            $isExist = $checkStmt->get_result()->fetch_assoc()['total'];
            $checkStmt->close();

            if ($isExist > 0) {
                $skippedCount++;
            } else {
                // 7. Jalankan INSERT data baru
                $insertSql = "INSERT INTO item (id, item_no, name, barcode, price, balance, image, id_users) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $insertStmt = $conn->prepare($insertSql);
                if ($insertStmt) {
                    $insertStmt->bind_param(
                        'isssddsi', 
                        $accId, $accItemNo, $accName, $accBarcode, $accPrice, $accBalance, $accImage, $accIdUsers
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

        // 8. Baca status pagination dari JSON API untuk menentukan apakah lanjut ke halaman berikutnya
        $hasMore = (bool)($decodes['pagination']['has_more'] ?? false);
        if ($hasMore) {
            $page++; // Naikkan counter halaman untuk loop berikutnya
        }

    } else {
        $errorMessages[] = "API Error pada halaman {$page}: " . ($decodes['message'] ?? 'Unknown Error');
        break; // Hentikan loop jika ada error token/akses dari API
    }
}

// 9. Simpan laporan hasil ke session flash message
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['import_flash'] = [
    'type'    => 'success',
    'message' => "Proses Selesai! Berhasil memeriksa hingga halaman ke-<strong>{$page}</strong>. Menambahkan <strong>{$insertedCount}</strong> data baru, dan melewati <strong>{$skippedCount}</strong> data lama."
];
if (!empty($errorMessages)) {
    $_SESSION['import_flash']['errors'] = $errorMessages;
}

// Tutup koneksi database
$conn->close();

// 10. Kembali ke halaman utama
header("Location: item.php?start_date={$startDate}&end_date={$endDate}");
exit;
?>