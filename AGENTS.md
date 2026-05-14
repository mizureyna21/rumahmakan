# AGENTS.md — Rumah Makan Sipatuo Jr.

Flat PHP (mysqli) restaurant ordering system. No framework, no build tools.

## Setup
- **DB**: MySQL `db_rumah_makan`, creds in `koneksi.php` (`root` / `0000`)
- **Serve**: PHP + Apache (Laragon) under `/rumahmakan/` subdirectory
- **No schema file** — tables `menu`, `pesanan`, `detail_pesanan` must pre-exist
- **`menu` columns**: `id_menu`, `nama_menu`, `kategori`, `harga`, `foto`, `stok` (`Tersedia`/`Habis`)
- **`DESIGN.md`** contains Airbnb design tokens — not project docs

## Access
- **Customer**: `index.php` (menu catalog with filter + live search)
- **Admin panel**: any visitor can reach `dashboard.php` — **no server-side auth**. Login is a JS modal with hardcoded creds: `admin` / `admin123`.

## Key Architecture
- **Session cart**: `$_SESSION['keranjang']` keyed by `id_menu`
- **Add to cart**: `proses_keranjang.php` (POST, returns JSON if `X-Requested-With: XMLHttpRequest` or redirects to `keranjang.php`)
- **Cart mgmt**: `keranjang.php?hapus=<id>` removes item; `keranjang.php?kosongkan` clears cart
- **Two checkout paths** (both redirect to `struk.php?id=` on success):
  - Single-item: `checkout.php` → `proses_pesanan.php`
  - Multi-item cart: `keranjang.php` → `proses_pesanan_multi.php`
- **Invoice page**: `struk.php?id=` (reads `pesanan` + `detail_pesanan` JOIN `menu`, shows order summary)
- **Order statuses**: `Pending` → `Dimasak` → `Dikirim` → `Selesai`
- **Status updates** via `proses_status.php` (AJAX or regular POST, sets `$_SESSION['flash_status']` for redirect)
- **Image uploads**: menu photos → `img/menu/`, payment proofs → `img/bukti/`
- **Payment methods**: DANA, GoPay, OVO, ShopeePay, QRIS, COD (all route to same phone `0813-4352-6694`)
- **Admin menu CRUD**: `kelola_menu.php` → `proses_menu.php` (add/edit/delete with file upload). Categories: Makanan, Minuman, Dessert, Snack.
- **Live search** on `index.php` (Vanilla JS, works alongside category filter)
- **Best seller widget** on `dashboard.php` (top 5 by `SUM(jumlah_beli)`)
- **Instagram**: `@sipatuojr` linked in footer of `index.php` and `keranjang.php`

## Notable Gotchas
- `dashboard.php` uses string interpolation for SQL `WHERE` clauses (filter params, escaped with `mysqli_real_escape_string`)
- `proses_pesanan_multi.php` uses `die()` for validation errors. `proses_pesanan.php` shows errors inline instead.
- `Transfer Bank` is in server-side valid method list but has **no UI radio button**
- QRIS image must exist at `img/qris_toko.jpeg`
- `checkout.php` sends `total_harga` as a hidden form field — client-tamperable
- No CSRF tokens anywhere
- No pagination on dashboard
- `<!-- #region -->` comment fragment appears mid-file in `index.php:1187`
- Payment proof upload: JPG/JPEG/PNG max 2MB. Menu photo upload: JPG/PNG/WebP/GIF max 3MB.
- `proses_pesanan.php` includes rollback logic (deletes orphan `pesanan` row if `detail_pesanan` insert fails)
- `proses_status.php` uses OOP mysqli (`$koneksi->prepare()`) while all other files use procedural (`mysqli_prepare()`)
- `proses_menu.php` uses `__DIR__ . '/img/menu/'` (absolute); other uploads use relative `'img/bukti/'`
