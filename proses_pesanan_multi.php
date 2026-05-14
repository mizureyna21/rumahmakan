<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['keranjang'])) {
    header('Location: index.php');
    exit;
}

$nama_pelanggan = trim(htmlspecialchars($_POST['nama_pelanggan'] ?? ''));
$no_telepon = trim(htmlspecialchars($_POST['no_telepon'] ?? ''));
$alamat = trim(htmlspecialchars($_POST['alamat'] ?? ''));
$metode_pembayaran = trim($_POST['metode_pembayaran'] ?? '');
$grand_total = 0;
$total_item = 0;
$id_pesanan = null;
$db_error_msg = null;
$errors = [];

if ($nama_pelanggan === '') {
    $errors[] = 'Nama pelanggan tidak boleh kosong.';
}
if ($no_telepon === '') {
    $errors[] = 'Nomor telepon tidak boleh kosong.';
}
if ($alamat === '') {
    $errors[] = 'Alamat tidak boleh kosong.';
}

// ── Validasi metode pembayaran ───────────────────────────────
$metode_valid = ['Transfer Bank', 'DANA', 'GoPay', 'OVO', 'ShopeePay', 'QRIS', 'COD'];
if (!in_array($metode_pembayaran, $metode_valid, true)) {
    $errors[] = 'Metode pembayaran tidak valid.';
}

// ── Validasi & proses upload bukti transfer ──────────────────
$bukti_filename = null;

if ($metode_pembayaran !== 'COD') {
    if (!isset($_FILES['bukti_transfer']) || $_FILES['bukti_transfer']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Bukti transfer wajib diunggah.';
    } else {
        $file = $_FILES['bukti_transfer'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png'];

        if (!in_array($ext, $allowed_ext, true)) {
            $errors[] = 'Format file bukti harus JPG, JPEG, atau PNG.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran file bukti melebihi batas 2MB.';
        } else {
            $upload_dir = 'img/bukti/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $bukti_filename = 'bukti_' . uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $upload_path = $upload_dir . $bukti_filename;

            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                $errors[] = 'Gagal menyimpan file bukti transfer.';
                $bukti_filename = null;
            }
        }
    }
}

// Jika ada error validasi, tampilkan pesan sederhana
if (!empty($errors)) {
    die('Validasi gagal: ' . implode(' | ', $errors));
}

$ids = array_map('intval', array_keys($_SESSION['keranjang']));
$ids_str = implode(',', $ids);
$res_menu = mysqli_query($koneksi, "SELECT id_menu, harga FROM menu WHERE id_menu IN ($ids_str)");

while ($row = mysqli_fetch_assoc($res_menu)) {
    $qty = (int) $_SESSION['keranjang'][$row['id_menu']];
    $grand_total += ($row['harga'] * $qty);
    $total_item += $qty;
}

$stmt1 = mysqli_prepare($koneksi, "INSERT INTO pesanan (nama_pelanggan, no_telepon, alamat, total_harga, metode_pembayaran, bukti_transfer) VALUES (?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt1, 'sssiss', $nama_pelanggan, $no_telepon, $alamat, $grand_total, $metode_pembayaran, $bukti_filename);

if (mysqli_stmt_execute($stmt1)) {
    $id_pesanan = mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmt1);

    $stmt2 = mysqli_prepare($koneksi, "INSERT INTO detail_pesanan (id_pesanan, id_menu, jumlah_beli) VALUES (?, ?, ?)");
    foreach ($_SESSION['keranjang'] as $id_menu => $qty) {
        mysqli_stmt_bind_param($stmt2, 'iii', $id_pesanan, $id_menu, $qty);
        mysqli_stmt_execute($stmt2);
    }
    mysqli_stmt_close($stmt2);

    unset($_SESSION['keranjang']);
    header('Location: struk.php?id=' . $id_pesanan);
    exit;
} else {
    $db_error_msg = mysqli_error($koneksi);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil – Rumah Makan Sipatuo Jr.</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --clr-primary: #e8622a;
            --clr-success: #16a34a;
            --clr-bg: #faf8f5;
            --clr-text: #1a1a2e;
            --clr-text-muted: #6b7280;
            --radius-lg: 24px;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--clr-bg);
            color: var(--clr-text);
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .navbar {
            background: #fff;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .navbar__inner {
            max-width: 860px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background: var(--clr-primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .stage {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .result-card {
            background: #fff;
            width: 100%;
            max-width: 500px;
            border-radius: var(--radius-lg);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            text-align: center;
        }

        .strip {
            height: 6px;
            background: #22c55e;
        }

        .body {
            padding: 3rem 2rem;
        }

        .icon-check {
            width: 70px;
            height: 70px;
            background: #dcfce7;
            color: #16a34a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--clr-success);
            margin-bottom: 0.5rem;
        }

        .subtitle {
            font-size: 0.9rem;
            color: var(--clr-text-muted);
            margin-bottom: 2rem;
            line-height: 1.5;
        }

        .detail-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 1.25rem;
            text-align: left;
            margin-bottom: 1.5rem;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 0.85rem;
        }

        .row:last-child {
            margin-bottom: 0;
            padding-top: 0.75rem;
            border-top: 1px solid #bbf7d0;
        }

        .label {
            color: var(--clr-text-muted);
            font-weight: 600;
        }

        .val {
            font-weight: 700;
        }

        .total-val {
            color: var(--clr-success);
            font-size: 1rem;
        }

        .time {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-bottom: 2rem;
            display: block;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 1rem;
            background: #16a34a;
            color: #fff;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.95rem;
            transition: 0.2s;
        }

        .btn:hover {
            background: #15803d;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>

    <header class="navbar">
        <div class="navbar__inner">
            <div class="logo-icon">🍽️</div>
            <div style="font-weight: 800; font-size: 1.1rem;">Rumah Makan <span
                    style="color:var(--clr-primary)">Sipatuo Jr.</span></div>
        </div>
    </header>

    <main class="stage">
        <div class="result-card">
            <div class="strip"></div>
            <div class="body">
                <div class="icon-check">✅</div>
                <h1>Pesanan Berhasil Dibuat!</h1>
                <p class="subtitle">Terima kasih, <strong><?= htmlspecialchars($nama_pelanggan) ?></strong>! Pesanan
                    Anda telah kami terima dan sedang diproses.</p>

                <div class="detail-box">
                    <div class="row">
                        <span class="label">No. Pesanan</span>
                        <span class="val">#<?= str_pad($id_pesanan, 5, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Nama Pelanggan</span>
                        <span class="val"><?= htmlspecialchars($nama_pelanggan) ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Nomor Telepon</span>
                        <span class="val"><?= htmlspecialchars($no_telepon) ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Alamat</span>
                        <span class="val"><?= htmlspecialchars($alamat) ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Jumlah Pesanan</span>
                        <span class="val"><?= $total_item ?> porsi</span>
                    </div>
                    <div class="row">
                        <span class="label">Total Pembayaran</span>
                        <span class="val total-val">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
                    </div>
                </div>

                <span class="time">📅 <?= date('d F Y, H:i') ?> WIB</span>
                <a href="index.php" class="btn">🏠 Kembali ke Beranda</a>
            </div>
        </div>
    </main>

</body>

</html>

<?php mysqli_close($koneksi); ?>