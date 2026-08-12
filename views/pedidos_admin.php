<?php
// views/pedidos_admin.php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Headers anti-caché
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Restricción estricta: Solo producción y administración
require_login(['admin']);

$pdo = app();
$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;

// =========================================================================
// PROCESAR CAMBIO DE ESTADO DESDE LA FÁBRICA
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $pedido_id = intval($_POST['pedido_id']);
    $nuevo_estado = isset($_POST['estado_fabrica']) ? trim($_POST['estado_fabrica']) : '';

    // ✅ VALORES CORREGIDOS: Deben coincidir EXACTAMENTE con el ENUM de tu BD
    $estados_validos = ['En Corte', 'En Costura', 'Terminado', 'Entregado'];
    
    if (in_array($nuevo_estado, $estados_validos)) {
        try {
            $stmt = $pdo->prepare("UPDATE pedidos SET estado = ?, fecha_actualizacion = NOW() WHERE id = ?");
            $stmt->execute([$nuevo_estado, $pedido_id]);
            
            $filas_afectadas = $stmt->rowCount();
            
            if ($filas_afectadas > 0) {
                $msg = ($nuevo_estado === 'Terminado') ? 'pedido_listo_pos' : 'estado_actualizado';
                header("Location: pedidos_admin.php?success=" . $msg . "&t=" . time());
                exit();
            } else {
                header("Location: pedidos_admin.php?error=no_cambio&t=" . time());
                exit();
            }
        } catch (Exception $e) {
            error_log("Error al actualizar pedido ID $pedido_id: " . $e->getMessage());
            header("Location: pedidos_admin.php?error=db_error&t=" . time());
            exit();
        }
    } else {
        header("Location: pedidos_admin.php?error=estado_invalido&t=" . time());
        exit();
    }
}

// =========================================================================
// CONSULTA DE ÓRDENES EN PRODUCCIÓN
// =========================================================================
$stmt = $pdo->query("SELECT p.*, c.nombre_completo, c.nit_cedula 
                     FROM pedidos p 
                     INNER JOIN clientes c ON p.cliente_id = c.id 
                     WHERE p.estado NOT IN ('Entregado', 'Cancelado') 
                     ORDER BY 
                        CASE p.estado 
                            WHEN 'En Corte' THEN 1 
                            WHEN 'En Costura' THEN 2 
                            WHEN 'Terminado' THEN 3 
                            ELSE 4 
                        END ASC,
                     p.fecha_entrega ASC");
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =========================================================================
// FUNCIONES AUXILIARES PARA FECHAS
// =========================================================================
$dias_semana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$hoy_timestamp = strtotime('today');

function calcular_urgencia($fecha_entrega, $hoy_timestamp, $dias_semana) {
    $fecha_ts = strtotime($fecha_entrega);
    $diferencia_dias = (int)floor(($fecha_ts - $hoy_timestamp) / 86400);
    $dia_semana = $dias_semana[date('w', $fecha_ts)];
    $fecha_formateada = date('d-m-Y', $fecha_ts);
    
    if ($diferencia_dias < 0) {
        return [
            'badge' => '⚠️ VENCIDO',
            'badge_class' => 'report-badge report-badge--danger',
            'color' => 'var(--danger)',
            'detalle' => 'Hace ' . abs($diferencia_dias) . ' día' . (abs($diferencia_dias) === 1 ? '' : 's'),
            'fecha_completa' => "$dia_semana $fecha_formateada"
        ];
    } elseif ($diferencia_dias === 0) {
        return [
            'badge' => '🔥 HOY',
            'badge_class' => 'report-badge report-badge--danger',
            'color' => 'var(--danger)',
            'detalle' => 'Entrega hoy mismo',
            'fecha_completa' => "$dia_semana $fecha_formateada"
        ];
    } elseif ($diferencia_dias <= 3) {
        return [
            'badge' => '⏰ URGENTE',
            'badge_class' => 'report-badge report-badge--warning',
            'color' => 'var(--warning)',
            'detalle' => "Faltan $diferencia_dias día" . ($diferencia_dias === 1 ? '' : 's'),
            'fecha_completa' => "$dia_semana $fecha_formateada"
        ];
    } elseif ($diferencia_dias <= 7) {
        return [
            'badge' => '📅 Próximo',
            'badge_class' => 'report-badge report-badge--info',
            'color' => 'var(--primary)',
            'detalle' => "Faltan $diferencia_dias días",
            'fecha_completa' => "$dia_semana $fecha_formateada"
        ];
    } else {
        return [
            'badge' => '✅ En tiempo',
            'badge_class' => 'report-badge report-badge--success',
            'color' => 'var(--success)',
            'detalle' => "Faltan $diferencia_dias días",
            'fecha_completa' => "$dia_semana $fecha_formateada"
        ];
    }
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . "/sidebar_control.php"); ?>

    <main class="main-content-panel">
        
        <div class="page-header">
            <div>
                <h1 class="page-header__title">🏭 Control de Producción Taller</h1>
                <p class="page-header__subtitle">Mapeo de órdenes mayoristas en confección. Cambia el estado para que se refleje en el punto de venta.</p>
            </div>
        </div>

        <?php if ($success === 'estado_actualizado'): ?>
            <div class="alert alert-success">🔄 Estado del pedido sincronizado con éxito.</div>
        <?php elseif ($success === 'pedido_listo_pos'): ?>
            <div class="alert alert-success">✅ Pedido marcado como <strong>Terminado</strong>. Ya es visible en el Punto de Venta.</div>
        <?php elseif ($error === 'no_cambio'): ?>
            <div class="alert alert-warning">ℹ️ El estado ya estaba seleccionado, no se realizaron cambios.</div>
        <?php elseif ($error): ?>
            <div class="alert alert-error">❌ Ocurrió un error al procesar la solicitud. Por favor, inténtalo de nuevo.</div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="tabla-maestra">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Detalles de Confección</th>
                        <th style="text-align: center;">Cantidad</th>
                        <th>Fecha Límite</th>
                        <th style="text-align: center;">Fase de Fábrica</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pedidos) > 0): ?>
                        <?php foreach ($pedidos as $row): 
                            $urgencia = calcular_urgencia($row['fecha_entrega'], $hoy_timestamp, $dias_semana);
                        ?>
                            <tr>
                                <td>
                                    <strong style="display: block;"><?= htmlspecialchars($row['nombre_completo']) ?></strong>
                                    <span style="font-size: 0.82rem; color: var(--text-light);">NIT: <?= htmlspecialchars($row['nit_cedula']) ?></span>
                                </td>
                                <td>
                                    <span style="font-weight: 600;"><?= htmlspecialchars($row['detalle']) ?></span>
                                    <?php if (!empty($row['descripcion'])): ?>
                                        <div style="margin-top: 8px; padding: 8px; background: var(--input-bg); border-left: 3px solid var(--warning); border-radius: 
                                        var(--radius-sm); font-size: 0.85rem; color: var(--text);">
                                            <strong>📝 Obs:</strong> <?= htmlspecialchars($row['descripcion']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; font-weight: 700; font-size: 1.1rem;">
                                    <?= (int)$row['cantidad'] ?>
                                </td>
                                <td>
                                    <div style="margin-bottom: 6px;">
                                        <span class="<?= $urgencia['badge_class'] ?>" style="font-size: 0.78rem;">
                                            <?= $urgencia['badge'] ?>
                                        </span>
                                    </div>
                                    <div style="font-weight: 600; color: <?= $urgencia['color'] ?>; font-size: 0.92rem;">
                                        📅 <?= $urgencia['fecha_completa'] ?>
                                    </div>
                                    <div style="font-size: 0.78rem; color: var(--text-light); margin-top: 2px;">
                                        <?= $urgencia['detalle'] ?>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <form method="POST" action="" class="form-estado" onsubmit="return manejarEnvio(this)">
                                        <input type="hidden" name="pedido_id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="actualizar_estado" value="1">
                                        
                                        <!-- ✅ VALORES CORREGIDOS para coincidir con tu ENUM -->
                                        <select name="estado_fabrica" class="form-control" style="max-width: 220px; margin: 0 auto; cursor: pointer;" 
                                        onchange="this.form.submit()">
                                            <option value="En Corte" <?= $row['estado'] === 'En Corte' ? 'selected' : '' ?>>✂️ En Corte</option>
                                            <option value="En Costura" <?= $row['estado'] === 'En Costura' ? 'selected' : '' ?>>🪡 En Costura</option>
                                            <option value="Terminado" <?= $row['estado'] === 'Terminado' ? 'selected' : '' ?>>✅ Terminado (Listo para POS)</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-state">
                                🎉 No hay prendas en la línea de producción actualmente.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
    function manejarEnvio(form) {
        const select = form.querySelector('select[name="estado_fabrica"]');
        select.disabled = true;
        const opcionActual = select.options[select.selectedIndex];
        opcionActual.text = "⏳ Guardando...";
        return true;
    }
</script>

<?php include(__DIR__ . "/footer.php"); ?>