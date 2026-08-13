<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/InventarioModel.php';

require_login();
$conn = app();

// 1. CONFIGURACIÓN DE LA PAGINACIÓN ESTRICTA
$productos_por_pagina = 12; 
$pagina_actual = !empty($_GET['page']) ? intval($_GET['page']) : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}
$offset = ($pagina_actual - 1) * $productos_por_pagina;

// 2. CAPTURA DEL TÉRMINO DE BÚSQUEDA
$searchRaw = $_GET['q'] ?? '';
$search = is_string($searchRaw) ? trim($searchRaw) : '';

$filtroAlertaRaw = $_GET['alerta'] ?? '';
if (is_array($filtroAlertaRaw)) {
    $filtroAlertaRaw = reset($filtroAlertaRaw);
}
$filtroAlerta = is_string($filtroAlertaRaw) ? trim($filtroAlertaRaw) : '';
$alertasPermitidas = ['critico', 'bajo', 'optimo', 'nuevo_pedido'];
if (!in_array($filtroAlerta, $alertasPermitidas, true)) {
    $filtroAlerta = '';
}
$alertaEtiquetas = [
    'critico' => 'STOCK CRÍTICO',
    'bajo' => 'STOCK BAJO',
    'optimo' => 'STOCK ÓPTIMO',
    'nuevo_pedido' => 'NUEVO PEDIDO',
];

// 3. CONSULTAS SINCRO CON INVENTARIOMODEL (Usando parámetros enlazados)
$total_productos_filtrados = contarInventarioFiltrado($conn, $search, $filtroAlerta);
$productos = obtenerInventarioPaginado($conn, $search, $filtroAlerta, $productos_por_pagina, $offset);
$resumen_alertas = obtenerResumenAlertasInventario($conn, $search);

// 4. CÁLCULO DE PÁGINAS MAESTRAS
$total_paginas = ceil($total_productos_filtrados / $productos_por_pagina);
if ($total_paginas < 1) {
    $total_paginas = 1;
}
if ($pagina_actual > $total_paginas) {
    $pagina_actual = $total_paginas;
    // Recalculamos el offset si la página actual excede el máximo permitido
    $offset = ($pagina_actual - 1) * $productos_por_pagina;
    $productos = obtenerInventarioPaginado($conn, $search, $filtroAlerta, $productos_por_pagina, $offset);
}

$success = '';
if (!empty($_GET['success']) && $_GET['success'] === 'producto_registrado') {
    $success = 'Producto registrado exitosamente.';
} elseif (!empty($_GET['success']) && $_GET['success'] === 'producto_actualizado') {
    $success = 'Producto actualizado correctamente.';
} elseif (!empty($_GET['success']) && $_GET['success'] === 'venta_registrada') {
    $success = 'Nuevo pedido registrado por venta. Dashboard de inventario actualizado.';
}

include(__DIR__ . "/../views/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . "/../views/sidebar_control.php"); ?>

    <main class="main-content-panel">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="header-carapteristicas" style="margin-bottom: 15px;">
            <h1 style="margin: 0; font-size: 1.8rem; color: #0f172a;">Inventario Unideportes</h1>
            <?php if ($search !== ''): ?>
                <p style="color: #64748b; font-size: 0.9rem; margin: 4px 0 0 0;">Resultados para: "<strong><?= htmlspecialchars($search) ?></strong>" 
                (<?= $total_productos_filtrados ?> encontrados)</p>
            <?php else: ?>
                <p style="color: #64748b; font-size: 0.9rem; margin: 4px 0 0 0;">Mostrando listado de existencias físicas en bodega. 
                    (<strong>Total catálogo: <?= $total_productos_filtrados ?> artículos</strong>)</p>
            <?php endif; ?>
            <?php if ($filtroAlerta !== ''): ?>
                <p style="color: #1e40af; font-size: 0.88rem; margin: 6px 0 0 0;">
                    Filtro activo: <strong><?= htmlspecialchars($alertaEtiquetas[$filtroAlerta] ?? 'ALERTA') ?></strong>
                    · <a href="inventario.php?<?= http_build_query(['q' => $search]) ?>" style="color:#1d4ed8;">Quitar filtro</a>
                </p>
            <?php endif; ?>
        </div>

        <div class="search-wrapper" style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
            <form method="GET" action="inventario.php" style="display: flex; gap: 10px; width: 100%;">
                <div style="position: relative; flex-grow: 1;">
                    <input type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por nombre de prenda o código de referencia..." 
                    style="width: 100%; padding: 12px 40px 12px 15px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; outline: none; box-sizing: 
                    border-box;" autocomplete="off">
                    <?php if ($search !== '' || $filtroAlerta !== ''): ?>
                        <a href="inventario.php" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); text-decoration: none; color: 
                            #94a3b8; font-weight: bold;" title="Limpiar filtro">❌</a>
                    <?php endif; ?>
                </div>
                <?php if ($filtroAlerta !== ''): ?>
                    <input type="hidden" name="alerta" value="<?= htmlspecialchars($filtroAlerta) ?>">
                <?php endif; ?>
                <button type="submit" style="padding: 12px 25px; background: #1e3a8a; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: 
                pointer; font-size: 0.95rem; display: flex; align-items: center; gap: 5px;">
                    Buscar
                </button>
            </form>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px;">
            <?php
                $baseQuery = ['q' => $search];
                $criticoUrl = 'inventario.php?' . http_build_query(array_merge($baseQuery, ['alerta' => 'critico']));
                $bajoUrl = 'inventario.php?' . http_build_query(array_merge($baseQuery, ['alerta' => 'bajo']));
                $optimoUrl = 'inventario.php?' . http_build_query(array_merge($baseQuery, ['alerta' => 'optimo']));
                $nuevoPedidoUrl = 'inventario.php?' . http_build_query(array_merge($baseQuery, ['alerta' => 'nuevo_pedido']));
            ?>
            <a href="<?= htmlspecialchars($criticoUrl) ?>" style="text-decoration:none; background: #fee2e2; color: #991b1b; border: 1px solid <?= $filtroAlerta === 'critico' ? '#ef4444' : '#fecaca' ?>; padding: 6px 10px; border-radius: 999px; font-size: 0.82rem; font-weight: 700;">
                🔴 Stock Crítico (<?= (int)$resumen_alertas['critico'] ?>): Cantidad &lt; Stock Mínimo
            </a>
            <a href="<?= htmlspecialchars($bajoUrl) ?>" style="text-decoration:none; background: #fef3c7; color: #92400e; border: 1px solid <?= $filtroAlerta === 'bajo' ? '#f59e0b' : '#fde68a' ?>; padding: 6px 10px; border-radius: 999px; font-size: 0.82rem; font-weight: 700;">
                🟡 Stock Bajo (<?= (int)$resumen_alertas['bajo'] ?>): Cantidad cerca del mínimo
            </a>
            <a href="<?= htmlspecialchars($optimoUrl) ?>" style="text-decoration:none; background: #dcfce7; color: #166534; border: 1px solid <?= $filtroAlerta === 'optimo' ? '#22c55e' : '#bbf7d0' ?>; padding: 6px 10px; border-radius: 999px; font-size: 0.82rem; font-weight: 700;">
                🟢 Stock Óptimo (<?= (int)$resumen_alertas['optimo'] ?>): Cantidad &gt; Stock Mínimo
            </a>
            <a href="<?= htmlspecialchars($nuevoPedidoUrl) ?>" style="text-decoration:none; background: #dbeafe; color: #1e40af; border: 1px solid <?= $filtroAlerta === 'nuevo_pedido' ? '#3b82f6' : '#bfdbfe' ?>; padding: 6px 10px; border-radius: 999px; font-size: 0.82rem; font-weight: 700;">
                🔵 Nuevo Pedido (<?= (int)$resumen_alertas['nuevo_pedido'] ?>): Productos de la venta más reciente
            </a>
        </div>

        <table class="tabla-maestra">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoría</th> <th>Talla</th>
                    <th>Estado</th>
                    <th>Precio</th>
                    <th>Características</th>
                </tr>
            </thead>
            <tbody id="tablaProductos">
                <?php if (count($productos) > 0): ?>
                    <?php foreach ($productos as $row): ?>
                        <tr>
                            <td style="text-align: left;">
                                <strong><?= htmlspecialchars($row['nombre']) ?></strong><br>
                                <small style="color: #666;">Ref: <?= htmlspecialchars($row['referencia']) ?></small><br>
                                <?php if (!empty($row['color'])): ?>
                                    <small style="color: #4b5563;">Color: <?= htmlspecialchars($row['color']) ?></small><br>
                                <?php endif; ?>
                                <?php if (!empty($row['material'])): ?>
                                    <small style="color: #4b5563;">Material: <?= htmlspecialchars($row['material']) ?></small>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <span style="background: #f1f5f9; color: #475569; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: 500; border: 
                                1px solid #e2e8f0;">
                                    <?= htmlspecialchars($row['categoria'] ?? 'Sin Línea') ?>
                                </span>
                            </td>

                            <td><span class="talla-badge"><?= htmlspecialchars($row['talla']) ?></span></td>
                            <td>
                                <?php 
                                    $stockActual = (int)($row['stock'] ?? 0);
                                    $stockMinimo = max(1, (int)($row['stock_minimo'] ?? 5));
                                    $limiteBajo = max($stockMinimo, (int)($row['limite_stock_bajo'] ?? $stockMinimo));

                                    if ($stockActual < $stockMinimo) {
                                        echo "<span class='badge rojo'>🔴 CRITICO ({$stockActual}/Min {$stockMinimo})</span>";
                                    } elseif ($stockActual <= $limiteBajo) {
                                        echo "<span class='badge naranja'>🟡 BAJO ({$stockActual}/Min {$stockMinimo})</span>";
                                    } else {
                                        echo "<span class='badge verde'>🟢 OPTIMO ({$stockActual}/Min {$stockMinimo})</span>";
                                    }

                                    if ((int)($row['es_nuevo_pedido'] ?? 0) === 1) {
                                        echo " <span style='display:inline-block; margin-left: 4px; background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; 
                                        padding:4px 8px; border-radius:999px; font-size:0.78rem; font-weight:700;'>🔵 NUEVO PEDIDO</span>";
                                    }
                                ?>
                            </td>
                            <td>$<?= number_format($row['precio'], 0, ',', '.') ?></td>
                            <td>
                                <a href="detalle_prod.php?id=<?= $row['id'] ?>" class="btn-action view" title="Ver detalle">🔎</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; color: #888; padding: 40px;">No se encontraron productos coincidentes en el catálogo.</td> </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($total_paginas > 1): ?>
            <div class="pagination-container" style="display: flex; justify-content: center; align-items: center; gap: 5px; margin-top: 30px; padding-bottom: 10px;">
                
                <?php if ($pagina_actual > 1): ?>
                    <a href="inventario.php?page=<?= $pagina_actual - 1 ?>&q=<?= urlencode($search) ?>&alerta=<?= urlencode($filtroAlerta) ?>" style="padding: 8px 14px; background: #f1f5f9; color: #334155; 
                    text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 0.85rem; border: 1px solid #e2e8f0;">&laquo; Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="inventario.php?page=<?= $i ?>&q=<?= urlencode($search) ?>&alerta=<?= urlencode($filtroAlerta) ?>" 
                       style="padding: 8px 14px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 0.85rem; border: 1px solid <?= $i === $pagina_actual ? 
                       '#c91a25' : '#e2e8f0' ?>; <?= $i === $pagina_actual ? 'background: #c91a25; color: white;' : 'background: #ffffff; color: #334155;' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($pagina_actual < $total_paginas): ?>
                    <a href="inventario.php?page=<?= $pagina_actual + 1 ?>&q=<?= urlencode($search) ?>&alerta=<?= urlencode($filtroAlerta) ?>" style="padding: 8px 14px; background: #f1f5f9; color: #334155; 
                    text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 0.85rem; border: 1px solid #e2e8f0;">Siguiente &raquo;</a>
                <?php endif; ?>
                
            </div>
        <?php endif; ?>

    </main>
</div>

<?php include(__DIR__ . "/../views/footer.php"); ?>