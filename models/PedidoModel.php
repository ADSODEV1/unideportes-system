<?php

function obtenerPedidos(PDO $pdo): array {
    $stmt = $pdo->query(
        "SELECT p.id, p.detalle, p.cantidad, p.estado, p.fecha_entrega, c.nombre_completo FROM pedidos p JOIN clientes c ON p.cliente_id = c.id ORDER BY p.fecha_entrega ASC"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerPedidoPorId(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare(
        "SELECT p.id, p.detalle, p.cantidad, p.estado, p.fecha_entrega, c.nombre_completo FROM pedidos p JOIN clientes c ON p.cliente_id = c.id WHERE p.id = ? LIMIT 1"
    );
    $stmt->execute([$id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    return $pedido ?: null;
}
