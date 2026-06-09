- cari api memuat harga berdasarkan tipe pelanggan
  https://resto.samdev.org/pos-accurate/api/item/stokharga.php?no=100008&priceCategoryName=Membership
  https://resto.samdev.org/pos-accurate/api/pelanggan/list.php?
  https://resto.samdev.org/pos-accurate/api/pelanggan/list.php?search=Membership

api penjualan/detail-invoice.php?number=SI.2026.06.00002

-cari promo


http://localhost/pos-accurate/api/promo/listpromo-lokal.php?no=100015&category=membership

buat table promo
name, start,finish, persentase,active,kuota,category_user, no_item [ok]

buat api untuk promo munculkan nama_promo,persentase berdasarkan category_user, no_item, active true atau berdasarkan start finish dalam range waktu saat ini [ok]

letakan disini api\promo\listpromo-lokal.php [ok]

tambahkan di newfaktur field keterangan, [ok]
tambhkan angka persen ketika kembali ke new faktur[ok]
redesign cart container[ok]
diskon view tambahkan picker untuk memunculkan nama promo berdasarkan table promo[ok]

api dijalankan saat form_load ItemAdd [ok]
jangan lupa untuk update kuota setelah modul faktur penjualan di eksekusi [ok]


update kuota promo pake id_promo

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
EntryKeterangan

{
  "status": "success",
  "data": {
    "tax1Amount": 340773,
  
    "poNumber": "PO/2026/9010",
    "toAddress": "Jl. Ahmad Yani",
    "shipment": {
      "name": "Kurir Toko",
    },
   
    "description": "Antar jam 5 sore",
    "transDate": "05/06/2026",
    "cashDiscount": 63515,
    "number": "SI.2026.06.00002",
    "detailExpense": [
      {
        "detailName": "Pengantaran ke Customer",
        "expenseAmount": 50000,
        "account": {
          "no": "4402",
          }
      }
    ],
    "detailItem": [
      {
        "detailSerialNumber": [
          {
            "quantity": 1,
            "serialNumber": 
            {
              "number": "M7-002"
            }
          }
        ],
        "unitPrice": 2699000,
        "salesmanName": "Lia Octaviana",
       
        "warehouse": {
          "name": "Gudang Utama",
        },
        "detailName": "POCO M7 (8GB/256GB) - Black",
        "totalPrice": 2699000,
        "salesmanList": [
          {
            "number": "E.00002",
          }
        ],
        "itemDiscPercent":3,
        "quantity": 1,
      },
     
    ],
    "totalExpense": 50000,
    "subTotal": 3161452.35,
    "lastCashDiscount": 63515,
    "totalAmount": 3488710.35,
    "customer": {
      "name": "Membership",
      "customerNo": "MB002",
    }
  }
}

tambahkan di simpan "numericField1" : 0; sampai 3
batasi tambah promo hanya 3 row teratas.

{
  "status": "success",
  "data": {
    "tax1Amount": 340773,
    "numericField1" : 0;
    "numericField2" : 0;
    "numericField3" : 0;
    "poNumber": "PO/2026/9010",
    "toAddress": "Jl. Ahmad Yani",
    "shipment": {
      "name": "Kurir Toko",
    },
    "description": "Antar jam 5 sore",
    "transDate": "05/06/2026",
    "cashDiscount": 63515,
    "number": "SI.2026.06.00002",
    "detailExpense": [
      {
        "id" : 201,
        "detailName": "Pengantaran ke Customer",
        "expenseAmount": 50000,
        "account": {
          "no": "4402",
          }
      }
    ],
    "detailItem": [
      {
        "detailSerialNumber": [
          {
            "quantity": 1,
            "serialNumber": 
            {
              "number": "M7-002"
            },
            "id" : 350
          }
        ],
        "unitPrice": 2699000,
        "salesmanName": "Lia Octaviana",
        "item" : 
        {
          "no": "100015", <- NoItem Sekaligus gambar yang mana tinggal ditambah .jpg
        }
        "warehouse": {
          "name": "Gudang Utama",
        },
        "detailName": "POCO M7 (8GB/256GB) - Black",
        "totalPrice": 2699000,
        "salesmanList": [
          {
            "number": "E.00002",
          }
        ],
        "itemDiscPercent":3,
        "quantity": 1,
      },
     
    ],
    "totalExpense": 50000,
    "subTotal": 3161452.35,
    "lastCashDiscount": 63515,
    "totalAmount": 3488710.35,
    "customer": {
      "name": "Membership",
      "customerNo": "MB002",
    },
    "id" : 450;
  }
}


"data" : {
"subTotal": 100000,
"cashDiscount": 1322510,
"totalExpense": 15000,
"tax1Amount": 145476,
"totalAmount": 1482986,
"number": "SI.2026.06.00001",
"description" : "Test Description",
"transDate" : "08/06/2026"
"detailItem": [
      {
        "item": {
          "unitPrice": 2800000,
          "name": "POCO M7 (8GB/256GB) - Black",
          "no": "100014",
        },
        "itemCashDiscount": 53980,
        "totalPrice": 2645020,
        "quantity":1
      }
    ],
"customer": 
    {
       "name": "Membership",
       "customerNo": "MB002"
    }
}