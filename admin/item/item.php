<?php
/**
 * HALAMAN ADMIN - VIEW ITEM LIST (Menggunakan cURL + Teruskan Session)
 * File: admin/item/item.php
 */

// 1. Jalankan session di halaman admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil data cookie selagi session masih terbuka
$sessionCookie = isset($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : '';

// JALAN KELUAR: Segera tutup kunci file session agar tidak terjadi deadlock saat cURL menembak list-lokal.php
session_write_close();

// 2. Ambil parameter dari URL browser (set default jika tidak ada)
$page       = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit      = 250; 
$barcode    = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';
$startDate  = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$endDate    = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

// 3. Bangun URL query untuk menembak API lokal
$apiBaseUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/../../api/item/list-lokal.php";

$queryParams = http_build_query([
    'page'       => $page,
    'limit'      => $limit,
    'barcode'    => $barcode,
    'start_date' => $startDate,
    'end_date'   => $endDate
]);

$apiUrlWithParams = $apiBaseUrl . "?" . $queryParams;

$rowNumber = ($page - 1) * $limit + 1;

// 4. Fetch data dari API menggunakan cURL
$items = [];
$pagination = ['current_page' => 1, 'total_page' => 1, 'has_more' => false, 'total_items' => 0];
$errorMessage = '';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrlWithParams);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Naikkan timeout sedikit menjadi 15 detik demi kestabilan

// Teruskan session yang tadi sudah disimpan variabelnya
if ($sessionCookie !== '') {
    curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . $sessionCookie);
}

$response = curl_exec($ch);

if (curl_errno($ch)) {
    $errorMessage = 'Gagal menghubungi API: ' . curl_error($ch);
} else {
    $decodes = json_decode($response, true);
    if (isset($decodes['status']) && $decodes['status'] === 'success') {
        $items = $decodes['data'] ?? [];
        $pagination = $decodes['pagination'] ?? $pagination;
    } else {
        $errorMessage = $decodes['message'] ?? 'Gagal memuat data dari API.';
    }
}
curl_close($ch);

// 5. BUKA KEMBALI SESSION DI SINI: Agar flash message import_flash di bawah tetap terbaca dengan normal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin - Daftar Barang</title>
</head>
<body>

    <h1>Daftar Barang</h1>

    <?php 
        if (isset($_SESSION['import_flash'])): 
            $flash = $_SESSION['import_flash'];
            $bgColor = ($flash['type'] === 'success') ? '#e2f0d9' : '#fce4d6';
            $borderColor = ($flash['type'] === 'success') ? '#385723' : '#c65911';
        ?>
            <div style="background-color: <?php echo $bgColor; ?>; border: 1px solid <?php echo $borderColor; ?>; padding: 10px; margin-bottom: 15px;">
                <p style="margin: 0;"><?php echo $flash['message']; ?></p>
                <?php if (!empty($flash['errors'])): ?>
                    <ul style="margin: 5px 0 0 0; font-size: 12px; color: red;">
                        <?php foreach ($flash['errors'] as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php 
            unset($_SESSION['import_flash']); // Hapus notifikasi setelah ditampilkan sekali
        endif; 
    ?>

    <fieldset style="margin-bottom: 20px; padding: 15px;">
        <legend>Panel Pencarian & Filter</legend>
        <form method="GET" action="">
            <label for="barcode">Barcode:</label>
            <input type="text" id="barcode" name="barcode" value="<?php echo htmlspecialchars($barcode); ?>" placeholder="Masukkan Barcode">
            
            &nbsp;&nbsp;&nbsp;
            
            <label for="start_date">Tgl Awal:</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
            
            <label for="end_date">Tgl Akhir:</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
            
            &nbsp;&nbsp;
            <button type="submit">Cari Data</button>
            <a href="item.php"><button type="button">Reset</button></a>
            
            <button type="button" onclick="if(confirm('Mulai import data dari Accurate sesuai filter tanggal?')) window.location.href='import-accurate.php?<?php echo http_build_query(['start_date' => $startDate, 'end_date' => $endDate]); ?>'">Import Accurate</button>
        </form>
    </fieldset>

    <?php if ($errorMessage): ?>
        <p style="color: red; font-weight: bold;">Error: <?php echo $errorMessage; ?></p>
    <?php endif; ?>

    <p>Total Data Tersaring: <strong><?php echo $pagination['total_items'] ?? 0; ?></strong> item</p>

    <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th>No</th>
                <th>ID</th>
                <th>Item No</th>
                <th>Nama Barang</th>
                <th>Barcode</th>
                <th>Harga</th>
                <th>Stok (Balance)</th>
                <th>User ID</th>
                <th>Last Sync</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="9" align="center">Data tidak ditemukan atau kosong.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td align="center"><?php echo $rowNumber++; ?></td>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo htmlspecialchars($item['item_no'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($item['name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($item['barcode'] ?? ''); ?></td>
                        <td align="right"><?php echo number_format($item['price'] ?? 0, 0, ',', '.'); ?></td>
                        <td align="center"><?php echo number_format($item['balance'] ?? 0, 0, ',', '.'); ?></td>
                        <td align="center"><?php echo $item['id_users'] ?? '-'; ?></td>
                        <td><?php echo $item['last_sync'] ?? '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 20px; text-align: center;">
        <p>Halaman <?php echo $pagination['current_page']; ?> dari <?php echo $pagination['total_page']; ?></p>
        
        <?php if ($page > 1): ?>
            <?php 
            $prevParams = $_GET;
            $prevParams['page'] = $page - 1;
            ?>
            <a href="?<?php echo http_build_query($prevParams); ?>"><button>&laquo; Sebelumnya</button></a>
        <?php else: ?>
            <button disabled>&laquo; Sebelumnya</button>
        <?php endif; ?>

        &nbsp;

        <?php if ($pagination['has_more']): ?>
            <?php 
            $nextParams = $_GET;
            $nextParams['page'] = $page + 1;
            ?>
            <a href="?<?php echo http_build_query($nextParams); ?>"><button>Selanjutnya &raquo;</button></a>
        <?php else: ?>
            <button disabled>Selanjutnya &raquo;</button>
        <?php endif; ?>
    </div>

</body>
</html>