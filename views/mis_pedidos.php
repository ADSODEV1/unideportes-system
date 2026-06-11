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
                   p.detalle, 
                   p.total_pedido, 
                   p.estado,
                   IFNULL((SELECT SUM(pa.monto) FROM pagos pa WHERE pa.id_pg_pedido = p.id), 0) AS total_pagado
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
        <?php elseif ($status === 'error'): ?>
            <div class="alert-error" style="margin-bottom: 20px;">
                Hubo un problema al procesar la entrega del pedido. Por favor, intente nuevamente.
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
                        <th>Estado de Cartera</th>
                        <th style="text-align: center;">Acción Comercial</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pedidos)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-light); padding: 30px;">
                                No se encontraron pedidos pendientes por entregar.
                            </td>
                        </tr>
                    <?php dry: ?>
                    <?php else: ?>
                        <?php foreach ($pedidos as $pedido): 
                            $saldo_pendiente = $pedido['total_pedido'] - $pedido['total_pagado']; 
                        ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($pedido['cliente_nombre']) ?></strong><br>
                                    <small style="color: var(--text-light);">NIT: <?= htmlspecialchars($pedido['cliente_nit']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($pedido['detalle']) ?></td>
                                <td><strong>$<?= number_format($pedido['total_pedido'], 0, ',', '.') ?></strong></td>
                                <td>
                                    <?php if ($saldo_pendiente > 0): ?>
                                        <span class="badge naranja">Por Pagar: $<?= number_format($saldo_pendiente, 0, ',', '.') ?></span>
                                    <?php else: ?>
                                        <span class="badge verde">Pagado Totalmente</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <a href="/unideportes-system/controllers/procesar_entrega_controller.php?id=<?= $pedido['id'] ?>" 
                                       class="btn-action btn-active" 
                                       style="font-size: 0.88rem; font-weight: 600; padding: 8px 14px; gap: 6px; text-decoration: none;"
                                       onclick="return confirm('¿Confirmas que deseas registrar el recaudo final y marcar este pedido como ENTREGADO?');">
                                        📦 Entregar y Liquidar
                                    </a>
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