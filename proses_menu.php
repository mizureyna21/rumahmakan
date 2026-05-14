<?php
// proses_menu.php — Backend CRUD Menu | Rumah Makan Sipatuo Jr.
include 'koneksi.php';

define('UPLOAD_DIR', __DIR__ . '/img/menu/');
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('MAX_SIZE', 3 * 1024 * 1024);

function redirect(string $msg, bool $ok = true): never {
    session_start();
    $_SESSION['flash'] = ['type' => $ok ? 'success' : 'error', 'message' => $msg];
    header('Location: kelola_menu.php');
    exit;
}

function uploadFoto(array $file, ?string $lama = null): string|false {
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return false;
    if ($file['error'] !== UPLOAD_ERR_OK) redirect('Error upload: ' . $file['error'], false);

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_TYPES, true)) redirect('Tipe file tidak diizinkan. Gunakan JPG, PNG, atau WebP.', false);
    if ($file['size'] > MAX_SIZE) redirect('Ukuran foto melebihi 3 MB.', false);

    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $nama    = 'menu_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest    = UPLOAD_DIR . $nama;

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $dest)) redirect('Gagal menyimpan foto.', false);

    if ($lama && $lama !== 'default.jpg' && is_file(UPLOAD_DIR . $lama)) {
        @unlink(UPLOAD_DIR . $lama);
    }
    return $nama;
}

$aksi = $_POST['aksi'] ?? '';

// ── TAMBAH ──────────────────────────────────────────────────
if ($aksi === 'tambah') {
    $nama     = trim($_POST['nama_menu'] ?? '');
    $kategori = trim($_POST['kategori']  ?? '');
    $harga    = (int)($_POST['harga']    ?? 0);
    $stok     = ($_POST['stok'] ?? '') === 'Habis' ? 'Habis' : 'Tersedia';

    if ($nama === '' || $kategori === '' || $harga <= 0)
        redirect('Nama, kategori, dan harga wajib diisi.', false);

    $foto = 'default.jpg';
    if (!empty($_FILES['foto']['name'])) {
        $r = uploadFoto($_FILES['foto']);
        if ($r !== false) $foto = $r;
    }

    $stmt = $koneksi->prepare("INSERT INTO menu (nama_menu, kategori, harga, foto, stok) VALUES (?,?,?,?,?)");
    $stmt->bind_param('ssiss', $nama, $kategori, $harga, $foto, $stok);
    $stmt->execute() ? redirect("Menu \"$nama\" berhasil ditambahkan.") : redirect('Gagal tambah: '.$stmt->error, false);
    $stmt->close();
}

// ── EDIT ────────────────────────────────────────────────────
elseif ($aksi === 'edit') {
    $id       = (int)($_POST['id_menu']   ?? 0);
    $nama     = trim($_POST['nama_menu']  ?? '');
    $kategori = trim($_POST['kategori']   ?? '');
    $harga    = (int)($_POST['harga']     ?? 0);
    $stok     = ($_POST['stok'] ?? '') === 'Habis' ? 'Habis' : 'Tersedia';

    if ($id <= 0 || $nama === '' || $kategori === '' || $harga <= 0)
        redirect('Data tidak valid.', false);

    $qf = $koneksi->prepare("SELECT foto FROM menu WHERE id_menu = ?");
    $qf->bind_param('i', $id); $qf->execute();
    $qf->bind_result($fotoLama); $qf->fetch(); $qf->close();

    $foto = $fotoLama ?? 'default.jpg';
    if (!empty($_FILES['foto']['name'])) {
        $r = uploadFoto($_FILES['foto'], $fotoLama);
        if ($r !== false) $foto = $r;
    }

    $stmt = $koneksi->prepare("UPDATE menu SET nama_menu=?,kategori=?,harga=?,foto=?,stok=? WHERE id_menu=?");
    $stmt->bind_param('ssissi', $nama, $kategori, $harga, $foto, $stok, $id);
    $stmt->execute() ? redirect("Menu \"$nama\" berhasil diperbarui.") : redirect('Gagal update: '.$stmt->error, false);
    $stmt->close();
}

// ── HAPUS ───────────────────────────────────────────────────
elseif ($aksi === 'hapus') {
    $id = (int)($_POST['id_menu'] ?? 0);
    if ($id <= 0) redirect('ID tidak valid.', false);

    $qf = $koneksi->prepare("SELECT foto, nama_menu FROM menu WHERE id_menu = ?");
    $qf->bind_param('i', $id); $qf->execute();
    $qf->bind_result($foto, $nama); $qf->fetch(); $qf->close();

    $stmt = $koneksi->prepare("DELETE FROM menu WHERE id_menu = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        if ($foto && $foto !== 'default.jpg' && is_file(UPLOAD_DIR . $foto)) @unlink(UPLOAD_DIR . $foto);
        redirect("Menu \"$nama\" berhasil dihapus.");
    } else {
        redirect('Gagal hapus: '.$stmt->error, false);
    }
    $stmt->close();
} else {
    redirect('Permintaan tidak valid.', false);
}
mysqli_close($koneksi);
