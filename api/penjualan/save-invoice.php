<?php
/**
 * API ENDPOINT - SAVE SALES INVOICE (CREATE / UPDATE FAKTUR)
 * File: api/penjualan/save-invoice.php
 */

// 1. Muat konfigurasi dan class utama
require_once __DIR__ . '/../../bootstrap.php';

// 2. Proteksi endpoint menggunakan file utils Dual-Auth (Wajib login / Token Mobile)
require_once __DIR__ . '/../../utils/api_auth.php';

// 3. Set header agar output berupa JSON
header('Content-Type: application/json; charset=UTF-8');

// Proteksi HTTP Method: Hanya izinkan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method tidak diizinkan. Gunakan POST.'
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    // 4. Tangkap payload POST (Mendukung JSON Raw maupun Form-Data / x-www-form-urlencoded)
    $rawInput = file_get_contents('php://input');
    $inputData = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE || empty($inputData)) {
        $inputData = $_POST;
    }

    // 5. Normalisasi format tanggal HTML5 (YYYY-MM-DD) ke format Accurate (dd/mm/yyyy)
    if (!empty($inputData['transDate'])) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $inputData['transDate'])) {
            $inputData['transDate'] = date('d/m/Y', strtotime($inputData['transDate']));
        }
    } else {
        $inputData['transDate'] = date('d/m/Y'); // Default tanggal hari ini jika kosong
    }

    // =========================================================================
    // PERBAIKAN: Sanitasi Parameter "taxable" (Boolean)
    // =========================================================================
    if (isset($inputData['taxable'])) {
        // Mengonversi string "true"/"1" menjadi true, dan "false"/"0" menjadi false murni
        $inputData['taxable'] = filter_var($inputData['taxable'], FILTER_VALIDATE_BOOLEAN);
    } else {
        $inputData['taxable'] = false; // Default false jika tidak dikirim
    }
    // =========================================================================

    // 6. Sanitasi nilai uang/angka (Hilangkan karakter titik ribuan dari string input)
    if (isset($inputData['cashDiscount'])) {
        $inputData['cashDiscount'] = (float)str_replace('.', '', $inputData['cashDiscount']);
    }
    
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
            
            // Sanitasi angka untuk Serial Number di dalam detail item jika ada
            if (isset($item['detailSerialNumber']) && is_array($item['detailSerialNumber'])) {
                foreach ($item['detailSerialNumber'] as $snKey => $snItem) {
                    if (isset($snItem['quantity'])) {
                        $inputData['detailItem'][$key]['detailSerialNumber'][$snKey]['quantity'] = (float)str_replace('.', '', $snItem['quantity']);
                    }
                }
            }
        }
    }

    // 7. Kirim data ke Accurate Cloud via Core Class (Validasi terpusat berjalan di dalam sini)
    $api = new AccurateAPI();
    $result = $api->saveSalesInvoice($inputData);

    // 8. Berikan kembalian JSON Response akhir
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