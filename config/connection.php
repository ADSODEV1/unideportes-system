<?php

function connection(): mysqli
{
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }

    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "unideportes";

    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    $conn->set_charset("utf8");
    return $conn;
}

// Compatibilidad con scripts antiguos que esperan $conn
$conn = connection();
?>