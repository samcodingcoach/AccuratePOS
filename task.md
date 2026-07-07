api untuk save stok opname order
/api/stock-opname-order
/save.do (HTTP Method: POST, Scope: stock_opname_order_save)

field yang di simpan
*itemCategoryListName[n]
number 
*personCharged
*userListAccount[n]
*warehouseName
id 
*startDate
*transDate
description

[n], jika isian lebih dari satu
number jika kosong, gunakan generate dari accurate
id untuk delete / update
startDate format mm/dd/yyyy
transDate hari ini format mm/dd/yyyy


setelah dibuat berikan dokumentasi di api-dokumentasi 