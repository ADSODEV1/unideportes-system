<?php

function obtenerProductos(mysqli $conn, string $search = ''): array {
    $productos = [];
    if ($search !== '') {
        $like = "%" . $conn->real_escape_string($search) . "%";
        $sql = "SELECT * FROM productos WHERE nombre LIKE ? OR referencia LIKE ? ORDER BY nombre ASC";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ss', $like, $like);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $productos[] = $row;
            }
            $stmt->close();
        }
    } else {
        $result = $conn->query("SELECT * FROM productos ORDER BY nombre ASC");
        while ($row = $result->fetch_assoc()) {
            $productos[] = $row;
        }
    }
    return $productos;
}

function obtenerProductoPorId(mysqli $conn, int $id): ?array {
    $sql = "SELECT * FROM productos WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $producto = $result->fetch_assoc();
    $stmt->close();
    return $producto ?: null;
}

function crearProducto(mysqli $conn, array $data): bool {
    $sql = "INSERT INTO productos (nombre, referencia, talla, stock, precio) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('sssdi', $data['nombre'], $data['referencia'], $data['talla'], $data['stock'], $data['precio']);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function actualizarProducto(mysqli $conn, int $id, array $data): bool {
    $sql = "UPDATE productos SET nombre = ?, referencia = ?, talla = ?, stock = ?, precio = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('sssidi', $data['nombre'], $data['referencia'], $data['talla'], $data['stock'], $data['precio'], $id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function existeReferenciaProducto(mysqli $conn, string $referencia, int $excludeId = 0): bool {
    if ($excludeId > 0) {
        $sql = "SELECT 1 FROM productos WHERE referencia = ? AND id != ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('si', $referencia, $excludeId);
    } else {
        $sql = "SELECT 1 FROM productos WHERE referencia = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $referencia);
    }
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}
