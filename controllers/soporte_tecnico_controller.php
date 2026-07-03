<?php
// controllers/soporte_tecnico_controller.php

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/SoporteTecnicoModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login(['vendedor', 'colaborador', 'admin']);
$conn = app();
asegurarTablaSoporteTecnico($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/soporte_tecnico.php');
    exit();
}

$accion = trim($_POST['accion'] ?? '');

try {
    if ($accion === 'crear') {
        $asunto = trim($_POST['asunto'] ?? '');
        $prioridad = trim($_POST['prioridad'] ?? 'Media');
        $comentario = trim($_POST['comentario_solucion'] ?? '');
        $vendedor = trim($_SESSION['username'] ?? 'Sistema');

        $prioridadesValidas = ['Crítica', 'Alta', 'Media', 'Baja'];

        if ($asunto === '' || !in_array($prioridad, $prioridadesValidas, true)) {
            throw new RuntimeException('Datos inválidos para crear el ticket.');
        }

        crearTicketSoporte($conn, $asunto, $prioridad, $vendedor, $comentario !== '' ? $comentario : null);

        $vistaDestino = (($_SESSION['role'] ?? '') === 'admin')
            ? '../views/soporte_tecnico.php'
            : '../views/soporte_tecnico_vendedor.php';

        header('Location: ' . $vistaDestino . '?success=ticket_creado');
        exit();
    }

    if ($accion === 'actualizar') {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            throw new RuntimeException('Acceso no autorizado para actualizar tickets.');
        }

        $idTicket = (int) ($_POST['id_ticket'] ?? 0);
        $estado = trim($_POST['estado'] ?? 'Abierto');
        $comentario = trim($_POST['comentario_solucion'] ?? '');

        $estadosValidos = ['Abierto', 'En Proceso', 'Resuelto', 'Cerrado'];

        if ($idTicket <= 0 || !in_array($estado, $estadosValidos, true)) {
            throw new RuntimeException('Datos inválidos para actualizar el ticket.');
        }

        actualizarTicketSoporte($conn, $idTicket, $estado, $comentario !== '' ? $comentario : null);

        // Mensaje requerido por especificación
        header('Location: ../views/soporte_tecnico.php?success=ticket_actualizado');
        exit();
    }

    throw new RuntimeException('Acción no permitida.');
} catch (Throwable $e) {
    header('Location: ../views/soporte_tecnico.php?error=' . urlencode($e->getMessage()));
    exit();
}
