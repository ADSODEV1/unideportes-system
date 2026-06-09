<?php
// models/ProductoModel.php

/**
 * Obtiene el listado de productos filtrado o completo (Ordenado alfabéticamente)
 */
function obtenerProductos(PDO $pdo, string $search = ''): array {
    if ($search !== '') {
        $like = '%' . $search . '%';
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE nombre LIKE ? OR referencia LIKE ? ORDER BY nombre ASC");
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
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    return $producto ?: null;
}

/**
 * Sincronizado con el Controlador: Inserta un nuevo producto en el catálogo
 */
function crearProducto(PDO $pdo, array $data): bool {
    $stmt = $pdo->prepare("INSERT INTO productos (nombre, referencia, categoria, color, material, genero, estado, descripcion, talla, stock, precio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([
        $data['nombre'],
        $data['referencia'],
        $data['categoria'],
        $data['color'],
        $data['material'],
        $data['genero'],
        $data['estado'],
        $data['descripcion'],
        $data['talla'],
        $data['stock'],
        $data['precio'],
    ]);
}

/**
 * Actualiza las propiedades físicas y económicas de una prenda existente
 */
function actualizarProducto(PDO $pdo, int $id, array $data): bool {
    $stmt = $pdo->prepare("UPDATE productos SET nombre = ?, referencia = ?, categoria = ?, color = ?, material = ?, genero = ?, estado = ?, descripcion = ?, talla = ?, stock = ?, precio = ? WHERE id = ?");
    return $stmt->execute([
        $data['nombre'],
        $data['referencia'],
        $data['categoria'],
        $data['color'],
        $data['material'],
        $data['genero'],
        $data['estado'],
        $data['descripcion'],
        $data['talla'],
        $data['stock'],
        $data['precio'],
        $id,
    ]);
}

/**
 * Valida la existencia de una referencia para evitar duplicados en la base de datos
 */
function existeReferenciaProducto(PDO $pdo, string $referencia, int $excludeId = 0): bool {
    if ($excludeId > 0) {
        $stmt = $pdo->prepare("SELECT 1 FROM productos WHERE referencia = ? AND id != ? LIMIT 1");
        $stmt->execute([$referencia, $excludeId]);
    } else {
        $stmt = $pdo->prepare("SELECT 1 FROM productos WHERE referencia = ? LIMIT 1");
        $stmt->execute([$referencia]);
    }

    return (bool) $stmt->fetchColumn();
}