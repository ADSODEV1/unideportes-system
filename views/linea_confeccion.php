<?php
// Alias para mantener compatibilidad con enlaces existentes.
// El sistema usa actualmente `venta_mayorista.php` como la vista de la línea de confección.

$query = $_SERVER['QUERY_STRING'] ?? '';
$location = '/unideportes-system/views/venta_mayorista.php';
if ($query !== '') {
    $location .= '?' . $query;
}
header('Location: ' . $location);
exit();
