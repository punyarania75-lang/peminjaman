<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/koneksi.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WinterOutfit Bromo - Persewaan Perlengkapan Cold Weather</title>
    
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.css">
    <style>
        :root {
            --emerald-dark: #062319;   
            --emerald-main: #0B3C2D;   
            --emerald-hover: #041E15;  
            --accent-gold: #D4AF37;    
            --gold-hover: #B89628;     
            --bg-soft: #F5F7F6;        
            --text-dark: #1C2826;      
        }

        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-soft);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .bg-emerald {
            background-color: var(--emerald-dark) !important;
        }

        .btn-gold {
            background-color: var(--accent-gold);
            color: var(--emerald-dark);
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-gold:hover {
            background-color: var(--gold-hover);
            color: var(--emerald-dark);
            transform: translateY(-2px);
        }

        .hero-section {
            background: linear-gradient(135deg, rgba(6, 35, 25, 0.95) 0%, rgba(11, 60, 45, 0.90) 100%), 
                        url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1350&q=80') center/cover no-repeat;
            color: white;
            padding: 120px 0 130px 0;
            flex: 1;
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-emerald sticky-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold text-warning d-flex align-items-center gap-2" href="index.php">
      <i class="bi bi-snow2 fs-4"></i> WinterOutfit Bromo
    </a>
    
    <div class="d-flex align-items-center gap-2 ms-auto">
      <?php if (isset($_SESSION['user_id'])): ?>
        <?php 
            $role = $_SESSION['role'] ?? 'peminjam';
            $dash_url = ($role === 'admin') ? 'admin/admin_dashboard.php' : (($role === 'petugas') ? 'petugas/petugas_dashboard.php' : 'peminjam/peminjam_dashboard.php');
        ?>
        <a href="<?= $dash_url; ?>" class="btn btn-sm btn-gold">Dashboard Saya</a>
        <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-sm btn-outline-light px-3">Login</a>
        <a href="register.php" class="btn btn-sm btn-gold px-3">Daftar Akun</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<section class="hero-section text-center">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-9 col-md-10">
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3 shadow-sm">
          <i class="bi bi-snow me-1"></i> Persewaan Alat Hangat Bromo
        </span>
        
        <h1 class="display-4 fw-bold mb-3">Nikmati Keindahan Bromo<br>Tanpa Rasa Dingin</h1>
        <p class="lead text-white-50 mb-4 fs-5">
          Sewa perlengkapan musim dingin lengkap, hangat, dan siap pakai untuk liburan impianmu.
        </p>

        <div class="d-flex justify-content-center">
          <a href="katalog.php" class="btn btn-gold btn-lg px-5 py-3 shadow">
            <i class="bi bi-grid-fill me-2"></i> Lihat Katalog & Sewa
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="bg-emerald text-white-50 py-3 mt-auto border-top border-secondary">
  <div class="container text-center small">
    <p class="mb-1 text-white">&copy; <?= date('Y'); ?> <b>WinterOutfit Bromo</b> - Aplikasi Peminjaman</p>
    <a href="login.php" class="text-warning text-decoration-none me-2"><i class="bi bi-lock-fill"></i> Portal Staf (Admin & Petugas)</a>
  </div>
</footer>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>