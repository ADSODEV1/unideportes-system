<?php
// Redirige al controlador real si alguien accede a la ruta desde /views/delete_user.php
$query = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: ../controllers/delete_user.php' . $query);
exit();
