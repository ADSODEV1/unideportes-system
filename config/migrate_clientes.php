<?php
require_once __DIR__ . '/bootstrap.php';

$conn = app(); 

try {
    // Revisamos si ya existe la columna 'estado'
    $stmt = $conn->prepare("SHOW COLUMNS FROM clientes LIKE 'estado'");
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        // Si no existe, la creamos de forma segura
        $conn->exec("ALTER TABLE clientes ADD COLUMN estado ENUM('activo', 'inactivo') DEFAULT 'activo'");
        echo "✓ Columna 'estado' agregada exitosamente a la tabla clientes.\n";
    } else {
        echo "✓ La columna 'estado' ya existe en la tabla clientes.\n";
    }
} catch (PDOException $e) {
    echo "✗ Error al migrar clientes: " . $e->getMessage() . "\n";
}
?>