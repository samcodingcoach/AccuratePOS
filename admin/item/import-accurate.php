<?php
/**
 * PROSES IMPORT DATA FROM ACCURATE API TO LOCAL DATABASE (UI Error Handling Version)
 * File: admin/item/import-accurate.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil data session & cookie yang dibutuhkan selagi session masih terbuka
$sessionCookie = isset($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : '';
$accIdUsers    = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;

session_write_close(); 

require_once __DIR__ . '/../../config/koneksi.php';

$startDate = (isset($_GET['start_date']) && trim($_GET['start_date']) !== '') ? trim($_GET['start_date']) : date('Y-m-d');
$endDate   = (isset($_GET['end_date']) && trim($_GET['end_date']) !== '') ? trim($_GET['end_date']) : date('Y-m-d');

$apiBaseUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/../../api/item/list.php";

$insertedCount = 0;
$skippedCount  = 0;
$errorMessages = [];

// Aktifkan internal error reporting untuk mysqli agar bisa ditangkap oleh try-catch (Sangat Penting untuk PHP 8)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $checkSql = "SELECT COUNT(*) as total FROM item WHERE id = ? OR item_no = ?";
    $checkStmt = $conn->prepare($checkSql);

    $insertSql = "INSERT INTO item (id, item_no, name, barcode, price, balance, image, id_users) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertSql);
} catch (mysqli_sql_exception $e) {
    // Tangkap error jika skrip gagal menyiapkan statement database di awal
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['import_flash'] = [
        'type'    => 'error',
        'message' => '<strong>Gagal Menyiapkan Query Database:</strong> Struktur tabel lokal Anda tidak sesuai. Detail: ' . $e->getMessage()
    ];
    $conn->close();
    header("Location: item.php?start_date={$startDate}&end_date={$endDate}");
    exit;
}

$page = 1;
$hasMore = true;
$limit = 250; 

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

        foreach ($accurateItems as $item) {
            $accId       = (int)$item['id'];
            $accItemNo   = trim($item['item_no']);
            $accName     = isset($item['name']) ? trim($item['name']) : '';
            $accBarcode  = isset($item['barcode']) ? trim($item['barcode']) : '';
            $accPrice    = (float)($item['price'] ?? 0);   
            $accBalance  = (int)($item['balance'] ?? 0);   
            $accImage    = isset($item['image']) && trim($item['image']) !== '' ? trim($item['image']) : '';

            try {
                // Eksekusi cek eksistensi data
                $checkStmt->bind_param('is', $accId, $accItemNo);
                $checkStmt->execute();
                $isExist = $checkStmt->get_result()->fetch_assoc()['total'];

                if ($isExist > 0) {
                    $skippedCount++;
                } else {
                    // Eksekusi INSERT data baru
                    $insertStmt->bind_param(
                        'isssdisi', 
                        $accId, $accItemNo, $accName, $accBarcode, $accPrice, $accBalance, $accImage, $accIdUsers
                    );
                    $insertStmt->execute();
                    $insertedCount++;
                }
            } catch (mysqli_sql_exception $dbException) {
                // JIKA TERJADI ERROR DATABASE (Seperti Allow Null / Constraint): Tangkap di sini tanpa membuat halaman crash!
                $errorCode = $dbException->getCode();
                
                // Terjemahkan error umum database agar operator/user mudah paham
                if ($errorCode === 1048) {
                    $friendlyMessage = "Kolom database tidak boleh kosong (aturan Allow Null = No).";
                } elseif ($errorCode === 1062) {
                    $friendlyMessage = "Terjadi duplikasi data pada kolom unik.";
                } else {
                    $friendlyMessage = $dbException->getMessage();
                }

                $errorMessages[] = "<strong>[Item No: {$accItemNo}]</strong> Gagal disimpan karena: {$friendlyMessage}";
            }
        }

        $hasMore = (bool)($decodes['pagination']['has_more'] ?? false);
        if ($hasMore) {
            $page++; 
        }

    } else {
        $errorMessages[] = "API Error pada halaman {$page}: " . ($decodes['message'] ?? 'Unknown Error');
        break; 
    }
}

$checkStmt->close();
$insertStmt->close();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 9. Kirimkan Laporan Hasil Akhir secara Visual ke UI flash message
if ($insertedCount > 0 && empty($errorMessages)) {
    $_SESSION['import_flash'] = [
        'type'    => 'success',
        'message' => "<strong>Proses Sukses Sepenuhnya!</strong> Berhasil menambahkan <strong>{$insertedCount}</strong> data baru, dan melewati <strong>{$skippedCount}</strong> data lama."
    ];
} else {
    // Jika ada error pada beberapa item, status tetap diset 'error' agar kotak UI berwarna merah/oranye
    $_SESSION['import_flash'] = [
        'type'    => 'error',
        'message' => "<strong>Proses Selesai dengan Catatan!</strong> Berhasil menyimpan <strong>{$insertedCount}</strong> item, melewati <strong>{$skippedCount}</strong> item lama, namun terdapat <strong>" . count($errorMessages) . "</strong> item yang gagal disimpan."
    ];
}

if (!empty($errorMessages)) {
    $_SESSION['import_flash']['errors'] = $errorMessages;
}

$conn->close();

header("Location: item.php?start_date={$startDate}&end_date={$endDate}");
exit;
?>