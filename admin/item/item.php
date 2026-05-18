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
$startDate = (isset($_GET['start_date']) && trim($_GET['start_date']) !== '') ? trim($_GET['start_date']) : date('Y-m-d');
$endDate   = (isset($_GET['end_date']) && trim($_GET['end_date']) !== '') ? trim($_GET['end_date']) : date('Y-m-d');

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

/**
 * FUNGSI HELPER FORMAT TANGGAL LAST SYNC
 * Jika hari ini: "Today, 16:25"
 * Jika lewat hari: "17 Mei 2026 16:25"
 */
function formatLastSync($datetimeStr) {
    if (empty($datetimeStr) || $datetimeStr === '-') return '-';
    
    $timestamp = strtotime($datetimeStr);
    if (!$timestamp) return htmlspecialchars($datetimeStr);

    $datePart = date('Y-m-d', $timestamp);
    $todayPart = date('Y-m-d');

    // Array nama bulan Indonesia
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    if ($datePart === $todayPart) {
        return 'Today, ' . date('H:i', $timestamp);
    } else {
        $mNum = (int)date('n', $timestamp);
        return date('j', $timestamp) . ' ' . $months[$mNum] . ' ' . date('Y H:i', $timestamp);
    }
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
            
            <button type="button" onclick="
                if(confirm('Mulai import data dari Accurate sesuai filter tanggal?')) {
                    let st = document.getElementById('start_date').value;
                    let ed = document.getElementById('end_date').value;
                    
                    // Jika input kosong di browser, paksa ambil tanggal hari ini (WITA) via JS
                    let today = new Date().toISOString().slice(0, 10);
                    if(!st) st = today;
                    if(!ed) ed = today;
                    
                    window.location.href='import-accurate.php?start_date=' + st + '&end_date=' + ed;
                }
            ">Import Accurate</button>
        </form>
    </fieldset>

    <?php if ($errorMessage): ?>
        <p style="color: red; font-weight: bold;">Error: <?php echo $errorMessage; ?></p>
    <?php endif; ?>

    <div style="margin-bottom: 10px; background: #f9f9f9; padding: 10px; border: 1px solid #ddd;">
        <span style="font-weight: bold;">Multi Action Terpilih:</span> &nbsp;
        <button type="button" onclick="prosesMultiActionDemo()">Cek Item No Terpilih</button>
        
        <button type="button" style="background-color: #0066cc; color: white; font-weight: bold;" onclick="jalankanMultiUpdate('harga')">Multi Update Harga</button>
        <button type="button" style="background-color: #4CAF50; color: white; font-weight: bold;" onclick="jalankanMultiUpdate('stok')">Multi Update Stok</button>
        
        <span id="queue_status" style="margin-left: 15px; font-weight: bold; color: #cc0000; display: none;"></span>
    </div>

    <p>Total Data Tersaring: <strong><?php echo $pagination['total_items'] ?? 0; ?></strong> item</p>

    <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th width="30"><input type="checkbox" id="check_all" onclick="toggleSelectAll(this)"></th>
                <th width="40">No</th>
                <th>Item No</th>
                <th>Barcode</th>
                <th>Nama Barang</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Last Sync</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="8" align="center">Data tidak ditemukan atau kosong.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td align="center">
                            <input type="checkbox" class="item-checkbox" value="<?php echo htmlspecialchars($item['item_no'] ?? ''); ?>">
                        </td>

                        <td align="center"><?php echo $rowNumber++; ?></td>
                        
                        <td>
                            <a href="pricestock.php?no=<?php echo urlencode($item['item_no'] ?? ''); ?>&priceCategoryName=Umum" style="text-decoration: none; color: #0066cc; font-weight: bold;">
                                <?php echo htmlspecialchars($item['item_no'] ?? ''); ?>
                            </a>
                        </td>
                        
                        <td><?php echo htmlspecialchars($item['barcode'] ?? ''); ?></td>
                        
                        <td>
                            <?php if (!empty($item['image'])): ?>
                                <a href="https://odin.accurate.id/<?php echo ltrim($item['image'], '/'); ?>" target="_blank" style="text-decoration: none; color: #0066cc; font-weight: 500;">
                                    <?php echo htmlspecialchars($item['name'] ?? ''); ?> 🖼️
                                </a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($item['name'] ?? ''); ?>
                            <?php endif; ?>
                        </td>
                        
                        <td align="right"><?php echo number_format($item['price'] ?? 0, 0, ',', '.'); ?></td>
                        
                        <td align="center"><?php echo number_format($item['balance'] ?? 0, 0, ',', '.'); ?></td>
                        
                        <td align="center"><?php echo formatLastSync($item['last_sync'] ?? ''); ?></td>
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

    <script>
        /**
         * Fungsi Check All / Uncheck All barang di tabel
         */
        function toggleSelectAll(master) {
            const checkboxes = document.getElementsByClassName('item-checkbox');
            for (let i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = master.checked;
            }
        }

        /**
         * Fungsi Pengumpul (Collect) Kolom Item No yang sedang dicentang
         * @returns Array list item_no
         */
        function getSelectedItemNos() {
            const checkboxes = document.getElementsByClassName('item-checkbox');
            const selectedItemNos = [];
            
            for (let i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i].checked) {
                    // Masukkan value (item_no) yang dicentang ke dalam array array
                    selectedItemNos.push(checkboxes[i].value);
                }
            }
            return selectedItemNos;
        }

        /**
         * Fungsi Demo Aksi untuk memvalidasi koleksi data item_no
         */
        function prosesMultiActionDemo() {
            const itemNos = getSelectedItemNos();
            
            if (itemNos.length === 0) {
                alert('Silakan pilih minimal satu barang terlebih dahulu!');
                return;
            }
            
            // Contoh implementasi visual pengumpulan item_no
            alert('Berhasil mengumpulkan ' + itemNos.length + ' item_no:\n' + itemNos.join(', '));
            
            /* Rencana Pengembangan Lanjutan:
               Anda bisa melempar array 'itemNos' ini via AJAX fetch POST, 
               atau memasukkannya ke input hidden form untuk diproses di file PHP lain.
            */
        }

        /**
         * Fungsi Pemicu Awal Multi Update Massal
         * @param {string} tipe - 'harga' atau 'stok'
         */
        function jalankanMultiUpdate(tipe) {
            const selectedItems = getSelectedItemNos(); // Ambil semua item_no yang dicentang [cite: 64]
            
            if (selectedItems.length === 0) {
                alert('Silakan centang minimal satu barang terlebih dahulu!');
                return;
            }

            if (!confirm(`Apakah Anda yakin ingin memproses update ${tipe} untuk ${selectedItems.length} item terpilih secara berurutan?`)) {
                return;
            }

            // Tampilkan indikator status antrean di layar
            const statusEl = document.getElementById('queue_status');
            statusEl.style.display = 'inline';
            
            // Mulai jalankan antrean dari indeks ke-0
            prosesAntreanBarang(selectedItems, 0, tipe);
        }

        /**
         * Fungsi Recursive Queue (Memproses barang secara bergantian dengan jeda)
         */
        function prosesAntreanBarang(arrItemNo, index, tipe) {
            const statusEl = document.getElementById('queue_status');
            
            // Kondisi Berhenti: Jika semua barang dalam array sudah selesai diproses
            if (index >= arrItemNo.length) {
                statusEl.innerHTML = `✨ Sukses! Selesai memproses ${arrItemNo.length} barang.`;
                statusEl.style.color = '#4CAF50';
                
                alert(`Semua proses multi update ${tipe} telah selesai dilakukan!`);
                // Ambil parameter tanggal dari URL untuk refresh halaman ke hari ini [cite: 6, 7]
                const urlParams = new URLSearchParams(window.location.search);
                const startDate = urlParams.get('start_date') || '<?php echo date('Y-m-d'); ?>';
                const endDate   = urlParams.get('end_date')   || '<?php echo date('Y-m-d'); ?>';
                window.location.href = `item.php?barcode=&start_date=${startDate}&end_date=${endDate}`;
                return;
            }

            const currentItemNo = arrItemNo[index];
            const nomorUrut = index + 1;
            
            // Update teks status di browser agar admin tahu progresnya
            statusEl.innerHTML = `⏳ Memproses (${nomorUrut}/${arrItemNo.length}): Item #${currentItemNo}...`;
            statusEl.style.color = '#0066cc';

            // 1. Ambil data harga & stok terbaru dari Accurate Cloud terlebih dahulu via api/item/stokharga.php
            // Kategori harga dikunci ke 'Umum' sesuai standar default halaman list barang [cite: 47]
            const apiFetchUrl = `../../api/item/stokharga.php?no=${encodeURIComponent(currentItemNo)}&priceCategoryName=Umum`;

            fetch(apiFetchUrl)
                .then(res => res.json())
                .then(resData => {
                    if (resData.status !== 'success' || !resData.data) {
                        throw new Error(resData.message || 'Gagal mengambil data dari Accurate API');
                    }
                    
                    // Siapkan cetakan FormData untuk dikirim ke lokal database
                    const formData = new FormData();
                    formData.append('no', currentItemNo);

                    let targetDbUrl = '';

                    // 2. Tentukan arah file eksekusi query lokal berdasarkan tipe tombol yang diklik
                    if (tipe === 'harga') {
                        targetDbUrl = '../../classes/update_price_default_lokal.php';
                        formData.append('harga', resData.data.unitPrice);
                    } else {
                        targetDbUrl = '../../classes/update_stock_available_lokal.php';
                        formData.append('stock', resData.data.availableStock);
                    }

                    // 3. Tembak ke database lokal untuk melakukan UPDATE table item
                    return fetch(targetDbUrl, { method: 'POST', body: formData });
                })
                .then(dbRes => dbRes.json())
                .then(dbData => {
                    console.log(`Item #${currentItemNo} Result:`, dbData);
                    
                    // 4. MEKANISME JEDA AMAN (1.5 Detik): Panggil barang berikutnya setelah jeda waktu selesai
                    setTimeout(() => {
                        prosesAntreanBarang(arrItemNo, index + 1, tipe);
                    }, 1500); 
                })
                .catch(err => {
                    console.error(`Gagal pada Item #${currentItemNo}:`, err);
                    // Jika ada 1 barang error, antrean tidak macet melainkan tetap lanjut ke barang berikutnya
                    setTimeout(() => {
                        prosesAntreanBarang(arrItemNo, index + 1, tipe);
                    }, 1500);
                });
        }



    </script>
</body>
</html>