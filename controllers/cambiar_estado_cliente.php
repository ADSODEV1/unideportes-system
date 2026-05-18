<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ClienteModel.php';

require_login();
$conn = app();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('clientes.php?error=id_invalido');
}

if (!isset($_GET['estado']) || !in_array($_GET['estado'], ['activo', 'inactivo'], true)) {
    redirect('clientes.php?error=estado_invalido');
}

$id = intval(request('id'));
$nuevo_estado = request('estado');

if (!cambiarEstadoCliente($conn, $id, $nuevo_estado)) {
    redirect('clientes.php?error=no_actualizar_estado');
}

redirect('clientes.php?msj=estado_actualizado');
