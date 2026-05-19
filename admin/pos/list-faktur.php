<?php
/**
 * HALAMAN ADMIN - VIEW INVOICE LIST (REVISI - WITH TOTAL SUMMARY)
 * File: admin/pos/list-faktur.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil Session ID sebelum menutup session untuk mencegah deadlock cURL
$sessionCookie = isset($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : '';
session_write_close(); 

// 1. Ambil Parameter Filter & Paging dari URL Browser
$page       = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit      = 100; 

$startDate  = (isset($_GET['start_date']) && $_GET['start_date'] !== '') ? trim($_GET['start_date']) : date('Y-m-d');
$endDate    = (isset($_GET['end_date']) && $_GET['end_date'] !== '') ? trim($_GET['end_date']) : date('Y-m-d');
$search     = isset($_GET['search']) ? trim($_GET['search']) : '';

// 2. Bangun URL Query untuk Menembak API perantara
$apiBaseUrl = "http://" . $_SERVER['HTTP_HOST'] . "/pos-accurate/api/penjualan/list-invoice.php";
$queryParams = http_build_query([
    'page'       => $page,
    'limit'      => $limit,
    'start_date' => $startDate,
    'end_date'   => $endDate,
    'search'     => $search
]);
$apiUrlWithParams = $apiBaseUrl . "?" . $queryParams;

// 3. Fetch Data Menggunakan cURL lokal
$invoices = [];
$errorMessage = '';
$totalItems = 0;
$totalPage = 1;

// Tambahan variabel untuk menampung total jumlah uang hasil saringan halaman aktif
$pageTotalAmount = 0; 

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrlWithParams);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

if ($sessionCookie !== '') {
    curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . $sessionCookie);
}

$response = curl_exec($ch);

if (curl_errno($ch)) {
    $errorMessage = 'Gagal terhubung ke API Faktur: ' . curl_error($ch);
} else {
    $decodes = json_decode($response, true);
    if (isset($decodes['status']) && $decodes['status'] === 'success') {
        $invoices = $decodes['data'] ?? [];
        
        // Paging meta-data subset
        $totalItems = $decodes['pagination']['total_items'] ?? count($invoices);
        $totalPage  = $decodes['pagination']['total_page'] ?? max(1, ceil($totalItems / $limit));

        // Hitung total dari baris data yang berhasil di-load pada halaman ini
        foreach ($invoices as $inv) {
            $pageTotalAmount += (float)($inv['totalAmount'] ?? 0);
        }
    } else {
        $errorMessage = $decodes['message'] ?? 'Gagal memuat data faktur dari server API.';
    }
}
curl_close($ch);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rowNumber = ($page - 1) * $limit + 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin POS - Daftar Faktur Penjualan</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; color: #333; background-color: #f8f9fa; }
        h1 { margin-bottom: 20px; font-size: 24px; color: #222; }
        
        /* Filter Panel & Form Styling */
        .filter-panel { background: #fff; padding: 15px; border: 1px solid #dee2e6; border-radius: 4px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        .filter-form { display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group label { font-size: 13px; font-weight: bold; color: #555; }
        
        .filter-form input[type="text"], .filter-form input[type="date"] { padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
        .filter-form input[type="text"] { width: 280px; }
        .filter-form input[type="date"] { width: 150px; }
        
        .btn { padding: 9px 16px; font-weight: bold; border: none; cursor: pointer; border-radius: 4px; text-decoration: none; font-size: 14px; display: inline-block; }
        .btn-success { background-color: #28a745; color: white; }
        .btn-success:hover { background-color: #218838; }
        .btn-primary { background-color: #0066cc; color: white; }
        .btn-primary:hover { background-color: #0052a3; }
        .btn-secondary { background-color: #6c757d; color: white; text-align: center; }
        .btn-secondary:hover { background-color: #5a6268; }
        
        /* Table Styling */
        .table-container { background: #fff; border: 1px solid #dee2e6; border-radius: 4px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        th, td { padding: 12px 15px; border-bottom: 1px solid #dee2e6; }
        th { background-color: #f1f3f5; font-weight: bold; color: #495057; }
        tr:hover { background-color: #f8f9fa; }
        
        /* Footer Table Highlight */
        tfoot tr { background-color: #eaedf0; font-weight: bold; color: #212529; }
        tfoot td { border-top: 2px solid #ced4da; padding: 14px 15px; }

        .badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        
        /* Pagination Styling */
        .pagination-container { margin-top: 20px; text-align: center; display: flex; justify-content: center; align-items: center; gap: 10px; }
        .pagination-container button { padding: 6px 12px; border: 1px solid #ccc; background: white; cursor: pointer; border-radius: 4px; }
        .pagination-container button:disabled { background: #e9ecef; cursor: not-allowed; color: #6c757d; }
        .pagination-container button:hover:not(:disabled) { background: #f1f3f5; }
        
        .error-msg { background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #f5c6cb; font-weight: bold; }
        .multi-action-bar { background-color: #e2e3e5; padding: 10px 15px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; display: none; align-items: center; }
    </style>
</head>
<body>

    <h1>Daftar Faktur Penjualan (Sales Invoice)</h1>

    <?php if ($errorMessage): ?>
        <div class="error-msg">Error: <?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <div class="filter-panel">
        <form method="GET" action="" class="filter-form">
            <div class="form-group">
                <button type="button" class="btn btn-success" onclick="alert('Membuka modul formulir pembuatan faktur baru...')">Buat Faktur</button>
            </div>

            <div class="form-group">
                <label for="start_date">Dari Tanggal:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
            </div>

            <div class="form-group">
                <label for="end_date">Sampai Tanggal:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
            </div>

            <div class="form-group">
                <label for="search_input">No. Faktur / No. Konsumen:</label>
                <input type="text" id="search_input" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Contoh: SI.2026.05.00003 atau MB002">
            </div>

            <div class="form-group" style="flex-direction: row; gap: 5px;">
                <button type="submit" class="btn btn-primary">Saring Data</button>
                <a href="list-faktur.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div id="multi_action_bar" class="multi-action-bar">
        <strong>Item Terpilih: </strong>&nbsp;<span id="selected_count">0</span> faktur tercentang. &nbsp;&nbsp;
        <button type="button" onclick="prosesAksiMassalFaktur()">Cek ID Massal</button>
    </div>

    <div class="table-container">
        <table id="faktur_table">
            <thead>
                <tr>
                    <th width="30"><input type="checkbox" id="check_all_faktur" onclick="toggleSelectAllFaktur(this)"></th>
                    <th width="40" style="text-align: center;">No</th>
                    <th>No. Faktur</th>
                    <th>Tanggal</th>
                    <th>Konsumen</th>
                    <th>Status</th>
                    <th style="text-align: right;">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="7" align="center" style="color: #888; padding: 30px;">Tidak ada data faktur yang ditemukan untuk kriteria filter ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($invoices as $invoice): 
                        $custNo = $invoice['customer']['customerNo'] ?? '';
                        $custName = $invoice['customer']['name'] ?? '';
                        $consumerDisplay = trim($custNo . ' - ' . $custName, ' -');
                        $statusClass = (trim($invoice['statusName']) === 'Lunas') ? 'badge-success' : 'badge-danger';
                    ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="faktur-checkbox" value="<?php echo htmlspecialchars($invoice['id'] ?? ''); ?>" onclick="hitungDinamisCheckbox()">
                            </td>
                            <td align="center"><?php echo $rowNumber++; ?></td>
                            <td style="font-weight: bold; color: #0056b3;"><?php echo htmlspecialchars($invoice['number'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($invoice['transDate'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($consumerDisplay ?: '-'); ?></td>
                            <td>
                                <span class="badge <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($invoice['statusName'] ?? 'Draft'); ?>
                                </span>
                            </td>
                            <td align="right" style="font-weight: 500;">
                                Rp <?php echo number_format($invoice['totalAmount'] ?? 0, 0, ',', '.'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>

            <?php if (!empty($invoices)): ?>
            <tfoot>
                <tr>
                    <td colspan="6" align="right">GRAND TOTAL:</td>
                    <td align="right" style="color: #0056b3; font-size: 15px;">
                        Rp <?php echo number_format($pageTotalAmount, 0, ',', '.'); ?>
                    </td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>

    <div class="pagination-container">
        <p style="margin-right: 15px; font-size: 14px; color: #666;">Total Data Terfilter: <strong><?php echo $totalItems; ?></strong> baris</p>
        
        <?php if ($page > 1): 
            $prevParams = $_GET;
            $prevParams['page'] = $page - 1;
        ?>
            <a href="?<?php echo http_build_query($prevParams); ?>"><button>&laquo; Sebelumnya</button></a>
        <?php else: ?>
            <button disabled>&laquo; Sebelumnya</button>
        <?php endif; ?>

        <span style="font-size: 14px;">Halaman <strong><?php echo $page; ?></strong> dari <strong><?php echo $totalPage; ?></strong></span>

        <?php if ($page < $totalPage): 
            $nextParams = $_GET;
            $nextParams['page'] = $page + 1;
        ?>
            <a href="?<?php echo http_build_query($nextParams); ?>"><button>Selanjutnya &raquo;</button></a>
        <?php else: ?>
            <button disabled>Selanjutnya &raquo;</button>
        <?php endif; ?>
    </div>

    <script>
        function toggleSelectAllFaktur(master) {
            const checkboxes = document.getElementsByClassName('faktur-checkbox');
            for (let i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = master.checked;
            }
            hitungDinamisCheckbox();
        }

        function hitungDinamisCheckbox() {
            const checkboxes = document.getElementsByClassName('faktur-checkbox');
            const actionBar = document.getElementById('multi_action_bar');
            const countLabel = document.getElementById('selected_count');
            
            let checkedCount = 0;
            for (let i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i].checked) checkedCount++;
            }

            if (checkedCount > 0) {
                actionBar.style.display = 'flex';
                countLabel.innerText = checkedCount;
            } else {
                actionBar.style.display = 'none';
                document.getElementById('check_all_faktur').checked = false;
            }
        }

        function getSelectedFakturIds() {
            const checkboxes = document.getElementsByClassName('faktur-checkbox');
            const selectedIds = [];
            for (let i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i].checked) {
                    selectedIds.push(checkboxes[i].value);
                }
            }
            return selectedIds;
        }

        function prosesAksiMassalFaktur() {
            const ids = getSelectedFakturIds();
            alert('Sukses mengumpulkan ' + ids.length + ' ID Faktur:\n\n' + ids.join(', '));
        }
    </script>
</body>
</html>