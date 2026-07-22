<?php
/**
 * API DASHBOARD - 3 FAKTUR TERAKHIR HARI INI (LUNAS)
 * File: api/dashboard/faktur-terakhir.php
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../utils/api_auth.php';
require_once __DIR__ . '/../../config/koneksi.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method tidak diizinkan. Gunakan GET.'
    ]);
    exit;
}

try {
    $dbDate = date('Y-m-d');
    $accurateDate = date('d/m/Y');
    
    $shouldSync = false;
    $localData = [];
    $recordExists = false;
    
    // 1. Cek Data di Database Lokal (MariaDB) - Tabel last_sale
    // Urutkan id_last_sales DESC untuk memastikan urutan terbaru
    $stmt = $conn->prepare("SELECT * FROM last_sale WHERE tanggal = ? ORDER BY id_last_sales DESC");
    $stmt->bind_param("s", $dbDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $recordExists = true;
        $localData[] = [
            'invoiceTime'          => $row['invoiceTime'] ?? '',
            'number'               => $row['number'],
            'statusName'           => $row['statusName'],
            'totalAmount'          => (float)$row['totalAmount'],
            'branchName'           => $row['branchName'],
            'receiptHistoryNumber' => $row['receiptHistoryNumber']
        ];
        
        // Pengecekan umur data cukup dari baris pertama saja
        if (count($localData) === 1) {
            $lastSyncTime = strtotime($row['last_sync']);
            $currentTime = time();
            $diffHours = ($currentTime - $lastSyncTime) / 3600;
            
            if ($diffHours >= 1) {
                $shouldSync = true;
            }
        }
    }
    $stmt->close();
    
    // Jika tidak ada data sama sekali, maka wajib sync
    if (!$recordExists) {
        $shouldSync = true;
    }
    
    // 2. Jika Tidak Perlu Sync (Data fresh)
    if (!$shouldSync) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Data diambil dari cache lokal (fresh)',
            'data'    => $localData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    // 3. Sync ke Accurate API (Mencari 3 Lunas Terakhir)
    $api = new AccurateAPI();
    $allLunas = [];
    $page = 1;
    $hasMore = true;
    
    // Tarik semua halaman untuk hari ini
    while ($hasMore) {
        $params = [
            'filter.transDate.op' => 'BETWEEN',
            'filter.transDate.val[0]' => $accurateDate,
            'filter.transDate.val[1]' => $accurateDate,
            'sp.page' => $page,
            'sp.pageSize' => 100
        ];
        
        $accResult = $api->getSalesInvoiceList($params);
        
        if (isset($accResult['success']) && $accResult['success']) {
            $data = $accResult['data']['d'] ?? [];
            
            foreach ($data as $item) {
                if (isset($item['statusName']) && strtolower($item['statusName']) === 'lunas') {
                    $allLunas[] = $item;
                }
            }
            
            $pagination = $accResult['data']['sp'] ?? [];
            if (isset($pagination['page']) && isset($pagination['pageCount'])) {
                if ($pagination['page'] >= $pagination['pageCount']) {
                    $hasMore = false;
                } else {
                    $page++;
                }
            } else {
                if (count($data) < 100) {
                    $hasMore = false;
                } else {
                    $page++;
                }
            }
        } else {
            // Fallback lokal jika API gagal
            if ($recordExists) {
                echo json_encode([
                    'status'  => 'success',
                    'message' => 'Gagal sync ke Accurate (List), menampilkan data lokal terakhir',
                    'data'    => $localData
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                exit;
            } else {
                http_response_code(400);
                echo json_encode([
                    'status'  => 'error',
                    'message' => $accResult['message'] ?? 'Gagal mengambil data dari Faktur Penjualan',
                    'error'   => $accResult['error'] ?? null
                ]);
                exit;
            }
        }
    }
    
    // Urutkan berdasarkan ID Descending untuk mendapatkan yang paling baru
    usort($allLunas, function($a, $b) {
        return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
    });
    
    // Ambil 3 Teratas
    $top3List = array_slice($allLunas, 0, 3);
    $finalData = [];
    
    // Tarik Detail untuk Masing-Masing 3 Teratas
    foreach ($top3List as $inv) {
        $detailResult = $api->getSalesInvoiceDetail(null, $inv['number']);
        
        if (isset($detailResult['success']) && $detailResult['success']) {
            $det = $detailResult['data']['d'] ?? [];
            
            $receiptHistoryNumber = '';
            if (!empty($det['receiptHistory']) && is_array($det['receiptHistory'])) {
                $receiptHistoryNumber = $det['receiptHistory'][0]['historyNumber'] ?? '';
            }
            
            $rawInvoiceTime = $det['invoiceTime'] ?? '';
            $invoiceTime = explode('.', $rawInvoiceTime)[0]; // Menghilangkan milidetik jika ada
            
            $finalData[] = [
                'invoiceTime'          => $invoiceTime,
                'number'               => $det['number'] ?? '',
                'statusName'           => $det['statusName'] ?? '',
                'totalAmount'          => (float)($det['totalAmount'] ?? 0),
                'branchName'           => $det['branchName'] ?? ($det['branch']['name'] ?? ''),
                'receiptHistoryNumber' => $receiptHistoryNumber
            ];
        }
    }
    
    // 4. Hapus data lama untuk hari ini, lalu Insert yang baru ke Database Lokal
    // Ini memastikan kita tidak menumpuk baris (hanya menyimpan 3 terbaru per hari)
    $delStmt = $conn->prepare("DELETE FROM last_sale WHERE tanggal = ?");
    $delStmt->bind_param("s", $dbDate);
    $delStmt->execute();
    $delStmt->close();
    
    $insertStmt = $conn->prepare("INSERT INTO last_sale (number, statusName, totalAmount, branchName, receiptHistoryNumber, invoiceTime, tanggal, last_sync) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    
    // Balik urutan finalData saat insert agar ID di database urut secara alami (paling baru id paling besar)
    $reversedData = array_reverse($finalData);
    foreach ($reversedData as $item) {
        $insertStmt->bind_param("ssdssss", 
            $item['number'], 
            $item['statusName'], 
            $item['totalAmount'], 
            $item['branchName'], 
            $item['receiptHistoryNumber'], 
            $item['invoiceTime'],
            $dbDate
        );
        $insertStmt->execute();
    }
    $insertStmt->close();
    
    // 5. Response Hasil Sync
    echo json_encode([
        'status'  => 'success',
        'message' => 'Data disinkronisasi dari Accurate',
        'data'    => $finalData
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan internal server.',
        'error'   => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
