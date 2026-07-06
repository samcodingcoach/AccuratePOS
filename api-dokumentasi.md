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
              "street": "Jl. Kesejahteraan",
              "province": "KALIMANTAN TIMUR",
              "pic": "Budi"
          }
      ],
      "pagination": { ... },
      "meta": { ... }
  }
  ```

### 7.1 API Detail Gudang (Warehouse Detail)
Mengambil informasi detail untuk sebuah gudang.

- **URL:** `/api/gudang/detail.php`
- **Method:** `GET`
- **Parameter Query:**
  - `id` (Wajib - Integer): ID unik dari gudang.
- **Response Sukses (200 OK):** Mengembalikan _object_ detail gudang.
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "data": {
          "id": 100,
          "name": "Gudang Utama",
          "street": "Jl. Kesejahteraan",
          "province": "KALIMANTAN TIMUR",
          "pic": "Budi"
      }
  }
  ```
### 7.2 API Update Gudang (Update Warehouse)
Memperbarui informasi data gudang di Accurate Online berdasarkan ID gudang.

- **URL:** `/api/gudang/update.php`
- **Method:** `POST`
- **Payload Data (JSON / Form-Data):**
  - `id` (Wajib - Integer/String): ID unik gudang yang ingin diperbarui.
  - `name` (Wajib - String): Nama gudang.
  - `pic` (Opsional - String): Nama penanggung jawab (PIC) gudang.
  - `province` (Opsional - String): Nama provinsi dari alamat gudang.
  - `street` (Opsional - String): Alamat lengkap (jalan) gudang.
  - `scrapWarehouse` (Opsional - Boolean): Tanda jika gudang ini adalah gudang barang rusak. Default akan dibaca `true` jika tidak dikirim, sesuai spesifikasi internal.
  - `suspended` (Opsional - Boolean): Tanda jika gudang dinonaktifkan. Default `false`.
- **Response Sukses (200 OK):** Mengembalikan _object_ detail gudang yang baru diperbarui.
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "message": "Gudang berhasil diperbarui",
      "data": {
          "id": 100,
          "name": "Gudang Utama"
      }
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
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "data": {
          "id": 1,
          "name": "Elektronik",
          "lvl": 1,
          "parent_id": null
      }
  }
  ```

### 9.1 API Simpan Kategori Barang (Save Item Category)
Menyimpan atau membuat Kategori Barang (Item Category) baru ke Accurate Online.

- **URL:** `/api/item-category/save.php`
- **Method:** `POST`
- **Payload Data (JSON / Form-Data):**
  - `name` (Wajib - String): Nama kategori barang.
  - `defaultCategory` (Opsional - Boolean): Apakah ini kategori default (`true`/`false`).
  - `parentName` (Opsional - String): Nama dari kategori induk (jika ini merupakan sub-kategori). Dapat diisi dengan string kosong `""` jika tidak ingin memiliki kategori induk.
- **Response Sukses (200 OK):** Mengembalikan _object_ detail kategori yang baru saja disimpan.
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "message": "Kategori barang berhasil disimpan",
      "data": {
          "id": 2,
          "name": "Aksesoris",
          "parentName": "Elektronik"
      }
  }
  ```
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
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "message": "Kategori barang berhasil diperbarui",
      "data": {
          "id": 2,
          "name": "Aksesoris Elektronik"
      }
  }
  ```
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
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "data": [
          {
              "id": 100,
              "item_no": "BRG-001",
              "name": "Kopi Susu",
              "barcode": "899123456789",
              "price": 15000,
              "balance": 50
          }
      ],
      "pagination": { ... }
  }
  ```

### 12. API Detail Barang
Mengambil seluruh data mentah (Raw Data) informasi barang secara spesifik. Tersedia dalam 3 Endpoint berdasarkan cara mencarinya:

- **Berdasarkan Kode Barcode (UPC):** `/api/item/detail.php`
  - Parameter: `upcno` atau `upc` (Wajib).
- **Berdasarkan Nomor Barang (Item No):** `/api/item/detail_byNo.php`
  - Parameter: `no` (Wajib).
- **Berdasarkan Nomor atau Serial Number (SN):** `/api/item/search_byNoItem.php`
  - Parameter: `no` (Wajib). Endpoint ini bisa mendeteksi nomor barang *maupun* nomor seri (SN).

**Contoh Output JSON (Detail Barang):**
```json
{
    "status": "success",
    "data": {
        "id": 100,
        "item_no": "BRG-001",
        "name": "Kopi Susu",
        "unitPrice": 15000
    }
}
```

### 13. API Harga Barang (Raw Price)
Mengambil informasi harga jual barang beserta simulasinya.

- **URL:** `/api/item/price.php`
- **Method:** `GET`
- **Parameter Query (Opsional tapi disarankan):**
  - `no` atau `upcNo` (Salah satu Wajib).
  - `branchName`, `currencyCode`, `discountCategoryName`, `effectiveDate`, `priceCategoryName`.
- **Response Sukses (200 OK):** Mengembalikan data mentah harga jual dari Accurate.
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "data": {
          "no": "BRG-001",
          "price": 15000,
          "discountAmount": 0
      }
  }
  ```

### 14. API Stok Barang 
Mengambil informasi ketersediaan stok barang. Terdapat 3 Endpoint:

- **Raw Stock by Item No:** `/api/item/stock.php`
  - Parameter: `no` (Wajib), `warehouseName` (Opsional).
- **List Stock All Items:** `/api/item/list-stok.php`
  - Parameter: `warehouse` atau `warehouseName` (Opsional), `page`, `limit`.
- **Serial Number by Warehouse:** `/api/item/serial_byNo.php`
  - Parameter: `itemNo` atau `no` (Wajib). Mengembalikan rincian nomor seri barang per gudang.

**Contoh Output JSON (Informasi Stok Mentah):**
```json
{
    "status": "success",
    "data": [
        {
            "warehouseId": 1,
            "warehouseName": "Gudang Utama",
            "quantity": 50
        }
    ]
}
```

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
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "data": [
          {
              "id": 12345,
              "number": "INV-26-0001",
              "transDate": "22/06/2026",
              "totalAmount": 150000,
              "customerName": "Umum"
          }
      ]
  }
  ```

### 17. API Detail Faktur Penjualan
Mengambil informasi lengkap (detail) dari satu faktur penjualan tertentu.

- **URL:** `/api/penjualan/detail-invoice.php`
- **Method:** `GET`
- **Parameter Query:**
  - `id` atau `number` (Salah Satu Wajib): ID unik sistem atau Nomor Faktur.
- **Response Sukses (200 OK):** Mengembalikan _object_ detail faktur dari Accurate.
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "data": {
          "id": 12345,
          "number": "INV-26-0001",
          "transDate": "22/06/2026",
          "detailItem": [
              {
                  "item": { "name": "Kopi Susu", "no": "BRG-001" },
                  "quantity": 10,
                  "unitPrice": 15000
              }
          ]
      }
  }
  ```

### 18. API Detail Faktur Penjualan (Ter-Filter & Ringan)
Sama seperti detail biasa, namun menspesifikkan filter tanggal/pelanggan dan merampingkan data yang dibalas (membuang field yang tidak perlu) agar JSON response menjadi sangat ringan.

- **URL:** `/api/penjualan/detail-invoice-filter.php`
- **Method:** `GET`
- **Parameter Query (Opsional):**
  - `customerNo`, `fromDate`, `toDate`, `page`, `pageSize`.
- **Response Sukses (200 OK):** Mengembalikan _array_ yang field-nya hanya berisi: `transDate`, `invoiceTime`, `dueDate`, `paymentTermId`, `number`, `subTotal`, `salesAmountBase`, `status`.
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "data": [
          {
              "number": "INV-26-0001",
              "transDate": "22/06/2026",
              "subTotal": 150000,
              "status": "PAID"
          }
      ]
  }
  ```

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
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "data": [
          {
              "id": 98765,
              "number": "SR-26-0001",
              "transDate": "22/06/2026",
              "chequeAmount": 150000,
              "customer": { "name": "Umum" }
          }
      ]
  }
  ```

### 22. API Detail Penerimaan Penjualan
Mengambil informasi detail untuk satu data penerimaan penjualan/pelunasan.

- **URL:** `/api/penjualan/detail-receipt.php`
- **Method:** `GET`
- **Parameter Query:**
  - `id` atau `number` (Salah satu Wajib).
- **Response Sukses (200 OK):** Mengembalikan object data detail pelunasan dari Accurate.
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "data": {
          "id": 98765,
          "number": "SR-26-0001",
          "transDate": "22/06/2026",
          "chequeAmount": 150000,
          "detailInvoice": [
              {
                  "invoiceNumber": "INV-26-0001",
                  "paymentAmount": 150000
              }
          ]
      }
  }
  ```

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
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "data": [
          {
              "id": 10,
              "customerNo": "CUST-01",
              "name": "Pelanggan Umum",
              "mobilePhone": "08123456789"
          }
      ],
      "pagination": { ... }
  }
  ```

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
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "data": [
          {
              "id": 5,
              "number": "EMP-001",
              "name": "Budi",
              "salesman": true
          }
      ],
      "pagination": { ... }
  }
  ```

### 26. API Detail Karyawan (Employee Detail)
Mengambil informasi lengkap (detail) dari satu profil karyawan.

- **URL:** `/api/karyawan/detail.php`
- **Method:** `GET`
- **Parameter Query:**
  - `id` atau `number` (Salah Satu Wajib): ID unik sistem atau Nomor Karyawan.
- **Response Sukses (200 OK):** Mengembalikan _object_ detail karyawan.
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "data": {
          "id": 5,
          "number": "EMP-001",
          "name": "Budi",
          "salutation": "MR",
          "salesman": true
      }
  }
  ```

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
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "message": "Data karyawan berhasil disimpan",
      "data": {
          "id": 6,
          "name": "Andi",
          "number": "EMP-002"
      }
  }
  ```
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
Mengambil daftar akun perkiraan (COA) untuk kategori **Pendapatan / Account Receivable** dari Accurate. 
*Catatan Performa:* Endpoint ini menerapkan _Eager Loading_ dengan _Rate Limiter_ (sekitar 6 hit/detik) untuk memuat `lvl` dan `balance`. Waktu muat dapat berlangsung selama puluhan detik tergantung dari jumlah data.

- **URL:** `/api/coa/list.php`
- **Method:** `GET`
- **Parameter Query (Opsional):**
  - `page` (Integer), `limit` / `pageSize` (Integer), `search` (String).
- **Response Sukses (200 OK):** Mengembalikan daftar COA kategori Pendapatan/Piutang yang diperkaya dengan *field* tambahan (`lvl`, `balance`, `accountTypeName`, `asOf`).
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "data": [
          {
              "id": 20,
              "no": "4100",
              "name": "Pendapatan Penjualan",
              "accountType": "REVENUE",
              "accountTypeName": "Pendapatan",
              "lvl": 1,
              "balance": 15000000,
              "asOf": "22/06/2026"
          }
      ],
      "pagination": { ... }
  }
  ```

### 28.1 API Detail Akun Perkiraan (COA Detail)
Mengambil informasi detail untuk satu akun perkiraan (GL Account).

- **URL:** `/api/coa/detail.php`
- **Method:** `GET`
- **Parameter Query:**
  - `id` (Wajib - Integer): ID unik dari akun perkiraan.
  - `no` (Opsional - String): Nomor akun perkiraan.
- **Response Sukses (200 OK):** Mengembalikan _object_ detail akun perkiraan.
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "data": {
          "id": 20,
          "no": "4100",
          "name": "Pendapatan Penjualan",
          "accountType": "REVENUE"
      }
  }
  ```

### 28.2 API Saldo Neraca COA (Balance Sheet Amount)
Mengambil daftar saldo seluruh akun bertipe Neraca (Balance Sheet) pada tanggal tertentu. 
*Catatan:* Data yang dikembalikan sudah difilter (hanya yang saldo / `amount` bukan 0), diurutkan (*sorted*) berdasarkan properti `isParent` secara *descending* (akun induk berada di atas), dan **dikelompokkan ke dalam _nested array_ (Aset, Liabilitas, Ekuitas)** berdasarkan `accountType`.

- **URL:** `/api/coa/saldo-neraca.php`
- **Method:** `GET`
- **Parameter Query:**
  - `asOfDate` (Opsional - String): Tanggal cut-off untuk penarikan saldo (format: `DD/MM/YYYY`, contoh: `13/01/2026`). Jika tidak diisi, otomatis akan menggunakan tanggal hari ini.
- **Response Sukses (200 OK):** Mengembalikan _nested object_ berisi `data` yang memuat kelompok utama (`aset-aktiva`, `liabilitas-hutang`, `equitas-modal`). Tiap kelompok memuat `total` dan `items`. Selain itu, terdapat _object_ `summary` yang mengembalikan perhitungan `totalAset`, `totalLiabilitasEkuitas`, dan `selisih`.
  
  **Contoh Output JSON:**
  ```json
  {
    "status": "success",
    "message": "Saldo neraca akun perkiraan berhasil diambil",
    "summary": {
      "totalAset": 157103102.1,
      "totalLiabilitasEkuitas": 154761233,
      "selisih": 2341869.1
    },
    "data": {
      "aset-aktiva": {
        "total": 157103102.1,
        "items": [
          {
            "id": 123,
            "no": "1100",
            "name": "Kas Kecil",
            "accountType": "CASH_BANK",
            "amount": 5000000,
            "lvl": 1,
            "isParent": true
          }
        ]
      },
      "liabilitas-hutang": {
        "total": 154761233,
        "items": []
      },
      "equitas-modal": {
        "total": 0,
        "items": []
      },
      "lainnya": {
        "total": 0,
        "items": []
      }
    }
  }
  ```

### 28.3 API Rugi Laba COA (Profit & Loss Amount)
Mengambil daftar nilai/saldo akun yang tergolong dalam Rugi Laba (Profit & Loss) untuk suatu rentang waktu tertentu.

- **URL:** `/api/coa/rugilaba.php`
- **Method:** `GET`
- **Parameter Query:**
  - `fromDate` (Opsional - String): Tanggal mulai penarikan data (format: `DD/MM/YYYY`, contoh: `01/02/2026`). Jika tidak diisi, otomatis mengambil **awal bulan ini**.
  - `toDate` (Opsional - String): Tanggal akhir penarikan data (format: `DD/MM/YYYY`, contoh: `28/02/2026`). Jika tidak diisi, otomatis mengambil **akhir bulan ini**.
- **Response Sukses (200 OK):** Mengembalikan _nested object_ berisi `data` yang memuat kelompok akun Rugi Laba (`revenue`, `cogs`, `expense`, dll) beserta `total` dan `items` masing-masing. Terdapat juga _object_ `summary` yang mengkalkulasi `totalPendapatan`, `hpp`, `labaKotor`, `bebanOperasional`, `labaOperasional`, dan `labaBersih`.
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "message": "Data rugi laba berhasil diambil",
      "summary": {
          "totalPendapatan": 25000000,
          "hpp": 10000000,
          "labaKotor": 15000000,
          "bebanOperasional": 5000000,
          "labaOperasional": 10000000,
          "pendapatanLainLain": 1000000,
          "bebanLainLain": 500000,
          "labaBersih": 10500000
      },
      "data": {
          "revenue": {
              "total": 25000000,
              "items": [
                  {
                      "id": 200,
                      "no": "4000",
                      "name": "Pendapatan Usaha",
                      "accountType": "REVENUE",
                      "amount": 25000000,
                      "lvl": 1,
                      "isParent": true
                  }
              ]
          },
          "cogs": { "total": 10000000, "items": [] },
          "expense": { "total": 5000000, "items": [] },
          "other-income": { "total": 1000000, "items": [] },
          "other-expense": { "total": 500000, "items": [] },
          "lainnya": { "total": 0, "items": [] }
      }
  }
  ```
### 29. API Daftar Akun Kas & Bank
Mengambil daftar akun perkiraan khusus kategori **Kas & Bank**. Sangat berguna untuk memilih metode pelunasan pembayaran.
*Catatan Performa:* Endpoint ini juga menerapkan _Eager Loading_ yang sama seperti Daftar COA.

- **URL:** `/api/coa/list-kasbank.php`
- **Method:** `GET`
- **Parameter Query (Opsional):** Sama seperti list COA biasa.
- **Response Sukses (200 OK):** Mengembalikan daftar COA Kas & Bank beserta *field* utuhnya (`lvl`, `balance`, dsb).
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "data": [
          {
              "id": 15,
              "no": "1101",
              "name": "Kasir Kas",
              "accountType": "CASH_BANK",
              "lvl": 1,
              "balance": 5000000
          }
      ],
      "pagination": { ... }
  }
  ```

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
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "data": [
          {
              "id_promo": 1,
              "nama_promo": "Diskon Akhir Tahun",
              "percentage": 10,
              "kuota": 100,
              "item_no": "BRG-001"
          }
      ]
  }
  ```

### 31. API Penggunaan Kuota Promo (Update Kuota)
Mengurangi kuota promo di database lokal ketika transaksi penjualan menggunakan promo tersebut berhasil.

- **URL:** `/api/promo/update-kuota.php`
- **Method:** `POST`
- **Payload Data (JSON / Form-Data):**
  - `id_promo` (Wajib - Integer): ID Promo.
  - `kuota` (Wajib - Integer): Jumlah kuota yang ingin dikurangi.
- **Response Sukses (200 OK):** Mengembalikan pesan sukses dan informasi kuota yang telah dikurangi.
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "message": "Kuota promo berhasil dikurangi",
      "sisa_kuota": 99
  }
  ```

### 32. API Pembatalan Kuota Promo (Cancel Kuota)
Mengembalikan / menambahkan ulang kuota promo di database lokal jika transaksi batal atau dihapus.

- **URL:** `/api/promo/cancel-kuota.php`
- **Method:** `POST`
- **Payload Data (JSON / Form-Data):**
  - `id_promo` (Wajib - Integer): ID Promo.
  - `kuota` (Wajib - Integer): Jumlah kuota yang ingin ditambahkan kembali.
- **Response Sukses (200 OK):** Mengembalikan pesan sukses dan informasi kuota yang telah ditambahkan.
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "message": "Kuota promo berhasil dikembalikan",
      "sisa_kuota": 100
  }
  ```

### 33. API Kredensial Payment Gateway (Midtrans)
Mengambil daftar konfigurasi kredensial (Merchant ID, Client Key, Server Key) Midtrans dari Database Lokal POS.

- **URL:** `/api/midtrans/list.php`
- **Method:** `GET`
- **Response Sukses (200 OK):** Mengembalikan data koneksi Midtrans.
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "data": {
          "merchant_id": "M-12345",
          "client_key": "Mid-client-abc",
          "server_key": "Mid-server-def"
      }
  }
  ```

### 34. API Detail Penyesuaian Harga (Selling Price Adjustment)
Mengambil informasi detail mengenai penyesuaian/perubahan harga jual secara spesifik dari Accurate Online.

- **URL:** `/api/sellingprice/detail.php`
- **Method:** `GET`
- **Parameter Query:**
  - `id` atau `number` (Salah Satu Wajib).
- **Response Sukses (200 OK):** Mengembalikan data mentah penyesuaian harga dari Accurate.
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "data": {
          "id": 12,
          "number": "ADJ-001",
          "effectiveDate": "22/06/2026",
          "details": []
      }
  }
  ```

---

## FASE 6: Modul Stok Opname

### 35. API Daftar Perintah Stok Opname (Stock Opname Order List)
Mengambil daftar perintah stok opname dari Accurate Online.

- **URL:** `/api/stokopname-order/list.php`
- **Method:** `GET`
- **Parameter Query (Opsional):**
  - `page` (Integer): Halaman data.
  - `limit` (Integer): Jumlah maksimal data per halaman.
  - `transDate` (String): Filter berdasarkan tanggal transaksi (format `DD/MM/YYYY`, contoh: `04/07/2026`).
- **Response Sukses (200 OK):**
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "message": "Data stok opname order berhasil diambil",
      "data": [
          {
              "id": 1,
              "number": "SOO-001",
              "startDate": "03/07/2026",
              "description": "Opname Gudang Utama Juli",
              "transDate": "04/07/2026",
              "branchName": "Pusat",
              "personCharged": "Budi",
              "warehouse": {
                  "id": 1,
                  "name": "Gudang Utama"
              },
              "statusName": "Draft"
          }
      ],
      "pagination": {
          "page": 1,
          "pageSize": 100,
          "pageCount": 1,
          "rowCount": 1
      }
  }
  ```

### 36. API Detail Perintah Stok Opname (Stock Opname Order Detail)
Mengambil informasi lengkap (detail) dari satu perintah stok opname tertentu.

- **URL:** `/api/stokopname-order/detail.php`
- **Method:** `GET`
- **Parameter Query:**
  - `id` atau `number` (Salah Satu Wajib): ID unik sistem atau Nomor Perintah Stok Opname.
- **Response Sukses (200 OK):** Mengembalikan _object_ detail stok opname order dari Accurate.
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "message": "Detail stok opname order berhasil diambil",
      "data": {
          "id": 1,
          "number": "SOO-001",
          "transDate": "04/07/2026",
          "status": "DRAFT",
          "detailItem": [
              {
                  "item": { "name": "Kopi Susu", "no": "BRG-001" },
                  "quantity": 100,
                  "unitName": "PCS"
              }
          ]
      }
  }
  ```

### 37. API Daftar Hasil Stok Opname (Stock Opname Result List)
Mengambil daftar hasil eksekusi stok opname dari Accurate Online.

- **URL:** `/api/stokopname-result/list.php`
- **Method:** `GET`
- **Parameter Query (Opsional):**
  - `page` (Integer): Halaman data.
  - `limit` (Integer): Jumlah maksimal data per halaman.
  - `search` (String): Pencarian umum berdasarkan kata kunci (nomor transaksi, keterangan, dll).
  - `transDate` (String): Filter berdasarkan tanggal transaksi (format `DD/MM/YYYY`, contoh: `04/07/2026`).
- **Response Sukses (200 OK):**
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "message": "Data stok opname result berhasil diambil",
      "data": [
          {
              "number": "OPR.00001",
              "transDate": "04/07/2026",
              "description": "Maaf telah memberika stok opname",
              "transDateView": "04 Jul 2026",
              "order": {
                  "number": "OPO.00001"
              }
          }
      ],
      "pagination": {
          "page": 1,
          "pageSize": 100,
          "pageCount": 1,
          "rowCount": 1
      }
  }
  ```

### 38. API Detail Hasil Stok Opname (Stock Opname Result Detail)
Mengambil informasi lengkap (detail) dari satu hasil stok opname tertentu.

- **URL:** `/api/stokopname-result/detail.php`
- **Method:** `GET`
- **Parameter Query:**
  - `id` atau `number` (Salah Satu Wajib): ID unik sistem atau Nomor Hasil Stok Opname.
- **Response Sukses (200 OK):** Mengembalikan _object_ detail stok opname result dari Accurate.
  **Contoh Output JSON:**
  ```json
  {
      "status": "success",
      "message": "Detail stok opname result berhasil diambil",
      "data": {
          "number": "OPR.00001",
          "id": 50,
          "order": {
              "number": "OPO.00001",
              "id": 50,
              "startDate": "04/07/2026",
              "status": "DONE"
          },
          "detailItem": [
              {
                  "item": {
                      "unit1": { "name": "Unit" },
                      "name": "KRISBOW 4 INCI KIPAS ANGIN MEJA PERSONAL",
                      "no": "100016"
                  },
                  "quantity": 84,
                  "detailSerialNumber": [
                      {
                          "quantity": 1,
                          "serialNumber": {
                              "number": "KRB013",
                              "updateStockDate": "01/01/2026"
                          }
                      }
                  ]
              },
              {
                  "item": {
                      "unit1": { "name": "Unit" },
                      "name": "POCO M7 (8GB/256GB) - Black",
                      "no": "100014"
                  },
                  "quantity": 0,
                  "detailSerialNumber": []
              }
          ],
          "description": "Maaf telah memberika stok opname",
          "transDate": "04/07/2026"
      }
  }
  ```
