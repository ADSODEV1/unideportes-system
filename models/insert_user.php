<?php
require_once __DIR__ . '/../config/bootstrap.php';
$pdo = app();

if (empty($_POST['username']) || empty($_POST['password'])) {
    header("Location: ../views/admin_user.php?msj=vacio");
    exit();
}

$name = trim($_POST['name'] ?? '');
$lastname = trim($_POST['lastname'] ?? '');
$username = trim($_POST['username']);
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? 'colaborador';

if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Email inválido";
    exit();
}

$check = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
$check->execute([$username]);
if ($check->fetch()) {
    header("Location: ../views/admin_user.php?msj=existe");
    exit();
}

$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$sql = "INSERT INTO usuarios (name, lastname, username, password, email, role) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);

if ($stmt->execute([$name, $lastname, $username, $password, $email, $role])) {
    header("Location: ../views/admin_user.php?msj=ok");
    exit();
}

echo "Error en la BD.";
?>