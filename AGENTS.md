# AGENTS.md — Rumah Makan Sipatuo Jr.

Flat PHP (mysqli) restaurant ordering system. No framework, no build tools, no tests.

## Setup
- **DB**: MySQL `db_rumah_makan`, creds in `koneksi.php` (`root` / `0000`)
- **Serve**: PHP + Apache (Laragon) under `/rumahmakan/` subdirectory
- **No schema file** — tables `menu`, `pesanan`, `detail_pesanan` must pre-exist
- **`menu` columns**: `id_menu`, `nama_menu`, `kategori`, `harga`, `foto`, `stok` (`Tersedia`/`Habis`)
- **`DESIGN.md`** is an Airbnb design tokens reference — not project docs. Ignore it.

## Access
- **Customer**: `index.php` (menu catalog with filter + live search)
- **Admin panel**: any visitor can reach `dashboard.php` — **no server-side auth**. Login is a JS modal *in `index.php`* (not `dashboard.php`) with hardcoded creds `admin` / `admin123`, on submit it simply redirects to `dashboard.php`. No auth persistence — just bookmark `dashboard.php` to bypass it entirely.

## Key Architecture
- **Session cart**: `$_SESSION['keranjang']` keyed by `id_menu`
- **Add to cart**: `proses_keranjang.php` (POST, returns JSON if `X-Requested-With: XMLHttpRequest` or redirects to `keranjang.php`)
- **Cart mgmt**: `keranjang.php?hapus=<id>` removes item; `?kosongkan` clears cart
- **Two checkout paths** (both redirect to `struk.php?id=` on success):
  - Single-item: `checkout.php` → `proses_pesanan.php`
  - Multi-item cart: `keranjang.php` → `proses_pesanan_multi.php`
- **Invoice page**: `struk.php?id=` reads `pesanan` + `detail_pesanan` JOIN `menu`, shows order summary
- **Order statuses**: `Pending` → `Dimasak` → `Dikirim` → `Selesai`
- **Status updates** via `proses_status.php` (AJAX or regular POST, sets `$_SESSION['flash_status']` for redirect)
- **Image uploads**: menu photos → `img/menu/`, payment proofs → `img/bukti/`
- **Payment methods**: DANA, GoPay, OVO, ShopeePay, QRIS, COD (all route to same phone `0813-4352-6694`)
- **Admin menu CRUD**: `kelola_menu.php` → `proses_menu.php` (add/edit/delete with file upload)
- **Menu categories**: Makanan, Minuman, Dessert, Snack (but `index.php` filter UI only shows Makanan, Minuman, Dessert)
- **Live search** on `index.php` (Vanilla JS, works alongside category filter)
- **Best seller widget** on `dashboard.php` (top 5 by `SUM(jumlah_beli)`)
- **Instagram**: `@sipatuojr` linked in footer of `index.php` and `keranjang.php`

## Notable Gotchas
- `dashboard.php` uses string interpolation for SQL `WHERE` clauses with `mysqli_real_escape_string` (no prepared statements)
- `dashboard.php` main query JOINs `pesanan` + `detail_pesanan` + `menu`, so multi-item orders produce duplicate rows in the table
- `proses_pesanan_multi.php` uses `die()` for validation errors; `proses_pesanan.php` shows errors inline
- `proses_pesanan_multi.php` has **no rollback** — if a `detail_pesanan` insert fails, the parent `pesanan` row remains orphan
- `proses_pesanan.php` includes manual rollback (deletes orphan `pesanan` if `detail_pesanan` insert fails)
- `Transfer Bank` is in the server-side valid method list but has **no UI radio button**
- QRIS image must exist at `img/qris_toko.jpeg`
- `checkout.php` sends `total_harga` as a hidden form field — client-tamperable (single-item path only)
- `proses_pesanan_multi.php` computes total from DB prices — not affected by client tampering
- No CSRF tokens, no pagination on dashboard, no server-side auth
- `proses_status.php` uses OOP mysqli (`$koneksi->prepare()`); other files are a mix of OOP and procedural
- `proses_menu.php` uses `__DIR__ . '/img/menu/'` (absolute path); other uploads use relative `'img/bukti/'`
- `proses_pesanan.php` uses `Plus Jakarta Sans` font; all other pages use `Inter`
- Stray `<!-- #region -->` comment fragment at `index.php:1187`
- Payment proof upload: JPG/JPEG/PNG max 2MB. Menu photo upload: JPG/PNG/WebP/GIF max 3MB.
- Items with `stok = 'Habis'` render in `index.php` with an overlay and no add-to-cart button (cards still visible)
- `index.php` filter bar only has Makanan, Minuman, Dessert — Snack items never get a dedicated filter pill

## CSS & Frontend Conventions
- **All CSS is inline** in `<style>` tags within each PHP file — no external stylesheets
- **`kelola_menu.php` uses minified CSS** (one line per rule-set); other files use pretty-print. Match the file's existing style when editing
- **`kelola_menu.php` and `dashboard.php` share layout**: sidebar + topbar + `.main-panel` + `.content`. Both now have a `.bottom-nav` for mobile (≤840px, 3 items). Fixes usually apply to both
- **`</style>` tag is fragile**: positioned just before `</head>` after the responsive rules. Easy to accidentally delete when editing the last CSS block — ensure it's present
- **Breakpoints used across admin pages**: 900px (layout collapse), 840px (sidebar→bottom nav), 640px (table→card), 600px (topbar compact), 480px (compact pills), 400px (stats 1fr)
- **Bottom nav** added manually to both `dashboard.php` and `kelola_menu.php`. Keep in sync: same 3 links, `.bottom-nav--item.active`, fixed `z-index: 100` above FAB `z-index: 99`
- **FAB** (`.fab`) on `kelola_menu.php`: pill-shaped, `border-radius: var(--air-radius-full)`, positioned `bottom: 80px` (or `calc(64px + var(--air-space-base))` when bottom nav is visible)
