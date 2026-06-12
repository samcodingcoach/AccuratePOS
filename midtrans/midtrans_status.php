<?php
error_reporting(E_ALL & ~E_NOTICE);
require_once 'config.php'; // Konfigurasi Midtrans

if (!isset($_GET['order_id']) || empty(trim($_GET['order_id']))) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Parameter order_id is required']);
    exit;
}

$order_id = urlencode(trim($_GET['order_id']));

// Tentukan environment URL berdasarkan konfigurasi di config.php
$baseUrl = \Midtrans\Config::$isProduction ? 'https://api.midtrans.com/v2' : 'https://api.sandbox.midtrans.com/v2';
$url = "$baseUrl/$order_id/status";

// Ambil server key yang sudah di-load dari tabel midtrans di config.php
$serverKey = \Midtrans\Config::$serverKey;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode($serverKey . ':')
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'CURL Error: ' . curl_error($ch)]);
    exit;
}

curl_close($ch);

$responseData = json_decode($response, true);

if (isset($responseData['transaction_status'])) {
    $filteredData = [
        'order_id' => $responseData['order_id'] ?? null,
        'gross_amount' => $responseData['gross_amount'] ?? null,
        'transaction_status' => $responseData['transaction_status'] ?? null,
        'transaction_id' => $responseData['transaction_id'] ?? null,
        'acquirer' => $responseData['acquirer'] ?? null,
        'settlement_time' => $responseData['settlement_time'] ?? null,
    ];
    
    $responseArray = [$filteredData];
    
    header('Content-Type: application/json');
    echo json_encode($responseArray);
} else {
    header('Content-Type: application/json');
    $errorMessage = $responseData['status_message'] ?? 'Unknown status or invalid response';
    echo json_encode(['error' => $errorMessage]);
}
?>
