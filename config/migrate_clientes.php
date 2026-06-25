<?php
require_once __DIR__ . '/bootstrap.php';

$conn = app(); 

try {
    // Si la tabla clientes no existe, la creamos completa con los campos que usa la app.
    $tableCheck = $conn->query("SHOW TABLES LIKE 'clientes'");
    if ($tableCheck->rowCount() === 0) {
        $conn->exec(<<<SQL
CREATE TABLE clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_descriptivo VARCHAR(50) DEFAULT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    nit_cedula VARCHAR(30) NOT NULL,
    telefono VARCHAR(30) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    tipo_cliente ENUM('Individual', 'Equipo', 'Colegio', 'Empresa') DEFAULT 'Individual',
    direccion VARCHAR(255) DEFAULT NULL,
    barrio VARCHAR(100) DEFAULT NULL,
    ciudad VARCHAR(100) DEFAULT 'Sogamoso',
    referencia_entrega TEXT DEFAULT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_clientes_nit_cedula (nit_cedula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
        );
        echo "✓ Tabla 'clientes' creada exitosamente.\n";
    }

    $requiredColumns = [
        "codigo_descriptivo VARCHAR(50) DEFAULT NULL",
        "nombre_completo VARCHAR(150) NOT NULL",
        "nit_cedula VARCHAR(30) NOT NULL",
        "telefono VARCHAR(30) DEFAULT NULL",
        "email VARCHAR(100) DEFAULT NULL",
        "tipo_cliente ENUM('Individual', 'Equipo', 'Colegio', 'Empresa') DEFAULT 'Individual'",
        "direccion VARCHAR(255) DEFAULT NULL",
        "barrio VARCHAR(100) DEFAULT NULL",
        "ciudad VARCHAR(100) DEFAULT 'Sogamoso'",
        "referencia_entrega TEXT DEFAULT NULL",
        "estado ENUM('activo', 'inactivo') DEFAULT 'activo'",
    ];

    foreach ($requiredColumns as $columnDef) {
        $columnName = strtok($columnDef, ' ');
        $stmt = $conn->prepare("SHOW COLUMNS FROM clientes LIKE '$columnName'");
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            $conn->exec("ALTER TABLE clientes ADD COLUMN $columnDef");
            echo "✓ Columna '$columnName' agregada exitosamente a la tabla clientes.\n";
        }
    }

    // Índice único para evitar duplicar cédulas/NIT en clientes existentes.
    try {
        $conn->exec("ALTER TABLE clientes ADD UNIQUE KEY uq_clientes_nit_cedula (nit_cedula)");
    } catch (PDOException $ignored) {
        // El índice ya existe o la tabla todavía no está en estado para crearlo.
    }

    echo "✓ Migración de clientes completada.\n";
} catch (PDOException $e) {
    echo "✗ Error al migrar clientes: " . $e->getMessage() . "\n";
}
?>