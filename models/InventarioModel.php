<?php
// models/InventarioModel.php

/**
 * Retorna el primer nombre de columna existente para stock mínimo.
 */
function obtenerColumnaStockMinimo(PDO $conn): ?string {
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

/**
 * Obtiene los productos del inventario de forma paginado y opcionalmente filtrados.
 *
 * @param PDO $conn Conexión a la base de datos mediante PDO.
 * @param string $search Término de búsqueda (nombre o referencia).
 * @param int $limit Cantidad máxima de registros por página.
 * @param int $offset Punto de inicio de lectura de registros.
 * @return array Arreglo asociativo con los productos encontrados.
 */
function obtenerInventarioPaginado(PDO $conn, string $search, int $limit, int $offset): array {
    // Desactivar emulación de consultas preparadas para corregir tipos de datos
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    $colStockMin = obtenerColumnaStockMinimo($conn);
    $colStockMinEscaped = $colStockMin !== null ? str_replace('`', '``', $colStockMin) : null;
    $selectStockMin = $colStockMin !== null
        ? "COALESCE(`{$colStockMinEscaped}`, 5) AS stock_minimo"
        : "5 AS stock_minimo";

    // Agregamos 'categoria' a la consulta SQL
        $sql = "SELECT id, nombre, referencia, categoria, color, material, talla, stock, {$selectStockMin}, precio 
            FROM productos 
            WHERE estado = 'activo'";
    
    if ($search !== '') {
        $sql .= " AND (nombre LIKE :search1 OR referencia LIKE :search2)";
    }
    
    // Cláusulas de ordenamiento y control de flujo de datos
    $sql .= " ORDER BY nombre ASC LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($sql);
    
    // Vinculamos los términos de búsqueda si existen
    if ($search !== '') {
        $searchTerm = "%$search%";
        $stmt->bindValue(':search1', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':search2', $searchTerm, PDO::PARAM_STR);
    }
    
    // Vinculación estricta de enteros para el sistema de paginación
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Cuenta el total de productos que coinciden con el criterio de búsqueda.
 */
function contarInventarioFiltrado(PDO $conn, string $search): int {
    $sql = "SELECT COUNT(*) FROM productos WHERE estado = 'activo'";
    
    if ($search !== '') {
        $sql .= " AND (nombre LIKE :search1 OR referencia LIKE :search2)";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($search !== '') {
        $searchTerm = "%$search%";
        $stmt->bindValue(':search1', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':search2', $searchTerm, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    
    return (int) $stmt->fetchColumn();
}