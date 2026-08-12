<?php
// views/mis_pedidos.php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = app();

// Seguridad: Ajustado para los roles de tu sistema (Admin o Vendedor)
require_login(['admin', 'colaborador', 'vendedor']);

// Capturar filtro de búsqueda si existe
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// Construir consulta dinámica alineada a unideportes-bd.sql 
try {
    $sql = "SELECT p.id, 
                   c.nombre_completo AS cliente_nombre, 
                   c.nit_cedula AS cliente_nit,
                   COALESCE(
                       NULLIF(
                           (SELECT GROUP_CONCAT(
                                CONCAT(
                                    COALESCE(NULLIF(pbc.tipo_prenda, ''), 'Prenda personalizada'),
                                    ' x',
                                    dp.cantidad
                                )
                                ORDER BY dp.id ASC
                                SEPARATOR ', '
                            )
                            FROM detalle_pedido dp
                            LEFT JOIN precios_base_confeccion pbc ON pbc.id = dp.tipo_prenda_id
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

// Incluir Header del sistema
include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">

    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">
        
        <div class="page-header" style="margin-bottom: 25px;">
            <div>
                <h1>🎁 Despacho y Entrega de Pedidos</h1>
                <p>Busca los pedidos listos de clientes, recauda saldos pendientes y efectúa la entrega formal.</p>
            </div>
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
                Hubo un problema al procesar la operación del pedido. Por favor, intente nuevamente.
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

        <!-- BUSCADOR MEJORADO -->
        <form method="GET" action="" class="mis-pedidos-search">
            <input type="text" name="buscar" value="<?= htmlspecialchars($busqueda) ?>" 
                   placeholder="🔍 Buscar por nombre de cliente o NIT/Cédula...">
            <button type="submit" class="btn-primary">Buscar</button>
            <?php if ($busqueda !== ''): ?>
                <a href="mis_pedidos.php" class="btn-secondary">✖ Limpiar</a>
            <?php endif; ?>
        </form>

        <!-- ============ VISTA DESKTOP: TABLA ============ -->
        <div class="table-responsive mis-pedidos-tabla-desktop">
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
                            <td colspan="6" class="mis-pedidos-empty">
                                <div class="mis-pedidos-empty-icon">📦</div>
                                <p>No se encontraron pedidos pendientes por entregar.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                       <?php foreach ($pedidos as $pedido): 
                            $saldo_pendiente = max(0, floatval($pedido['saldo_pendiente_real'] ?? 0)); 

                            // Normalizar estado para validar entrega (CORRECCIÓN DEL WARNING)
                            $estado_texto = strtolower(trim((string)($pedido['estado'] ?? '')));
                            $estado_terminado = ($estado_texto === 'terminado');
                            $puede_entregar = ($saldo_pendiente <= 0 && $estado_terminado);

                            $motivo_bloqueo = '';
                            if ($saldo_pendiente > 0 && !$estado_terminado) {
                                $motivo_bloqueo = 'Entrega bloqueada: falta pago y el estado debe ser Terminado.';
                            } elseif ($saldo_pendiente > 0) {
                                $motivo_bloqueo = 'Primero registra el pago completo para entregar.';
                            } elseif (!$estado_terminado) {
                                $motivo_bloqueo = 'Entrega bloqueada: el estado debe ser Terminado.';
                            }
                            
                            // Badge de estado del pedido
                            $estadoClase = match($pedido['estado']) {
                                'En Corte'      => 'report-badge--warning',
                                'En Costura',
                                'En Confección' => 'report-badge--info',
                                'Terminado'     => 'report-badge--success',
                                default         => 'report-badge--light',
                            };
                        ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($pedido['cliente_nombre']) ?></strong><br>
                                    <small class="text-muted">NIT: <?= htmlspecialchars($pedido['cliente_nit']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($pedido['detalle_resumen']) ?></td>
                                <td>
                                    <strong class="text-primary">
                                        $<?= number_format($pedido['total_pedido_real'], 0, ',', '.') ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="report-badge <?= $estadoClase ?>">
                                        <?= htmlspecialchars($pedido['estado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($saldo_pendiente > 0): ?>
                                        <span class="report-badge report-badge--warning">
                                            💳 Por Pagar: $<?= number_format($saldo_pendiente, 0, ',', '.') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="report-badge report-badge--success">
                                            ✅ Pagado Totalmente
                                        </span>
                                    <?php endif; ?>
                                </td>
                               <td style="text-align: center; min-width: 280px;">
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

                                            <button type="submit" class="btn-primary" style="font-size:0.88rem; padding:8px 10px;">
                                                💰 Registrar Pago
                                            </button>
                                        </form>

                                        <button type="button" class="btn-secondary" disabled
                                                style="font-size:0.84rem; padding:7px 10px; cursor:not-allowed; opacity:0.6;"
                                                title="<?= htmlspecialchars($motivo_bloqueo) ?>">
                                            📦 Entregar (bloqueado)
                                        </button>

                                    <?php elseif ($puede_entregar): ?>
                                        <form method="POST" action="/unideportes-system/controllers/procesar_entrega_controller.php"
                                            onsubmit="return confirm('¿Confirmas la entrega del pedido?');">
                                            <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                                            <input type="hidden" name="accion" value="entregar">

                                            <button type="submit" class="btn-primary" style="font-size:0.88rem; padding:8px 14px;">
                                                📦 Confirmar Entrega
                                            </button>
                                        </form>

                                    <?php else: ?>
                                        <button type="button" class="btn-secondary" disabled
                                                style="font-size:0.84rem; padding:7px 10px; cursor:not-allowed; opacity:0.6;"
                                                title="<?= htmlspecialchars($motivo_bloqueo) ?>">
                                            📦 Entregar (requiere Terminado)
                                        </button>
                                    <?php endif; ?>
                                </td>
   
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ============ VISTA MÓVIL: TARJETAS ============ -->
        <div class="mis-pedidos-tarjetas-movil">
            <?php if (empty($pedidos)): ?>
                <div class="pedido-card">
                    <div class="pedido-card__body mis-pedidos-empty">
                        <div class="mis-pedidos-empty-icon">📦</div>
                        <p>No se encontraron pedidos pendientes.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($pedidos as $pedido):
                    $saldo_pendiente = max(0, floatval($pedido['saldo_pendiente_real'] ?? 0));

                    // Normalizar estado para validar entrega
                    $estado_texto = strtolower(trim((string)($pedido['estado'] ?? '')));
                    $estado_terminado = ($estado_texto === 'terminado');
                    $puede_entregar = ($saldo_pendiente <= 0 && $estado_terminado);

                    $motivo_bloqueo = '';
                    if ($saldo_pendiente > 0 && !$estado_terminado) {
                        $motivo_bloqueo = 'Entrega bloqueada: falta pago y el estado debe ser Terminado.';
                    } elseif ($saldo_pendiente > 0) {
                        $motivo_bloqueo = 'Primero registra el pago completo para entregar.';
                    } elseif (!$estado_terminado) {
                        $motivo_bloqueo = 'Entrega bloqueada: el estado debe ser Terminado.';
                    }

                    $estadoClase = match($pedido['estado']) {
                        'En Corte'      => 'report-badge--warning',
                        'En Costura',
                        'En Confección' => 'report-badge--info',
                        'Terminado'     => 'report-badge--success',
                        default         => 'report-badge--light',
                    };
                ?>
                    <div class="pedido-card">
                        <!-- Header de la tarjeta -->
                        <div class="pedido-card__header">
                            <div class="pedido-card__cliente">
                                <strong><?= htmlspecialchars($pedido['cliente_nombre']) ?></strong>
                                <small>NIT: <?= htmlspecialchars($pedido['cliente_nit']) ?></small>
                            </div>
                            <div class="pedido-card__total">
                                $<?= number_format($pedido['total_pedido_real'], 0, ',', '.') ?>
                            </div>
                        </div>

                        <!-- Body de la tarjeta -->
                        <div class="pedido-card__body">
                            <div class="pedido-card__row">
                                <span class="pedido-card__label">📦 Detalle</span>
                                <span class="pedido-card__value">
                                    <?= htmlspecialchars($pedido['detalle_resumen']) ?>
                                </span>
                            </div>

                            <div class="pedido-card__row">
                                <span class="pedido-card__label">🏭 Estado</span>
                                <span class="pedido-card__value">
                                    <span class="report-badge <?= $estadoClase ?>">
                                        <?= htmlspecialchars($pedido['estado']) ?>
                                    </span>
                                </span>
                            </div>

                            <div class="pedido-card__row">
                                <span class="pedido-card__label">💰 Cartera</span>
                                <span class="pedido-card__value">
                                    <?php if ($saldo_pendiente > 0): ?>
                                        <span class="report-badge report-badge--warning">
                                            💳 Por Pagar: $<?= number_format($saldo_pendiente, 0, ',', '.') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="report-badge report-badge--success">
                                            ✅ Pagado Totalmente
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <!-- Formulario de pago si hay saldo pendiente -->
                            <?php if ($saldo_pendiente > 0): ?>
                                <div class="pedido-card__pago">
                                    <div class="pedido-card__pago-titulo">
                                        💰 Registrar Pago/Abono
                                    </div>

                                    <form method="POST" action="/unideportes-system/controllers/procesar_entrega_controller.php"
                                        onsubmit="return confirm('¿Registrar este pago/abono?');">
                                        <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                                        <input type="hidden" name="accion" value="abonar">

                                        <div class="pedido-card__pago-input-group">
                                            <label>Monto ($):</label>
                                            <input type="number" name="pago_recibido" min="0.01" step="0.01"
                                                max="<?= number_format($saldo_pendiente, 2, '.', '') ?>"
                                                value="<?= number_format($saldo_pendiente, 2, '.', '') ?>"
                                                required>
                                        </div>

                                        <select name="metodo_pago">
                                            <option value="Efectivo">💵 Efectivo</option>
                                            <option value="Transferencia">📲 Transferencia</option>
                                            <option value="Tarjeta">💳 Tarjeta</option>
                                        </select>

                                        <button type="submit" class="btn-primary" style="width: 100%;">
                                            💰 Registrar Pago
                                        </button>

                                        <button type="button" class="btn-secondary" disabled
                                                style="width: 100%; opacity: 0.6; cursor: not-allowed; font-size: 0.85rem;"
                                                title="<?= htmlspecialchars($motivo_bloqueo) ?>">
                                            📦 Entregar (bloqueado)
                                        </button>
                                    </form>
                                </div>

                            <?php elseif ($puede_entregar): ?>
                                <!-- Botón de entrega cuando está pagado y Terminado -->
                                <div style="padding-top: 12px; border-top: 1px solid var(--border); margin-top: 12px;">
                                    <form method="POST" action="/unideportes-system/controllers/procesar_entrega_controller.php"
                                        onsubmit="return confirm('¿Confirmas la entrega del pedido?');">
                                        <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                                        <input type="hidden" name="accion" value="entregar">

                                        <button type="submit" class="btn-primary" style="width: 100%;">
                                            📦 Confirmar Entrega
                                        </button>
                                    </form>
                                </div>

                            <?php else: ?>
                                <!-- Pedido pagado pero que todavía no está Terminado -->
                                <div style="padding-top: 12px; border-top: 1px solid var(--border); margin-top: 12px;">
                                    <button type="button" class="btn-secondary" disabled
                                            style="width: 100%; opacity: 0.6; cursor: not-allowed; font-size: 0.85rem;"
                                            title="<?= htmlspecialchars($motivo_bloqueo) ?>">
                                        📦 Entregar (requiere Terminado)
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>
</div>

<?php 
// Incluir Footer del sistema
include(__DIR__ . "/footer.php"); 
?>