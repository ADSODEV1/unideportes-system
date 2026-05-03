<?php
session_start();
require_once __DIR__ . '/../config/connection.php';

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
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: ../views/admin_user.php?msj=eliminado");
    exit();
}

echo "No se pudo eliminar: " . $stmt->error;

$stmt->close();
$conn->close();
?>