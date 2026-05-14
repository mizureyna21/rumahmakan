<?php
// ============================================================
//  proses_status.php — Update status pesanan (AJAX / form)
//  Rumah Makan Sipatuo Jr. | Prepared Statements · PHP 8.x
// ============================================================
include 'koneksi.php';

// Daftar status yang valid (whitelist)
const STATUS_VALID = ['Pending', 'Dimasak', 'Dikirim', 'Selesai'];

// Deteksi apakah request berasal dari AJAX (fetch/XHR)
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Helper: kirim JSON error lalu exit
function jsonError(string $msg, int $code = 400): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'message' => $msg]);
    exit;
}

// Helper: redirect dengan flash (fallback non-AJAX)
function redirectFlash(string $msg, bool $ok = true): never {
    session_start();
    $_SESSION['flash_status'] = ['type' => $ok ? 'success' : 'error', 'message' => $msg];
    header('Location: dashboard.php' . (!empty($_POST['filter']) ? '?status=' . urlencode($_POST['filter']) : ''));
    exit;
}

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $isAjax ? jsonError('Method not allowed.', 405) : header('Location: dashboard.php') . exit();
}

$id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
$status_baru = trim($_POST['status'] ?? '');

// Validasi
if ($id_pesanan <= 0) {
    $isAjax ? jsonError('ID pesanan tidak valid.') : redirectFlash('ID pesanan tidak valid.', false);
}
if (!in_array($status_baru, STATUS_VALID, true)) {
    $isAjax ? jsonError('Status tidak dikenali.') : redirectFlash('Status tidak valid.', false);
}

// UPDATE dengan prepared statement
$stmt = $koneksi->prepare("UPDATE pesanan SET status = ? WHERE id_pesanan = ?");
$stmt->bind_param('si', $status_baru, $id_pesanan);

if (!$stmt->execute()) {
    $isAjax ? jsonError('Gagal update: ' . $stmt->error, 500) : redirectFlash('Gagal memperbarui status.', false);
}

if ($stmt->affected_rows === 0) {
    $isAjax ? jsonError('Pesanan tidak ditemukan.', 404) : redirectFlash('Pesanan tidak ditemukan.', false);
}
$stmt->close();
mysqli_close($koneksi);

// Respons
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'ok'         => true,
        'id_pesanan' => $id_pesanan,
        'status'     => $status_baru,
        'message'    => 'Status pesanan #' . str_pad($id_pesanan, 5, '0', STR_PAD_LEFT) . ' diperbarui ke ' . $status_baru,
    ]);
    exit;
}

redirectFlash('Status pesanan #' . str_pad($id_pesanan, 5, '0', STR_PAD_LEFT) . ' diperbarui ke ' . $status_baru . '.', true);
