<?php
/**
 * HALAMAN ADMIN - DETAIL PRICE & STOCK INTEGRATION (AJAX Driven - Fixed Error 500)
 * File: admin/item/pricestock.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_write_close();

// Tangkap nomor barang dari URL browser
$itemNo = isset($_GET['no']) ? trim($_GET['no']) : '';
$defaultCategory = isset($_GET['priceCategoryName']) ? trim($_GET['priceCategoryName']) : 'Umum';

if (empty($itemNo)) {
    die("<h3>Error: Nomor barang (no) tidak ditemukan pada URL.</h3><a href='item.php'>Kembali ke Daftar Barang</a>");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin - Update Harga & Stok</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: inline-block; width: 150px; font-weight: bold; }
        .form-group input { padding: 6px; width: 300px; border: 1px solid #ccc; background-color: #f5f5f5; }
        .form-group input:focus { outline: none; }
        select { padding: 6px; width: 314px; }
        button { padding: 8px 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; font-weight: bold; }
        button:hover { background-color: #45a049; }
        .back-link { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #0066cc; }
        #loading_status { color: #888; font-style: italic; margin-left: 10px; display: none; }
    </style>
</head>
<body>

    <a href="item.php" class="back-link">&laquo; Kembali ke Daftar Barang</a>

    <h2>Detail & Penyesuaian Barang (#<?php echo htmlspecialchars($itemNo); ?>)</h2>

    <fieldset style="padding: 20px; border: 1px solid #ddd; max-width: 500px;">
        <legend>Informasi Stok & Kategori Harga</legend>

        <div class="form-group">
            <label for="priceCategoryName">Kategori Harga:</label>
            <select id="priceCategoryName" onchange="loadPriceAndStock()">
                <option value="Umum" <?php echo $defaultCategory === 'Umum' ? 'selected' : ''; ?>>Umum</option>
                <option value="Membership" <?php echo $defaultCategory === 'Membership' ? 'selected' : ''; ?>>Membership</option>
                <option value="Shopee" <?php echo $defaultCategory === 'Shopee' ? 'selected' : ''; ?>>Shopee</option>
                <option value="Free" <?php echo $defaultCategory === 'Free' ? 'selected' : ''; ?>>Free</option>
            </select>
            <span id="loading_status">Memuat...</span>
        </div>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

        <div class="form-group">
            <label for="name">Nama Barang:</label>
            <input type="text" id="name" readonly placeholder="Memuat nama barang...">
        </div>

        <div class="form-group">
            <label for="unitPrice">Harga (IDR):</label>
            <input type="text" id="unitPrice" readonly placeholder="0">
        </div>

        <div class="form-group">
            <label for="availableStock">Stok Saat Ini:</label>
            <input type="text" id="availableStock" readonly placeholder="0">
        </div>

        <div style="margin-top: 20px; text-align: right;">
            <button type="button" onclick="updateStockAction()">Update Stock</button>
        </div>
    </fieldset>

    <script>
        // PERBAIKAN: Mengganti idn_to_ascii dengan htmlspecialchars agar aman di server hosting manapun
        const itemNo = "<?php echo htmlspecialchars($itemNo, ENT_QUOTES, 'UTF-8'); ?>";

        function loadPriceAndStock() {
            const categoryElement = document.getElementById('priceCategoryName');
            const selectedCategory = categoryElement.value;
            const loadingText = document.getElementById('loading_status');

            loadingText.style.display = 'inline';

            const apiUrl = `../../api/item/stokharga.php?no=${encodeURIComponent(itemNo)}&priceCategoryName=${encodeURIComponent(selectedCategory)}`;

            fetch(apiUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Gagal merespons server lokal.');
                    }
                    return response.json();
                })
                .then(res => {
                    loadingText.style.display = 'none';
                    if (res.status === 'success' && res.data) {
                        document.getElementById('name').value = res.data.name || '-';
                        document.getElementById('unitPrice').value = formatRupiah(res.data.unitPrice);
                        document.getElementById('availableStock').value = res.data.availableStock ?? 0;
                    } else {
                        alert('Gagal memuat spesifikasi data barang: ' + res.message);
                    }
                })
                .catch(error => {
                    loadingText.style.display = 'none';
                    console.error('Error fetching data:', error);
                    alert('Terjadi kendala jaringan saat memanggil stokharga.php');
                });
        }

        function updateStockAction() {
            const currentStock = document.getElementById('availableStock').value;
            const currentCategory = document.getElementById('priceCategoryName').value;
            alert(`Aksi Sinkronisasi: Memulai update data stok barang #${itemNo} untuk kategori [${currentCategory}]. Stok saat ini di Accurate: ${currentStock}`);
        }

        function formatRupiah(number) {
            return new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(number);
        }

        window.onload = function() {
            loadPriceAndStock();
        };
    </script>
</body>
</html>