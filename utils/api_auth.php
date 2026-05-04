<?php
/**
 * File: utils/api_auth.php
 * Tugas: Memproteksi endpoint API agar hanya bisa diakses jika sudah login
 */

// Mulai session (pastikan belum ada session_start di file pemanggilnya)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user_id ada di session
if (!isset($_SESSION['user_id'])) {
    // Beri tahu browser/client bahwa ini format JSON
    header('Content-Type: application/json; charset=UTF-8');
    
    // Set HTTP Status Code ke 401 (Unauthorized / Tidak ada izin)
    http_response_code(401);
    
    // Keluarkan pesan error JSON
    echo json_encode([
        'status'  => 'error',
        'message' => 'Akses ditolak. Anda harus login terlebih dahulu.',
        'code'    => 401
    ], JSON_PRETTY_PRINT);
    
    // Hentikan proses agar script di bawahnya tidak tereksekusi
    exit;
}
?>