<?php
// test_productos.php
header("Content-Type: application/json");

// Función simulada de tu controlador CRUD de UniDeportes
function validarProducto($nombre, $precio, $stock) {
    if (empty($nombre) || $precio <= 0 || $stock < 0) {
        return false; // Datos inválidos
    }
    return true; // Datos listos para el INSERT en MySQL
}

// --- EJECUCIÓN DE ASIONES (PRUEBAS UNITARIAS) ---
$errores = 0;

// Prueba 1: Intentar registrar un uniforme con precio negativo
if (validarProducto("Camiseta Real Madrid", -25000, 10) === false) {
    echo "✔ Prueba 1 Exitosa: El sistema rechazó correctamente un precio negativo.\n";
} else {
    echo "❌ Prueba 1 Fallida: El sistema aceptó un precio inválido.\n";
    $errores++;
}

// Prueba 2: Registrar un producto válido
if (validarProducto("Pantaloneta Entrenamiento", 35000, 15) === true) {
    echo "✔ Prueba 2 Exitosa: El sistema validó correctamente un producto correcto.\n";
} else {
    echo "❌ Prueba 2 Fallida: El sistema rechazó datos válidos.\n";
    $errores++;
}

if ($errores == 0) {
    echo "\n[RESULTADO]: Todas las pruebas unitarias del backend pasaron con éxito.";
}
?>