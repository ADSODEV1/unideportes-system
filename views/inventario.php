<?php
// views/inventario.php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/InventarioModel.php';

require_login(['vendedor', 'colaborador', 'admin']);
$conn = app();

// 1. CONFIGURACIÓN DE LA PAGINACIÓN
$productos_por_pagina = 12;
$pagina_actual = !empty($_GET['page']) ? intval($_GET['page']) : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}
$offset = ($pagina_actual - 1) * $productos_por_pagina;

// 2. CAPTURA DEL TÉRMINO DE BÚSQUEDA
$search = trim($_GET['q'] ?? '');

// 3. CONSULTAS SINCRONIZADAS CON INVENTARIOMODEL
$total_productos_filtrados = contarInventarioFiltrado($conn, $search);
$productos = obtenerInventarioPaginado($conn, $search, $productos_por_pagina, $offset);

// 4. CÁLCULO DE PÁGINAS
$total_paginas = ceil($total_productos_filtrados / $productos_por_pagina);
if ($total_paginas < 1) {
    $total_paginas = 1;
}
if ($pagina_actual > $total_paginas) {
    $pagina_actual = $total_paginas;
    $offset = ($pagina_actual - 1) * $productos_por_pagina;
    $productos = obtenerInventarioPaginado($conn, $search, $productos_por_pagina, $offset);
}

// 5. MENSAJES DE ÉXITO
$success = '';
if (!empty($_GET['success'])) {
    if ($_GET['success'] === 'producto_registrado') {
        $success = '✅ Producto registrado exitosamente.';
    } elseif ($_GET['success'] === 'producto_actualizado') {
        $success = '✅ Producto actualizado correctamente.';
    }
}

include(__DIR__ . "/../views/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . "/../views/sidebar_control.php"); ?>

    <main class="main-content-panel">
        
        <!-- ENCABEZADO -->
        <div class="page-header">
            <div>
                <h1>Inventario Unideportes</h1>
                <?php if ($search !== ''): ?>
                    <p>Resultados para: "<strong><?= htmlspecialchars($search) ?></strong>" (<?= $total_productos_filtrados ?> encontrados)</p>
                <?php else: ?>
                    <p>Listado de existencias físicas en bodega. <strong>Total catálogo: <?= $total_productos_filtrados ?> artículos</strong></p>
                <?php endif; ?>
            </div>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="registrar_productos.php" class="btn-primary">
                    + Nuevo Producto
                </a>
            <?php endif; ?>
        </div>

        <!-- ALERTA DE ÉXITO -->
        <?php if ($success): ?>
            <div class="alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- BARRA DE BÚSQUEDA -->
        <form method="GET" action="inventario.php" class="search-form">
            <div class="search-input-wrapper">
                <input type="search" 
                       name="q" 
                       value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Buscar por nombre de prenda o código de referencia..." 
                       class="search-input" 
                       autocomplete="off">
                <?php if ($search !== ''): ?>
                    <a href="inventario.php" class="search-clear" title="Limpiar filtro">❌</a>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn-primary">🔍 Buscar</button>
        </form>

        <!-- TABLA DE PRODUCTOS -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Talla</th>
                        <th>Estado</th>
                        <th>Precio</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($productos) > 0): ?>
                        <?php foreach ($productos as $row): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($row['nombre']) ?></strong><br>
                                    <span class="text-small">Ref: <?= htmlspecialchars($row['referencia']) ?></span>
                                    <?php if (!empty($row['color'])): ?>
                                        <br><span class="text-small">Color: <?= htmlspecialchars($row['color']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($row['material'])): ?>
                                        <br><span class="text-small">Material: <?= htmlspecialchars($row['material']) ?></span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <span class="badge badge-info">
                                        <?= htmlspecialchars($row['categoria'] ?? 'Sin Línea') ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="talla-badge"><?= htmlspecialchars($row['talla']) ?></span>
                                </td>
                                
                                <td>
                                    <?php 
                                        $s = $row['stock'];
                                        if ($s == 0) {
                                            echo '<span class="badge badge-danger">AGOTADO</span>';
                                        } elseif ($s <= 5) {
                                            echo '<span class="badge badge-warning">BAJO (' . $s . ')</span>';
                                        } else {
                                            echo '<span class="badge badge-success">STOCK (' . $s . ')</span>';
                                        }
                                    ?>
                                </td>
                                
                                <td>
                                    <strong>$<?= number_format($row['precio'], 0, ',', '.') ?></strong>
                                </td>
                                
                                <td class="text-center">
                                    <a href="detalle_prod.php?id=<?= $row['id'] ?>" class="btn-action" title="Ver detalle">🔎</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-results">
                                <span class="empty-icon">📦</span>
                                <p>No se encontraron productos coincidentes en el catálogo.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINACIÓN -->
        <?php if ($total_paginas > 1): ?>
            <div class="pagination">
                <?php if ($pagina_actual > 1): ?>
                    <a href="inventario.php?page=<?= $pagina_actual - 1 ?>&q=<?= urlencode($search) ?>" class="pagination-link">
                        &laquo; Anterior
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="inventario.php?page=<?= $i ?>&q=<?= urlencode($search) ?>" 
                       class="pagination-link <?= $i === $pagina_actual ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($pagina_actual < $total_paginas): ?>
                    <a href="inventario.php?page=<?= $pagina_actual + 1 ?>&q=<?= urlencode($search) ?>" class="pagination-link">
                        Siguiente &raquo;
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

<style>
/* --- INVENTARIO - ESTILOS SIMPLIFICADOS --- */

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

/* Alerta de éxito */
.alert-success {
    padding: 12px 16px;
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 500;
}

/* Barra de búsqueda */
.search-form {
    display: flex;
    gap: 14px;
    margin-bottom: 25px;
    background: #f8fafc;
    padding: 20px;
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

/* Botón de búsqueda más grande */
.search-form .btn-primary {
    padding: 14px 28px;  /* ← MÁS GRANDE */
    font-size: 1rem;  /* ← MÁS GRANDE */
    white-space: nowrap;
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
}

.badge-info {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #bfdbfe;
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

/* Badge de talla */
.talla-badge {
    display: inline-block;
    padding: 4px 10px;
    background: #f1f5f9;
    color: #334155;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 600;
    border: 1px solid #e2e8f0;
}

/* Botón de acción */
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

/* Paginación */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 5px;
    margin-top: 30px;
    padding-bottom: 10px;
    flex-wrap: wrap;
}

.pagination-link {
    padding: 8px 14px;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.85rem;
    border: 1px solid #e2e8f0;
    background: white;
    color: #334155;
    transition: all 0.2s;
}

.pagination-link:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}

.pagination-link.active {
    background: #2563eb;
    color: white;
    border-color: #2563eb;
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
    
    .pagination {
        gap: 3px;
    }
    
    .pagination-link {
        padding: 6px 10px;
        font-size: 0.8rem;
    }
}
</style>

<?php include(__DIR__ . "/../views/footer.php"); ?>