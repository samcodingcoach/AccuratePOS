- cari api memuat harga berdasarkan tipe pelanggan
  https://resto.samdev.org/pos-accurate/api/item/stokharga.php?no=100008&priceCategoryName=Membership
  https://resto.samdev.org/pos-accurate/api/pelanggan/list.php?
  https://resto.samdev.org/pos-accurate/api/pelanggan/list.php?search=Membership

buat table promo
name, start,finish, persentase,active,kuota,category_user, no_item

buat api untuk promo munculkan nama_promo,persentase berdasarkan category_user, no_item, active true atau berdasarkan start finish dalam range waktu saat ini

letakan disini api\promo\listpromo-lokal.php

tambahkan di newfaktur field keterangan,
diskon view tambahkan picker untuk memunculkan nama promo berdasarkan table promo

api dijalankan saat form_load ItemAdd
jangan lupa untuk update kuota setelah modul faktur penjualan di eksekusi
