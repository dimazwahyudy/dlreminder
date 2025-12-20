<?php
// setup_admin.php
require 'config.php';

// Konfigurasi Akun Admin
$nama = "Super Admin";
$email = "admin@dlreminder.com";
$password = "admin123"; // Password yang akan digunakan

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Cek apakah email sudah ada
$check = $conn->query("SELECT id FROM user WHERE email = '$email'");
if ($check->num_rows > 0) {
    echo "<h3>Akun admin sudah ada!</h3>";
    echo "<p>Silakan login dengan: <b>$email</b></p>";
} else {
    // Masukkan ke tabel user
    // Karena logika di auth.php: "Jika user tidak ada di tabel mahasiswa/dosen, maka dia Admin",
    // kita cukup insert ke tabel user saja.
    $sql = "INSERT INTO user (name, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nama, $email, $password_hash);
    
    if ($stmt->execute()) {
        echo "<h3>Berhasil membuat Admin!</h3>";
        echo "<p>Email: <b>$email</b></p>";
        echo "<p>Password: <b>$password</b></p>";
        echo "<br><a href='login.php'>Ke Halaman Login</a>";
    } else {
        echo "Gagal: " . $conn->error;
    }
}
?>