<?php
require_once '../config/koneksi.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

// Ambil dan bersihkan input
$nama_lengkap = isset($_POST['nama_lengkap']) ? trim($_POST['nama_lengkap']) : '';
$username     = isset($_POST['username']) ? trim($_POST['username']) : '';
$email        = isset($_POST['email']) ? trim($_POST['email']) : '';
$password     = isset($_POST['password']) ? $_POST['password'] : '';
$aktif        = isset($_POST['aktif']) ? (int)$_POST['aktif'] : 0;
$hint         = isset($_POST['hint']) ? trim($_POST['hint']) : '';

// 1. VALIDASI: Cek apakah Username atau Email sudah ada
// Kita gunakan OR agar pengecekan lebih efisien dalam satu query
$checkQuery = $conn->prepare("SELECT username, email FROM users WHERE username = ? OR email = ? LIMIT 1");
$checkQuery->bind_param("ss", $username, $email);
$checkQuery->execute();
$result = $checkQuery->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['username'] === $username) {
        $msg = 'Username sudah digunakan oleh orang lain!';
    } else {
        $msg = 'Email sudah terdaftar! Gunakan email lain.';
    }
    
    echo json_encode(['success' => false, 'message' => $msg]);
    $checkQuery->close();
    exit;
}
$checkQuery->close();

// 2. HASHING PASSWORD
$password_aman = password_hash($password, PASSWORD_BCRYPT);

// 3. SIMPAN DATA
$stmt = $conn->prepare("INSERT INTO users (username, password, email, aktif, hint, nama_lengkap) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssiss", $username, $password_aman, $email, $aktif, $hint, $nama_lengkap);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'User ' . $username . ' berhasil didaftarkan!'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Gagal menyimpan ke database: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>