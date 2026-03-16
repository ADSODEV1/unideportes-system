<?php
function connection() {
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "unideportes";

    $conn = mysqli_connect($host, $user, $pass, $db);

    if (!$conn) {
        die("Error crítico: " . mysqli_connect_error());
    }

    mysqli_set_charset($conn, "utf8");
    
    return $conn;
}

// Esto crea la conexión para los archivos que NO usan la función
$conn = connection(); 
?>