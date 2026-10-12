<?php
session_start();
include "koneksi.php";

if (isset($_SESSION['id_user'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);

    $query = mysqli_query($conn, "
        SELECT * FROM user
        WHERE username = '$username'
        AND password = '$password'
    ");

    if (mysqli_num_rows($query) == 1) {
        $data = mysqli_fetch_assoc($query);
        $_SESSION['id_user'] = $data['id_user'];
        $_SESSION['nama'] = $data['nama'];
        $_SESSION['username'] = $data['username'];
        $_SESSION['role'] = $data['role'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bank Sampah</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
<div class="login-box">
    <div class="logo">♻️</div>
    <h1>Bank Sampah</h1>
    <p>Silakan masuk ke akun Anda</p>

    <?php if ($error != ""): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Username</label>
        <input type="text" name="username" placeholder="Masukkan username" required>

        <label>Password</label>
        <input type="password" name="password" placeholder="Masukkan password" required>

        <button type="submit" name="login">Masuk</button>
    </form>
</div>
</body>
</html>
