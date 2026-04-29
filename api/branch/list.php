<?php
/**
 * API BRANCH LIST & DETAIL - STANDAR GLOBAL
 * File: api/branch/list.php
 * Standar: Consistent with Customer API Response
 */

require_once __DIR__ . '/../../bootstrap.php';

// 1. Set Standar Header
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Validasi Method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method ' . $_SERVER['REQUEST_METHOD'] . ' tidak diizinkan. Gunakan GET.'
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    $api = new AccurateAPI();
    
    // Ambil parameter ID (jika ingin detail)
    $branchId = $_GET['id'] ?? null;
    $cleanData = [];
    $message = "";

    if ($branchId) {
        // --- MODE DETAIL ---
        $result = $api->getBranchDetail($branchId);
        
        if ($result['success'] && isset($result['data']['d'])) {
            $d = $result['data']['d'];
            $cleanData = [
                'id'         => $d['id'] ?? null,
                'name'       => $d['name'] ?? null,
                'address'    => $d['address'] ?? null,
                'city'       => $d['city'] ?? null,
                'province'   => $d['province'] ?? null,
                'is_default' => $d['defaultBranch'] ?? false,
                'suspended'  => $d['suspended'] ?? false,
                'phone'      => $d['phoneNumber'] ?? null
            ];
            $message = "Detail branch berhasil dimuat";
        } else {
            // Handle jika ID tidak valid
            http_response_code(404);
            throw new Exception("Branch dengan ID $branchId tidak ditemukan.");
        }
    } else {
        // --- MODE LIST ---
        $result = $api->getBranchList();
        
        if ($result['success'] && isset($result['data']['d'])) {
            $rawList = $result['data']['d'];
            foreach ($rawList as $b) {
                $cleanData[] = [
                    'id'         => $b['id'] ?? null,
                    'name'       => $b['name'] ?? null,
                    'address'    => $b['address'] ?? null,
                    'is_default' => $b['defaultBranch'] ?? false,
                    'suspended'  => $b['suspended'] ?? false
                ];
            }
            $message = "Daftar branch berhasil dimuat";
        } else {
            throw new Exception($result['error'] ?? 'Gagal mengambil daftar branch');
        }
    }

    // 2. Final Response Standar Global
    echo json_encode([
        'status'  => 'success',
        'message' => $message,
        'data'    => $cleanData,
        'meta'    => [
            'timestamp'   => date('c'),
            'api_version' => '1.0',
            'request_id'  => uniqid('brn_'),
            'accurate_status' => $result['http_code'] ?? 200
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    // 3. Error Handling
    logError("Branch API Error: " . $e->getMessage(), __FILE__, __LINE__);
    
    // Sesuaikan status code jika 404 atau default 500
    if (http_response_code() === 200) http_response_code(500);

    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
        'data'    => null
    ], JSON_PRETTY_PRINT);
}