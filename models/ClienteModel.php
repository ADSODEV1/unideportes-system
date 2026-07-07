<?php
// models/ClienteModel.php

function generarCodigoDescriptivoCliente(): string {
    try {
        return 'CLI-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
    } catch (Exception $e) {
        return 'CLI-' . date('YmdHis') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 4));
    }
}

function obtenerClientes(PDO $pdo, string $search = ''): array {
    if ($search !== '') {
        $like = '%' . $search . '%';
        $stmt = $pdo->prepare(
            "SELECT id, codigo_descriptivo, nombre_completo, nit_cedula, telefono, email, tipo_cliente, direccion, barrio, ciudad, referencia_entrega, estado 
             FROM clientes 
             WHERE nombre_completo LIKE ? OR nit_cedula LIKE ? 
             ORDER BY nombre_completo ASC"
        );
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmt = $pdo->query(
        "SELECT id, codigo_descriptivo, nombre_completo, nit_cedula, telefono, email, tipo_cliente, direccion, barrio, ciudad, referencia_entrega, estado 
         FROM clientes 
         ORDER BY nombre_completo ASC"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerClientePorId(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare(
        "SELECT id, codigo_descriptivo, nombre_completo, nit_cedula, telefono, email, tipo_cliente, direccion, barrio, ciudad, referencia_entrega, estado 
         FROM clientes 
         WHERE id = ? LIMIT 1"
    );
    $stmt->execute([$id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    return $cliente ?: null;
}

function crearCliente(PDO $pdo, array $data): bool {
    $codigoDescriptivo = trim((string)($data['codigo_descriptivo'] ?? ''));
    if ($codigoDescriptivo === '') {
        $codigoDescriptivo = generarCodigoDescriptivoCliente();
    }

    $stmt = $pdo->prepare(
        "INSERT INTO clientes (codigo_descriptivo, nombre_completo, nit_cedula, telefono, email, tipo_cliente, direccion, barrio, ciudad, referencia_entrega) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    return $stmt->execute([
        $codigoDescriptivo,
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
         SET nombre_completo = ?, nit_cedula = ?, telefono = ?, email = ?, tipo_cliente = ?, direccion = ?, barrio = ?, ciudad = ?, referencia_entrega = ?, estado = ? 
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
        $data['estado'] ?? 'activo',
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