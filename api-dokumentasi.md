# Dokumentasi API POS - Accurate

Dokumen ini berisi panduan penggunaan seluruh endpoint API yang tersedia di dalam folder `api`. Dokumentasi ini disusun secara bertahap berdasarkan fungsi dan fitur operasional POS.

---

## FASE 1: Sistem, Profil & Kasir

### 1. API Profil Perusahaan
Mengambil detail informasi perusahaan dan alamat utama dari menu Preferensi Accurate Online.

- **URL:** `/api/profile/company.php`
- **Method:** `GET`
- **Parameter:** (Tidak Ada)
- **Response Sukses (200 OK):**
  ```json
  {
      "status": "success",
      "data": {
          "name": "PT. MUSA COM",
          "address": "Jl. Bung Tomo Perum Keledang Mas",
          "city": "Kota Samarinda",
          "phone": "089825812345"
          // ... field lainnya dari accurate
      }
  }
  ```

### 2. API Daftar Database (List)
Mengambil daftar seluruh database Accurate Online yang dapat diakses oleh API Token saat ini.

- **URL:** `/api/profile/list.php`
- **Method:** `GET`
- **Parameter:** (Tidak Ada)
- **Response Sukses (200 OK):**
  ```json
  {
      "status": "success",
      "data": [
          {
              "id": 2716251,
              "alias": "POS TRIAL #2",
              "trial": true
          }
      ]
  }
  ```

### 3. API Detail Database
Mengambil detail informasi spesifik dari sebuah database Accurate Online.

- **URL:** `/api/profile/detail.php`
- **Method:** `GET`
- **Parameter Query:**
  - `id` (Wajib - Integer): ID Database Accurate (misal: `2716251`).
- **Response Sukses (200 OK):**
  ```json
  {
      "status": "success",
      "data": {
          // ... Detail database spesifik dari accurate
      }
  }
  ```

### 4. API Kasir (Lokal)
Mengambil daftar akun kasir / user yang ada di database lokal POS. Data hanya menampilkan kasir yang berstatus aktif (`aktif = 1`) dengan urutan data terbaru di atas.

- **URL:** `/api/kasir/list-lokal.php`
- **Method:** `GET`
- **Parameter Query (Opsional - Pagination):**
  - `page` (Integer): Halaman ke berapa (Default: 1).
  - `limit` (Integer): Jumlah maksimal data per halaman (Default: 100, Maks: 500).
- **Response Sukses (200 OK):**
  ```json
  {
      "status": "success",
      "message": "Data kasir berhasil dimuat",
      "data": [
          {
              "id_users": 1,
              "username": "admin",
              "email": "admin@pos.com",
              "created_date": "2026-06-22 10:00:00",
              "nama_lengkap": "Administrator",
              "aktif": 1
          }
      ],
      "pagination": {
          "current_page": 1,
          "total_page": 1,
          "has_more": false,
          "total_items": 1
      },
      "meta": {
          "timestamp": "2026-06-22T10:00:00+07:00",
          "api_version": "1.0-local"
      }
  }
  ```

### 5. API Merk / Brand (Placeholder)
Mengembalikan balasan kosong karena Accurate Online secara bawaan tidak menyediakan endpoint khusus untuk Merk. Disediakan sebagai kerangka jika ke depannya POS menggunakan custom field / kategori barang sebagai merk.

- **URL:** `/api/brand/list.php`
- **Method:** `GET`
- **Parameter:** (Tidak Ada)
- **Response Sukses (200 OK):**
  ```json
  {
      "status": "success",
      "message": "Accurate Online tidak menyediakan endpoint khusus merk/brand secara native.",
      "data": []
  }
  ```

### 6. API Cabang (Branch)
Mengambil daftar seluruh cabang atau detail cabang spesifik jika ID disertakan.

- **URL:** `/api/cabang/list.php`
- **Method:** `GET`
- **Parameter Query (Opsional):**
  - `id` (Integer): Jika disertakan, API akan mengembalikan *Detail* cabang tersebut. Jika tidak, akan mengembalikan *List* (daftar cabang).
- **Response Sukses (200 OK) - Mode List (Tanpa ID):**
  ```json
  {
      "status": "success",
      "message": "Daftar branch berhasil dimuat",
      "data": [
          {
              "id": 50,
              "name": "Kantor Pusat",
              "address": "Jl. Utama",
              "is_default": true,
              "suspended": false
          }
      ],
      "meta": { ... }
  }
  ```
- **Response Sukses (200 OK) - Mode Detail (Dengan ID):**
  ```json
  {
      "status": "success",
      "message": "Detail branch berhasil dimuat",
      "data": {
          "id": 50,
          "name": "Kantor Pusat",
          "address": "Jl. Utama",
          "city": "Samarinda",
          "province": "Kaltim",
          "is_default": true,
          "suspended": false,
          "phone": "0812345678"
      },
      "meta": { ... }
  }
  ```

---

## FASE 2: Master Data Barang & Gudang

### 7. API Gudang (Warehouse)
Mengambil daftar gudang beserta detail lengkapnya (termasuk alamat dan nama lokasi). Endpoint ini melakukan *eager loading* ke detail gudang secara otomatis.

- **URL:** `/api/gudang/list.php`
- **Method:** `GET`
- **Parameter Query (Opsional - Pagination):**
  - `page` (Integer): Halaman ke berapa (Default: 1).
  - `limit` (Integer): Jumlah maksimal data per halaman (Default: 25, Maks: 100).
- **Response Sukses (200 OK):**
  ```json
  {
      "status": "success",
      "message": "Data warehouse berhasil dimuat dengan detail lengkap",
      "data": [
          {
              "id": 100,
              "name": "Gudang Utama",
              "description": "",
              "is_suspended": false,
              "is_default": true,
              "is_scrap": false,
              "location_id": null,
              "full_address": "Jl. Kesejahteraan",
              "pic": "Budi"
          }
      ],
      "pagination": { ... },
      "meta": { ... }
  }
  ```

### 8. API Kategori Barang (Item Category)
Mengambil daftar kategori barang, dilengkapi perhitungan Level Indentasi (`lvl`) dan informasi Parent Category.

- **URL:** `/api/item-category/list.php`
- **Method:** `GET`
- **Parameter Query (Opsional):**
  - `page` (Integer): Halaman ke berapa (Default: 1).
  - `limit` (Integer): Jumlah data (Default: 25, Maks: 100).
  - `search` (String): Filter pencarian nama kategori.
- **Response Sukses (200 OK):**
  ```json
  {
      "status": "success",
      "data": [
          {
              "id": 1,
              "name": "Elektronik",
              "lvl": 1,
              "parent_id": null,
              "parent_name": null,
              "is_sub": false
          }
      ],
      "pagination": { ... }
  }
  ```

### 9. API Detail Kategori Barang
Mengambil detail informasi spesifik untuk satu kategori barang.

- **URL:** `/api/item-category/detail-list.php`
- **Method:** `GET`
- **Parameter Query:**
  - `id` (Wajib - Integer): ID kategori barang.
- **Response Sukses (200 OK):** Mengembalikan object detail kategori dari Accurate.

### 9.1 API Simpan Kategori Barang (Save Item Category)
Menyimpan atau membuat Kategori Barang (Item Category) baru ke Accurate Online.

- **URL:** `/api/item-category/save.php`
- **Method:** `POST`
- **Payload Data (JSON / Form-Data):**
  - `name` (Wajib - String): Nama kategori barang.
  - `defaultCategory` (Opsional - Boolean): Apakah ini kategori default (`true`/`false`).
  - `parentName` (Opsional - String): Nama dari kategori induk (jika ini merupakan sub-kategori). Dapat diisi dengan string kosong `""` jika tidak ingin memiliki kategori induk.
- **Response Sukses (200 OK):** Mengembalikan _object_ detail kategori yang baru saja disimpan.

### 9.2 API Update Kategori Barang (Update Item Category)
Memperbarui Kategori Barang (Item Category) yang sudah ada di Accurate Online.

- **URL:** `/api/item-category/update.php`
- **Method:** `POST`
- **Payload Data (JSON / Form-Data):**
  - `id` (Wajib - String/Integer): ID dari kategori barang yang ingin diperbarui.
  - `name` (Wajib - String): Nama kategori barang (baru atau tetap).
  - `defaultCategory` (Opsional - Boolean): Apakah ini kategori default (`true`/`false`).
  - `parentName` (Opsional - String): Nama dari kategori induk (jika ini merupakan sub-kategori). Dapat diisi dengan string kosong `""` untuk **menghapus** relasi kategori induk yang sudah ada sebelumnya.
- **Response Sukses (200 OK):** Mengembalikan _object_ detail kategori yang baru saja diperbarui.
### 10. API Barang (Item List - Accurate)
Mengambil daftar barang **langsung dari Accurate Online** dengan versi yang lebih ringan (hanya mengekstrak field penting seperti stok `balance`, harga `price`, dan gambar `image`). 

- **URL:** `/api/item/list.php`
- **Method:** `GET`
- **Parameter Query (Opsional):**
  - `page` (Integer): Halaman (Default: 1).
  - `limit` (Integer): Jumlah data (Default: 20, Maks: 250).
  - `start_date` & `end_date` (String - Format `YYYY-MM-DD`): Memfilter barang berdasarkan kapan terakhir diperbarui (lastUpdate).
- **Response Sukses (200 OK):**
  ```json
  {
      "status": "success",
      "data": [
          {
              "id": 100,
              "item_no": "BRG-001",
              "name": "Kopi Susu",
              "barcode": "899123456789",
              "balance": 50,
              "unit": "PCS",
              "price": 15000,
              "image": "/path/to/image.jpg"
          }
      ],
      "pagination": { ... }
  }
  ```

### 11. API Barang (Item List - Database Lokal)
Mengambil daftar barang **dari database lokal (MySQL)** POS, dilengkapi fitur filter dan pencarian yang kaya.

- **URL:** `/api/item/list-lokal.php`
- **Method:** `GET`
- **Parameter Query (Opsional):**
  - `page` (Integer): Default 1.
  - `limit` (Integer): Default 250, Maks 500.
  - `search` (String): Filter pencarian nama barang atau kode barang.
  - `barcode` (String): Filter pencarian spesifik barcode barang.
  - `start_date` & `end_date` (String): Filter berdasarkan rentang waktu sinkronisasi (`last_sync`).
- **Response Sukses (200 OK):** Mirip dengan struktur list Accurate, bersumber dari tabel `item`.

### 12. API Detail Barang
Mengambil seluruh data mentah (Raw Data) informasi barang secara spesifik. Tersedia dalam 3 Endpoint berdasarkan cara mencarinya:

- **Berdasarkan Kode Barcode (UPC):** `/api/item/detail.php`
  - Parameter: `upcno` atau `upc` (Wajib).
- **Berdasarkan Nomor Barang (Item No):** `/api/item/detail_byNo.php`
  - Parameter: `no` (Wajib).
- **Berdasarkan Nomor atau Serial Number (SN):** `/api/item/search_byNoItem.php`
  - Parameter: `no` (Wajib). Endpoint ini bisa mendeteksi nomor barang *maupun* nomor seri (SN).

### 13. API Harga Barang (Raw Price)
Mengambil informasi harga jual barang beserta simulasinya.

- **URL:** `/api/item/price.php`
- **Method:** `GET`
- **Parameter Query (Opsional tapi disarankan):**
  - `no` atau `upcNo` (Salah satu Wajib).
  - `branchName`, `currencyCode`, `discountCategoryName`, `effectiveDate`, `priceCategoryName`.
- **Response Sukses (200 OK):** Mengembalikan data mentah harga jual dari Accurate.

### 14. API Stok Barang 
Mengambil informasi ketersediaan stok barang. Terdapat 3 Endpoint:

- **Raw Stock by Item No:** `/api/item/stock.php`
  - Parameter: `no` (Wajib), `warehouseName` (Opsional).
- **List Stock All Items:** `/api/item/list-stok.php`
  - Parameter: `warehouse` atau `warehouseName` (Opsional), `page`, `limit`.
- **Serial Number by Warehouse:** `/api/item/serial_byNo.php`
  - Parameter: `itemNo` atau `no` (Wajib). Mengembalikan rincian nomor seri barang per gudang.

### 15. API Agregator Stok & Harga (Stokharga)
Endpoint spesial yang menggabungkan (mengagregasi) pemanggilan informasi **Harga Jual** dan ketersediaan **Stok Total** secara bersamaan. Menggunakan jeda waktu (`sleep(1)`) untuk menghindari rate-limit Accurate API.

- **URL:** `/api/item/stokharga.php`
- **Method:** `GET`
- **Parameter Query:**
  - `no` (Wajib): Nomor Barang.
  - `priceCategoryName` (Opsional): Kategori harga (misal: "Umum", "Member").
- **Response Sukses (200 OK):**
  ```json
  {
      "status": "success",
      "message": "Data gabungan harga dan stok berhasil dimuat dengan jeda aman",
      "data": {
          "no": "BRG-001",
          "name": "Kopi Susu",
          "unitPrice": 15000,
          "availableStock": 50
      },
      "meta": {
          "timestamp": "2026-06-22T10:00:00+07:00",
          "price_category_used": "Umum",
          "delay_applied": "1 second"
      }
  }
  ```

---

## FASE 3: Transaksi Penjualan & Penerimaan Penjualan

### 16. API Daftar Faktur Penjualan (Sales Invoice List)
Mengambil daftar riwayat faktur penjualan yang ada di Accurate Online.

- **URL:** `/api/penjualan/list-invoice.php`
- **Method:** `GET`
- **Parameter Query (Opsional):**
  - `limit` (Integer): Jumlah maksimal data per halaman.
- **Response Sukses (200 OK):** Mengembalikan _array_ riwayat faktur penjualan (struktur JSON dari Accurate).

### 17. API Detail Faktur Penjualan
Mengambil informasi lengkap (detail) dari satu faktur penjualan tertentu.

- **URL:** `/api/penjualan/detail-invoice.php`
- **Method:** `GET`
- **Parameter Query:**
  - `id` atau `number` (Salah Satu Wajib): ID unik sistem atau Nomor Faktur.
- **Response Sukses (200 OK):** Mengembalikan _object_ detail faktur dari Accurate.

### 18. API Detail Faktur Penjualan (Ter-Filter & Ringan)
Sama seperti detail biasa, namun menspesifikkan filter tanggal/pelanggan dan merampingkan data yang dibalas (membuang field yang tidak perlu) agar JSON response menjadi sangat ringan.

- **URL:** `/api/penjualan/detail-invoice-filter.php`
- **Method:** `GET`
- **Parameter Query (Opsional):**
  - `customerNo`, `fromDate`, `toDate`, `page`, `pageSize`.
- **Response Sukses (200 OK):** Mengembalikan _array_ yang field-nya hanya berisi: `transDate`, `invoiceTime`, `dueDate`, `paymentTermId`, `number`, `subTotal`, `salesAmountBase`, `status`.

### 19. API Simpan Faktur Penjualan (Create / Update Sales Invoice)
Menyimpan transaksi kasir (faktur penjualan) baru ke Accurate Online. API ini juga melakukan sanitasi otomatis untuk mengubah format tanggal (`YYYY-MM-DD` ke `dd/mm/yyyy`) dan menghilangkan tanda titik pada format ribuan nilai uang.

- **URL:** `/api/penjualan/save-invoice.php`
- **Method:** `POST`
- **Payload Data (JSON / Form-Data):**
  - `transDate` (String - Opsional): Tanggal transaksi. Jika kosong diisi hari ini.
  - `taxable` (Boolean): Apakah dikenakan pajak.
  - `cashDiscount` (Number/String): Diskon tunai global faktur.
  - `detailItem` (Array): Kumpulan barang yang dibeli (mengandung `unitPrice`, `quantity`, `itemCashDiscount`, dan opsi `detailSerialNumber`).
  - `detailExpense` (Array): Biaya tambahan pengiriman/lain-lain (`expenseAmount`).
- **Response Sukses (200 OK):**
  ```json
  {
      "status": "success",
      "message": "Faktur Penjualan berhasil disimpan ke Accurate Online.",
      "id": 12345,
      "number": "INV-26-0001",
      "log": { ... }
  }
  ```

### 20. API Hapus Faktur Penjualan (Delete Invoice)
Menghapus faktur penjualan yang sudah tercatat di Accurate Online berdasarkan nomor fakturnya.

- **URL:** `/api/penjualan/delete-invoice.php`
- **Method:** `POST` atau `DELETE`
- **Parameter/Payload:**
  - `number` (Wajib): Nomor faktur yang ingin dihapus.
- **Response Sukses (200 OK):**
  ```json
  {
      "status": "success",
      "message": "Faktur penjualan berhasil dihapus",
      "data": { "number": "INV-26-0001" }
  }
  ```

### 21. API Daftar Penerimaan Penjualan (Sales Receipt List)
Mengambil daftar pelunasan/penerimaan uang atas faktur penjualan.

- **URL:** `/api/penerimaan-jual/list-receipt.php`
- **Method:** `GET`
- **Parameter Query (Opsional):**
  - `limit`, `page`, `start_date`, `end_date`, `customerNo`, `number`.
- **Response Sukses (200 OK):** Mengembalikan daftar pelunasan dari Accurate.

### 22. API Detail Penerimaan Penjualan
Mengambil informasi detail untuk satu data penerimaan penjualan/pelunasan.

- **URL:** `/api/penjualan/detail-receipt.php`
- **Method:** `GET`
- **Parameter Query:**
  - `id` atau `number` (Salah satu Wajib).
- **Response Sukses (200 OK):** Mengembalikan object data detail pelunasan dari Accurate.

### 23. API Simpan Penerimaan Penjualan (Create Sales Receipt)
Mencatat pelunasan (pembayaran uang) atas faktur yang sudah dibuat. 

- **URL:** `/api/penjualan/save-receipt.php`
- **Method:** `POST`
- **Payload Data (JSON / Form-Data):**
  - `transDate` & `chequeDate` (String - Opsional): Akan disanitasi otomatis.
  - `chequeAmount` (Number/String): Total bayar, akan disanitasi.
  - `paymentMethod` (String): Metode bayar. Otomatis membersihkan pembungkus tanda kurung jika dikirim dengan format `(CASH)`.
  - `detailInvoice` (Array): Daftar faktur yang dilunasi (`paymentAmount`).
- **Response Sukses (200 OK):**
  ```json
  {
      "status": "success",
      "message": "Penerimaan Penjualan (Pelunasan) berhasil disimpan ke Accurate Online.",
      "id": 98765,
      "number": "SR-26-0001",
      "log": { ... }
  }
  ```

---

## FASE 4: Master Tambahan (Pelanggan, Karyawan, & Pengiriman)

### 24. API Daftar Pelanggan (Customer List)
Mengambil daftar pelanggan (Customer) dari Accurate Online.

- **URL:** `/api/pelanggan/list.php`
- **Method:** `GET`
- **Parameter Query (Opsional):**
  - `page` (Integer): Halaman (Default: 1).
  - `limit` (Integer): Jumlah data (Default: 25).
  - `search` (String): Filter pencarian umum.
  - `customerNo` (String): Mencari pelanggan berdasarkan ID/Nomor Pelanggan.
  - `name` (String): Mencari pelanggan berdasarkan Nama.
- **Response Sukses (200 OK):** Mengembalikan _array_ riwayat pelanggan.

### 25. API Daftar Karyawan / Salesman (Employee List)
Mengambil daftar karyawan (termasuk salesman) dari Accurate Online.

- **URL:** `/api/karyawan/list.php`
- **Method:** `GET`
- **Parameter Query (Opsional):**
  - `page` (Integer): Halaman (Default: 1).
  - `limit` atau `pageSize` (Integer): Jumlah data (Default: 100).
  - `search` (String): Filter pencarian umum.
  - `number` (String): Mencari berdasarkan Nomor Karyawan.
  - `id` (String): Mencari berdasarkan ID internal.
  - `name` (String): Mencari berdasarkan nama karyawan.
  - `sales` (Boolean/String): Filter untuk menampilkan hanya karyawan yang berstatus Salesman.
- **Response Sukses (200 OK):** Mengembalikan _array_ daftar karyawan.

### 26. API Detail Karyawan (Employee Detail)
Mengambil informasi lengkap (detail) dari satu profil karyawan.

- **URL:** `/api/karyawan/detail.php`
- **Method:** `GET`
- **Parameter Query:**
  - `id` atau `number` (Salah Satu Wajib): ID unik sistem atau Nomor Karyawan.
- **Response Sukses (200 OK):** Mengembalikan _object_ detail karyawan.

### 26.1 API Simpan Karyawan (Save / Update Employee)
Menyimpan data karyawan baru atau memperbarui data karyawan yang sudah ada di Accurate Online.

- **URL:** `/api/karyawan/save.php`
- **Method:** `POST`
- **Payload Data (JSON / Form-Data):**
  - `name` (Wajib - String): Nama karyawan.
  - `id` (Opsional - Integer): ID karyawan. Diisi jika ingin melakukan **Update** data yang sudah ada.
  - `number` (Opsional - String): Nomor karyawan. Jika dikosongkan, Accurate akan membuatkan secara otomatis.
  - `salutation` (Wajib - String): Sapaan (contoh: `MR`, `MRS`).
  - `transDate` (Wajib - String): Tanggal transaksi/pencatatan (format: `DD/MM/YYYY` atau `YYYY-MM-DD`).
  - `joinDate` (Opsional - String): Tanggal bergabung (format: `DD/MM/YYYY` atau `YYYY-MM-DD`).
  - `bankAccount` (Opsional - String): Nomor rekening bank.
  - `bankCode` (Opsional - String): Kode bank.
  - `bankName` (Opsional - String): Nama bank.
  - `bankAccountName` (Opsional - String): Nama pemilik rekening.
  - `salesman` (Opsional - Boolean): Apakah karyawan ini adalah seorang tenaga penjual (salesman). Default akan dibaca sebagai `false` jika tidak dikirim.
  - `domisiliType` (Opsional - String): Tipe domisili (contoh: `INA`).
  - `email` (Opsional - String): Alamat email karyawan.
  - `mobilePhone` (Opsional - String): Nomor handphone.
- **Response Sukses (200 OK):** Mengembalikan _object_ detail karyawan yang baru saja disimpan atau diperbarui.
### 27. API Daftar Jasa Pengiriman (Shipment List)
Mengambil daftar ekspedisi atau jasa pengiriman yang tersedia di Accurate Online. API ini telah dipangkas sedemikian rupa sehingga hanya mengembalikan **ID** dan **Nama** pengiriman saja.

- **URL:** `/api/pengirim/list.php`
- **Method:** `GET`
- **Parameter Query (Opsional):**
  - `page` (Integer): Halaman (Default: 1).
  - `limit` (Integer): Jumlah data (Default: 25).
- **Response Sukses (200 OK):**
  ```json
  {
      "status": "success",
      "message": "Data pengiriman berhasil dimuat",
      "data": [
          {
              "id": 1,
              "name": "JNE"
          },
          {
              "id": 2,
              "name": "JNT"
          }
      ],
      "pagination": { ... }
  }
  ```

---

## FASE 5: Akuntansi (COA), Promo, Payment Gateway & Harga

### 28. API Daftar Akun Perkiraan (Chart of Account)
Mengambil daftar akun perkiraan (COA) untuk kategori **Pendapatan / Account Receivable** dari Accurate. Berguna untuk memetakan akun saat terjadi transaksi.

- **URL:** `/api/coa/list.php`
- **Method:** `GET`
- **Parameter Query (Opsional):**
  - `page` (Integer), `limit` / `pageSize` (Integer), `search` (String).
- **Response Sukses (200 OK):** Mengembalikan daftar COA kategori Pendapatan/Piutang.

### 29. API Daftar Akun Kas & Bank
Mengambil daftar akun perkiraan khusus kategori **Kas & Bank**. Sangat berguna untuk memilih metode pelunasan pembayaran.

- **URL:** `/api/coa/list-kasbank.php`
- **Method:** `GET`
- **Parameter Query (Opsional):** Sama seperti list COA biasa.
- **Response Sukses (200 OK):** Mengembalikan daftar COA Kas & Bank.

### 30. API Daftar Promo Aktif (Lokal)
Mengambil daftar promo/diskon yang tersimpan di dalam **Database Lokal POS**, otomatis difilter berdasarkan yang masih aktif, kuota masih tersedia, dan tanggal promo masih valid.

- **URL:** `/api/promo/listpromo-lokal.php`
- **Method:** `GET`
- **Parameter Query (Opsional):**
  - `limit` & `page` (Paginasi).
  - `search` (String): Cari berdasarkan nama promo atau nama barang.
  - `no` (String): Filter berdasarkan Nomor Barang (`item_no`).
  - `category` (String): Filter berdasarkan kategori pelanggan (`category_user`).
- **Response Sukses (200 OK):** Mengembalikan data promo, termasuk besaran `percentage` dan sisa `kuota`.

### 31. API Penggunaan Kuota Promo (Update Kuota)
Mengurangi kuota promo di database lokal ketika transaksi penjualan menggunakan promo tersebut berhasil.

- **URL:** `/api/promo/update-kuota.php`
- **Method:** `POST`
- **Payload Data (JSON / Form-Data):**
  - `id_promo` (Wajib - Integer): ID Promo.
  - `kuota` (Wajib - Integer): Jumlah kuota yang ingin dikurangi.
- **Response Sukses (200 OK):** Mengembalikan pesan sukses dan informasi kuota yang telah dikurangi.

### 32. API Pembatalan Kuota Promo (Cancel Kuota)
Mengembalikan / menambahkan ulang kuota promo di database lokal jika transaksi batal atau dihapus.

- **URL:** `/api/promo/cancel-kuota.php`
- **Method:** `POST`
- **Payload Data (JSON / Form-Data):**
  - `id_promo` (Wajib - Integer): ID Promo.
  - `kuota` (Wajib - Integer): Jumlah kuota yang ingin ditambahkan kembali.
- **Response Sukses (200 OK):** Mengembalikan pesan sukses dan informasi kuota yang telah ditambahkan.

### 33. API Kredensial Payment Gateway (Midtrans)
Mengambil daftar konfigurasi kredensial (Merchant ID, Client Key, Server Key) Midtrans dari Database Lokal POS.

- **URL:** `/api/midtrans/list.php`
- **Method:** `GET`
- **Response Sukses (200 OK):** Mengembalikan data koneksi Midtrans.

### 34. API Detail Penyesuaian Harga (Selling Price Adjustment)
Mengambil informasi detail mengenai penyesuaian/perubahan harga jual secara spesifik dari Accurate Online.

- **URL:** `/api/sellingprice/detail.php`
- **Method:** `GET`
- **Parameter Query:**
  - `id` atau `number` (Salah Satu Wajib).
- **Response Sukses (200 OK):** Mengembalikan data mentah penyesuaian harga dari Accurate.
