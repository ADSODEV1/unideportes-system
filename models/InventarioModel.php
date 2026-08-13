<?php
// models/InventarioModel.php

function obtenerColumnaStockMinimo(PDO $conn): ?string
{
    static $resolvedColumn = '__PENDING__';

    if ($resolvedColumn !== '__PENDING__') {
        return $resolvedColumn === '__NONE__' ? null : $resolvedColumn;
    }

    $candidatas = ['stock_minimo', 'stock_min', 'minimo_stock', 'stock_minimo_alerta'];
    $sql = "SELECT 1
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'productos'
              AND COLUMN_NAME = :col
            LIMIT 1";
    $stmt = $conn->prepare($sql);

    foreach ($candidatas as $columna) {
        $stmt->execute([':col' => $columna]);
        if ($stmt->fetchColumn() !== false) {
            $resolvedColumn = $columna;
            return $resolvedColumn;
        }
    }

    $resolvedColumn = '__NONE__';
    return null;
}

function obtenerExpresionesInventario(PDO $conn): array
{
    $colStockMin = obtenerColumnaStockMinimo($conn);
    $colStockMinEscaped = $colStockMin !== null ? str_replace('`', '``', $colStockMin) : null;
    $stockMinExpr = $colStockMin !== null ? "COALESCE(p.`{$colStockMinEscaped}`, 5)" : "5";
    $margenExpr = "GREATEST(1, CEIL(({$stockMinExpr}) * 0.3))";
    $limiteBajoExpr = "({$stockMinExpr} + {$margenExpr})";
    $ultimoPedidoExpr = "SELECT DISTINCT dv.producto_id
                         FROM detalle_venta dv
                         INNER JOIN ventas v ON v.id = dv.venta_id
                         WHERE v.id = (
                             SELECT v2.id
                             FROM ventas v2
                             ORDER BY v2.fecha_venta DESC, v2.id DESC
                             LIMIT 1
                         )";

    return [
        'stock_min' => $stockMinExpr,
        'limite_bajo' => $limiteBajoExpr,
        'ultimo_pedido_productos' => $ultimoPedidoExpr,
    ];
}

function aplicarFiltrosInventario(string $baseSql, string $search, string $alerta, array $expr): array
{
    $sql = $baseSql . " WHERE p.estado = 'activo'";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (p.nombre LIKE :search1 OR p.referencia LIKE :search2)";
        $params[':search1'] = "%{$search}%";
        $params[':search2'] = "%{$search}%";
    }

    if ($alerta === 'critico') {
        $sql .= " AND p.stock < {$expr['stock_min']}";
    } elseif ($alerta === 'bajo') {
        $sql .= " AND p.stock >= {$expr['stock_min']} AND p.stock <= {$expr['limite_bajo']}";
    } elseif ($alerta === 'optimo') {
        $sql .= " AND p.stock > {$expr['limite_bajo']}";
    } elseif ($alerta === 'nuevo_pedido') {
        $sql .= " AND p.id IN ({$expr['ultimo_pedido_productos']})";
    }

    return [$sql, $params];
}

function obtenerInventarioPaginado(PDO $conn, string $search, string $alerta, int $limit, int $offset): array
{
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $expr = obtenerExpresionesInventario($conn);

    $baseSql = "SELECT p.id, p.nombre, p.referencia, p.categoria, p.color, p.material, p.talla, p.stock,
                       {$expr['stock_min']} AS stock_minimo,
                       {$expr['limite_bajo']} AS limite_stock_bajo,
                       p.precio,
                       CASE WHEN p.id IN ({$expr['ultimo_pedido_productos']}) THEN 1 ELSE 0 END AS es_nuevo_pedido
                FROM productos p";

    [$sql, $params] = aplicarFiltrosInventario($baseSql, $search, $alerta, $expr);
    $sql .= " ORDER BY p.nombre ASC LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($sql);

    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value, PDO::PARAM_STR);
    }

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function contarInventarioFiltrado(PDO $conn, string $search, string $alerta): int
{
    $expr = obtenerExpresionesInventario($conn);
    $baseSql = "SELECT COUNT(*) FROM productos p";
    [$sql, $params] = aplicarFiltrosInventario($baseSql, $search, $alerta, $expr);
    $stmt = $conn->prepare($sql);

    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value, PDO::PARAM_STR);
    }

    $stmt->execute();
    return (int)$stmt->fetchColumn();
}

function obtenerResumenAlertasInventario(PDO $conn, string $search): array
{
    $expr = obtenerExpresionesInventario($conn);
    $sql = "SELECT
                SUM(CASE WHEN p.stock < {$expr['stock_min']} THEN 1 ELSE 0 END) AS critico,
                SUM(CASE WHEN p.stock >= {$expr['stock_min']} AND p.stock <= {$expr['limite_bajo']} THEN 1 ELSE 0 END) AS bajo,
                SUM(CASE WHEN p.stock > {$expr['limite_bajo']} THEN 1 ELSE 0 END) AS optimo,
                SUM(CASE WHEN p.id IN ({$expr['ultimo_pedido_productos']}) THEN 1 ELSE 0 END) AS nuevo_pedido
            FROM productos p
            WHERE p.estado = 'activo'";

    $stmt = $conn->prepare($sql . ($search !== '' ? " AND (p.nombre LIKE :search1 OR p.referencia LIKE :search2)" : ""));
    if ($search !== '') {
        $searchTerm = "%{$search}%";
        $stmt->bindValue(':search1', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':search2', $searchTerm, PDO::PARAM_STR);
    }

    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'critico' => (int)($row['critico'] ?? 0),
        'bajo' => (int)($row['bajo'] ?? 0),
        'optimo' => (int)($row['optimo'] ?? 0),
        'nuevo_pedido' => (int)($row['nuevo_pedido'] ?? 0),
    ];
}