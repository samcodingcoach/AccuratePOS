<?php
/**
 * API DASHBOARD - TOTAL PENERIMAAN (Dengan Caching Lokal MariaDB)
 * File: api/dashboard/total-penerimaan.php
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
    // Tanggal untuk DB lokal (Y-m-d) dan API Accurate (d/m/Y)
    $dbDate = date('Y-m-d');
    $accurateDate = date('d/m/Y');
    
    $shouldSync = false;
    $localTotal = 0;
    $recordExists = false;
    
    // 1. Cek Data di Database Lokal (MariaDB)
    $stmt = $conn->prepare("SELECT total, last_sync FROM report_sale WHERE tanggal = ?");
    $stmt->bind_param("s", $dbDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $recordExists = true;
        $localTotal = (float)$row['total'];
        
        // Cek selisih waktu last_sync dengan waktu sekarang
        $lastSyncTime = strtotime($row['last_sync']);
        $currentTime = time();
        $diffHours = ($currentTime - $lastSyncTime) / 3600;
        
        if ($diffHours >= 1) {
            $shouldSync = true; // Sudah lebih dari 1 jam, harus sync lagi
        }
    } else {
        $shouldSync = true; // Data belum ada sama sekali untuk hari ini
    }
    $stmt->close();
    
    // 2. Jika Tidak Perlu Sync (Data masih fresh < 1 jam)
    if (!$shouldSync) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Data diambil dari cache lokal (fresh)',
            'data'    => [
                'totalPenerimaan' => $localTotal,
                'date'            => $accurateDate
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    // 3. Jika Perlu Sync, Lakukan Hit ke Accurate API
    $api = new AccurateAPI();
    $totalPenerimaan = 0;
    $page = 1;
    $hasMore = true;
    
    while ($hasMore) {
        $params = [
            'filter.transDate.op' => 'BETWEEN',
            'filter.transDate.val[0]' => $accurateDate,
            'filter.transDate.val[1]' => $accurateDate,
            'sp.page' => $page,
            'sp.pageSize' => 100
        ];
        
        $accResult = $api->getSalesReceiptList($params);
        
        if (isset($accResult['success']) && $accResult['success']) {
            $data = $accResult['data']['d'] ?? [];
            
            // Looping dan jumlahkan totalPayment
            foreach ($data as $item) {
                if (isset($item['totalPayment'])) {
                    $totalPenerimaan += (float)$item['totalPayment'];
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
            // Jika gagal sync tapi punya data lokal (walaupun basi), tampilkan lokal saja sebagai fallback
            if ($recordExists) {
                echo json_encode([
                    'status'  => 'success',
                    'message' => 'Gagal sync ke Accurate, menampilkan data lokal terakhir',
                    'data'    => [
                        'totalPenerimaan' => $localTotal,
                        'date'            => $accurateDate
                    ]
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                exit;
            } else {
                http_response_code(400);
                echo json_encode([
                    'status'  => 'error',
                    'message' => $accResult['message'] ?? 'Gagal mengambil data penerimaan penjualan',
                    'error'   => $accResult['error'] ?? null
                ]);
                exit;
            }
        }
    }
    
    // 4. Update / Insert Data ke Database Lokal
    if ($recordExists) {
        $updateStmt = $conn->prepare("UPDATE report_sale SET total = ?, last_sync = NOW() WHERE tanggal = ?");
        $updateStmt->bind_param("ds", $totalPenerimaan, $dbDate);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        $insertStmt = $conn->prepare("INSERT INTO report_sale (tanggal, total, last_sync) VALUES (?, ?, NOW())");
        $insertStmt->bind_param("sd", $dbDate, $totalPenerimaan);
        $insertStmt->execute();
        $insertStmt->close();
    }
    
    // 5. Kembalikan Response Hasil Sync
    echo json_encode([
        'status'  => 'success',
        'message' => 'Data disinkronisasi dari Accurate',
        'data'    => [
            'totalPenerimaan' => $totalPenerimaan,
            'date'            => $accurateDate
        ]
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
