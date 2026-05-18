<?php

function obtenerPedidos(mysqli $conn): array {
    $pedidos = [];
    $sql = "SELECT p.id, p.detalle, p.cantidad, p.estado, p.fecha_entrega, c.nombre_completo FROM pedidos p JOIN clientes c ON p.cliente_id = c.id ORDER BY p.fecha_entrega ASC";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = $row;
    }
    return $pedidos;
}

function obtenerPedidoPorId(mysqli $conn, int $id): ?array {
    $sql = "SELECT p.id, p.detalle, p.cantidad, p.estado, p.fecha_entrega, c.nombre_completo FROM pedidos p JOIN clientes c ON p.cliente_id = c.id WHERE p.id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pedido = $result->fetch_assoc();
    $stmt->close();
    return $pedido ?: null;
}
