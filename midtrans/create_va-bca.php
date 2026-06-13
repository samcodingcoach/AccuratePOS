<?php
// Set header untuk API JSON
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Gunakan konfigurasi Midtrans bawaan project yang sudah mengambil ServerKey dari DB
require_once 'config.php'; 

// Ambil payload JSON dari request
$input = json_decode(file_get_contents('php://input'), true);

$orderId = isset($input['order_id']) ? $input['order_id'] : 'ORDER-' . time();
$grossAmount = isset($input['gross_amount']) ? (int)$input['gross_amount'] : 0;

if ($grossAmount <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Parameter gross_amount wajib diisi dan harus lebih dari 0'
    ]);
    exit;
}

// Setup parameter untuk BCA Virtual Account
$params = [
    'payment_type' => 'bank_transfer',
    'transaction_details' => [
        'order_id' => $orderId,
        'gross_amount' => $grossAmount,
    ],
    'bank_transfer' => [
        'bank' => 'bca'
    ]
];

try {
    // Memanggil API Midtrans (Core API charge)
    $response = \Midtrans\CoreApi::charge($params);

    // Cek apakah response sukses (Status 201 Created atau 200 OK)
    if (isset($response->status_code) && ($response->status_code == '201' || $response->status_code == '200')) {
        $vaNumber = '';
        $bank = '';
        
        if (isset($response->va_numbers) && count($response->va_numbers) > 0) {
            $vaNumber = $response->va_numbers[0]->va_number;
            $bank = $response->va_numbers[0]->bank;
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'BCA Virtual Account berhasil dibuat',
            'data' => [
                'transaction_id' => $response->transaction_id ?? null,
                'order_id' => $response->order_id ?? null,
                'gross_amount' => $response->gross_amount ?? null,
                'payment_type' => $response->payment_type ?? null,
                'transaction_status' => $response->transaction_status ?? null,
                'va_number' => $vaNumber,
                'bank' => $bank
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $response->status_message ?? 'Gagal membuat Virtual Account'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>
