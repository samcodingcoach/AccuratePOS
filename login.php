<?php
// Memulai session
session_start();

// Jika sudah login, langsung lempar ke admin
if (isset($_SESSION['user_id'])) {
    header("Location: admin/index.php");
    exit;
}

require_once 'config/koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = isset($_POST['identifier']) ? trim($_POST['identifier']) : '';
    $password   = isset($_POST['password']) ? $_POST['password'] : '';

    if (!empty($identifier) && !empty($password)) {
        // Mencari user berdasarkan username ATAU email
        $stmt = $conn->prepare("SELECT id_users, username, email, password, aktif FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // 1. Cek apakah status user aktif (tinyint 1)
            if ($user['aktif'] == 0) {
                $error = "Akun Anda dinonaktifkan.";
            } else {
                // 2. Verifikasi Password Hash
                if (password_verify($password, $user['password'])) {
                    // Login Berhasil - Simpan data ke Session
                    $_SESSION['user_id']   = $user['id_users'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['email']     = $user['email'];
                    
                    // Direct ke halaman admin
                    header("Location: admin/index.php");
                    exit;
                } else {
                    $error = "Password salah.";
                }
            }
        } else {
            $error = "Username atau Email tidak ditemukan.";
        }
        $stmt->close();
    } else {
        $error = "Silakan isi semua kolom.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Project</title>
</head>
<body>
    <h2>Login</h2>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Username atau Email:</label><br>
        <input type="text" name="identifier" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Login</button>
    </form>
</body>
</html>