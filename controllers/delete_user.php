<?php
require_once __DIR__ . '/../config/bootstrap.php';
$pdo = app();

if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /unideportes-system/public/index.php?error=acceso_denegado');
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: ../views/admin_user.php?error=id_invalido');
    exit();
}

$sql = "DELETE FROM usuarios WHERE id = ?";
$stmt = $pdo->prepare($sql);

if ($stmt->execute([$id])) {
    header("Location: ../views/admin_user.php?msj=eliminado");
    exit();
}

echo "No se pudo eliminar.";
?>