<?php
/**
 * API DASHBOARD - JUMLAH PENERIMAAN (COUNT INVOICE LUNAS)
 * File: api/dashboard/jumlah-penerimaan.php
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
    $localCount = 0;
    $recordExists = false;
    
    // 1. Cek Data di Database Lokal (MariaDB) - Tabel count_sale
    $stmt = $conn->prepare("SELECT jumlah, last_sync FROM count_sale WHERE tanggal = ?");
    $stmt->bind_param("s", $dbDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $recordExists = true;
        $localCount = (int)$row['jumlah'];
        
        // Cek selisih waktu
        $lastSyncTime = strtotime($row['last_sync']);
        $currentTime = time();
        $diffHours = ($currentTime - $lastSyncTime) / 3600;
        
        if ($diffHours >= 1) {
            $shouldSync = true;
        }
    } else {
        $shouldSync = true;
    }
    $stmt->close();
    
    // 2. Jika Tidak Perlu Sync (Data fresh)
    if (!$shouldSync) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Data diambil dari cache lokal (fresh)',
            'data'    => [
                'jumlahPenerimaan' => $localCount,
                'date'             => $accurateDate
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    // 3. Sync ke Accurate API (Mencari status Lunas)
    $api = new AccurateAPI();
    $jumlahLunas = 0;
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
        
        // Memanggil endpoint Sales Invoice
        $accResult = $api->getSalesInvoiceList($params);
        
        if (isset($accResult['success']) && $accResult['success']) {
            $data = $accResult['data']['d'] ?? [];
            
            foreach ($data as $item) {
                // Hanya hitung jika statusnya 'Lunas'
                if (isset($item['statusName']) && strtolower($item['statusName']) === 'lunas') {
                    $jumlahLunas++;
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
                    'message' => 'Gagal sync ke Accurate, menampilkan data lokal terakhir',
                    'data'    => [
                        'jumlahPenerimaan' => $localCount,
                        'date'             => $accurateDate
                    ]
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
    
    // 4. Update / Insert Data ke Database Lokal
    if ($recordExists) {
        $updateStmt = $conn->prepare("UPDATE count_sale SET jumlah = ?, last_sync = NOW() WHERE tanggal = ?");
        $updateStmt->bind_param("is", $jumlahLunas, $dbDate);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        $insertStmt = $conn->prepare("INSERT INTO count_sale (tanggal, jumlah, last_sync) VALUES (?, ?, NOW())");
        $insertStmt->bind_param("si", $dbDate, $jumlahLunas);
        $insertStmt->execute();
        $insertStmt->close();
    }
    
    // 5. Response Hasil Sync
    echo json_encode([
        'status'  => 'success',
        'message' => 'Data disinkronisasi dari Accurate',
        'data'    => [
            'jumlahPenerimaan' => $jumlahLunas,
            'date'             => $accurateDate
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
