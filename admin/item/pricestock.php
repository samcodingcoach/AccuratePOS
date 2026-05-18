<?php
/**
 * HALAMAN ADMIN - DETAIL PRICE & STOCK INTEGRATION (AJAX Driven - Fixed Text Wrap & Multi Button)
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
        .form-group { margin-bottom: 15px; display: flex; align-items: flex-start; }
        .form-group label { display: inline-block; width: 150px; font-weight: bold; margin-top: 6px; }
        .form-group input { padding: 6px; width: 300px; border: 1px solid #ccc; background-color: #f5f5f5; box-sizing: border-box; }
        
        /* Style khusus Textarea agar mendukung wrap text nama barang yang panjang */
        .form-group textarea { 
            padding: 6px; 
            width: 300px; 
            height: 65px; 
            border: 1px solid #ccc; 
            background-color: #f5f5f5; 
            font-family: Arial, sans-serif;
            resize: vertical; 
            box-sizing: border-box;
        }
        
        .form-group input:focus, .form-group textarea:focus { outline: none; }
        select { padding: 6px; width: 300px; box-sizing: border-box; }
        
        /* Style Tombol Aksi */
        .btn-container { margin-top: 20px; text-align: right; width: 450px; }
        .btn { padding: 8px 15px; border: none; cursor: pointer; font-weight: bold; margin-left: 5px; }
        .btn-success { background-color: #4CAF50; color: white; }
        .btn-success:hover { background-color: #45a049; }
        .btn-primary { background-color: #0066cc; color: white; }
        .btn-primary:hover { background-color: #0052a3; }
        
        .back-link { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #0066cc; }
        #loading_status { color: #888; font-style: italic; margin-left: 10px; display: none; align-self: center; }
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
            <textarea id="name" readonly placeholder="Memuat nama barang..."></textarea>
        </div>

        <div class="form-group">
            <label for="unitPrice">Harga (IDR):</label>
            <input type="text" id="unitPrice" readonly placeholder="0">
        </div>

        <div class="form-group">
            <label for="availableStock">Stok Saat Ini:</label>
            <input type="text" id="availableStock" readonly placeholder="0">
        </div>

        <div class="btn-container">
            <button type="button" class="btn btn-primary" onclick="updatePriceAction()">Update Harga</button>
            <button type="button" class="btn btn-success" onclick="updateStockAction()">Update Stock</button>
        </div>
    </fieldset>

    <script>
        const itemNo = "<?php echo htmlspecialchars($itemNo, ENT_QUOTES, 'UTF-8'); ?>";

        function loadPriceAndStock() {
            const categoryElement = document.getElementById('priceCategoryName');
            const selectedCategory = categoryElement.value;
            const loadingText = document.getElementById('loading_status');

            loadingText.innerText = "Memuat...";
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

        // =========================================================================
        // PERUBAHAN UTAMA: Fungsi Aksi Kirim POST AJAX ke update_price_default_lokal.php
        // =========================================================================
        // Fungsi Aksi Tombol: Update Harga ke classes/update_price_default_lokal.php + Paksa Kembali ke Hari Ini
        function updatePriceAction() {
            const rawPrice = document.getElementById('unitPrice').value;
            
            if (!rawPrice || rawPrice === "0" || rawPrice === "Memuat...") {
                alert("Nilai harga belum siap atau tidak valid untuk diupdate.");
                return;
            }

            if (!confirm(`Apakah Anda yakin ingin memperbarui harga item #${itemNo} di database lokal menjadi Rp ${rawPrice}?`)) {
                return;
            }

            // Path relatif menuju target pemroses backend
            const targetActionUrl = '../../classes/update_price_default_lokal.php';

            // Bungkus data ke dalam FormData
            const formData = new FormData();
            formData.append('no', itemNo);
            formData.append('harga', rawPrice);

            // Ubah teks indikator loading menjadi "Menyimpan..."
            const loadingText = document.getElementById('loading_status');
            loadingText.innerText = "Menyimpan...";
            loadingText.style.display = 'inline';

            fetch(targetActionUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Server mengembalikan error internal (HTTP 500/404).');
                }
                return response.json();
            })
            .then(res => {
                loadingText.style.display = 'none';

                if (res.status === 'success') {
                    alert('Sukses! ' + res.message);
                    
                    // =========================================================================
                    // LOCK REDIRECT: PAKSA SELALU KEMBALI KE TANGGAL HARI INI (18 Mei 2026)
                    // =========================================================================
                    const todayStr = '<?php echo date('Y-m-d'); ?>';
                    
                    // Alihkan halaman kembali ke daftar item dengan mengunci parameter tanggal hari ini
                    window.location.href = `item.php?barcode=&start_date=${todayStr}&end_date=${todayStr}`;
                    
                } else {
                    alert('Gagal memperbarui database lokal: ' + res.message);
                }
            })
            .catch(error => {
                loadingText.style.display = 'none';
                console.error('Error updating price:', error);
                alert('Terjadi kesalahan jaringan atau script error saat memproses pembaruan harga.');
            });
        }

        // Fungsi Aksi Tombol: Update Stock (Masih Placeholder)
        function updateStockAction() {
            const currentStock = document.getElementById('availableStock').value;
            const currentCategory = document.getElementById('priceCategoryName').value;
            alert(`Aksi Sinkronisasi Stok: Memulai update data stok barang #${itemNo} untuk kategori [${currentCategory}]. Stok saat ini di Accurate: ${currentStock}`);
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