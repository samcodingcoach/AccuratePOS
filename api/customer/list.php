<?php
/**
 * API CUSTOMER LIST - FULL DATA ENRICHED
 * File: api/customer/list.php
 * Note: Menggunakan detail fetch untuk menjamin Price Level & Category muncul.
 */

require_once __DIR__ . '/../../bootstrap.php';

// Set header standar API
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

try {
    $api = new AccurateAPI();

    // Pagination Params
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 25;
    $page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

    // 1. Ambil List Utama
    $result = $api->getCustomerList($limit, $page);

    if (!$result['success']) {
        throw new Exception($result['error'] ?? 'Gagal mengambil data dari Accurate');
    }

    $rawCustomers = $result['data']['d'] ?? [];
    $enrichedCustomers = [];

    // 2. Loop dan Tarik Detail untuk setiap Customer (Enrichment)
    foreach ($rawCustomers as $c) {
        $customerId = $c['id'];
        $priceLevel = 'Default';
        $categoryName = 'Umum';
        
        // Tarik detail untuk mendapatkan Price Level & Category yang pasti ada
        $detailRes = $api->getCustomerDetail($customerId);
        
        if ($detailRes['success'] && isset($detailRes['data']['d'])) {
            $d = $detailRes['data']['d'];
            
            // Mapping Price Category / Price Level
            if (isset($d['priceCategory']['name'])) {
                $priceLevel = $d['priceCategory']['name'];
            }
            
            // Mapping Customer Category
            if (isset($d['category']['name'])) {
                $categoryName = $d['category']['name'];
            }
        }

        // Susun struktur standar global
        $enrichedCustomers[] = [
            'id'            => $c['id'],
            'customer_no'   => $c['customerNo'] ?? ($c['no'] ?? null),
            'name'          => $c['name'] ?? 'Tanpa Nama',
            'email'         => $c['email'] ?? null,
            'phone'         => $c['mobilePhone'] ?? ($c['workPhone'] ?? null),
            'address'       => $c['address'] ?? '',
            'category'      => $categoryName,
            'price_level'   => $priceLevel,
            'status'        => [
                'is_suspended' => $c['suspended'] ?? false,
                'balance'      => (float)($c['balance'] ?? 0)
            ]
        ];
    }

    // 3. Output Response Global
    echo json_encode([
        'status'  => 'success',
        'message' => 'Data pelanggan berhasil dimuat dengan detail lengkap',
        'data'    => $enrichedCustomers,
        'pagination' => [
            'current_page' => (int)$page,
            'total_page'   => (int)($result['data']['sp']['pageCount'] ?? 0),
            'has_more'     => (bool)($result['data']['sp']['hasMore'] ?? false)
        ],
        'meta' => [
            'timestamp'   => date('c'),
            'api_version' => '1.2',
            'method'      => 'Eager Loading Detail'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    logError("Customer List Error: " . $e->getMessage(), __FILE__, __LINE__);
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}