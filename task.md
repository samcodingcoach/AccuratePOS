pada item.php bagian kolom Item No dapat diklik dan menuju halaman lain
halaman tsb adalah pricestock.php?no=(value dari item no)&priceCategoryName=Umum
Umum

Buatkan pada admin/item/pricestock.php, sebuah tampilan

dropdownlist (isinya : Umum,Membership,Shopee,Free) default Umum
muncul dibawahnya field entry
Nama Barang -> name
Harga -> unitPrice
Stok -> avaiableStock

Tombol Update Stock

data data sini diambil dari api/item/stokharga.php?no=XXX&priceCategoryName=XXX
no diambil dari get url no dan priceCategoryName diambil dari dropdownlist value

berikut contoh json stokharga.php
{
"status": "success",
"message": "Data gabungan harga dan stok berhasil dimuat dengan jeda aman",
"data": {
"no": "100008",
"name": "JBL Sense Pro True wireless open-ear headphones - Black",
"unitPrice": 410000,
"availableStock": 5
},
"meta": {
"timestamp": "2026-05-18T11:16:20+08:00",
"price_category_used": "Shopee",
"delay_applied": "1 second"
}
}
