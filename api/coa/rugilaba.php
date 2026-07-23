<?php
/**
 * API COA (Chart of Accounts) - RUGI LABA
 * File: api/coa/rugilaba.php
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../utils/api_auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method tidak diizinkan. Gunakan GET.'
    ]);
    exit;
}

// Ambil parameter fromDate dan toDate
// Default: bulan ini (tanggal 01 sampai akhir bulan)
$fromDate = isset($_GET['fromDate']) && trim($_GET['fromDate']) !== '' ? trim($_GET['fromDate']) : date('01/m/Y');
$toDate = isset($_GET['toDate']) && trim($_GET['toDate']) !== '' ? trim($_GET['toDate']) : date('t/m/Y');

try {
    // Eksekusi request ke AccurateAPI
    $api = new AccurateAPI();
    $result = $api->getPLAccountAmount($fromDate, $toDate);

    if (isset($result['success']) && $result['success']) {
        $data = $result['data']['d'] ?? [];
        
        // 1. Filter: Hanya ambil akun dengan amount != 0
        $filteredData = array_filter($data, function($item) {
            return isset($item['amount']) && (float)$item['amount'] != 0;
        });
        $filteredData = array_values($filteredData);

        // 2. Sort: isParent DESC
        usort($filteredData, function($a, $b) {
            $parentA = !empty($a['isParent']) ? 1 : 0;
            $parentB = !empty($b['isParent']) ? 1 : 0;
            return $parentB - $parentA;
        });

        // 3. Kelompokkan Data
        $groupedData = [
            'revenue'       => ['total' => 0, 'items' => []],
            'cogs'          => ['total' => 0, 'items' => []],
            'expense'       => ['total' => 0, 'items' => []],
            'other-income'  => ['total' => 0, 'items' => []],
            'other-expense' => ['total' => 0, 'items' => []],
            'lainnya'       => ['total' => 0, 'items' => []]
        ];

        foreach ($filteredData as $item) {
            $type = $item['accountType'] ?? '';
            $amount = round((float)($item['amount'] ?? 0), 2);
            $item['amount'] = $amount; 
            
            $lvl = isset($item['lvl']) ? (int)$item['lvl'] : 0;
            $isParent = !empty($item['isParent']);
            $shouldAddToTotal = ($lvl === 1); // Tambahkan semua akun level 1 (Root), terlepas ia punya sub-akun atau berdiri sendiri

            if ($type === 'REVENUE') {
                $groupedData['revenue']['items'][] = $item;
                if ($shouldAddToTotal) $groupedData['revenue']['total'] = round($groupedData['revenue']['total'] + $amount, 2);
            } elseif ($type === 'COST_OF_GOOD_SOLD') {
                $groupedData['cogs']['items'][] = $item;
                if ($shouldAddToTotal) $groupedData['cogs']['total'] = round($groupedData['cogs']['total'] + $amount, 2);
            } elseif ($type === 'EXPENSE') {
                $groupedData['expense']['items'][] = $item;
                if ($shouldAddToTotal) $groupedData['expense']['total'] = round($groupedData['expense']['total'] + $amount, 2);
            } elseif ($type === 'OTHER_INCOME') {
                $groupedData['other-income']['items'][] = $item;
                if ($shouldAddToTotal) $groupedData['other-income']['total'] = round($groupedData['other-income']['total'] + $amount, 2);
            } elseif ($type === 'OTHER_EXPENSE') {
                $groupedData['other-expense']['items'][] = $item;
                if ($shouldAddToTotal) $groupedData['other-expense']['total'] = round($groupedData['other-expense']['total'] + $amount, 2);
            } else {
                $groupedData['lainnya']['items'][] = $item;
                if ($shouldAddToTotal) $groupedData['lainnya']['total'] = round($groupedData['lainnya']['total'] + $amount, 2);
            }
        }

        // 4. Hitung Summary (Sesuai hierarki akuntansi dan formula Laba Bersih)
        $totalRevenue = $groupedData['revenue']['total'];
        $totalCOGS = $groupedData['cogs']['total'];
        $totalExpense = $groupedData['expense']['total'];
        $totalOtherIncome = $groupedData['other-income']['total'];
        $totalOtherExpense = $groupedData['other-expense']['total'];

        $totalPendapatan = $totalRevenue; 
        $hpp = $totalCOGS;
        $labaKotor = round($totalPendapatan - $hpp, 2);
        
        $bebanOperasional = $totalExpense;
        $labaOperasional = round($labaKotor - $bebanOperasional, 2);
        
        $pendapatanLainLain = $totalOtherIncome;
        $bebanLainLain = $totalOtherExpense;
        
        $labaBersih = round($labaOperasional + $pendapatanLainLain - $bebanLainLain, 2);

        echo json_encode([
            'status'  => 'success',
            'message' => 'Data rugi laba berhasil diambil',
            'summary' => [
                'totalPendapatan'    => $totalPendapatan,
                'hpp'                => $hpp,
                'labaKotor'          => $labaKotor,
                'bebanOperasional'   => $bebanOperasional,
                'labaOperasional'    => $labaOperasional,
                'pendapatanLainLain' => $pendapatanLainLain,
                'bebanLainLain'      => $bebanLainLain,
                'labaBersih'         => $labaBersih
            ],
            'data'    => $groupedData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $result['message'] ?? 'Gagal mengambil data rugi laba',
            'error'   => $result['error'] ?? null
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan internal server.',
        'error'   => $e->getMessage()
    ]);
}
?>
