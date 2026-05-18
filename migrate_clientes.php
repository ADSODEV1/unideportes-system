<?php
require_once __DIR__ . '/config/connection.php';

$conn = connection();

// Verificar si la columna ya existe
$result = $conn->query("SHOW COLUMNS FROM clientes LIKE 'estado'");

if ($result && $result->num_rows === 0) {
    // Agregar la columna si no existe
    if ($conn->query("ALTER TABLE clientes ADD COLUMN estado ENUM('activo', 'inactivo') DEFAULT 'activo'")) {
        echo "✓ Columna 'estado' agregada exitosamente a la tabla clientes";
    } else {
        echo "✗ Error al agregar la columna: " . $conn->error;
    }
} else {
    echo "✓ La columna 'estado' ya existe en la tabla clientes";
}

$conn->close();
?>
