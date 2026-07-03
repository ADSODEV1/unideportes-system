<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login(['admin']);
$pdo = app();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/admin_usuarios.php');
    exit();
}

function limpiarTextoBase(string $value): string {
    $value = trim($value);
    $value = strip_tags($value);
    $value = str_replace(['"', "'", '“', '”', '‘', '’'], '', $value);
    $value = preg_replace('/\s+/u', ' ', $value) ?? '';
    return trim($value);
}

function redirigirError(string $mensaje): void {
    header('Location: ../views/admin_usuarios.php?msj=' . urlencode($mensaje));
    exit();
}

try {
    $name = limpiarTextoBase((string) ($_POST['name'] ?? ''));
    $lastname = limpiarTextoBase((string) ($_POST['lastname'] ?? ''));
    $username = limpiarTextoBase((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $email = limpiarTextoBase((string) ($_POST['email'] ?? ''));
    $role = limpiarTextoBase((string) ($_POST['role'] ?? 'vendedor'));

    if ($name === '' || $lastname === '' || $username === '' || $password === '' || $email === '') {
        redirigirError('datos_invalidos');
    }

    if (mb_strlen($name) < 2 || mb_strlen($name) > 60 || mb_strlen($lastname) < 2 || mb_strlen($lastname) > 60 || mb_strlen($username) < 4 || mb_strlen($username) > 30) {
        redirigirError('datos_invalidos');
    }

    if (!preg_match('/^[\pL\pM\s\-.]+$/u', $name) || !preg_match('/^[\pL\pM\s\-.]+$/u', $lastname)) {
        redirigirError('caracteres_no_permitidos');
    }

    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        redirigirError('caracteres_no_permitidos');
    }

    if (strlen($password) < 8 || strlen($password) > 72) {
        redirigirError('password_invalida');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirigirError('email_invalido');
    }

    $rolesValidos = ['vendedor', 'admin'];
    if (!in_array($role, $rolesValidos, true)) {
        redirigirError('rol_invalido');
    }

    $name = mb_substr($name, 0, 60);
    $lastname = mb_substr($lastname, 0, 60);
    $username = mb_strtolower($username);
    $email = mb_strtolower($email);

    $check = $pdo->prepare('SELECT id FROM usuarios WHERE username = ? OR email = ? LIMIT 1');
    $check->execute([$username, $email]);
    if ($check->fetchColumn()) {
        redirigirError('usuario_o_correo_duplicado');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        redirigirError('datos_invalidos');
    }

    $sql = 'INSERT INTO usuarios (name, lastname, username, password, email, role) VALUES (?, ?, ?, ?, ?, ?)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $lastname, $username, $passwordHash, $email, $role]);

    header('Location: ../views/admin_usuarios.php?msj=ok');
    exit();
} catch (Throwable $e) {
    redirigirError('datos_invalidos');
}
