- cari api memuat harga berdasarkan tipe pelanggan
  https://resto.samdev.org/pos-accurate/api/item/stokharga.php?no=100008&priceCategoryName=Membership
  https://resto.samdev.org/pos-accurate/api/pelanggan/list.php?
  https://resto.samdev.org/pos-accurate/api/pelanggan/list.php?search=Membership

-cari promo
http://localhost/pos-accurate/api/promo/listpromo-lokal.php?no=100015&category=membership

buat table promo
name, start,finish, persentase,active,kuota,category_user, no_item [ok]

buat api untuk promo munculkan nama_promo,persentase berdasarkan category_user, no_item, active true atau berdasarkan start finish dalam range waktu saat ini [ok]

letakan disini api\promo\listpromo-lokal.php [ok]

tambahkan di newfaktur field keterangan,
tambhkan angka persen ketika kembali ke new faktur[ok]
redesign cart container[ok]
diskon view tambahkan picker untuk memunculkan nama promo berdasarkan table promo[ok]

api dijalankan saat form_load ItemAdd [ok]
jangan lupa untuk update kuota setelah modul faktur penjualan di eksekusi [ok]

cek postman untuk sesuaikan api save.do faktur penjualan [ok]

New Faktur x:Name
PickerKonsumen -> menampilkan konsumen
ViewBarang - Grid
SearchBar_Item - Search
List_AutoComplete - CollectionView -> Menampilkan Barang
CartContainer - CollectionView -> Menampilkan cart
PickerPengirim - menampilkan pengiriman (Hardcode)
EntryAlamat
EntryNoPO
PickerBiaya -> menampilkan akun prakiraan
EntryHargaBiaya
BTambahBiaya -> Submit biaya
ListBiayaContainer - > Menampilkan biaya terpilih
CheckBoxPPN -> Pajak 11/12
EntrySubtotal
EntryTotalDiskon
EntryTotalBiaya
EntryTotalPajak
EntryGrandTotal
