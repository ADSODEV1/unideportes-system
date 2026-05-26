<?php
// models/InventarioModel.php

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

    // Agregamos 'categoria' a la consulta SQL
    $sql = "SELECT id, nombre, referencia, categoria, talla, stock, precio 
            FROM productos 
            WHERE 1=1";
    
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
    $sql = "SELECT COUNT(*) FROM productos WHERE 1=1";
    
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