<?php
// ============================================================
//  proses_pesanan.php — Backend Processor Pesanan
//  Rumah Makan Sipatuo Jr. | Pemrograman Web – Semester 4
// ============================================================
include 'koneksi.php';

// ── 0. Tolak akses langsung (bukan dari POST) ────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// ── 1. Ambil & sanitasi data dari form checkout.php ──────────
$nama_pelanggan = trim(htmlspecialchars($_POST['nama_pelanggan'] ?? ''));
$no_telepon = trim(htmlspecialchars($_POST['no_telepon'] ?? ''));
$alamat = trim(htmlspecialchars($_POST['alamat'] ?? ''));
$id_menu = (int) ($_POST['id_menu'] ?? 0);
$jumlah_beli = (int) ($_POST['jumlah_beli'] ?? 0);
$total_harga = (int) ($_POST['total_harga'] ?? 0);
$metode_pembayaran = trim($_POST['metode_pembayaran'] ?? '');

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
if ($id_menu <= 0) {
    $errors[] = 'Menu yang dipilih tidak valid.';
}
if ($jumlah_beli <= 0) {
    $errors[] = 'Jumlah pesanan harus lebih dari 0.';
}
if ($total_harga <= 0) {
    $errors[] = 'Total harga tidak valid.';
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
            // Buat folder jika belum ada
            $upload_dir = 'img/bukti/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            // Nama file unik: uniqid + random
            $bukti_filename = 'bukti_' . uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $upload_path = $upload_dir . $bukti_filename;

            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                $errors[] = 'Gagal menyimpan file bukti transfer.';
                $bukti_filename = null;
            }
        }
    }
}

// ── 2. Proses INSERT jika validasi lolos ─────────────────────
$success = false;
$id_pesanan = null;
$db_error_msg = null;

if (empty($errors)) {

    // ── STEP 1: INSERT ke tabel `pesanan` ────────────────────
    // Schema: id_pesanan (AUTO), nama_pelanggan, total_harga, metode_pembayaran, bukti_transfer, tanggal_pesanan (DEFAULT NOW())
    $stmt1 = mysqli_prepare(
        $koneksi,
        "INSERT INTO pesanan (nama_pelanggan, no_telepon, alamat, total_harga, metode_pembayaran, bukti_transfer) VALUES (?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt1) {
        $db_error_msg = 'Gagal menyiapkan query pesanan: ' . mysqli_error($koneksi);
    } else {
        mysqli_stmt_bind_param($stmt1, 'sssiss', $nama_pelanggan, $no_telepon, $alamat, $total_harga, $metode_pembayaran, $bukti_filename);

        if (mysqli_stmt_execute($stmt1)) {

            // ── STEP 2: Ambil ID pesanan yang baru dibuat ────
            $id_pesanan = mysqli_insert_id($koneksi);
            mysqli_stmt_close($stmt1);

            // ── STEP 3: INSERT ke tabel `detail_pesanan` ─────
            // Schema: id_detail (AUTO), id_pesanan (FK), id_menu (FK), jumlah_beli
            $stmt2 = mysqli_prepare(
                $koneksi,
                "INSERT INTO detail_pesanan (id_pesanan, id_menu, jumlah_beli) VALUES (?, ?, ?)"
            );

            if (!$stmt2) {
                $db_error_msg = 'Gagal menyiapkan query detail pesanan: ' . mysqli_error($koneksi);
            } else {
                mysqli_stmt_bind_param($stmt2, 'iii', $id_pesanan, $id_menu, $jumlah_beli);

                if (mysqli_stmt_execute($stmt2)) {
                    $success = true;
                    header('Location: struk.php?id=' . $id_pesanan);
                    exit;
                } else {
                    $db_error_msg = 'Gagal menyimpan detail pesanan: ' . mysqli_stmt_error($stmt2);
                    // Rollback manual: hapus pesanan yang sudah masuk agar data tidak orphan
                    $rollback = mysqli_prepare($koneksi, "DELETE FROM pesanan WHERE id_pesanan = ?");
                    if ($rollback) {
                        mysqli_stmt_bind_param($rollback, 'i', $id_pesanan);
                        mysqli_stmt_execute($rollback);
                        mysqli_stmt_close($rollback);
                    }
                }

                mysqli_stmt_close($stmt2);
            }

        } else {
            $db_error_msg = 'Gagal menyimpan pesanan: ' . mysqli_stmt_error($stmt1);
            mysqli_stmt_close($stmt1);
        }
    }
}

// ── Format total harga untuk tampilan ───────────────────────
$total_formatted = 'Rp ' . number_format($total_harga, 0, ',', '.');

// Tutup koneksi
if (isset($koneksi)) {
    mysqli_close($koneksi);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Status pemrosesan pesanan Rumah Makan Sipatuo Jr..">
    <title>
        <?= $success ? 'Pesanan Berhasil – Rumah Makan Sipatuo Jr.' : 'Terjadi Kesalahan – Rumah Makan Sipatuo Jr.' ?>
    </title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        /* ══════════════════════════════════════════════
           DESIGN TOKENS (identik dengan file lain)
        ══════════════════════════════════════════════ */
        :root {
            --clr-primary: #e8622a;
            --clr-primary-dark: #c94f1a;
            --clr-primary-light: #fdf0eb;
            --clr-success: #16a34a;
            --clr-success-dark: #15803d;
            --clr-success-light: #f0fdf4;
            --clr-success-mid: #dcfce7;
            --clr-error: #dc2626;
            --clr-error-light: #fef2f2;
            --clr-warning: #d97706;
            --clr-warning-light: #fffbeb;
            --clr-bg: #faf8f5;
            --clr-surface: #ffffff;
            --clr-text: #1a1a2e;
            --clr-text-muted: #6b7280;
            --clr-border: #e5e7eb;

            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 24px;
            --shadow-card: 0 4px 12px rgba(0, 0, 0, .07), 0 16px 40px rgba(0, 0, 0, .07);
            --transition: .22s cubic-bezier(.4, 0, .2, 1);
        }

        /* ══════════════════════════════════════════════
           RESET & BASE
        ══════════════════════════════════════════════ */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: var(--clr-bg);
            color: var(--clr-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.6;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* ══════════════════════════════════════════════
           NAVBAR
        ══════════════════════════════════════════════ */
        .navbar {
            background: rgba(255, 255, 255, .88);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--clr-border);
            padding: 0 1.5rem;
        }

        .navbar__inner {
            max-width: 860px;
            margin: 0 auto;
            height: 64px;
            display: flex;
            align-items: center;
            gap: .65rem;
        }

        .navbar__logo-icon {
            width: 38px;
            height: 38px;
            background: var(--clr-primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .navbar__title {
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: -.3px;
        }

        .navbar__title span {
            color: var(--clr-primary);
        }

        /* ══════════════════════════════════════════════
           CENTERED STAGE
        ══════════════════════════════════════════════ */
        .stage {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.5rem;
        }

        /* ══════════════════════════════════════════════
           SUCCESS CARD
        ══════════════════════════════════════════════ */
        .result-card {
            background: var(--clr-surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            width: 100%;
            max-width: 520px;
            overflow: hidden;
            animation: cardIn .45s cubic-bezier(.34, 1.56, .64, 1) both;
        }

        @keyframes cardIn {
            from {
                opacity: 0;
                transform: translateY(28px) scale(.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* ── Header strip ───────────────────────────── */
        .result-card__strip {
            height: 6px;
            width: 100%;
        }

        .strip--success {
            background: linear-gradient(90deg, #16a34a, #22c55e, #4ade80);
        }

        .strip--error {
            background: linear-gradient(90deg, #dc2626, #ef4444, #fca5a5);
        }

        .strip--warning {
            background: linear-gradient(90deg, #d97706, #f59e0b, #fcd34d);
        }

        /* ── Card body ──────────────────────────────── */
        .result-card__body {
            padding: 2.5rem 2rem 2rem;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .6rem;
        }

        /* Icon circle */
        .result-icon-wrap {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.4rem;
            margin-bottom: .5rem;
            animation: iconBounce .5s .2s cubic-bezier(.34, 1.56, .64, 1) both;
        }

        @keyframes iconBounce {
            from {
                opacity: 0;
                transform: scale(.4);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .icon-wrap--success {
            background: var(--clr-success-mid);
        }

        .icon-wrap--error {
            background: #fee2e2;
        }

        .icon-wrap--warning {
            background: #fef3c7;
        }

        /* Title */
        .result-title {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -.4px;
        }

        .title--success {
            color: var(--clr-success);
        }

        .title--error {
            color: var(--clr-error);
        }

        .title--warning {
            color: var(--clr-warning);
        }

        .result-subtitle {
            font-size: .92rem;
            color: var(--clr-text-muted);
            max-width: 340px;
            line-height: 1.55;
        }

        /* ── Detail rows (success) ──────────────────── */
        .detail-box {
            width: 100%;
            background: var(--clr-success-light);
            border: 1px solid #bbf7d0;
            border-radius: var(--radius-md);
            padding: 1rem 1.2rem;
            margin-top: .5rem;
            display: flex;
            flex-direction: column;
            gap: .55rem;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            font-size: .85rem;
        }

        .detail-row__label {
            color: var(--clr-text-muted);
            font-weight: 600;
        }

        .detail-row__value {
            font-weight: 700;
            color: var(--clr-text);
            text-align: right;
        }

        .detail-row__value.total {
            color: var(--clr-success);
            font-size: 1rem;
        }

        .detail-divider {
            height: 1px;
            background: #bbf7d0;
        }

        /* ── Error detail box ───────────────────────── */
        .error-box {
            width: 100%;
            background: var(--clr-error-light);
            border: 1px solid #fecaca;
            border-radius: var(--radius-md);
            padding: 1rem 1.2rem;
            margin-top: .5rem;
            text-align: left;
        }

        .error-box__label {
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--clr-error);
            margin-bottom: .35rem;
        }

        .error-box__msg {
            font-size: .82rem;
            color: #7f1d1d;
            font-family: 'Courier New', monospace;
            word-break: break-all;
            line-height: 1.5;
        }

        /* ── Validation errors list ─────────────────── */
        .validation-list {
            width: 100%;
            background: var(--clr-warning-light);
            border: 1px solid #fde68a;
            border-radius: var(--radius-md);
            padding: .85rem 1.1rem;
            margin-top: .5rem;
            text-align: left;
        }

        .validation-list ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }

        .validation-list ul li {
            font-size: .85rem;
            color: #92400e;
            font-weight: 600;
            display: flex;
            align-items: flex-start;
            gap: .4rem;
        }

        .validation-list ul li::before {
            content: '›';
            font-size: 1rem;
            color: var(--clr-warning);
            flex-shrink: 0;
            margin-top: -.05rem;
        }

        /* ── Action buttons ─────────────────────────── */
        .action-group {
            display: flex;
            flex-direction: column;
            gap: .7rem;
            width: 100%;
            margin-top: .75rem;
        }

        .btn-primary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            width: 100%;
            padding: .85rem 1.5rem;
            background: var(--clr-success);
            color: #fff;
            font-family: inherit;
            font-size: .95rem;
            font-weight: 800;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: background var(--transition), transform var(--transition);
            text-decoration: none;
        }

        .btn-primary:hover {
            background: var(--clr-success-dark);
            transform: translateY(-2px);
        }

        .btn-primary.orange {
            background: var(--clr-primary);
        }

        .btn-primary.orange:hover {
            background: var(--clr-primary-dark);
        }

        .btn-secondary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            width: 100%;
            padding: .75rem 1.5rem;
            background: transparent;
            color: var(--clr-text-muted);
            font-family: inherit;
            font-size: .875rem;
            font-weight: 600;
            border: 1.5px solid var(--clr-border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: border-color var(--transition), color var(--transition), background var(--transition);
            text-decoration: none;
        }

        .btn-secondary:hover {
            border-color: var(--clr-primary);
            color: var(--clr-primary-dark);
            background: var(--clr-primary-light);
        }

        /* ── Timestamp note ─────────────────────────── */
        .timestamp-note {
            font-size: .72rem;
            color: #9ca3af;
            margin-top: .25rem;
        }

        /* ══════════════════════════════════════════════
           FOOTER
        ══════════════════════════════════════════════ */
        .footer {
            background: var(--clr-text);
            color: rgba(255, 255, 255, .5);
            text-align: center;
            padding: 1.4rem;
            font-size: .8rem;
        }

        .footer strong {
            color: #fff;
        }

        /* ══════════════════════════════════════════════
           RESPONSIVE
        ══════════════════════════════════════════════ */
        @media (max-width: 520px) {
            .result-card__body {
                padding: 2rem 1.25rem 1.5rem;
            }

            .result-title {
                font-size: 1.25rem;
            }
        }
    </style>
</head>

<body>

    <!-- ══════════════════════════════════════════════════════════
     NAVBAR
══════════════════════════════════════════════════════════ -->
    <header>
        <nav class="navbar" aria-label="Navigasi utama">
            <div class="navbar__inner">
                <a href="index.php" class="navbar__brand" id="nav-brand"
                    style="display:flex;align-items:center;gap:.65rem;">
                    <div class="navbar__logo-icon" aria-hidden="true">🍽️</div>
                    <span class="navbar__title">Rumah Makan <span>Sipatuo Jr.</span></span>
                </a>
            </div>
        </nav>
    </header>

    <!-- ══════════════════════════════════════════════════════════
     RESULT STAGE
══════════════════════════════════════════════════════════ -->
    <main class="stage" id="result-stage">

        <?php if (!empty($errors)): ?>
            <!-- ══════════════════════════════════════════
         STATE: VALIDASI GAGAL (data tidak lengkap)
    ══════════════════════════════════════════ -->
            <div class="result-card" id="card-validation-error" role="alert">
                <div class="result-card__strip strip--warning"></div>
                <div class="result-card__body">

                    <div class="result-icon-wrap icon-wrap--warning" aria-hidden="true">⚠️</div>

                    <h1 class="result-title title--warning">Data Tidak Lengkap</h1>
                    <p class="result-subtitle">
                        Terdapat kesalahan pada data yang dikirimkan. Silakan periksa kembali isian Anda.
                    </p>

                    <!-- Daftar error validasi -->
                    <div class="validation-list">
                        <ul>
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="action-group">
                        <a href="javascript:history.back()" class="btn-primary orange" id="btn-kembali-validasi">
                            ← Kembali & Perbaiki
                        </a>
                        <a href="index.php" class="btn-secondary" id="btn-ke-beranda-validasi">
                            🏠 Kembali ke Beranda
                        </a>
                    </div>

                </div>
            </div>

        <?php elseif ($success): ?>
            <!-- ══════════════════════════════════════════
         STATE: BERHASIL ✓
    ══════════════════════════════════════════ -->
            <div class="result-card" id="card-success" role="status">
                <div class="result-card__strip strip--success"></div>
                <div class="result-card__body">

                    <div class="result-icon-wrap icon-wrap--success" aria-hidden="true">✅</div>

                    <h1 class="result-title title--success">Pesanan Berhasil Dibuat!</h1>
                    <p class="result-subtitle">
                        Terima kasih, <strong><?= htmlspecialchars($nama_pelanggan) ?></strong>! Pesanan Anda telah kami
                        terima dan sedang diproses.
                    </p>

                    <!-- Ringkasan pesanan -->
                    <div class="detail-box" id="success-detail" aria-label="Ringkasan pesanan">
                        <div class="detail-row">
                            <span class="detail-row__label">No. Pesanan</span>
                            <span class="detail-row__value">#<?= str_pad($id_pesanan, 5, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-row__label">Nama Pelanggan</span>
                            <span class="detail-row__value"><?= htmlspecialchars($nama_pelanggan) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-row__label">No. Telepon</span>
                            <span class="detail-row__value"><?= htmlspecialchars($no_telepon) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-row__label">Alamat</span>
                            <span class="detail-row__value"
                                style="text-align:right; max-width:200px;"><?= htmlspecialchars($alamat) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-row__label">Jumlah Pesanan</span>
                            <span class="detail-row__value"><?= $jumlah_beli ?> porsi</span>
                        </div>
                        <div class="detail-divider" aria-hidden="true"></div>
                        <div class="detail-row">
                            <span class="detail-row__label">Total Pembayaran</span>
                            <span class="detail-row__value total"><?= $total_formatted ?></span>
                        </div>
                    </div>

                    <p class="timestamp-note">
                        🕐 <?= date('d F Y, H:i') ?> WIB
                    </p>

                    <div class="action-group">
                        <a href="index.php" class="btn-primary" id="btn-kembali-beranda">
                            🏠 Kembali ke Beranda
                        </a>
                    </div>

                </div>
            </div>

        <?php else: ?>
            <!-- ══════════════════════════════════════════
         STATE: GAGAL DATABASE
    ══════════════════════════════════════════ -->
            <div class="result-card" id="card-db-error" role="alert">
                <div class="result-card__strip strip--error"></div>
                <div class="result-card__body">

                    <div class="result-icon-wrap icon-wrap--error" aria-hidden="true">❌</div>

                    <h1 class="result-title title--error">Pesanan Gagal Disimpan</h1>
                    <p class="result-subtitle">
                        Terjadi kesalahan pada database saat menyimpan pesanan Anda. Silakan coba lagi atau hubungi
                        pengelola.
                    </p>

                    <!-- Detail error untuk debugging -->
                    <div class="error-box" id="error-detail" aria-label="Detail kesalahan database">
                        <p class="error-box__label">🔧 Detail Error (untuk debugging)</p>
                        <p class="error-box__msg"><?= htmlspecialchars($db_error_msg ?? 'Kesalahan tidak diketahui.') ?></p>
                    </div>

                    <div class="action-group">
                        <a href="javascript:history.back()" class="btn-primary orange" id="btn-coba-lagi">
                            ↩ Coba Lagi
                        </a>
                        <a href="index.php" class="btn-secondary" id="btn-ke-beranda-error">
                            🏠 Kembali ke Beranda
                        </a>
                    </div>

                </div>
            </div>

        <?php endif; ?>

    </main>

    <!-- ══════════════════════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════════════════ -->
    <footer class="footer">
        <p>&copy; <?= date('Y') ?> <strong>Rumah Makan Sipatuo Jr.</strong>. Dibuat untuk tugas UMKM – Pemrograman Web
            Semester 4.</p>
    </footer>

</body>

</html>