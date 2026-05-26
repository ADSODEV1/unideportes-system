<?php
// filepath: public/reset_admin.php
require_once __DIR__ . '/../config/connection.php';

// Verificamos si la variable de conexión tiene el nombre correcto ($conn o $pdo)
// Si usas $pdo en bootstrap, adaptamos el flujo automáticamente
$db = isset($conn) ? $conn : (isset($pdo) ? $pdo : null);

if (!$db) {
    die("❌ Error de infraestructura: No se encontró una instancia de conexión válida (PDO).");
}

try {
    $password = 'admin123';
    // Generación del hash seguro compatible con PASSWORD_DEFAULT (BCRYPT)
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Sentencia preparada estándar de PDO
    $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE username = 'admin'");
    
    // En PDO, pasamos las variables directamente en el execute como un array plano
    if ($stmt->execute([$hash])) {
        echo "<div style='font-family: Arial, sans-serif; padding: 20px; border-radius: 8px; background: #d1fae5; color: #065f46; max-width: 500px; margin: 30px auto; border: 1px solid #10b981;'>";
        echo "<h3>✅ Base de datos sincronizada con éxito</h3>";
        echo "<p>El usuario <strong>admin</strong> ha sido actualizado con los nuevos parámetros de seguridad.</p>";
        echo "<ul>";
        echo "<li><strong>Usuario:</strong> admin</li>";
        echo "<li><strong>Contraseña temporal:</strong> admin123</li>";
        echo "</ul>";
        echo "<small style='color: #666;'>Hash generado: " . htmlspecialchars(substr($hash, 0, 30)) . "...</small><br><br>";
        echo "<a href='/unideportes-system/public/index.php' style='display:inline-block; padding: 8px 15px; background: #065f46; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;'>Ir al Login</a>";
        echo "</div>";
    } else {
        echo "❌ Error al intentar ejecutar la actualización en la tabla usuarios.";
    }

} catch (PDOException $e) {
    echo "<div style='font-family: Arial, sans-serif; padding: 20px; border-radius: 8px; background: #fee2e2; color: #b91c1c; max-width: 500px; margin: 30px auto; border: 1px solid #f87171;'>";
    echo "<h3>❌ Error de Base de Datos</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}