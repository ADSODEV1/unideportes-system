<?php
// controllers/marcar_entrega.php
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['admin', 'colaborador', 'vendedor']);
$pdo = app();

// 1. Recibimos el ID de la venta desde la URL (?id=...)
$venta_id = intval($_GET['id'] ?? 0);

if ($venta_id > 0) {
    try {
        // 2. Obtenemos el ID del usuario que está haciendo clic desde la sesión
        // IMPORTANTE: Cambia 'user_id' por el nombre exacto que uses en tu login 
        // (ej: $_SESSION['id'], $_SESSION['usuario_id'], etc.)
        $usuario_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

        // 3. AQUÍ ES DONDE SE REGISTRA EN LA BASE DE DATOS:
        // Actualizamos el estado, guardamos QUIÉN entregó y la FECHA/HORA exacta del sistema
        $stmt = $pdo->prepare("
            UPDATE ventas 
            SET estado = 'Entregado', 
                entregado_por = ?, 
                fecha_entrega_real = NOW() 
            WHERE id = ? AND estado = 'En Camino'
        ");
        
        // Ejecutamos la consulta pasando el ID del usuario y el ID de la venta
        $stmt->execute([$usuario_id, $venta_id]);
        
        // 4. Verificamos si se afectó alguna fila (significa que sí estaba "En Camino" y se actualizó)
        if ($stmt->rowCount() > 0) {
            // Redirigimos de vuelta a la vista con un mensaje de éxito
            header("Location: ../views/seguimiento_entregas.php?success=entregado");
        } else {
            // Si rowCount es 0, es porque la venta ya estaba entregada o el ID no existe
            header("Location: ../views/seguimiento_entregas.php?error=no_actualizado");
        }
        exit();
        
    } catch (Exception $e) {
        // Si hay un error de base de datos, lo registramos y mostramos un mensaje genérico
        error_log("Error al marcar entrega ID $venta_id: " . $e->getMessage());
        header("Location: ../views/seguimiento_entregas.php?error=1");
        exit();
    }
}

// Si no llega un ID válido en la URL, redirigimos al listado por seguridad
header("Location: ../views/seguimiento_entregas.php");
exit();