<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ClienteModel.php';

// Asegurar que solo usuarios autenticados con los roles correctos accedan
require_login(['vendedor', 'colaborador', 'admin']);
$conn = app();

 
// 1. FLUJO POST: CREACIÓN DE NUEVO CLIENTE 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nombre'])) {
    $data = [
        'nombre_completo' => trim(request('nombre')),
        'nit_cedula'      => trim(request('documento')),
        'telefono'        => trim(request('telefono')),
        'email'           => trim(request('email')) ?: '',
        'tipo_cliente'    => request('tipo') ?: 'Individual',
        'estado'          => 'activo' // Criterio: Todo cliente nuevo inicia Activo
    ];

    // Validación básica en el servidor antes de tocar la BD
    if (!empty($data['nombre_completo']) && !empty($data['nit_cedula'])) {
        if (crearCliente($conn, $data)) {
            redirect('../views/clientes.php?msj=cliente_creado');
        }
        redirect('../views/clientes.php?error=fallo_creacion');
    }

    redirect('../views/clientes.php?error=datos_invalidos');
}

 
// 2. FLUJO GET: CAMBIO DE ESTADO (ACTIVO/INACTIVO) 
if ($_SERVER['REQUEST_METHOD'] === 'GET' && request('action') === 'toggle_status') {
    $id = intval(request('id'));
    $nuevo_estado = request('estado'); // Espera 'activo' o 'inactivo'
    $search_persist = request('search'); // Conserva la búsqueda que tenía el usuario en la vista

    // Validamos estrictamente que el ID sea real y el estado sea correcto
    if ($id > 0 && ($nuevo_estado === 'activo' || $nuevo_estado === 'inactivo')) {
        try {
            // Consulta directa segura usando PDO para actualizar el criterio de estado
            $sql = "UPDATE clientes SET estado = :estado WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'estado' => $nuevo_estado,
                'id'     => $id
            ]);

            // Redirección exitosa manteniendo el filtro de búsqueda del usuario si existía
            $url_retorno = '../views/clientes.php?msj=estado_actualizado';
            if (!empty($search_persist)) {
                $url_retorno .= '&search=' . urlencode($search_persist);
            }
            redirect($url_retorno);

        } catch (Exception $e) {
            redirect('../views/clientes.php?error=fallo_actualizacion_estado');
        }
    }
    redirect('../views/clientes.php?error=datos_invalidos');
}

// Redirección por defecto si entran al controlador sin parámetros válidos
redirect('../views/clientes.php');