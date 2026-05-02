<?php
/**
 * KONEKSI DATABASE LOKAL (NON-API VERSION)
 * File: koneksi.php
 */

$host     = 'localhost';
$username = 'samsu';
$password = 'samsu';
$database = 'pos-accurate';

// Set Timezone WITA (Samarinda)
date_default_timezone_set("Asia/Makassar");

// Inisialisasi MySQLi
$conn = new mysqli($host, $username, $password, $database);

// Cek Koneksi secara langsung
if ($conn->connect_error) {
    // Tampilan error sederhana untuk browser
    die("<div style='color:red; font-family:sans-serif;'>
            <h3>Koneksi database gagal!</h3>
            <p>Pesan Error: " . $conn->connect_error . "</p>
         </div>");
}

// Gunakan charset utf8mb4 agar support semua karakter
$conn->set_charset("utf8mb4");

// Variabel $conn sekarang siap digunakan di halaman Anda
?>