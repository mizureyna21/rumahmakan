<?php
// ============================================================
//  dashboard.php — Panel Admin | Daftar Pesanan & Status
//  Rumah Makan Sipatuo Jr. | Pemrograman Web – Semester 4
// ============================================================
session_start();
include 'koneksi.php';

// ── Flash message dari proses_status.php ─────────────────────
$flash = $_SESSION['flash_status'] ?? null;
unset($_SESSION['flash_status']);

// ── Filter status dari URL (?status=Pending dst.) ────────────
$status_list = ['Pending', 'Dimasak', 'Dikirim', 'Selesai'];
$filter_aktif = isset($_GET['status']) && in_array($_GET['status'], $status_list, true)
    ? $_GET['status'] : '';

// ── Filter periode dari URL ─────────────────────────────────
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : '';

// ── Query utama dengan optional filter status & periode ───────
$where_clauses = [];
if ($filter_aktif) {
    $where_clauses[] = "p.status = '" . mysqli_real_escape_string($koneksi, $filter_aktif) . "'";
}
if ($filter_bulan !== '') {
    $where_clauses[] = "MONTH(p.tanggal_pesanan) = '" . mysqli_real_escape_string($koneksi, $filter_bulan) . "'";
}
if ($filter_tahun !== '') {
    $where_clauses[] = "YEAR(p.tanggal_pesanan) = '" . mysqli_real_escape_string($koneksi, $filter_tahun) . "'";
}

$where = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$query = "
    SELECT
        p.id_pesanan,
        p.tanggal_pesanan,
        p.nama_pelanggan,
        p.no_telepon,
        p.alamat,
        p.status,
        p.metode_pembayaran,
        p.bukti_transfer,
        m.nama_menu,
        dp.jumlah_beli,
        p.total_harga
    FROM pesanan p
    INNER JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    INNER JOIN menu            m  ON dp.id_menu   = m.id_menu
    $where
    ORDER BY p.tanggal_pesanan DESC
";

$result = mysqli_query($koneksi, $query);
$db_error = null;
if (!$result) {
    $db_error = mysqli_error($koneksi);
}

// ── Statistik ringkas ────────────────────────────────────────
$total_pesanan = 0;
$total_omzet = 0;
$pesanan_hari_ini = 0;
$stat_per_status = array_fill_keys($status_list, 0); // ['Pending'=>0, ...]

if (!$db_error) {
    // Stat where clause (tanpa filter status, tapi pakai filter periode)
    $stat_where_clauses = [];
    if ($filter_bulan !== '') {
        $stat_where_clauses[] = "MONTH(tanggal_pesanan) = '" . mysqli_real_escape_string($koneksi, $filter_bulan) . "'";
    }
    if ($filter_tahun !== '') {
        $stat_where_clauses[] = "YEAR(tanggal_pesanan) = '" . mysqli_real_escape_string($koneksi, $filter_tahun) . "'";
    }
    $stat_where = !empty($stat_where_clauses) ? 'WHERE ' . implode(' AND ', $stat_where_clauses) : '';

    $stat = mysqli_query(
        $koneksi,
        "SELECT COUNT(*) AS jml, COALESCE(SUM(total_harga),0) AS omzet FROM pesanan $stat_where"
    );
    if ($stat) {
        $r = mysqli_fetch_assoc($stat);
        $total_pesanan = (int) $r['jml'];
        $total_omzet = (int) $r['omzet'];
    }
    
    // Pesanan hari ini (tetap hari ini, tapi di dalam periode yang difilter jika ada)
    $hari_where = "DATE(tanggal_pesanan) = CURDATE()";
    if (!empty($stat_where_clauses)) {
        $hari_where .= " AND " . implode(' AND ', $stat_where_clauses);
    }
    $hari = mysqli_query(
        $koneksi,
        "SELECT COUNT(*) AS jml FROM pesanan WHERE $hari_where"
    );
    if ($hari) {
        $r = mysqli_fetch_assoc($hari);
        $pesanan_hari_ini = (int) $r['jml'];
    }
    
    // Hitung per-status
    $qs = mysqli_query(
        $koneksi,
        "SELECT status, COUNT(*) AS jml FROM pesanan $stat_where GROUP BY status"
    );
    if ($qs) {
        while ($r = mysqli_fetch_assoc($qs)) {
            if (isset($stat_per_status[$r['status']])) {
                $stat_per_status[$r['status']] = (int) $r['jml'];
            }
        }
    }
}

// ── 5 Menu Paling Laris ──────────────────────────────────────
$best_seller = [];
if (!$db_error) {
    $bs_query = "
        SELECT m.nama_menu, SUM(dp.jumlah_beli) AS total_terjual
        FROM detail_pesanan dp
        JOIN menu m ON dp.id_menu = m.id_menu
        GROUP BY m.id_menu
        ORDER BY total_terjual DESC
        LIMIT 5
    ";
    $bs_result = mysqli_query($koneksi, $bs_query);
    if ($bs_result) {
        $best_seller = $bs_result->fetch_all(MYSQLI_ASSOC);
    }
}

// ── Helper: format Rupiah ────────────────────────────────────
function rupiah(int $n): string
{
    return 'Rp ' . number_format($n, 0, ',', '.');
}

// ── Helper: format tanggal Indonesia ────────────────────────
function tanggal_id(string $dt): string
{
    $bulan = [
        '01' => 'Jan',
        '02' => 'Feb',
        '03' => 'Mar',
        '04' => 'Apr',
        '05' => 'Mei',
        '06' => 'Jun',
        '07' => 'Jul',
        '08' => 'Agu',
        '09' => 'Sep',
        '10' => 'Okt',
        '11' => 'Nov',
        '12' => 'Des',
    ];
    $ts = strtotime($dt);
    $m = date('m', $ts);
    return date('d', $ts) . ' ' . $bulan[$m] . ' ' . date('Y', $ts) . ' · ' . date('H:i', $ts);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard admin Rumah Makan Sipatuo Jr. – Kelola dan pantau pesanan yang masuk.">
    <title>Dashboard Admin – Rumah Makan Sipatuo Jr.</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        /* ══════════════════════════════════════════════
           Airbnb Design Tokens (from DESIGN.md)
        ══════════════════════════════════════════════ */
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
            --sidebar-w: 230px;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, system-ui, Roboto, 'Helvetica Neue', sans-serif;
            background: var(--air-surface-soft);
            color: var(--air-ink);
            min-height: 100vh;
            display: flex;
            -webkit-font-smoothing: antialiased;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* ══════════════════════════════════════════════
           SIDEBAR – Dark nav with Rausch active
        ══════════════════════════════════════════════ */
        .sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: #1a1a2e;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            align-self: flex-start;
            height: 100vh;
        }

        .sidebar__brand {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: 1.4rem 1.2rem 1.2rem;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }

        .sidebar__logo {
            width: 38px;
            height: 38px;
            background: var(--air-primary);
            border-radius: var(--air-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .sidebar__title {
            font-size: .9rem;
            font-weight: 600;
            color: #fff;
            line-height: 1.25;
        }

        .sidebar__title small {
            display: block;
            font-size: .68rem;
            font-weight: 400;
            color: rgba(255,255,255,.45);
            margin-top: 1px;
        }

        .sidebar__nav {
            flex: 1;
            padding: 1rem .75rem;
            display: flex;
            flex-direction: column;
            gap: .2rem;
        }

        .nav-label {
            font-size: .65rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: rgba(255,255,255,.3);
            padding: .6rem .5rem .3rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .55rem .85rem;
            border-radius: var(--air-radius-sm);
            font-size: .85rem;
            font-weight: 500;
            color: rgba(255,255,255,.55);
            transition: background var(--air-transition), color var(--air-transition);
        }

        .nav-item:hover {
            background: rgba(255,255,255,.06);
            color: rgba(255,255,255,.9);
        }

        .nav-item.active {
            background: var(--air-primary);
            color: #fff;
        }

        .nav-item .nav-icon {
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }

        .sidebar__footer {
            padding: 1rem 1.2rem;
            border-top: 1px solid rgba(255,255,255,.07);
            font-size: .75rem;
            color: rgba(255,255,255,.3);
        }

        /* ══════════════════════════════════════════════
           MAIN PANEL
        ══════════════════════════════════════════════ */
        .main-panel {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        /* ── Topbar ─────────────────────────────────── */
        .topbar {
            background: var(--air-canvas);
            border-bottom: 1px solid var(--air-hairline);
            padding: 0 var(--air-space-xl);
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar__breadcrumb {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: .85rem;
            color: var(--air-muted);
        }

        .topbar__breadcrumb .current {
            font-weight: 600;
            color: var(--air-ink);
        }

        .topbar__sep {
            color: var(--air-hairline);
        }

        .topbar__actions {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .badge-live {
            display: flex;
            align-items: center;
            gap: .35rem;
            font-size: .75rem;
            font-weight: 500;
            color: #15803d;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: .25rem .7rem;
            border-radius: var(--air-radius-full);
        }

        .badge-live .dot {
            width: 6px;
            height: 6px;
            background: #15803d;
            border-radius: 50%;
            animation: pulse 1.8s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%      { opacity: .5; transform: scale(1.4); }
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .5rem 1.1rem;
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-sm);
            background: transparent;
            font-family: inherit;
            font-size: .85rem;
            font-weight: 500;
            color: var(--air-ink);
            cursor: pointer;
            transition: border-color var(--air-transition);
            text-decoration: none;
        }

        .btn-outline:hover {
            border-color: var(--air-ink);
        }

        /* ══════════════════════════════════════════════
           CONTENT AREA
        ══════════════════════════════════════════════ */
        .content {
            padding: var(--air-space-xl);
            flex: 1;
        }

        .page-heading {
            margin-bottom: 1.5rem;
        }

        .page-heading h1 {
            font-size: 1.4rem;
            font-weight: 600;
            letter-spacing: -.4px;
            margin-bottom: .25rem;
        }

        .page-heading p {
            font-size: .88rem;
            color: var(--air-muted);
        }

        /* ══════════════════════════════════════════════
           STATS CARDS
        ══════════════════════════════════════════════ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--air-space-base);
            margin-bottom: var(--air-space-xl);
        }

        .stat-card {
            background: var(--air-canvas);
            border-radius: var(--air-radius-md);
            padding: 1.2rem 1.4rem;
            border: 1px solid var(--air-hairline);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: box-shadow var(--air-transition);
        }

        .stat-card:hover {
            box-shadow: var(--air-shadow-hover);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--air-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .stat-icon.orange {
            background: #fef2f2;
        }

        .stat-icon.green {
            background: #f0fdf4;
        }

        .stat-icon.blue {
            background: #f0f9ff;
        }

        .stat-icon.purple {
            background: #f5f3ff;
        }

        .stat-info {
            min-width: 0;
        }

        .stat-label {
            font-size: .73rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--air-muted);
            margin-bottom: .2rem;
        }

        .stat-value {
            font-size: 1.35rem;
            font-weight: 600;
            color: var(--air-ink);
            letter-spacing: -.5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .stat-sub {
            font-size: .7rem;
            color: var(--air-muted);
            margin-top: .15rem;
            font-weight: 500;
        }

        /* ══════════════════════════════════════════════
           BEST SELLER CARD
        ══════════════════════════════════════════════ */
        .bestseller-wrap {
            margin-bottom: 1.5rem;
        }

        .bestseller-card {
            background: var(--air-canvas);
            border-radius: var(--air-radius-md);
            border: 1px solid var(--air-hairline);
            overflow: hidden;
        }

        .bestseller-card__header {
            padding: 1rem 1.4rem;
            border-bottom: 1px solid var(--air-hairline-soft);
            display: flex;
            align-items: center;
            gap: .6rem;
        }

        .bestseller-card__header-icon {
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .bestseller-card__header h3 {
            font-size: .95rem;
            font-weight: 600;
            letter-spacing: -.2px;
            color: var(--air-ink);
        }

        .bestseller-card__header span {
            margin-left: auto;
            font-size: .75rem;
            font-weight: 500;
            color: var(--air-muted);
            background: var(--air-surface-soft);
            padding: .2rem .65rem;
            border-radius: var(--air-radius-full);
            border: 1px solid var(--air-hairline);
        }

        .bestseller-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .bestseller-item {
            display: flex;
            align-items: center;
            gap: .85rem;
            padding: .7rem 1.4rem;
            border-bottom: 1px solid var(--air-hairline-soft);
            transition: background var(--air-transition);
        }

        .bestseller-item:last-child {
            border-bottom: none;
        }

        .bestseller-item:hover {
            background: var(--air-surface-soft);
        }

        .bestseller-rank {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .75rem;
            font-weight: 600;
            flex-shrink: 0;
        }

        .bestseller-name {
            flex: 1;
            font-size: .88rem;
            font-weight: 500;
            color: var(--air-ink);
            min-width: 0;
        }

        .bestseller-qty {
            display: flex;
            align-items: center;
            gap: .35rem;
            font-size: .82rem;
            font-weight: 600;
            color: var(--air-muted);
            white-space: nowrap;
        }

        .bestseller-qty small {
            font-size: .7rem;
            font-weight: 500;
            color: var(--air-muted-soft);
        }

        .bestseller-empty {
            padding: var(--air-space-xl) 1.4rem;
            text-align: center;
            color: var(--air-muted);
            font-size: .85rem;
        }

        /* ══════════════════════════════════════════════
           PERIODE FILTER
        ══════════════════════════════════════════════ */
        .periode-filter {
            display: flex;
            align-items: flex-end;
            gap: var(--air-space-lg);
            margin-bottom: var(--air-space-lg);
            background: var(--air-canvas);
            padding: var(--air-space-lg);
            border-radius: var(--air-radius-md);
            border: 1px solid var(--air-hairline);
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: .45rem;
        }

        .filter-group label {
            font-size: .72rem;
            font-weight: 600;
            color: var(--air-muted);
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .form-select {
            appearance: none;
            padding: .6rem 1rem;
            padding-right: 2.2rem;
            border-radius: var(--air-radius-sm);
            border: 1px solid var(--air-hairline);
            font-family: inherit;
            font-size: .88rem;
            font-weight: 500;
            color: var(--air-ink);
            background: var(--air-canvas) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath d='M0 0l6 8 6-8z' fill='%236a6a6a'/%3E%3C/svg%3E") no-repeat right .8rem center;
            cursor: pointer;
            min-width: 160px;
            transition: border-color var(--air-transition);
            outline: none;
        }

        .form-select:focus {
            border-color: var(--air-ink);
            border-width: 2px;
        }

        /* ══════════════════════════════════════════════
           STATUS FILTER PILLS
        ══════════════════════════════════════════════ */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: .45rem;
            flex-wrap: wrap;
            margin-bottom: var(--air-space-base);
        }

        .filter-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .4rem 1rem;
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-full);
            font-family: inherit;
            font-size: .82rem;
            font-weight: 500;
            color: var(--air-muted);
            background: var(--air-canvas);
            cursor: pointer;
            text-decoration: none;
            transition: all var(--air-transition);
        }

        .filter-pill:hover {
            border-color: var(--air-ink);
            color: var(--air-ink);
        }

        .filter-pill.active {
            background: var(--air-ink);
            border-color: var(--air-ink);
            color: var(--air-on-primary);
        }

        .filter-pill .pill-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: var(--air-radius-full);
            font-size: .68rem;
            font-weight: 500;
            background: rgba(0,0,0,.08);
            color: inherit;
        }

        .filter-pill.active .pill-count {
            background: rgba(255,255,255,.2);
        }

        /* ══════════════════════════════════════════════
           FLASH ALERT
        ══════════════════════════════════════════════ */
        .flash-alert {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .75rem 1.2rem;
            border-radius: var(--air-radius-sm);
            margin-bottom: var(--air-space-lg);
            font-size: .875rem;
            font-weight: 500;
            border: 1px solid;
            animation: slideDown .3s ease;
        }

        .flash-alert.success {
            background: #f0fdf4;
            color: #15803d;
            border-color: #bbf7d0;
        }

        .flash-alert.error {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ══════════════════════════════════════════════
           TABLE CARD – White surface, hairline borders
        ══════════════════════════════════════════════ */
        .table-card {
            background: var(--air-canvas);
            border-radius: var(--air-radius-md);
            border: 1px solid var(--air-hairline);
            overflow: hidden;
        }

        .table-card__header {
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 1px solid var(--air-hairline);
        }

        .table-card__title {
            display: flex;
            align-items: center;
            gap: .55rem;
        }

        .table-card__title-icon {
            width: 32px;
            height: 32px;
            background: var(--air-surface-soft);
            border-radius: var(--air-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .table-card__title h2 {
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: -.2px;
        }

        .table-card__count {
            font-size: .75rem;
            font-weight: 500;
            color: var(--air-muted);
            background: var(--air-surface-soft);
            border: 1px solid var(--air-hairline);
            padding: .2rem .7rem;
            border-radius: var(--air-radius-full);
        }

        .table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .85rem;
        }

        .orders-table thead tr {
            border-bottom: 1px solid var(--air-hairline);
        }

        .orders-table thead th {
            padding: .75rem 1rem;
            text-align: left;
            font-size: .7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--air-muted);
            white-space: nowrap;
        }

        .orders-table thead th.center { text-align: center; }
        .orders-table thead th.right  { text-align: right; }

        .orders-table tbody tr {
            border-bottom: 1px solid var(--air-hairline-soft);
            transition: background var(--air-transition);
        }

        .orders-table tbody tr:last-child {
            border-bottom: none;
        }

        .orders-table tbody tr:hover {
            background: var(--air-surface-soft);
        }

        .orders-table tbody td {
            padding: .75rem 1rem;
            color: var(--air-ink);
            vertical-align: middle;
        }

        .orders-table tbody td.center { text-align: center; }
        .orders-table tbody td.right  { text-align: right; }

        /* ── Cell helpers ───────────────────────────── */
        .cell-id {
            font-size: .75rem;
            font-weight: 600;
            color: var(--air-muted);
            background: var(--air-surface-soft);
            border: 1px solid var(--air-hairline);
            padding: .2rem .55rem;
            border-radius: var(--air-radius-xs);
            display: inline-block;
        }

        .cell-date {
            font-size: .78rem;
            color: var(--air-muted);
            white-space: nowrap;
        }

        .cell-date strong {
            display: block;
            font-size: .85rem;
            font-weight: 600;
            color: var(--air-ink);
            margin-bottom: 1px;
        }

        .cell-pelanggan {
            font-weight: 600;
        }

        .cell-menu {
            font-weight: 500;
            color: var(--air-ink);
        }

        .cell-qty {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 30px;
            padding: .15rem .55rem;
            background: var(--air-surface-soft);
            color: var(--air-muted);
            border-radius: var(--air-radius-full);
            font-size: .78rem;
            font-weight: 600;
        }

        .cell-total {
            font-weight: 600;
            color: var(--air-primary);
            font-size: .9rem;
            white-space: nowrap;
        }

        /* ── Table footer ───────────────────────────── */
        .table-card__footer {
            padding: .85rem 1.5rem;
            border-top: 1px solid var(--air-hairline);
            background: var(--air-surface-soft);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .tfoot-info {
            font-size: .8rem;
            color: var(--air-muted);
            font-weight: 500;
        }

        .tfoot-total {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .tfoot-total__label {
            font-size: .8rem;
            font-weight: 600;
            color: var(--air-muted);
        }

        .tfoot-total__value {
            font-size: 1rem;
            font-weight: 600;
            color: #15803d;
        }

        /* ══════════════════════════════════════════════
           STATES: EMPTY & ERROR
        ══════════════════════════════════════════════ */
        .state-box {
            padding: 3rem 2rem;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .65rem;
        }

        .state-box .state-icon {
            font-size: 3rem;
        }

        .state-box h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--air-ink);
        }

        .state-box p {
            font-size: .88rem;
            color: var(--air-muted);
            max-width: 320px;
        }

        .state-box.error h3 {
            color: #dc2626;
        }

        .state-box.error p {
            color: #b91c1c;
            font-family: monospace;
        }

        /* ══════════════════════════════════════════════
           STATUS STEPPER
        ══════════════════════════════════════════════ */
        .status-stepper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .3rem;
            min-width: 130px;
        }
        .stepper-dots {
            display: flex;
            align-items: center;
            gap: 0;
        }
        .stepper-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--air-hairline);
            border: 2px solid var(--air-hairline);
            flex-shrink: 0;
            transition: background var(--air-transition), border-color var(--air-transition);
        }
        .stepper-dot.done {
            background: var(--air-primary);
            border-color: var(--air-primary);
        }
        .stepper-line {
            width: 20px;
            height: 2px;
            background: var(--air-hairline);
            flex-shrink: 0;
            transition: background var(--air-transition);
        }
        .stepper-line.done {
            background: var(--air-primary);
        }
        .stepper-label {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .02em;
            padding: .15rem .6rem;
            border-radius: var(--air-radius-full);
            border: 1px solid;
        }
        .stepper-label.pending  { background:#fff7ed; color:#c2410c; border-color:#fed7aa; }
        .stepper-label.dimasak  { background:#fefce8; color:#a16207; border-color:#fde047; }
        .stepper-label.dikirim  { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
        .stepper-label.selesai  { background:#f0fdf4; color:#15803d; border-color:#bbf7d0; }
        .stepper-actions {
            display: flex;
            align-items: center;
            gap: .3rem;
        }
        .btn-next-status {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .3rem .7rem;
            background: var(--air-primary);
            color: #fff;
            border: none;
            border-radius: var(--air-radius-sm);
            font-family: inherit;
            font-size: .72rem;
            font-weight: 600;
            cursor: pointer;
            transition: background var(--air-transition), opacity var(--air-transition);
            min-height: 30px;
        }
        .btn-next-status:hover { background: var(--air-primary-active); }
        .btn-next-status:disabled { opacity: .5; cursor: not-allowed; }
        .btn-ubah-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-sm);
            background: var(--air-canvas);
            color: var(--air-muted);
            font-size: .8rem;
            cursor: pointer;
            transition: border-color var(--air-transition), color var(--air-transition);
            padding: 0;
            font-family: inherit;
        }
        .btn-ubah-status:hover { border-color: var(--air-ink); color: var(--air-ink); }

        /* ══════════════════════════════════════════════
           MINI MODAL — Konfirmasi & Ubah Status Manual
        ══════════════════════════════════════════════ */
        .mini-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 200;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .mini-modal-overlay.active { display: flex; }
        .mini-modal {
            background: var(--air-canvas);
            border-radius: var(--air-radius-md);
            padding: 1.5rem;
            max-width: 360px;
            width: 100%;
            box-shadow: var(--air-shadow-hover);
            animation: modalIn .15s ease;
        }
        .mini-modal__icon { font-size: 2rem; margin-bottom: .5rem; text-align: center; }
        .mini-modal__title { font-size: 1rem; font-weight: 700; margin-bottom: .25rem; text-align: center; }
        .mini-modal__desc  { font-size: .85rem; color: var(--air-muted); margin-bottom: 1.25rem; text-align: center; }
        .status-option-list {
            display: flex;
            flex-direction: column;
            gap: .5rem;
            margin-bottom: 1rem;
        }
        .status-option-btn {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .65rem 1rem;
            border: 2px solid var(--air-hairline);
            border-radius: var(--air-radius-sm);
            background: var(--air-canvas);
            font-family: inherit;
            font-size: .875rem;
            font-weight: 500;
            cursor: pointer;
            text-align: left;
            transition: border-color var(--air-transition), background var(--air-transition);
            min-height: 48px;
        }
        .status-option-btn:hover    { border-color: var(--air-ink); }
        .status-option-btn.aktif    { border-color: var(--air-primary); background: #fff5f7; font-weight: 700; }
        .status-option-btn .opt-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        .opt-dot.pending  { background: #c2410c; }
        .opt-dot.dimasak  { background: #a16207; }
        .opt-dot.dikirim  { background: #1d4ed8; }
        .opt-dot.selesai  { background: #15803d; }
        .mini-modal__cancel {
            display: block;
            width: 100%;
            padding: .6rem;
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-sm);
            background: transparent;
            font-family: inherit;
            font-size: .875rem;
            color: var(--air-muted);
            cursor: pointer;
            transition: border-color var(--air-transition);
            min-height: 44px;
        }
        .mini-modal__cancel:hover { border-color: var(--air-ink); color: var(--air-ink); }
        .mini-modal__confirm {
            display: flex;
            gap: .5rem;
            margin-top: .75rem;
        }
        .mini-modal__confirm .btn-confirm-ya {
            flex: 1;
            padding: .65rem;
            background: var(--air-primary);
            color: #fff;
            border: none;
            border-radius: var(--air-radius-sm);
            font-family: inherit;
            font-size: .875rem;
            font-weight: 600;
            cursor: pointer;
            min-height: 44px;
            transition: background var(--air-transition);
        }
        .mini-modal__confirm .btn-confirm-ya:hover { background: var(--air-primary-active); }
        .mini-modal__confirm .btn-confirm-tidak {
            flex: 1;
            padding: .65rem;
            background: var(--air-canvas);
            color: var(--air-ink);
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-sm);
            font-family: inherit;
            font-size: .875rem;
            font-weight: 500;
            cursor: pointer;
            min-height: 44px;
            transition: border-color var(--air-transition);
        }
        .mini-modal__confirm .btn-confirm-tidak:hover { border-color: var(--air-ink); }

        /* ══════════════════════════════════════════════
           PAYMENT BADGES
        ══════════════════════════════════════════════ */
        .badge-payment {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            font-size: .7rem;
            font-weight: 600;
            padding: .2rem .6rem;
            border-radius: var(--air-radius-full);
            white-space: nowrap;
            border: 1px solid;
        }
        .badge-dana  { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .badge-ovo   { background: #faf5ff; color: #7c3aed; border-color: #e9d5ff; }
        .badge-gopay { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
        .badge-qris  { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
        .badge-cod   { background: #f3f4f6; color: #4b5563; border-color: #d1d5db; }
        .badge-shopee { background: #fff7ed; color: #ea580c; border-color: #ffedd5; }
        .badge-other { background: #f3f4f6; color: #6b7280; border-color: #e5e7eb; }

        .btn-bukti {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .3rem .7rem;
            font-family: inherit;
            font-size: .75rem;
            font-weight: 500;
            color: var(--air-ink);
            background: var(--air-canvas);
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-sm);
            cursor: pointer;
            transition: border-color var(--air-transition);
        }

        .btn-bukti:hover {
            border-color: var(--air-ink);
        }

        .no-bukti {
            font-size: .75rem;
            color: var(--air-muted-soft);
            font-style: italic;
        }

        /* ══════════════════════════════════════════════
           MODAL BUKTI TRANSFER – Airbnb style
        ══════════════════════════════════════════════ */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: var(--air-scrim);
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            animation: fadeIn .2s ease;
        }

        .modal-overlay.active {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        .modal-card {
            background: var(--air-canvas);
            border-radius: var(--air-radius-md);
            max-width: 520px;
            width: 100%;
            overflow: hidden;
            animation: modalIn .25s cubic-bezier(.34,1.56,.64,1);
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(.92) translateY(16px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--air-hairline);
        }

        .modal-header h3 {
            font-size: .9rem;
            font-weight: 600;
            color: var(--air-ink);
        }

        .modal-close {
            width: 32px;
            height: 32px;
            border: none;
            background: var(--air-surface-strong);
            border-radius: 50%;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--air-ink);
            transition: background var(--air-transition);
        }

        .modal-close:hover {
            background: var(--air-hairline);
        }

        .modal-body {
            padding: var(--air-space-lg);
            text-align: center;
        }

        .modal-body img {
            max-width: 100%;
            max-height: 400px;
            border-radius: var(--air-radius-sm);
            border: 1px solid var(--air-hairline-soft);
        }

        /* ══════════════════════════════════════════════
           BOTTOM NAV – mobile replacement for sidebar
        ══════════════════════════════════════════════ */
        .bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: var(--air-canvas);
            border-top: 1px solid var(--air-hairline);
            box-shadow: 0 -2px 8px rgba(0,0,0,.06);
            height: 64px;
            flex-shrink: 0;
        }
        .bottom-nav__inner {
            max-width: 1280px;
            margin: 0 auto;
            height: 100%;
            display: flex;
            align-items: stretch;
        }
        .bottom-nav__item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            padding: 6px 4px;
            color: var(--air-muted);
            font-size: .62rem;
            font-weight: 600;
            text-decoration: none;
            transition: color var(--air-transition);
            position: relative;
        }
        .bottom-nav__item.active {
            color: var(--air-primary);
        }
        .bottom-nav__item.active::after {
            content: '';
            position: absolute;
            top: 0;
            left: 20%;
            right: 20%;
            height: 2px;
            background: var(--air-primary);
            border-radius: 0 0 2px 2px;
        }
        .bottom-nav__icon {
            font-size: 1.2rem;
            line-height: 1;
        }
        .bottom-nav__label {
            line-height: 1.1;
        }

        /* ══════════════════════════════════════════════
           RESPONSIVE
        ══════════════════════════════════════════════ */
        @media (max-width: 840px) {
            .sidebar { display: none; }
            .main-panel { padding-bottom: 64px; }
            .content { padding: var(--air-space-lg); }
            .topbar  { padding: 0 var(--air-space-lg); }
            .bottom-nav { display: flex; }
        }

        @media (max-width: 600px) {
            .topbar__breadcrumb { display: none; }
            .badge-live { display: none; }
            .topbar .btn-outline { font-size: .8rem; padding: .4rem .8rem; }

            .periode-filter {
                flex-direction: column;
                align-items: stretch;
                gap: var(--air-space-sm);
            }
            .periode-filter .filter-group { width: 100%; }
            .periode-filter .form-select { width: 100%; min-width: 0; }
            .periode-filter .btn-outline {
                width: 100%;
                text-align: center;
                justify-content: center;
                box-sizing: border-box;
            }

            .table-wrap { overflow-x: visible; }
            .orders-table,
            .orders-table tbody,
            .orders-table tbody tr { display: block; }
            .orders-table thead { display: none; }
            .orders-table tbody tr {
                border: 1px solid var(--air-hairline);
                border-radius: var(--air-radius-md);
                margin-bottom: var(--air-space-base);
                padding: var(--air-space-base);
                background: var(--air-canvas);
            }
            .orders-table tbody tr:last-child { margin-bottom: 0; }
            .orders-table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: .45rem 0;
                border-bottom: 1px solid var(--air-hairline-soft);
                text-align: right;
                gap: .5rem;
            }
            .orders-table tbody td:last-child { border-bottom: none; }
            .orders-table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                font-size: .7rem;
                color: var(--air-muted);
                text-transform: uppercase;
                letter-spacing: .04em;
                white-space: nowrap;
                flex-shrink: 0;
            }
            .orders-table tbody td.center { text-align: right; justify-content: space-between; }
            .orders-table tbody td.right  { text-align: right; }
            .orders-table tbody td .cell-id { display: inline-flex; }
            .orders-table tbody td .cell-date strong { display: inline; }
            .orders-table tbody td .cell-date br { display: none; }
            .orders-table tbody td .stepper-dots { transform: scale(.85); transform-origin: center; }
            .orders-table tbody td .stepper-label { font-size: .65rem; }
            .orders-table tbody td .btn-next-status { font-size: .65rem; padding: .25rem .55rem; min-height: 28px; }

            .table-card__footer {
                flex-direction: column;
                text-align: center;
                gap: .5rem;
            }
            .tfoot-total { justify-content: center; }

            .content { padding: var(--air-space-base); }
            .page-heading h1 { font-size: 1.15rem; }
        }

        @media (max-width: 480px) {
            .filter-pill {
                padding: .3rem .7rem;
                font-size: .78rem;
            }
            .filter-pill .pill-count { display: none; }
            .page-heading h1 { font-size: 1rem; }
            .stat-value { font-size: 1.1rem; }
        }

        @media (max-width: 400px) {
            .stats-grid { grid-template-columns: 1fr; }
            .stat-value {
                white-space: normal;
                word-break: break-word;
            }
        }
    </style>
</head>

<body>

    <!-- ══════════════════════════════════════════════════════════
     SIDEBAR
══════════════════════════════════════════════════════════ -->
    <aside class="sidebar" aria-label="Navigasi admin">

        <div class="sidebar__brand">
            <div class="sidebar__logo" aria-hidden="true">🍽️</div>
            <div class="sidebar__title">
                RM Sipatuo Jr.
                <small>Panel Admin</small>
            </div>
        </div>

        <nav class="sidebar__nav">
            <span class="nav-label">Menu Utama</span>
            <a href="dashboard.php" class="nav-item active" id="nav-dashboard">
                <span class="nav-icon">📊</span> Dashboard
            </a>
            <a href="index.php" class="nav-item" id="nav-katalog">
                <span class="nav-icon">🛍️</span> Katalog Menu
            </a>

            <span class="nav-label" style="margin-top:.5rem;">Manajemen</span>
            <a href="kelola_menu.php" class="nav-item" id="nav-kelola">
                <span class="nav-icon">🍴</span> Kelola Menu
            </a>
        </nav>

        <div class="sidebar__footer">
            &copy; <?= date('Y') ?> RM Sipatuo Jr.
        </div>

    </aside>

    <!-- ══════════════════════════════════════════════════════════
     MAIN PANEL
══════════════════════════════════════════════════════════ -->
    <div class="main-panel">

        <!-- ── Topbar ──────────────────────────────────────────── -->
        <header class="topbar" aria-label="Topbar admin">
            <div class="topbar__breadcrumb">
                <span>Admin</span>
                <span class="topbar__sep">›</span>
                <span class="current">Daftar Pesanan</span>
            </div>
            <div class="topbar__actions">
                <div class="badge-live">
                    <span class="dot" aria-hidden="true"></span>
                    Live
                </div>
                <a href="index.php" class="btn-outline" id="btn-lihat-katalog">
                    🛍️ Lihat Katalog
                </a>
            </div>
        </header>

        <!-- ── Content ─────────────────────────────────────────── -->
        <main class="content" id="dashboard-content">

            <!-- Page heading -->
            <div class="page-heading">
                <h1>Dashboard Admin – Daftar Pesanan</h1>
                <p>Pantau dan perbarui status pesanan yang masuk dari pelanggan secara real-time.</p>
            </div>

            <?php if ($flash): ?>
                <div class="flash-alert <?= $flash['type'] ?>" role="alert" id="flash-msg">
                    <?= $flash['type'] === 'success' ? '✅' : '❌' ?>
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
            <?php endif; ?>

            <!-- ── Stats Cards ─────────────────────────────────── -->
            <div class="stats-grid" aria-label="Statistik ringkas">

                <div class="stat-card" id="stat-total-pesanan">
                    <div class="stat-icon orange" aria-hidden="true">📋</div>
                    <div class="stat-info">
                        <div class="stat-label">Total Pesanan</div>
                        <div class="stat-value"><?= $total_pesanan ?></div>
                    </div>
                </div>

                <div class="stat-card" id="stat-omzet">
                    <div class="stat-icon green" aria-hidden="true">💰</div>
                    <div class="stat-info">
                        <div class="stat-label">Total Omzet</div>
                        <div class="stat-value" title="<?= rupiah($total_omzet) ?>">
                            <?= rupiah($total_omzet) ?>
                        </div>
                    </div>
                </div>

                <div class="stat-card" id="stat-hari-ini">
                    <div class="stat-icon blue" aria-hidden="true">📅</div>
                    <div class="stat-info">
                        <div class="stat-label">Hari Ini</div>
                        <div class="stat-value"><?= $pesanan_hari_ini ?> pesanan</div>
                        <div class="stat-sub">🟡 Pending: <?= $stat_per_status['Pending'] ?> &nbsp;·&nbsp; ✅ Selesai:
                            <?= $stat_per_status['Selesai'] ?>
                        </div>
                    </div>
                </div>

                <div class="stat-card" id="stat-tanggal">
                    <div class="stat-icon purple" aria-hidden="true">🕐</div>
                    <div class="stat-info">
                        <div class="stat-label">Diperbarui</div>
                        <div class="stat-value" style="font-size:1rem;">
                            <?= date('H:i') ?> WIB
                        </div>
                    </div>
                </div>

            </div>

            <!-- ── Best Seller ──────────────────────────────────── -->
            <div class="bestseller-wrap">
                <div class="bestseller-card">
                    <div class="bestseller-card__header">
                        <span class="bestseller-card__header-icon">🏆</span>
                        <h3>Menu Paling Laris</h3>
                        <span>Top 5</span>
                    </div>
                    <?php if (empty($best_seller)): ?>
                        <div class="bestseller-empty">Belum ada data penjualan.</div>
                    <?php else: ?>
                    <ul class="bestseller-list">
                        <?php $i = 1; ?>
                        <?php foreach ($best_seller as $bs): ?>
                        <li class="bestseller-item">
                            <span class="bestseller-rank rank-<?= $i ?>"><?= $i ?></span>
                            <span class="bestseller-name"><?= htmlspecialchars($bs['nama_menu']) ?></span>
                            <span class="bestseller-qty">
                                <?= (int) $bs['total_terjual'] ?> <small>terjual</small>
                            </span>
                        </li>
                        <?php $i++; ?>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Filter Periode ──────────────────────────────── -->
            <form method="GET" action="dashboard.php" class="periode-filter" id="form-periode">
                <?php if ($filter_aktif): ?>
                    <input type="hidden" name="status" value="<?= htmlspecialchars($filter_aktif) ?>">
                <?php endif; ?>

                <div class="filter-group">
                    <label for="filter_bulan">Bulan</label>
                    <select name="bulan" id="filter_bulan" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Bulan</option>
                        <?php
                        $nama_bulan = [
                            '1' => 'Januari', '2' => 'Februari', '3' => 'Maret',
                            '4' => 'April', '5' => 'Mei', '6' => 'Juni',
                            '7' => 'Juli', '8' => 'Agustus', '9' => 'September',
                            '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                        ];
                        foreach ($nama_bulan as $num => $name):
                            $sel = ($filter_bulan === (string)$num) ? 'selected' : '';
                        ?>
                            <option value="<?= $num ?>" <?= $sel ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filter_tahun">Tahun</label>
                    <select name="tahun" id="filter_tahun" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Tahun</option>
                        <?php
                        $tahun_sekarang = date('Y');
                        for ($y = $tahun_sekarang + 1; $y >= 2020; $y--):
                            $sel = ($filter_tahun === (string)$y) ? 'selected' : '';
                        ?>
                            <option value="<?= $y ?>" <?= $sel ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <?php if ($filter_bulan !== '' || $filter_tahun !== ''): ?>
                <div class="filter-group">
                    <a href="dashboard.php<?= $filter_aktif ? '?status='.urlencode($filter_aktif) : '' ?>" class="btn-outline" style="padding: .65rem 1.1rem; height: 100%;">Reset Filter</a>
                </div>
                <?php endif; ?>
            </form>

            <!-- ── Filter Bar ──────────────────────────────────── -->
            <?php
            $base_params = [];
            if ($filter_bulan !== '') $base_params['bulan'] = $filter_bulan;
            if ($filter_tahun !== '') $base_params['tahun'] = $filter_tahun;
            
            $semua_qs = http_build_query($base_params);
            $semua_href = "dashboard.php" . ($semua_qs ? '?' . $semua_qs : '');
            ?>
            <div class="filter-bar" id="filter-bar-status" role="navigation" aria-label="Filter status pesanan">
                <a href="<?= htmlspecialchars($semua_href) ?>" class="filter-pill <?= $filter_aktif === '' ? 'active' : '' ?>"
                    id="filter-semua">
                    🗂️ Semua <span class="pill-count"><?= $total_pesanan ?></span>
                </a>
                <?php
                $pill_icons = ['Pending' => '🕐', 'Dimasak' => '👨‍🍳', 'Dikirim' => '🛵', 'Selesai' => '✅'];
                foreach ($status_list as $s):
                    $isActive = $filter_aktif === $s;
                    $s_params = $base_params;
                    $s_params['status'] = $s;
                    $status_qs = http_build_query($s_params);
                    $status_href = "dashboard.php?" . $status_qs;
                    ?>
                    <a href="<?= htmlspecialchars($status_href) ?>" id="filter-<?= strtolower($s) ?>"
                        class="filter-pill <?= $isActive ? 'active' : '' ?>">
                        <?= $pill_icons[$s] ?>     <?= $s ?>
                        <span class="pill-count"><?= $stat_per_status[$s] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- ── Orders Table Card ───────────────────────────── -->
            <div class="table-card" id="table-card-pesanan">

                <!-- Card header -->
                <div class="table-card__header">
                    <div class="table-card__title">
                        <div class="table-card__title-icon" aria-hidden="true">📋</div>
                        <h2>Semua Pesanan Masuk</h2>
                    </div>
                    <?php if (!$db_error && $result): ?>
                        <span class="table-card__count">
                            <?= mysqli_num_rows($result) ?> transaksi
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($db_error): ?>
                    <!-- ── Error state ─────────────────────────── -->
                    <div class="state-box error" role="alert" id="state-error">
                        <div class="state-icon">⚠️</div>
                        <h3>Gagal Memuat Data</h3>
                        <p><?= htmlspecialchars($db_error) ?></p>
                    </div>

                <?php elseif (mysqli_num_rows($result) === 0): ?>
                    <!-- ── Empty state ─────────────────────────── -->
                    <div class="state-box" id="state-empty">
                        <div class="state-icon">🛒</div>
                        <h3>Belum Ada Pesanan Masuk</h3>
                        <p>Pesanan dari pelanggan akan muncul di sini secara otomatis.</p>
                    </div>

                <?php else: ?>
                    <!-- ── Data table ──────────────────────────── -->
                    <div class="table-wrap">
                        <table class="orders-table" id="orders-table" aria-label="Tabel daftar pesanan">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>ID Pesanan</th>
                                    <th>Tanggal</th>
                                    <th>Nama Pelanggan</th>
                                    <th>No. Telepon</th>
                                    <th>Alamat</th>
                                    <th class="center">Jumlah</th>
                                    <th class="right">Total Bayar</th>
                                    <th class="center">Pembayaran</th>
                                    <th class="center">Bukti</th>
                                    <th class="center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                $grand_total = 0;

                                while ($row = mysqli_fetch_assoc($result)):
                                    $grand_total += (int) $row['total_harga'];
                                    $tgl_obj = date_create($row['tanggal_pesanan']);
                                    $tgl_teks = $tgl_obj ? date_format($tgl_obj, 'd M Y') : '-';
                                    $jam_teks = $tgl_obj ? date_format($tgl_obj, 'H:i') : '';
                                    $st = $row['status'] ?? 'Pending';
                                    $st_cls = strtolower($st);
                                    ?>
                                    <tr id="row-pesanan-<?= (int) $row['id_pesanan'] ?>"
                                        data-status="<?= htmlspecialchars($st) ?>">

                                        <!-- No -->
                                        <td style="color:var(--clr-text-muted);font-weight:600;font-size:.8rem;"><?= $no++ ?>
                                        </td>

                                        <!-- ID Pesanan -->
                                        <td><span
                                                class="cell-id">#<?= str_pad($row['id_pesanan'], 5, '0', STR_PAD_LEFT) ?></span>
                                        </td>

                                        <!-- Tanggal -->
                                        <td>
                                            <div class="cell-date">
                                                <strong><?= htmlspecialchars($tgl_teks) ?></strong>
                                                <?= htmlspecialchars($jam_teks) ?> WIB
                                            </div>
                                        </td>

                                        <!-- Nama Pelanggan -->
                                        <td class="cell-pelanggan"><?= htmlspecialchars($row['nama_pelanggan']) ?></td>

                                        <!-- No. Telepon -->
                                        <td style="font-size:.85rem;color:var(--clr-text-muted);white-space:nowrap;">
                                            <?= htmlspecialchars($row['no_telepon'] ?? '-') ?>
                                        </td>

                                        <!-- Alamat -->
                                        <td style="font-size:.85rem;color:var(--clr-text-muted);max-width:160px;">
                                            <?= htmlspecialchars($row['alamat'] ?? '-') ?>
                                        </td>

                                        <!-- Jumlah -->
                                        <td class="center">
                                            <span class="cell-qty"><?= (int) $row['jumlah_beli'] ?> porsi</span>
                                        </td>

                                        <!-- Total Bayar -->
                                        <td class="right cell-total"><?= rupiah((int) $row['total_harga']) ?></td>

                                        <!-- Metode Pembayaran -->
                                        <td class="center">
                                            <?php if (!empty($row['metode_pembayaran'])):
                                                $pm = $row['metode_pembayaran'];
                                                $pm_cls = match($pm) {
                                                    'DANA'  => 'badge-dana',
                                                    'OVO'   => 'badge-ovo',
                                                    'GoPay' => 'badge-gopay',
                                                    'QRIS'  => 'badge-qris',
                                                    'COD'   => 'badge-cod',
                                                    'ShopeePay' => 'badge-shopee',
                                                    default => 'badge-other',
                                                }; ?>
                                                <span class="badge-payment <?= $pm_cls ?>">
                                                    <?= htmlspecialchars($pm) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="no-bukti">-</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Bukti Transfer -->
                                        <td class="center">
                                            <?php if (!empty($row['bukti_transfer'])): ?>
                                                <button type="button" class="btn-bukti"
                                                    onclick="lihatBukti('img/bukti/<?= htmlspecialchars($row['bukti_transfer']) ?>', '#<?= str_pad($row['id_pesanan'], 5, '0', STR_PAD_LEFT) ?>')"
                                                    id="btn-bukti-<?= (int) $row['id_pesanan'] ?>">
                                                    🖼️ Lihat
                                                </button>
                                            <?php elseif ($row['metode_pembayaran'] === 'COD'): ?>
                                                <span class="no-bukti" style="color:var(--clr-success);font-weight:600;">Bayar di Tempat</span>
                                            <?php else: ?>
                                                <span class="no-bukti">Belum ada</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Status -->
                                        <td class="center">
                                            <?php
                                            $status_steps = ['Pending', 'Dimasak', 'Dikirim', 'Selesai'];
                                            $idx_sekarang = array_search($st, $status_steps);
                                            $status_berikutnya = $status_steps[$idx_sekarang + 1] ?? null;
                                            ?>
                                            <div class="status-stepper" id="stepper-<?= $row['id_pesanan'] ?>"
                                                 data-id="<?= (int) $row['id_pesanan'] ?>"
                                                 data-status="<?= htmlspecialchars($st) ?>">

                                                <div class="stepper-dots">
                                                    <?php foreach ($status_steps as $i => $step): ?>
                                                    <span class="stepper-dot <?= $i <= $idx_sekarang ? 'done' : '' ?>"
                                                          title="<?= $step ?>"></span>
                                                    <?php if ($i < 3): ?><span class="stepper-line <?= $i < $idx_sekarang ? 'done' : '' ?>"></span><?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>

                                                <span class="stepper-label <?= $st_cls ?>"><?= $st === 'Selesai' ? '✅ ' : '' ?><?= htmlspecialchars($st) ?></span>

                                                <div class="stepper-actions">
                                                    <?php if ($status_berikutnya): ?>
                                                    <button type="button"
                                                            class="btn-next-status"
                                                            data-id="<?= (int) $row['id_pesanan'] ?>"
                                                            data-next="<?= htmlspecialchars($status_berikutnya) ?>"
                                                            onclick="konfirmasiMaju(this)"
                                                            id="btn-next-<?= $row['id_pesanan'] ?>">
                                                        ▶ <?= htmlspecialchars($status_berikutnya) ?>
                                                    </button>
                                                    <?php endif; ?>
                                                    <button type="button"
                                                            class="btn-ubah-status"
                                                            data-id="<?= (int) $row['id_pesanan'] ?>"
                                                            data-status="<?= htmlspecialchars($st) ?>"
                                                            onclick="bukaUbahStatus(this)"
                                                            title="Ubah status manual"
                                                            id="btn-ubah-<?= $row['id_pesanan'] ?>">⚙</button>
                                                </div>

                                            </div>
                                        </td>

                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Card footer: grand total -->
                    <div class="table-card__footer" id="table-footer">
                        <span class="tfoot-info">
                            Menampilkan <?= $no - 1 ?> transaksi · diurutkan dari terbaru
                        </span>
                        <div class="tfoot-total">
                            <span class="tfoot-total__label">Total Omzet:</span>
                            <span class="tfoot-total__value" id="grand-total-value">
                                <?= rupiah($grand_total) ?>
                            </span>
                        </div>
                    </div>

                <?php endif; ?>

            </div><!-- /.table-card -->

        </main>

    </div><!-- /.main-panel -->

    <!-- MODAL KONFIRMASI MAJU STATUS -->
    <div class="mini-modal-overlay" id="modal-konfirmasi-status" role="dialog" aria-modal="true">
        <div class="mini-modal">
            <div class="mini-modal__icon">🚀</div>
            <div class="mini-modal__title" id="modal-konfirmasi-title">Ubah ke Dimasak?</div>
            <div class="mini-modal__desc" id="modal-konfirmasi-desc">
                Pesanan <strong id="modal-konfirmasi-id">#00000</strong> akan dipindah dari
                <strong id="modal-dari"></strong> ke <strong id="modal-ke"></strong>.
            </div>
            <div class="mini-modal__confirm">
                <button class="btn-confirm-tidak" onclick="tutupModalStatus()">Batal</button>
                <button class="btn-confirm-ya" id="btn-konfirmasi-ya" onclick="eksekusiMaju()">Ya, Ubah</button>
            </div>
        </div>
    </div>

    <!-- MODAL UBAH STATUS MANUAL -->
    <div class="mini-modal-overlay" id="modal-ubah-status" role="dialog" aria-modal="true">
        <div class="mini-modal">
            <div class="mini-modal__icon">⚙️</div>
            <div class="mini-modal__title">Ubah Status Manual</div>
            <div class="mini-modal__desc">
                Pesanan <strong id="modal-ubah-id">#00000</strong> — pilih status baru:
            </div>
            <div class="status-option-list">
                <button class="status-option-btn" data-val="Pending" onclick="pilihStatusManual(this)"><span class="opt-dot pending"></span>Pending</button>
                <button class="status-option-btn" data-val="Dimasak" onclick="pilihStatusManual(this)"><span class="opt-dot dimasak"></span>Dimasak</button>
                <button class="status-option-btn" data-val="Dikirim" onclick="pilihStatusManual(this)"><span class="opt-dot dikirim"></span>Dikirim</button>
                <button class="status-option-btn" data-val="Selesai" onclick="pilihStatusManual(this)"><span class="opt-dot selesai"></span>Selesai</button>
            </div>
            <button class="mini-modal__cancel" onclick="tutupModalUbah()">Batal</button>
        </div>
    </div>

    <!-- Modal Bukti Transfer -->
    <div class="modal-overlay" id="modal-bukti" role="dialog" aria-modal="true" aria-label="Bukti Transfer">
        <div class="modal-card">
            <div class="modal-header">
                <h3>🖼️ Bukti Transfer <span id="modal-order-id"></span></h3>
                <button class="modal-close" onclick="tutupModal()" aria-label="Tutup">✕</button>
            </div>
            <div class="modal-body">
                <img id="modal-bukti-img" src="" alt="Bukti transfer pesanan">
            </div>
        </div>
    </div>

    <script>
        /* ── State sementara untuk modal ── */
        let _pendingId = null;
        let _pendingNext = null;
        let _pendingDari = null;

        /* ── Tombol "▶ Status Berikutnya" diklik ── */
        function konfirmasiMaju(btn) {
            _pendingId   = btn.dataset.id;
            _pendingNext = btn.dataset.next;
            const stepper = document.getElementById('stepper-' + _pendingId);
            _pendingDari = stepper.dataset.status;

            document.getElementById('modal-konfirmasi-title').textContent = 'Ubah ke ' + _pendingNext + '?';
            document.getElementById('modal-konfirmasi-id').textContent = '#' + String(_pendingId).padStart(5, '0');
            document.getElementById('modal-dari').textContent = _pendingDari;
            document.getElementById('modal-ke').textContent = _pendingNext;
            document.getElementById('modal-konfirmasi-status').classList.add('active');
        }

        /* ── Tombol "Ya, Ubah" di modal konfirmasi ── */
        async function eksekusiMaju() {
            tutupModalStatus();
            if (!_pendingId || !_pendingNext) return;
            await kirimStatus(_pendingId, _pendingNext);
            _pendingId = _pendingNext = _pendingDari = null;
        }

        /* ── Tombol ⚙ Ubah manual ── */
        function bukaUbahStatus(btn) {
            const id = btn.dataset.id;
            const statusSekarang = btn.dataset.status;
            document.getElementById('modal-ubah-id').textContent = '#' + String(id).padStart(5, '0');
            document.getElementById('modal-ubah-status').dataset.targetId = id;

            document.querySelectorAll('.status-option-btn').forEach(b => {
                b.classList.toggle('aktif', b.dataset.val === statusSekarang);
            });
            document.getElementById('modal-ubah-status').classList.add('active');
        }

        /* ── Pilih status di modal ubah manual ── */
        async function pilihStatusManual(btn) {
            const statusBaru = btn.dataset.val;
            const id = document.getElementById('modal-ubah-status').dataset.targetId;
            tutupModalUbah();
            await kirimStatus(id, statusBaru);
        }

        /* ── Tutup modal ── */
        function tutupModalStatus() { document.getElementById('modal-konfirmasi-status').classList.remove('active'); }
        function tutupModalUbah()   { document.getElementById('modal-ubah-status').classList.remove('active'); }

        /* ── Kirim ke proses_status.php (AJAX) ── */
        async function kirimStatus(id, statusBaru) {
            const statusSteps = ['Pending', 'Dimasak', 'Dikirim', 'Selesai'];
            const klsMap = { Pending: 'pending', Dimasak: 'dimasak', Dikirim: 'dikirim', Selesai: 'selesai' };

            const btnNext = document.getElementById('btn-next-' + id);
            if (btnNext) btnNext.disabled = true;

            try {
                const fd = new FormData();
                fd.append('id_pesanan', id);
                fd.append('status', statusBaru);

                const res  = await fetch('proses_status.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                });
                const data = await res.json();

                if (data.ok) {
                    /* Update stepper di DOM */
                    const stepper = document.getElementById('stepper-' + id);
                    const idxBaru = statusSteps.indexOf(statusBaru);
                    const statusBerikutnya = statusSteps[idxBaru + 1] ?? null;

                    stepper.dataset.status = statusBaru;

                    stepper.querySelectorAll('.stepper-dot').forEach((dot, i) => {
                        dot.classList.toggle('done', i <= idxBaru);
                    });
                    stepper.querySelectorAll('.stepper-line').forEach((line, i) => {
                        line.classList.toggle('done', i < idxBaru);
                    });

                    const label = stepper.querySelector('.stepper-label');
                    label.className = 'stepper-label ' + (klsMap[statusBaru] ?? '');
                    label.textContent = (statusBaru === 'Selesai' ? '✅ ' : '') + statusBaru;

                    const actionsDiv = stepper.querySelector('.stepper-actions');
                    const oldBtnNext = stepper.querySelector('.btn-next-status');
                    if (oldBtnNext) oldBtnNext.remove();
                    if (statusBerikutnya) {
                        const newBtn = document.createElement('button');
                        newBtn.type = 'button';
                        newBtn.className = 'btn-next-status';
                        newBtn.dataset.id = id;
                        newBtn.dataset.next = statusBerikutnya;
                        newBtn.id = 'btn-next-' + id;
                        newBtn.setAttribute('onclick', 'konfirmasiMaju(this)');
                        newBtn.textContent = '▶ ' + statusBerikutnya;
                        actionsDiv.prepend(newBtn);
                    }

                    const btnUbah = document.getElementById('btn-ubah-' + id);
                    if (btnUbah) btnUbah.dataset.status = statusBaru;

                    showFlash(data.message, 'success');
                } else {
                    showFlash(data.message || 'Gagal memperbarui.', 'error');
                }
            } catch (e) {
                showFlash('Kesalahan koneksi. Coba lagi.', 'error');
            } finally {
                const b = document.getElementById('btn-next-' + id);
                if (b) b.disabled = false;
            }
        }

        /* ── Tutup modal klik di luar ── */
        document.getElementById('modal-konfirmasi-status').addEventListener('click', function(e) {
            if (e.target === this) tutupModalStatus();
        });
        document.getElementById('modal-ubah-status').addEventListener('click', function(e) {
            if (e.target === this) tutupModalUbah();
        });

        /* ── Helper: tampilkan flash message ── */
        function showFlash(msg, type) {
            let el = document.getElementById('flash-msg');
            if (!el) {
                el = document.createElement('div');
                el.id = 'flash-msg';
                document.querySelector('.page-heading').after(el);
            }
            el.className = 'flash-alert ' + type;
            el.innerHTML = (type === 'success' ? '✅' : '❌') + ' ' + msg;
            el.style.display = 'flex';
            clearTimeout(el._t);
            el._t = setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 4000);
        }

        /* ── Auto-hide flash yang di-render dari PHP ── */
        const phpFlash = document.getElementById('flash-msg');
        if (phpFlash) {
            setTimeout(() => { phpFlash.style.opacity = '0'; setTimeout(() => phpFlash.remove(), 300); }, 5000);
        }

        /* ── Modal bukti transfer ── */
        function lihatBukti(src, orderId) {
            document.getElementById('modal-bukti-img').src = src;
            document.getElementById('modal-order-id').textContent = orderId;
            document.getElementById('modal-bukti').classList.add('active');
        }

        function tutupModal() {
            document.getElementById('modal-bukti').classList.remove('active');
            document.getElementById('modal-bukti-img').src = '';
        }

        // Tutup modal dengan klik di luar atau tekan Escape
        document.getElementById('modal-bukti').addEventListener('click', function (e) {
            if (e.target === this) tutupModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') tutupModal();
        });

        /* ── Set data-label pada td dari thead th (untuk card layout mobile) ── */
        (function () {
            var table = document.querySelector('.orders-table');
            if (!table) return;
            var headers = [];
            var ths = table.querySelectorAll('thead th');
            for (var i = 0; i < ths.length; i++) {
                headers.push(ths[i].textContent.trim());
            }
            var rows = table.querySelectorAll('tbody tr');
            for (var r = 0; r < rows.length; r++) {
                var tds = rows[r].querySelectorAll('td');
                for (var c = 0; c < tds.length && c < headers.length; c++) {
                    tds[c].setAttribute('data-label', headers[c]);
                }
            }
        })();
    </script>

    <!-- ══════════════════════════════════════════════════════════
     BOTTOM NAV – mobile (≤840px replaces sidebar)
    ══════════════════════════════════════════════════════════ -->
    <nav class="bottom-nav" aria-label="Navigasi mobile">
        <div class="bottom-nav__inner">
            <a href="dashboard.php" class="bottom-nav__item active" id="nav-mobile-dashboard">
                <span class="bottom-nav__icon">📊</span>
                <span class="bottom-nav__label">Dashboard</span>
            </a>
            <a href="kelola_menu.php" class="bottom-nav__item" id="nav-mobile-kelola">
                <span class="bottom-nav__icon">🍴</span>
                <span class="bottom-nav__label">Menu</span>
            </a>
            <a href="index.php" class="bottom-nav__item" id="nav-mobile-katalog">
                <span class="bottom-nav__icon">🛍️</span>
                <span class="bottom-nav__label">Katalog</span>
            </a>
        </div>
    </nav>

</body>

</html>
<?php
if (isset($koneksi)) {
    mysqli_close($koneksi);
}
?>