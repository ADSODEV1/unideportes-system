<?php
session_start();
session_destroy(); // Borra todo lo guardado
header("Location: index.php");
exit();
?>