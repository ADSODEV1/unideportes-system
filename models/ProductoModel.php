<?php

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

function obtenerProductoPorId(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    return $producto ?: null;
}

function crearProducto(PDO $pdo, array $data): bool {
    $stmt = $pdo->prepare("INSERT INTO productos (nombre, referencia, talla, stock, precio) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([
        $data['nombre'],
        $data['referencia'],
        $data['talla'],
        $data['stock'],
        $data['precio'],
    ]);
}

function actualizarProducto(PDO $pdo, int $id, array $data): bool {
    $stmt = $pdo->prepare("UPDATE productos SET nombre = ?, referencia = ?, talla = ?, stock = ?, precio = ? WHERE id = ?");
    return $stmt->execute([
        $data['nombre'],
        $data['referencia'],
        $data['talla'],
        $data['stock'],
        $data['precio'],
        $id,
    ]);
}

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
