<?php
/**
 * API AKSES - LIST (Access Privilege)
 * File: api/akses/list.php
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

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$params = [];

if (!empty($search)) {
    $params['filter.keywords.op'] = 'EQUAL';
    $params['filter.keywords.val'] = [$search];
}

try {
    $api = new AccurateAPI();
    $result = $api->getAccessPrivilegeList($params, $page, $limit);
    
    if (isset($result['success']) && $result['success']) {
        $data = $result['data']['d'] ?? [];
        
        // Loop untuk mengambil userList dari masing-masing detail
        foreach ($data as &$item) {
            if (isset($item['id'])) {
                $detailResult = $api->getAccessPrivilegeDetail($item['id']);
                
                $mappedUserList = [];
                if (isset($detailResult['success']) && $detailResult['success']) {
                    $rawUserList = $detailResult['data']['d']['userList'] ?? [];
                    foreach ($rawUserList as $user) {
                        if (!empty($user['email'])) {
                            $mappedUserList[] = [
                                'email' => $user['email']
                            ];
                        }
                    }
                }
                
                $item['userList'] = $mappedUserList;
                
                // Beri jeda 150ms agar tidak melebihi batas 8 hit/detik Accurate
                usleep(150000); 
            }
        }
        
        echo json_encode([
            'status'  => 'success',
            'message' => 'Data hak akses berhasil diambil',
            'data'    => $data,
            'pagination' => $result['data']['sp'] ?? []
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $result['message'] ?? 'Gagal mengambil data hak akses',
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
