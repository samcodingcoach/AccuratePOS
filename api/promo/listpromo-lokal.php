<?php
/**
 * API PROMO LIST - LOCAL VERSION (Filter Aktif, Pencarian, Item No & Kategori)
 * File: api/promo/listpromo-lokal.php
 * Path Koneksi: ../../config/koneksi.php
 */

// 1. Muat konfigurasi dan class utama
require_once __DIR__ . '/../../bootstrap.php';

// 2. Proteksi endpoint menggunakan file utils bawaan (Wajib login / Token)
require_once __DIR__ . '/../../utils/api_auth.php';
require_once __DIR__ . '/../../config/koneksi.php';

// 3. Set header agar output berupa JSON
header('Content-Type: application/json; charset=UTF-8');

// Proteksi Method HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Method not allowed'
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    // 1. Ambil dan validasi parameter pagination (Default: 50)
    $limit  = isset($_GET['limit']) ? max(1, min(500, (int)$_GET['limit'])) : 50;
    $page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    // 2. Ambil parameter pencarian dan filter spesifik (opsional)
    $search   = isset($_GET['search']) ? trim($_GET['search']) : ''; 
    $itemNo   = isset($_GET['no']) ? trim($_GET['no']) : ''; 
    $category = isset($_GET['category']) ? trim($_GET['category']) : ''; 

    // 3. Bangun Query Dinamis
    // Kondisi default: Aktif = 1, Kuota > 0, dan Tanggal Valid
    $baseWhere = "promo.isActive = 1 AND promo.kuota > 0 AND NOW() BETWEEN promo.start_date AND promo.finish_date";
    $whereSql = " WHERE " . $baseWhere;
    
    $bindTypes = "";
    $bindParams = [];

    // Filter Pencarian Text
    if ($search !== '') {
        $whereSql .= " AND (promo.name LIKE ? OR item.name LIKE ?)";
        $bindTypes .= "ss"; // Dua parameter string
        $searchTerm = "%" . $search . "%"; 
        $bindParams[] = $searchTerm; 
        $bindParams[] = $searchTerm; 
    }

    // Filter berdasarkan item_no (no=XXX)
    if ($itemNo !== '') {
        $whereSql .= " AND promo.item_no = ?";
        $bindTypes .= "s";
        $bindParams[] = $itemNo;
    }

    // Filter berdasarkan category_user (category=XXX)
    if ($category !== '') {
        $whereSql .= " AND promo.category_user = ?";
        $bindTypes .= "s";
        $bindParams[] = $category;
    }

    // 4. Hitung total data untuk keperluan pagination
    $totalQuery = "SELECT COUNT(*) as total 
                   FROM promo 
                   INNER JOIN item ON promo.item_no = item.item_no 
                   INNER JOIN users ON promo.id_users = users.id_users" . $whereSql;
    
    // Eksekusi total query bergantung pada ada tidaknya parameter binding
    if ($bindTypes !== '') {
        $totalStmt = $conn->prepare($totalQuery);
        $totalStmt->bind_param($bindTypes, ...$bindParams);
        $totalStmt->execute();
        $totalRows = $totalStmt->get_result()->fetch_assoc()['total'];
        $totalStmt->close();
    } else {
        $totalResult = $conn->query($totalQuery);
        $totalRows = $totalResult->fetch_assoc()['total'];
    }
    
    // Hitung total halaman
    $totalPage = ceil($totalRows / $limit);
    $hasMore = $page < $totalPage;

    // 5. Query utama untuk mengambil data dengan LIMIT dan OFFSET
    $sql = "SELECT 
                promo.id_promo, 
                promo.name, 
                promo.category_user, 
                promo.percentage, 
                promo.start_date, 
                promo.finish_date, 
                promo.item_no, 
                item.name as name_product, 
                promo.kuota, 
                promo.isActive, 
                promo.id_users, 
                users.username
            FROM promo
            INNER JOIN item ON promo.item_no = item.item_no
            INNER JOIN users ON promo.id_users = users.id_users" 
            . $whereSql . " LIMIT ? OFFSET ?";

    // Tambahkan parameter limit & offset (integer) di urutan terakhir
    $bindTypes .= "ii";
    $bindParams[] = $limit;
    $bindParams[] = $offset;

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Gagal menyiapkan statement: " . $conn->error);
    }

    // Bind parameter secara dinamis ke mysqli (mengakomodasi semua kombinasi filter)
    $stmt->bind_param($bindTypes, ...$bindParams);
    $stmt->execute();
    $result = $stmt->get_result();

    $cleanPromos = [];

    // 6. Mapping data hasil query agar format JSON rapi
    while ($row = $result->fetch_assoc()) {
        $cleanPromos[] = [
            'id_promo'      => (int)$row['id_promo'],
            'promo_name'    => $row['name'],
            'category_user' => $row['category_user'],
            'percentage'    => (float)$row['percentage'],
            'start_date'    => $row['start_date'],
            'finish_date'   => $row['finish_date'],
            'item_no'       => $row['item_no'],
            'item_name'     => $row['name_product'],
            'kuota'         => (int)$row['kuota'],
            'is_active'     => (bool)$row['isActive'],
            'id_users'      => (int)$row['id_users'],
            'username'      => $row['username']
        ];
    }

    // 7. Kembalikan response JSON identik dengan list-lokal.php
    echo json_encode([
        'status'  => 'success',
        'message' => 'Data promo aktif berhasil dimuat',
        'data'    => $cleanPromos,
        'pagination' => [
            'current_page' => (int)$page,
            'total_page'   => (int)$totalPage,
            'has_more'     => (bool)$hasMore,
            'total_items'  => (int)$totalRows
        ],
        'meta' => [
            'timestamp'   => date('c'),
            'api_version' => '1.0-local'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error', 
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>