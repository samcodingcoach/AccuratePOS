<?php
// require_once dirname(__FILE__) . '/vendor/autoload.php'; // Composer
require_once 'Midtrans.php'; // Jika manual

require_once __DIR__ . '/../config/koneksi.php';

// Default Server Key
$serverKey = 'SB-Mid-server-IV-Hqe8N16ymtZ4Z55HnxyhY';

try {
    $sqlMidtrans = "SELECT ServerKey FROM midtrans ORDER BY UpdateAt DESC LIMIT 1";
    $resMidtrans = $conn->query($sqlMidtrans);
    if ($resMidtrans && $resMidtrans->num_rows > 0) {
        $rowMidtrans = $resMidtrans->fetch_assoc();
        if (!empty($rowMidtrans['ServerKey'])) {
            $serverKey = $rowMidtrans['ServerKey'];
        }
    }
} catch (Exception $e) {
    // Biarkan default key jika terjadi error DB
}

\Midtrans\Config::$serverKey = $serverKey;
\Midtrans\Config::$isProduction = false; // Ganti ke true untuk mode produksi
\Midtrans\Config::$isSanitized = true;   // Menyalakan sanitasi data
\Midtrans\Config::$is3ds = true;
?>
