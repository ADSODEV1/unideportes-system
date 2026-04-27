<?php

// POO

$host = "localhost";
$user = "root";
$pass = "";
$db = "unideportes";

// INSTANCIA

$conn = new mysqli($host, $user, $pass, $db);

// VALIDACIÓN

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// IDIOMA
$conn->set_charset("utf8");
?>