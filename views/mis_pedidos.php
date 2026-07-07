<?php
// views/mis_pedidos.php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = app();

// Seguridad: Ajustado para los roles de tu sistema (Admin o Vendedor)
require_login(['admin' , 'colaborador', 'vendedor']);

// Capturar filtro de búsqueda si existe
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// Construir consulta dinámica alineada a unideportes-bd.sql con cálculo matemático corregido
try {
    $sql = "SELECT p.id, 
                   c.nombre_completo AS cliente_nombre, 
                   c.nit_cedula AS cliente_nit,
                   COALESCE(
                       NULLIF(
                           (SELECT GROUP_CONCAT(
                               CONCAT(
                                   COALESCE(NULLIF(prod.nombre, ''), NULLIF(p.detalle, ''), 'Producto personalizado'),
                                   ' x',
                                   dp.cantidad
                               )
                               ORDER BY dp.id ASC
                               SEPARATOR ', '
                           )
                           FROM detalle_pedido dp
                           LEFT JOIN productos prod ON prod.id = dp.producto_id
                           WHERE dp.pedido_id = p.id),
                           ''
                       ),
                       p.detalle,
                       'Pedido sin detalle'
                   ) AS detalle_resumen,
                   COALESCE(
                       (SELECT SUM(dp.cantidad * dp.precio_unitario)
                        FROM detalle_pedido dp
                        WHERE dp.pedido_id = p.id),
                       p.total_pedido,
                       0
                   ) AS total_pedido_real,
                   p.estado,
                   IFNULL((SELECT SUM(pa.monto) FROM pagos pa WHERE pa.id_pg_pedido = p.id), 0) AS total_pagado,
                   COALESCE(
                       p.saldo_pendiente,
                       GREATEST(
                           COALESCE(
                               (SELECT SUM(dp.cantidad * dp.precio_unitario)
                                FROM detalle_pedido dp
                                WHERE dp.pedido_id = p.id),
                               p.total_pedido,
                               0
                           ) - IFNULL((SELECT SUM(pa.monto) FROM pagos pa WHERE pa.id_pg_pedido = p.id), 0),
                           0
                       )
                   ) AS saldo_pendiente_real
            FROM pedidos p
            INNER JOIN clientes c ON p.cliente_id = c.id
            WHERE p.estado != 'Entregado'";

    if ($busqueda !== '') {
        $sql .= " AND (c.nombre_completo LIKE :busqueda OR c.nit_cedula LIKE :busqueda)";
    }

    $sql .= " ORDER BY p.id DESC";
    
    $stmt = $pdo->prepare($sql);
    
    if ($busqueda !== '') {
        $stmt->bindValue(':busqueda', "%{$busqueda}%", PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $pedidos = [];
    $error_msg = "Error al cargar la lista de pedidos: " . $e->getMessage();
}

// Mensajes de estado
$status = $_GET['status'] ?? null;
$monto_pagado_msg = isset($_GET['monto']) ? floatval($_GET['monto']) : null;
$saldo_anterior_msg = isset($_GET['saldo_anterior']) ? floatval($_GET['saldo_anterior']) : null;
$saldo_actual_msg = isset($_GET['saldo_actual']) ? floatval($_GET['saldo_actual']) : null;

// Incluir Header del sistema original de la marca
include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">

    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">
        
        <div class="page-header" style="margin-bottom: 25px;">
            <h1>🎁 Despacho y Entrega de Pedidos</h1>
            <p>Busca los pedidos listos de clientes, recauda saldos pendientes y efectúa la entrega formal.</p>
        </div>

        <?php if ($status === 'success'): ?>
            <div class="alert-success" style="margin-bottom: 20px;">
                ¡Pedido entregado con éxito y pago registrado en el histórico!
            </div>
        <?php elseif ($status === 'pago_success'): ?>
            <div class="alert-success" style="margin-bottom: 20px;">
                ¡Pago registrado correctamente!
                <?php if ($monto_pagado_msg !== null && $saldo_actual_msg !== null): ?>
                    <br><small>
                        Se registró un pago de <strong>$<?= number_format($monto_pagado_msg, 0, ',', '.') ?></strong>
                        <?php if ($saldo_anterior_msg !== null): ?>
                            y el saldo bajó de <strong>$<?= number_format($saldo_anterior_msg, 0, ',', '.') ?></strong>
                        <?php endif; ?>
                        a <strong>$<?= number_format($saldo_actual_msg, 0, ',', '.') ?></strong>.
                    </small>
                <?php endif; ?>
            </div>
        <?php elseif ($status === 'error'): ?>
            <div class="alert-error" style="margin-bottom: 20px;">
                Hubo un problema al procesar la operacion del pedido. Por favor, intente nuevamente.
                <?php if (!empty($_GET['msg'])): ?>
                    <br><small><?= htmlspecialchars($_GET['msg']) ?></small>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
            <div class="alert-error" style="margin-bottom: 20px;">
                <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <form method="GET" action="" class="search-bar">
            <input type="text" name="buscar" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Buscar por nombre de cliente o NIT/Cédula...">
            <button type="submit" class="btn-primary">🔍 Buscar</button>
            <?php if ($busqueda !== ''): ?>
                <a href="mis_pedidos.php" class="btn-secondary" style="display: inline-flex; align-items: center; text-decoration: none;">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="table-responsive">
            <table class="tabla-maestra">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Detalle / Prendas</th>
                        <th>Total Pedido</th>
                        <th>Estado del Pedido</th>
                        <th>Estado de Cartera</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pedidos)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-light); padding: 30px;">
                                No se encontraron pedidos pendientes por entregar.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pedidos as $pedido): 
                            $saldo_pendiente = max(0, floatval($pedido['saldo_pendiente_real'] ?? 0)); 
                        ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($pedido['cliente_nombre']) ?></strong><br>
                                    <small style="color: var(--text-light);">NIT: <?= htmlspecialchars($pedido['cliente_nit']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($pedido['detalle_resumen']) ?></td>
                                <td><strong>$<?= number_format($pedido['total_pedido_real'], 0, ',', '.') ?></strong></td>
                                <td>
                                    <?php
                                        $estadoBadgeStyle = match($pedido['estado']) {
                                            'En Corte'      => 'background:#fef3c7; color:#92400e; border:1px solid #fde68a;',
                                            'En Costura',
                                            'En Confección' => 'background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe;',
                                            'Terminado'     => 'background:#d1fae5; color:#065f46; border:1px solid #6ee7b7;',
                                            default         => 'background:#f1f5f9; color:#475569; border:1px solid #e2e8f0;',
                                        };
                                    ?>
                                    <span style="display:inline-block; padding:4px 10px; border-radius:20px; font-size:0.82rem; font-weight:700; white-space:nowrap; <?= $estadoBadgeStyle ?>">
                                        <?= htmlspecialchars($pedido['estado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($saldo_pendiente > 0): ?>
                                        <span class="badge naranja">Por Pagar: $<?= number_format($saldo_pendiente, 0, ',', '.') ?></span>
                                    <?php else: ?>
                                        <span class="badge verde">Pagado Totalmente ✅</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; min-width: 260px;">
                                    <?php if ($saldo_pendiente > 0): ?>
                                        <form method="POST" action="/unideportes-system/controllers/procesar_entrega_controller.php"
                                              onsubmit="return confirm('¿Registrar este pago/abono?');"
                                              style="display:flex; flex-direction:column; gap:6px; align-items:stretch; margin-bottom:8px;">
                                            <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                                            <input type="hidden" name="accion" value="abonar">

                                            <div style="display:flex; gap:6px; align-items:center;">
                                                <label style="font-size:0.8rem; white-space:nowrap; color:var(--text-light);">Monto ($):</label>
                                                <input type="number" name="pago_recibido" min="0.01" step="0.01"
                                                       max="<?= number_format($saldo_pendiente, 2, '.', '') ?>"
                                                       value="<?= number_format($saldo_pendiente, 2, '.', '') ?>"
                                                       required
                                                       style="width:120px; padding:6px 8px; border:1px solid var(--border); border-radius:6px; font-size:0.9rem;">
                                            </div>

                                            <select name="metodo_pago" style="padding:6px 8px; border:1px solid var(--border); border-radius:6px; font-size:0.85rem; background:#fff;">
                                                <option value="Efectivo">💵 Efectivo</option>
                                                <option value="Transferencia">📲 Transferencia</option>
                                                <option value="Tarjeta">💳 Tarjeta</option>
                                            </select>

                                            <button type="submit" class="btn-action btn-active"
                                                    style="font-size:0.88rem; font-weight:600; padding:8px 10px; border:none; cursor:pointer;">
                                                💰 Registrar Pago
                                            </button>
                                        </form>

                                        <button type="button" class="btn-action" disabled
                                                style="font-size:0.84rem; padding:7px 10px; border:none; cursor:not-allowed; opacity:0.6;"
                                                title="Primero registra el pago completo para entregar">
                                            📦 Entregar (bloqueado por saldo)
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" action="/unideportes-system/controllers/procesar_entrega_controller.php"
                                              onsubmit="return confirm('¿Confirmas la entrega del pedido?');">
                                            <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                                            <input type="hidden" name="accion" value="entregar">
                                            <button type="submit" class="btn-action btn-active"
                                                    style="font-size:0.88rem; font-weight:600; padding:8px 14px; border:none; cursor:pointer;">
                                                📦 Confirmar Entrega
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<?php 
// Incluir Footer del sistema
include(__DIR__ . "/footer.php"); 
?>