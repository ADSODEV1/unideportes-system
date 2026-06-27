<?php
// models/ClienteModel.php

function obtenerClientes(PDO $pdo, string $search = ''): array {
    if ($search !== '') {
        $like = '%' . $search . '%';
        $stmt = $pdo->prepare(
            "SELECT id, codigo_descriptivo, nombre_completo, nit_cedula, telefono, email, tipo_cliente, direccion, barrio, ciudad, referencia_entrega 
             FROM clientes 
             WHERE nombre_completo LIKE ? OR nit_cedula LIKE ? 
             ORDER BY nombre_completo ASC"
        );
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmt = $pdo->query(
        "SELECT id, codigo_descriptivo, nombre_completo, nit_cedula, telefono, email, tipo_cliente, direccion, barrio, ciudad, referencia_entrega 
         FROM clientes 
         ORDER BY nombre_completo ASC"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerClientePorId(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare(
        "SELECT id, codigo_descriptivo, nombre_completo, nit_cedula, telefono, email, tipo_cliente, direccion, barrio, ciudad, referencia_entrega 
         FROM clientes 
         WHERE id = ? LIMIT 1"
    );
    $stmt->execute([$id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    return $cliente ?: null;
}

function crearCliente(PDO $pdo, array $data): bool {
    // Generar codigo_descriptivo automáticamente si no viene en el array
    $codigo = $data['codigo_descriptivo'] ?? null;
    if (empty($codigo)) {
        $codigo = 'CLI-' . date('ymd') . '-' . rand(100, 999);
    }
    
    $stmt = $pdo->prepare(
        "INSERT INTO clientes (codigo_descriptivo, nombre_completo, nit_cedula, telefono, email, tipo_cliente, direccion, barrio, ciudad, referencia_entrega)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    return $stmt->execute([
        $codigo,
        $data['nombre_completo'],
        $data['nit_cedula'],
        $data['telefono'],
        $data['email'] ?: null,
        $data['tipo_cliente'] ?? 'Individual',
        $data['direccion'] ?? null,
        $data['barrio'] ?? null,
        $data['ciudad'] ?? 'Sogamoso',
        $data['referencia_entrega'] ?? null
    ]);
}

function actualizarCliente(PDO $pdo, int $id, array $data): bool {
    $stmt = $pdo->prepare(
        "UPDATE clientes 
         SET nombre_completo = ?, nit_cedula = ?, telefono = ?, email = ?, tipo_cliente = ?, direccion = ?, barrio = ?, ciudad = ?, referencia_entrega = ? 
         WHERE id = ?"
    );
    return $stmt->execute([
        $data['nombre_completo'],
        $data['nit_cedula'],
        $data['telefono'],
        $data['email'] ?: null,
        $data['tipo_cliente'] ?? 'Individual',
        $data['direccion'] ?? null,
        $data['barrio'] ?? null,
        $data['ciudad'] ?? 'Sogamoso',
        $data['referencia_entrega'] ?? null,
        $id
    ]);
}

function clienteTienePedidos(PDO $pdo, int $id): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM pedidos WHERE cliente_id = ? LIMIT 1");
    $stmt->execute([$id]);
    return (bool) $stmt->fetchColumn();
}

function eliminarCliente(PDO $pdo, int $id): bool {
    if (clienteTienePedidos($pdo, $id)) {
        return false;
    }
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Obtiene el saldo pendiente de ventas mayoristas del cliente
 */
function obtenerSaldoVentasMayoristas(PDO $pdo, int $clienteId): array {
    $stmt = $pdo->prepare(
        "SELECT 
            COUNT(*) as total_ventas,
            SUM(total_venta) as total_compras,
            SUM(abono) as total_abonado,
            SUM(saldo_pendiente) as saldo_pendiente
         FROM ventas 
         WHERE cliente_id = ? AND tipo_venta = 'mayorista' AND saldo_pendiente > 0"
    );
    $stmt->execute([$clienteId]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $resultado ?: [
        'total_ventas' => 0,
        'total_compras' => 0,
        'total_abonado' => 0,
        'saldo_pendiente' => 0
    ];
}

/**
 * Obtiene el estado de los pedidos de fabricación del cliente
 */
function obtenerEstadoPedidosFabricacion(PDO $pdo, int $clienteId): array {
    $stmt = $pdo->prepare(
        "SELECT 
            COUNT(*) as total_pedidos,
            SUM(CASE WHEN estado = 'Terminado' THEN 1 ELSE 0 END) as pedidos_listos,
            SUM(CASE WHEN estado IN ('En Corte', 'En Costura') THEN 1 ELSE 0 END) as pedidos_produccion,
            SUM(CASE WHEN estado = 'Entregado' THEN 1 ELSE 0 END) as pedidos_entregados
         FROM pedidos 
         WHERE cliente_id = ? AND estado != 'Entregado'"
    );
    $stmt->execute([$clienteId]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $resultado ?: [
        'total_pedidos' => 0,
        'pedidos_listos' => 0,
        'pedidos_produccion' => 0,
        'pedidos_entregados' => 0
    ];
}

/**
 * Obtiene clientes con información completa incluyendo saldos y pedidos
 */
function obtenerClientesConSaldo(PDO $pdo, string $search = ''): array {
    if ($search !== '') {
        $like = '%' . $search . '%';
        $stmt = $pdo->prepare(
            "SELECT id, codigo_descriptivo, nombre_completo, nit_cedula, telefono, email, 
                    tipo_cliente, direccion, barrio, ciudad, referencia_entrega, estado
             FROM clientes
             WHERE nombre_completo LIKE ? OR nit_cedula LIKE ?
             ORDER BY nombre_completo ASC"
        );
        $stmt->execute([$like, $like]);
    } else {
        $stmt = $pdo->query(
            "SELECT id, codigo_descriptivo, nombre_completo, nit_cedula, telefono, email, 
                    tipo_cliente, direccion, barrio, ciudad, referencia_entrega, estado
             FROM clientes
             ORDER BY nombre_completo ASC"
        );
    }
    
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agregar información de saldos y pedidos a cada cliente
    foreach ($clientes as &$cliente) {
        $saldoData = obtenerSaldoVentasMayoristas($pdo, $cliente['id']);
        $pedidosData = obtenerEstadoPedidosFabricacion($pdo, $cliente['id']);
        
        $cliente['saldo_pendiente'] = floatval($saldoData['saldo_pendiente']);
        $cliente['total_ventas_mayorista'] = intval($saldoData['total_ventas']);
        $cliente['pedidos_listos'] = intval($pedidosData['pedidos_listos']);
        $cliente['pedidos_produccion'] = intval($pedidosData['pedidos_produccion']);
    }
    
    return $clientes;
}