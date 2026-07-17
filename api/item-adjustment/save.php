<?php
/**
 * API ITEM ADJUSTMENT - SAVE
 * File: api/item-adjustment/save.php
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

// ID untuk Update
if (!empty($input['id'])) {
    $dataToSave['id'] = $input['id'];
}

// Otomatis isi transDate dengan tanggal sekarang (DD/MM/YYYY) jika kosong
if (!empty($input['transDate'])) {
    $dataToSave['transDate'] = trim($input['transDate']);
} else {
    $dataToSave['transDate'] = date('d/m/Y');
}

$fields = ['adjustmentAccountNo', 'description'];
foreach ($fields as $field) {
    if (isset($input[$field])) {
        $dataToSave[$field] = trim($input[$field]);
    }
}

// Detail Item
if (isset($input['detailItem']) && is_array($input['detailItem'])) {
    foreach ($input['detailItem'] as $index => $item) {
        if (isset($item['itemAdjustmentType'])) {
            $dataToSave['detailItem[' . $index . '].itemAdjustmentType'] = trim($item['itemAdjustmentType']);
        }
        if (isset($item['itemNo'])) {
            $dataToSave['detailItem[' . $index . '].itemNo'] = trim($item['itemNo']);
        }
        if (isset($item['unitCost'])) {
            $dataToSave['detailItem[' . $index . '].unitCost'] = $item['unitCost'];
        }
        if (isset($item['detailNotes'])) {
            $dataToSave['detailItem[' . $index . '].detailNotes'] = trim($item['detailNotes']);
        }
        if (isset($item['quantity'])) {
            $dataToSave['detailItem[' . $index . '].quantity'] = $item['quantity'];
        }
        if (isset($item['warehouseName'])) {
            $dataToSave['detailItem[' . $index . '].warehouseName'] = trim($item['warehouseName']);
        }
        
        // Nested Detail Serial Number
        if (isset($item['detailSerialNumber']) && is_array($item['detailSerialNumber'])) {
            foreach ($item['detailSerialNumber'] as $snIndex => $snItem) {
                if (isset($snItem['serialNumberNo'])) {
                    $dataToSave['detailItem[' . $index . '].detailSerialNumber[' . $snIndex . '].serialNumberNo'] = trim($snItem['serialNumberNo']);
                }
                // Quantity untuk serial number umumnya adalah 1
                if (isset($snItem['quantity'])) {
                    $dataToSave['detailItem[' . $index . '].detailSerialNumber[' . $snIndex . '].quantity'] = $snItem['quantity'];
                } else {
                    $dataToSave['detailItem[' . $index . '].detailSerialNumber[' . $snIndex . '].quantity'] = 1;
                }
            }
        }
    }
}

try {
    $api = new AccurateAPI();
    $result = $api->saveItemAdjustment($dataToSave);
    
    if (isset($result['success']) && $result['success']) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Data item adjustment berhasil disimpan',
            'data'    => $result['data'] ?? null
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $result['message'] ?? 'Gagal menyimpan item adjustment',
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
