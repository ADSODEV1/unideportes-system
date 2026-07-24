<?php
// views/seguimiento_entregas.php
require_once __DIR__ . '/../config/bootstrap.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_login(['admin', 'colaborador', 'vendedor']);
$pdo = app();

try {
    // Obtener todas las ventas con estado "En Camino"
    $sql = "SELECT v.id, v.ticket_numero, v.fecha_venta, v.total_venta, 
                   v.direccion_entrega, v.barrio_entrega, v.ciudad_entrega,
                   v.observaciones_entrega, v.tipo_entrega,
                   c.nombre_completo AS cliente_nombre, c.telefono AS cliente_telefono,
                   u.username AS vendedor_nombre
            FROM ventas v
            INNER JOIN clientes c ON v.cliente_id = c.id
            INNER JOIN usuarios u ON v.vendedor_id = u.id
            WHERE v.estado = 'En Camino'
            ORDER BY v.fecha_venta ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $entregas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $entregas = [];
    $error_msg = "Error al cargar entregas: " . $e->getMessage();
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . "/sidebar_control.php"); ?>
    
    <main class="main-content-panel">
        <div class="page-header">
            <div>
                <h1> Seguimiento de Entregas</h1>
                <p>Gestiona los domicilios pendientes de entrega</p>
            </div>
        </div>

        <?php if (isset($error_msg)): ?>
            <div class="alert-error"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'entregado'): ?>
            <div class="alert-success">✅ Entrega marcada como completada exitosamente.</div>
        <?php endif; ?>

        <?php if (empty($entregas)): ?>
            <div class="alert-success" style="text-align: center; padding: 40px;">
                <h3>🎉 No hay entregas pendientes</h3>
                <p>Todos los domicilios han sido entregados</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Dirección</th>
                            <th>Total</th>
                            <th>Vendedor</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entregas as $entrega): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($entrega['ticket_numero']) ?></strong>
                                </td>
                                <td>
                                    <?= date('d/m/Y H:i', strtotime($entrega['fecha_venta'])) ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($entrega['cliente_nombre']) ?></strong>
                                    <?php if (!empty($entrega['cliente_telefono'])): ?>
                                        <br>
                                        <small>📞 <?= htmlspecialchars($entrega['cliente_telefono']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($entrega['direccion_entrega']) ?>
                                    <?php if (!empty($entrega['barrio_entrega'])): ?>
                                        <br>
                                        <small><?= htmlspecialchars($entrega['barrio_entrega']) ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($entrega['ciudad_entrega'])): ?>
                                        <br>
                                        <small><?= htmlspecialchars($entrega['ciudad_entrega']) ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($entrega['observaciones_entrega'])): ?>
                                        <br>
                                        <em style="color: #64748b;"><?= htmlspecialchars($entrega['observaciones_entrega']) ?></em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong>$<?= number_format($entrega['total_venta'], 0, ',', '.') ?></strong>
                                </td>
                                <td>
                                    <?= htmlspecialchars($entrega['vendedor_nombre']) ?>
                                </td>
                                <td class="text-center">
                                    <a href="../controllers/marcar_entrega.php?id=<?= $entrega['id'] ?>" 
                                       class="btn-success btn-small"
                                       onclick="return confirm('¿Confirmas que esta entrega fue completada?');">
                                        ✅ Marcar Entregado
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>

<style>
/* ============================================
SEGUIMIENTO DE ENTREGAS - ESTILOS
============================================ */
.page-header {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    margin-bottom: 25px;
}

.page-header h1 {
    color: #1e293b;
    font-size: 1.6rem;
    font-weight: 700;
    margin: 0 0 5px 0;
}

.page-header p {
    color: #64748b;
    margin: 0;
    font-size: 0.95rem;
}

.alert-success {
    padding: 12px 16px;
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 500;
}

.alert-error {
    padding: 12px 16px;
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 500;
}

.table-container {
    background: white;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: #f1f5f9;
    border-bottom: 2px solid #e2e8f0;
}

.data-table th {
    padding: 14px;
    text-align: left;
    color: #475569;
    font-weight: 600;
    font-size: 0.9rem;
}

.data-table td {
    padding: 14px;
    border-bottom: 1px solid #e2e8f0;
    color: #334155;
    vertical-align: top;
}

.data-table tbody tr:hover {
    background: #f8fafc;
}

.text-center {
    text-align: center;
}

.btn-success {
    padding: 9px 18px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.95rem;
    text-decoration: none;
    display: inline-block;
    transition: background 0.2s;
}

.btn-success:hover {
    background: #059669;
}

.btn-small {
    padding: 6px 12px;
    font-size: 0.85rem;
}

@media (max-width: 768px) {
    .data-table {
        font-size: 0.85rem;
    }
    
    .data-table th,
    .data-table td {
        padding: 8px;
    }
}
</style>

<?php include(__DIR__ . "/footer.php"); ?>