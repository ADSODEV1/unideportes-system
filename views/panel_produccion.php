<?php
// views/panel_produccion.php

// 1. INICIALIZACIÓN Y SEGURIDAD CENTRALIZADA DE TU SISTEMA
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validamos que estrictamente solo el 'admin' pueda gestionar este panel del taller
require_login(['admin']);

// Cargamos tu conexión PDO original
$pdo = app();
$conn = connection(); 

$rol_usuario = $_SESSION['role'] ?? '';
$usuario_nombre = $_SESSION['username'] ?? 'Usuario';
$pagina_actual = basename($_SERVER['PHP_SELF']);
$base = "/unideportes-system";

// 2. OBTENER LOS PEDIDOS ACTIVOS SUMANDO EL ABONO NATIVO + PAGOS DE LA TABLA EXTRA
try {
    // CORRECCIÓN: Sumamos el p.abono (de la tabla pedidos) con los montos de la tabla pagos si existen
    $sql = "SELECT p.*, 
                   COALESCE(p.total_pedido, dt.total_detalle, 0) AS total_pedido_real,
                   c.nombre_completo as cliente_nombre,
                   IFNULL(pg.total_pagado, 0) AS total_pagado_db,
                   COALESCE(
                       p.saldo_pendiente,
                       GREATEST(
                           COALESCE(p.total_pedido, dt.total_detalle, 0) - (COALESCE(p.abono, 0) + IFNULL(pg.total_pagado, 0)),
                           0
                       )
                   ) AS saldo_pendiente_real
            FROM pedidos p 
            LEFT JOIN (
                SELECT pedido_id, SUM(cantidad * precio_unitario) AS total_detalle
                FROM detalle_pedido
                GROUP BY pedido_id
            ) dt ON dt.pedido_id = p.id
            LEFT JOIN (
                SELECT id_pg_pedido, SUM(monto) AS total_pagado
                FROM pagos
                GROUP BY id_pg_pedido
            ) pg ON pg.id_pg_pedido = p.id
            LEFT JOIN clientes c ON p.cliente_id = c.id 
            WHERE p.estado IN ('En Corte', 'En Confección', 'En Costura', 'En Acabado')
            ORDER BY p.fecha_entrega ASC";
            
    $stmt_pedidos = $conn->query($sql);
    $pedidos_activos = $stmt_pedidos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error al consultar la línea de producción: " . $e->getMessage());
}

// Incluir el Header Nativo del sistema
include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">

    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">

        <div class="page-header" style="margin-bottom: 25px;">
            <h2>🧵 Órdenes en Línea de Fabricación (Taller)</h2>
            <p>Monitorea las prendas en confección avanzada y gestiona las fases operativas de la fábrica.</p>
        </div>
        
        <div class="table-responsive" style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 20px;">
            <table class="tabla-maestra" style="width: 100%; min-width: 800px; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th>OP #</th>
                        <th>Cliente</th>
                        <th>Fecha Entrega</th>
                        <th>Estado de Fábrica</th>
                        <th>Cuentas (Abono / Saldo)</th>
                        <th>Lo que se va a confeccionar</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($pedidos_activos) == 0): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-light); padding: 40px;">
                                No hay órdenes activas en fabricación en este momento.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach($pedidos_activos as $pedido): ?>
                        <?php 
                        $p_id = $pedido['id'];
                        
                        // Buscar detalles del pedido por PDO
                        // Evitar referenciar columnas que pueden no existir en la BD remota
                        $stmtD = $conn->prepare("SELECT dp.*, prod.nombre AS producto_nombre FROM detalle_pedido dp 
                                                 LEFT JOIN productos prod ON dp.producto_id = prod.id 
                                                 WHERE dp.pedido_id = ?");
                        $stmtD->execute([$p_id]);
                        $detalles = $stmtD->fetchAll(PDO::FETCH_ASSOC);
                        
                        // CÁLCULO DE CUENTAS EN TIEMPO REAL
                        $total_cuenta = floatval($pedido['total_pedido_real'] ?? 0);
                        $saldo_real = max(0, floatval($pedido['saldo_pendiente_real'] ?? 0));
                        $abono_real = max(0, $total_cuenta - $saldo_real);

                        // Clases dinámicas nativas para Badges según estado
                        $clase_badge = 'naranja'; 
                        if(in_array($pedido['estado'], ['En Confección', 'En Costura'])) $clase_badge = 'azul';
                        if($pedido['estado'] == 'En Acabado') $clase_badge = 'verde';
                        ?>
                        <tr>
                            <td><strong>#<?= $pedido['id']; ?></strong></td>
                            <td><?= htmlspecialchars($pedido['cliente_nombre'] ?? 'Cliente General'); ?></td>
                            <td>
                                <span style="color: #e74c3c; font-weight: bold; white-space: nowrap;">
                                    📅 <?= date('d/m/Y', strtotime($pedido['fecha_entrega'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $clase_badge; ?>">
                                    <?= $pedido['estado']; ?>
                                </span>
                            </td>
                            <td>
                                <small style="display: block; color: #27ae60; font-weight: 500; white-space: nowrap;">
                                    Abonó: $<?= number_format($abono_real, 0, ',', '.'); ?>
                                </small>
                                <small style="display: block; color: #c0392b; font-weight: bold; white-space: nowrap;">
                                    Debe: $<?= number_format($saldo_real, 0, ',', '.'); ?>
                                </small>
                            </td>
                            <td>
                                <ul style="list-style: none; padding-left: 0; margin-bottom: 0; font-size: 0.9rem;">
                                    <?php if (!empty($detalles)): ?>
                                        <?php foreach($detalles as $det): 
                                            $nombre_det = $det['producto_nombre'] ?? $det['nombre'] ?? $det['producto'] ?? $pedido['detalle'] ?? 'Prenda'; ?>
                                            <li style="margin-bottom: 4px;">
                                                🧵 <strong>(x<?= $det['cantidad']; ?>)</strong> 
                                                <?= htmlspecialchars($nombre_det); ?> 
                                                <span style="color: #7f8c8d; font-size: 0.8rem; display: block;">[Talla: <?= $det['talla'] ?: 'N/A'; ?> | Color: <?= $det['color'] ?: 'N/A'; ?>]</span>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li style="margin-bottom: 4px;">
                                            🧵 <?= htmlspecialchars($pedido['detalle'] ?? 'Pedido sin detalle cargado'); ?>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </td>
                            <td style="text-align: center;">
                                <form action="../controllers/cambiar_estado_pedido.php" method="POST" style="display: inline-block;">
                                    <input type="hidden" name="pedido_id" value="<?= $pedido['id']; ?>">
                                    <select name="nuevo_estado" class="search-bar" style="padding: 5px 8px; font-size: 0.85rem; width: auto; margin: 0;" onchange="this.form.submit()">
                                        <option value="">-- Avanzar --</option>
                                        <option value="En Corte">Mover a Corte ✂️</option>
                                        <option value="En Confección">Mover a Costura 🪡</option>
                                        <option value="En Acabado">Mover a Acabado ✨</option>
                                        <option value="Terminado">Ir a Despacho / Entregar 📦</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<?php 
// Incluir Footer del sistema
include(__DIR__ . "/footer.php"); 
?>