<?php
// views/ventas_mayoristas_pendientes.php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = app();
require_login(['admin', 'colaborador', 'vendedor']);

$busqueda = trim($_GET['buscar'] ?? '');

try {
    $sql = "SELECT v.id, 
                   v.ticket_numero,
                   v.fecha_entrega,
                   v.total_venta,
                   v.abono AS abono_inicial,
                   v.saldo_pendiente AS saldo_inicial,
                   v.estado,
                   c.nombre_completo AS cliente_nombre,
                   c.nit_cedula AS cliente_nit,
                   c.telefono AS cliente_telefono,
                   IFNULL((SELECT SUM(pv.monto) FROM pagos_venta pv WHERE pv.venta_id = v.id), 0) AS pagos_adicionales
            FROM ventas v
            INNER JOIN clientes c ON v.cliente_id = c.id
            WHERE v.tipo_venta = 'mayorista' 
              AND v.estado = 'Pendiente'";

    if ($busqueda !== '') {
        $sql .= " AND (c.nombre_completo LIKE :busqueda OR c.nit_cedula LIKE :busqueda OR v.ticket_numero LIKE :busqueda)";
    }

    $sql .= " ORDER BY v.fecha_entrega ASC";
    
    $stmt = $pdo->prepare($sql);
    
    if ($busqueda !== '') {
        $stmt->bindValue(':busqueda', "%{$busqueda}%", PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $ventas = [];
    $error_msg = "Error al cargar ventas mayoristas: " . $e->getMessage();
}

$status = $_GET['status'] ?? null;
include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>
    
    <main class="main-content-panel">
        
        <!-- ENCABEZADO -->
        <div class="page-header">
            <div>
                <h1>Ventas Mayoristas Pendientes</h1>
                <p>Gestiona las entregas y cobra los saldos pendientes de ventas al por mayor.</p>
            </div>
            <a href="venta_mayorista.php" class="btn-primary">
                + Nueva Venta Mayorista
            </a>
        </div>

        <!-- ALERTAS -->
        <?php if ($status === 'pago_registrado'): ?>
            <div class="alert-success">✅ Abono registrado exitosamente.</div>
        <?php elseif ($status === 'entregado'): ?>
            <div class="alert-success">✅ Venta marcada como entregada.</div>
        <?php elseif ($status === 'error_saldo'): ?>
            <div class="alert-error">⚠️ No se puede entregar: aún hay saldo pendiente.</div>
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
            <div class="alert-error"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <!-- BARRA DE BÚSQUEDA -->
        <form method="GET" action="" class="search-form">
            <input type="text" name="buscar" value="<?= htmlspecialchars($busqueda) ?>" 
                   placeholder="Buscar por cliente, NIT o ticket..." 
                   class="search-input">
            <button type="submit" class="btn-primary">🔍 Buscar</button>
            <?php if ($busqueda !== ''): ?>
                <a href="ventas_mayoristas_pendientes.php" class="btn-secondary">Limpiar</a>
            <?php endif; ?>
        </form>

        <!-- TABLA DE VENTAS -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Cliente</th>
                        <th>Fecha Entrega</th>
                        <th>Total Venta</th>
                        <th>Estado de Cartera</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ventas)): ?>
                        <tr>
                            <td colspan="6" class="no-results">
                                <span class="empty-icon">📦</span>
                                <p>No hay ventas mayoristas pendientes.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ventas as $venta): 
                            $total_abonado = $venta['abono_inicial'] + $venta['pagos_adicionales'];
                            $saldo_real = $venta['total_venta'] - $total_abonado;
                        ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($venta['ticket_numero']) ?></strong><br>
                                    <a href="ticket_mayorista.php?id=<?= $venta['id'] ?>" target="_blank" class="link-small">
                                        Ver Ticket
                                    </a>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($venta['cliente_nombre']) ?></strong><br>
                                    <span class="text-small">
                                        NIT: <?= htmlspecialchars($venta['cliente_nit']) ?><br>
                                        Tel: <?= htmlspecialchars($venta['cliente_telefono'] ?: 'N/A') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fecha-entrega">
                                        📅 <?= date('d/m/Y', strtotime($venta['fecha_entrega'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong>$<?= number_format($venta['total_venta'], 0, ',', '.') ?></strong>
                                </td>
                                <td>
                                    <?php if ($saldo_real > 0): ?>
                                        <span class="badge badge-warning">
                                            Abonó: $<?= number_format($total_abonado, 0, ',', '.') ?>
                                        </span>
                                        <span class="badge badge-danger">
                                            Debe: $<?= number_format($saldo_real, 0, ',', '.') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-success">✅ Pagado Totalmente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="actions-vertical">
                                        <?php if ($saldo_real > 0): ?>
                                            <button onclick="abrirModalPago(<?= $venta['id'] ?>, <?= $saldo_real ?>)" 
                                                    class="btn-success btn-small">
                                                💰 Cobrar Abono
                                            </button>
                                        <?php endif; ?>
                                        
                                        <a href="../controllers/marcar_venta_mayorista_entregada.php?id=<?= $venta['id'] ?>" 
                                           class="btn-primary btn-small"
                                           onclick="return confirm('¿Confirmas que el cliente recibió su pedido?');">
                                            📦 Marcar Entregado
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- MODAL PARA REGISTRAR ABONO -->
<div id="modalPago" class="modal-overlay">
    <div class="modal-content">
        <h3 class="modal-title">💰 Registrar Abono Adicional</h3>
        <form id="formPago" method="POST" action="../controllers/registrar_pago_venta_mayorista.php">
            <input type="hidden" name="venta_id" id="pago_venta_id">
            
            <div class="form-group">
                <label for="pago_monto">Monto a abonar:</label>
                <input type="number" name="monto" id="pago_monto" step="0.01" min="0" required 
                       class="form-input">
            </div>
            
            <div class="form-group">
                <label for="pago_metodo">Método de pago:</label>
                <select name="metodo_pago" id="pago_metodo" class="form-input">
                    <option value="Efectivo">Efectivo</option>
                    <option value="Tarjeta">Tarjeta</option>
                    <option value="Transferencia">Transferencia</option>
                </select>
            </div>
            
            <div class="modal-actions">
                <button type="button" onclick="cerrarModalPago()" class="btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn-success">
                    Registrar Pago
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* ============================================
   VENTAS MAYORISTAS PENDIENTES - ESTILOS
   ============================================ */

/* Encabezado */
.page-header {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.page-header h1 {
    color: #1e293b;
    font-size: 1.6rem;
    font-weight: 700;
    margin: 0;
}

.page-header p {
    color: #64748b;
    margin: 5px 0 0 0;
    font-size: 0.95rem;
}

/* Alertas */
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

/* Barra de búsqueda */
.search-form {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
}

.search-input {
    flex: 1;
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 1rem;
}

.search-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Tabla */
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

/* Texto pequeño */
.text-small {
    font-size: 0.85rem;
    color: #64748b;
}

.link-small {
    font-size: 0.85rem;
    color: #2563eb;
    text-decoration: none;
}

.link-small:hover {
    text-decoration: underline;
}

/* Fecha de entrega */
.fecha-entrega {
    color: #dc2626;
    font-weight: bold;
    font-size: 0.95rem;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 4px;
}

.badge-warning {
    background: #fef3c7;
    color: #92400e;
}

.badge-danger {
    background: #fee2e2;
    color: #991b1b;
}

.badge-success {
    background: #d1fae5;
    color: #065f46;
}

/* Acciones verticales */
.actions-vertical {
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: center;
}

/* Botones */
.btn-primary {
    padding: 9px 18px;
    background: #2563eb;
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

.btn-primary:hover {
    background: #1d4ed8;
}

.btn-secondary {
    padding: 9px 18px;
    background: white;
    color: #475569;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.95rem;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
}

.btn-secondary:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
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

/* Sin resultados */
.no-results {
    text-align: center;
    padding: 40px !important;
    color: #94a3b8;
}

.no-results .empty-icon {
    font-size: 2rem;
    margin-bottom: 10px;
    display: block;
}

.no-results p {
    margin: 10px 0;
}

/* ============================================
   MODAL
   ============================================ */

.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: white;
    padding: 25px;
    border-radius: 12px;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.modal-title {
    margin: 0 0 20px 0;
    color: #1e293b;
    font-size: 1.2rem;
    font-weight: 700;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #334155;
    font-size: 0.9rem;
}

.form-input {
    width: 100%;
    padding: 10px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 1rem;
    box-sizing: border-box;
}

.form-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.modal-actions button {
    flex: 1;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        text-align: center;
    }
    
    .search-form {
        flex-direction: column;
    }
    
    .data-table {
        font-size: 0.85rem;
    }
    
    .data-table th,
    .data-table td {
        padding: 8px;
    }
    
    .actions-vertical {
        flex-direction: column;
    }
    
    .actions-vertical > * {
        width: 100%;
    }
}
</style>

<script>
function abrirModalPago(ventaId, saldo) {
    document.getElementById('pago_venta_id').value = ventaId;
    document.getElementById('pago_monto').value = saldo;
    document.getElementById('pago_monto').max = saldo;
    document.getElementById('modalPago').style.display = 'flex';
}

function cerrarModalPago() {
    document.getElementById('modalPago').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
document.getElementById('modalPago').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalPago();
    }
});
</script>

<?php include(__DIR__ . "/footer.php"); ?>