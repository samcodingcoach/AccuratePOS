<?php
/**
 * API COA (Chart of Accounts) - SALDO NERACA
 * File: api/coa/saldo-neraca.php
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../utils/api_auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// 1. Pastikan method adalah GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method tidak diizinkan. Gunakan GET.'
    ]);
    exit;
}

// 2. Ambil parameter asOfDate (opsional, default hari ini)
$asOfDate = isset($_GET['asOfDate']) && trim($_GET['asOfDate']) !== '' ? trim($_GET['asOfDate']) : date('d/m/Y');

try {
    // 3. Eksekusi request ke AccurateAPI
    $api = new AccurateAPI();
    $result = $api->getBSAccountAmount($asOfDate);

    if (isset($result['success']) && $result['success']) {
        $data = $result['data']['d'] ?? [];

        // 1. Filter: Hanya ambil akun dengan amount != 0
        $filteredData = array_filter($data, function($item) {
            return isset($item['amount']) && (float)$item['amount'] != 0;
        });

        // Re-index array setelah filter
        $filteredData = array_values($filteredData);

        // 2. Sort: isParent DESC (True lebih dulu daripada False)
        usort($filteredData, function($a, $b) {
            $parentA = !empty($a['isParent']) ? 1 : 0;
            $parentB = !empty($b['isParent']) ? 1 : 0;
            return $parentB - $parentA;
        });

        // 3. Kelompokkan Data (Grouping) berdasarkan accountType dan Hitung Total
        $groupedData = [
            'aset-aktiva' => [
                'total' => 0,
                'items' => []
            ],
            'liabilitas-hutang' => [
                'total' => 0,
                'items' => []
            ],
            'equitas-modal' => [
                'total' => 0,
                'items' => []
            ]
        ];

        foreach ($filteredData as $item) {
            $type = $item['accountType'] ?? '';
            // Jadikan 2 angka di belakang koma untuk amount masing-masing akun
            $amount = round((float)($item['amount'] ?? 0), 2);
            $item['amount'] = $amount; 
            
            $lvl = isset($item['lvl']) ? (int)$item['lvl'] : 0;
            $isParent = !empty($item['isParent']);

            // Kondisi penambahan total: Khusus level 1 DAN isParent 1
            $shouldAddToTotal = ($lvl === 1 && $isParent);

            if (in_array($type, ['CASH_BANK', 'ACCOUNT_RECEIVABLE', 'INVENTORY', 'FIXED_ASSET', 'ACCUMULATED_DEPRECIATION', 'OTHER_CURRENT_ASSET', 'OTHER_ASSET'])) {
                $groupedData['aset-aktiva']['items'][] = $item;
                if ($shouldAddToTotal) $groupedData['aset-aktiva']['total'] = round($groupedData['aset-aktiva']['total'] + $amount, 2);
            } elseif (in_array($type, ['ACCOUNT_PAYABLE', 'OTHER_CURRENT_LIABILITY', 'LONG_TERM_LIABILITY'])) {
                $groupedData['liabilitas-hutang']['items'][] = $item;
                if ($shouldAddToTotal) $groupedData['liabilitas-hutang']['total'] = round($groupedData['liabilitas-hutang']['total'] + $amount, 2);
            } elseif ($type === 'EQUITY') {
                $groupedData['equitas-modal']['items'][] = $item;
                if ($shouldAddToTotal) $groupedData['equitas-modal']['total'] = round($groupedData['equitas-modal']['total'] + $amount, 2);
            } else {
                // Tampung jika ada tipe akun di luar yang disebutkan
                if (!isset($groupedData['lainnya'])) {
                    $groupedData['lainnya'] = [
                        'total' => 0,
                        'items' => []
                    ];
                }
                $groupedData['lainnya']['items'][] = $item;
                if ($shouldAddToTotal) $groupedData['lainnya']['total'] = round($groupedData['lainnya']['total'] + $amount, 2);
            }
        }

        $totalAset = round($groupedData['aset-aktiva']['total'] ?? 0, 2);
        $totalLiabilitasEkuitas = round(($groupedData['liabilitas-hutang']['total'] ?? 0) + ($groupedData['equitas-modal']['total'] ?? 0), 2);
        $selisih = round($totalAset - $totalLiabilitasEkuitas, 2);

        echo json_encode([
            'status'  => 'success',
            'message' => 'Saldo neraca akun perkiraan berhasil diambil',
            'summary' => [
                'totalAset'              => $totalAset,
                'totalLiabilitasEkuitas' => $totalLiabilitasEkuitas,
                'selisih'                => $selisih
            ],
            'data'    => $groupedData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $result['error'] ?? 'Gagal mengambil saldo neraca dari Accurate'
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
