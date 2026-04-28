<?php
// Mulai sesi untuk mengenalinya, lalu hancurkan semuanya!
session_start();
session_unset();
session_destroy();

// Tendang kembali ke halaman Beranda
header("Location: index.php");
exit();
?>