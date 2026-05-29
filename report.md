# Ringkasan Sistem POS Akurasi

## Deskripsi Umum

Proyek ini adalah sistem Point of Sale (POS) yang terintegrasi dengan Accurate Accounting API untuk manajemen inventaris, penjualan, pelanggan, dan keuangan. Sistem dirancang untuk digunakan oleh restoran atau retail yang memerlukan sinkronisasi data dengan sistem akuntansi Accurate.

## Struktur Utama Proyek

- **api/** - Berisi endpoint API untuk berbagai modul (item, pelanggan, karyawan, gudang, coa, cabang, penjualan, dll.)
- **admin/** - Antarmuka administrasi untuk manajemen item, POS, dan impor data dari Accurate
- **classes/** - Kelas PHP inti termasuk wrapper AccurateAPI dan utility untuk operasi spesifik
- **config/** - File konfigurasi database dan pengambilan kredensial Accurate
- **utils/** - Fungsi utilitas termasuk otentikasi API dan fungsi helper
- **log/** dan **logs/** - Direktori untuk menyimpan catatan error

## Komponen Kunci

### 1. Konfigurasi dan Koneksi

- **config/koneksi.php** - Koneksi database MySQL
- **config/config.php** - Mengambil kredensial Accurate (app_key, signature_secret, api_token) dari tabel `configs` dengan status aktif
- **bootstrap.php** - Menginisialisasi zona waktu (Asia/Makassar) dan memuat konfigurasi, utilitas, serta kelas AccurateAPI

### 2. Autentikasi dan Keamanan

- **utils/api_auth.php** - Mengandung fungsi untuk generating signature dan timestamp yang diperlukan oleh Accurate API
- Semua endpoint API memerlukan header otentikasi yang dihandle oleh kelas `AccurateAPI`

### 3. Kelas AccurateAPI (classes/AccurateAPI.php)

- Wrapper untuk berkomunikasi dengan Accurate API
- Methods utama:
  - `getItemList($limit, $page, $filters)` - Mendapatkan daftar barang dengan filter tanggal
  - `getItemDetail($id)` - Mendapatkan detail spesifik barang
  - Method serupa untuk modul lain (pelanggan, karyawan, coa, cabang, penjualan, dll.)
  - `doRequest($method, $endpoint, $data, $queryParams)` - Method inti untuk melakukan request ke Accurate API

### 4. Endpoint API Utama

Setiap modul di folder `api/` memiliki struktur serupa:

- **list.php** - Mendapatkan daftar data (dengan paginasi dan filter)
- **detail.php** - Mendapatkan detail berdasarkan ID
- **save.php** atau fungsi serupa - Untuk menyimpan/memperbarui data

Contoh:

- `api/item/list.php` - Mendapatkan daftar barang dengan filter tanggal (optimized untuk mass-import dengan limit maks 250)
- `api/item/stokharga.php` - Mendapatkan harga berdasarkan kategori pelanggan (seperti yang disebutkan dalam task.md)
- `api/pelanggan/list.php` - Mendapatkan daftar pelanggan
- `api/penjualan/save-invoice.php` - Menyimpan invoice penjualan
- `api/penjualan/save-receipt.php` - Menyimpan pembayaran

### 5. Admin Interface

- **admin/item/item.php** - Manajemen barang
- **admin/item/import-accurate.php** - Impor barang dari Accurate
- **admin/pos/** - Modul Point of Sale
- **admin/new.php** - Halaman utama administrasi

### 6. Utility dan Kelas Pendukung

- **classes/simpan_user.php** - Untuk operasi penyimpanan user
- **classes/update_price_default_lokal.php** - Update harga lokal
- **classes/update_stock_available_lokal.php** - Update stok yang tersedia lokal
- **utils/utils.php** - Fungsi utilitas umum
- **utils/api_auth.php** - Spesifik untuk otentikasi Accurate

## Alur Data

1. Konfigurasi Accurate diambil dari database saat bootstrap
2. Saat ada request ke API (misal: `api/item/list.php`):
   - Memuat bootstrap untuk konfigurasi dan autentikasi
   - Membuat instance AccurateAPI
   - Mengirim request ke Accurate API dengan header yang ditandatangani
   - Memproses respons dan mengembalikan data dalam format JSON
3. Untuk operasi write (simpan/update), serupa tetapi menggunakan method POST/PUT

## Tugas yang Dapat Dilakukan (Dari task.md)

1. **Cari API memuat harga berdasarkan tipe pelanggan**
   - Referensi: `https://resto.samdev.org/pos-accurate/api/item/stokharga.php?no=100008&priceCategoryName=Membership`
   - Perlu memastikan endpoint ini sudah berfungsi dengan baik untuk berbagai kategori pelanggan
2. **Tambahkan API untuk mengambil pengiriman atau manual isi pengiriman**
   - Opsi pengiriman: "Ambil Sendiri", "Kurir Toko"
   - Mungkin perlu membuat endpoint baru atau memperluas existing sales API

3. **Nomor Seri**
   - Perlu mencari atau membuat fitur untuk handling nomor seri produk

## Teknologi yang Digunakan

- PHP (native, tidak menggunakan framework)
- MySQL/MariaDB untuk penyimpanan konfigurasi
- Accurate Accounting API untuk backend akuntansi dan sinkronisasi data
- JSON untuk format pertukaran data API
- Bootstrap/jQuery untuk antarmuka admin (lihat admin/samplepos.html)

## Catatan Penting

- Zona waktu disetel ke Asia/Makassar untuk konsistensi dengan Accurate API
- Semua endpoint API mengizinkan CORS (`Access-Control-Allow-Origin: *`) untuk kemungkinan penggunaan dari frontend berbeda
- Error logging dilakukan melalui fungsi `logError()` jika tersedia
- Paginasi default 20 item per halaman, dapat diubah melalui parameter `limit` (maks 250 untuk item list)

## Rekomendasi untuk Tim

1. Selalu periksa kredensial Accurate di tabel `configs` pastikan ada yang aktif
2. Monitor log error di `log/error.log` dan `logs/error.log` untuk troubleshooting
3. Saat menambah endpoint baru, ikuti pola autentikasi dan respons yang konsisten dengan API yang ada
4. Untuk perubahan struktur database pada tabel `configs`, pastikan kode konfigurasi masih kompatibel
5. Uji endpoint harga pelanggan (stokharga.php) dengan berbagai kategori pelanggan untuk memastikan akurasi

## Catatan Singkat

- Pastikan semua endpoint API sudah diuji dengan berbagai scenario sebelum produksi
- Selalu sinkronkan
