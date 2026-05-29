- cari api memuat harga berdasarkan tipe pelanggan
  https://resto.samdev.org/pos-accurate/api/item/stokharga.php?no=100008&priceCategoryName=Membership
  https://resto.samdev.org/pos-accurate/api/pelanggan/list.php?
  https://resto.samdev.org/pos-accurate/api/pelanggan/list.php?search=Membership

edit invoice save.do
4402 - Biaya Pengantaran ke Customer
tambahkan api untuk mengambil pengiriman atau manual isi pengiriman (Ambil Sendiri, Kurir Toko)

pada itemAdd.xaml.cs apakah barang tsb ada nomor serinya dengan cari di item/detail_byNo.php?no=100002
bagian manageSN true atau false
jika true visible inputan true. jika false visible inputan false.

alamat mencari sn
item/serial_byNo.php?no=100002

output

      {
        "warehouse": {
          "id": 50,
          "name": "Gudang Utama"
        },
        "serialNumber": {
          "id": 52,
          "number": "SR260505-0003",
          "createDate": "05/05/2026 08:47:40",
          "expiredDate": ""
        },
        "quantity": 1
      },...

pastikan nomor seri tsb cocok
