<?php
/**
 * API ENDPOINT - LIST COA / GL ACCOUNT (ACCOUNT RECEIVABLE)
 * File: api/coa/list.php
 */

// 1. Muat konfigurasi dan class utama
require_once __DIR__ . '/../../bootstrap.php';

// 2. Proteksi endpoint menggunakan utils bawaan (Wajib login / Token)
require_once __DIR__ . '/../../utils/api_auth.php';

// 3. Set header agar output berupa JSON
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_PRETTY_PRINT);
    exit;
}

try {
    // Inisialisasi Accurate API
    $api = new AccurateAPI();

    // Tangkap parameter Paginasi & Pencarian
    $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : (isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 100);
    $page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Susun parameter untuk dilempar ke class core
    $params = [
        'sp.page'     => $page,
        'sp.pageSize' => $limit,
        'search'      => $search
    ];

    // Eksekusi fungsi getGLAccountList
    $result = $api->getGLAccountList($params);

    if (isset($result['success']) && $result['success']) {
        
        // Ambil data payload dari Accurate
        $rawCoaData = $result['data']['d'] ?? [];
        $paging     = $result['data']['sp'] ?? [];
        $enrichedCoa = [];

        // Hapus batas waktu eksekusi script agar tidak timeout (karena proses akan lama)
        set_time_limit(0);

        foreach ($rawCoaData as $coa) {
            $coaId = $coa['id'];
            $detailRes = $api->getGLAccountDetail($coaId);
            
            if ($detailRes['success'] && isset($detailRes['data']['d'])) {
                $d = $detailRes['data']['d'];
                // Perkaya dengan field detail
                $coa['balance']         = $d['balance'] ?? ($coa['balance'] ?? 0);
                $coa['accountTypeName'] = $d['accountTypeName'] ?? ($coa['accountTypeName'] ?? '');
                $coa['lvl']             = $d['lvl'] ?? ($coa['lvl'] ?? 1);
                $coa['asOf']            = $d['asOf'] ?? ($coa['asOf'] ?? '');
            }
            $enrichedCoa[] = $coa;

            // Rate Limiting: Accurate melarang lebih dari 8 hit/detik
            // usleep(150000) = Jeda 150 milidetik (sekitar 6 hit per detik)
            usleep(150000); 
        }

        echo json_encode([
            'status'  => 'success',
            'message' => 'Data Akun Perkiraan (COA) Pendapatan berhasil dimuat',
            'data'    => $enrichedCoa,
            'pagination' => [
                'current_page' => $page,
                'total_page'   => isset($paging['pageCount']) ? (int)$paging['pageCount'] : 0,
                'has_more'     => isset($paging['hasMore']) ? (bool)$paging['hasMore'] : false
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $result['error'] ?? 'Gagal mengambil data COA'
        ], JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>