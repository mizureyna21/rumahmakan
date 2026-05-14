<?php
// Konfigurasi database
$host = "localhost";
$user = "root";
$pass = "0000";
$db = "db_rumah_makan";

// Membuat koneksi ke database menggunakan mysqli
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Memeriksa apakah koneksi berhasil, jika tidak hentikan eksekusi dan tampilkan error
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>