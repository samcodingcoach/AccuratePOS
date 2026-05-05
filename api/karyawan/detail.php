<?php
/**
 * File: api/karyawan/detail.php
 * Deskripsi: Mengambil detail 1 karyawan berdasarkan ID atau Nomor Karyawan
 */

// 1. Muat konfigurasi dan class utama
require_once __DIR__ . '/../../bootstrap.php';

// 2. Proteksi endpoint (Hanya yang sudah login yang bisa akses)
require_once __DIR__ . '/../../utils/api_auth.php';

// 3. Set header agar output berupa JSON
header('Content-Type: application/json; charset=UTF-8');

// Inisialisasi API
$api = new AccurateAPI();

// Tangkap parameter dari URL
$id = isset($_GET['id']) ? $_GET['id'] : null;
$number = isset($_GET['number']) ? $_GET['number'] : null;

// Validasi awal: Pastikan salah satu parameter diisi
if (empty($id) && empty($number)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Parameter id atau number (Nomor Karyawan) wajib diisi.'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Panggil fungsi dari AccurateAPI.php
$result = $api->getEmployeeDetail($id, $number);

// Format dan kembalikan response
if ($result['success']) {
    echo json_encode([
        'status' => 'success',
        // Response data detail biasanya langsung berada di dalam array 'd', bukan array list
        'data' => $result['data']['d'] 
    ], JSON_PRETTY_PRINT);
} else {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $result['error'] ? $result['error'] : 'Data karyawan tidak ditemukan atau terjadi kesalahan'
    ], JSON_PRETTY_PRINT);
}
?>