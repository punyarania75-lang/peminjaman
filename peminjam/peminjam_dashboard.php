<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$nama_user = $_SESSION['nama'];

if (isset($_POST['proses_sewa'])) {
    $id_alat     = intval($_POST['alat_id']);
    $jumlah_sewa = intval($_POST['jumlah_sewa']); // Jumlah barang yang ingin disewa
    $lama_sewa   = intval($_POST['lama_sewa']);
    $tgl_pinjam  = date('Y-m-d');
    $tgl_rencana = date('Y-m-d', strtotime("+$lama_sewa days"));
    
    $q_alat = mysqli_query($koneksi, "SELECT * FROM alat WHERE id = '$id_alat'");
    $d_alat = mysqli_fetch_assoc($q_alat);

    if ($d_alat && $d_alat['stok'] >= $jumlah_sewa && $jumlah_sewa > 0) {
        // Total harga = (Harga sewa per hari * lama sewa) * jumlah barang yang disewa
        $total_harga = ($d_alat['harga_sewa'] * $lama_sewa) * $jumlah_sewa; 
        
        if (mysqli_query($koneksi, "INSERT INTO peminjaman (user_id, alat_id, tgl_pinjam, tgl_kembali_rencana, jumlah, total_harga, status, denda) VALUES ('$user_id', '$id_alat', '$tgl_pinjam', '$tgl_rencana', '$jumlah_sewa', '$total_harga', 'pending', 0)")) {
            // Kurangi stok alat sesuai jumlah yang disewa
            mysqli_query($koneksi, "UPDATE alat SET stok = stok - $jumlah_sewa WHERE id = '$id_alat'");
            header("Location: peminjam_dashboard.php?status=success");
            exit;
        }
    } else {
        // Jika stok tidak cukup
        header("Location: peminjam_dashboard.php?status=gagal_stok");
        exit;
    }
}

$item_sewa = null;
if (isset($_GET['pinjam_id'])) {
    $id_sewa_get = intval($_GET['pinjam_id']);
    $q_get_alat  = mysqli_query($koneksi, "SELECT alat.*, kategori.nama_kategori FROM alat JOIN kategori ON alat.kategori_id = kategori.id WHERE alat.id = '$id_sewa_get'");
    $item_sewa   = mysqli_fetch_assoc($q_get_alat);
}

$query_riwayat = "SELECT peminjaman.*, alat.nama_alat, alat.harga_sewa, alat.foto, kategori.nama_kategori 
                  FROM peminjaman 
                  JOIN alat ON peminjaman.alat_id = alat.id 
                  JOIN kategori ON alat.kategori_id = kategori.id 
                  WHERE peminjaman.user_id = '$user_id' 
                  ORDER BY peminjaman.id DESC";
$result_riwayat = mysqli_query($koneksi, $query_riwayat);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Peminjam - Aplikasi Peminjaman Alat</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/bootstrap-icons.css">
    <style>
        :root { --emerald-dark: #062319; --emerald-main: #0B3C2D; --accent-gold: #D4AF37; --bg-soft: #F5F7F6; }
        body { font-family: 'Segoe UI', Roboto, sans-serif; background-color: var(--bg-soft); }
        .bg-emerald { background-color: var(--emerald-dark) !important; }
        .btn-gold { background-color: var(--accent-gold); color: var(--emerald-dark) !important; font-weight: 600; border: none; }
        .img-thumb { width: 50px; height: 50px; object-fit: contain; background: #fff; border-radius: 6px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-emerald sticky-top shadow-sm">
  <div class="container-fluid px-lg-5">
    <a class="navbar-brand fw-bold text-warning" href="../index.php"><i class="bi bi-snow2 fs-4 me-1"></i> Peminjam Portal</a>
    <div class="d-flex align-items-center gap-2 ms-auto">
      <span class="text-white-50 small me-2">Halo, <strong class="text-white"><?= $nama_user; ?></strong></span>
      <a href="../katalog.php" class="btn btn-sm btn-gold"><i class="bi bi-bag-plus me-1"></i> Lihat Daftar Alat / Katalog</a>
      <a href="../logout.php" class="btn btn-sm btn-outline-light">Logout</a>
    </div>
  </div>
</nav>

<div class="container-fluid px-lg-5 py-4">
    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] === 'success'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> Pengajuan peminjaman alat berhasil diajukan dan menunggu persetujuan petugas!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['status'] === 'gagal_stok'): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> Gagal! Jumlah yang ingin kamu sewa melebihi stok yang tersedia di gudang.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-5">
        <div class="card-header bg-white py-3 px-4 border-bottom">
            <h5 class="fw-bold mb-0 text-success"><i class="bi bi-journal-bookmark-fill me-2"></i>Riwayat Peminjaman & Informasi Denda</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-emerald text-white">
                        <tr>
                            <th class="ps-4">No</th>
                            <th>Foto</th>
                            <th>Nama Alat</th>
                            <th>Jumlah</th>
                            <th>Tgl Pinjam</th>
                            <th>Rencana Kembali</th>
                            <th>Total Biaya</th>
                            <th>Denda</th>
                            <th class="text-center pe-4">Status Peminjaman</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result_riwayat) > 0): ?>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($result_riwayat)): ?>
                                <?php 
                                    $st = strtolower(trim($row['status'])); 
                                    $foto_path = (!empty($row['foto']) && file_exists('../assets/img/' . $row['foto'])) ? '../assets/img/' . $row['foto'] : '../assets/img/no-image.png';
                                    
                                    $tgl_sekarang = date('Y-m-d');
                                    $estimasi_denda = intval($row['denda']);
                                    if ($st !== 'dikembalikan' && $tgl_sekarang > $row['tgl_kembali_rencana']) {
                                        $dt1 = new DateTime($row['tgl_kembali_rencana']);
                                        $dt2 = new DateTime($tgl_sekarang);
                                        $terlambat_hari = $dt1->diff($dt2)->days;
                                        $estimasi_denda = $terlambat_hari * 20000;
                                    }
                                ?>
                                <tr>
                                    <td class="ps-4"><?= $no++; ?></td>
                                    <td><img src="<?= $foto_path; ?>" class="img-thumb border" alt="Foto Alat"></td>
                                    <td class="fw-bold"><?= $row['nama_alat']; ?></td>
                                    <td><span class="badge bg-dark"><?= $row['jumlah']; ?> Unit</span></td>
                                    <td><?= date('d M Y', strtotime($row['tgl_pinjam'])); ?></td>
                                    <td><?= date('d M Y', strtotime($row['tgl_kembali_rencana'])); ?></td>
                                    <td class="fw-bold text-success">Rp <?= number_format(intval($row['total_harga']), 0, ',', '.'); ?></td>
                                    <td>
                                        <?php if ($estimasi_denda > 0): ?>
                                            <span class="text-danger fw-bold">Rp <?= number_format($estimasi_denda, 0, ',', '.'); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Rp 0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-4">
                                        <?php if ($st === 'pending'): ?>
                                            <span class="badge bg-secondary px-3 py-1 rounded-pill">Menunggu Persetujuan</span>
                                        <?php elseif ($st === 'disetujui' || $st === 'dipinjam'): ?>
                                            <span class="badge bg-warning text-dark px-3 py-1 rounded-pill">Sudah Disetujui</span>
                                        <?php else: ?>
                                            <span class="badge bg-success px-3 py-1 rounded-pill">Sudah Dikembalikan</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center py-5 text-muted">Belum ada riwayat sewa. <a href="../katalog.php">Jelajahi daftar alat</a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($item_sewa): ?>
<div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header bg-emerald text-white">
                <h5 class="modal-title fw-bold text-warning">Form Pengajuan Peminjaman Alat</h5>
                <a href="peminjam_dashboard.php" class="btn-close btn-close-white"></a>
            </div>
            <form action="peminjam_dashboard.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="alat_id" value="<?= $item_sewa['id']; ?>">
                    <h6 class="fw-bold mb-1 text-dark"><?= $item_sewa['nama_alat']; ?></h6>
                    <p class="small text-muted mb-2">Harga Sewa: Rp <?= number_format($item_sewa['harga_sewa'], 0, ',', '.'); ?> / hari</p>
                    <p class="small text-success fw-semibold mb-3">Stok Tersedia: <?= $item_sewa['stok']; ?> Unit</p>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Jumlah Unit yang Disewa</label>
                        <input type="number" name="jumlah_sewa" class="form-control" value="1" min="1" max="<?= $item_sewa['stok']; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Durasi Sewa (Hari)</label>
                        <select name="lama_sewa" class="form-select" required>
                            <option value="1">1 Hari</option>
                            <option value="2">2 Hari</option>
                            <option value="3">3 Hari</option>
                            <option value="5">5 Hari</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <a href="peminjam_dashboard.php" class="btn btn-sm btn-secondary">Batal</a>
                    <button type="submit" name="proses_sewa" class="btn btn-sm btn-gold px-4">Ajukan Peminjaman</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>