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
    // ============================================
    // ACCIÓN: CREAR TICKET
    // ============================================
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

    // ============================================
    // ACCIÓN: ACTUALIZAR TICKET (solo admin)
    // ============================================
    if ($accion === 'actualizar') {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            throw new RuntimeException('Acceso no autorizado para actualizar tickets.');
        }

        $idTicket = (int) ($_POST['id_ticket'] ?? 0);
        $estado = trim($_POST['estado'] ?? 'Abierto');
        $nuevoComentario = trim($_POST['nuevo_comentario'] ?? '');

        $estadosValidos = ['Abierto', 'En Proceso', 'Resuelto', 'Cerrado'];

        if ($idTicket <= 0 || !in_array($estado, $estadosValidos, true)) {
            throw new RuntimeException('Datos inválidos para actualizar el ticket.');
        }

        // Actualizar estado
        actualizarTicketSoporte($conn, $idTicket, $estado, null);

        // Si hay nuevo comentario, agregarlo al historial
        if ($nuevoComentario !== '') {
            $autor = trim($_SESSION['username'] ?? 'Sistema');
            agregarComentario($conn, $idTicket, $autor, $nuevoComentario);
        }

        header('Location: ../views/soporte_tecnico.php?success=ticket_actualizado');
        exit();
    }

    // ============================================
    // ACCIÓN: COMENTAR (vendedor o admin)
    // ============================================
    if ($accion === 'comentar') {
        $idTicket = (int) ($_POST['id_ticket'] ?? 0);
        $nuevoComentario = trim($_POST['nuevo_comentario'] ?? '');
        $vendedorSesion = trim($_SESSION['username'] ?? '');

        if ($idTicket <= 0 || $nuevoComentario === '') {
            throw new RuntimeException('Datos inválidos para comentar.');
        }

        // Verificar que el ticket sea del vendedor (si no es admin)
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $stmt = $conn->prepare("SELECT vendedor FROM soporte_tickets WHERE id_ticket = ?");
            $stmt->execute([$idTicket]);
            $ticketVendedor = $stmt->fetchColumn();

            if ($ticketVendedor !== $vendedorSesion) {
                throw new RuntimeException('No puedes comentar en tickets de otros vendedores.');
            }
        }

        $autor = $vendedorSesion;
        agregarComentario($conn, $idTicket, $autor, $nuevoComentario);

        $vistaDestino = (($_SESSION['role'] ?? '') === 'admin')
            ? '../views/soporte_tecnico.php'
            : '../views/soporte_tecnico_vendedor.php';

        header('Location: ' . $vistaDestino . '?success=comentario_agregado');
        exit();
    }

    // Si llega aquí, la acción no es válida
    throw new RuntimeException('Acción no permitida: ' . htmlspecialchars($accion));

} catch (Throwable $e) {
    header('Location: ../views/soporte_tecnico.php?error=' . urlencode($e->getMessage()));
    exit();
}

