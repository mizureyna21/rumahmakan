<?php
// Memulai sesi untuk menyimpan data keranjang
session_start();

// Mengambil ID menu dan jumlah beli dari form POST, dengan validasi dasar
$id_menu = isset($_POST['id_menu']) ? (int) $_POST['id_menu'] : 0;
$jumlah_beli = isset($_POST['jumlah_beli']) ? (int) $_POST['jumlah_beli'] : 1;

// Memproses jika ID menu dan jumlah beli valid (lebih dari 0)
if ($id_menu > 0 && $jumlah_beli > 0) {
    // Inisialisasi array keranjang jika belum ada di sesi
    if (!isset($_SESSION['keranjang'])) {
        $_SESSION['keranjang'] = [];
    }
    // Jika item sudah ada di keranjang, tambahkan jumlahnya
    if (isset($_SESSION['keranjang'][$id_menu])) {
        $_SESSION['keranjang'][$id_menu] += $jumlah_beli;
    } else {
        // Jika belum ada, masukkan sebagai item baru
        $_SESSION['keranjang'][$id_menu] = $jumlah_beli;
    }
}

// Menghitung total jumlah barang di keranjang
$cart_count = 0;
foreach ($_SESSION['keranjang'] ?? [] as $qty) {
    $cart_count += (int) $qty;
}

// Memeriksa apakah request berasal dari AJAX
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Jika AJAX, kembalikan response JSON
if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'cart_count' => $cart_count]);
    exit;
}

// Jika bukan AJAX, redirect ke halaman keranjang
header("Location: keranjang.php");
exit;
