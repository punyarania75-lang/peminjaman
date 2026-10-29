<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../login.php");
    exit;
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'peminjaman';

if (isset($_GET['aksi']) && $_GET['aksi'] === 'setujui') {
    $id_p = intval($_GET['id']);
    mysqli_query($koneksi, "UPDATE peminjaman SET status = 'disetujui' WHERE id = '$id_p'");
    header("Location: petugas_dashboard.php?tab=peminjaman&msg=approved");
    exit;
}

if (isset($_GET['aksi']) && $_GET['aksi'] === 'kembali') {
    $id_p = intval($_GET['id']);
    $id_a = intval($_GET['alat_id']);
    $jml  = intval($_GET['jumlah']);
    $tgl_real = date('Y-m-d');

    $q_trx = mysqli_query($koneksi, "SELECT tgl_kembali_rencana FROM peminjaman WHERE id = '$id_p'");
    $d_trx = mysqli_fetch_assoc($q_trx);
    
    $denda = 0;
    if ($d_trx && $tgl_real > $d_trx['tgl_kembali_rencana']) {
        $datetime1 = new DateTime($d_trx['tgl_kembali_rencana']);
        $datetime2 = new DateTime($tgl_real);
        $selisih_hari = $datetime1->diff($datetime2)->days;
        
        $denda = $selisih_hari * 20000;
    }
    
    mysqli_query($koneksi, "UPDATE peminjaman SET status = 'dikembalikan', tgl_kembali_real = '$tgl_real', denda = '$denda' WHERE id = '$id_p'");
    
    mysqli_query($koneksi, "UPDATE alat SET stok = stok + $jml WHERE id = '$id_a'");
    
    header("Location: petugas_dashboard.php?tab=peminjaman&msg=returned&denda=$denda");
    exit;
}


$query_peminjaman = "SELECT peminjaman.*, users.nama AS nama_peminjam, users.no_hp, alat.nama_alat, alat.harga_sewa, alat.id AS id_alat_ref 
                     FROM peminjaman 
                     JOIN users ON peminjaman.user_id = users.id 
                     JOIN alat ON peminjaman.alat_id = alat.id 
                     ORDER BY peminjaman.id DESC";
$result_peminjaman = mysqli_query($koneksi, $query_peminjaman);


$result_customers = mysqli_query($koneksi, "SELECT DISTINCT users.id, users.nama, users.no_hp FROM users JOIN peminjaman ON users.id = peminjaman.user_id ORDER BY users.nama ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas - Aplikasi Peminjaman Alat</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/bootstrap-icons.css">
    <style>
        :root { --emerald-dark: #062319; --emerald-main: #0B3C2D; --accent-gold: #D4AF37; --bg-soft: #F5F7F6; }
        body { font-family: 'Segoe UI', Roboto, sans-serif; background-color: var(--bg-soft); }
        .bg-emerald { background-color: var(--emerald-dark) !important; }
        .btn-gold { background-color: var(--accent-gold); color: var(--emerald-dark) !important; font-weight: 600; border: none; }
        .nav-pills .nav-link { color: #495057; font-weight: 600; border-radius: 10px; padding: 10px 16px; margin-bottom: 5px; }
        .nav-pills .nav-link.active { background-color: var(--emerald-main) !important; color: #fff !important; }
        @media print {
            body * { visibility: hidden; }
            #printableArea, #printableArea * { visibility: visible; }
            #printableArea { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-emerald sticky-top shadow-sm no-print">
  <div class="container-fluid px-lg-4">
    <a class="navbar-brand fw-bold text-warning" href="petugas_dashboard.php">
        <i class="bi bi-person-badge-fill me-2"></i>Dashboard Petugas
    </a>
    <div class="d-flex align-items-center gap-3 ms-auto">
      <span class="text-white-50 small">Petugas: <strong class="text-white"><?= $_SESSION['nama']; ?></strong></span>
      <a href="../logout.php" class="btn btn-sm btn-gold">Logout</a>
    </div>
  </div>
</nav>

<div class="container-fluid px-lg-4 py-4">
    
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> 
            <?php if ($_GET['msg'] === 'returned'): ?>
                Pengembalian berhasil diproses! Denda keterlambatan: Rp <?= number_format(intval($_GET['denda']), 0, ',', '.'); ?>
            <?php elseif ($_GET['msg'] === 'approved'): ?>
                Peminjaman berhasil disetujui! Barang siap diambil oleh penyewa.
            <?php else: ?>
                Aksi berhasil diproses!
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
       
        <div class="col-md-3 col-lg-2 no-print">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="nav flex-column nav-pills" role="tablist">
                    <a href="petugas_dashboard.php?tab=peminjaman" class="nav-link <?= $active_tab === 'peminjaman' ? 'active' : ''; ?>">
                        <i class="bi bi-journal-check me-2"></i> Peminjaman & Denda
                    </a>
                    <a href="petugas_dashboard.php?tab=laporan" class="nav-link <?= $active_tab === 'laporan' ? 'active' : ''; ?>">
                        <i class="bi bi-file-earmark-text me-2"></i> Laporan Customer
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-9 col-lg-10">
        
            <?php if ($active_tab === 'peminjaman'): ?>
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-success"><i class="bi bi-journal-check me-2"></i>Pemantauan Peminjaman & Cek Denda</h5>
                        <button onclick="window.print()" class="btn btn-sm btn-outline-dark">
                            <i class="bi bi-printer me-1"></i> Cetak Data
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-emerald text-white">
                                    <tr>
                                        <th class="ps-4">No. Nota</th>
                                        <th>Peminjam</th>
                                        <th>Alat (Jumlah)</th>
                                        <th>Rencana Kembali</th>
                                        <th>Status & Estimasi Denda</th>
                                        <th class="text-center">Aksi Petugas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($result_peminjaman) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($result_peminjaman)): ?>
                                            <?php 
                                                $st = strtolower(trim($row['status'])); 
                                                $tgl_sekarang = date('Y-m-d');
                                                $terlambat_hari = 0;
                                                $estimasi_denda = 0;

                                                if ($st === 'disetujui' && $tgl_sekarang > $row['tgl_kembali_rencana']) {
                                                    $dt1 = new DateTime($row['tgl_kembali_rencana']);
                                                    $dt2 = new DateTime($tgl_sekarang);
                                                    $terlambat_hari = $dt1->diff($dt2)->days;
                                                    $estimasi_denda = $terlambat_hari * 20000;
                                                }
                                            ?>
                                            <tr>
                                                <td class="ps-4 fw-bold text-muted">#INV-<?= sprintf("%04d", $row['id']); ?></td>
                                                <td class="fw-bold"><?= $row['nama_peminjam']; ?></td>
                                                <td><?= $row['nama_alat']; ?> <span class="badge bg-dark">(<?= $row['jumlah']; ?> Unit)</span></td>
                                                <td><?= date('d/m/Y', strtotime($row['tgl_kembali_rencana'])); ?></td>
                                                <td>
                                                    <?php if ($st === 'pending'): ?>
                                                        <span class="badge bg-secondary">Menunggu Persetujuan</span>
                                                    <?php elseif ($st === 'disetujui'): ?>
                                                        <?php if ($estimasi_denda > 0): ?>
                                                            <span class="badge bg-danger">Terlambat <?= $terlambat_hari; ?> Hari</span>
                                                            <div class="small text-danger fw-bold mt-1">Denda: Rp <?= number_format($estimasi_denda, 0, ',', '.'); ?></div>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning text-dark">Sedang Disetujui / Dipinjam</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Sudah Dikembalikan</span>
                                                        <?php if (isset($row['denda']) && $row['denda'] > 0): ?>
                                                            <div class="small text-muted">Denda Dibayar: Rp <?= number_format($row['denda'], 0, ',', '.'); ?></div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <a href="../cetak_struk.php?id=<?= $row['id']; ?>" target="_blank" class="btn btn-sm btn-outline-success me-1 mb-1" title="Cetak Struk">
                                                        <i class="bi bi-printer"></i> Struk
                                                    </a>
                                                    
                                                    <?php if ($st === 'pending'): ?>
                                                        <!-- Tombol 1: Setujui Peminjaman -->
                                                        <a href="petugas_dashboard.php?aksi=setujui&id=<?= $row['id']; ?>" class="btn btn-sm btn-primary mb-1">
                                                            <i class="bi bi-check-circle me-1"></i> Setujui
                                                        </a>
                                                    <?php elseif ($st === 'disetujui'): ?>
                                                        <!-- Tombol 2: Terima Pengembalian Barang -->
                                                        <a href="petugas_dashboard.php?aksi=kembali&id=<?= $row['id']; ?>&alat_id=<?= $row['id_alat_ref']; ?>&jumlah=<?= $row['jumlah']; ?>" class="btn btn-sm btn-success mb-1" onclick="return confirm('Konfirmasi bahwa barang sudah dikembalikan?<?= $estimasi_denda > 0 ? " Terdeteksi denda keterlambatan sebesar Rp " . number_format($estimasi_denda, 0, ',', '.') : ""; ?>')">
                                                            <i class="bi bi-box-arrow-in-down-left me-1"></i> Terima Kembali
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-dark border">Selesai</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data peminjaman.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($active_tab === 'laporan'): ?>
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom">
                        <h5 class="fw-bold mb-0 text-success"><i class="bi bi-file-earmark-text me-2"></i>Pilih Laporan Berdasarkan Customer</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <?php if ($result_customers && mysqli_num_rows($result_customers) > 0): ?>
                                <?php while($cust = mysqli_fetch_assoc($result_customers)): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card border shadow-sm rounded-3 p-3 h-100 bg-light">
                                            <h6 class="fw-bold text-dark mb-1"><i class="bi bi-person-circle me-1 text-success"></i> <?= $cust['nama']; ?></h6>
                                            <p class="small text-muted mb-3">No HP: <?= !empty($cust['no_hp']) ? $cust['no_hp'] : '-'; ?></p>
                                            <div class="mt-auto">
                                                <a href="petugas_dashboard.php?tab=laporan&customer_id=<?= $cust['id']; ?>" class="btn btn-sm btn-gold w-100">
                                                    <i class="bi bi-eye me-1"></i> Lihat & Cetak Laporan
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-12 text-center py-4 text-muted">Belum ada data customer.</div>
                            <?php endif; ?>
                        </div>

                        <?php if (isset($_GET['customer_id'])): ?>
                            <?php 
                            $c_id = intval($_GET['customer_id']);
                            $q_c_info = mysqli_query($koneksi, "SELECT * FROM users WHERE id = '$c_id'");
                            $c_info = mysqli_fetch_assoc($q_c_info);

                            $q_c_trans = mysqli_query($koneksi, "SELECT peminjaman.*, alat.nama_alat, alat.harga_sewa FROM peminjaman JOIN alat ON peminjaman.alat_id = alat.id WHERE peminjaman.user_id = '$c_id' ORDER BY peminjaman.id DESC");
                            ?>
                            <div class="mt-5 pt-4 border-top" id="printableArea">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h5 class="fw-bold text-success mb-1">Laporan Riwayat Peminjaman: <?= $c_info['nama']; ?></h5>
                                        <p class="small text-muted mb-0">Nomor Telepon: <?= $c_info['no_hp']; ?></p>
                                    </div>
                                    <button onclick="window.print()" class="btn btn-sm btn-dark no-print">
                                        <i class="bi bi-printer me-1"></i> Cetak Laporan Customer Ini
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle mb-0">
                                        <thead class="bg-emerald text-white">
                                            <tr>
                                                <th class="ps-3">No. Nota</th>
                                                <th>Nama Alat</th>
                                                <th>Jumlah</th>
                                                <th>Tgl Pinjam</th>
                                                <th>Rencana Kembali</th>
                                                <th>Denda</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (mysqli_num_rows($q_c_trans) > 0): ?>
                                                <?php while($tr = mysqli_fetch_assoc($q_c_trans)): ?>
                                                    <tr>
                                                        <td class="ps-3 fw-bold">#INV-<?= sprintf("%04d", $tr['id']); ?></td>
                                                        <td class="fw-bold"><?= $tr['nama_alat']; ?></td>
                                                        <td><?= $tr['jumlah']; ?> Unit</td>
                                                        <td><?= date('d/m/Y', strtotime($tr['tgl_pinjam'])); ?></td>
                                                        <td><?= date('d/m/Y', strtotime($tr['tgl_kembali_rencana'])); ?></td>
                                                        <td>Rp <?= number_format($tr['denda'], 0, ',', '.'); ?></td>
                                                        <td><span class="badge bg-secondary"><?= ucfirst($tr['status']); ?></span></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr><td colspan="7" class="text-center py-3 text-muted">Belum ada transaksi.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>