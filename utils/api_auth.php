<?php
/**
 * File: utils/api_auth.php
 * Tugas: Memproteksi endpoint API dengan opsi Web Session & Secure Token Mobile (Hashed)
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
// OPSI 2: CEK SECURE TOKEN (Untuk Aplikasi C# / Mobile API)
// =========================================================================
if (!$is_authenticated) {
    $clientToken = '';

    // 1. Ambil HTTP request headers dari client
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    
    if (isset($headers['authorization'])) {
        $authHeader = trim($headers['authorization']);
        
        // Cari format 'Bearer <token_mentah_dari_c#>'
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $clientToken = $matches[1];
        }
    }

    // 2. Jika token ditemukan di header, lakukan verifikasi ke database
    if (!empty($clientToken)) {
        // Pastikan variabel koneksi ($conn) hasil require bootstrap.php tersedia
        if (isset($conn) && $conn instanceof mysqli) {
            
            $currentDateTime = date('Y-m-d H:i:s');
            
            // PERBAIKAN: Menggunakan nama tabel 'token' dan kolom 'token_key' sesuai struktur asli kamu
            $sqlAuth = "SELECT token_key FROM token WHERE aktif = 1 AND tanggal_exp > ?";
            $stmtAuth = $conn->prepare($sqlAuth);
            
            if ($stmtAuth) {
                $stmtAuth->bind_param("s", $currentDateTime);
                $stmtAuth->execute();
                $resAuth = $stmtAuth->get_result();
                
                if ($resAuth) {
                    // Ambil daftar hash token yang valid untuk dicocokkan dengan token mentah dari C#
                    while ($row = $resAuth->fetch_assoc()) {
                        if (password_verify($clientToken, $row['token_key'])) {
                            // Jika COCOK, loloskan autentikasi!
                            $_SESSION['user_id'] = 'mobile_client'; 
                            $is_authenticated = true;
                            break; // Hentikan perulangan karena token sudah valid
                        }
                    }
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