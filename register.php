<?php
require_once 'config/koneksi.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = mysqli_real_escape_string($koneksi, trim($_POST['nama']));
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $password = mysqli_real_escape_string($koneksi, trim($_POST['password']));
    $no_hp    = mysqli_real_escape_string($koneksi, trim($_POST['no_hp']));

    if (empty($nama) || empty($username) || empty($password)) {
        $error = 'Harap isi semua kolom wajib!';
    } else {
      
        $check_query = "SELECT * FROM users WHERE username = '$username'";
        $check_res   = mysqli_query($koneksi, $check_query);

        if (mysqli_num_rows($check_res) > 0) {
            $error = 'Username sudah digunakan, silakan pilih username lain.';
        } else {
            
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            $query = "INSERT INTO users (nama, username, password, no_hp, role) 
                      VALUES ('$nama', '$username', '$hashed_password', '$no_hp', 'peminjam')";

            if (mysqli_query($koneksi, $query)) {
                $success = 'Pendaftaran berhasil! Silakan login untuk mulai menyewa.';
            } else {
                $error = 'Gagal mendaftar: ' . mysqli_error($koneksi);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - WinterOutfit Bromo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --emerald-dark: #062319;
            --emerald-main: #0B3C2D;
            --emerald-hover: #041E15;
            --accent-gold: #D4AF37;
            --gold-hover: #B89628;
            --bg-soft: #F5F7F6;
        }

        body {
            font-family: 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg-soft);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }

        .card-auth {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(6, 35, 25, 0.12);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
        }

        .auth-header {
            background-color: var(--emerald-dark);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .btn-gold {
            background-color: var(--accent-gold);
            color: var(--emerald-dark) !important;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-gold:hover, .btn-gold:focus, .btn-gold:active {
            background-color: var(--gold-hover) !important;
            color: var(--emerald-dark) !important;
        }

        .btn-emerald {
            background-color: var(--emerald-main);
            color: #ffffff;
            border: none;
            font-weight: 600;
        }

        .btn-emerald:hover {
            background-color: var(--emerald-hover);
            color: #ffffff;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="card card-auth bg-white">
        <div class="auth-header">
            <a href="index.php" class="text-decoration-none text-warning fw-bold fs-4 d-inline-flex align-items-center gap-2 mb-2">
                <i class="bi bi-snow2"></i> WinterOutfit Bromo
            </a>
            <h5 class="fw-normal text-white-50 mb-0">Buat Akun Peminjam Baru</h5>
        </div>

        <div class="card-body p-4">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success py-2 small text-center" role="alert">
                    <i class="bi bi-check-circle-fill me-1"></i> <?= $success; ?><br>
                    <a href="login.php" class="fw-bold text-success text-decoration-underline mt-2 d-inline-block">Klik di sini untuk Login</a>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nomor WhatsApp / HP</label>
                    <input type="tel" name="no_hp" class="form-control">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-emerald w-100 py-2 rounded-3 mb-3">
                    <i class="bi bi-person-plus-fill me-1"></i> Daftar Sekarang
                </button>
            </form>

            <div class="text-center small text-muted">
                Sudah punya akun? <a href="login.php" class="text-emerald fw-bold text-decoration-none" style="color: var(--emerald-main);">Login di sini</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>