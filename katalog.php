<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/koneksi.php';

$is_logged_in = isset($_SESSION['user_id']);
$role = $is_logged_in ? $_SESSION['role'] : 'guest';

$kategori_result = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");

$query_alat = "SELECT alat.*, kategori.nama_kategori FROM alat JOIN kategori ON alat.kategori_id = kategori.id ORDER BY alat.id DESC";
$result_alat = mysqli_query($koneksi, $query_alat);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Sewa - WinterOutfit Bromo</title>

    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.css">
    <style>
        :root {
            --emerald-dark: #062319;   
            --emerald-main: #0B3C2D;   
            --accent-gold: #D4AF37;    
            --bg-soft: #F5F7F6;        
        }
        body { font-family: 'Segoe UI', Roboto, sans-serif; background-color: var(--bg-soft); }
        .bg-emerald { background-color: var(--emerald-dark) !important; }
        .btn-gold { background-color: var(--accent-gold); color: var(--emerald-dark) !important; font-weight: 600; border: none; }
        .btn-gold:hover { background-color: #B89628 !important; color: #fff; }
        .card-product { border: none; border-radius: 14px; transition: transform 0.2s ease; overflow: hidden; }
        .card-product:hover { transform: translateY(-4px); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-emerald sticky-top shadow-sm">
  <div class="container px-lg-5">
    <a class="navbar-brand fw-bold text-warning d-flex align-items-center gap-2" href="index.php">
      <i class="bi bi-snow2 fs-4"></i> Katalog WinterOutfit Bromo
    </a>
    <div class="d-flex align-items-center gap-2 ms-auto">
      <?php if ($is_logged_in): ?>
          <?php if ($role === 'peminjam'): ?>
              <a href="peminjam/peminjam_dashboard.php" class="btn btn-sm btn-outline-light"><i class="bi bi-speedometer2 me-1"></i> Dashboard Saya</a>
          <?php elseif ($role === 'admin'): ?>
              <a href="admin/admin_dashboard.php" class="btn btn-sm btn-outline-light"><i class="bi bi-speedometer2 me-1"></i> Admin Panel</a>
          <?php elseif ($role === 'petugas'): ?>
              <a href="petugas/petugas_dashboard.php" class="btn btn-sm btn-outline-light"><i class="bi bi-speedometer2 me-1"></i> Kasir Pos</a>
          <?php endif; ?>
          <a href="logout.php" class="btn btn-sm btn-gold">Logout</a>
      <?php else: ?>
          <a href="login.php" class="btn btn-sm btn-gold px-3">Login / Masuk</a>
          <a href="register.php" class="btn btn-sm btn-outline-light">Daftar</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container py-5">
    
    <div class="text-center mb-5">
        <h2 class="fw-bold" style="color: var(--emerald-main);">Pilihan Jaket & Perlengkapan Gunung Bromo</h2>
        <p class="text-muted">Pilih perlengkapan terbaik untuk kenyamanan perjalanan wisatamu di Bromo.</p>
    </div>

    <div class="row g-4">
        <?php if (mysqli_num_rows($result_alat) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result_alat)): ?>
                <?php 
                    $foto = (!empty($row['foto']) && file_exists('assets/img/'.$row['foto'])) ? 'assets/img/'.$row['foto'] : 'https://via.placeholder.com/300x200?text=No+Image';
                    $stok = intval($row['stok']);
                ?>
                <div class="col-md-4 col-lg-3">
                    <div class="card card-product shadow-sm bg-white h-100">
                        <div class="position-relative bg-light text-center p-3" style="height: 200px;">
                            <img src="<?= $foto; ?>" class="img-fluid h-100 object-fit-contain" alt="<?= $row['nama_alat']; ?>">
                            <span class="badge position-absolute top-0 start-0 m-3 bg-secondary bg-opacity-75"><?= $row['nama_kategori']; ?></span>
                        </div>
                        <div class="card-body d-flex flex-column p-4">
                            <h5 class="fw-bold text-dark mb-1"><?= $row['nama_alat']; ?></h5>
                            <span class="badge bg-light text-dark border align-self-start mb-2">Ukuran: <?= $row['ukuran']; ?></span>
                            
                            <div class="mb-3 mt-auto">
                                <span class="text-muted small">Harga Sewa:</span>
                                <div class="fw-bold fs-5 text-success">Rp <?= number_format(intval($row['harga_sewa']), 0, ',', '.'); ?> <span class="small text-muted fw-normal">/hari</span></div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small <?= $stok > 0 ? 'text-muted' : 'text-danger fw-bold'; ?>">
                                    Stok: <strong><?= $stok; ?></strong>
                                </span>

                                <?php if ($stok > 0): ?>
                                    <?php if ($is_logged_in && $role === 'peminjam'): ?>
                                        <a href="peminjam/peminjam_dashboard.php?pinjam_id=<?= $row['id']; ?>" class="btn btn-sm btn-gold px-3">
                                            <i class="bi bi-cart-plus me-1"></i> Sewa Sekarang
                                        </a>
                                    <?php elseif (!$is_logged_in): ?>
                                        <a href="login.php" class="btn btn-sm btn-gold px-3" onclick="alert('Silakan login terlebih dahulu untuk melakukan peminjaman.');">
                                            <i class="bi bi-cart-plus me-1"></i> Sewa
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary border">Mode Lihat</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary px-3" disabled>Habis</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5 text-muted">
                <i class="bi bi-box-seam fs-1 d-block mb-2"></i>
                Belum ada produk perlengkapan yang tersedia di katalog.
            </div>
        <?php endif; ?>
    </div>

</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>