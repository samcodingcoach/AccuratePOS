<?php
/**
 * API ENDPOINT - SAVE SALES RECEIPT (PENERIMAAN PENJUALAN / PELUNASAN)
 * File: api/penjualan/save-receipt.php
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
    // Penanganan transDate
    if (!empty($inputData['transDate'])) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $inputData['transDate'])) {
            $inputData['transDate'] = date('d/m/Y', strtotime($inputData['transDate']));
        }
    }
    // Penanganan chequeDate
    if (!empty($inputData['chequeDate'])) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $inputData['chequeDate'])) {
            $inputData['chequeDate'] = date('d/m/Y', strtotime($inputData['chequeDate']));
        }
    }

    // 6. Sanitasi nilai uang/angka (Hilangkan karakter titik ribuan dari string input)
    if (isset($inputData['chequeAmount'])) {
        $inputData['chequeAmount'] = (float)str_replace('.', '', $inputData['chequeAmount']);
    }
    
    if (isset($inputData['detailInvoice']) && is_array($inputData['detailInvoice'])) {
        foreach ($inputData['detailInvoice'] as $key => $inv) {
            if (isset($inv['paymentAmount'])) {
                $inputData['detailInvoice'][$key]['paymentAmount'] = (float)str_replace('.', '', $inv['paymentAmount']);
            }
        }
    }

    // 7. Bersihkan pembungkus tanda kurung pada paymentMethod jika terkirim dalam format string "(QRIS)"
    if (isset($inputData['paymentMethod'])) {
        $inputData['paymentMethod'] = trim($inputData['paymentMethod'], "() ");
    }

    // 8. Kirim data ke Accurate Cloud via Core Class (Mengeksekusi validasi berurutan)
    $api = new AccurateAPI();
    $result = $api->saveSalesReceipt($inputData);

    // 9. Berikan kembalian JSON Response akhir
    if (isset($result['success']) && $result['success']) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Penerimaan Penjualan (Pelunasan) berhasil disimpan ke Accurate Online.',
            'id'      => $result['data']['r']['id'] ?? null, 
            'number'  => $result['data']['r']['number'] ?? null,
            'log'     => $result['data'] ?? []
        ], JSON_PRETTY_PRINT);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $result['error'] ?? 'Gagal memproses penerimaan penjualan.',
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