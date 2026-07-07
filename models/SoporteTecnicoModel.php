<?php
// models/SoporteTecnicoModel.php

function asegurarTablaSoporteTecnico(PDO $conn): void {
    // Tabla de tickets
    $conn->exec("CREATE TABLE IF NOT EXISTS soporte_tickets (
        id_ticket INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        asunto VARCHAR(180) NOT NULL,
        prioridad ENUM('Crítica','Alta','Media','Baja') NOT NULL DEFAULT 'Media',
        estado ENUM('Abierto','En Proceso','Resuelto','Cerrado') NOT NULL DEFAULT 'Abierto',
        fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        vendedor VARCHAR(120) NOT NULL,
        comentario_solucion TEXT NULL,
        updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_prioridad (prioridad),
        INDEX idx_estado (estado),
        INDEX idx_fecha (fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla de historial de comentarios
    $conn->exec("CREATE TABLE IF NOT EXISTS soporte_comentarios (
        id_comentario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_ticket INT UNSIGNED NOT NULL,
        autor VARCHAR(120) NOT NULL,
        mensaje TEXT NOT NULL,
        fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ticket (id_ticket),
        INDEX idx_fecha (fecha),
        FOREIGN KEY (id_ticket) REFERENCES soporte_tickets(id_ticket) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function listarTicketsSoporte(PDO $conn): array {
    $stmt = $conn->query(
        "SELECT id_ticket, asunto, prioridad, estado, fecha, vendedor, comentario_solucion, updated_at
         FROM soporte_tickets
         ORDER BY fecha DESC, id_ticket DESC"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function listarTicketsPorVendedor(PDO $conn, string $vendedor): array {
    $stmt = $conn->prepare(
        "SELECT id_ticket, asunto, prioridad, estado, fecha, vendedor, comentario_solucion, updated_at
         FROM soporte_tickets
         WHERE vendedor = :vendedor
         ORDER BY fecha DESC, id_ticket DESC"
    );
    $stmt->execute([':vendedor' => $vendedor]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function crearTicketSoporte(PDO $conn, string $asunto, string $prioridad, string $vendedor, ?string $comentario): int {
    $sql = "INSERT INTO soporte_tickets (asunto, prioridad, estado, fecha, vendedor, comentario_solucion)
            VALUES (:asunto, :prioridad, 'Abierto', NOW(), :vendedor, :comentario)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':asunto' => $asunto,
        ':prioridad' => $prioridad,
        ':vendedor' => $vendedor,
        ':comentario' => $comentario,
    ]);
    $ticketId = (int) $conn->lastInsertId();

    // Si hay comentario inicial, guardarlo en el historial
    if ($comentario !== null && trim($comentario) !== '') {
        agregarComentario($conn, $ticketId, $vendedor, $comentario);
    }

    return $ticketId;
}

function actualizarTicketSoporte(PDO $conn, int $idTicket, string $estado, ?string $comentario): bool {
    $sql = "UPDATE soporte_tickets
            SET estado = :estado,
                comentario_solucion = COALESCE(:comentario, comentario_solucion),
                updated_at = NOW()
            WHERE id_ticket = :id_ticket";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([
        ':estado' => $estado,
        ':comentario' => $comentario,
        ':id_ticket' => $idTicket,
    ]);
}

// Funciones para historial de comentarios
function agregarComentario(PDO $conn, int $idTicket, string $autor, string $mensaje): int {
    $sql = "INSERT INTO soporte_comentarios (id_ticket, autor, mensaje, fecha)
            VALUES (:id_ticket, :autor, :mensaje, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':id_ticket' => $idTicket,
        ':autor' => $autor,
        ':mensaje' => $mensaje,
    ]);

    // Actualizar updated_at del ticket
    $conn->exec("UPDATE soporte_tickets SET updated_at = NOW() WHERE id_ticket = $idTicket");

    return (int) $conn->lastInsertId();
}

function listarComentariosTicket(PDO $conn, int $idTicket): array {
    $stmt = $conn->prepare(
        "SELECT autor, mensaje, fecha 
         FROM soporte_comentarios 
         WHERE id_ticket = :id_ticket 
         ORDER BY fecha ASC"
    );
    $stmt->execute([':id_ticket' => $idTicket]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}