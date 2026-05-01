<?php
// filepath: public/verificar_passwords.php
require_once __DIR__ . '/../config/connection.php';

$query = $conn->query("SELECT id, username, password, role FROM usuarios");
echo "<h2>Verificación de Contraseñas</h2>";
echo "<table border='1'><tr><th>ID</th><th>Usuario</th><th>Hash</th><th>Rol</th><th>¿Hasheada?</th></tr>";

while ($row = $query->fetch_assoc()) {
    $isHashed = password_get_info($row['password'])['algo'] !== 0;
    $hashPreview = substr($row['password'], 0, 20) . '...';
    echo "<tr><td>{$row['id']}</td><td>{$row['username']}</td><td>{$hashPreview}</td><td>{$row['role']}</td><td>" . ($isHashed ? '✅' : '❌') . "</td></tr>";
}
echo "</table>";

echo "<br><h3>Prueba de login:</h3>";
echo "<form method='POST'>";
echo "Usuario: <input name='user' value='Pablo'><br>";
echo "Contraseña: <input name='pass' value='1234'><br>";
echo "<input type='submit' value='Probar'>";
echo "</form>";

if ($_POST) {
    $user = $_POST['user'];
    $pass = $_POST['pass'];

    $stmt = $conn->prepare("SELECT password FROM usuarios WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $valid = password_verify($pass, $row['password']);
        echo "<br>Resultado: " . ($valid ? '✅ CORRECTO' : '❌ INCORRECTO');
    } else {
        echo "<br>Usuario no encontrado";
    }
}
?>