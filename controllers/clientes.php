<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ClienteModel.php';

require_login(['vendedor', 'colaborador', 'admin']);
$conn = app();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nombre'])) {
    $data = [
        'nombre_completo' => request('nombre'),
        'nit_cedula' => request('documento'),
        'telefono' => request('telefono'),
        'email' => request('email') ?: '',
        'tipo_cliente' => request('tipo') ?: 'Individual',
    ];

    if (trim($data['nombre_completo']) && trim($data['nit_cedula'])) {
        if (crearCliente($conn, $data)) {
            redirect('../views/clientes.php?msj=cliente_creado');
        }
        redirect('../views/clientes.php?error=fallo_creacion');
    }

    redirect('../views/clientes.php?error=datos_invalidos');
}

redirect('../views/clientes.php');
