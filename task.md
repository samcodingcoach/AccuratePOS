buatkan api controller (stokharga.php) untuk mengecek jadi satu sebuah harga dan stok
cek dari item/price.php?no=100008&priceCategoryName=Umum
ini sample json price.php
{
"status": "success",
"message": "Data harga berhasil diambil",
"data": {
"unitPrice": 350000, <- ambil ini

    "no": "100008"

}
}
dan item/stock.php?no=100008
ini sample json stok.php
{
"status": "success",
"message": "Data stok mentah berhasil diambil",
"data": {
"availableStock": 5 <- ambil ini
}
}

output yang saya mau
no,unitPrice,availableStok

/api/sellingprice-adjustment
/detail.do (HTTP Method: GET, Scope: sellingprice_adjustment_view)

parameter request id nanti bakal muncul apa, jika muncul noitem (kode_barang) dan tipe pelanggan.

send gemini
