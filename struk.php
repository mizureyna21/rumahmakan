<?php
session_start();
require 'koneksi.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = mysqli_prepare($koneksi, "SELECT * FROM pesanan WHERE id_pesanan = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$pesanan = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$pesanan) {
    header('Location: index.php');
    exit;
}

$stmt2 = mysqli_prepare($koneksi,
    "SELECT dp.*, m.nama_menu, m.harga
     FROM detail_pesanan dp
     JOIN menu m ON dp.id_menu = m.id_menu
     WHERE dp.id_pesanan = ?");
mysqli_stmt_bind_param($stmt2, 'i', $id);
mysqli_stmt_execute($stmt2);
$items = mysqli_stmt_get_result($stmt2)->fetch_all(MYSQLI_ASSOC);
mysqli_stmt_close($stmt2);

mysqli_close($koneksi);

$total = (int) $pesanan['total_harga'];
$status = $pesanan['status'] ?? 'Pending';
$status_icon = match ($status) {
    'Pending'  => '🕐',
    'Dimasak'  => '👨‍🍳',
    'Dikirim'  => '🛵',
    'Selesai'  => '✅',
    default    => '📋',
};
$st_cls = strtolower($status);

function rupiah(int $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

function tgl_id(string $dt): string {
    $bln = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $ts  = strtotime($dt);
    return date('d', $ts) . ' ' . $bln[(int)date('m', $ts)-1] . ' ' . date('Y', $ts) . ' · ' . date('H:i', $ts) . ' WIB';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Struk digital pesanan Rumah Makan Sipatuo Jr.">
<title>Struk Pesanan #<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?> — RM Sipatuo Jr.</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
        :root {
            --air-primary: #ff385c;
            --air-primary-active: #e00b41;
            --air-ink: #222222;
            --air-body: #3f3f3f;
            --air-muted: #6a6a6a;
            --air-muted-soft: #929292;
            --air-hairline: #dddddd;
            --air-hairline-soft: #ebebeb;
            --air-canvas: #ffffff;
            --air-surface-soft: #f7f7f7;
            --air-surface-strong: #f2f2f2;
            --air-on-primary: #ffffff;
            --air-radius-xs: 4px;
            --air-radius-sm: 8px;
            --air-radius-md: 14px;
            --air-radius-lg: 20px;
            --air-radius-full: 9999px;
            --air-space-xs: 4px;
            --air-space-sm: 8px;
            --air-space-base: 16px;
            --air-space-lg: 24px;
            --air-space-xl: 32px;
            --air-space-xxl: 48px;
            --air-transition: .2s cubic-bezier(.4,0,.2,1);
        }

        *,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, system-ui, Roboto, 'Helvetica Neue', sans-serif;
            background: var(--air-surface-soft); color: var(--air-ink);
            min-height: 100vh; display: flex; flex-direction: column; line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }
        img { display: block; max-width: 100%; }

        /* ── Navbar ── */
        .navbar {
            background: var(--air-canvas);
            border-bottom: 1px solid var(--air-hairline);
            padding: 0 var(--air-space-lg); height: 80px;
        }
        .navbar__inner {
            max-width: 680px; margin: 0 auto; height: 100%;
            display: flex; align-items: center; gap: .65rem;
        }
        .navbar__logo-icon { width: 38px; height: 38px; border-radius: var(--air-radius-sm); object-fit: contain; flex-shrink: 0; }
        .navbar__title { font-size: 1.05rem; font-weight: 600; letter-spacing: -.3px; }
        .navbar__title span { color: var(--air-primary); }

        .stage { flex: 1; padding: var(--air-space-xl) var(--air-space-lg) var(--air-space-xxl); }

        /* ── Invoice ── */
        .invoice {
            max-width: 600px; margin: 0 auto;
            background: var(--air-canvas);
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-md);
            overflow: hidden;
        }
        .invoice__strip { height: 4px; background: var(--air-primary); }
        .invoice__body { padding: var(--air-space-xl); }

        .invoice__header {
            text-align: center; margin-bottom: var(--air-space-lg);
            padding-bottom: var(--air-space-lg);
            border-bottom: 1px solid var(--air-hairline);
        }
        .invoice__logo { width: 56px; height: 56px; margin: 0 auto .75rem; }
        .invoice__resto { font-size: 1.2rem; font-weight: 600; }
        .invoice__resto span { color: var(--air-primary); }
        .invoice__tagline { font-size: .78rem; color: var(--air-muted); margin-top: .2rem; }

        .invoice__ref {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: var(--air-space-lg); gap: .5rem;
        }
        .invoice__order-id {
            font-size: .75rem; font-weight: 600; color: var(--air-muted);
            background: var(--air-surface-soft);
            border: 1px solid var(--air-hairline);
            padding: .3rem .75rem; border-radius: var(--air-radius-sm); display: inline-block;
        }
        .invoice__order-id strong { color: var(--air-ink); font-size: .85rem; }

        .invoice__status {
            display: inline-flex; align-items: center; gap: .35rem;
            font-size: .75rem; font-weight: 600; padding: .25rem .75rem;
            border-radius: var(--air-radius-full);
        }
        .invoice__status.pending { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
        .invoice__status.dimasak { background: #fefce8; color: #a16207; border: 1px solid #fde047; }
        .invoice__status.dikirim { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .invoice__status.selesai { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }

        .invoice__meta {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: .75rem 1.25rem;
            background: var(--air-surface-soft);
            border-radius: var(--air-radius-sm);
            padding: 1rem 1.25rem; margin-bottom: var(--air-space-lg); font-size: .85rem;
        }
        .meta__label { color: var(--air-muted); font-weight: 600; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; }
        .meta__value { color: var(--air-ink); font-weight: 600; margin-top: .15rem; }
        .meta__full { grid-column: 1 / -1; }

        .invoice__table { width: 100%; border-collapse: collapse; margin-bottom: var(--air-space-lg); font-size: .875rem; }
        .invoice__table thead th {
            padding: .6rem .75rem;
            font-size: .68rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em;
            color: var(--air-muted); text-align: left;
            border-bottom: 1px solid var(--air-hairline);
        }
        .invoice__table thead th.right { text-align: right; }
        .invoice__table tbody td { padding: .65rem .75rem; border-bottom: 1px solid var(--air-hairline-soft); vertical-align: middle; }
        .invoice__table tbody td.right { text-align: right; }
        .invoice__table tbody tr:last-child td { border-bottom: none; }
        .col-name { font-weight: 600; color: var(--air-ink); }
        .col-price, .col-sub { color: var(--air-muted); white-space: nowrap; }
        .col-qty { text-align: center; font-weight: 600; }
        .col-sub { font-weight: 600; color: var(--air-ink); }

        .invoice__total {
            display: flex; justify-content: space-between; align-items: center;
            background: var(--air-surface-soft);
            border-radius: var(--air-radius-sm);
            padding: .85rem 1rem; margin-bottom: var(--air-space-lg);
        }
        .invoice__total-label { font-size: .8rem; font-weight: 600; color: var(--air-ink); text-transform: uppercase; letter-spacing: .05em; }
        .invoice__total-value { font-size: 1.3rem; font-weight: 700; color: var(--air-primary); letter-spacing: -.4px; }
        .invoice__pay-method { font-size: .78rem; color: var(--air-muted); text-align: right; margin-top: .1rem; }

        .invoice__footer { text-align: center; padding-top: var(--air-space-lg); border-top: 1px solid var(--air-hairline); }
        .invoice__footer p { font-size: .78rem; color: var(--air-muted); margin-bottom: 1rem; }

        .btn-primary {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .7rem 1.75rem; background: var(--air-primary); color: var(--air-on-primary);
            border: none; border-radius: var(--air-radius-sm); font-family: inherit;
            font-size: .9rem; font-weight: 500; cursor: pointer;
            transition: background var(--air-transition);
            height: 48px;
        }
        .btn-primary:hover { background: var(--air-primary-active); }

        .footer {
            background: var(--air-canvas); color: var(--air-muted);
            text-align: center; padding: var(--air-space-lg);
            font-size: .8rem; margin-top: auto; border-top: 1px solid var(--air-hairline);
        }
        .footer strong { color: var(--air-ink); }

        @media (max-width: 520px) {
            .stage { padding: var(--air-space-lg) var(--air-space-base) var(--air-space-xl); }
            .invoice__body { padding: var(--air-space-lg); }
            .invoice__meta { grid-template-columns: 1fr; }
        }

        /* ── Print-friendly ── */
        @media print {
            .navbar, .btn-primary, .footer { display: none !important; }
            .stage { padding: 0; }
            .invoice { border: none; border-radius: 0; box-shadow: none; }
            .invoice__body { padding: 1.5rem; }
            .invoice__strip { display: none; }
        }
    </style>
</head>
<body>

<header>
    <nav class="navbar">
        <div class="navbar__inner">
            <a href="index.php" style="display:flex;align-items:center;gap:.65rem;">
                <img src="logo.png" alt="Logo" class="navbar__logo-icon">
                <span class="navbar__title">Rumah Makan <span>Sipatuo Jr.</span></span>
            </a>
        </div>
    </nav>
</header>

<main class="stage">
    <div class="invoice">
        <div class="invoice__strip"></div>
        <div class="invoice__body">

            <div class="invoice__header">
                <img src="logo.png" alt="Logo" class="invoice__logo">
                <div class="invoice__resto">Rumah Makan <span>Sipatuo Jr.</span></div>
                <div class="invoice__tagline">Cita Rasa Khas Minahasa</div>
            </div>

            <div class="invoice__ref">
                <span class="invoice__order-id">
                    No. Pesanan &nbsp;<strong>#<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></strong>
                </span>
                <span class="invoice__status <?= $st_cls ?>">
                    <?= $status_icon ?> <?= htmlspecialchars($status) ?>
                </span>
            </div>

            <div class="invoice__meta">
                <div>
                    <div class="meta__label">Tanggal</div>
                    <div class="meta__value"><?= tgl_id($pesanan['tanggal_pesanan']) ?></div>
                </div>
                <div>
                    <div class="meta__label">Pembayaran</div>
                    <div class="meta__value"><?= htmlspecialchars($pesanan['metode_pembayaran'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="meta__label">Nama Pelanggan</div>
                    <div class="meta__value"><?= htmlspecialchars($pesanan['nama_pelanggan']) ?></div>
                </div>
                <div>
                    <div class="meta__label">No. Telepon</div>
                    <div class="meta__value"><?= htmlspecialchars($pesanan['no_telepon'] ?? '-') ?></div>
                </div>
                <div class="meta__full">
                    <div class="meta__label">Alamat</div>
                    <div class="meta__value"><?= htmlspecialchars($pesanan['alamat']) ?></div>
                </div>
            </div>

            <table class="invoice__table">
                <thead>
                    <tr>
                        <th>Menu</th>
                        <th class="right">Harga</th>
                        <th class="right">Qty</th>
                        <th class="right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item):
                        $sub = (int) $item['harga'] * (int) $item['jumlah_beli'];
                    ?>
                    <tr>
                        <td class="col-name"><?= htmlspecialchars($item['nama_menu']) ?></td>
                        <td class="right col-price"><?= rupiah((int) $item['harga']) ?></td>
                        <td class="right col-qty"><?= (int) $item['jumlah_beli'] ?>x</td>
                        <td class="right col-sub"><?= rupiah($sub) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="invoice__total">
                <div>
                    <div class="invoice__total-label">Total Pembayaran</div>
                    <div class="invoice__pay-method">via <?= htmlspecialchars($pesanan['metode_pembayaran'] ?? '-') ?></div>
                </div>
                <div class="invoice__total-value"><?= rupiah($total) ?></div>
            </div>

            <div class="invoice__footer">
                <p>Terima kasih telah memesan di Rumah Makan Sipatuo Jr. 🙏<br>
                Pesanan akan segera diproses.</p>
                <a href="index.php">
                    <button class="btn-primary" id="btn-kembali">← Kembali ke Menu</button>
                </a>
            </div>

        </div>
    </div>
</main>

<footer class="footer">
    <p>&copy; <?= date('Y') ?> <strong>Rumah Makan Sipatuo Jr.</strong> — Cita Rasa Khas Minahasa</p>
</footer>

</body>
</html>
