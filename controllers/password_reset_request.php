<?php
// controllers/password_reset_request.php
require_once __DIR__ . '/../config/bootstrap.php';

$pdo = app();

// Forzar alertas estrictas en caso de fallos internos
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$email = trim($_POST['email'] ?? '');
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/unideportes-system/views/recuperar_password.php?error=email_invalido');
}

// 1. Validar si el usuario existe en la tabla usuarios
$stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    redirect('/unideportes-system/views/recuperar_password.php?error=no_encontrado');
}

// 2. Generar Token único y expiración (15 minutos)
$token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', time() + 900); 

try {
    // Iniciamos transacción para asegurar la persistencia que acabamos de comprobar
    $pdo->beginTransaction();

    // 3. Limpiar solicitudes antiguas de este mismo correo
    $deleteStmt = $pdo->prepare('DELETE FROM password_resets WHERE email = ?');
    $deleteStmt->execute([$email]);

    // 4. Insertar el nuevo token de forma física
    $insertStmt = $pdo->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)');
    $insertStmt->execute([$email, $token, $expires_at]);

    // Consolidamos la escritura en el disco duro de MySQL
    $pdo->commit();

    // 5. Redirección automática inmediata al formulario de nueva clave
    redirect('/unideportes-system/public/reset_password_view.php?token=' . $token);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Error crítico en la base de datos al solicitar el reset: " . $e->getMessage());
}