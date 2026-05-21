<?php
// Mulai session di bagian paling atas sebelum ada output HTML
session_start();

// Proteksi Halaman: Cek apakah session user_id sudah ada
// Jika tidak ada, berarti user belum login atau langsung menembak URL ini
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Halaman Admin</title>
</head>
<body>
    <!-- Menampilkan Greeting dengan mengambil data dari Session -->
    <!-- htmlspecialchars digunakan untuk mencegah serangan XSS -->
    <h2>Selamat Datang, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    
    <p>Ini adalah halaman utama dashboard admin Anda.</p>
    
    <br><br>
    
    <a href="item/item.php" style="color: blue; text-decoration: none; font-weight: bold;">[ Kelola Item ]</a>
    <a href="pos/list-faktur.php" style="color: blue; text-decoration: none; font-weight: bold;">[ Faktur Item ]</a>
    <!-- Link Logout yang mengarah ke file logout.php di luar folder admin -->
    <a href="../logout.php" style="color: red; text-decoration: none; font-weight: bold;">[ Logout ]</a>
</body>
</html>