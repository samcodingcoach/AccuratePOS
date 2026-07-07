<?php
/**
 * API STOK OPNAME ORDER - SAVE
 * File: api/stokopname-order/save.php
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../utils/api_auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method tidak diizinkan. Gunakan POST.'
    ]);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (empty($input)) {
    $input = $_POST;
}

$dataToSave = [];

// ID untuk Update / Delete
if (!empty($input['id'])) {
    $dataToSave['id'] = $input['id'];
}

// Number (jika kosong di-generate Accurate)
if (!empty($input['number'])) {
    $dataToSave['number'] = trim($input['number']);
}

// Field-field wajib / opsional
$fields = ['personCharged', 'warehouseName', 'startDate', 'description'];
foreach ($fields as $field) {
    if (isset($input[$field])) {
        $dataToSave[$field] = trim($input[$field]);
    }
}

// Otomatis isi transDate dengan tanggal sekarang (DD/MM/YYYY) jika kosong
if (!empty($input['transDate'])) {
    $dataToSave['transDate'] = trim($input['transDate']);
} else {
    $dataToSave['transDate'] = date('d/m/Y');
}

// Field array (itemCategoryListName dan userListAccount)
if (isset($input['itemCategoryListName']) && is_array($input['itemCategoryListName'])) {
    foreach ($input['itemCategoryListName'] as $index => $val) {
        $dataToSave['itemCategoryListName[' . $index . ']'] = $val;
    }
}

if (isset($input['userListAccount']) && is_array($input['userListAccount'])) {
    foreach ($input['userListAccount'] as $index => $val) {
        $dataToSave['userListAccount[' . $index . ']'] = $val;
    }
}

try {
    $api = new AccurateAPI();
    $result = $api->saveStockOpnameOrder($dataToSave);
    
    if (isset($result['success']) && $result['success']) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Data perintah stok opname berhasil disimpan',
            'data'    => $result['data'] ?? null
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $result['message'] ?? 'Gagal menyimpan perintah stok opname',
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
