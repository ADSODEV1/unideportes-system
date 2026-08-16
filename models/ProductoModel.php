<?php
// models/ProductoModel.php

/**
 * Genera un código descriptivo único para el producto
 * Formato: PROD-YYYYMMDDHHMMSS-XXXX
 */
function generarCodigoDescriptivoProducto(): string {
    try {
        return 'PROD-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
    } catch (Exception $e) {
        return 'PROD-' . date('YmdHis') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 4));
    }
}

/**
 * Obtiene el listado completo de productos filtrado o sin filtrar
 * (Ordenado alfabéticamente por nombre)
 */
function obtenerProductos(PDO $pdo, string $search = ''): array {
    if ($search !== '') {
        $like = '%' . $search . '%';
        $stmt = $pdo->prepare(
            "SELECT * FROM productos 
             WHERE nombre LIKE ? OR referencia LIKE ? 
             ORDER BY nombre ASC"
        );
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmt = $pdo->query("SELECT * FROM productos ORDER BY nombre ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Busca un producto específico por su ID único
 */
function obtenerProductoPorId(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare(
        "SELECT * FROM productos WHERE id = ? LIMIT 1"
    );
    $stmt->execute([$id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    return $producto ?: null;
}

/**
 * Busca un producto específico por su referencia (código único)
 */
function obtenerProductoPorReferencia(PDO $pdo, string $referencia): ?array {
    $stmt = $pdo->prepare(
        "SELECT * FROM productos WHERE referencia = ? LIMIT 1"
    );
    $stmt->execute([$referencia]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    return $producto ?: null;
}

/**
 * Inserta un nuevo producto en el catálogo
 */
function crearProducto(PDO $pdo, array $data): bool {
    $codigoDescriptivo = trim((string)($data['codigo_descriptivo'] ?? ''));
    if ($codigoDescriptivo === '') {
        $codigoDescriptivo = generarCodigoDescriptivoProducto();
    }

    $stmt = $pdo->prepare(
        "INSERT INTO productos 
            (codigo_descriptivo, nombre, referencia, categoria, color, material, genero, estado, descripcion, talla, stock, unidad, precio) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    return $stmt->execute([
        $codigoDescriptivo,
        $data['nombre'],
        $data['referencia'],
        $data['categoria'] ?? null,
        $data['color'] ?? null,
        $data['material'] ?? null,
        $data['genero'] ?? null,
        $data['estado'] ?? 'activo',
        $data['descripcion'] ?? null,
        $data['talla'] ?? 'Única',
        $data['stock'] ?? 0,
        $data['unidad'] ?? 'Unidad',
        $data['precio'],
    ]);
}

/**
 * Actualiza las propiedades físicas y económicas de una prenda existente
 */
function actualizarProducto(PDO $pdo, int $id, array $data): bool {
    $stmt = $pdo->prepare(
        "UPDATE productos 
         SET nombre = ?, referencia = ?, categoria = ?, color = ?, material = ?, 
             genero = ?, estado = ?, descripcion = ?, talla = ?, stock = ?, 
             unidad = ?, precio = ? 
         WHERE id = ?"
    );
    return $stmt->execute([
        $data['nombre'],
        $data['referencia'],
        $data['categoria'] ?? null,
        $data['color'] ?? null,
        $data['material'] ?? null,
        $data['genero'] ?? null,
        $data['estado'] ?? 'activo',
        $data['descripcion'] ?? null,
        $data['talla'] ?? 'Única',
        $data['stock'] ?? 0,
        $data['unidad'] ?? 'Unidad',
        $data['precio'],
        $id,
    ]);
}

/**
 * Valida la existencia de una referencia para evitar duplicados en la base de datos
 */
function existeReferenciaProducto(PDO $pdo, string $referencia, int $excludeId = 0): bool {
    if ($excludeId > 0) {
        $stmt = $pdo->prepare(
            "SELECT 1 FROM productos WHERE referencia = ? AND id != ? LIMIT 1"
        );
        $stmt->execute([$referencia, $excludeId]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT 1 FROM productos WHERE referencia = ? LIMIT 1"
        );
        $stmt->execute([$referencia]);
    }
    return (bool) $stmt->fetchColumn();
}

/**
 * 🆕 Aumenta el stock de un producto en la cantidad indicada.
 * Usa "stock = stock + ?" para evitar condiciones de carrera (race conditions).
 *
 * @param PDO $pdo          Conexión a la base de datos
 * @param int $producto_id  ID del producto a actualizar
 * @param int $cantidad     Cantidad a sumar (debe ser > 0)
 * @return bool             True si se actualizó correctamente
 */
function aumentarStockProducto(PDO $pdo, int $producto_id, int $cantidad): bool {
    if ($cantidad <= 0) {
        return false;
    }

    $stmt = $pdo->prepare(
        "UPDATE productos 
         SET stock = stock + ? 
         WHERE id = ? AND estado = 'activo'"
    );

    return $stmt->execute([$cantidad, $producto_id]);
}

/**
 * 🆕 Reduce el stock de un producto en la cantidad indicada.
 * Usa GREATEST(0, ...) para evitar que el stock quede negativo.
 *
 * @param PDO $pdo          Conexión a la base de datos
 * @param int $producto_id  ID del producto a actualizar
 * @param int $cantidad     Cantidad a restar (debe ser > 0)
 * @return bool             True si se actualizó correctamente
 */
function reducirStockProducto(PDO $pdo, int $producto_id, int $cantidad): bool {
    if ($cantidad <= 0) {
        return false;
    }

    $stmt = $pdo->prepare(
        "UPDATE productos 
         SET stock = GREATEST(0, stock - ?) 
         WHERE id = ? AND estado = 'activo'"
    );

    return $stmt->execute([$cantidad, $producto_id]);
}

/**
 * 🆕 Obtiene el stock actual de un producto específico
 *
 * @param PDO $pdo          Conexión a la base de datos
 * @param int $producto_id  ID del producto
 * @return int|null         Stock actual o null si no existe
 */
function obtenerStockProducto(PDO $pdo, int $producto_id): ?int {
    $stmt = $pdo->prepare(
        "SELECT stock FROM productos WHERE id = ? LIMIT 1"
    );
    $stmt->execute([$producto_id]);
    $resultado = $stmt->fetchColumn();
    return $resultado !== false ? (int) $resultado : null;
}

/**
 * 🆕 Cambia el estado de un producto entre 'activo' e 'inactivo'
 * (Solo administradores deberían llamar esta función)
 */
function cambiarEstadoProducto(PDO $pdo, int $id, string $estado): bool {
    if (!in_array($estado, ['activo', 'inactivo'], true)) {
        return false;
    }

    $stmt = $pdo->prepare(
        "UPDATE productos SET estado = ? WHERE id = ?"
    );
    return $stmt->execute([$estado, $id]);
}
