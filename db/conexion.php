<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "unideportes-system"; // en phpMyAdmin

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>