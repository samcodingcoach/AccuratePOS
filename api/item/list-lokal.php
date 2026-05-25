<?php
/**
 * API ITEM LIST - LOCAL VERSION (Filter Rentang Tanggal & Pencarian)
 * File: api/item/list-lokal.php
 * Path Koneksi: ../../config/koneksi.php
 */

// Load koneksi database
// 1. Muat konfigurasi dan class utama (WAJIB PERTAMA)
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
    // 1. Ambil dan validasi parameter pagination (Default: 250)
    $limit  = isset($_GET['limit']) ? max(1, min(500, (int)$_GET['limit'])) : 250;
    $page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    // 2. Ambil parameter filter tanggal, barcode, dan search
    $startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : ''; 
    $endDate   = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
    $barcode   = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';
    $search    = isset($_GET['search']) ? trim($_GET['search']) : ''; // Tambahan penangkap search

    // 3. Bangun Query Dinamis untuk WHERE clause
    $whereClauses = [];
    $bindTypes = "";
    $bindParams = [];

    // Filter Barcode jika diisi
    if ($barcode !== '') {
        $whereClauses[] = "barcode = ?";
        $bindTypes .= "s";
        $bindParams[] = $barcode;
    }

    // Filter Pencarian (Search) berdasarkan nama atau kode barang
    if ($search !== '') {
        $whereClauses[] = "(name LIKE ? OR item_no LIKE ?)";
        $bindTypes .= "ss"; // Dua parameter string
        $searchTerm = "%" . $search . "%"; 
        $bindParams[] = $searchTerm; // Untuk nama
        $bindParams[] = $searchTerm; // Untuk item_no
    }

    // Filter Rentang Tanggal pada last_sync
    if ($startDate !== '') {
        // Format ke awal hari: YYYY-MM-DD 00:00:00
        $fullStart = $startDate . " 00:00:00";
        $whereClauses[] = "last_sync >= ?";
        $bindTypes .= "s";
        $bindParams[] = $fullStart;
    }

    if ($endDate !== '') {
        // Format ke akhir hari: YYYY-MM-DD 23:59:59
        $fullEnd = $endDate . " 23:59:59";
        $whereClauses[] = "last_sync <= ?";
        $bindTypes .= "s";
        $bindParams[] = $fullEnd;
    }

    // Gabungkan kondisi WHERE jika ada parameter yang digunakan
    $whereSql = "";
    if (count($whereClauses) > 0) {
        $whereSql = " WHERE " . implode(" AND ", $whereClauses);
    }

    // 4. Hitung total data dengan kondisi filter yang sama
    $totalQuery = "SELECT COUNT(*) as total FROM item" . $whereSql;
    
    if (count($whereClauses) > 0) {
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

    // 5. Ambil data dengan LIMIT dan OFFSET
    $sql = "SELECT id, item_no, name, barcode, price, balance, image, last_sync, id_users 
            FROM item" . $whereSql . " LIMIT ? OFFSET ?";

    // Tambahkan tipe data dan parameter untuk limit & offset
    $bindTypes .= "ii";
    $bindParams[] = $limit;
    $bindParams[] = $offset;

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Gagal menyiapkan statement: " . $conn->error);
    }

    // Bind semua parameter secara dinamis
    $stmt->bind_param($bindTypes, ...$bindParams);
    $stmt->execute();
    $result = $stmt->get_result();

    $cleanItems = [];

    // 6. Mapping data hasil query
    while ($row = $result->fetch_assoc()) {
        $cleanItems[] = [
            'id'        => (int)$row['id'],
            'item_no'   => $row['item_no'],
            'name'      => $row['name'],
            'barcode'   => $row['barcode'],
            'balance'   => (int)$row['balance'],
            'price'     => (float)$row['price'],
            'image'     => $row['image'],
            'last_sync' => $row['last_sync'],
            'id_users'  => (int)$row['id_users']
        ];
    }

    // Balikin response JSON
    echo json_encode([
        'status'  => 'success',
        'message' => 'Data barang berhasil dimuat',
        'data'    => $cleanItems,
        'pagination' => [
            'current_page' => (int)$page,
            'total_page'   => (int)$totalPage,
            'has_more'     => (bool)$hasMore,
            'total_items'  => (int)$totalRows
        ],
        'meta' => [
            'timestamp'   => date('c'),
            'api_version' => '1.2-local'
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