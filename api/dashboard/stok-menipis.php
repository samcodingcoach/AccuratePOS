<?php
/**
 * API DASHBOARD - STOK MENIPIS (3 TERKECIL)
 * File: api/dashboard/stok-menipis.php
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
    
    // 1. Cek Data di Database Lokal (MariaDB) - Tabel last_stok
    // Urutkan quantity ASC (terkecil di atas)
    $stmt = $conn->prepare("SELECT * FROM last_stok WHERE tanggal = ? ORDER BY quantity ASC");
    $stmt->bind_param("s", $dbDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $recordExists = true;
        $localData[] = [
            'itemNo'   => $row['itemNo'],
            'name'     => $row['name'],
            'quantity' => (int)$row['quantity']
        ];
        
        // Pengecekan umur data dari baris pertama saja
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
    
    // 3. Sync ke Accurate API (Mencari 3 Stok Tersedikit)
    $api = new AccurateAPI();
    $allItems = [];
    $page = 1;
    $hasMore = true;
    
    while ($hasMore) {
        // Panggil list stok tanpa filter gudang (semua cabang/gudang)
        $accResult = $api->getListStock('', 100, $page);
        
        if (isset($accResult['success']) && $accResult['success']) {
            $data = $accResult['data']['d'] ?? [];
            
            foreach ($data as $item) {
                // Kumpulkan semua item dengan qty-nya
                $allItems[] = [
                    'itemNo'   => $item['no'] ?? '',
                    'name'     => $item['name'] ?? '',
                    'quantity' => (float)($item['quantity'] ?? 0)
                ];
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
                    'message' => $accResult['error'] ?? 'Gagal mengambil data list stok dari Accurate',
                    'error'   => $accResult['error'] ?? null
                ]);
                exit;
            }
        }
    }
    
    // Urutkan semua item berdasarkan kuantitas secara Ascending (Terkecil -> Terbesar)
    usort($allItems, function($a, $b) {
        return $a['quantity'] <=> $b['quantity'];
    });
    
    // Ambil 3 Teratas (Tersedikit)
    $top3List = array_slice($allItems, 0, 3);
    
    // 4. Hapus data lama untuk hari ini, lalu Insert yang baru ke Database Lokal
    $delStmt = $conn->prepare("DELETE FROM last_stok WHERE tanggal = ?");
    $delStmt->bind_param("s", $dbDate);
    $delStmt->execute();
    $delStmt->close();
    
    $insertStmt = $conn->prepare("INSERT INTO last_stok (tanggal, itemNo, name, quantity, last_sync) VALUES (?, ?, ?, ?, NOW())");
    
    foreach ($top3List as $item) {
        $insertStmt->bind_param("sssd", 
            $dbDate,
            $item['itemNo'], 
            $item['name'], 
            $item['quantity']
        );
        $insertStmt->execute();
    }
    $insertStmt->close();
    
    // 5. Response Hasil Sync
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
