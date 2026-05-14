<?php
session_start();
include 'koneksi.php';

$cart_count = 0;
if (!empty($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $qty) {
        $cart_count += (int) $qty;
    }
}

if (isset($_GET['hapus'])) {
    $hapus_id = (int) $_GET['hapus'];
    if (isset($_SESSION['keranjang'][$hapus_id])) {
        unset($_SESSION['keranjang'][$hapus_id]);
    }
    header("Location: keranjang.php");
    exit;
}

if (isset($_GET['kosongkan'])) {
    unset($_SESSION['keranjang']);
    header("Location: keranjang.php");
    exit;
}

$items = [];
$grand_total = 0;

if (!empty($_SESSION['keranjang'])) {
    $ids = array_map('intval', array_keys($_SESSION['keranjang']));
    $ids_str = implode(',', $ids);
    $query = "SELECT id_menu, nama_menu, harga FROM menu WHERE id_menu IN ($ids_str)";
    $result = mysqli_query($koneksi, $query);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $id = (int) $row['id_menu'];
            $qty = (int) $_SESSION['keranjang'][$id];
            $subtotal = (int) $row['harga'] * $qty;

            $items[] = [
                'id' => $id,
                'nama' => $row['nama_menu'],
                'harga' => (int) $row['harga'],
                'qty' => $qty,
                'subtotal' => $subtotal,
            ];

            $grand_total += $subtotal;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Keranjang belanja Rumah Makan Sipatuo Jr. – Review pesanan Anda sebelum checkout.">
    <title>Keranjang – Rumah Makan Sipatuo Jr.</title>
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
            --air-scrim: rgba(0,0,0,0.5);
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
            min-height: 100vh;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        img { display: block; max-width: 100%; }
        a { text-decoration: none; color: inherit; }

        /* ── Navbar ── */
        .navbar {
            position: sticky; top: 0; z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--air-hairline);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 0 var(--air-space-lg);
            height: 80px;
        }
        .navbar__inner {
            max-width: 960px; margin: 0 auto; height: 100%;
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        }
        .navbar__brand { display: flex; align-items: center; gap: .65rem; }
        .navbar__logo-icon {
            width: 38px; height: 38px;
            border-radius: var(--air-radius-sm); object-fit: contain; flex-shrink: 0;
        }
        .navbar__title { font-size: 1.05rem; font-weight: 600; letter-spacing: -.3px; }
        .navbar__title span { color: var(--air-primary); }

        .btn-back {
            display: flex; align-items: center; gap: .5rem;
            padding: .55rem 1.1rem;
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-sm);
            font-family: inherit; font-size: .875rem; font-weight: 500;
            background: transparent; color: var(--air-ink);
            cursor: pointer; transition: border-color var(--air-transition);
        }
        .btn-back:hover { border-color: var(--air-ink); }

        /* ── Page ── */
        .page { max-width: 960px; margin: 0 auto; padding: var(--air-space-xl) var(--air-space-lg) var(--air-space-xxl); }

        .section-header { display: flex; align-items: center; gap: .75rem; margin-bottom: var(--air-space-xl); }
        .section-header__line { display: none; }
        .section-header h1 { font-size: 1.4rem; font-weight: 600; letter-spacing: -.3px; }

        /* ── Empty state ── */
        .empty-state {
            text-align: center; padding: 4rem 1.5rem;
            border: 2px dashed var(--air-hairline);
            border-radius: var(--air-radius-lg); color: var(--air-muted);
        }
        .empty-state .empty-icon { font-size: 4rem; margin-bottom: 1rem; }
        .empty-state h2 { font-size: 1.2rem; font-weight: 600; color: var(--air-ink); margin-bottom: .5rem; }

        .btn-primary {
            display: inline-flex; align-items: center; gap: .45rem;
            margin-top: var(--air-space-lg);
            padding: .65rem 1.5rem;
            background: var(--air-primary); color: var(--air-on-primary);
            border: none; border-radius: var(--air-radius-sm);
            font-family: inherit; font-size: .9rem; font-weight: 500;
            cursor: pointer; transition: background var(--air-transition);
            height: 48px;
        }
        .btn-primary:hover { background: var(--air-primary-active); }

        /* ── Cart actions ── */
        .cart-actions { display: flex; justify-content: flex-end; gap: .75rem; margin-bottom: var(--air-space-lg); }
        .btn-clear {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .5rem 1.1rem;
            background: transparent; color: #dc2626;
            border: 1px solid #fecaca;
            border-radius: var(--air-radius-sm);
            font-family: inherit; font-size: .85rem; font-weight: 500;
            cursor: pointer; transition: background var(--air-transition);
        }
        .btn-clear:hover { background: #fef2f2; }

        /* ── Cart table ── */
        .cart-card {
            background: var(--air-canvas); border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-md); overflow: hidden; margin-bottom: 1.5rem;
        }
        .cart-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .cart-table th {
            padding: .8rem 1.1rem; text-align: left;
            font-size: .72rem; font-weight: 600; letter-spacing: .06em;
            text-transform: uppercase; color: var(--air-muted);
            border-bottom: 1px solid var(--air-hairline);
        }
        .cart-table th.text-right, .cart-table td.text-right { text-align: right; }
        .cart-table td {
            padding: .85rem 1.1rem; font-size: .88rem;
            border-bottom: 1px solid var(--air-hairline-soft);
            vertical-align: middle;
        }
        .cart-table tr:last-child td { border-bottom: none; }
        .cart-table tr:hover td { background: var(--air-surface-soft); }
        .item-name { font-weight: 600; color: var(--air-ink); }
        .item-qty { font-weight: 600; color: var(--air-ink); }
        .item-sub { font-weight: 600; color: var(--air-primary); }

        .btn-hapus {
            display: inline-flex; align-items: center; gap: .3rem;
            padding: .3rem .65rem;
            background: var(--air-canvas); color: #dc2626;
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-sm);
            font-family: inherit; font-size: .75rem; font-weight: 500;
            cursor: pointer; transition: border-color var(--air-transition);
        }
        .btn-hapus:hover { border-color: #dc2626; }

        .grand-total-row td {
            background: var(--air-surface-soft) !important;
            font-size: 1rem; font-weight: 600;
            border-top: 1px solid var(--air-hairline);
        }
        .grand-total-row .label { color: var(--air-ink); }
        .grand-total-row .amount { color: var(--air-primary); font-size: 1.1rem; }

        /* ── Checkout card ── */
        .checkout-card {
            background: var(--air-canvas);
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-md);
            padding: var(--air-space-xl);
        }
        .checkout-card h2 {
            font-size: 1.05rem; font-weight: 600;
            margin-bottom: var(--air-space-lg);
            display: flex; align-items: center; gap: .5rem;
        }

        /* ── Form ── */
        .form-group { margin-bottom: var(--air-space-base); }
        .form-label {
            display: block; font-size: .78rem; font-weight: 600;
            color: var(--air-muted); margin-bottom: .4rem;
            letter-spacing: .03em; text-transform: uppercase;
        }
        .form-input {
            width: 100%; padding: .7rem .9rem;
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-sm);
            font-family: inherit; font-size: .92rem; font-weight: 500;
            color: var(--air-ink); transition: border-color var(--air-transition);
            outline: none;
        }
        .form-input:focus { border-color: var(--air-ink); border-width: 2px; }

        .btn-checkout {
            width: 100%; padding: .8rem 1.5rem;
            background: var(--air-primary); color: var(--air-on-primary);
            border: none; border-radius: var(--air-radius-sm);
            font-family: inherit; font-size: 1rem; font-weight: 500;
            cursor: pointer; transition: background var(--air-transition);
            display: flex; align-items: center; justify-content: center; gap: .5rem;
            height: 48px;
        }
        .btn-checkout:hover { background: var(--air-primary-active); }

        /* ── Payment Accordion ── */
        .payment-accordion {
            background: var(--air-canvas);
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-md);
            overflow: hidden; margin-bottom: 1rem;
        }
        .accordion-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1rem 1.25rem; cursor: pointer; user-select: none;
            background: var(--air-surface-soft);
            transition: background var(--air-transition);
        }
        .accordion-header:hover { background: var(--air-hairline-soft); }
        .accordion-title { font-size: .92rem; font-weight: 600; color: var(--air-ink); }
        .accordion-right { display: flex; align-items: center; gap: .75rem; }
        .accordion-logos { display: flex; align-items: center; gap: .4rem; }
        .accordion-logos img { width: 22px; height: 22px; object-fit: contain; border-radius: var(--air-radius-xs); }
        .accordion-chevron { font-size: .7rem; color: var(--air-muted); transition: transform .3s ease; }
        .accordion-chevron.open { transform: rotate(180deg); }
        .accordion-body {
            max-height: 0; overflow: hidden;
            transition: max-height .35s ease, padding .35s ease;
            padding: 0 1.25rem;
        }
        .accordion-body.open { max-height: 600px; padding: var(--air-space-lg) 1.5rem 1.5rem; }

        .payment-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: var(--air-space-sm);
        }
        .payment-card {
            position: relative;
            display: flex; flex-direction: column; align-items: center; gap: .5rem;
            padding: 1rem .75rem .75rem;
            background: var(--air-canvas);
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-md);
            cursor: pointer;
            transition: box-shadow var(--air-transition), border-color var(--air-transition);
        }
        .payment-card:hover {
            box-shadow: var(--air-shadow-hover);
        }
        .payment-card.selected {
            border-color: var(--air-ink); border-width: 2px;
            background: var(--air-surface-soft);
        }
        .payment-card .card-logo { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; }
        .payment-card .card-logo img { width: 100%; height: 100%; object-fit: contain; }
        .card-divider { width: 100%; border-top: 1px dashed var(--air-hairline-soft); }
        .card-footer { text-align: center; }
        .card-amount { display: block; font-size: .82rem; font-weight: 600; color: var(--air-ink); }
        .card-check-text { display: block; font-size: .62rem; font-weight: 500; color: var(--air-muted); margin-top: .2rem; }
        .payment-card .card-radio { position: absolute; opacity: 0; width: 0; height: 0; }

        /* ── Payment Info Box ── */
        .payment-info-box {
            display: none; margin-top: var(--air-space-sm);
            padding: .9rem 1rem;
            background: var(--air-surface-soft);
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-sm);
            animation: fadeSlideIn .22s ease;
        }
        .payment-info-box.visible { display: block; }
        @keyframes fadeSlideIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
        .payment-info-box__title {
            font-size: .72rem; font-weight: 600;
            color: var(--air-muted); text-transform: uppercase;
            letter-spacing: .04em; margin-bottom: .35rem;
        }
        .payment-info-box__text { font-size: .85rem; font-weight: 500; color: var(--air-ink); line-height: 1.5; }
        .payment-info-box__number {
            display: inline-block; margin-top: .25rem; padding: .3rem .75rem;
            background: var(--air-canvas);
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-sm);
            font-size: .88rem; font-weight: 600; color: var(--air-ink);
            letter-spacing: .5px; user-select: all;
        }
        .payment-info-box__qris { margin-top: .5rem; text-align: center; }
        .payment-info-box__qris img {
            max-width: 200px; margin: 0 auto;
            border-radius: var(--air-radius-sm); border: 1px solid var(--air-hairline);
        }
        .payment-info-box__qris p { font-size: .72rem; color: var(--air-muted); margin-top: .35rem; }

        /* ── Upload ── */
        .upload-section { display: none; margin-top: .7rem; margin-bottom: 1rem; }
        .upload-section.visible { display: block; }
        .upload-area {
            position: relative;
            border: 2px dashed var(--air-hairline);
            border-radius: var(--air-radius-sm); padding: 1rem;
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
        .upload-preview img { max-height: 110px; border-radius: var(--air-radius-xs); margin: 0 auto; }
        .upload-preview__name { font-size: .72rem; color: var(--air-muted); margin-top: .25rem; font-weight: 500; word-break: break-all; }

        /* ── Footer ── */
        .footer {
            background: var(--air-canvas); color: var(--air-muted);
            padding: var(--air-space-xxl) var(--air-space-lg) 0;
            font-size: .875rem; border-top: 1px solid var(--air-hairline);
        }
        .footer__inner {
            max-width: 960px; margin: 0 auto;
            display: grid;
            grid-template-columns: 1.4fr 1fr 1.6fr;
            gap: var(--air-space-xl);
            padding-bottom: var(--air-space-xl);
            border-bottom: 1px solid var(--air-hairline-soft);
            align-items: start;
        }
        .footer__brand-name { font-size: 1rem; font-weight: 600; color: var(--air-ink); margin-bottom: .35rem; }
        .footer__brand-name span { color: var(--air-primary); }
        .footer__desc { font-size: .85rem; color: var(--air-muted); line-height: 1.6; margin-top: .35rem; max-width: 280px; }
        .footer__col-title { font-size: .72rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--air-muted); margin-bottom: var(--air-space-base); }
        .footer__link { display: flex; align-items: center; gap: .55rem; padding: .4rem 0; color: var(--air-muted); font-size: .875rem; font-weight: 500; transition: color var(--air-transition); }
        .footer__link:hover { color: var(--air-ink); }
        .footer__link-icon { width: 32px; height: 32px; border-radius: var(--air-radius-sm); display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
        .icon-ig, .icon-fb { background: transparent; }
        .footer__bottom { max-width: 960px; margin: 0 auto; padding: var(--air-space-base) 0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem; font-size: .8rem; color: var(--air-muted-soft); }
        .footer__bottom strong { color: var(--air-ink); }

        @media (max-width: 720px) {
            .footer__inner { grid-template-columns: 1fr; gap: var(--air-space-lg); }
        }
        @media (max-width: 600px) {
            .page { padding: var(--air-space-lg) var(--air-space-base) var(--air-space-xl); }
            .cart-table th, .cart-table td { padding: .7rem .75rem; font-size: .82rem; }
            .cart-table .col-hapus { display: none; }
        }
    </style>
</head>

<body>

    <header>
        <nav class="navbar" aria-label="Navigasi utama">
            <div class="navbar__inner">
                <a href="index.php" class="navbar__brand" id="nav-brand">
                    <img src="logo.png" alt="Logo Rumah Makan" class="navbar__logo-icon">
                    <span class="navbar__title">Rumah Makan <span>Sipatuo Jr.</span></span>
                </a>
                <a href="index.php">
                    <button class="btn-back" type="button" id="btn-lanjut-belanja">← Lanjut Belanja</button>
                </a>
            </div>
        </nav>
    </header>

    <main class="page" id="keranjang-page">

        <div class="section-header">
            <div class="section-header__line" aria-hidden="true"></div>
            <h1>Keranjang Pesanan Anda</h1>
        </div>

        <?php if (empty($items)): ?>
            <div class="empty-state" id="keranjang-kosong">
                <div class="empty-icon">🛒</div>
                <h2>Keranjang Anda masih kosong</h2>
                <p>Silakan pilih menu terlebih dahulu dari katalog kami.</p>
                <a href="index.php">
                    <button class="btn-primary" id="btn-ke-katalog">🍽️ Lihat Katalog Menu</button>
                </a>
            </div>

        <?php else: ?>
            <div class="cart-actions">
                <a href="keranjang.php?kosongkan=1" onclick="return confirm('Yakin ingin mengosongkan keranjang?')">
                    <button class="btn-clear" type="button" id="btn-kosongkan">🗑️ Kosongkan Keranjang</button>
                </a>
            </div>

            <div class="cart-card">
                <table class="cart-table" id="tabel-keranjang">
                    <thead>
                        <tr>
                            <th>Nama Menu</th>
                            <th class="text-right">Harga Satuan</th>
                            <th class="text-right">Jumlah</th>
                            <th class="text-right">Subtotal</th>
                            <th class="col-hapus"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr id="row-menu-<?= $item['id'] ?>">
                                <td class="item-name"><?= htmlspecialchars($item['nama']) ?></td>
                                <td class="text-right">Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                                <td class="text-right item-qty"><?= $item['qty'] ?> pcs</td>
                                <td class="text-right item-sub">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                                <td class="col-hapus">
                                    <a href="keranjang.php?hapus=<?= $item['id'] ?>"
                                        onclick="return confirm('Hapus item ini dari keranjang?')">
                                        <button class="btn-hapus" type="button" id="hapus-<?= $item['id'] ?>">✕ Hapus</button>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="grand-total-row">
                            <td colspan="3" class="label">🧾 Total Keseluruhan</td>
                            <td class="text-right amount">Rp <?= number_format($grand_total, 0, ',', '.') ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="checkout-card">
                <h2>📋 Detail Pemesanan</h2>
                <form method="POST" action="proses_pesanan_multi.php" id="form-checkout" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label" for="nama_pelanggan">Nama Pelanggan</label>
                        <input type="text" class="form-input" id="nama_pelanggan" name="nama_pelanggan"
                            placeholder="Masukkan nama Anda…" required autocomplete="name">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="no_telepon">Nomor Telepon</label>
                        <input type="tel" class="form-input" id="no_telepon" name="no_telepon"
                            placeholder="Contoh: 08123456789" required autocomplete="tel">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="alamat">Alamat</label>
                        <textarea class="form-input" id="alamat" name="alamat" rows="3"
                            placeholder="Masukkan alamat lengkap Anda…" required></textarea>
                    </div>

                    <!-- ── Metode Pembayaran (Dark Accordion) ────── -->
                    <div class="payment-accordion" id="payment-accordion">
                        <div class="accordion-header" id="accordion-header">
                            <span class="accordion-title">Metode Pembayaran</span>
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
                                    <input type="radio" name="metode_pembayaran" value="DANA" required class="card-radio">
                                    <div class="card-logo"><img src="img/logo/dana.png" alt="DANA"></div>
                                    <div class="card-divider"></div>
                                    <div class="card-footer">
                                        <span class="card-amount">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
                                        <span class="card-check-text">Dicek Otomatis</span>
                                    </div>
                                </label>
                                <label class="payment-card" data-method="GoPay">
                                    <input type="radio" name="metode_pembayaran" value="GoPay" class="card-radio">
                                    <div class="card-logo"><img src="img/logo/gopay.png" alt="GoPay"></div>
                                    <div class="card-divider"></div>
                                    <div class="card-footer">
                                        <span class="card-amount">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
                                        <span class="card-check-text">Dicek Otomatis</span>
                                    </div>
                                </label>
                                <label class="payment-card" data-method="OVO">
                                    <input type="radio" name="metode_pembayaran" value="OVO" class="card-radio">
                                    <div class="card-logo"><img src="img/logo/ovo.png" alt="OVO"></div>
                                    <div class="card-divider"></div>
                                    <div class="card-footer">
                                        <span class="card-amount">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
                                        <span class="card-check-text">Dicek Otomatis</span>
                                    </div>
                                </label>
                                <label class="payment-card" data-method="ShopeePay">
                                    <input type="radio" name="metode_pembayaran" value="ShopeePay" class="card-radio">
                                    <div class="card-logo"><img src="img/logo/spay.png" alt="ShopeePay"></div>
                                    <div class="card-divider"></div>
                                    <div class="card-footer">
                                        <span class="card-amount">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
                                        <span class="card-check-text">Dicek Otomatis</span>
                                    </div>
                                </label>
                                <label class="payment-card" data-method="QRIS">
                                    <input type="radio" name="metode_pembayaran" value="QRIS" class="card-radio">
                                    <div class="card-logo"><img src="img/logo/qris.png" alt="QRIS"></div>
                                    <div class="card-divider"></div>
                                    <div class="card-footer">
                                        <span class="card-amount">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
                                        <span class="card-check-text">Dicek Otomatis</span>
                                    </div>
                                </label>
                                <label class="payment-card" data-method="COD">
                                    <input type="radio" name="metode_pembayaran" value="COD" class="card-radio">
                                    <div class="card-logo"><img src="img/logo/cod.png" alt="COD"></div>
                                    <div class="card-divider"></div>
                                    <div class="card-footer">
                                        <span class="card-amount">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
                                        <span class="card-check-text">Dicek Otomatis</span>
                                    </div>
                                </label>
                            </div>

                        </div>
                    </div>

                    <!-- Instruksi pembayaran (di luar accordion) -->
                    <div class="payment-info-box" id="payment-info-box" style="margin-top:1rem;">
                        <div class="payment-info-box__title">📋 Instruksi Pembayaran</div>
                        <div id="payment-info-content"></div>
                    </div>

                    <!-- Upload Bukti Transfer -->
                    <div class="upload-section" id="upload-section" style="margin-top:.75rem;">
                        <label class="form-label" for="bukti_transfer" style="color:#374151;">
                            Upload Bukti Pembayaran <span style="color:#dc2626">*</span>
                        </label>
                        <div class="upload-area" id="upload-area">
                            <input type="file" name="bukti_transfer" id="bukti_transfer" accept=".jpg,.jpeg,.png">
                            <div class="upload-area__icon">📤</div>
                            <div class="upload-area__text">Klik atau seret file ke sini</div>
                            <div class="upload-area__hint">Format: JPG, JPEG, PNG (maks. 2MB)</div>
                        </div>
                        <div class="upload-preview" id="upload-preview">
                            <img id="preview-img" src="" alt="Preview bukti transfer">
                            <div class="upload-preview__name" id="preview-name"></div>
                        </div>
                    </div>

                    <button type="submit" class="btn-checkout" id="btn-proses-pesanan">✅ Proses Semua Pesanan</button>
                </form>
            </div>

        <?php endif; ?>

    </main>

    <footer class="footer" id="site-footer">
        <div class="footer__inner">
            <div>
                <div class="footer__brand-name">Rumah Makan <span>Sipatuo Jr.</span></div>
                <p class="footer__desc">Menyajikan cita rasa khas Minahasa yang autentik dan lezat. Pilih menu favoritmu
                    dan nikmati kelezatannya!</p>
            </div>
            <div>
                <div class="footer__col-title">Ikuti Kami</div>
                <a href="https://www.instagram.com/sipatuojr/" target="_blank" rel="noopener noreferrer"
                    class="footer__link" id="link-instagram">
                    <span class="footer__link-icon icon-ig">
                        <img src="img/logo/ig.jpg" alt="Instagram"
                            style="width:100%;height:100%;object-fit:cover;border-radius:8px;">
                    </span> @sipatuojr
                </a>
                <a href="https://web.facebook.com/sipatuojr/" target="_blank" rel="noopener noreferrer"
                    class="footer__link" id="link-facebook">
                    <span class="footer__link-icon icon-fb">
                        <img src="img/logo/fb.png" alt="Facebook"
                            style="width:100%;height:100%;object-fit:contain;border-radius:8px;">
                    </span> Sipatuo Jr.
                </a>
            </div>
            <div>
                <div class="footer__col-title">Lokasi Kami</div>
                <a href="https://www.google.com/maps/place/SIPATUO-JR+MINI+SOCCER+%26+CAFE/@0.694815,124.2979114,16.96z"
                    target="_blank" rel="noopener noreferrer" id="link-gmaps" style="display:block;">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d992.62!2d124.2979114!3d0.694815!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x327e3d573025a76f%3A0xb4b3b0fdb8640e4a!2sSIPATUO-JR%20MINI%20SOCCER%20%26%20CAFE!5e0!3m2!1sid!2sid!4v1714900000000"
                        class="footer__map" allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Lokasi Rumah Makan Sipatuo Jr. di Google Maps">
                    </iframe>
                </a>
            </div>
        </div>
        <div class="footer__bottom">
            <span>&copy; <?= date('Y') ?> <strong>Rumah Makan Sipatuo Jr.</strong> — Semua hak dilindungi.</span>
            <span>Dibuat untuk tugas UMKM – Pemrograman Web Semester 4.</span>
        </div>
    </footer>

    <script>
        (function () {
            const paymentData = {
                'DANA': { text: 'Silakan transfer ke DANA:', number: '0813-4352-6694' },
                'GoPay': { text: 'Silakan transfer ke GoPay:', number: '0813-4352-6694' },
                'ShopeePay': { text: 'Silakan transfer ke ShopeePay:', number: '0813-4352-6694' },
                'OVO': { text: 'Silakan transfer ke OVO:', number: '0813-4352-6694' },
                'QRIS': { text: 'Scan kode QR di bawah ini untuk melakukan pembayaran:', qris: true },
                'COD': { text: 'Pembayaran akan dilakukan secara tunai kepada kurir saat pesanan tiba di lokasi Anda.', cod: true }
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

            /* ── Accordion toggle ── */
            accordionHeader.addEventListener('click', function () {
                accordionBody.classList.toggle('open');
                accordionChevron.classList.toggle('open');
            });

            /* ── Card selection ── */
            cards.forEach(function (card) {
                card.addEventListener('click', function (e) {
                    cards.forEach(function (c) { c.classList.remove('selected'); });
                    this.classList.add('selected');
                    var radio = this.querySelector('.card-radio');
                    if (radio) radio.checked = true;
                    radio.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });

            /* ── Radio change → show instructions ── */
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

            /* ── File input preview ── */
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

            /* ── Form validation ── */
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
if (isset($koneksi)) {
    mysqli_close($koneksi);
}
?>