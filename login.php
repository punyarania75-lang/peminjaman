<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/koneksi.php';

$error = '';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $password = mysqli_real_escape_string($koneksi, trim($_POST['password']));

    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($koneksi, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        $password_valid = false;
        if (password_verify($password, $user['password'])) {
            $password_valid = true;
        } elseif (md5($password) === $user['password']) {
            $password_valid = true;
        } elseif ($password === $user['password']) {
            $password_valid = true;
        }

        if ($password_valid) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama']     = $user['nama'];
            $_SESSION['role']     = strtolower(trim($user['role']));

            if ($_SESSION['role'] === 'admin') {
                header("Location: admin/admin_dashboard.php");
                exit;
            } elseif ($_SESSION['role'] === 'petugas') {
                header("Location: petugas/petugas_dashboard.php");
                exit;
            } else {
                header("Location: peminjam/peminjam_dashboard.php");
                exit;
            }
        } else {
            $error = "Password yang kamu masukkan salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - WinterOutfit Bromo</title>
    <!-- Bootstrap Offline Lokal -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.css">
    <style>
        :root { --emerald-dark: #062319; --emerald-main: #0B3C2D; --accent-gold: #D4AF37; --bg-soft: #F5F7F6; }
        body { font-family: 'Segoe UI', Roboto, sans-serif; background-color: var(--bg-soft); height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card-login { width: 100%; max-width: 400px; border-radius: 16px; border: none; }
        .btn-gold { background-color: var(--accent-gold); color: var(--emerald-dark); font-weight: 600; border: none; }
        .btn-gold:hover { background-color: #B89628; color: #fff; }
    </style>
</head>
<body>

<div class="card card-login shadow-lg bg-white overflow-hidden">
    <div class="p-4 text-center bg-emerald text-white">
        <h4 class="fw-bold text-warning mb-1"><i class="bi bi-snow2 me-2"></i>WinterOutfit</h4>
        <p class="small text-white-50 mb-0">Masuk ke Akun Portal Bromo</p>
    </div>
    <div class="card-body p-4">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                <i class="bi bi-exclamation-circle-fill me-1"></i> <?= $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-semibold">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
            </div>
            <button type="submit" name="login" class="btn btn-gold w-100 py-2 rounded-3 mb-3">Login Akun</button>
        </form>

        <div class="text-center">
            <span class="small text-muted">Belum punya akun? <a href="register.php" class="fw-bold text-decoration-none" style="color: var(--emerald-main);">Daftar Sekarang</a></span>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>