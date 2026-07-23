<?php
/**
 * API DASHBOARD - RUGI LABA (HPP & Laba Bersih)
 * File: api/dashboard/rugi-laba.php
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
    
    $shouldSync = false;
    $localData = [];
    $recordExists = false;
    
    // 1. Cek Data di Database Lokal (MariaDB) - Tabel last_rugilaba
    $stmt = $conn->prepare("SELECT * FROM last_rugilaba WHERE tanggal = ?");
    $stmt->bind_param("s", $dbDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $recordExists = true;
        $localData = [
            'hpp'        => (float)$row['hpp'],
            'labaBersih' => (float)$row['labaBersih']
        ];
        
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
            'summary' => $localData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    // 3. Sync ke Accurate API (Hitung Rugi Laba Bulan Ini)
    $fromDate = date('01/m/Y'); // Awal bulan
    $toDate   = date('t/m/Y'); // Akhir bulan
    
    $accResult = $api->getPLAccountAmount($fromDate, $toDate);
    
    if (isset($accResult['success']) && $accResult['success']) {
        $data = $accResult['data']['d'] ?? [];
        
        $totalRevenue = 0;
        $totalCOGS = 0;
        $totalExpense = 0;
        $totalOtherIncome = 0;
        $totalOtherExpense = 0;
        
        foreach ($data as $item) {
            $type = $item['accountType'] ?? '';
            $amount = (float)($item['amount'] ?? 0);
            $lvl = isset($item['lvl']) ? (int)$item['lvl'] : 0;
            
            // Tambahkan semua akun Level 1 (Root)
            if ($lvl === 1 && $amount != 0) {
                if ($type === 'REVENUE') {
                    $totalRevenue += $amount;
                } elseif ($type === 'COST_OF_GOOD_SOLD') {
                    $totalCOGS += $amount;
                } elseif ($type === 'EXPENSE') {
                    $totalExpense += $amount;
                } elseif ($type === 'OTHER_INCOME') {
                    $totalOtherIncome += $amount;
                } elseif ($type === 'OTHER_EXPENSE') {
                    $totalOtherExpense += $amount;
                }
            }
        }
        
        // Perhitungan Laba Bersih
        $totalPendapatan = round($totalRevenue, 2);
        $hpp             = round($totalCOGS, 2);
        $labaKotor       = $totalPendapatan - $hpp;
        $labaOperasional = $labaKotor - round($totalExpense, 2);
        $labaBersih      = $labaOperasional + round($totalOtherIncome, 2) - round($totalOtherExpense, 2);
        
        $summaryData = [
            'hpp'        => $hpp,
            'labaBersih' => $labaBersih
        ];
        
        // 4. Hapus data lama untuk hari ini, lalu Insert yang baru ke Database Lokal
        $delStmt = $conn->prepare("DELETE FROM last_rugilaba WHERE tanggal = ?");
        $delStmt->bind_param("s", $dbDate);
        $delStmt->execute();
        $delStmt->close();
        
        $insertStmt = $conn->prepare("INSERT INTO last_rugilaba (hpp, labaBersih, tanggal, last_sync) VALUES (?, ?, ?, NOW())");
        $insertStmt->bind_param("dds", $summaryData['hpp'], $summaryData['labaBersih'], $dbDate);
        $insertStmt->execute();
        $insertStmt->close();
        
        // 5. Response Hasil Sync
        echo json_encode([
            'status'  => 'success',
            'message' => 'Data disinkronisasi dari Accurate',
            'summary' => $summaryData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
    } else {
        // Fallback lokal jika API gagal
        if ($recordExists) {
            echo json_encode([
                'status'  => 'success',
                'message' => 'Gagal sync ke Accurate, menampilkan data lokal terakhir',
                'summary' => $localData
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            exit;
        } else {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Gagal mengambil data rugi laba: ' . ($accResult['message'] ?? ''),
                'error'   => $accResult['error'] ?? null
            ]);
            exit;
        }
    }
    
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
