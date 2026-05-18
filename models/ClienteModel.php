<?php

function obtenerClientes(mysqli $conn, string $search = ''): array {
    $clientes = [];
    if ($search !== '') {
        $search = "%" . $conn->real_escape_string($search) . "%";
        $sql = "SELECT id, nombre_completo, nit_cedula, telefono, email, tipo_cliente, estado FROM clientes WHERE nombre_completo LIKE ? OR nit_cedula LIKE ? ORDER BY nombre_completo ASC";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ss', $search, $search);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $clientes[] = $row;
            }
            $stmt->close();
        }
    } else {
        $sql = "SELECT id, nombre_completo, nit_cedula, telefono, email, tipo_cliente, estado FROM clientes ORDER BY nombre_completo ASC";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $clientes[] = $row;
        }
    }
    return $clientes;
}

function obtenerClientePorId(mysqli $conn, int $id): ?array {
    $sql = "SELECT id, nombre_completo, nit_cedula, telefono, email, tipo_cliente, estado FROM clientes WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cliente = $result->fetch_assoc();
    $stmt->close();
    return $cliente ?: null;
}

function crearCliente(mysqli $conn, array $data): bool {
    $sql = "INSERT INTO clientes (nombre_completo, nit_cedula, telefono, email, tipo_cliente) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param(
        'sssss',
        $data['nombre_completo'],
        $data['nit_cedula'],
        $data['telefono'],
        $data['email'],
        $data['tipo_cliente']
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function actualizarCliente(mysqli $conn, int $id, array $data): bool {
    $sql = "UPDATE clientes SET nombre_completo = ?, nit_cedula = ?, telefono = ?, email = ?, tipo_cliente = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param(
        'sssssi',
        $data['nombre_completo'],
        $data['nit_cedula'],
        $data['telefono'],
        $data['email'],
        $data['tipo_cliente'],
        $id
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function clienteTienePedidos(mysqli $conn, int $id): bool {
    $sql = "SELECT 1 FROM pedidos WHERE cliente_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->store_result();
    $hasPedidos = $stmt->num_rows > 0;
    $stmt->close();
    return $hasPedidos;
}

function eliminarCliente(mysqli $conn, int $id): bool {
    if (clienteTienePedidos($conn, $id)) {
        return false;
    }
    $sql = "DELETE FROM clientes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param('i', $id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function cambiarEstadoCliente(mysqli $conn, int $id, string $estado): bool {
    if (!in_array($estado, ['activo', 'inactivo'], true)) {
        return false;
    }
    $sql = "UPDATE clientes SET estado = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param('si', $estado, $id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}
