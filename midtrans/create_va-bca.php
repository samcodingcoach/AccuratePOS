<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

require_once 'config.php';

// Ambil input JSON
$input = json_decode(file_get_contents('php://input'), true);

// Default order & amount
$orderId = $input['order_id'] ?? 'ORDER-' . time();
$grossAmount = isset($input['gross_amount']) ? (int)$input['gross_amount'] : 0;

// OPTIONAL: VA custom dari request
// kalau tidak dikirim → pakai dynamic VA
$customVa = $input['va_number'] ?? null;

if ($grossAmount <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'gross_amount wajib lebih dari 0'
    ]);
    exit;
}

/**
 * BASE PARAMS
 */
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

/**
 * JIKA ADA CUSTOM VA → STATIC VA MODE
 */
if (!empty($customVa)) {
    $params['bank_transfer']['bca_va'] = [
        'va_number' => $customVa
    ];
}

try {
    // HIT MIDTRANS
    $response = \Midtrans\CoreApi::charge($params);

    // ambil VA number
    $vaNumber = null;
    $bank = null;

    if (!empty($response->va_numbers)) {
        $vaNumber = $response->va_numbers[0]->va_number ?? null;
        $bank = $response->va_numbers[0]->bank ?? null;
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Virtual Account berhasil dibuat',
        'data' => [
            'transaction_id' => $response->transaction_id ?? null,
            'order_id' => $response->order_id ?? null,
            'gross_amount' => $response->gross_amount ?? null,
            'payment_type' => $response->payment_type ?? null,
            'transaction_status' => $response->transaction_status ?? null,
            'va_number' => $vaNumber,
            'bank' => $bank,
            'mode' => empty($customVa) ? 'dynamic' : 'static'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}