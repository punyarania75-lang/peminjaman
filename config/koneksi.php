<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$koneksi = mysqli_connect("localhost", "root", "", "db_winter_outfit");

if (!mysqli_connect_errno()) {
}
?>