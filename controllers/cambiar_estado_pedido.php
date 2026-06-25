<?php
// controllers/cambiar_estado_pedido.php

// 1. INICIALIZACIÓN Y SEGURIDAD CENTRALIZADA
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Estricto: Solo el administrador puede mover los estados en el taller de confección
require_login(['admin']);

// Cargamos la conexión PDO nativa de tu sistema
$conn = connection();

// 2. VALIDACIÓN DE DATOS RECIBIDOS POR POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedido_id = isset($_POST['pedido_id']) ? intval($_POST['pedido_id']) : 0;
    $nuevo_estado = isset($_POST['nuevo_estado']) ? trim($_POST['nuevo_estado']) : '';

    // Estados válidos que permite tu flujo de fabricación y tienda
    $estados_permitidos = ['En Corte', 'En Confección', 'En Costura', 'En Acabado', 'Terminado'];

    if ($pedido_id > 0 && in_array($nuevo_estado, $estados_permitidos)) {
        try {
            // 3. ACTUALIZAR EL ESTADO EN LA BASE DE DATOS
            $sql = "UPDATE pedidos SET estado = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nuevo_estado, $pedido_id]);

            // Guardamos un mensaje de éxito en la sesión por si tu layout los muestra
            $_SESSION['mensaje_exito'] = "¡Pedido #{$pedido_id} movido a '{$nuevo_estado}' con éxito!";
            
        } catch (Exception $e) {
            $_SESSION['mensaje_error'] = "Error al actualizar el estado: " . $e->getMessage();
        }
    } else {
        $_SESSION['mensaje_error'] = "Datos inválidos o estado no permitido.";
    }
}

// 4. REDIRECCIÓN AUTOMÁTICA DE VUELTA AL PANEL DE PRODUCCIÓN
// Usamos la ruta relativa correcta para regresar a views/panel_produccion.php
header("Location: ../views/panel_produccion.php");
exit();