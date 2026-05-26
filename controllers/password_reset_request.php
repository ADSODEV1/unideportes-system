<?php
require_once __DIR__ . '/../config/bootstrap.php';
$pdo = app();

$email = trim($_POST['email'] ?? '');
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/unideportes-system/views/recuperar_password.php?error=email_invalido');
}

$stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();
if (!$user) {
    redirect('/unideportes-system/views/recuperar_password.php?error=no_encontrado');
}

$token = bin2hex(random_bytes(16));
if (!isset($_SESSION['password_reset_tokens'])) {
    $_SESSION['password_reset_tokens'] = [];
}

$_SESSION['password_reset_tokens'][$token] = [
    'email' => $email,
    'expires' => time() + 900,
];
$_SESSION['last_password_reset_token'] = $token;

redirect('/unideportes-system/views/recuperar_password.php?success=1');
