<?php
// config/connection.php

function connection() {
    $host = "127.0.0.1";
    $dbname = "unideportes";
    $username = "root";
    $password = "";

    try {
        // Usamos PDO
        $dsn = "mysql:host=" . $host . ";dbname=" . $dbname . ";charset=utf8mb4";
        
        $pdo = new PDO($dsn, $username, $password);
        
        // Configuración de PDO
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $pdo;
    } catch (PDOException $e) {
        // En caso de error
        die("Error de conexión a la BD: " . $e->getMessage());
    }
}
?>