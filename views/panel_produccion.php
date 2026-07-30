<?php
// views/panel_produccion.php

// 1. INICIALIZACIÓN Y SEGURIDAD
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validamos que estrictamente solo el 'admin' pueda gestionar este panel
require_login(['admin']);

// Usamos ÚNICAMENTE la conexión PDO oficial del sistema
$pdo = app();

$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;

// =========================================================================
// PROCESAR CAMBIO DE ESTADO (Manejado aquí para garantizar consistencia)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $pedido_id = intval($_POST['pedido_id']);
    $nuevo_estado = isset($_POST['nuevo_estado']) ? trim($_POST['nuevo_estado']) : '';

    // ✅ VALORES CORREGIDOS: Coinciden EXACTAMENTE con el ENUM de tu BD
    $estados_validos = ['En Corte', 'En Costura', 'Terminado', 'Entregado'];
    
    if (in_array($nuevo_estado, $estados_validos)) {
        try {
            $stmt = $pdo->prepare("UPDATE pedidos SET estado = ?, fecha_actualizacion = NOW() WHERE id = ?");
            $stmt->execute([$nuevo_estado, $pedido_id]);
            
            if ($stmt->rowCount() > 0) {
                $msg = ($nuevo_estado === 'Terminado') ? 'pedido_listo_pos' : 'estado_actualizado';
                header("Location: panel_produccion.php?success=" . $msg . "&t=" . time());
                exit();
            } else {
                header("Location: panel_produccion.php?error=no_cambio&t=" . time());
                exit();
            }
        } catch (Exception $e) {
            // SEGURIDAD: Nunca mostrar el error real al usuario
            error_log("Error en panel_produccion al actualizar pedido ID $pedido_id: " . $e->getMessage());
            header("Location: panel_produccion.php?error=db_error&t=" . time());
            exit();
        }
    } else {
        header("Location: panel_produccion.php?error=estado_invalido&t=" . time());
        exit();
    }
}

// =========================================================================
// CONSULTA OPTIMIZADA (Elimina el problema N+1 con GROUP_CONCAT)
// =========================================================================
try {
    $sql = "SELECT 
                p.id, p.estado, p.fecha_entrega, p.abono, p.saldo_pendiente, p.total_pedido,
                c.nombre_completo AS cliente_nombre,
                -- Agrupamos los detalles en un solo texto. 
                -- Usa prod.nombre, y si no existe, muestra 'Producto'.
                GROUP_CONCAT(
                    CONCAT(
                        IFNULL(prod.nombre, 'Producto'), 
                        ' (x', dp.cantidad, ') T:', IFNULL(dp.talla, 'N/A'), ' C:', IFNULL(dp.color, 'N/A')
                    ) SEPARATOR ' | '
                ) AS resumen_detalle
            FROM pedidos p 
            LEFT JOIN detalle_pedido dp ON dp.pedido_id = p.id
            LEFT JOIN productos prod ON dp.producto_id = prod.id
            LEFT JOIN clientes c ON p.cliente_id = c.id 
            WHERE p.estado IN ('En Corte', 'En Costura', 'Terminado')
            GROUP BY p.id
            ORDER BY p.fecha_entrega ASC";
            
    $stmt_pedidos = $pdo->query($sql);
    $pedidos_activos = $stmt_pedidos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error al consultar la línea de producción: " . $e->getMessage());
    $pedidos_activos = []; // Fallback seguro: página no se rompe, muestra tabla vacía
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">
        <div class="page-header">
            <div>
                <h1 class="page-header__title">🧵 Órdenes en Línea de Fabricación</h1>
                <p class="page-header__subtitle">Monitorea las prendas en confección, revisa saldos y gestiona las fases operativas.</p>
            </div>
        </div>
        
        <?php if ($success === 'estado_actualizado'): ?>
            <div class="alert alert-success">🔄 Estado del pedido actualizado correctamente.</div>
        <?php elseif ($success === 'pedido_listo_pos'): ?>
            <div class="alert alert-success">✅ Pedido marcado como <strong>Terminado</strong>. Visible en Punto de Venta.</div>
        <?php elseif ($error === 'no_cambio'): ?>
            <div class="alert alert-warning">ℹ️ El estado ya era el seleccionado, no se realizaron cambios.</div>
        <?php elseif ($error): ?>
            <div class="alert alert-error">❌ Ocurrió un error al procesar la solicitud.</div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="tabla-maestra">
                <thead>
                    <tr>
                        <th>OP #</th>
                        <th>Cliente</th>
                        <th>Fecha Entrega</th>
                        <th>Estado</th>
                        <th>Finanzas (Abono / Saldo)</th>
                        <th>Detalle de Confección</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pedidos_activos) == 0): ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                No hay órdenes activas en fabricación en este momento.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pedidos_activos as $pedido): 
                            // Cálculo seguro de finanzas basado en la tabla pedidos
                            $total = floatval($pedido['total_pedido'] ?? 0);
                            $abono = floatval($pedido['abono'] ?? 0);
                            $saldo = max(0, floatval($pedido['saldo_pendiente'] ?? ($total - $abono)));
                            
                            // Badge según estado (usando clases de tu CSS global)
                            $badge_class = 'report-badge--warning'; // Default (En Corte)
                            if ($pedido['estado'] === 'En Costura') $badge_class = 'report-badge--info';
                            if ($pedido['estado'] === 'Terminado') $badge_class = 'report-badge--success';
                        ?>
                            <tr>
                                <td><strong>#<?= $pedido['id']; ?></strong></td>
                                <td><?= htmlspecialchars($pedido['cliente_nombre'] ?? 'Cliente General'); ?></td>
                                <td style="color: var(--danger); font-weight: 600; white-space: nowrap;">
                                    📅 <?= date('d/m/Y', strtotime($pedido['fecha_entrega'])); ?>
                                </td>
                                <td>
                                    <span class="report-badge <?= $badge_class; ?>">
                                        <?= htmlspecialchars($pedido['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small style="display: block; color: var(--success); font-weight: 600;">
                                        Abonó: $<?= number_format($abono, 0, ',', '.'); ?>
                                    </small>
                                    <small style="display: block; color: var(--danger); font-weight: 700;">
                                        Saldo: $<?= number_format($saldo, 0, ',', '.'); ?>
                                    </small>
                                </td>
                                <td style="font-size: 0.85rem; color: var(--text); line-height: 1.5;">
                                    <?= htmlspecialchars($pedido['resumen_detalle'] ?: 'Sin detalles específicos cargados'); ?>
                                </td>
                                <td style="text-align: center;">
                                    <form method="POST" action="" onsubmit="return manejarEnvioProduccion(this)">
                                        <input type="hidden" name="pedido_id" value="<?= $pedido['id']; ?>">
                                        <input type="hidden" name="actualizar_estado" value="1">
                                        
                                        <select name="nuevo_estado" class="form-control" style="max-width: 160px; font-size: 0.85rem; padding: 6px;" onchange="this.form.submit()">
                                            <option value="">-- Avanzar --</option>
                                            <option value="En Corte" <?= $pedido['estado'] === 'En Corte' ? 'selected' : '' ?>>✂️ En Corte</option>
                                            <option value="En Costura" <?= $pedido['estado'] === 'En Costura' ? 'selected' : '' ?>>🪡 En Costura</option>
                                            <option value="Terminado" <?= $pedido['estado'] === 'Terminado' ? 'selected' : '' ?>>✅ Terminado (POS)</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
    function manejarEnvioProduccion(form) {
        const select = form.querySelector('select[name="nuevo_estado"]');
        if (select.value === "") {
            alert("Por favor, selecciona un estado válido.");
            return false;
        }
        select.disabled = true;
        const opcionActual = select.options[select.selectedIndex];
        opcionActual.text = "⏳ Guardando...";
        return true;
    }
</script>

<?php include(__DIR__ . "/footer.php"); ?>