<?php
/**
 * API KARYAWAN - SAVE / UPDATE
 * File: api/karyawan/save.php
 */

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../utils/api_auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 1. Pastikan method adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method tidak diizinkan. Gunakan POST.'
    ]);
    exit;
}

try {
    // 2. Ambil payload
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    // 3. Validasi Field Wajib (name, salutation, transDate)
    $name = isset($input['name']) ? trim($input['name']) : '';
    $salutation = isset($input['salutation']) ? trim($input['salutation']) : '';
    $transDate = isset($input['transDate']) ? trim($input['transDate']) : '';

    if ($name === '' || $salutation === '' || $transDate === '') {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Parameter "name", "salutation", dan "transDate" wajib diisi.'
        ]);
        exit;
    }

    // 4. Susun payload untuk dikirim ke Accurate
    $payload = [
        'name'       => $name,
        'salutation' => strtoupper($salutation)
    ];

    // Format Tanggal transDate (ubah YYYY-MM-DD ke DD/MM/YYYY agar diterima Accurate)
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $transDate)) {
        $payload['transDate'] = date('d/m/Y', strtotime($transDate));
    } else {
        $payload['transDate'] = $transDate;
    }

    // Field Opsional
    $optionalFields = [
        'id', 'number', 'bankAccount', 'bankCode', 'bankName',
        'bankAccountName', 'domisiliType', 'email', 'mobilePhone'
    ];

    foreach ($optionalFields as $field) {
        if (array_key_exists($field, $input) && trim($input[$field]) !== '') {
            $payload[$field] = trim($input[$field]);
        }
    }

    if (isset($input['salesman'])) {
        $payload['salesman'] = filter_var($input['salesman'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }

    // Format Tanggal opsional: joinDate
    if (isset($input['joinDate']) && trim($input['joinDate']) !== '') {
        $jDate = trim($input['joinDate']);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $jDate)) {
            $payload['joinDate'] = date('d/m/Y', strtotime($jDate));
        } else {
            $payload['joinDate'] = $jDate;
        }
    }

    // 5. Eksekusi request ke AccurateAPI
    $api = new AccurateAPI();
    $result = $api->saveEmployee($payload);

    if (isset($result['success']) && $result['success']) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Data karyawan berhasil disimpan',
            'data'    => $result['data']['d'] ?? null
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $result['error'] ?? 'Gagal menyimpan data karyawan ke Accurate'
        ], JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
