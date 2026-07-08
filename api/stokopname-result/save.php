<?php
/**
 * API STOK OPNAME RESULT - SAVE
 * File: api/stokopname-result/save.php
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

// Otomatis isi transDate dengan tanggal sekarang (DD/MM/YYYY) jika kosong
if (!empty($input['transDate'])) {
    $dataToSave['transDate'] = trim($input['transDate']);
} else {
    $dataToSave['transDate'] = date('d/m/Y');
}

// Field-field string
$fields = ['orderNumber', 'description'];
foreach ($fields as $field) {
    if (isset($input[$field])) {
        $dataToSave[$field] = trim($input[$field]);
    }
}

// Detail Item
if (isset($input['detailItem']) && is_array($input['detailItem'])) {
    foreach ($input['detailItem'] as $index => $item) {
        if (isset($item['itemNo'])) {
            $dataToSave['detailItem[' . $index . '].itemNo'] = trim($item['itemNo']);
        }
        if (isset($item['quantity'])) {
            $dataToSave['detailItem[' . $index . '].quantity'] = $item['quantity'];
        }

        // Nested Detail Serial Number
        if (isset($item['detailSerialNumber']) && is_array($item['detailSerialNumber'])) {
            foreach ($item['detailSerialNumber'] as $snIndex => $snItem) {
                if (isset($snItem['_status'])) {
                    $dataToSave['detailItem[' . $index . '].detailSerialNumber[' . $snIndex . ']._status'] = trim($snItem['_status']);
                }
                if (isset($snItem['id'])) {
                    $dataToSave['detailItem[' . $index . '].detailSerialNumber[' . $snIndex . '].id'] = trim($snItem['id']);
                }
                if (isset($snItem['quantity'])) {
                    $dataToSave['detailItem[' . $index . '].detailSerialNumber[' . $snIndex . '].quantity'] = $snItem['quantity'];
                }
                if (isset($snItem['serialNumberNo'])) {
                    $dataToSave['detailItem[' . $index . '].detailSerialNumber[' . $snIndex . '].serialNumberNo'] = trim($snItem['serialNumberNo']);
                }
            }
        }
    }
}

try {
    $api = new AccurateAPI();
    $result = $api->saveStockOpnameResult($dataToSave);
    
    if (isset($result['success']) && $result['success']) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Data hasil stok opname berhasil disimpan',
            'data'    => $result['data'] ?? null
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $result['message'] ?? 'Gagal menyimpan hasil stok opname',
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
