<?php
/**
 * API DASHBOARD - BARANG TERLARIS (Top 3)
 * File: api/dashboard/barang-terlaris.php
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../utils/api_auth.php';
require_once __DIR__ . '/../../config/koneksi.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan. Gunakan GET.']);
    exit;
}

try {
    $api = new AccurateAPI();
    
    $dbDate = date('Y-m-d');
    $accurateDate = date('d/m/Y'); // Filter untuk transaksi hari ini
    
    $shouldSync = false;
    $localData = [];
    $recordExists = false;
    
    // 1. Cek Data di Database Lokal (MariaDB) - Tabel last_terlaris
    $stmt = $conn->prepare("SELECT * FROM last_terlaris WHERE tanggal = ? ORDER BY terjual DESC");
    $stmt->bind_param("s", $dbDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $recordExists = true;
        $localData[] = [
            'itemNo'  => $row['itemNo'],
            'name'    => $row['name'],
            'terjual' => (int)$row['terjual']
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
    
    // 3. Sync ke Accurate API (Hitung Mutasi)
    $penjualanItems = [];
    $page = 1;
    $hasMore = true;
    
    while ($hasMore) {
        $params = [
            'filter.transactionType.op'  => 'EQUAL',
            'filter.transactionType.val' => 'SI', // Hanya Faktur Penjualan
            'sp.page'                    => $page,
            'sp.pageSize'                => 100
        ];
        
        $accResult = $api->getStockMutationHistory($params);
        
        if (isset($accResult['success']) && $accResult['success']) {
            $data = $accResult['data']['d'] ?? [];
            
            foreach ($data as $item) {
                $itemNo = $item['itemNo'] ?? 'UNKNOWN';
                $qtyTerjual = abs((float)($item['mutation'] ?? 0));
                
                if (!isset($penjualanItems[$itemNo])) {
                    $penjualanItems[$itemNo] = [
                        'itemNo'   => $itemNo,
                        'terjual'  => 0
                    ];
                }
                $penjualanItems[$itemNo]['terjual'] += $qtyTerjual;
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
                    'data'    => $localData
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                exit;
            } else {
                http_response_code(400);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Gagal mengambil mutasi stok: ' . ($accResult['message'] ?? ''),
                    'error'   => $accResult['error'] ?? null
                ]);
                exit;
            }
        }
    }
    
    $finalItems = array_values($penjualanItems);
    
    usort($finalItems, function($a, $b) {
        return $b['terjual'] <=> $a['terjual'];
    });
    
    // Ambil 3 Teratas
    $top3List = array_slice($finalItems, 0, 3);
    
    // 4. Cari Nama Barang (name) dari Database Lokal (MariaDB)
    foreach ($top3List as &$tItem) {
        $stmtName = $conn->prepare("SELECT name FROM item WHERE item_no = ?");
        $stmtName->bind_param("s", $tItem['itemNo']);
        $stmtName->execute();
        $resName = $stmtName->get_result();
        if ($rowName = $resName->fetch_assoc()) {
            $tItem['name'] = $rowName['name']; 
        } else {
            $tItem['name'] = 'Unknown Item'; 
        }
        $stmtName->close();
    }
    unset($tItem);
    
    // 5. Hapus data lama untuk hari ini, lalu Insert yang baru ke Database Lokal
    $delStmt = $conn->prepare("DELETE FROM last_terlaris WHERE tanggal = ?");
    $delStmt->bind_param("s", $dbDate);
    $delStmt->execute();
    $delStmt->close();
    
    $insertStmt = $conn->prepare("INSERT INTO last_terlaris (tanggal, itemNo, name, terjual, last_sync) VALUES (?, ?, ?, ?, NOW())");
    foreach ($top3List as $item) {
        $insertStmt->bind_param("sssi", 
            $dbDate,
            $item['itemNo'], 
            $item['name'], 
            $item['terjual']
        );
        $insertStmt->execute();
    }
    $insertStmt->close();
    
    // 6. Response Hasil Sync
    echo json_encode([
        'status'  => 'success',
        'message' => 'Data disinkronisasi dari Accurate',
        'data'    => $top3List
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
