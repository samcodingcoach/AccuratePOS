<?php
/**
 * API ENDPOINT - SAVE SALES INVOICE (CREATE / UPDATE FAKTUR)
 * File: api/penjualan/save-invoice.php
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../utils/api_auth.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method tidak diizinkan. Gunakan POST.'
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    $rawInput = file_get_contents('php://input');
    $inputData = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE || empty($inputData)) {
        $inputData = $_POST;
    }

    if (!empty($inputData['transDate'])) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $inputData['transDate'])) {
            $inputData['transDate'] = date('d/m/Y', strtotime($inputData['transDate']));
        }
    } else {
        $inputData['transDate'] = date('d/m/Y'); 
    }

    if (isset($inputData['taxable'])) {
        $inputData['taxable'] = filter_var($inputData['taxable'], FILTER_VALIDATE_BOOLEAN);
    } else {
        $inputData['taxable'] = false; 
    }

    if (isset($inputData['cashDiscount'])) {
        $inputData['cashDiscount'] = (float)str_replace('.', '', $inputData['cashDiscount']);
    }
    
    // Sanitasi Item Barang
    if (isset($inputData['detailItem']) && is_array($inputData['detailItem'])) {
        foreach ($inputData['detailItem'] as $key => $item) {
            if (isset($item['unitPrice'])) {
                $inputData['detailItem'][$key]['unitPrice'] = (float)str_replace('.', '', $item['unitPrice']);
            }
            if (isset($item['quantity'])) {
                $inputData['detailItem'][$key]['quantity'] = (float)str_replace('.', '', $item['quantity']);
            }
            if (isset($item['itemCashDiscount'])) {
                $inputData['detailItem'][$key]['itemCashDiscount'] = (float)str_replace('.', '', $item['itemCashDiscount']);
            }
            
            if (isset($item['detailSerialNumber']) && is_array($item['detailSerialNumber'])) {
                foreach ($item['detailSerialNumber'] as $snKey => $snItem) {
                    if (isset($snItem['quantity'])) {
                        $inputData['detailItem'][$key]['detailSerialNumber'][$snKey]['quantity'] = (float)str_replace('.', '', $snItem['quantity']);
                    }
                }
            }
        }
    }

    // Sanitasi Biaya Tambahan/Lain-lain (Independen)
    if (isset($inputData['detailExpense']) && is_array($inputData['detailExpense']) && count($inputData['detailExpense']) > 0) {
        foreach ($inputData['detailExpense'] as $key => $expense) {
            if (isset($expense['expenseAmount']) && trim($expense['expenseAmount']) !== '') {
                $inputData['detailExpense'][$key]['expenseAmount'] = (float)str_replace('.', '', $expense['expenseAmount']);
            }
        }
    }

    // Eksekusi API
    $api = new AccurateAPI();
    $result = $api->saveSalesInvoice($inputData);

    // Output Kembalian
    if (isset($result['success']) && $result['success']) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Faktur Penjualan berhasil disimpan ke Accurate Online.',
            'id'      => $result['data']['r']['id'] ?? null, 
            'number'  => $result['data']['r']['number'] ?? null,
            'log'     => $result['data'] ?? []
        ], JSON_PRETTY_PRINT);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $result['error'] ?? 'Gagal memproses pembuatan faktur.',
            'detail'  => $result
        ], JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan sistem internal: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>