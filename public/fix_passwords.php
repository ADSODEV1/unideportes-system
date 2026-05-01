<?php
// filepath: public/fix_passwords.php
require_once __DIR__ . '/../config/connection.php';

// Contraseñas originales
$passwords = [
    1 => 'admin123',    // admin
    2 => '123',         // joel_dev
    3 => '1234',        // Pablo
    5 => 'jon@123',     // JonathanS
];

foreach ($passwords as $id => $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $id);
    $stmt->execute();
    echo "Usuario ID $id actualizado: $password → hash generado<br>";
}

echo "<br>✅ Todas las contraseñas actualizadas!<br>";
echo "<br>Ahora puedes probar:<br>";
echo "- Pablo / 1234<br>";
echo "- joel_dev / 123<br>";
echo "- JonathanS / jon@123<br>";
echo "- admin / admin123<br>";
?>