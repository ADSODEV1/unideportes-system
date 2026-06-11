<?php
// controllers/password_update_process.php
require_once __DIR__ . '/../config/bootstrap.php';

$pdo = app();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$token = trim(request('token') ?? '');
$password = trim(request('password') ?? '');
$password_confirm = trim(request('password_confirm') ?? '');

if (empty($token)) {
    redirect('/unideportes-system/public/reset_password_view.php?error=token_invalido');
}

// 1. Buscar token en la base de datos
$stmt = $pdo->prepare('SELECT email, expires_at FROM password_resets WHERE token = ? LIMIT 1');
$stmt->execute([$token]);
$resetData = $stmt->fetch();

if (!$resetData) {
    redirect('/unideportes-system/public/reset_password_view.php?error=token_invalido');
}

// 2. Validar si el token ya expiró por tiempo
if ($resetData['expires_at'] < date('Y-m-d H:i:s')) {
    $deleteStmt = $pdo->prepare('DELETE FROM password_resets WHERE token = ?');
    $deleteStmt->execute([$token]);
    redirect('/unideportes-system/public/reset_password_view.php?error=token_expirado');
}

// 3. Validaciones de seguridad de la contraseña
if (strlen($password) < 6) {
    redirect("/unideportes-system/public/reset_password_view.php?token=$token&error=contrasena_corta");
}

if ($password !== $password_confirm) {
    redirect("/unideportes-system/public/reset_password_view.php?token=$token&error=contrasena_no_coincide");
}

try {
    // 4. Encriptar la contraseña del usuario
    $passwordEncriptada = password_hash($password, PASSWORD_BCRYPT);
    $userEmail = trim($resetData['email']);

    // 5. ACTUALIZAR CONTRASEÑA EN LA TABLA USUARIOS
    $updateUser = $pdo->prepare('UPDATE usuarios SET password = ? WHERE TRIM(email) = ?');
    $updateUser->execute([$passwordEncriptada, $userEmail]);

    // 6. BORRAR EL TOKEN RECIÉN USADO (Por seguridad, un token solo sirve una vez)
    $deleteToken = $pdo->prepare('DELETE FROM password_resets WHERE token = ?');
    $deleteToken->execute([$token]);

    // Redireccionar mostrando el mensaje de éxito completo
    redirect('/unideportes-system/public/reset_password_view.php?success=1');

} catch (PDOException $e) {
    die("Error crítico en la base de datos al guardar la clave: " . $e->getMessage());
}