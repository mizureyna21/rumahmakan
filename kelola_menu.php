<?php
// kelola_menu.php — Manajemen Menu | Rumah Makan Sipatuo Jr.
session_start();
include 'koneksi.php';

// Flash message
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Ambil data menu
$result = $koneksi->query("SELECT id_menu, nama_menu, kategori, harga, foto, stok FROM menu ORDER BY kategori, nama_menu");
$menus  = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$total  = count($menus);

function rupiah(int $n): string { return 'Rp ' . number_format($n,0,',','.'); }

// Data edit (jika ada ?edit=id)
$edit = null;
if (isset($_GET['edit'])) {
    $eid  = (int)$_GET['edit'];
    $stmt = $koneksi->prepare("SELECT * FROM menu WHERE id_menu = ?");
    $stmt->bind_param('i', $eid); $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Kelola daftar menu Rumah Makan Sipatuo Jr. – tambah, edit, dan hapus menu.">
<title>Kelola Menu – RM Sipatuo Jr.</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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

        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body {
            font-family: 'Inter', -apple-system, system-ui, Roboto, 'Helvetica Neue', sans-serif;
            background: var(--air-surface-soft);
            color: var(--air-ink);
            min-height: 100vh;
            display: flex;
            -webkit-font-smoothing: antialiased;
        }
        a{text-decoration:none;color:inherit}

        /* ══════════════════════════════════════════════
           SIDEBAR
        ══════════════════════════════════════════════ */
        .sidebar{width:var(--sidebar-w);min-height:100vh;background:#1a1a2e;display:flex;flex-direction:column;flex-shrink:0;position:sticky;top:0;align-self:flex-start;height:100vh}
        .sidebar__brand{display:flex;align-items:center;gap:.65rem;padding:1.4rem 1.2rem 1.2rem;border-bottom:1px solid rgba(255,255,255,.07)}
        .sidebar__logo{width:38px;height:38px;background:var(--air-primary);border-radius:var(--air-radius-sm);display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0}
        .sidebar__title{font-size:.9rem;font-weight:600;color:#fff;line-height:1.25}
        .sidebar__title small{display:block;font-size:.68rem;font-weight:400;color:rgba(255,255,255,.45);margin-top:1px}
        .sidebar__nav{flex:1;padding:1rem .75rem;display:flex;flex-direction:column;gap:.2rem}
        .nav-label{font-size:.65rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.3);padding:.6rem .5rem .3rem}
        .nav-item{display:flex;align-items:center;gap:.65rem;padding:.55rem .85rem;border-radius:var(--air-radius-sm);font-size:.85rem;font-weight:500;color:rgba(255,255,255,.55);transition:background var(--air-transition),color var(--air-transition)}
        .nav-item:hover{background:rgba(255,255,255,.06);color:rgba(255,255,255,.9)}
        .nav-item.active{background:var(--air-primary);color:#fff}
        .nav-item .nav-icon{font-size:1rem;width:20px;text-align:center}
        .sidebar__footer{padding:1rem 1.2rem;border-top:1px solid rgba(255,255,255,.07);font-size:.75rem;color:rgba(255,255,255,.3)}

        /* ══════════════════════════════════════════════
           MAIN PANEL
        ══════════════════════════════════════════════ */
        .main-panel{flex:1;min-width:0;display:flex;flex-direction:column}
        .topbar{background:var(--air-canvas);border-bottom:1px solid var(--air-hairline);padding:0 var(--air-space-xl);height:64px;display:flex;align-items:center;justify-content:space-between;gap:1rem;position:sticky;top:0;z-index:50}
        .topbar__breadcrumb{display:flex;align-items:center;gap:.5rem;font-size:.85rem;color:var(--air-muted)}
        .topbar__breadcrumb .current{font-weight:600;color:var(--air-ink)}
        .topbar__sep{color:var(--air-hairline)}
        .topbar__actions{display:flex;align-items:center;gap:.75rem}
        .content{padding:var(--air-space-xl);flex:1}
        .page-heading{margin-bottom:1.5rem}
        .page-heading h1{font-size:1.4rem;font-weight:600;letter-spacing:-.4px;margin-bottom:.25rem}
        .page-heading p{font-size:.88rem;color:var(--air-muted)}

        /* ══════════════════════════════════════════════
           FLASH
        ══════════════════════════════════════════════ */
        .flash{display:flex;align-items:center;gap:.75rem;padding:.75rem 1.2rem;border-radius:var(--air-radius-sm);margin-bottom:1.5rem;font-size:.875rem;font-weight:500;border:1px solid}
        .flash.success{background:#f0fdf4;color:#15803d;border-color:#bbf7d0}
        .flash.error{background:#fef2f2;color:#dc2626;border-color:#fecaca}

        /* ══════════════════════════════════════════════
           LAYOUT GRID
        ══════════════════════════════════════════════ */
        .layout-grid{display:grid;grid-template-columns:1fr 360px;gap:1.5rem;align-items:start}

        /* ══════════════════════════════════════════════
           TABLE CARD
        ══════════════════════════════════════════════ */
        .table-card{background:var(--air-canvas);border-radius:var(--air-radius-md);border:1px solid var(--air-hairline);overflow:hidden}
        .table-card__header{padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;border-bottom:1px solid var(--air-hairline)}
        .table-card__title{display:flex;align-items:center;gap:.55rem}
        .table-card__title-icon{width:32px;height:32px;background:var(--air-surface-soft);border-radius:var(--air-radius-sm);display:flex;align-items:center;justify-content:center;font-size:1rem}
        .table-card__title h2{font-size:1rem;font-weight:600;letter-spacing:-.2px}
        .table-card__count{font-size:.75rem;font-weight:500;color:var(--air-muted);background:var(--air-surface-soft);border:1px solid var(--air-hairline);padding:.2rem .7rem;border-radius:var(--air-radius-full)}
        .table-wrap{overflow-x:auto}
        .menu-table{width:100%;border-collapse:collapse;font-size:.85rem}
        .menu-table thead tr{border-bottom:1px solid var(--air-hairline)}
        .menu-table thead th{padding:.75rem 1rem;text-align:left;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--air-muted);white-space:nowrap}
        .menu-table thead th.center{text-align:center}
        .menu-table tbody tr{border-bottom:1px solid var(--air-hairline-soft);transition:background var(--air-transition)}
        .menu-table tbody tr:hover{background:var(--air-surface-soft)}
        .menu-table tbody tr:last-child{border-bottom:none}
        .menu-table tbody td{padding:.7rem 1rem;vertical-align:middle}
        .menu-table tbody td.center{text-align:center}

        .thumb{width:48px;height:48px;border-radius:var(--air-radius-sm);object-fit:cover;border:1px solid var(--air-hairline)}
        .thumb-ph{width:48px;height:48px;border-radius:var(--air-radius-sm);background:var(--air-surface-soft);display:flex;align-items:center;justify-content:center;font-size:1.3rem;border:1px solid var(--air-hairline)}
        .cell-name{font-weight:600}
        .cell-name small{display:block;font-size:.75rem;font-weight:500;color:var(--air-muted)}
        .badge-kat{display:inline-block;font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;padding:.18rem .6rem;border-radius:var(--air-radius-full)}
        .badge-kat.makanan{background:#fff7ed;color:#c2410c}
        .badge-kat.minuman{background:#eff6ff;color:#1d4ed8}
        .badge-kat.other{background:#f0fdf4;color:#065f46}
        .badge-stok{display:inline-flex;align-items:center;gap:.25rem;font-size:.72rem;font-weight:600;padding:.2rem .65rem;border-radius:var(--air-radius-full)}
        .badge-stok.tersedia{background:#f0fdf4;color:#15803d}
        .badge-stok.habis{background:#fef2f2;color:#dc2626}
        .cell-price{font-weight:600;color:var(--air-primary);font-size:.9rem;white-space:nowrap}

        .action-btns{display:flex;gap:.4rem;justify-content:center}
        .btn-edit,.btn-del{display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .75rem;border-radius:var(--air-radius-sm);font-family:inherit;font-size:.78rem;font-weight:500;cursor:pointer;border:1px solid;transition:border-color var(--air-transition),background var(--air-transition)}
        .btn-edit{background:var(--air-canvas);color:var(--air-ink);border-color:var(--air-hairline)}
        .btn-edit:hover{border-color:var(--air-ink)}
        .btn-del{background:var(--air-canvas);color:#dc2626;border-color:var(--air-hairline)}
        .btn-del:hover{border-color:#dc2626}

        /* ══════════════════════════════════════════════
           FORM CARD
        ══════════════════════════════════════════════ */
        .form-card{background:var(--air-canvas);border-radius:var(--air-radius-md);border:1px solid var(--air-hairline);overflow:hidden;position:sticky;top:80px}
        .form-card__header{padding:1rem 1.4rem;border-bottom:1px solid var(--air-hairline);display:flex;align-items:center;gap:.55rem}
        .form-card__header-icon{width:32px;height:32px;background:var(--air-surface-soft);border-radius:var(--air-radius-sm);display:flex;align-items:center;justify-content:center;font-size:1rem}
        .form-card__header h2{font-size:.95rem;font-weight:600}
        .form-body{padding:1.4rem}
        .form-group{margin-bottom:1rem}

        .form-label{display:block;font-size:.78rem;font-weight:600;color:var(--air-ink);margin-bottom:.4rem}
        .form-label span{color:var(--air-error-text);margin-left:2px}
        .form-control{width:100%;padding:.6rem .85rem;border:1px solid var(--air-hairline);border-radius:var(--air-radius-sm);font-family:inherit;font-size:.875rem;color:var(--air-ink);background:var(--air-canvas);transition:border-color var(--air-transition);outline:none}
        .form-control:focus{border-color:var(--air-ink);border-width:2px}
        .form-hint{font-size:.72rem;color:var(--air-muted);margin-top:.3rem}

        /* ── Foto Preview ── */
        .foto-preview{width:100%;height:130px;border-radius:var(--air-radius-sm);object-fit:cover;border:1px solid var(--air-hairline);margin-bottom:.5rem;display:block}
        .foto-preview-ph{width:100%;height:130px;border-radius:var(--air-radius-sm);background:var(--air-surface-soft);border:2px dashed var(--air-hairline);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.3rem;font-size:.82rem;color:var(--air-muted);margin-bottom:.5rem}

        /* ── Submit btn (Rausch primary CTA) ── */
        .btn-submit{width:100%;padding:.75rem;background:var(--air-primary);color:var(--air-on-primary);border:none;border-radius:var(--air-radius-sm);font-family:inherit;font-size:.9rem;font-weight:500;cursor:pointer;transition:background var(--air-transition);display:flex;align-items:center;justify-content:center;gap:.45rem;height:48px}
        .btn-submit:hover{background:var(--air-primary-active)}

        .btn-cancel{display:block;text-align:center;margin-top:.65rem;font-size:.82rem;font-weight:500;color:var(--air-muted);padding:.4rem;border-radius:var(--air-radius-sm);transition:color var(--air-transition)}
        .btn-cancel:hover{color:var(--air-ink)}

        .btn-outline-sm{display:inline-flex;align-items:center;gap:.4rem;padding:.45rem 1rem;border:1px solid var(--air-hairline);border-radius:var(--air-radius-sm);font-family:inherit;font-size:.82rem;font-weight:500;color:var(--air-ink);background:transparent;cursor:pointer;transition:border-color var(--air-transition)}
        .btn-outline-sm:hover{border-color:var(--air-ink)}

        /* ══════════════════════════════════════════════
           STATES
        ══════════════════════════════════════════════ */
        .state-box{padding:3rem 2rem;text-align:center}
        .state-box .si{font-size:2.8rem;margin-bottom:.6rem}
        .state-box h3{font-size:1rem;font-weight:600;margin-bottom:.25rem}
        .state-box p{font-size:.85rem;color:var(--air-muted)}

        /* ══════════════════════════════════════════════
           RESPONSIVE
        ══════════════════════════════════════════════ */
        @media(max-width:900px){.layout-grid{grid-template-columns:1fr}.form-card{position:static}}
        @media(max-width:840px){.sidebar{display:none}.content{padding:var(--air-space-lg)}.topbar{padding:0 var(--air-space-lg)}}
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" aria-label="Navigasi admin">
  <div class="sidebar__brand">
    <div class="sidebar__logo">🍽️</div>
    <div class="sidebar__title">RM Sipatuo Jr.<small>Panel Admin</small></div>
  </div>
  <nav class="sidebar__nav">
    <span class="nav-label">Menu Utama</span>
    <a href="dashboard.php" class="nav-item" id="nav-dashboard"><span class="nav-icon">📊</span> Dashboard</a>
    <a href="index.php" class="nav-item" id="nav-katalog"><span class="nav-icon">🛍️</span> Katalog Menu</a>
    <span class="nav-label" style="margin-top:.5rem">Manajemen</span>
    <a href="kelola_menu.php" class="nav-item active" id="nav-kelola"><span class="nav-icon">🍴</span> Kelola Menu</a>
  </nav>
  <div class="sidebar__footer">&copy; <?= date('Y') ?> RM Sipatuo Jr.</div>
</aside>

<!-- MAIN PANEL -->
<div class="main-panel">
  <header class="topbar">
    <div class="topbar__breadcrumb">
      <span>Admin</span><span class="topbar__sep">›</span>
      <span class="current">Kelola Menu</span>
    </div>
    <div class="topbar__actions">
      <a href="dashboard.php" class="btn-outline-sm">📊 Dashboard</a>
      <a href="index.php" class="btn-outline-sm">🛍️ Lihat Katalog</a>
    </div>
  </header>

  <main class="content">
    <div class="page-heading">
      <h1>Kelola Menu Restoran</h1>
      <p>Tambah, edit, dan hapus item menu. Total <strong><?= $total ?></strong> menu terdaftar.</p>
    </div>

    <?php if ($flash): ?>
    <div class="flash <?= $flash['type'] ?>" role="alert">
      <?= $flash['type'] === 'success' ? '✅' : '❌' ?>
      <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>

    <div class="layout-grid">

      <!-- ── TABEL MENU ── -->
      <div class="table-card" id="table-card-menu">
        <div class="table-card__header">
          <div class="table-card__title">
            <div class="table-card__title-icon">🍴</div>
            <h2>Daftar Menu</h2>
          </div>
          <span class="table-card__count"><?= $total ?> item</span>
        </div>

        <?php if (empty($menus)): ?>
        <div class="state-box">
          <div class="si">🍽️</div>
          <h3>Belum Ada Menu</h3>
          <p>Tambahkan menu pertama menggunakan form di samping.</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
          <table class="menu-table" id="menu-table" aria-label="Tabel daftar menu">
            <thead>
              <tr>
                <th>No</th>
                <th>Foto</th>
                <th>Nama Menu</th>
                <th>Kategori</th>
                <th>Harga</th>
                <th class="center">Stok</th>
                <th class="center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($menus as $i => $m): ?>
              <?php
                $fotoSrc = !empty($m['foto']) && $m['foto'] !== 'default.jpg' && file_exists(__DIR__.'/img/menu/'.$m['foto'])
                  ? 'img/menu/' . htmlspecialchars($m['foto'])
                  : null;
                $katClass = strtolower($m['kategori']) === 'makanan' ? 'makanan'
                           : (strtolower($m['kategori']) === 'minuman' ? 'minuman' : 'other');
                $stokClass = $m['stok'] === 'Habis' ? 'habis' : 'tersedia';
              ?>
              <tr id="row-menu-<?= $m['id_menu'] ?>">
                <td style="color:var(--clr-muted);font-weight:600;font-size:.8rem"><?= $i+1 ?></td>
                <td>
                  <?php if ($fotoSrc): ?>
                    <img src="<?= $fotoSrc ?>" alt="<?= htmlspecialchars($m['nama_menu']) ?>" class="thumb">
                  <?php else: ?>
                    <div class="thumb-ph">🍽️</div>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="cell-name">
                    <?= htmlspecialchars($m['nama_menu']) ?>
                    <small>#<?= str_pad($m['id_menu'],4,'0',STR_PAD_LEFT) ?></small>
                  </div>
                </td>
                <td><span class="badge-kat <?= $katClass ?>"><?= htmlspecialchars($m['kategori']) ?></span></td>
                <td class="cell-price"><?= rupiah((int)$m['harga']) ?></td>
                <td class="center">
                  <span class="badge-stok <?= $stokClass ?>">
                    <?= $m['stok'] === 'Habis' ? '🔴 Habis' : '🟢 Tersedia' ?>
                  </span>
                </td>
                <td class="center">
                  <div class="action-btns">
                    <a href="kelola_menu.php?edit=<?= $m['id_menu'] ?>" class="btn-edit" id="btn-edit-<?= $m['id_menu'] ?>">✏️ Edit</a>
                    <form method="POST" action="proses_menu.php" onsubmit="return confirm('Hapus menu ini?')" style="display:inline">
                      <input type="hidden" name="aksi" value="hapus">
                      <input type="hidden" name="id_menu" value="<?= $m['id_menu'] ?>">
                      <button type="submit" class="btn-del" id="btn-hapus-<?= $m['id_menu'] ?>">🗑️ Hapus</button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- ── FORM TAMBAH / EDIT ── -->
      <div class="form-card" id="form-card-menu">
        <div class="form-card__header">
          <div class="form-card__header-icon"><?= $edit ? '✏️' : '➕' ?></div>
          <h2><?= $edit ? 'Edit Menu' : 'Tambah Menu Baru' ?></h2>
        </div>
        <div class="form-body">
          <form method="POST" action="proses_menu.php" enctype="multipart/form-data" id="form-menu">
            <input type="hidden" name="aksi" value="<?= $edit ? 'edit' : 'tambah' ?>">
            <?php if ($edit): ?>
            <input type="hidden" name="id_menu" value="<?= $edit['id_menu'] ?>">
            <?php endif; ?>

            <!-- Preview Foto -->
            <?php
              $prevSrc = $edit && !empty($edit['foto']) && $edit['foto'] !== 'default.jpg'
                && file_exists(__DIR__.'/img/menu/'.$edit['foto'])
                ? 'img/menu/' . htmlspecialchars($edit['foto']) : null;
            ?>
            <div class="form-group">
              <?php if ($prevSrc): ?>
                <img src="<?= $prevSrc ?>" alt="Preview foto" class="foto-preview" id="foto-preview-img">
              <?php else: ?>
                <div class="foto-preview-ph" id="foto-preview-ph"><span style="font-size:2rem">📷</span><span>Preview foto</span></div>
              <?php endif; ?>
              <label class="form-label" for="foto">Foto Menu</label>
              <input type="file" name="foto" id="foto" accept="image/*" class="form-control"
                onchange="previewFoto(this)">
              <p class="form-hint">JPG/PNG/WebP · Maks 3 MB <?= $edit ? '· Kosongkan jika tidak ganti foto' : '' ?></p>
            </div>

            <div class="form-group">
              <label class="form-label" for="nama_menu">Nama Menu <span>*</span></label>
              <input type="text" name="nama_menu" id="nama_menu" class="form-control" required
                maxlength="100" placeholder="cth. Ayam Rica-Rica"
                value="<?= $edit ? htmlspecialchars($edit['nama_menu']) : '' ?>">
            </div>

            <div class="form-group">
              <label class="form-label" for="kategori">Kategori <span>*</span></label>
              <select name="kategori" id="kategori" class="form-control" required>
                <option value="">— Pilih Kategori —</option>
                <?php foreach (['Makanan','Minuman','Dessert','Snack'] as $kat): ?>
                <option value="<?= $kat ?>" <?= ($edit && $edit['kategori'] === $kat) ? 'selected' : '' ?>><?= $kat ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="harga">Harga (Rp) <span>*</span></label>
              <input type="number" name="harga" id="harga" class="form-control" required
                min="500" step="500" placeholder="cth. 25000"
                value="<?= $edit ? (int)$edit['harga'] : '' ?>">
            </div>

            <div class="form-group">
              <label class="form-label" for="stok">Status Stok <span>*</span></label>
              <select name="stok" id="stok" class="form-control" required>
                <option value="Tersedia" <?= (!$edit || $edit['stok'] === 'Tersedia') ? 'selected' : '' ?>>🟢 Tersedia</option>
                <option value="Habis" <?= ($edit && $edit['stok'] === 'Habis') ? 'selected' : '' ?>>🔴 Habis</option>
              </select>
            </div>

            <button type="submit" class="btn-submit" id="btn-submit-menu">
              <?= $edit ? '💾 Simpan Perubahan' : '➕ Tambah Menu' ?>
            </button>
            <?php if ($edit): ?>
            <a href="kelola_menu.php" class="btn-cancel" id="btn-batal-edit">✖ Batal Edit</a>
            <?php endif; ?>
          </form>
        </div>
      </div>

    </div><!-- /.layout-grid -->
  </main>
</div><!-- /.main-panel -->

<script>
function previewFoto(input) {
  const ph  = document.getElementById('foto-preview-ph');
  const img = document.getElementById('foto-preview-img');
  if (!input.files || !input.files[0]) return;
  const url = URL.createObjectURL(input.files[0]);
  if (img) { img.src = url; }
  else {
    const newImg = document.createElement('img');
    newImg.src = url; newImg.alt = 'Preview'; newImg.className = 'foto-preview';
    newImg.id = 'foto-preview-img';
    if (ph) ph.replaceWith(newImg);
    else document.querySelector('.form-group').prepend(newImg);
  }
}
</script>
</body>
</html>
<?php mysqli_close($koneksi); ?>
