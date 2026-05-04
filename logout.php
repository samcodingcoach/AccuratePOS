<?php
// 1. Mulai session agar PHP tahu session mana yang akan dihancurkan
session_start();

// 2. Kosongkan semua variabel session yang sudah diset (seperti user_id, username)
session_unset();

// 3. Hancurkan session dari server
session_destroy();

// 4. Arahkan kembali ke halaman login
header("Location: login.php");
exit;
?>