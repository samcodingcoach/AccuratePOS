<?php
/**
 * PROSES IMPORT DATA FROM ACCURATE API TO LOCAL DATABASE (Fixed Looping & Prepared Statements)
 * File: admin/item/import-accurate.php
 */

// 1. Jalankan session di awal untuk mengamankan data login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil data session & cookie yang dibutuhkan selagi session masih terbuka
$sessionCookie = isset($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : '';
$accIdUsers    = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;

// JALAN KELUAR: Langsung tutup session di sini agar TIDAK TERJADI DEADLOCK / LOCKING dengan cURL API lokal
session_write_close();

// Load koneksi database lokal
require_once __DIR__ . '/../../config/koneksi.php';

// 2. Tangkap parameter tanggal dari URL browser. JIKA KOSONG, BERLAKU TANGGAL HARI INI
$startDate = (isset($_GET['start_date']) && trim($_GET['start_date']) !== '') ? trim($_GET['start_date']) : date('Y-m-d');
$endDate   = (isset($_GET['end_date']) && trim($_GET['end_date']) !== '') ? trim($_GET['end_date']) : date('Y-m-d');

// 3. Alamat URL API Accurate lokal Anda (api/item/list.php)
$apiBaseUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/../../api/item/list.php";

$insertedCount = 0;
$skippedCount  = 0;
$errorMessages = [];

$page = 1;
$hasMore = true;
$limit = 250; 

// 4. SIAPKAN PREPARED STATEMENT DI LUAR LOOP AGAR TIDAK MEMBUAT ULANG BUFFER MEMORI (PHP OPTIMIZATION)
// Statement untuk Cek Eksistensi Data
$checkSql = "SELECT COUNT(*) as total FROM item WHERE id = ? OR item_no = ?";
$checkStmt = $conn->prepare($checkSql);

// Statement untuk Insert Data Baru
$insertSql = "INSERT INTO item (id, item_no, name, barcode, price, balance, image, id_users) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$insertStmt = $conn->prepare($insertSql);

// Jika gagal menyiapkan statement di database, hentikan proses sejak awal
if (!$checkStmt || !$insertStmt) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['import_flash'] = [
        'type'    => 'error',
        'message' => 'Gagal menyiapkan struktur query database lokal: ' . $conn->error
    ];
    $conn->close();
    header("Location: item.php?start_date={$startDate}&end_date={$endDate}");
    exit;
}

// 5. MEKANISME LOOPING AUTO-PAGINATION: Ambil semua data berantai
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); 

    if ($sessionCookie !== '') {
        curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . $sessionCookie);
    }

    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) {
        $errorMessages[] = "Gagal terhubung dengan endpoint API Accurate pada halaman ke-{$page}.";
        break; 
    }

    $decodes = json_decode($response, true);
    
    if (isset($decodes['status']) && $decodes['status'] === 'success') {
        $accurateItems = $decodes['data'] ?? [];
        
        if (empty($accurateItems)) {
            break;
        }

        // 6. Looping data barang hasil fetch
        foreach ($accurateItems as $item) {
            $accId       = (int)$item['id'];
            $accItemNo   = trim($item['item_no']);
            $accName     = $item['name'] ?? null;
            $accBarcode  = isset($item['barcode']) ? trim($item['barcode']) : null;
            $accPrice    = (float)($item['price'] ?? 0);   
            $accBalance  = (int)($item['balance'] ?? 0);   
            
            // Aturan Image: Jika dari list.php bernilai null/kosong, set ke NULL lokal
            $accImage    = isset($item['image']) && trim($item['image']) !== '' ? trim($item['image']) : null;

            // 7. Eksekusi Cek Eksistensi Menggunakan Statement yang Sudah Ada
            $checkStmt->bind_param('is', $accId, $accItemNo);
            $checkStmt->execute();
            $isExist = $checkStmt->get_result()->fetch_assoc()['total'];

            if ($isExist > 0) {
                // Skip jika data sudah ada
                $skippedCount++;
            } else {
                // 8. Eksekusi INSERT Menggunakan Statement yang Sudah Ada
                $insertStmt->bind_param(
                    'isssdisi', 
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
            }
        }

        // Baca status pagination dari response JSON API untuk melanjutkannya ke halaman berikutnya
        $hasMore = (bool)($decodes['pagination']['has_more'] ?? false);
        if ($hasMore) {
            $page++; 
        }

    } else {
        $errorMessages[] = "API Error pada halaman {$page}: " . ($decodes['message'] ?? 'Unknown Error');
        break; 
    }
}

// 9. Tutup semua statement yang berada di luar loop setelah selesai digunakan
$checkStmt->close();
$insertStmt->close();

// 10. Jalankan kembali session di akhir kode untuk Flash Message Notifikasi
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

// Tutup koneksi database lokal dengan aman
$conn->close();

// 11. Kembali ke halaman utama admin
header("Location: item.php?start_date={$startDate}&end_date={$endDate}");
exit;
?>