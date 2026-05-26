<?php
require_once __DIR__ . '/../config/bootstrap.php';
$pdo = app();

$token = request('token');
$password = request('password');
$passwordConfirm = request('password_confirm');

if (!$token || !isset($_SESSION['password_reset_tokens'][$token])) {
    redirect('/unideportes-system/views/reset_password.php?error=token_invalido');
}

$resetData = $_SESSION['password_reset_tokens'][$token];
if ($resetData['expires'] < time()) {
    unset($_SESSION['password_reset_tokens'][$token]);
    redirect('/unideportes-system/views/reset_password.php?error=token_expirado');
}

if ($password === '' || $passwordConfirm === '' || $password !== $passwordConfirm) {
    redirect('/unideportes-system/views/reset_password.php?token=' . urlencode($token) . '&error=contrasena_no_coincide');
}

if (strlen($password) < 6) {
    redirect('/unideportes-system/views/reset_password.php?token=' . urlencode($token) . '&error=contrasena_corta');
}

$hashed = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('UPDATE usuarios SET password = ? WHERE email = ?');
$stmt->execute([$hashed, $resetData['email']]);

unset($_SESSION['password_reset_tokens'][$token], $_SESSION['last_password_reset_token']);
redirect('/unideportes-system/public/index.php?success=reset_completado');
