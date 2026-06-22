<?php
/**
 * API KASIR LIST - LOCAL VERSION
 * File: api/kasir/list-lokal.php
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
    // 1. Ambil dan validasi parameter pagination (Default: 100)
    $limit  = isset($_GET['limit']) ? max(1, min(500, (int)$_GET['limit'])) : 100;
    $page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    // 2. Kondisi wajib: aktif = 1
    $whereSql = " WHERE aktif = 1";

    // 3. Hitung total data
    $totalQuery = "SELECT COUNT(*) as total FROM users" . $whereSql;
    $totalResult = $conn->query($totalQuery);
    $totalRows = $totalResult->fetch_assoc()['total'];
    
    // Hitung total halaman
    $totalPage = ceil($totalRows / $limit);
    $hasMore = $page < $totalPage;

    // 4. Ambil data dengan LIMIT, OFFSET dan diurutkan berdasarkan created_date DESC
    $sql = "SELECT id_users, username, email, created_date, nama_lengkap, aktif 
            FROM users" . $whereSql . " ORDER BY created_date DESC LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Gagal menyiapkan statement: " . $conn->error);
    }

    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $cleanUsers = [];

    // 5. Mapping data hasil query
    while ($row = $result->fetch_assoc()) {
        $cleanUsers[] = [
            'id_users'     => (int)$row['id_users'],
            'username'     => $row['username'],
            'email'        => $row['email'],
            'created_date' => $row['created_date'],
            'nama_lengkap' => $row['nama_lengkap'],
            'aktif'        => (int)$row['aktif']
        ];
    }

    // Balikin response JSON
    echo json_encode([
        'status'  => 'success',
        'message' => 'Data kasir berhasil dimuat',
        'data'    => $cleanUsers,
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
