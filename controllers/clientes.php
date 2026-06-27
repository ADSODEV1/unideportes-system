<?php
// controllers/clientes.php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ClienteModel.php';

require_login(['vendedor', 'colaborador', 'admin']);
$conn = app();


// FLUJO 1: CREAR NUEVO CLIENTE (Todos los roles)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generar código descriptivo automáticamente
    $codigo_auto = 'CLI-' . date('ymd') . '-' . rand(100, 999);
    
    $data = [
        'codigo_descriptivo' => $codigo_auto,
        'nombre_completo'    => trim(request('nombre_completo')),
        'nit_cedula'         => trim(request('nit_cedula')),
        'telefono'           => trim(request('telefono')) ?: null,
        'email'              => trim(request('email')) ?: null,
        'tipo_cliente'       => request('tipo_cliente') ?: 'Individual',
        'direccion'          => trim(request('direccion')) ?: null,
        'barrio'             => trim(request('barrio')) ?: null,
        'ciudad'             => trim(request('ciudad')) ?: 'Sogamoso',
        'referencia_entrega' => trim(request('referencia_entrega')) ?: null,
    ];

    // Validación básica
    if (empty($data['nombre_completo']) || empty($data['nit_cedula'])) {
        redirect('../views/nuevo_cliente.php?error=datos_invalidos');
    }

    try {
        if (crearCliente($conn, $data)) {
            redirect('../views/clientes.php?msj=cliente_creado');
        }
        redirect('../views/nuevo_cliente.php?error=fallo_creacion');
    } catch (Exception $e) {
        redirect('../views/nuevo_cliente.php?error=' . urlencode($e->getMessage()));
    }
}


// FLUJO 2: CAMBIAR ESTADO (SOLO ADMIN)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && request('action') === 'toggle_status') {
    
    // ✅ Restricción SOLO para este flujo
    if ($_SESSION['role'] !== 'admin') {
        redirect('../views/clientes.php?error=permiso_denegado');
    }
    
    $id = intval(request('id'));
    $nuevo_estado = request('estado');
    $search_persist = request('search');

    // Validación estricta
    if ($id > 0 && in_array($nuevo_estado, ['activo', 'inactivo'])) {
        try {
            $sql = "UPDATE clientes SET estado = :estado WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'estado' => $nuevo_estado,
                'id'     => $id
            ]);

            $url_retorno = '../views/clientes.php?msj=estado_actualizado';
            if (!empty($search_persist)) {
                $url_retorno .= '&search=' . urlencode($search_persist);
            }
            redirect($url_retorno);

        } catch (Exception $e) {
            redirect('../views/clientes.php?error=fallo_actualizacion');
        }
    }
    
    redirect('../views/clientes.php?error=datos_invalidos');
}

// Redirección por defecto
redirect('../views/clientes.php');