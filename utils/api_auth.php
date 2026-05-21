<?php
/**
 * File: utils/api_auth.php
 * Tugas: Memproteksi endpoint API dengan opsi Web Session & Secure Token Mobile
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_authenticated = false;

// =========================================================================
// OPSI 1: CEK WEB SESSION (Untuk Admin Web POS Browser)
// =========================================================================
if (isset($_SESSION['user_id'])) {
    $is_authenticated = true;
}

// =========================================================================
// OPSI 2: CEK SECURE TOKEN (Untuk Aplikasi C# / Mobile API / Postman)
// =========================================================================
if (!$is_authenticated) {
    $clientToken = '';

    // 1. Ambil HTTP request headers dari client
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    
    if (isset($headers['authorization'])) {
        $authHeader = trim($headers['authorization']);
        
        // Cari format 'Bearer <token_key>'
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $clientToken = $matches[1];
        }
    }

    // 2. Jika token ditemukan di header, langsung cocokkan di database
    if (!empty($clientToken)) {
        // Pastikan variabel koneksi ($conn) hasil require bootstrap.php tersedia
        if (isset($conn) && $conn instanceof mysqli) {
            
            $currentDateTime = date('Y-m-d H:i:s');
            
            // PERBAIKAN: Gunakan = untuk mencocokkan string hash secara langsung.
            // Tidak perlu lagi mengambil semua token lalu me-looping dengan password_verify()
            $sqlAuth = "SELECT id_token FROM token WHERE token_key = ? AND aktif = 1 AND tanggal_exp > ? LIMIT 1";
            $stmtAuth = $conn->prepare($sqlAuth);
            
            if ($stmtAuth) {
                // Bind parameter: string token dan string datetime
                $stmtAuth->bind_param("ss", $clientToken, $currentDateTime);
                $stmtAuth->execute();
                $resAuth = $stmtAuth->get_result();
                
                if ($resAuth && $resAuth->num_rows > 0) {
                    // Token ditemukan, identik, dan valid! Loloskan autentikasi.
                    $_SESSION['user_id'] = 'mobile_client'; 
                    $is_authenticated = true;
                }
                $stmtAuth->close();
            }
        }
    }
}

// =========================================================================
// JIKA KEDUANYA GAGAL: TOLAK AKSES (Kembalikan Error 401)
// =========================================================================
if (!$is_authenticated) {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(401);
    
    echo json_encode([
        'status'  => 'error',
        'message' => 'Akses ditolak. Token tidak valid, nonaktif, atau telah kedaluwarsa.',
        'code'    => 401
    ], JSON_PRETTY_PRINT);
    
    exit;
}
?>