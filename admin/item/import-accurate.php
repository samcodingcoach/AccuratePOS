<?php
/**
 * PROSES IMPORT DATA FROM ACCURATE API TO LOCAL DATABASE (Murni 6 Kolom Utama)
 * File: admin/item/import-accurate.php
 */

// 1. Jalankan session di awal untuk mengamankan data login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil session cookie sebelum ditutup
$sessionCookie = isset($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : '';

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

// 4. MEKANISME LOOPING AUTO-PAGINATION: Ambil semua data berantai sampai 'has_more' = false
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

        // 5. Looping data barang (Hanya memproses 6 kolom utama pilihan Anda)
        foreach ($accurateItems as $item) {
            $accId       = (int)$item['id'];
            $accItemNo   = trim($item['item_no']);
            $accName     = $item['name'] ?? null;
            $accBarcode  = isset($item['barcode']) ? trim($item['barcode']) : null;
            $accPrice    = (float)($item['price'] ?? 0);   
            $accBalance  = (int)($item['balance'] ?? 0);   

            // 6. Cek eksistensi data berdasarkan 'id' ATAU 'item_no' di DB lokal
            $checkSql = "SELECT COUNT(*) as total FROM item WHERE id = ? OR item_no = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param('is', $accId, $accItemNo);
            $checkStmt->execute();
            $isExist = $checkStmt->get_result()->fetch_assoc()['total'];
            $checkStmt->close();

            if ($isExist > 0) {
                $skippedCount++;
            } else {
                // 7. Jalankan perintah INSERT (MURNI 6 KOLOM PILIHAN ANDA, TANPA image DAN id_users)
                $insertSql = "INSERT INTO item (id, item_no, name, barcode, price, balance) 
                              VALUES (?, ?, ?, ?, ?, ?)";
                
                $insertStmt = $conn->prepare($insertSql);
                if ($insertStmt) {
                    /**
                     * BIND PARAM 6 PARAMETER ('isssdi'):
                     * i = id (int)
                     * s = item_no (string)
                     * s = name (string)
                     * s = barcode (string)
                     * d = price (double)
                     * i = balance (int)
                     */
                    $insertStmt->bind_param(
                        'isssdi', 
                        $accId, 
                        $accItemNo, 
                        $accName, 
                        $accBarcode, 
                        $accPrice, 
                        $accBalance
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

        // 8. Baca status pagination dari response JSON API
        $hasMore = (bool)($decodes['pagination']['has_more'] ?? false);
        if ($hasMore) {
            $page++; 
        }

    } else {
        $errorMessages[] = "API Error pada halaman {$page}: " . ($decodes['message'] ?? 'Unknown Error');
        break; 
    }
}

// 9. Jalankan kembali session di akhir kode untuk Flash Message Notifikasi
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

$conn->close();

// 10. Kembali ke halaman utama
header("Location: item.php?start_date={$startDate}&end_date={$endDate}");
exit;
?>