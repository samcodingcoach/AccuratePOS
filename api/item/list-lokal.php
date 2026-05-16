<?php
/**
 * API ITEM LIST - LOCAL VERSION
 * File: api/item/list-lokal.php
 * Path Koneksi: ../../config/koneksi.php
 */

// Load koneksi database
require_once __DIR__ . '/../../config/koneksi.php';

// Set header response
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

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
    // 1. Ambil dan validasi parameter pagination (Default: limit=20, page=1)
    $limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 20;
    $page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    // 2. Hitung total data dari tabel 'item' untuk keperluan pagination metadata
    $totalQuery = "SELECT COUNT(*) as total FROM item";
    $totalResult = $conn->query($totalQuery);
    if (!$totalResult) {
        throw new Exception("Gagal menghitung total data: " . $conn->error);
    }
    $totalRows = $totalResult->fetch_assoc()['total'];
    
    // Hitung total halaman
    $totalPage = ceil($totalRows / $limit);
    $hasMore = $page < $totalPage;

    // 3. Ambil data lokal dari tabel 'item' dengan Prepared Statement
    $sql = "SELECT id, item_no, name, barcode, price, balance, image, last_sync, id_users 
            FROM item 
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Gagal menyiapkan statement: " . $conn->error);
    }

    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $cleanItems = [];

    // 4. Mapping data sesuai dengan field di database lokal Anda
    while ($row = $result->fetch_assoc()) {
        $cleanItems[] = [
            'id'        => (int)$row['id'],
            'item_no'   => $row['item_no'],
            'name'      => $row['name'],
            'barcode'   => $row['barcode'],
            'balance'   => (int)$row['balance'], // cast ke int
            'price'     => (float)$row['price'], // cast ke float/double
            'image'     => $row['image'],
            'last_sync' => $row['last_sync'],
            'id_users'  => (int)$row['id_users']
        ];
    }

    // Balikin response sukses dengan format persis seperti contoh Anda
    echo json_encode([
        'status'  => 'success',
        'message' => 'Data barang berhasil dimuat',
        'data'    => $cleanItems,
        'pagination' => [
            'current_page' => (int)$page,
            'total_page'   => (int)$totalPage,
            'has_more'     => (bool)$hasMore
        ],
        'meta' => [
            'timestamp'   => date('c'),
            'api_version' => '1.0-local'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    // Return error response jika ada kendala query/database
    http_response_code(500);
    echo json_encode([
        'status'  => 'error', 
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
} finally {
    // Pastikan koneksi & stmt ditutup (Best Practice PHP 8.3)
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>