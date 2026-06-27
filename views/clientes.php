<?php
// views/clientes.php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ClienteModel.php';

require_login(['vendedor', 'colaborador', 'admin']);
$pdo = app();

// 1. CAPTURA Y LIMPIEZA DEL CRITERIO DE BÚSQUEDA
$search = trim(request('search'));
$res_clientes = [];

// 2. CONSULTA OPTIMIZADA (UNA SOLA QUERY EN LUGAR DE N+1)
if (!empty($search)) {
    $sql = "
        SELECT 
            c.id,
            c.codigo_descriptivo,
            c.nombre_completo,
            c.nit_cedula,
            c.telefono,
            c.email,
            c.tipo_cliente,
            c.direccion,
            c.barrio,
            c.ciudad,
            c.referencia_entrega,
            c.estado,
            COALESCE((
                SELECT COUNT(*) 
                FROM pedidos 
                WHERE cliente_id = c.id AND estado = 'Terminado'
            ), 0) as pedidos_listos,
            COALESCE((
                SELECT SUM(saldo_pendiente) 
                FROM ventas 
                WHERE cliente_id = c.id 
                AND tipo_venta = 'mayorista' 
                AND saldo_pendiente > 0
            ), 0) as saldo_mayorista
        FROM clientes c
        WHERE c.nombre_completo LIKE :search 
           OR c.nit_cedula LIKE :search
        ORDER BY c.nombre_completo ASC
        LIMIT 50
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['search' => "%{$search}%"]);
    $res_clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_resultados = count($res_clientes);
    $es_limite = ($total_resultados >= 50);
}

// 3. MENSAJES DE RETROALIMENTACIÓN
$msg = '';
$error = '';

if ($msj = request('msj')) {
    $msg = match($msj) {
        'cliente_creado' => '✅ Cliente creado exitosamente.',
        'cliente_eliminado' => '✅ Cliente eliminado correctamente.',
        'estado_actualizado' => '✅ Estado del cliente actualizado.',
        default => ''
    };
}

if ($err = request('error')) {
    $error = match($err) {
        'cliente_tiene_pedidos' => '⚠️ No se puede eliminar: tiene pedidos asociados.',
        'id_invalido' => '⚠️ ID de cliente inválido.',
        default => ''
    };
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>
    
    <main class="main-content-panel">
        
        <!-- ENCABEZADO -->
        <div class="page-header">
            <div>
                <h1>Gestión de Clientes</h1>
                <p>Busca y administra la información de los clientes.</p>
            </div>
            <a href="nuevo_cliente.php" class="btn-primary">
                + Nuevo Cliente
            </a>
        </div>

        <!-- ALERTAS -->
        <?php if (!empty($msg)): ?>
            <div class="alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- BARRA DE BÚSQUEDA -->
        <form method="GET" action="clientes.php" class="search-form">
            <div class="search-input-wrapper">
                <input type="text" 
                       name="search" 
                       value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Buscar por nombre, NIT o cédula..." 
                       class="search-input" 
                       required>
                <?php if ($search !== ''): ?>
                    <a href="clientes.php" class="search-clear" title="Limpiar filtro">❌</a>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn-primary">🔍 Buscar</button>
        </form>

        <!-- CONTENIDO PRINCIPAL -->
        <?php if (empty($search)): ?>
            <!-- Estado inicial: Sin búsqueda -->
            <div class="empty-state">
                <span class="empty-icon">🔍</span>
                <h2>Busca un cliente</h2>
                <p>Introduce el nombre, NIT o cédula en la barra de búsqueda para consultar la información del cliente.</p>
                <a href="nuevo_cliente.php" class="btn-secondary">
                    + Registrar Nuevo Cliente
                </a>
            </div>
        <?php else: ?>
            
            <!-- Alerta de límite de resultados -->
            <?php if ($es_limite): ?>
                <div class="alert-warning">
                    <strong>⚠️ Búsqueda limitada:</strong> Mostrando los primeros 50 resultados. 
                    Refina tu búsqueda para ver resultados más específicos.
                </div>
            <?php endif; ?>

            <!-- Tabla de resultados -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>NIT / Cédula</th>
                            <th>Teléfono</th>
                            <th>Ubicación</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                <tbody>
                    <?php if (count($res_clientes) > 0): ?>
                        <?php foreach ($res_clientes as $cli): 
                            // Calcular días desde última compra
                            $stmtUltimaCompra = $pdo->prepare("
                                SELECT MAX(fecha_venta) as ultima 
                                FROM ventas 
                                WHERE cliente_id = ?
                            ");
                            $stmtUltimaCompra->execute([$cli['id']]);
                            $ultimaCompra = $stmtUltimaCompra->fetchColumn();
                            
                            $diasSinComprar = 0;
                            $esInactivoPorTiempo = false;
                            if ($ultimaCompra) {
                                $diasSinComprar = (int)((time() - strtotime($ultimaCompra)) / 86400);
                                $esInactivoPorTiempo = ($diasSinComprar > 365); // Más de 1 año
                            } else {
                                $diasSinComprar = -1; // Nunca ha comprado
                            }
                        ?>
                            <tr class="<?= $esInactivoPorTiempo ? 'fila-inactiva' : '' ?>">
                                <td>
                                    <span class="text-small">
                                        <?= htmlspecialchars($cli['codigo_descriptivo'] ?? 'S/C') ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($cli['nombre_completo']) ?></strong>
                                    <?php if ($esInactivoPorTiempo): ?>
                                        <br><span class="badge badge-warning" style="font-size: 0.7rem;">
                                            ⚠️ <?= $diasSinComprar ?> días sin comprar
                                        </span>
                                    <?php elseif ($diasSinComprar === -1): ?>
                                        <br><span class="badge badge-info" style="font-size: 0.7rem;">
                                            🆕 Nunca ha comprado
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($cli['nit_cedula']) ?></td>
                                <td>
                                    <span class="text-small">
                                        <?= htmlspecialchars($cli['telefono'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="text-small">
                                        <?= htmlspecialchars($cli['ciudad'] ?? 'Sogamoso') ?>
                                        <?php if (!empty($cli['barrio'])): ?>
                                            <br><?= htmlspecialchars($cli['barrio']) ?>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= ($cli['tipo_cliente'] === 'Individual' ? 'info' : 'success') ?>">
                                        <?= htmlspecialchars($cli['tipo_cliente']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= (($cli['estado'] ?? 'activo') === 'activo' ? 'success' : 'danger') ?>">
                                        <?= ucfirst($cli['estado'] ?? 'activo') ?>
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    <a href="editar_cliente.php?id=<?= $cli['id'] ?>" 
                                    class="btn-action" 
                                    title="Editar">✏️</a>
                                    
                                    <?php if ($cli['pedidos_listos'] > 0): ?>
                                        <a href="mis_pedidos.php?buscar=<?= urlencode($cli['nombre_completo']) ?>" 
                                        class="badge badge-warning" 
                                        title="<?= $cli['pedidos_listos'] ?> pedido(s) listo(s)">
                                            📦 <?= $cli['pedidos_listos'] ?>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($cli['saldo_mayorista'] > 0): ?>
                                        <a href="ventas_mayoristas_pendientes.php?buscar=<?= urlencode($cli['nombre_completo']) ?>" 
                                        class="badge badge-danger" 
                                        title="Saldo: $<?= number_format($cli['saldo_mayorista'], 0, ',', '.') ?>">
                                            $<?= number_format($cli['saldo_mayorista']/1000, 0, ',', '.') ?>k
                                        </a>
                                    <?php endif; ?>
                                    
                                    <!-- ✅ SOLO EL ADMIN PUEDE ACTIVAR/DESACTIVAR -->
                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                        <?php if (($cli['estado'] ?? 'activo') === 'activo'): ?>
                                            <a href="../controllers/clientes.php?action=toggle_status&id=<?= $cli['id'] ?>&estado=inactivo&search=<?= urlencode($search) ?>" 
                                            class="btn-action" 
                                            title="Desactivar cliente"
                                            onclick="return confirm('¿Estás seguro de desactivar este cliente? No podrá realizar nuevas compras.')">⏸️</a>
                                        <?php else: ?>
                                            <a href="../controllers/clientes.php?action=toggle_status&id=<?= $cli['id'] ?>&estado=activo&search=<?= urlencode($search) ?>" 
                                            class="btn-action" 
                                            title="Activar cliente"
                                            onclick="return confirm('¿Reactivar este cliente?')">▶️</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="no-results">
                                <span class="empty-icon">⚠️</span>
                                <p>No se encontraron clientes con: <strong>"<?= htmlspecialchars($search) ?>"</strong></p>
                                <a href="nuevo_cliente.php" class="btn-secondary">Registrar Nuevo Cliente</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                </table>
            </div>
            
            <div class="form-actions">
                <a href="clientes.php" class="btn-secondary">Limpiar Búsqueda</a>
                <a href="nuevo_cliente.php" class="btn-primary">+ Nuevo Cliente</a>
            </div>
        <?php endif; ?>
        
    </main>
</div>

<style>
/* ============================================
   GESTIÓN DE CLIENTES - ESTILOS SIMPLIFICADOS
   ============================================ */

/* Encabezado de página */
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

.alert-warning {
    padding: 12px 16px;
    background: #fef3c7;
    color: #92400e;
    border-left: 4px solid #f59e0b;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 500;
}

/* Barra de búsqueda */
.search-form {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
    background: #f8fafc;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.search-input-wrapper {
    position: relative;
    flex-grow: 1;
}

.search-input {
    width: 100%;
    padding: 14px 45px 14px 18px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 1rem;
    box-sizing: border-box;
    transition: all 0.2s;
}

.search-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
}

.search-clear {
    position: absolute;
    right: 18px;
    top: 50%;
    transform: translateY(-50%);
    text-decoration: none;
    color: #94a3b8;
    font-weight: bold;
    font-size: 1.2rem;
    transition: color 0.2s;
}

.search-clear:hover {
    color: #64748b;
}

/* Estado vacío */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.empty-icon {
    font-size: 3.5rem;
    display: block;
    margin-bottom: 15px;
}

.empty-state h2 {
    color: #1e293b;
    margin-bottom: 8px;
    font-size: 1.4rem;
}

.empty-state p {
    color: #64748b;
    max-width: 500px;
    margin: 0 auto 20px;
    line-height: 1.5;
}

/* Tabla de datos */
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

.text-small {
    font-size: 0.85rem;
    color: #64748b;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
}

.badge-info {
    background: #dbeafe;
    color: #1e40af;
}

.badge-success {
    background: #d1fae5;
    color: #065f46;
}

.badge-warning {
    background: #fef3c7;
    color: #92400e;
}

.badge-danger {
    background: #fee2e2;
    color: #991b1b;
}

/* Celda de acciones */
.actions-cell {
    text-align: center;
    display: flex;
    gap: 6px;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-action {
    font-size: 1.1rem;
    text-decoration: none;
    padding: 4px 6px;
    border-radius: 4px;
    transition: background 0.2s;
}

.btn-action:hover {
    background: #f1f5f9;
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

/* Botones de acción */
.form-actions {
    margin-top: 20px;
    text-align: right;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

/* Botones */
.btn-primary {
    padding: 10px 20px;
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
    padding: 10px 20px;
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
    
    .actions-cell {
        flex-direction: column;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions > * {
        width: 100%;
        text-align: center;
    }
     /* Fila de cliente inactivo por tiempo */
    .fila-inactiva {
        background: #fef3c7 !important;
        opacity: 0.85;
    }

    .fila-inactiva:hover {
        background: #fde68a !important;
    }
}
</style>

<?php include(__DIR__ . "/footer.php"); ?>