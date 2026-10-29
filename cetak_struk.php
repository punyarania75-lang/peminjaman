<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_transaksi = isset($_GET['id']) ? intval($_GET['id']) : 0;

$query = "SELECT peminjaman.*, users.nama AS nama_peminjam, users.no_hp, alat.nama_alat, alat.harga_sewa 
          FROM peminjaman 
          JOIN users ON peminjaman.user_id = users.id 
          JOIN alat ON peminjaman.alat_id = alat.id 
          WHERE peminjaman.id = '$id_transaksi'";
$result = mysqli_query($koneksi, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo "Data transaksi tidak ditemukan!";
    exit;
}

$raw_h = floatval($data['total_harga']);
$total_biaya = ($raw_h > 0) ? intval($raw_h) : intval(floatval($data['harga_sewa']) * 2);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Sewa #INV-<?= sprintf("%04d", $data['id']); ?></title>
    <!-- Bootstrap Offline Lokal -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body { background: #eee; font-family: monospace; }
        .receipt { max-width: 400px; margin: 30px auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        @media print {
            body { background: #fff; }
            .no-print { display: none; }
            .receipt { box-shadow: none; margin: 0; padding: 0; width: 100%; }
        }
    </style>
</head>
<body onload="window.print();">

<div class="receipt">
    <div class="text-center border-bottom pb-3 mb-3">
        <h5 class="fw-bold mb-1">WINTEROUTFIT BROMO</h5>
        <p class="text-muted small mb-0">Pos Penyewaan Resmi Gunung Bromo<br>Telp: 0812-3456-7890</p>
    </div>

    <div class="mb-3 small">
        <div class="d-flex justify-content-between"><span>No. Nota:</span><strong>#INV-<?= sprintf("%04d", $data['id']); ?></strong></div>
        <div class="d-flex justify-content-between"><span>Tanggal:</span><span><?= date('d/m/Y H:i', strtotime($data['created_at'] ?? 'now')); ?></span></div>
        <div class="d-flex justify-content-between"><span>Penyewa:</span><strong><?= $data['nama_peminjam']; ?></strong></div>
        <div class="d-flex justify-content-between"><span>No. HP:</span><span><?= !empty($data['no_hp']) ? $data['no_hp'] : '-'; ?></span></div>
    </div>

    <div class="border-top border-bottom py-2 mb-3 small">
        <div class="d-flex justify-content-between fw-bold mb-1">
            <span>Item Perlengkapan</span>
            <span>Subtotal</span>
        </div>
        <div class="d-flex justify-content-between">
            <span><?= $data['nama_alat']; ?> (1x)</span>
            <span>Rp <?= number_format($total_biaya, 0, ',', '.'); ?></span>
        </div>
    </div>

    <div class="d-flex justify-content-between fw-bold mb-4">
        <span>TOTAL PEMBAYARAN (TUNAI):</span>
        <span class="text-success">Rp <?= number_format($total_biaya, 0, ',', '.'); ?></span>
    </div>

    <div class="text-center small text-muted border-top pt-3">
        <p class="mb-1">Terima kasih telah menyewa di tempat kami!</p>
        <p class="mb-0">Barang harap dikembalikan tepat waktu.</p>
    </div>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print();" class="btn btn-sm btn-dark w-100">Cetak Ulang Struk</button>
    </div>
</div>

</body>
</html>