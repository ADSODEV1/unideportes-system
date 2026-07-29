<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = app();

// Verificar permisos de administrador
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /unideportes-system/public/index.php?error=acceso_denegado');
    exit();
}

// Validar ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: ../views/admin_usuarios.php?msj=id_no_encontrado');
    exit();
}

// Prevenir auto-eliminación
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
    header('Location: ../views/admin_usuarios.php?msj=no_puede_eliminarse');
    exit();
}

try {
    // Verificar que el usuario existe
    $checkStmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $checkStmt->execute([$id]);
    
    if (!$checkStmt->fetch()) {
        header('Location: ../views/admin_usuarios.php?msj=usuario_no_existe');
        exit();
    }
    
    // Eliminar usuario
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$id])) {
        header('Location: ../views/admin_usuarios.php?msj=eliminado');
        exit();
    }
    
    header('Location: ../views/admin_usuarios.php?msj=datos_invalidos');
    exit();
    
} catch (Throwable $e) {
    header('Location: ../views/admin_usuarios.php?msj=datos_invalidos');
    exit();
}