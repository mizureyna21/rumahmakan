<?php
// ============================================================
//  checkout.php — Halaman Ringkasan & Konfirmasi Pesanan
//  Rumah Makan Sipatuo Jr. | Pemrograman Web – Semester 4
// ============================================================
include 'koneksi.php';

// ── Ambil data dari POST (diutamakan) atau GET ───────────────
// Mengambil ID menu dan jumlah beli dari request, fallback ke 0 jika tidak ada
$id_menu = isset($_POST['id_menu']) ? (int) $_POST['id_menu'] :
    (isset($_GET['id_menu']) ? (int) $_GET['id_menu'] : 0);
$jumlah_beli = isset($_POST['jumlah_beli']) ? (int) $_POST['jumlah_beli'] :
    (isset($_GET['jumlah_beli']) ? (int) $_GET['jumlah_beli'] : 0);

// Normalkan: jumlah minimal 1 jika id menu valid
if ($id_menu > 0 && $jumlah_beli < 1) {
    $jumlah_beli = 1;
}

// ── Cek apakah ada data pesanan ──────────────────────────────
$has_order = ($id_menu > 0 && $jumlah_beli > 0);
$menu = null;
$total_harga = 0;
$db_error = null;

if ($has_order) {
    // Query HANYA menu yang dipilih (gunakan prepared statement untuk keamanan)
    $stmt = mysqli_prepare($koneksi, "SELECT id_menu, nama_menu, kategori, harga, foto FROM menu WHERE id_menu = ?");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id_menu);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $menu = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$menu) {
            // id_menu tidak ditemukan di database
            $has_order = false;
            $db_error = "Menu dengan ID yang dipilih tidak ditemukan di database.";
        } else {
            // Menghitung total harga berdasarkan harga menu dan jumlah beli
            $total_harga = $menu['harga'] * $jumlah_beli;
        }
    } else {
        $has_order = false;
        $db_error = "Terjadi kesalahan pada persiapan query: " . mysqli_error($koneksi);
    }
}

// ── Format harga sebagai Rupiah ──────────────────────────────
// Fungsi pembantu untuk memformat angka menjadi format Rupiah
function rupiah(int $angka): string
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Halaman konfirmasi pesanan Rumah Makan Sipatuo Jr.. Periksa pesanan Anda sebelum melakukan pembayaran.">
    <title>Konfirmasi Pesanan – Rumah Makan Sipatuo Jr</title>

    <!-- Google Fonts (sama dengan index.php) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --air-primary: #ff385c;
            --air-primary-active: #e00b41;
            --air-primary-disabled: #ffd1da;
            --air-ink: #222222;
            --air-body: #3f3f3f;
            --air-muted: #6a6a6a;
            --air-muted-soft: #929292;
            --air-hairline: #dddddd;
            --air-hairline-soft: #ebebeb;
            --air-border-strong: #c1c1c1;
            --air-canvas: #ffffff;
            --air-surface-soft: #f7f7f7;
            --air-surface-strong: #f2f2f2;
            --air-on-primary: #ffffff;
            --air-error-text: #c13515;
            --air-radius-xs: 4px;
            --air-radius-sm: 8px;
            --air-radius-md: 14px;
            --air-radius-lg: 20px;
            --air-radius-full: 9999px;
            --air-space-xs: 4px;
            --air-space-sm: 8px;
            --air-space-md: 12px;
            --air-space-base: 16px;
            --air-space-lg: 24px;
            --air-space-xl: 32px;
            --air-space-xxl: 48px;
            --air-shadow-hover: rgba(0,0,0,0.02) 0 0 0 1px, rgba(0,0,0,0.04) 0 2px 6px, rgba(0,0,0,0.1) 0 4px 8px;
            --air-transition: .2s cubic-bezier(.4,0,.2,1);
        }

        *,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, system-ui, Roboto, 'Helvetica Neue', sans-serif;
            background: var(--air-surface-soft);
            color: var(--air-ink);
            min-height: 100vh; display: flex; flex-direction: column;
            line-height: 1.6; -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }
        img { display: block; max-width: 100%; }

        /* ── Navbar ── */
        .navbar {
            position: sticky; top: 0; z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--air-hairline);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 0 var(--air-space-lg); height: 80px;
        }
        .navbar__inner {
            max-width: 860px; margin: 0 auto; height: 100%;
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        }
        .navbar__brand { display: flex; align-items: center; gap: .65rem; }
        .navbar__logo-icon { width: 38px; height: 38px; border-radius: var(--air-radius-sm); object-fit: contain; flex-shrink: 0; }
        .navbar__title { font-size: 1.05rem; font-weight: 600; letter-spacing: -.3px; }
        .navbar__title span { color: var(--air-primary); }
        .navbar__back-btn {
            display: flex; align-items: center; gap: .45rem;
            padding: .5rem 1.1rem; background: transparent; color: var(--air-ink);
            font-family: inherit; font-size: .875rem; font-weight: 500;
            border: 1px solid var(--air-hairline); border-radius: var(--air-radius-sm);
            cursor: pointer; transition: border-color var(--air-transition);
        }
        .navbar__back-btn:hover { border-color: var(--air-ink); }

        /* ── Page wrapper ── */
        .page-wrapper {
            flex: 1; max-width: 860px; width: 100%;
            margin: var(--air-space-xl) auto var(--air-space-xxl);
            padding: 0 var(--air-space-lg);
        }

        /* ── Step indicator ── */
        .step-indicator {
            display: flex; align-items: center; gap: .5rem;
            font-size: .78rem; font-weight: 500;
            color: var(--air-muted); margin-bottom: var(--air-space-lg);
        }
        .step-indicator .step {
            padding: .25rem .8rem; border-radius: var(--air-radius-full);
            border: 1px solid var(--air-hairline);
        }
        .step-indicator .step.active {
            background: var(--air-primary); border-color: var(--air-primary);
            color: var(--air-on-primary);
        }
        .step-indicator .sep { font-size: .9rem; color: var(--air-hairline); }

        .page-title { font-size: 1.5rem; font-weight: 600; letter-spacing: -.4px; margin-bottom: .35rem; }
        .page-subtitle { font-size: .92rem; color: var(--air-muted); margin-bottom: var(--air-space-xl); }

        /* ── State cards ── */
        .state-card {
            background: var(--air-canvas);
            border: 2px dashed var(--air-hairline);
            border-radius: var(--air-radius-lg); padding: 4rem 2rem; text-align: center;
        }
        .state-card.error { border-color: #fecaca; background: #fef2f2; }
        .state-icon { font-size: 3.5rem; margin-bottom: 1rem; }
        .state-card h2 { font-size: 1.15rem; font-weight: 600; margin-bottom: .45rem; color: var(--air-ink); }
        .state-card p { color: var(--air-muted); font-size: .92rem; max-width: 380px; margin: 0 auto 1.5rem; }
        .state-card.error p { color: #dc2626; }
        .btn-back-catalog {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .7rem 1.6rem; background: var(--air-primary); color: var(--air-on-primary);
            font-family: inherit; font-size: .9rem; font-weight: 500;
            border: none; border-radius: var(--air-radius-sm);
            cursor: pointer; transition: background var(--air-transition);
            height: 48px;
        }
        .btn-back-catalog:hover { background: var(--air-primary-active); }

        /* ── Layout ── */
        .checkout-layout {
            display: grid; grid-template-columns: 1fr 1fr; gap: var(--air-space-xl); align-items: start;
        }
        @media (max-width: 680px) { .checkout-layout { grid-template-columns: 1fr; } }
        .col-left { display: flex; flex-direction: column; gap: var(--air-space-xl); }

        /* ── Summary card ── */
        .summary-card {
            background: var(--air-canvas);
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-md); overflow: hidden;
        }
        .summary-card__header {
            padding: 1.15rem 1.4rem;
            display: flex; align-items: center; gap: .6rem;
            border-bottom: 1px solid var(--air-hairline);
        }
        .summary-card__header-icon { font-size: 1.2rem; }
        .summary-card__header h2 { font-size: 1rem; font-weight: 600; color: var(--air-ink); letter-spacing: -.2px; }
        .summary-card__img { width: 100%; aspect-ratio: 16/7; object-fit: cover; background: var(--air-surface-soft); }
        .summary-card__img-placeholder {
            width: 100%; aspect-ratio: 16/7;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            background: var(--air-surface-soft); color: var(--air-muted-soft);
            font-size: .8rem; font-weight: 500; gap: .35rem;
        }
        .summary-card__img-placeholder .ph-icon { font-size: 2.2rem; }
        .summary-card__body { padding: var(--air-space-lg) 1.4rem; display: flex; flex-direction: column; gap: .75rem; }
        .summary-row { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; }
        .summary-row__label { font-size: .75rem; font-weight: 600; color: var(--air-muted); text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
        .summary-row__value { font-size: .92rem; font-weight: 600; color: var(--air-ink); text-align: right; }
        .summary-row__value.nama { font-size: 1rem; }
        .summary-row__value.harga { color: var(--air-primary); font-size: 1.05rem; }
        .summary-divider { height: 1px; background: var(--air-hairline-soft); }
        .summary-total {
            background: var(--air-surface-soft); border-radius: var(--air-radius-sm);
            padding: .85rem 1rem; display: flex; justify-content: space-between; align-items: center;
        }
        .summary-total__label { font-size: .8rem; font-weight: 600; color: var(--air-ink); text-transform: uppercase; letter-spacing: .05em; }
        .summary-total__value { font-size: 1.25rem; font-weight: 600; color: var(--air-primary); letter-spacing: -.4px; }
        .kategori-chip { display: inline-flex; align-items: center; gap: .25rem; font-size: .7rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; padding: .2rem .6rem; border-radius: var(--air-radius-full); }
        .chip--makanan { background: #fff7ed; color: #c2410c; }
        .chip--minuman { background: #eff6ff; color: #1d4ed8; }
        .chip--default { background: #f0fdf4; color: #065f46; }

        /* ── Form card ── */
        .form-card {
            background: var(--air-canvas);
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-md); overflow: hidden;
        }
        .form-card__header {
            padding: 1.15rem 1.4rem;
            display: flex; align-items: center; gap: .6rem;
            border-bottom: 1px solid var(--air-hairline);
        }
        .form-card__header-icon { font-size: 1.2rem; }
        .form-card__header h2 { font-size: 1rem; font-weight: 600; color: var(--air-ink); letter-spacing: -.2px; }
        .form-card__body { padding: 1.4rem; }

        .field { margin-bottom: var(--air-space-base); }
        .field label { display: block; font-size: .78rem; font-weight: 600; color: var(--air-muted); text-transform: uppercase; letter-spacing: .04em; margin-bottom: .4rem; }
        .field label .required-star { color: var(--air-error-text); margin-left: 2px; }
        .field input[type="text"], .field input[type="number"], .field input[type="tel"], .field textarea {
            width: 100%; padding: .7rem 1rem;
            border: 1px solid var(--air-hairline); border-radius: var(--air-radius-sm);
            font-family: inherit; font-size: .92rem; font-weight: 500;
            color: var(--air-ink); background: var(--air-canvas);
            transition: border-color var(--air-transition); outline: none;
        }
        .field input:focus, .field textarea:focus {
            border-color: var(--air-ink); border-width: 2px;
        }
        .field input::placeholder, .field textarea::placeholder { color: var(--air-muted-soft); font-weight: 400; }

        .btn-konfirmasi {
            width: 100%; padding: .8rem 1.5rem;
            background: var(--air-primary); color: var(--air-on-primary);
            font-family: inherit; font-size: 1rem; font-weight: 500;
            border: none; border-radius: var(--air-radius-sm);
            cursor: pointer; transition: background var(--air-transition);
            display: flex; align-items: center; justify-content: center; gap: .55rem;
            height: 48px; margin-top: var(--air-space-base);
        }
        .btn-konfirmasi:hover { background: var(--air-primary-active); }
        .btn-konfirmasi .btn-icon { font-size: 1.1rem; }
        .form-disclaimer { margin-top: .75rem; font-size: .75rem; color: var(--air-muted); text-align: center; display: flex; align-items: center; justify-content: center; gap: .35rem; }

        /* ── Payment Accordion ── */
        .payment-accordion {
            background: var(--air-canvas);
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-md); overflow: hidden; margin-bottom: 1rem;
        }
        .accordion-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1rem 1.25rem; cursor: pointer; user-select: none;
            background: var(--air-surface-soft); transition: background var(--air-transition);
        }
        .accordion-header:hover { background: var(--air-hairline-soft); }
        .accordion-title { font-size: .92rem; font-weight: 600; color: var(--air-ink); }
        .accordion-right { display: flex; align-items: center; gap: .75rem; }
        .accordion-logos { display: flex; align-items: center; gap: .4rem; }
        .accordion-logos img { width: 22px; height: 22px; object-fit: contain; border-radius: var(--air-radius-xs); }
        .accordion-chevron { font-size: .7rem; color: var(--air-muted); transition: transform .3s ease; }
        .accordion-chevron.open { transform: rotate(180deg); }
        .accordion-body { max-height: 0; overflow: hidden; transition: max-height .35s ease, padding .35s ease; padding: 0 1.25rem; }
        .accordion-body.open { max-height: 600px; padding: 1.25rem 1.5rem 1.5rem; }

        .payment-card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px,1fr)); gap: var(--air-space-sm); }
        .payment-card {
            position: relative; display: flex; flex-direction: column; align-items: center; gap: .5rem;
            padding: 1rem .75rem .75rem; background: var(--air-canvas);
            border: 1px solid var(--air-hairline); border-radius: var(--air-radius-md);
            cursor: pointer; transition: box-shadow var(--air-transition), border-color var(--air-transition);
        }
        .payment-card:hover { box-shadow: var(--air-shadow-hover); }
        .payment-card.selected { border-color: var(--air-ink); border-width: 2px; background: var(--air-surface-soft); }
        .payment-card .card-logo { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; }
        .payment-card .card-logo img { width: 100%; height: 100%; object-fit: contain; }
        .card-divider { width: 100%; border-top: 1px dashed var(--air-hairline-soft); }
        .card-footer { text-align: center; }
        .card-amount { display: block; font-size: .82rem; font-weight: 600; color: var(--air-ink); }
        .card-check-text { display: block; font-size: .62rem; font-weight: 500; color: var(--air-muted); margin-top: .2rem; }
        .payment-card .card-radio { position: absolute; opacity: 0; width: 0; height: 0; }

        /* ── Payment Info Box ── */
        .payment-info-box {
            display: none; margin-top: .8rem; padding: 1rem 1.15rem;
            background: var(--air-surface-soft); border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-sm); animation: fadeSlideIn .25s ease;
        }
        .payment-info-box.visible { display: block; }
        @keyframes fadeSlideIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }
        .payment-info-box__title { font-size: .72rem; font-weight: 600; color: var(--air-muted); text-transform: uppercase; letter-spacing: .04em; margin-bottom: .4rem; }
        .payment-info-box__text { font-size: .88rem; font-weight: 500; color: var(--air-ink); line-height: 1.5; }
        .payment-info-box__number { display: inline-block; margin-top: .25rem; padding: .3rem .75rem; background: var(--air-canvas); border: 1px solid var(--air-hairline); border-radius: var(--air-radius-sm); font-size: .9rem; font-weight: 600; color: var(--air-ink); letter-spacing: .5px; user-select: all; }
        .payment-info-box__qris { margin-top: .5rem; text-align: center; }
        .payment-info-box__qris img { max-width: 220px; margin: 0 auto; border-radius: var(--air-radius-sm); border: 1px solid var(--air-hairline); }
        .payment-info-box__qris p { font-size: .72rem; color: var(--air-muted); margin-top: .35rem; }

        /* ── Upload ── */
        .upload-section { display: none; margin-top: .8rem; margin-bottom: 1.1rem; }
        .upload-section.visible { display: block; }
        .upload-area {
            position: relative; border: 2px dashed var(--air-hairline);
            border-radius: var(--air-radius-sm); padding: 1.2rem;
            text-align: center; background: var(--air-surface-soft);
            cursor: pointer; transition: border-color var(--air-transition);
        }
        .upload-area:hover, .upload-area.dragover { border-color: var(--air-ink); }
        .upload-area input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .upload-area__icon { font-size: 1.6rem; margin-bottom: .25rem; }
        .upload-area__text { font-size: .8rem; font-weight: 500; color: var(--air-muted); }
        .upload-area__hint { font-size: .7rem; color: var(--air-muted-soft); margin-top: .15rem; }
        .upload-preview { display: none; margin-top: .5rem; padding: .5rem; background: var(--air-canvas); border: 1px solid var(--air-hairline); border-radius: var(--air-radius-sm); text-align: center; }
        .upload-preview.visible { display: block; }
        .upload-preview img { max-height: 120px; border-radius: var(--air-radius-xs); margin: 0 auto; }
        .upload-preview__name { font-size: .72rem; color: var(--air-muted); margin-top: .25rem; font-weight: 500; word-break: break-all; }

        /* ── Footer ── */
        .footer {
            background: var(--air-canvas); color: var(--air-muted);
            text-align: center; padding: var(--air-space-lg) var(--air-space-lg);
            font-size: .8rem; margin-top: auto; border-top: 1px solid var(--air-hairline);
        }
        .footer strong { color: var(--air-ink); }

        @media (max-width: 520px) {
            .page-wrapper { margin: var(--air-space-lg) auto var(--air-space-xl); }
            .page-title { font-size: 1.3rem; }
        }
    </style>
</head>

<body>

    <!-- ══════════════════════════════════════════════════════════
     NAVBAR
══════════════════════════════════════════════════════════ -->
    <header>
        <nav class="navbar" aria-label="Navigasi halaman checkout">
            <div class="navbar__inner">

                <!-- Brand -->
                <a href="index.php" class="navbar__brand" id="nav-brand">
                    <img src="logo.png" alt="Logo Rumah Makan" class="navbar__logo-icon">
                    <span class="navbar__title">Rumah Makan <span>Sipatuo Jr.</span></span>
                </a>

                <!-- Back button -->
                <a href="index.php" id="btn-kembali-katalog">
                    <button class="navbar__back-btn" type="button">
                        ← Kembali ke Katalog
                    </button>
                </a>

            </div>
        </nav>
    </header>

    <!-- ══════════════════════════════════════════════════════════
     MAIN CONTENT
══════════════════════════════════════════════════════════ -->
    <main class="page-wrapper" id="checkout-content">

        <!-- Step indicator -->
        <div class="step-indicator" aria-label="Langkah pemesanan">
            <span class="step">1. Pilih Menu</span>
            <span class="sep">›</span>
            <span class="step active">2. Konfirmasi</span>
            <span class="sep">›</span>
            <span class="step">3. Selesai</span>
        </div>

        <?php if (!$has_order): ?>
            <!-- ══════════════════════════════════════════════
         EMPTY / ERROR STATE
    ══════════════════════════════════════════════ -->
            <?php if ($db_error): ?>
                <div class="state-card error" role="alert" id="error-state">
                    <div class="state-icon">⚠️</div>
                    <h2>Terjadi Kesalahan</h2>
                    <p><?= htmlspecialchars($db_error) ?></p>
                    <a href="index.php" class="btn-back-catalog" id="btn-kembali-error">
                        ← Kembali ke Katalog
                    </a>
                </div>
            <?php else: ?>
                <div class="state-card" id="empty-state">
                    <div class="state-icon">🛒</div>
                    <h2>Belum Ada Pesanan yang Dipilih</h2>
                    <p>Silakan kembali ke halaman katalog dan pilih menu yang ingin Anda pesan terlebih dahulu.</p>
                    <a href="index.php" class="btn-back-catalog" id="btn-kembali-empty">
                        ← Kembali ke Katalog
                    </a>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- ══════════════════════════════════════════════
         CHECKOUT CONTENT
    ══════════════════════════════════════════════ -->
            <?php
            // Persiapan tampilan
            $kat_lower = strtolower($menu['kategori']);
            $chip_class = match (true) {
                $kat_lower === 'makanan' => 'chip--makanan',
                $kat_lower === 'minuman' => 'chip--minuman',
                default => 'chip--default',
            };
            $img_path = 'img/menu/' . $menu['foto'];
            $img_exists = !empty($menu['foto']) && file_exists($img_path);
            ?>

            <h1 class="page-title">Konfirmasi Pesanan</h1>
            <p class="page-subtitle">Periksa detail pesanan Anda, lalu masukkan nama dan konfirmasi pembayaran.</p>

            <form method="POST" action="proses_pesanan.php" id="form-checkout" enctype="multipart/form-data" novalidate>
                <div class="checkout-layout">

                    <div class="col-left">

                        <!-- ── Ringkasan Pesanan ────────────────────── -->
                        <section class="summary-card" aria-label="Ringkasan pesanan" id="order-summary">

                            <div class="summary-card__header">
                                <span class="summary-card__header-icon" aria-hidden="true">📋</span>
                                <h2>Ringkasan Pesanan</h2>
                            </div>

                            <?php if ($img_exists): ?>
                                <img class="summary-card__img" src="<?= htmlspecialchars($img_path) ?>"
                                    alt="Foto <?= htmlspecialchars($menu['nama_menu']) ?>">
                            <?php else: ?>
                                <div class="summary-card__img-placeholder">
                                    <span class="ph-icon">🍴</span>
                                    <span>Foto tidak tersedia</span>
                                </div>
                            <?php endif; ?>

                            <div class="summary-card__body">
                                <div class="summary-row">
                                    <span class="summary-row__label">Menu Dipilih</span>
                                    <span class="summary-row__value nama">
                                        <?= htmlspecialchars($menu['nama_menu']) ?>
                                    </span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-row__label">Kategori</span>
                                    <span class="summary-row__value">
                                        <span class="kategori-chip <?= $chip_class ?>">
                                            <?= htmlspecialchars($menu['kategori']) ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-row__label">Harga Satuan</span>
                                    <span class="summary-row__value harga">
                                        <?= rupiah($menu['harga']) ?>
                                    </span>
                                </div>
                                <div class="summary-divider" aria-hidden="true"></div>
                                <div class="summary-row">
                                    <span class="summary-row__label">Jumlah</span>
                                    <span class="summary-row__value">
                                        <?= $jumlah_beli ?> porsi
                                    </span>
                                </div>
                                <div class="summary-total" id="summary-total">
                                    <span class="summary-total__label">Total Pembayaran</span>
                                    <span class="summary-total__value">
                                        <?= rupiah($total_harga) ?>
                                    </span>
                                </div>
                            </div>
                        </section>

                        <!-- ── Metode Pembayaran ────────────────────── -->
                        <section class="form-card" id="payment-form-card">
                            <div class="form-card__header">
                                <span class="form-card__header-icon" aria-hidden="true">💳</span>
                                <h2>Metode Pembayaran</h2>
                            </div>
                            <div class="form-card__body">
                                <div class="payment-accordion" id="payment-accordion">
                                    <div class="accordion-header" id="accordion-header">
                                        <span class="accordion-title">Pilih Metode</span>
                                        <div class="accordion-right">
                                            <div class="accordion-logos">
                                                <img src="img/logo/dana.png" alt="DANA" loading="lazy">
                                                <img src="img/logo/gopay.png" alt="GoPay" loading="lazy">
                                                <img src="img/logo/ovo.png" alt="OVO" loading="lazy">
                                                <img src="img/logo/spay.png" alt="ShopeePay" loading="lazy">
                                            </div>
                                            <span class="accordion-chevron" id="accordion-chevron">▼</span>
                                        </div>
                                    </div>
                                    <div class="accordion-body" id="accordion-body">
                                        <div class="payment-card-grid">
                                            <label class="payment-card" data-method="DANA">
                                                <input type="radio" name="metode_pembayaran" value="DANA" required
                                                    class="card-radio">
                                                <div class="card-logo"><img src="img/logo/dana.png" alt="DANA"></div>
                                                <div class="card-divider"></div>
                                                <div class="card-footer">
                                                    <span class="card-amount">Rp
                                                        <?= number_format($total_harga, 0, ',', '.') ?></span>
                                                    <span class="card-check-text">Dicek Otomatis</span>
                                                </div>
                                            </label>
                                            <label class="payment-card" data-method="GoPay">
                                                <input type="radio" name="metode_pembayaran" value="GoPay"
                                                    class="card-radio">
                                                <div class="card-logo"><img src="img/logo/gopay.png" alt="GoPay"></div>
                                                <div class="card-divider"></div>
                                                <div class="card-footer">
                                                    <span class="card-amount">Rp
                                                        <?= number_format($total_harga, 0, ',', '.') ?></span>
                                                    <span class="card-check-text">Dicek Otomatis</span>
                                                </div>
                                            </label>
                                            <label class="payment-card" data-method="OVO">
                                                <input type="radio" name="metode_pembayaran" value="OVO" class="card-radio">
                                                <div class="card-logo"><img src="img/logo/ovo.png" alt="OVO"></div>
                                                <div class="card-divider"></div>
                                                <div class="card-footer">
                                                    <span class="card-amount">Rp
                                                        <?= number_format($total_harga, 0, ',', '.') ?></span>
                                                    <span class="card-check-text">Dicek Otomatis</span>
                                                </div>
                                            </label>
                                            <label class="payment-card" data-method="ShopeePay">
                                                <input type="radio" name="metode_pembayaran" value="ShopeePay"
                                                    class="card-radio">
                                                <div class="card-logo"><img src="img/logo/spay.png" alt="ShopeePay"></div>
                                                <div class="card-divider"></div>
                                                <div class="card-footer">
                                                    <span class="card-amount">Rp
                                                        <?= number_format($total_harga, 0, ',', '.') ?></span>
                                                    <span class="card-check-text">Dicek Otomatis</span>
                                                </div>
                                            </label>
                                            <label class="payment-card" data-method="QRIS">
                                                <input type="radio" name="metode_pembayaran" value="QRIS"
                                                    class="card-radio">
                                                <div class="card-logo"><img src="img/logo/qris.png" alt="QRIS"></div>
                                                <div class="card-divider"></div>
                                                <div class="card-footer">
                                                    <span class="card-amount">Rp
                                                        <?= number_format($total_harga, 0, ',', '.') ?></span>
                                                    <span class="card-check-text">Dicek Otomatis</span>
                                                </div>
                                            </label>
                                            <label class="payment-card" data-method="COD">
                                                <input type="radio" name="metode_pembayaran" value="COD" class="card-radio">
                                                <div class="card-logo"><img src="img/logo/cod.png" alt="COD"></div>
                                                <div class="card-divider"></div>
                                                <div class="card-footer">
                                                    <span class="card-amount">Rp
                                                        <?= number_format($total_harga, 0, ',', '.') ?></span>
                                                    <span class="card-check-text">Dicek Otomatis</span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                    </div><!-- /.col-left -->

                    <div class="col-right">

                        <!-- ── Data Pelanggan ───────────────────────── -->
                        <section class="form-card" aria-label="Form konfirmasi pesanan" id="checkout-form-section">
                            <div class="form-card__header">
                                <span class="form-card__header-icon" aria-hidden="true">✍️</span>
                                <h2>Data Pelanggan</h2>
                            </div>
                            <div class="form-card__body">

                                <div class="field">
                                    <label for="nama_pelanggan">
                                        Nama Pelanggan
                                        <span class="required-star" aria-label="wajib diisi">*</span>
                                    </label>
                                    <input type="text" id="nama_pelanggan" name="nama_pelanggan"
                                        placeholder="Contoh: Budi Santoso" required autocomplete="name" maxlength="100">
                                </div>

                                <div class="field">
                                    <label for="no_telepon">
                                        Nomor Telepon
                                        <span class="required-star" aria-label="wajib diisi">*</span>
                                    </label>
                                    <input type="tel" id="no_telepon" name="no_telepon" placeholder="Contoh: 08123456789"
                                        required autocomplete="tel" maxlength="15">
                                </div>

                                <div class="field">
                                    <label for="alamat">
                                        Alamat
                                        <span class="required-star" aria-label="wajib diisi">*</span>
                                    </label>
                                    <textarea id="alamat" name="alamat" rows="3"
                                        placeholder="Contoh: Jl. Sudirman No. 10, Manado" required
                                        autocomplete="street-address"></textarea>
                                </div>

                                <input type="hidden" name="id_menu" value="<?= (int) $menu['id_menu'] ?>">
                                <input type="hidden" name="jumlah_beli" value="<?= (int) $jumlah_beli ?>">
                                <input type="hidden" name="total_harga" value="<?= (int) $total_harga ?>">

                                <!-- Instruksi pembayaran -->
                                <div class="payment-info-box" id="payment-info-box" style="margin-top:1rem;">
                                    <div class="payment-info-box__title">📋 Instruksi Pembayaran</div>
                                    <div id="payment-info-content"></div>
                                </div>

                                <!-- Upload Bukti Transfer -->
                                <div class="upload-section" id="upload-section" style="margin-top:.75rem;">
                                    <label for="bukti_transfer"
                                        style="display:block;font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.45rem;color:#374151;">
                                        Upload Bukti Pembayaran <span style="color:#dc2626">*</span>
                                    </label>
                                    <div class="upload-area" id="upload-area">
                                        <input type="file" name="bukti_transfer" id="bukti_transfer"
                                            accept=".jpg,.jpeg,.png">
                                        <div class="upload-area__icon">📤</div>
                                        <div class="upload-area__text">Klik atau seret file ke sini</div>
                                        <div class="upload-area__hint">Format: JPG, JPEG, PNG (maks. 2MB)</div>
                                    </div>
                                    <div class="upload-preview" id="upload-preview">
                                        <img id="preview-img" src="" alt="Preview bukti transfer">
                                        <div class="upload-preview__name" id="preview-name"></div>
                                    </div>
                                </div>

                                <button type="submit" class="btn-konfirmasi" id="btn-konfirmasi-bayar"
                                    style="margin-top:1rem;">
                                    <span class="btn-icon" aria-hidden="true">💳</span>
                                    Konfirmasi &amp; Bayar
                                </button>

                                <p class="form-disclaimer">
                                    🔒 Data Anda aman dan hanya digunakan untuk keperluan pesanan ini.
                                </p>

                            </div>
                        </section>

                    </div><!-- /.col-right -->

                </div><!-- /.checkout-layout -->
            </form>

        <?php endif; ?>

    </main>

    <!-- ══════════════════════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════════════════ -->
    <footer class="footer">
        <p>&copy; <?= date('Y') ?> <strong>Rumah Makan Sipatuo Jr.</strong>. Dibuat untuk tugas UMKM – Pemrograman Web
            Semester 4.</p>
    </footer>

    <script>
        (function () {
            const paymentData = {
                'Transfer Bank': {
                    text: 'Silakan transfer ke rekening berikut:',
                    number: 'BRI: 0813-4352-6694 (a.n. RM Sipatuo Jr.)'
                },
                'DANA': {
                    text: 'Silakan transfer ke DANA:',
                    number: '0813-4352-6694'
                },
                'GoPay': {
                    text: 'Silakan transfer ke GoPay:',
                    number: '0813-4352-6694'
                },
                'ShopeePay': {
                    text: 'Silakan transfer ke ShopeePay:',
                    number: '0813-4352-6694'
                },
                'OVO': {
                    text: 'Silakan transfer ke OVO:',
                    number: '0813-4352-6694'
                },
                'QRIS': {
                    text: 'Scan kode QR di bawah ini untuk melakukan pembayaran:',
                    qris: true
                },
                'COD': {
                    text: 'Pembayaran akan dilakukan secara tunai kepada kurir saat pesanan tiba di lokasi Anda.',
                    cod: true
                }
            };

            const accordionHeader = document.getElementById('accordion-header');
            const accordionBody = document.getElementById('accordion-body');
            const accordionChevron = document.getElementById('accordion-chevron');
            const cards = document.querySelectorAll('.payment-card');
            const infoBox = document.getElementById('payment-info-box');
            const infoContent = document.getElementById('payment-info-content');
            const uploadSection = document.getElementById('upload-section');
            const fileInput = document.getElementById('bukti_transfer');
            const uploadArea = document.getElementById('upload-area');
            const previewBox = document.getElementById('upload-preview');
            const previewImg = document.getElementById('preview-img');
            const previewName = document.getElementById('preview-name');
            const form = document.getElementById('form-checkout');

            accordionHeader.addEventListener('click', function () {
                accordionBody.classList.toggle('open');
                accordionChevron.classList.toggle('open');
            });

            cards.forEach(function (card) {
                card.addEventListener('click', function () {
                    cards.forEach(function (c) { c.classList.remove('selected'); });
                    this.classList.add('selected');
                    var radio = this.querySelector('.card-radio');
                    if (radio) radio.checked = true;
                    radio.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });

            document.querySelectorAll('.card-radio').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    var method = this.value;
                    var data = paymentData[method];
                    if (!data) return;

                    var html = '<p class="payment-info-box__text">' + data.text + '</p>';
                    if (data.qris) {
                        html += '<div class="payment-info-box__qris">';
                        html += '<img src="img/qris_toko.jpeg" alt="QRIS RM Sipatuo Jr.">';
                        html += '<p>Scan menggunakan aplikasi e-wallet atau mobile banking Anda</p>';
                        html += '</div>';
                    } else if (data.cod) {
                        html += '<div class="payment-info-box__number">Siapkan Uang Pas</div>';
                    } else {
                        html += '<div class="payment-info-box__number">' + data.number + '</div>';
                    }

                    infoContent.innerHTML = html;
                    infoBox.classList.add('visible');

                    if (data.cod) {
                        uploadSection.classList.remove('visible');
                        fileInput.removeAttribute('required');
                    } else {
                        uploadSection.classList.add('visible');
                        fileInput.setAttribute('required', 'required');
                    }
                });
            });

            fileInput.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    var file = this.files[0];
                    var validExts = ['image/jpeg', 'image/jpg', 'image/png'];
                    if (validExts.indexOf(file.type) === -1) {
                        alert('Format file tidak valid. Hanya JPG, JPEG, dan PNG yang diterima.');
                        this.value = '';
                        previewBox.classList.remove('visible');
                        return;
                    }
                    if (file.size > 2 * 1024 * 1024) {
                        alert('Ukuran file melebihi 2MB. Silakan kompres terlebih dahulu.');
                        this.value = '';
                        previewBox.classList.remove('visible');
                        return;
                    }
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        previewImg.src = e.target.result;
                        previewName.textContent = file.name;
                        previewBox.classList.add('visible');
                    };
                    reader.readAsDataURL(file);
                }
            });

            uploadArea.addEventListener('dragover', function (e) { e.preventDefault(); this.classList.add('dragover'); });
            uploadArea.addEventListener('dragleave', function () { this.classList.remove('dragover'); });
            uploadArea.addEventListener('drop', function () { this.classList.remove('dragover'); });

            form.addEventListener('submit', function (e) {
                var selected = document.querySelector('input[name="metode_pembayaran"]:checked');
                if (!selected) {
                    e.preventDefault();
                    alert('Silakan pilih metode pembayaran terlebih dahulu.');
                    return;
                }
                if (selected.value !== 'COD') {
                    if (!fileInput.files || fileInput.files.length === 0) {
                        e.preventDefault();
                        alert('Silakan upload bukti transfer terlebih dahulu.');
                        return;
                    }
                }
            });
        })();
    </script>

</body>

</html>
<?php
// Tutup koneksi database
if (isset($koneksi)) {
    mysqli_close($koneksi);
}
?>