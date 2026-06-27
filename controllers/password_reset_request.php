<?php
// controllers/password_reset_request.php
require_once __DIR__ . '/../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/recuperar_password.php');
    exit();
}

$pdo = app();
$email = trim($_POST['email'] ?? '');

// Validar email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../views/recuperar_password.php?error=email_invalido');
    exit();
}

// Buscar usuario con ese email
$stmt = $pdo->prepare("SELECT id, username, email FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header('Location: ../views/recuperar_password.php?error=no_encontrado');
    exit();
}

// Generar token único (64 caracteres para coincidir con varchar(64))
$token = bin2hex(random_bytes(32));

// Fecha de expiración (1 hora desde ahora)
$expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

try {
    // Eliminar tokens anteriores del mismo email (por si acaso)
    $stmtDelete = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmtDelete->execute([$email]);

    // Insertar nuevo token en la base de datos
    $stmtInsert = $pdo->prepare("
        INSERT INTO password_resets (email, token, expires_at) 
        VALUES (?, ?, ?)
    ");
    $stmtInsert->execute([$email, $token, $expiresAt]);

    // Guardar el último token en sesión para mostrarlo en localhost
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['last_password_reset_token'] = $token;

    // Redirigir mostrando el token
    header('Location: ../views/recuperar_password.php?success=1');
    exit();

} catch (Exception $e) {
    header('Location: ../views/recuperar_password.php?error=error_db');
    exit();
}