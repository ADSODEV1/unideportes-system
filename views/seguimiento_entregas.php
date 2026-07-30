<?php
// views/seguimiento_entregas.php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login(['admin', 'colaborador', 'vendedor']);
$pdo = app();

// 1. Capturar y limpiar parámetros de búsqueda
$search = trim($_GET['search'] ?? '');
$mostrar_historial = isset($_GET['mostrar_historial']);

try {
    // 2. Condición base: por defecto solo 'En Camino'
    $conditions = ["v.estado = 'En Camino'"];
    $params = [];

    // Si se marca el checkbox, ampliamos la condición de estado
    if ($mostrar_historial) {
        $conditions[0] = "v.estado IN ('En Camino', 'Entregado')";
    }

    // 3. Si hay búsqueda, agregamos los campos a la consulta (incluyendo dirección y barrio)
    if (!empty($search)) {
        $search_term = "%" . $search . "%";
        $conditions[] = "(
            c.nombre_completo LIKE :search OR 
            c.telefono LIKE :search OR 
            v.ticket_numero LIKE :search OR 
            v.direccion_entrega LIKE :search OR 
            v.barrio_entrega LIKE :search
        )";
        $params[':search'] = $search_term;
    }

    // 4. Ensamblar la consulta final
    $sql = "SELECT v.id, v.ticket_numero, v.estado,
                   v.direccion_entrega, v.barrio_entrega, v.ciudad_entrega,
                   v.observaciones_entrega, v.entregado_por, v.fecha_entrega_real,
                   c.nombre_completo AS cliente_nombre, c.telefono AS cliente_telefono,
                   ent.username AS entregador_nombre
            FROM ventas v
            INNER JOIN clientes c ON v.cliente_id = c.id
            LEFT JOIN usuarios ent ON v.entregado_por = ent.id
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY v.fecha_venta DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $entregas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $entregas = [];
    $error_msg = "Error al cargar entregas: " . $e->getMessage();
    // Descomenta la siguiente línea solo si necesitas depurar un error de SQL
    // $error_msg .= " | SQL: " . $sql; 
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . "/sidebar_control.php"); ?>
    
    <main class="main-content-panel">
        <div class="page-header">
            <div>
                <h1>Seguimiento de Entregas</h1>
                <p>Gestiona los domicilios pendientes de forma rápida y clara.</p>
            </div>
        </div>

        <?php if (isset($error_msg)): ?>
            <div class="alert-error"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'entregado'): ?>
            <div class="alert-success">✅ Entrega marcada como completada exitosamente.</div>
        <?php endif; ?>

        <!-- BARRA DE BÚSQUEDA Y FILTROS -->
        <form method="GET" action="" class="search-filter-bar">
            <div class="search-input-group">
                <span class="search-icon">🔍</span>
                <input type="text" name="search" class="search-input" 
                       placeholder="Buscar por cliente, teléfono, ticket o dirección..." 
                       value="<?= htmlspecialchars($search) ?>" autocomplete="off">
            </div>
            
            <label class="checkbox-label">
                <input type="checkbox" name="mostrar_historial" <?= $mostrar_historial ? 'checked' : '' ?>>
                📦 Mostrar historial (Entregados)
            </label>
            
            <button type="submit" class="btn-search">Buscar</button>
            
            <?php if (!empty($search) || $mostrar_historial): ?>
                <a href="seguimiento_entregas.php" class="btn-clear">✖ Limpiar</a>
            <?php endif; ?>
        </form>

        <?php if (empty($entregas)): ?>
            <div class="alert-success" style="text-align: center; padding: 40px;">
                <h3>🔍 No se encontraron resultados</h3>
                <p><?= !empty($search) ? 'Intenta con otro nombre, dirección, teléfono o número de ticket.' : 'No hay domicilios en camino en este momento.' ?></p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Ticket</th>
                            <th style="width: 180px;">Cliente</th>
                            <th>Dirección de Entrega</th>
                            <th style="width: 110px;">Estado</th>
                            <th style="width: 160px;">Info. Entrega</th>
                            <th style="width: 130px;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entregas as $entrega): ?>
                            <tr>
                                <td data-label="Ticket">
                                    <strong>#<?= substr(htmlspecialchars($entrega['ticket_numero']), -5) ?></strong>
                                </td>
                                
                                <td data-label="Cliente">
                                    <strong><?= htmlspecialchars($entrega['cliente_nombre']) ?></strong>
                                    <br>
                                    <small style="color: #64748b;">
                                        📞 <?= htmlspecialchars($entrega['cliente_telefono'] ?: 'Sin tel.') ?>
                                    </small>
                                </td>
                                
                                <td data-label="Dirección">
                                    <?= htmlspecialchars($entrega['direccion_entrega']) ?>
                                    <?php if (!empty($entrega['barrio_entrega']) || !empty($entrega['ciudad_entrega'])): ?>
                                        <br>
                                        <small style="color: #64748b;">
                                            📍 <?= htmlspecialchars($entrega['barrio_entrega'] . ($entrega['ciudad_entrega'] ? ', ' . $entrega['ciudad_entrega'] : '')) ?>
                                        </small>
                                    <?php endif; ?>
                                    <?php if (!empty($entrega['observaciones_entrega'])): ?>
                                        <br>
                                        <em style="color: #d97706; font-size: 0.8rem;">⚠️ <?= htmlspecialchars($entrega['observaciones_entrega']) ?></em>
                                    <?php endif; ?>
                                </td>
                                
                                <td data-label="Estado">
                                    <?php if ($entrega['estado'] === 'Entregado'): ?>
                                        <span class="badge badge-success">Entregado</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">En Camino</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td data-label="Info Entrega">
                                    <?php if ($entrega['estado'] === 'Entregado'): ?>
                                        <div style="font-weight: 600; color: #334155; font-size: 0.9rem;">
                                            👤 <?= htmlspecialchars($entrega['entregador_nombre'] ?: $entrega['entregado_por']) ?>
                                        </div>
                                        <small style="color: #64748b;">
                                            🕒 <?= date('d/m H:i', strtotime($entrega['fecha_entrega_real'])) ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 0.85rem;">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td data-label="Acciones" class="text-center">
                                    <?php if ($entrega['estado'] === 'En Camino'): ?>
                                        <a href="../controllers/marcar_entrega.php?id=<?= $entrega['id'] ?>" 
                                           class="btn-success btn-small"
                                           onclick="return confirm('¿Confirmas que esta entrega fue completada?');">
                                            ✅ Entregado
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 0.8rem;">Completado</span>
                                    <?php endif; ?>
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
   ESTILOS BASE
   ============================================ */
.page-header {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    margin-bottom: 25px;
}
.page-header h1 { color: #1e293b; font-size: 1.5rem; font-weight: 700; margin: 0 0 5px 0; }
.page-header p { color: #64748b; margin: 0; font-size: 0.9rem; }

.alert-success { padding: 12px 16px; background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; border-radius: 6px; margin-bottom: 20px; font-weight: 500; }
.alert-error { padding: 12px 16px; background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; border-radius: 6px; margin-bottom: 20px; font-weight: 500; }

/* BARRA DE BÚSQUEDA */
.search-filter-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
    background: white;
    padding: 16px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    margin-bottom: 20px;
}
.search-input-group {
    position: relative;
    flex: 1;
    min-width: 250px;
}
.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
}
.search-input {
    width: 100%;
    padding: 10px 12px 10px 36px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.9rem;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.search-input:focus { 
    border-color: #10b981; 
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); 
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.9rem;
    color: #475569;
    cursor: pointer;
    white-space: nowrap;
}
.checkbox-label input { accent-color: #10b981; width: 16px; height: 16px; cursor: pointer; }

.btn-search {
    padding: 10px 20px;
    background: #1e293b;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.9rem;
    transition: background 0.2s;
}
.btn-search:hover { background: #334155; }

.btn-clear {
    padding: 10px 16px;
    background: #f1f5f9;
    color: #64748b;
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.2s;
}
.btn-clear:hover { background: #e2e8f0; color: #ef4444; }

/* TABLA */
.table-container { background: white; border-radius: 8px; border: 1px solid #e2e8f0; overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table thead { background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
.data-table th { padding: 12px 14px; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
.data-table td { padding: 14px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; font-size: 0.9rem; }
.data-table tbody tr:hover { background: #f8fafc; }

.text-center { text-align: center; }
.text-muted { color: #94a3b8; font-style: italic; }

.badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-warning { background: #fef3c7; color: #92400e; }

.btn-success { padding: 7px 14px; background: #10b981; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 0.8rem; text-decoration: none; display: inline-block; transition: all 0.2s; white-space: nowrap; }
.btn-success:hover { background: #059669; transform: translateY(-1px); }
.btn-small { padding: 6px 12px; font-size: 0.8rem; }

/* ============================================
   RESPONSIVE: MÓVIL
   ============================================ */
@media (max-width: 768px) {
    .container.admin-layout { display: flex !important; flex-direction: column !important; width: 100% !important; padding: 0 !important; }
    .main-content-panel { width: 100% !important; padding: 10px !important; }
    
    .search-filter-bar { flex-direction: column; align-items: stretch; }
    .search-input-group { min-width: 100%; }
    .checkbox-label { justify-content: center; margin: 8px 0; }
    .btn-search, .btn-clear { width: 100%; text-align: center; }

    .table-container { background: transparent !important; border: none !important; overflow-x: visible !important; }
    .data-table, .data-table thead, .data-table tbody, .data-table th, .data-table td, .data-table tr { display: block !important; width: 100% !important; box-sizing: border-box !important; }
    .data-table thead { display: none !important; }
    
    .data-table tr { background: white !important; border: 1px solid #e2e8f0 !important; border-radius: 8px !important; margin-bottom: 16px !important; box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important; padding: 16px !important; }
    
    .data-table td { display: flex !important; flex-direction: row !important; justify-content: space-between !important; align-items: flex-start !important; padding: 8px 0 !important; border-bottom: 1px solid #f1f5f9 !important; text-align: right !important; }
    .data-table td:last-child { border-bottom: none !important; justify-content: center !important; padding-top: 16px !important; background: #f8fafc !important; margin: 0 -16px -16px -16px !important; border-radius: 0 0 8px 8px !important; }
    
    .data-table td::before { content: attr(data-label) !important; font-weight: 700 !important; color: #475569 !important; font-size: 0.75rem !important; text-transform: uppercase !important; text-align: left !important; margin-right: 15px !important; flex-shrink: 0 !important; width: 35% !important; }
    
    .data-table td[data-label="Cliente"], .data-table td[data-label="Dirección"], .data-table td[data-label="Info Entrega"] { flex-direction: column !important; align-items: flex-start !important; }
    .data-table td[data-label="Cliente"]::before, .data-table td[data-label="Dirección"]::before, .data-table td[data-label="Info Entrega"]::before { width: 100% !important; margin-bottom: 6px !important; }
    .data-table td .btn-success { width: 100% !important; padding: 12px !important; font-size: 0.9rem !important; text-align: center !important; }
}
</style>

<?php include(__DIR__ . "/footer.php"); ?>