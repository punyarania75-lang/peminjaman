<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'stok';

function catat_log($koneksi, $user_id, $aktivitas) {
    $user_id   = intval($user_id);
    $aktivitas = mysqli_real_escape_string($koneksi, $aktivitas);
    $check_log = mysqli_query($koneksi, "SHOW TABLES LIKE 'log_aktivitas'");
    if ($check_log && mysqli_num_rows($check_log) > 0) {
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (user_id, aktivitas, created_at) VALUES ('$user_id', '$aktivitas', NOW())");
    }
}

if (isset($_POST['tambah_alat'])) {
    $nama_alat   = mysqli_real_escape_string($koneksi, trim($_POST['nama_alat']));
    $kategori_id = intval($_POST['kategori_id']);
    $ukuran      = mysqli_real_escape_string($koneksi, trim($_POST['ukuran']));
    $harga_sewa  = floatval($_POST['harga_sewa']);
    $stok        = intval($_POST['stok']);

    $foto_name = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_name = time() . '_' . uniqid() . '.' . strtolower($ext);
        $target_dir = '../assets/img/';
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        move_uploaded_file($_FILES['foto']['tmp_name'], $target_dir . $foto_name);
    }

    $q_add = "INSERT INTO alat (nama_alat, kategori_id, ukuran, harga_sewa, stok, foto) VALUES ('$nama_alat', '$kategori_id', '$ukuran', '$harga_sewa', '$stok', '$foto_name')";
    if (mysqli_query($koneksi, $q_add)) {
        catat_log($koneksi, $_SESSION['user_id'], "Menambahkan alat: $nama_alat");
        header("Location: admin_dashboard.php?tab=stok&msg=success");
        exit;
    }
}

if (isset($_POST['tambah_kategori'])) {
    $nama_kat = mysqli_real_escape_string($koneksi, trim($_POST['nama_kategori']));
    if (mysqli_query($koneksi, "INSERT INTO kategori (nama_kategori) VALUES ('$nama_kat')")) {
        catat_log($koneksi, $_SESSION['user_id'], "Menambahkan kategori: $nama_kat");
        header("Location: admin_dashboard.php?tab=kategori&msg=success");
        exit;
    }
}
if (isset($_GET['hapus_kategori'])) {
    $id_kat = intval($_GET['hapus_kategori']);
    mysqli_query($koneksi, "DELETE FROM kategori WHERE id = '$id_kat'");
    catat_log($koneksi, $_SESSION['user_id'], "Menghapus kategori ID: $id_kat");
    header("Location: admin_dashboard.php?tab=kategori&msg=success");
    exit;
}

if (isset($_POST['tambah_user'])) {
    $nama     = mysqli_real_escape_string($koneksi, trim($_POST['nama']));
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = mysqli_real_escape_string($koneksi, $_POST['role']);
    $no_hp    = mysqli_real_escape_string($koneksi, trim($_POST['no_hp']));

    if (mysqli_query($koneksi, "INSERT INTO users (nama, username, password, role, no_hp) VALUES ('$nama', '$username', '$password', '$role', '$no_hp')")) {
        catat_log($koneksi, $_SESSION['user_id'], "Menambahkan user baru: $username ($role)");
        header("Location: admin_dashboard.php?tab=users&msg=success");
        exit;
    }
}
if (isset($_GET['hapus_user'])) {
    $id_u = intval($_GET['hapus_user']);
    if ($id_u !== intval($_SESSION['user_id'])) {
        mysqli_query($koneksi, "DELETE FROM users WHERE id = '$id_u'");
        catat_log($koneksi, $_SESSION['user_id'], "Menghapus user ID: $id_u");
    }
    header("Location: admin_dashboard.php?tab=users&msg=success");
    exit;
}

$result_alat = mysqli_query($koneksi, "SELECT alat.*, kategori.nama_kategori FROM alat JOIN kategori ON alat.kategori_id = kategori.id ORDER BY alat.id DESC");
$result_kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY id DESC");
$result_users = mysqli_query($koneksi, "SELECT * FROM users ORDER BY id DESC");
$result_peminjaman = mysqli_query($koneksi, "SELECT peminjaman.*, users.nama AS nama_peminjam, alat.nama_alat FROM peminjaman JOIN users ON peminjaman.user_id = users.id JOIN alat ON peminjaman.alat_id = alat.id ORDER BY peminjaman.id DESC");
$result_log = mysqli_query($koneksi, "SELECT log_aktivitas.*, users.nama FROM log_aktivitas JOIN users ON log_aktivitas.user_id = users.id ORDER BY log_aktivitas.id DESC LIMIT 30");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Aplikasi Peminjaman Alat</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/bootstrap-icons.css">
    <style>
        :root { --emerald-dark: #062319; --emerald-main: #0B3C2D; --accent-gold: #D4AF37; --bg-soft: #F5F7F6; }
        body { font-family: 'Segoe UI', Roboto, sans-serif; background-color: var(--bg-soft); }
        .bg-emerald { background-color: var(--emerald-dark) !important; }
        .btn-gold { background-color: var(--accent-gold); color: var(--emerald-dark) !important; font-weight: 600; border: none; }
        .nav-pills .nav-link { color: #495057; font-weight: 600; border-radius: 10px; padding: 10px 16px; margin-bottom: 5px; }
        .nav-pills .nav-link.active { background-color: var(--emerald-main) !important; color: #fff !important; }
        .img-thumb { width: 45px; height: 45px; object-fit: contain; background: #fff; border-radius: 6px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-emerald sticky-top shadow-sm">
  <div class="container-fluid px-lg-4">
    <a class="navbar-brand fw-bold text-warning" href="admin_dashboard.php"><i class="bi bi-shield-lock-fill me-2"></i>Admin Panel</a>
    <div class="d-flex align-items-center gap-3 ms-auto">
      <span class="text-white-50 small">Admin: <strong class="text-white"><?= $_SESSION['nama']; ?></strong></span>
      <a href="../logout.php" class="btn btn-sm btn-gold">Logout</a>
    </div>
  </div>
</nav>

<div class="container-fluid px-lg-4 py-4">
    <div class="row g-4">
        <div class="col-md-3 col-lg-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="nav flex-column nav-pills" role="tablist">
                    <a href="admin_dashboard.php?tab=stok" class="nav-link <?= $active_tab === 'stok' ? 'active' : ''; ?>"><i class="bi bi-box-seam me-2"></i>  Alat</a>
                    <a href="admin_dashboard.php?tab=kategori" class="nav-link <?= $active_tab === 'kategori' ? 'active' : ''; ?>"><i class="bi bi-tags me-2"></i> Kategori</a>
                    <a href="admin_dashboard.php?tab=users" class="nav-link <?= $active_tab === 'users' ? 'active' : ''; ?>"><i class="bi bi-people me-2"></i>  User</a>
                    <a href="admin_dashboard.php?tab=peminjaman" class="nav-link <?= $active_tab === 'peminjaman' ? 'active' : ''; ?>"><i class="bi bi-journal-check me-2"></i> Peminjaman</a>
                    <a href="admin_dashboard.php?tab=log" class="nav-link <?= $active_tab === 'log' ? 'active' : ''; ?>"><i class="bi bi-clock-history me-2"></i> Log Aktivitas</a>
                </div>
            </div>
        </div>

        <div class="col-md-9 col-lg-10">
            <?php if ($active_tab === 'stok'): ?>
                <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
                        <h5 class="fw-bold mb-0 text-success"><i class="bi bi-box-seam me-2"></i>Manajemen Data Alat</h5>
                        <button class="btn btn-sm btn-gold px-3" data-bs-toggle="modal" data-bs-target="#modalTambahAlat"><i class="bi bi-plus-lg me-1"></i> Tambah Alat</button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-emerald text-white">
                                    <tr><th class="ps-4">No</th><th>Foto</th><th>Nama Alat</th><th>Kategori</th><th>Ukuran</th><th>Harga/Hari</th><th>Stok</th></tr>
                                </thead>
                                <tbody>
                                    <?php $no=1; while($row = mysqli_fetch_assoc($result_alat)): ?>
                                    <?php $f = (!empty($row['foto']) && file_exists('../assets/img/'.$row['foto'])) ? '../assets/img/'.$row['foto'] : '../assets/img/no-image.png'; ?>
                                    <tr>
                                        <td class="ps-4"><?= $no++; ?></td>
                                        <td><img src="<?= $f; ?>" class="img-thumb border"></td>
                                        <td class="fw-bold"><?= $row['nama_alat']; ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= $row['nama_kategori']; ?></span></td>
                                        <td><?= $row['ukuran']; ?></td>
                                        <td>Rp <?= number_format($row['harga_sewa'],0,',','.'); ?></td>
                                        <td class="fw-bold"><?= $row['stok']; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($active_tab === 'kategori'): ?>
                <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
                        <h5 class="fw-bold mb-0 text-success"><i class="bi bi-tags me-2"></i>Manajemen Kategori</h5>
                        <button class="btn btn-sm btn-gold px-3" data-bs-toggle="modal" data-bs-target="#modalTambahKategori"><i class="bi bi-plus-lg me-1"></i> Tambah Kategori</button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-emerald text-white"><tr><th class="ps-4">No</th><th>Nama Kategori</th><th class="text-center">Aksi</th></tr></thead>
                            <tbody>
                                <?php $no=1; while($kat = mysqli_fetch_assoc($result_kategori)): ?>
                                <tr>
                                    <td class="ps-4"><?= $no++; ?></td>
                                    <td class="fw-bold"><?= $kat['nama_kategori']; ?></td>
                                    <td class="text-center"><a href="admin_dashboard.php?tab=kategori&hapus_kategori=<?= $kat['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus kategori ini?')">Hapus</a></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($active_tab === 'users'): ?>
                <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
                        <h5 class="fw-bold mb-0 text-success"><i class="bi bi-people me-2"></i>Manajemen Pengguna (User)</h5>
                        <button class="btn btn-sm btn-gold px-3" data-bs-toggle="modal" data-bs-target="#modalTambahUser"><i class="bi bi-plus-lg me-1"></i> Tambah User</button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-emerald text-white"><tr><th class="ps-4">No</th><th>Nama Lengkap</th><th>Username</th><th>Role</th><th>No HP</th><th class="text-center">Aksi</th></tr></thead>
                                <tbody>
                                    <?php $no=1; while($u = mysqli_fetch_assoc($result_users)): ?>
                                    <tr>
                                        <td class="ps-4"><?= $no++; ?></td>
                                        <td class="fw-bold"><?= $u['nama']; ?></td>
                                        <td><?= $u['username']; ?></td>
                                        <td><span class="badge bg-secondary"><?= ucfirst($u['role']); ?></span></td>
                                        <td><?= $u['no_hp']; ?></td>
                                        <td class="text-center">
                                            <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                                <a href="admin_dashboard.php?tab=users&hapus_user=<?= $u['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus user ini?')">Hapus</a>
                                            <?php else: ?><span class="small text-muted">Aktif</span><?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($active_tab === 'peminjaman'): ?>
                <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom"><h5 class="fw-bold mb-0 text-success"><i class="bi bi-journal-check me-2"></i>Data Peminjaman Alat</h5></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-emerald text-white"><tr><th class="ps-4">No. Nota</th><th>Peminjam</th><th>Alat</th><th>Status</th><th class="text-center">Struk</th></tr></thead>
                                <tbody>
                                    <?php while($p = mysqli_fetch_assoc($result_peminjaman)): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold">#INV-<?= sprintf("%04d", $p['id']); ?></td>
                                        <td><?= $p['nama_peminjam']; ?></td>
                                        <td><?= $p['nama_alat']; ?></td>
                                        <td><span class="badge bg-warning text-dark"><?= ucfirst($p['status']); ?></span></td>
                                        <td class="text-center"><a href="../cetak_struk.php?id=<?= $p['id']; ?>" target="_blank" class="btn btn-sm btn-outline-success">Struk</a></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($active_tab === 'log'): ?>
                <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom"><h5 class="fw-bold mb-0 text-success"><i class="bi bi-clock-history me-2"></i>Log Aktivitas Sistem</h5></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="bg-emerald text-white"><tr><th class="ps-4">Waktu</th><th>Pengguna</th><th>Aktivitas</th></tr></thead>
                                <tbody>
                                    <?php while($l = mysqli_fetch_assoc($result_log)): ?>
                                    <tr><td class="ps-4 text-muted small"><?= date('d M Y H:i', strtotime($l['created_at'])); ?></td><td class="fw-bold"><?= $l['nama']; ?></td><td><?= $l['aktivitas']; ?></td></tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahAlat" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4">
      <div class="modal-header bg-emerald text-white"><h5 class="modal-title fw-bold text-warning">Tambah Alat Baru</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <form action="admin_dashboard.php?tab=stok" method="POST" enctype="multipart/form-data">
        <div class="modal-body p-3">
            <div class="mb-3"><label class="form-label small fw-semibold">Nama Alat</label><input type="text" name="nama_alat" class="form-control" required></div>
            <div class="mb-3"><label class="form-label small fw-semibold">Kategori</label><select name="kategori_id" class="form-select" required><?php $kat_m = mysqli_query($koneksi, "SELECT * FROM kategori"); while($km = mysqli_fetch_assoc($kat_m)){ echo "<option value='{$km['id']}'>{$km['nama_kategori']}</option>"; } ?></select></div>
            <div class="mb-3"><label class="form-label small fw-semibold">Ukuran</label><input type="text" name="ukuran" class="form-control" required></div>
            <div class="mb-3"><label class="form-label small fw-semibold">Harga Sewa / Hari</label><input type="number" name="harga_sewa" class="form-control" required></div>
            <div class="mb-3"><label class="form-label small fw-semibold">Stok</label><input type="number" name="stok" class="form-control" value="5" required></div>
            <div class="mb-3"><label class="form-label small fw-semibold">Foto Alat</label><input type="file" name="foto" class="form-control" accept="image/*" required></div>
        </div>
        <div class="modal-footer bg-light"><button type="submit" name="tambah_alat" class="btn btn-sm btn-gold w-100">Simpan Alat</button></div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalTambahKategori" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 rounded-4">
      <div class="modal-header bg-emerald text-white py-2"><h6 class="modal-title fw-bold">Tambah Kategori</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <form action="admin_dashboard.php?tab=kategori" method="POST">
        <div class="modal-body p-3"><div class="mb-3"><label class="form-label small fw-semibold">Nama Kategori</label><input type="text" name="nama_kategori" class="form-control" required></div></div>
        <div class="modal-footer bg-light p-2"><button type="submit" name="tambah_kategori" class="btn btn-sm btn-gold w-100">Simpan</button></div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalTambahUser" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4">
      <div class="modal-header bg-emerald text-white"><h5 class="modal-title fw-bold text-warning">Tambah User Baru</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <form action="admin_dashboard.php?tab=users" method="POST">
        <div class="modal-body p-3">
            <div class="mb-3"><label class="form-label small fw-semibold">Nama Lengkap</label><input type="text" name="nama" class="form-control" required></div>
            <div class="mb-3"><label class="form-label small fw-semibold">Username</label><input type="text" name="username" class="form-control" required></div>
            <div class="mb-3"><label class="form-label small fw-semibold">Password</label><input type="password" name="password" class="form-control" required></div>
            <div class="mb-3"><label class="form-label small fw-semibold">Role</label><select name="role" class="form-select" required><option value="admin">Admin</option><option value="petugas">Petugas</option><option value="peminjam">Peminjam</option></select></div>
            <div class="mb-3"><label class="form-label small fw-semibold">No HP</label><input type="text" name="no_hp" class="form-control"></div>
        </div>
        <div class="modal-footer bg-light"><button type="submit" name="tambah_user" class="btn btn-sm btn-gold w-100">Simpan User</button></div>
      </form>
    </div>
  </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>