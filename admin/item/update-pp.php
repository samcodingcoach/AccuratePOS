<?php
require_once __DIR__ . '/../../config/koneksi.php';
$item_no = isset($_GET['no']) ? trim($_GET['no']) : '';

if (empty($item_no)) {
    die('Parameter no tidak ditemukan.');
}

/*
|--------------------------------------------------------------------------
| Ambil Data Item
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT item_no, name, image
    FROM item
    WHERE item_no = ?
");

$stmt->bind_param("s", $item_no);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die('Item tidak ditemukan.');
}

$item = $result->fetch_assoc();
$stmt->close();

$message = '';

/*
|--------------------------------------------------------------------------
| Proses Upload
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (!isset($_FILES['image']) || $_FILES['image']['error'] != 0) {

        $message = "Silakan pilih gambar.";

    } else {

        $file = $_FILES['image'];

        // Maksimal 500KB
        if ($file['size'] > (500 * 1024)) {

            $message = "Ukuran gambar maksimal 500KB.";

        } else {

            // Validasi MIME Type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if ($mime !== 'image/jpeg') {

                $message = "File harus JPG/JPEG.";

            } else {

                // Folder root/images
                $upload_dir = __DIR__ . '/../../images/';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Nama file mengikuti item_no
                $new_filename = $item_no . '.jpg';

                $destination = $upload_dir . $new_filename;

                if (move_uploaded_file($file['tmp_name'], $destination)) {

                    $stmt_update = $conn->prepare("
                        UPDATE item
                        SET image = ? WHERE item_no = ?
                    ");

                    $stmt_update->bind_param(
                        "ss",
                        $new_filename,
                        $item_no
                    );

                        if ($stmt_update->execute()) {

                        echo "
                        <script>
                            alert('Gambar berhasil diperbarui');
                            window.location.href='item.php';
                        </script>
                        ";
                        exit;

                        }
                    
                    else {

                        $message = "Gagal update database.";

                    }

                    $stmt_update->close();

                } else {

                    $message = "Gagal mengupload file.";

                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Update Gambar Item</title>
</head>
<body>

<h2>Update Gambar Item</h2>

<?php if (!empty($message)) : ?>
    <p><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <td><strong>No Item</strong></td>
        <td><?php echo htmlspecialchars($item['item_no']); ?></td>
    </tr>

    <tr>
        <td><strong>Nama Barang</strong></td>
        <td><?php echo htmlspecialchars($item['name']); ?></td>
    </tr>

    <tr>
        <td><strong>Gambar Existing</strong></td>
        <td>
            <?php if (!empty($item['image']) && file_exists(__DIR__ . '/../../images/' . $item['image'])) : ?>

                <img
                    src="../../images/<?php echo htmlspecialchars($item['image']); ?>?t=<?php echo time(); ?>"
                    alt=""
                    style="max-width:250px; max-height:250px;"
                >

                <br><br>

                <?php echo htmlspecialchars($item['image']); ?>

            <?php else : ?>

                Belum ada gambar

            <?php endif; ?>
        </td>
    </tr>
</table>

<br>

<form method="post" enctype="multipart/form-data">

    <p>
        <label>Pilih Gambar Baru (JPG/JPEG, Maks. 500 KB)</label>
        <br><br>
        <input
            type="file"
            name="image"
            accept=".jpg,.jpeg,image/jpeg"
            required
        >
    </p>

    <p>
        <button type="submit">
            Upload & Update
        </button>
    </p>

</form>

</body>
</html>