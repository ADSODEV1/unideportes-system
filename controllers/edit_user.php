<?php
require_once __DIR__ . '/../config/bootstrap.php';
$pdo = app();

// 1. Proteger el controlador con el rol requerido
require_login(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/admin_user.php");
    exit();
}

// 2. Captura y sanitización de variables
$id       = intval($_POST['id'] ?? 0);
$name     = trim($_POST['name'] ?? '');
$lastname = trim($_POST['lastname'] ?? '');
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// 3. Validación de ID y campos obligatorios
if ($id <= 0 || empty($name) || empty($lastname) || empty($username)) {
    header("Location: ../views/update.php?id={$id}&error=datos_invalidos");
    exit();
}

try {
    // 4. Validar que el nuevo username no esté ocupado por OTRO usuario
    $checkStmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
    $checkStmt->execute([$username, $id]);

    if ($checkStmt->fetch()) {
        // Redirecciona a la vista pasándole el error exacto para que no rompa el flujo
        header("Location: ../views/update.php?id={$id}&error=usuario_existente");
        exit();
    }

    // 5. Criterio de contraseña inteligente (Usa PASSWORD_BCRYPT para mantener consistencia)
    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $sql = "UPDATE usuarios SET name = ?, lastname = ?, username = ?, email = ?, password = ? WHERE id = ?";
        $params = [$name, $lastname, $username, $email, $hashed, $id];
    } else {
        $sql = "UPDATE usuarios SET name = ?, lastname = ?, username = ?, email = ? WHERE id = ?";
        $params = [$name, $lastname, $username, $email, $id];
    }

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        header("Location: ../views/admin_user.php?success=usuario_actualizado");
        exit();
    }

    header("Location: ../views/update.php?id={$id}&error=fallo_registro");
    exit();

} catch (Exception $e) {
    header("Location: ../views/update.php?id={$id}&error=fallo_bd");
    exit();
}