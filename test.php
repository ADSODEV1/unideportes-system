<?php
/**
 * TEST CRUD - UNIDEPORTES
 * Pruebas completas del sistema CRUD de usuarios
 */

// 1. DATOS DE CONFIGURACIÓN
$db_host = 'localhost';
$db_name = 'unideportes';
$db_user = 'root';
$db_pass = '';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test CRUD - Unideportes</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 40px auto; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #E61E2A; margin: 0 0 20px 0; }
        .section { margin: 20px 0; border-bottom: 1px solid #ddd; padding-bottom: 20px; }
        .check { display: flex; align-items: center; margin: 10px 0; }
        .icon { font-size: 20px; margin-right: 10px; }
        .ok { color: green; }
        .error { color: #c0392b; }
        .warning { color: #f39c12; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .success-box { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; }
        .error-box { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; }
        .info-box { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; color: #0c5460; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .crud-test { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧪 Test CRUD - Unideportes</h1>
        <p><em>Ejecutado el: " . date('Y-m-d H:i:s') . "</em></p>";

$checks = ['Configuración' => [], 'Base de Datos' => [], 'CRUD Usuarios' => []];

//SECCIÓN 1: CONFIGURACIÓN
$checks['Configuración'][] = ['PHP Version >= 7.4', phpversion() >= '7.4', 'Actual: ' . phpversion()];
$checks['Configuración'][] = ['Extensión MySQLi', extension_loaded('mysqli'), 'Necesaria para BD'];
$checks['Configuración'][] = ['Función password_hash', function_exists('password_hash'), 'Para encriptar contraseñas'];
$checks['Configuración'][] = ['Función password_verify', function_exists('password_verify'), 'Para verificar contraseñas'];

//SECCIÓN 2: ARCHIVOS CRUD
$baseDir = __DIR__;
$crudFiles = [
    'config/connection.php',
    'controllers/auth.php',
    'controllers/login.php',
    'controllers/edit_user.php',
    'controllers/delete_user.php',
    'models/insert_user.php',
    'models/update.php',
    'views/admin_user.php',
    'public/index.php'
];

foreach ($crudFiles as $file) {
    $path = $baseDir . DIRECTORY_SEPARATOR . $file;
    $exists = file_exists($path);
    $checks['Configuración'][] = ["Archivo: $file", $exists, $exists ? '✓ Encontrado' : '❌ NO ENCONTRADO'];
}

//SECCIÓN 3: BASE DE DATOS Y CRUD
$dbConnected = false;
$crudTests = [];

try {
    // Conectar a la base de datos
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }

    $dbConnected = true;
    $checks['Base de Datos'][] = ["Conexión a '$db_name'", true, "Conectado como $db_user"];

    // Verificar tabla usuarios
    $res = $conn->query("SHOW TABLES LIKE 'usuarios'");
    $tableExists = ($res && $res->num_rows > 0);
    $checks['Base de Datos'][] = ["Tabla: usuarios", $tableExists, $tableExists ? '✓ OK' : '❌ Falta crear tabla'];

    if ($tableExists) {
        // Verificar estructura de la tabla
        $res = $conn->query("DESCRIBE usuarios");
        $columns = [];
        while ($row = $res->fetch_assoc()) {
            $columns[] = $row['Field'];
        }

        $requiredColumns = ['id', 'name', 'lastname', 'username', 'password', 'email', 'role'];
        $missingColumns = array_diff($requiredColumns, $columns);
        $checks['Base de Datos'][] = ["Columnas requeridas", empty($missingColumns), empty($missingColumns) ? '✓ Todas presentes' : '❌ Faltan: ' . implode(', ', $missingColumns)];

        // PRUEBAS CRUD
        $crudTests[] = "<div class='crud-test'><h3>📊 Usuarios en BD:</h3>";
        $res = $conn->query("SELECT id, username, role FROM usuarios ORDER BY id");
        if ($res && $res->num_rows > 0) {
            $crudTests[] = "<table><tr><th>ID</th><th>Usuario</th><th>Rol</th></tr>";
            while ($row = $res->fetch_assoc()) {
                $crudTests[] = "<tr><td>{$row['id']}</td><td>{$row['username']}</td><td>{$row['role']}</td></tr>";
            }
            $crudTests[] = "</table>";
        } else {
            $crudTests[] = "<p>❌ No hay usuarios en la base de datos</p>";
        }
        $crudTests[] = "</div>";

        // Test CREATE (Insertar usuario de prueba)
        $testUser = 'test_user_' . time();
        $testPass = password_hash('test123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO usuarios (username, password, role, name, lastname, email) VALUES (?, ?, 'colaborador', 'Test', 'User', 'test@example.com')");
        $stmt->bind_param("ss", $testUser, $testPass);
        $createSuccess = $stmt->execute();
        $checks['CRUD Usuarios'][] = ["CREATE - Insertar usuario", $createSuccess, $createSuccess ? "✓ Usuario '$testUser' creado" : '❌ Error al crear'];

        if ($createSuccess) {
            $newUserId = $conn->insert_id;

            // Test READ (Leer usuario creado)
            $stmt = $conn->prepare("SELECT username, role FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $newUserId);
            $readSuccess = $stmt->execute();
            $result = $stmt->get_result();
            $userData = $result->fetch_assoc();
            $checks['CRUD Usuarios'][] = ["READ - Leer usuario", $readSuccess && $userData, $readSuccess ? "✓ Usuario leído: {$userData['username']}" : '❌ Error al leer'];

            // Test UPDATE (Actualizar usuario)
            $newUsername = $testUser . '_updated';
            $stmt = $conn->prepare("UPDATE usuarios SET username = ? WHERE id = ?");
            $stmt->bind_param("si", $newUsername, $newUserId);
            $updateSuccess = $stmt->execute();
            $checks['CRUD Usuarios'][] = ["UPDATE - Actualizar usuario", $updateSuccess, $updateSuccess ? "✓ Usuario actualizado a '$newUsername'" : '❌ Error al actualizar'];

            // Test DELETE (Eliminar usuario de prueba)
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $newUserId);
            $deleteSuccess = $stmt->execute();
            $checks['CRUD Usuarios'][] = ["DELETE - Eliminar usuario", $deleteSuccess, $deleteSuccess ? '✓ Usuario eliminado' : '❌ Error al eliminar'];
        }

        // Test LOGIN (Verificar contraseña hasheada)
        $res = $conn->query("SELECT password FROM usuarios WHERE username = 'admin' LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $isHashed = password_get_info($row['password'])['algo'] !== 0;
            $loginTest = password_verify('admin123', $row['password']);
            $checks['CRUD Usuarios'][] = ["LOGIN - Verificar hash", $isHashed && $loginTest, $isHashed ? ($loginTest ? '✓ Hash correcto y login funciona' : '⚠️ Hash válido pero contraseña incorrecta') : '❌ Contraseña no hasheada'];
        }
    }

} catch (Exception $e) {
    $dbConnected = false;
    $checks['Base de Datos'][] = ["Conexión", false, "Error: " . $e->getMessage()];
}

// ======================== MOSTRAR RESULTADOS
foreach ($checks as $section => $items) {
    echo "<div class='section'><h2>$section</h2>";
    foreach ($items as [$check, $pass, $detail]) {
        $icon = $pass ? '✅' : '❌';
        $class = $pass ? 'ok' : 'error';
        echo "<div class='check'><span class='icon $class'>$icon</span> <strong>$check</strong> - <code>$detail</code></div>";
    }
    echo "</div>";
}

// Mostrar pruebas CRUD detalladas
if (!empty($crudTests)) {
    echo "<div class='section'><h2>Detalles CRUD</h2>";
    echo implode('', $crudTests);
    echo "</div>";
}

//RESUMEN FINAL
echo "<div class='section'><h2>Estado Final</h2>";
$allOk = true;
foreach ($checks as $section => $items) {
    foreach ($items as [$check, $pass, $detail]) {
        if (!$pass) $allOk = false;
    }
}

if ($allOk) {
    echo "<div class='success-box'><strong>🎉 ¡Sistema CRUD funcionando perfectamente!</strong><br>Todas las operaciones (Create, Read, Update, Delete) están operativas.</div>";
} else {
    echo "<div class='error-box'><strong>⚠️ Hay problemas que corregir:</strong><br>Revisa los puntos marcados con ❌ arriba.</div>";
}

echo "<div class='info-box'><strong>💡 Próximos pasos:</strong><br>";
echo "1. Si hay errores, corrige la configuración<br>";
echo "2. Prueba el login en: <code>http://localhost/unideportes-system/public/</code><br>";
echo "3. Usuario admin: <code>admin</code> / Contraseña: <code>admin123</code></div>";

echo "</div></body></html>";
