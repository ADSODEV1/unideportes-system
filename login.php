<?php
session_start();
session_destroy(); // Elimina la sesión del servidor
header("Location: index.php"); // Te devuelve a la portada
exit();
?>

