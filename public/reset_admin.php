<?php
// filepath: public/reset_admin.php
require_once __DIR__ . '/../config/connection.php';

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE username = 'admin'");
$stmt->bind_param("s", $hash);
$stmt->execute();

echo "✅ Admin actualizado<br>";
echo "Hash: " . substr($hash, 0, 30) . "...<br>";
echo "<br>Prueba ahora:<br>";
echo "Usuario: admin<br>";
echo "Contraseña: admin123";
?>