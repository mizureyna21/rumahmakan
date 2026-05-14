<?php
// Memulai sesi untuk manajemen keranjang
session_start();
// Menghubungkan ke database
include 'koneksi.php';

// Menghitung jumlah total item di keranjang
$cart_count = 0;
if (!empty($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $qty) {
        $cart_count += (int) $qty;
    }
}

// Mengambil data menu dari database dan mengurutkannya berdasarkan kategori dan nama
$query = "SELECT id_menu, nama_menu, kategori, harga, foto, stok FROM menu ORDER BY kategori, nama_menu";
$result = mysqli_query($koneksi, $query);

// Menangani error jika query gagal
if (!$result) {
    $db_error = "Gagal mengambil data menu: " . mysqli_error($koneksi);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Rumah Makan Sipatuo Jr. – Katalog menu masakan khas Minahasa. Pilih makanan favorit Anda dan tambahkan ke pesanan.">
    <title>Rumah Makan Sipatuo Jr. – Katalog Menu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="shortcut icon" href="/rumahmakan/logo.png" type="image/x-icon">

    <style>
        /* ══════════════════════════════════════════════
           Airbnb Design Tokens (from DESIGN.md)
           ══════════════════════════════════════════════ */
        :root {
            /* Colors */
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
            /* Rounded */
            --air-radius-sm: 8px;
            --air-radius-md: 14px;
            --air-radius-lg: 20px;
            --air-radius-xl: 32px;
            --air-radius-full: 9999px;
            /* Spacing */
            --air-space-xs: 4px;
            --air-space-sm: 8px;
            --air-space-md: 12px;
            --air-space-base: 16px;
            --air-space-lg: 24px;
            --air-space-xl: 32px;
            --air-space-xxl: 48px;
            --air-space-section: 64px;
            /* Elevation – single hover float shadow */
            --air-shadow-hover: rgba(0,0,0,0.02) 0 0 0 1px, rgba(0,0,0,0.04) 0 2px 6px, rgba(0,0,0,0.1) 0 4px 8px;
            /* Transition */
            --air-transition: .2s cubic-bezier(.4,0,.2,1);
        }

        /* ══════════════════════════════════════════════
           RESET & BASE
           ══════════════════════════════════════════════ */
        *, *::before, *::after {
            box-sizing: border-box; margin: 0; padding: 0;
        }
        body {
            font-family: 'Inter', -apple-system, system-ui, Roboto, 'Helvetica Neue', sans-serif;
            background: var(--air-canvas);
            color: var(--air-ink);
            min-height: 100vh;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        img { display: block; max-width: 100%; }
        a { text-decoration: none; color: inherit; }

        /* ══════════════════════════════════════════════
           TOP NAV – Airbnb top-nav component
           ══════════════════════════════════════════════ */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--air-hairline);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 0 var(--air-space-lg);
            height: 80px;
        }
        .navbar__inner {
            max-width: 1280px;
            margin: 0 auto;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .navbar__brand {
            display: flex;
            align-items: center;
            gap: .65rem;
        }
        .navbar__logo-icon {
            width: 38px;
            height: 38px;
            border-radius: var(--air-radius-sm);
            object-fit: contain;
            flex-shrink: 0;
        }
        .navbar__title {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--air-ink);
            letter-spacing: -.3px;
        }
        .navbar__title span {
            color: var(--air-primary);
        }

        /* ── Admin button (secondary outline) ── */
        .navbar__admin-btn {
            display: flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1rem;
            background: transparent;
            color: var(--air-ink);
            font-family: inherit;
            font-size: .85rem;
            font-weight: 500;
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-sm);
            cursor: pointer;
            transition: border-color var(--air-transition);
        }
        .navbar__admin-btn:hover {
            border-color: var(--air-ink);
        }

        /* ── Cart button (primary Rausch CTA) ── */
        .navbar__cart-btn {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .55rem 1.2rem;
            background: var(--air-primary);
            color: var(--air-on-primary);
            font-family: inherit;
            font-size: .875rem;
            font-weight: 500;
            border: none;
            border-radius: var(--air-radius-sm);
            cursor: pointer;
            transition: background var(--air-transition);
            height: 48px;
        }
        .navbar__cart-btn:hover {
            background: var(--air-primary-active);
        }
        .navbar__cart-btn .cart-icon {
            font-size: 1rem;
        }
        .cart-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 5px;
            background: var(--air-on-primary);
            color: var(--air-primary);
            font-size: .7rem;
            font-weight: 700;
            border-radius: var(--air-radius-full);
            line-height: 1;
        }

        /* ══════════════════════════════════════════════
           HERO – Clean generous whitespace
           ══════════════════════════════════════════════ */
        .hero {
            background: var(--air-surface-soft);
            padding: var(--air-space-section) var(--air-space-lg);
            text-align: center;
        }
        .hero__content {
            max-width: 680px;
            margin: 0 auto;
        }
        .hero__badge {
            display: inline-block;
            font-size: .8rem;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--air-muted);
            margin-bottom: var(--air-space-base);
        }
        .hero__title {
            font-size: clamp(1.75rem, 3.5vw, 2.5rem);
            font-weight: 600;
            color: var(--air-ink);
            line-height: 1.25;
            margin-bottom: var(--air-space-base);
            letter-spacing: -.4px;
        }
        .hero__title em {
            color: var(--air-primary);
            font-style: normal;
            font-weight: 600;
        }
        .hero__subtitle {
            font-size: 1rem;
            color: var(--air-body);
            max-width: 480px;
            margin: 0 auto var(--air-space-base);
            line-height: 1.5;
        }
        .hero__location {
            font-size: .85rem;
            color: var(--air-muted);
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .4rem 1.1rem;
            border-radius: var(--air-radius-full);
            background: var(--air-canvas);
            border: 1px solid var(--air-hairline);
        }

        /* ══════════════════════════════════════════════
           MAIN CONTENT / CATALOG
           ══════════════════════════════════════════════ */
        .main-content {
            max-width: 1280px;
            margin: 0 auto;
            padding: var(--air-space-xl) var(--air-space-lg) var(--air-space-xxl);
        }

        /* ── Section header ── */
        .section-header {
            display: flex;
            align-items: center;
            gap: var(--air-space-sm);
            margin-bottom: var(--air-space-lg);
        }
        .section-header__line {
            display: none;  /* Airbnb doesn't use decorative lines */
        }
        .section-header h2 {
            font-size: 1.35rem;
            font-weight: 600;
            letter-spacing: -.3px;
            color: var(--air-ink);
        }
        .section-header__count {
            margin-left: auto;
            font-size: .85rem;
            color: var(--air-muted);
            font-weight: 500;
        }

        /* ══════════════════════════════════════════════
           SEARCH BAR – Pill-shaped, hairline border
           ══════════════════════════════════════════════ */
        .search-bar {
            margin-bottom: var(--air-space-base);
        }
        .search-wrap {
            position: relative;
        }
        .search-input {
            width: 100%;
            padding: .75rem 1rem .75rem 2.8rem;
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-full);
            font-family: inherit;
            font-size: .95rem;
            font-weight: 500;
            color: var(--air-ink);
            background: var(--air-canvas) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%236a6a6a' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") no-repeat 1rem center;
            background-size: 18px;
            outline: none;
            transition: border-color var(--air-transition), box-shadow var(--air-transition);
            box-shadow: var(--air-shadow-hover);
            height: 56px;
        }
        .search-input:focus {
            border-color: var(--air-ink);
            border-width: 2px;
            box-shadow: none;
        }
        .search-input::placeholder {
            color: var(--air-muted-soft);
            font-weight: 400;
        }
        .search-clear {
            display: none;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: var(--air-surface-strong);
            border: none;
            width: 24px;
            height: 24px;
            border-radius: var(--air-radius-full);
            font-size: .8rem;
            color: var(--air-ink);
            cursor: pointer;
            line-height: 24px;
            text-align: center;
        }
        .search-clear.show {
            display: block;
        }
        .search-empty {
            display: none;
            text-align: center;
            padding: var(--air-space-xxl) var(--air-space-lg);
            color: var(--air-muted);
            grid-column: 1 / -1;
        }
        .search-empty.show {
            display: block;
        }
        .search-empty .si {
            font-size: 2.5rem;
            margin-bottom: .5rem;
        }
        .search-empty h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--air-ink);
            margin-bottom: .25rem;
        }

        /* ══════════════════════════════════════════════
           FILTER PILLS – Airbnb category-strip style
           ══════════════════════════════════════════════ */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: var(--air-space-xs);
            flex-wrap: wrap;
            margin-bottom: var(--air-space-lg);
            padding-bottom: var(--air-space-sm);
            border-bottom: 1px solid var(--air-hairline-soft);
        }
        .filter-btn {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .5rem var(--air-space-base);
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-full);
            background: var(--air-canvas);
            font-family: inherit;
            font-size: .85rem;
            font-weight: 500;
            color: var(--air-muted);
            cursor: pointer;
            transition: all var(--air-transition);
            white-space: nowrap;
        }
        .filter-btn:hover {
            border-color: var(--air-ink);
            color: var(--air-ink);
        }
        .filter-btn.active {
            border-color: var(--air-ink);
            background: var(--air-ink);
            color: var(--air-on-primary);
        }
        .filter-btn .filter-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 5px;
            border-radius: var(--air-radius-full);
            font-size: .7rem;
            font-weight: 600;
            background: rgba(0,0,0,.08);
            color: inherit;
        }
        .filter-btn.active .filter-count {
            background: rgba(255,255,255,.2);
        }

        .card--hidden {
            display: none !important;
        }

        .filter-empty {
            display: none;
            text-align: center;
            padding: var(--air-space-xxl) var(--air-space-lg);
            border: 2px dashed var(--air-hairline);
            border-radius: var(--air-radius-lg);
            color: var(--air-muted);
            grid-column: 1 / -1;
        }
        .filter-empty .state-icon {
            font-size: 2.5rem;
            margin-bottom: .5rem;
        }
        .filter-empty h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--air-ink);
            margin-bottom: .25rem;
        }
        .filter-empty.show {
            display: block;
        }

        /* ══════════════════════════════════════════════
           STATE BOX – Error / empty
           ══════════════════════════════════════════════ */
        .state-box {
            text-align: center;
            padding: var(--air-space-xxl) var(--air-space-lg);
            border: 2px dashed var(--air-hairline);
            border-radius: var(--air-radius-lg);
            color: var(--air-muted);
        }
        .state-box .state-icon {
            font-size: 3rem;
            margin-bottom: .75rem;
        }
        .state-box h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--air-ink);
            margin-bottom: .35rem;
        }
        .state-box.error {
            border-color: #fecaca;
            background: #fef2f2;
            color: #dc2626;
        }

        /* ══════════════════════════════════════════════
           MENU GRID – Airbnb property-card grid
           ══════════════════════════════════════════════ */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: var(--air-space-lg);
        }

        /* ── Card ── */
        .card {
            background: var(--air-canvas);
            border-radius: var(--air-radius-md);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: box-shadow var(--air-transition);
            border: 1px solid var(--air-hairline);
            box-shadow: none;
        }
        .card:hover {
            box-shadow: var(--air-shadow-hover);
        }

        /* ── Image area ── */
        .card__img-wrap {
            width: 100%;
            aspect-ratio: 4 / 3;
            background: var(--air-surface-soft);
            overflow: hidden;
            position: relative;
        }
        .card__img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .4s ease;
        }
        .card:hover .card__img-wrap img {
            transform: scale(1.04);
        }
        .card__img-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: var(--air-surface-soft);
            color: var(--air-muted-soft);
            gap: .4rem;
            font-size: .85rem;
            font-weight: 500;
        }
        .card__img-placeholder .ph-icon {
            font-size: 2.5rem;
        }

        /* ── Category badge (Airbnb guest-favorite style) ── */
        .card__badge {
            position: absolute;
            top: .65rem;
            left: .65rem;
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
            padding: .25rem .7rem;
            border-radius: var(--air-radius-full);
            background: var(--air-canvas);
            color: var(--air-ink);
            box-shadow: var(--air-shadow-hover);
        }
        .badge--makanan {
            background: var(--air-canvas);
            color: var(--air-ink);
        }
        .badge--minuman {
            background: var(--air-canvas);
            color: var(--air-ink);
        }
        .badge--default {
            background: var(--air-canvas);
            color: var(--air-ink);
        }

        /* ── Card body ── */
        .card__body {
            padding: var(--air-space-base);
            display: flex;
            flex-direction: column;
            gap: .35rem;
            flex: 1;
        }
        .card__name {
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.3;
            color: var(--air-ink);
        }
        .card__price {
            font-size: .95rem;
            font-weight: 500;
            color: var(--air-body);
            letter-spacing: 0;
        }

        /* ── Card actions ── */
        .card__actions {
            margin-top: auto;
            padding-top: var(--air-space-sm);
            border-top: 1px solid var(--air-hairline-soft);
            display: flex;
            align-items: flex-end;
            gap: .45rem;
        }
        .qty-group {
            display: flex;
            flex-direction: column;
            gap: .15rem;
            flex-shrink: 0;
        }
        .qty-label {
            font-size: .68rem;
            font-weight: 600;
            color: var(--air-muted);
        }
        .qty-input {
            width: 52px;
            padding: .4rem .35rem;
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-sm);
            font-family: inherit;
            font-size: .85rem;
            font-weight: 500;
            color: var(--air-ink);
            text-align: center;
            transition: border-color var(--air-transition);
            outline: none;
        }
        .qty-input:focus {
            border-color: var(--air-ink);
            border-width: 2px;
        }

        .btn-group {
            display: flex;
            gap: .35rem;
            flex: 1;
        }
        .btn-action {
            flex: 1;
            padding: .5rem .35rem;
            border: none;
            border-radius: var(--air-radius-sm);
            font-family: inherit;
            font-size: .78rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .25rem;
            white-space: nowrap;
            transition: background var(--air-transition);
            height: 40px;
        }
        .btn-action:active {
            transform: translateY(0) !important;
        }

        /* ── "Keranjang" = secondary outline ── */
        .btn-cart {
            background: var(--air-canvas);
            color: var(--air-ink);
            border: 1px solid var(--air-hairline);
        }
        .btn-cart:hover {
            border-color: var(--air-ink);
        }

        /* ── "Pesan" = primary Rausch CTA ── */
        .btn-order {
            background: var(--air-primary);
            color: var(--air-on-primary);
            border: 1px solid var(--air-primary);
        }
        .btn-order:hover {
            background: var(--air-primary-active);
            border-color: var(--air-primary-active);
        }

        /* ── Stok Habis ── */
        .badge--habis {
            position: absolute;
            top: .65rem;
            right: .65rem;
            font-size: .65rem;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
            padding: .25rem .7rem;
            border-radius: var(--air-radius-full);
            background: var(--air-ink);
            color: var(--air-on-primary);
            z-index: 2;
        }
        .card--habis {
            opacity: .7;
        }
        .card--habis .card__img-wrap img,
        .card--habis .card__img-placeholder {
            filter: grayscale(60%);
        }
        .btn-action:disabled {
            background: var(--air-hairline-soft) !important;
            color: var(--air-muted-soft) !important;
            cursor: not-allowed;
            border-color: var(--air-hairline-soft) !important;
        }
        .stok-habis-note {
            font-size: .78rem;
            font-weight: 500;
            color: var(--air-error-text);
            text-align: center;
            padding: .45rem .6rem;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: var(--air-radius-sm);
            width: 100%;
            margin-top: .5rem;
        }

        /* ══════════════════════════════════════════════
           FOOTER – Airbnb footer-light
           ══════════════════════════════════════════════ */
        .footer {
            background: var(--air-canvas);
            color: var(--air-muted);
            padding: var(--air-space-xxl) var(--air-space-lg) 0;
            font-size: .875rem;
            border-top: 1px solid var(--air-hairline);
        }
        .footer__inner {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.4fr 1fr 1.6fr;
            gap: var(--air-space-xl);
            padding-bottom: var(--air-space-xl);
            border-bottom: 1px solid var(--air-hairline-soft);
            align-items: start;
        }
        .footer__brand-name {
            font-size: 1rem;
            font-weight: 600;
            color: var(--air-ink);
            margin-bottom: .35rem;
        }
        .footer__brand-name span { color: var(--air-primary); }
        .footer__desc {
            font-size: .85rem;
            color: var(--air-muted);
            line-height: 1.6;
            margin-top: .35rem;
            max-width: 280px;
        }
        .footer__col-title {
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--air-muted);
            margin-bottom: var(--air-space-base);
        }
        .footer__link {
            display: flex;
            align-items: center;
            gap: .55rem;
            padding: .4rem 0;
            color: var(--air-muted);
            font-size: .875rem;
            font-weight: 500;
            text-decoration: none;
            transition: color var(--air-transition);
        }
        .footer__link:hover { color: var(--air-ink); }
        .footer__link-icon {
            width: 32px;
            height: 32px;
            border-radius: var(--air-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .icon-ig   { background: transparent; }
        .icon-fb   { background: transparent; }
        .footer__bottom {
            max-width: 1280px;
            margin: 0 auto;
            padding: var(--air-space-base) 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .5rem;
            font-size: .8rem;
            color: var(--air-muted-soft);
        }
        .footer__bottom strong { color: var(--air-ink); }
        .footer__map {
            width: 100%;
            height: 180px;
            border-radius: var(--air-radius-sm);
            overflow: hidden;
            border: 1px solid var(--air-hairline-soft);
            display: block;
            transition: opacity .25s ease;
        }
        .footer__map:hover { opacity: .85; }

        @media (max-width: 720px) {
            .footer__inner {
                grid-template-columns: 1fr;
                gap: var(--air-space-lg);
            }
            .footer__map { height: 200px; }
        }

        /* ══════════════════════════════════════════════
           MODAL OVERLAY – Airbnb scrim
           ══════════════════════════════════════════════ */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 9990;
            background: var(--air-scrim);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--air-space-base);
            opacity: 0;
            pointer-events: none;
            transition: opacity .25s ease;
        }
        .modal-overlay.modal--open {
            opacity: 1;
            pointer-events: all;
        }
        .modal {
            background: var(--air-canvas);
            border-radius: var(--air-radius-md);
            width: 100%;
            max-width: 400px;
            padding: var(--air-space-xl);
            transform: translateY(20px) scale(.97);
            transition: transform .28s cubic-bezier(.4,0,.2,1);
        }
        .modal-overlay.modal--open .modal {
            transform: translateY(0) scale(1);
        }
        .modal__header {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-bottom: var(--air-space-lg);
        }
        .modal__icon {
            width: 48px;
            height: 48px;
            background: var(--air-surface-soft);
            border-radius: var(--air-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .modal__title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--air-ink);
            letter-spacing: -.3px;
        }
        .modal__subtitle {
            font-size: .85rem;
            color: var(--air-muted);
            margin-top: .1rem;
        }
        .modal__close {
            margin-left: auto;
            background: var(--air-surface-strong);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: var(--air-radius-full);
            font-size: 1rem;
            color: var(--air-ink);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background var(--air-transition);
        }
        .modal__close:hover {
            background: var(--air-hairline);
        }

        /* ── Login form ── */
        .form-group {
            margin-bottom: var(--air-space-base);
        }
        .form-label {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            color: var(--air-muted);
            margin-bottom: .4rem;
            letter-spacing: .03em;
            text-transform: uppercase;
        }
        .form-input {
            width: 100%;
            padding: .7rem .9rem;
            border: 1px solid var(--air-hairline);
            border-radius: var(--air-radius-sm);
            font-family: inherit;
            font-size: .9rem;
            color: var(--air-ink);
            background: var(--air-canvas);
            transition: border-color var(--air-transition);
            outline: none;
        }
        .form-input:focus {
            border-color: var(--air-ink);
            border-width: 2px;
        }
        .form-input.input--error {
            border-color: var(--air-error-text);
        }
        .form-error {
            display: none;
            font-size: .75rem;
            color: var(--air-error-text);
            font-weight: 500;
            margin-top: .35rem;
        }
        .form-error.show {
            display: block;
        }
        .btn-login {
            width: 100%;
            padding: .8rem;
            background: var(--air-primary);
            color: var(--air-on-primary);
            font-family: inherit;
            font-size: .95rem;
            font-weight: 500;
            border: none;
            border-radius: var(--air-radius-sm);
            cursor: pointer;
            margin-top: var(--air-space-sm);
            transition: background var(--air-transition);
            height: 48px;
        }
        .btn-login:hover {
            background: var(--air-primary-active);
        }

        /* ══════════════════════════════════════════════
           TOAST NOTIFICATION
           ══════════════════════════════════════════════ */
        .toast {
            position: fixed;
            top: 96px;
            right: var(--air-space-lg);
            z-index: 9999;
            background: var(--air-ink);
            color: var(--air-on-primary);
            padding: .85rem 1.2rem;
            border-radius: var(--air-radius-sm);
            font-size: .875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: .65rem;
            min-width: 280px;
            max-width: 340px;
            transform: translateX(calc(100% + 2rem));
            opacity: 0;
            transition: transform .35s cubic-bezier(.4,0,.2,1), opacity .35s ease;
            overflow: hidden;
        }
        .toast.toast--show {
            transform: translateX(0);
            opacity: 1;
        }
        .toast__icon {
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        .toast__text {
            flex: 1;
            line-height: 1.4;
        }
        .toast__title {
            display: block;
            font-weight: 600;
            font-size: .9rem;
        }
        .toast__sub {
            display: block;
            font-size: .75rem;
            color: rgba(255,255,255,.6);
            margin-top: .05rem;
        }
        .toast__progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            width: 100%;
            background: var(--air-primary);
            transform-origin: left center;
            transform: scaleX(1);
        }
        .toast--show .toast__progress {
            animation: toastDrain 2.8s linear forwards;
        }
        @keyframes toastDrain {
            from { transform: scaleX(1); }
            to   { transform: scaleX(0); }
        }

        /* ══════════════════════════════════════════════
           RESPONSIVE
           ══════════════════════════════════════════════ */
        @media (max-width: 744px) {
            .navbar { height: 64px; }
            .navbar__title { font-size: .9rem; }
            .hero { padding: var(--air-space-xl) var(--air-space-base); }
            .main-content { padding: var(--air-space-lg) var(--air-space-base) var(--air-space-xl); }
            .menu-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: var(--air-space-base);
            }
            .footer__inner { grid-template-columns: 1fr; gap: var(--air-space-lg); }
        }
    </style>
</head>

<body>

    <div class="toast" id="cart-toast" role="alert" aria-live="polite" aria-atomic="true">
        <span class="toast__icon">🛒</span>
        <span class="toast__text">
            <span class="toast__title">Berhasil ditambahkan!</span>
            <span class="toast__sub">Item masuk ke keranjang Anda.</span>
        </span>
        <div class="toast__progress"></div>
    </div>

    <!-- Modal Login Admin -->
    <div class="modal-overlay" id="modal-admin" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <div class="modal">
            <div class="modal__header">
                <div class="modal__icon" aria-hidden="true">🔐</div>
                <div>
                    <div class="modal__title" id="modal-title">Login Admin</div>
                    <div class="modal__subtitle">Masuk ke panel dashboard admin</div>
                </div>
                <button class="modal__close" id="btn-close-modal" aria-label="Tutup modal" type="button">✕</button>
            </div>

            <form id="form-admin-login" novalidate>
                <div class="form-group">
                    <label class="form-label" for="input-username">Username</label>
                    <input class="form-input" type="text" id="input-username" placeholder="Masukkan username"
                        autocomplete="username">
                    <span class="form-error" id="err-username">Username tidak boleh kosong.</span>
                </div>
                <div class="form-group">
                    <label class="form-label" for="input-password">Password</label>
                    <input class="form-input" type="password" id="input-password" placeholder="Masukkan password"
                        autocomplete="current-password">
                    <span class="form-error" id="err-password">Password tidak boleh kosong.</span>
                </div>
                <span class="form-error show" id="err-credentials" style="display:none;margin-bottom:.75rem;">Username
                    atau password salah.</span>
                <button type="submit" class="btn-login" id="btn-submit-login">🔑 Masuk ke Dashboard</button>
            </form>
        </div>
    </div>

    <header>
        <nav class="navbar" aria-label="Navigasi utama">
            <div class="navbar__inner">
                <a href="index.php" class="navbar__brand" id="nav-brand">
                    <img src="logo.png" alt="Logo Rumah Makan" class="navbar__logo-icon">
                    <span class="navbar__title">Rumah Makan <span>Sipatuo Jr.</span></span>
                </a>
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <button class="navbar__admin-btn" id="btn-open-admin" type="button">
                        ⚙️ Admin
                    </button>
                    <a href="keranjang.php" id="btn-lihat-pesanan">
                        <button class="navbar__cart-btn" type="button">
                            <span class="cart-icon" aria-hidden="true">🛒</span>
                            Keranjang
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-badge"><?= $cart_count ?></span>
                            <?php endif; ?>
                        </button>
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <section class="hero" aria-label="Banner selamat datang">
        <div class="hero__content">
            <div class="hero__badge">🌶 Cita Rasa Khas Minahasa</div>
            <h1 class="hero__title">
                Selamat Datang di<br>
                <em>Rumah Makan Sipatuo Jr.</em>
            </h1>
            <p class="hero__subtitle">
                Silakan pilih menu favorit Anda dan tambahkan ke pesanan. Kami siap menyajikan cita rasa terbaik!
            </p>
            <p class="hero__location">
                📍 Jln. Bubak, Bungko, Kec. Kotamobagu Sel., Kota Kotamobagu, Sulawesi Utara 95717
            </p>
        </div>
    </section>

    <main class="main-content" id="menu-catalog">

        <div class="section-header">
            <div class="section-header__line" aria-hidden="true"></div>
            <h2>Katalog Menu</h2>
            <?php if (!empty($result) && mysqli_num_rows($result) > 0): ?>
                <span class="section-header__count">
                    <?= mysqli_num_rows($result) ?> item tersedia
                </span>
            <?php endif; ?>
        </div>

        <?php if (isset($db_error)): ?>
            <div class="state-box error" role="alert">
                <div class="state-icon">⚠️</div>
                <h3>Terjadi Kesalahan Database</h3>
                <p><?= htmlspecialchars($db_error) ?></p>
            </div>

        <?php elseif (mysqli_num_rows($result) === 0): ?>
            <div class="state-box">
                <div class="state-icon">🍽️</div>
                <h3>Menu Belum Tersedia</h3>
                <p>Belum ada menu yang terdaftar. Silakan tambahkan menu terlebih dahulu.</p>
            </div>

        <?php else: ?>
            <!-- Search Bar -->
            <div class="search-bar">
                <div class="search-wrap">
                    <input type="search" id="searchInput" class="search-input" placeholder="🔍 Cari menu (misal: Ayam)..."
                        autocomplete="off" spellcheck="false">
                    <button class="search-clear" id="searchClear" type="button" aria-label="Hapus pencarian">✕</button>
                </div>
            </div>

            <!-- Empty state untuk pencarian -->
            <div class="search-empty" id="search-empty">
                <div class="si">🔍</div>
                <h3>Menu Tidak Ditemukan</h3>
                <p>Coba gunakan kata kunci lain.</p>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar" id="filter-bar" role="group" aria-label="Filter kategori menu">
                <button class="filter-btn active" data-filter="semua" id="filter-semua">
                    🍽️ Semua <span class="filter-count" id="count-semua">0</span>
                </button>
                <button class="filter-btn" data-filter="makanan" id="filter-makanan">
                    🍲 Makanan <span class="filter-count" id="count-makanan">0</span>
                </button>
                <button class="filter-btn" data-filter="minuman" id="filter-minuman">
                    🥤 Minuman <span class="filter-count" id="count-minuman">0</span>
                </button>
                <button class="filter-btn" data-filter="dessert" id="filter-dessert">
                    🍰 Dessert <span class="filter-count" id="count-dessert">0</span>
                </button>
            </div>

            <div class="menu-grid" id="menu-grid">

                <?php while ($menu = mysqli_fetch_assoc($result)): ?>
                    <?php
                    $kategori_lower = strtolower($menu['kategori']);
                    if ($kategori_lower === 'makanan') {
                        $badge_class = 'badge--makanan';
                    } elseif ($kategori_lower === 'minuman') {
                        $badge_class = 'badge--minuman';
                    } else {
                        $badge_class = 'badge--default';
                    }

                    $img_path = 'img/menu/' . htmlspecialchars($menu['foto']);
                    $img_exists = !empty($menu['foto']) && file_exists($img_path);

                    $harga_formatted = 'Rp ' . number_format($menu['harga'], 0, ',', '.');
                    $is_habis = ($menu['stok'] === 'Habis');
                    ?>

                    <article class="card<?= $is_habis ? ' card--habis' : '' ?>" id="card-menu-<?= (int) $menu['id_menu'] ?>"
                        data-kategori="<?= htmlspecialchars(strtolower($menu['kategori'])) ?>">
 <!-- #region 
  
  -->
                        <div class="card__img-wrap">
                            <?php if ($img_exists): ?>
                                <img src="<?= $img_path ?>" alt="Foto <?= htmlspecialchars($menu['nama_menu']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="card__img-placeholder">
                                    <span class="ph-icon">🍴</span>
                                    <span>Foto tidak tersedia</span>
                                </div>
                            <?php endif; ?>

                            <?php if ($is_habis): ?>
                                <span class="badge--habis" aria-label="Stok habis">🔴 Stok Habis</span>
                            <?php endif; ?>

                            <span class="card__badge <?= $badge_class ?>">
                                <?= htmlspecialchars($menu['kategori']) ?>
                            </span>
                        </div>

                        <div class="card__body">
                            <h3 class="card__name"><?= htmlspecialchars($menu['nama_menu']) ?></h3>
                            <p class="card__price"><?= $harga_formatted ?></p>

                            <div class="card__actions">
                                <?php if ($is_habis): ?>
                                    <p class="stok-habis-note" style="width:100%;margin-top:.5rem">
                                        ⚠️ Menu ini sedang tidak tersedia
                                    </p>
                                <?php else: ?>
                                <div class="qty-group">
                                    <label class="qty-label" for="qty-<?= (int) $menu['id_menu'] ?>">Qty</label>
                                    <input type="number" class="qty-input" id="qty-<?= (int) $menu['id_menu'] ?>" value="1"
                                        min="1" max="99"
                                        oninput="document.getElementById('qc-<?= (int) $menu['id_menu'] ?>').value=this.value;document.getElementById('qo-<?= (int) $menu['id_menu'] ?>').value=this.value;">
                                </div>

                                <div class="btn-group">
                                    <form method="POST" action="proses_keranjang.php" style="display:contents"
                                        id="form-keranjang-<?= (int) $menu['id_menu'] ?>">
                                        <input type="hidden" name="id_menu" value="<?= (int) $menu['id_menu'] ?>">
                                        <input type="hidden" name="jumlah_beli" id="qc-<?= (int) $menu['id_menu'] ?>" value="1">
                                        <button type="submit" class="btn-action btn-cart"
                                            id="btn-keranjang-<?= (int) $menu['id_menu'] ?>" title="Tambah ke Keranjang">🛒
                                            Keranjang</button>
                                    </form>

                                    <form method="POST" action="checkout.php" style="display:contents"
                                        id="form-pesan-<?= (int) $menu['id_menu'] ?>">
                                        <input type="hidden" name="id_menu" value="<?= (int) $menu['id_menu'] ?>">
                                        <input type="hidden" name="jumlah_beli" id="qo-<?= (int) $menu['id_menu'] ?>" value="1">
                                        <button type="submit" class="btn-action btn-order"
                                            id="btn-pesan-<?= (int) $menu['id_menu'] ?>" title="Pesan Langsung">⚡ Pesan</button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </article>

                <?php endwhile; ?>

            </div>
        <?php endif; ?>

    </main>

    <footer class="footer" id="site-footer">
        <div class="footer__inner">

            <!-- Kolom Brand -->
            <div>
                <div class="footer__brand-name">Rumah Makan <span>Sipatuo Jr.</span></div>
                <p class="footer__desc">
                    Menyajikan cita rasa khas Minahasa yang autentik dan lezat.
                    Pilih menu favoritmu dan nikmati kelezatannya!
                </p>
            </div>

            <!-- Kolom Sosial Media -->
            <div>
                <div class="footer__col-title">Ikuti Kami</div>
                <a href="https://www.instagram.com/sipatuojr/" target="_blank" rel="noopener noreferrer"
                    class="footer__link" id="link-instagram">
                    <span class="footer__link-icon icon-ig">
                        <img src="img/logo/ig.jpg" alt="Instagram" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">
                    </span>
                    @sipatuojr
                </a>
                <a href="https://web.facebook.com/sipatuojr/" target="_blank" rel="noopener noreferrer"
                    class="footer__link" id="link-facebook">
                    <span class="footer__link-icon icon-fb">
                        <img src="img/logo/fb.png" alt="Facebook" style="width:100%;height:100%;object-fit:contain;border-radius:8px;">
                    </span>
                    Sipatuo Jr.
                </a>
            </div>

            <!-- Kolom Lokasi -->
            <div>
                <div class="footer__col-title">Lokasi Kami</div>
                <a href="https://www.google.com/maps/place/SIPATUO-JR+MINI+SOCCER+%26+CAFE/@0.694815,124.2979114,16.96z" target="_blank" rel="noopener noreferrer" id="link-gmaps" style="display:block;">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d992.62!2d124.2979114!3d0.694815!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x327e3d573025a76f%3A0xb4b3b0fdb8640e4a!2sSIPATUO-JR%20MINI%20SOCCER%20%26%20CAFE!5e0!3m2!1sid!2sid!4v1714900000000"
                        class="footer__map"
                        allowfullscreen=""
                        loading="lazy"
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

</body>

<script>
    let toastTimer = null;

    function showCartToast() {
        const toast = document.getElementById('cart-toast');
        const prog = toast.querySelector('.toast__progress');
        prog.style.animation = 'none';
        void toast.offsetWidth;
        prog.style.animation = '';
        toast.classList.add('toast--show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toast.classList.remove('toast--show');
        }, 3000);
    }

    function updateCartBadge(count) {
        const btn = document.querySelector('.navbar__cart-btn');
        let badge = btn ? btn.querySelector('.cart-badge') : null;
        if (!btn) return;
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'cart-badge';
                btn.appendChild(badge);
            }
            badge.textContent = count;
        } else if (badge) {
            badge.remove();
        }
    }

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.action || !form.action.includes('proses_keranjang.php')) return;
        e.preventDefault();
        const data = new FormData(form);
        fetch('proses_keranjang.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: data
        })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (json.success) {
                    showCartToast();
                    updateCartBadge(json.cart_count);
                }
            })
            .catch(function (err) {
                console.error('Gagal menambah ke keranjang:', err);
            });
    });

    // ── Admin Modal ──────────────────────────────────────────
    const modalOverlay = document.getElementById('modal-admin');
    const btnOpenAdmin = document.getElementById('btn-open-admin');
    const btnCloseModal = document.getElementById('btn-close-modal');
    const formLogin = document.getElementById('form-admin-login');
    const inputUser = document.getElementById('input-username');
    const inputPass = document.getElementById('input-password');
    const errUser = document.getElementById('err-username');
    const errPass = document.getElementById('err-password');
    const errCreds = document.getElementById('err-credentials');

    function openModal() {
        modalOverlay.classList.add('modal--open');
        inputUser.focus();
    }

    function closeModal() {
        modalOverlay.classList.remove('modal--open');
        formLogin.reset();
        [inputUser, inputPass].forEach(el => el.classList.remove('input--error'));
        [errUser, errPass, errCreds].forEach(el => el.style.display = 'none');
    }

    btnOpenAdmin.addEventListener('click', openModal);
    btnCloseModal.addEventListener('click', closeModal);

    modalOverlay.addEventListener('click', function (e) {
        if (e.target === modalOverlay) closeModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modalOverlay.classList.contains('modal--open')) closeModal();
    });

    formLogin.addEventListener('submit', function (e) {
        e.preventDefault();
        let valid = true;

        // Reset errors
        [inputUser, inputPass].forEach(el => el.classList.remove('input--error'));
        [errUser, errPass, errCreds].forEach(el => el.style.display = 'none');

        if (!inputUser.value.trim()) {
            inputUser.classList.add('input--error');
            errUser.style.display = 'block';
            valid = false;
        }
        if (!inputPass.value.trim()) {
            inputPass.classList.add('input--error');
            errPass.style.display = 'block';
            valid = false;
        }
        if (!valid) return;

        // Cek kredensial
        const ADMIN_USER = 'admin';
        const ADMIN_PASS = 'admin123';

        if (inputUser.value.trim() === ADMIN_USER && inputPass.value === ADMIN_PASS) {
            window.location.href = 'dashboard.php';
        } else {
            inputUser.classList.add('input--error');
            inputPass.classList.add('input--error');
            errCreds.style.display = 'block';
        }
    });

    // ── Pencarian & Filter Kategori ──────────────────────────
    (function () {
        const filterBtns = document.querySelectorAll('.filter-btn');
        const cards = document.querySelectorAll('#menu-grid .card');
        const countEl = document.querySelector('.section-header__count');
        const searchInput = document.getElementById('searchInput');
        const searchClear = document.getElementById('searchClear');
        const searchEmpty = document.getElementById('search-empty');

        if (!filterBtns.length || !cards.length) return;

        let activeFilter = 'semua';
        let searchTerm = '';

        // Hitung jumlah per kategori
        const counts = { semua: cards.length, makanan: 0, minuman: 0, dessert: 0 };
        cards.forEach(function (card) {
            const kat = (card.dataset.kategori || '').toLowerCase();
            if (counts[kat] !== undefined) counts[kat]++;
        });

        Object.keys(counts).forEach(function (key) {
            const el = document.getElementById('count-' + key);
            if (el) el.textContent = counts[key];
        });

        // Buat elemen pesan kosong filter
        const filterEmpty = document.createElement('div');
        filterEmpty.className = 'filter-empty';
        filterEmpty.innerHTML = '<div class="state-icon">🔍</div><h3>Tidak ada menu</h3><p>Tidak ada menu dalam kategori ini.</p>';
        document.getElementById('menu-grid').appendChild(filterEmpty);

        function updateVisibility() {
            let visible = 0;
            const term = searchTerm.toLowerCase().trim();

            cards.forEach(function (card) {
                const kat = (card.dataset.kategori || '').toLowerCase();
                const matchFilter = activeFilter === 'semua' || kat === activeFilter;

                let matchSearch = true;
                if (term) {
                    const name = (card.querySelector('.card__name')?.textContent || '').toLowerCase();
                    matchSearch = name.indexOf(term) !== -1;
                }

                const show = matchFilter && matchSearch;
                card.classList.toggle('card--hidden', !show);
                if (show) visible++;
            });

            // Tampilkan/sembunyikan pesan kosong filter (kategori tidak ketemu)
            filterEmpty.classList.toggle('show', visible === 0 && !term);

            // Tampilkan/sembunyikan search empty state  
            const hasSearch = term.length > 0;
            searchEmpty.classList.toggle('show', visible === 0 && hasSearch);

            if (countEl) {
                countEl.textContent = visible + ' item tersedia' + (hasSearch ? ' (pencarian)' : '');
            }
        }

        // ── Filter buttons ──
        filterBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                filterBtns.forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                activeFilter = btn.dataset.filter;
                updateVisibility();
            });
        });

        // ── Search ──
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                searchTerm = this.value;
                searchClear.classList.toggle('show', searchTerm.length > 0);
                updateVisibility();
            });

            searchClear.addEventListener('click', function () {
                searchInput.value = '';
                searchTerm = '';
                searchClear.classList.remove('show');
                updateVisibility();
                searchInput.focus();
            });
        }
    })();
</script>

</html>
<?php
if (isset($koneksi)) {
    mysqli_close($koneksi);
}
?>