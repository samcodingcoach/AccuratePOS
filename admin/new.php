<!DOCTYPE html>
<html>
<head>
    <title>Tambah User</title>
</head>
<body>
    <h2>Form Tambah User</h2>
    <!-- Pesan status akan muncul di sini via JS -->
    <div id="message"></div>

    <form id="userForm">
        <label>Nama Lengkap:</label><br>
        <input type="text" name="nama_lengkap" required><br><br>

        <label>Username:</label><br>
        <input type="text" name="username" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <label>Status Aktif:</label><br>
        <select name="aktif">
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
        </select><br><br>

        <label>Hint (Petunjuk Password):</label><br>
        <textarea name="hint" rows="3"></textarea><br><br>

        <button type="submit">Simpan Data</button>
    </form>

    <!-- Script JS untuk memproses form tanpa reload halaman -->
    <script>
        document.getElementById('userForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const msgDiv = document.getElementById('message');

            fetch('../classes/simpan_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    msgDiv.innerHTML = '<p style="color:green">' + data.message + '</p>';
                    this.reset(); // Kosongkan form jika berhasil
                } else {
                    msgDiv.innerHTML = '<p style="color:red">' + data.message + '</p>';
                }
            })
            .catch(error => {
                msgDiv.innerHTML = '<p style="color:red">Terjadi kesalahan sistem.</p>';
            });
        });
    </script>
</body>
</html>