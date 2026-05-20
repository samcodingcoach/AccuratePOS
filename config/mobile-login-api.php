<?php
/**
 * API ENDPOINT - MOBILE LOGIN (REVISI OUTPUT JSON)
 * File: config/mobile-login-api.php
 */

// 1. Set Header Standar API
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// 2. Proteksi HTTP Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan. Gunakan POST.', 'code' => 405], JSON_PRETTY_PRINT);
    exit;
}

// 3. Panggil Koneksi Database
require_once __DIR__ . '/koneksi.php';

try {
    // 4. Tangkap Payload Input
    $rawInput = file_get_contents('php://input');
    $inputData = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE || empty($inputData)) {
        $inputData = $_POST;
    }

    $email = isset($inputData['email']) ? trim($inputData['email']) : '';
    $password = isset($inputData['password']) ? $inputData['password'] : '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Email dan kata sandi wajib diisi.', 'code' => 400], JSON_PRETTY_PRINT);
        exit;
    }

    // 5. Cek Data User
    $sqlUser = "SELECT username, nama_lengkap, password FROM users WHERE email = ? AND aktif = 1 LIMIT 1";
    $stmtUser = $conn->prepare($sqlUser);
    
    if (!$stmtUser) {
        throw new Exception("Gagal menyiapkan statement users: " . $conn->error);
    }

    $stmtUser->bind_param("s", $email);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();

    if ($resultUser && $resultUser->num_rows > 0) {
        $user = $resultUser->fetch_assoc();

        // 6. Verifikasi Hash Password
        if (password_verify($password, $user['password'])) {
            
            // 7. Cari Token yang Aktif & Belum Expired
            $tokenBearer = null;
            $tokenValid = null;
            $currentDateTime = date('Y-m-d H:i:s');
            
            // REVISI: Tambahkan tanggal_exp pada kolom SELECT
            $sqlToken = "SELECT token_key, tanggal_exp FROM token WHERE aktif = 1 AND tanggal_exp > ? LIMIT 1";
            $stmtToken = $conn->prepare($sqlToken);
            
            if ($stmtToken) {
                $stmtToken->bind_param("s", $currentDateTime);
                $stmtToken->execute();
                $resToken = $stmtToken->get_result();
                
                if ($resToken && $resToken->num_rows > 0) {
                    $rowToken = $resToken->fetch_assoc();
                    $tokenBearer = "Bearer " . $rowToken['token_key'];
                    $tokenValid = $rowToken['tanggal_exp'];
                }
                $stmtToken->close();
            }

            if (!$tokenBearer) {
                http_response_code(401);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Login berhasil, namun tidak ada token aktif yang tersedia atau token telah kedaluwarsa.',
                    'code'    => 401
                ], JSON_PRETTY_PRINT);
                exit;
            }

            // 8. REVISI: Output JSON Standar Sesuai Permintaan
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Login Berhasil',
                'code'    => 200,
                'data'    => [
                    'username'     => $user['username'],
                    'nama_lengkap' => $user['nama_lengkap'],
                    'token_key'    => $tokenBearer,
                    'valid'        => $tokenValid
                ]
            ], JSON_PRETTY_PRINT);

        } else {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Email atau kata sandi tidak valid.', 'code' => 401], JSON_PRETTY_PRINT);
        }
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Email atau kata sandi tidak valid, atau akun dinonaktifkan.', 'code' => 401], JSON_PRETTY_PRINT);
    }
    
    $stmtUser->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan sistem internal: ' . $e->getMessage(), 'code' => 500], JSON_PRETTY_PRINT);
}
?>