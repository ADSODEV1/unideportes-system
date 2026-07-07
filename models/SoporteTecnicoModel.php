<?php
// models/SoporteTecnicoModel.php

function asegurarTablaSoporteTecnico(PDO $conn): void {
    $sql = "CREATE TABLE IF NOT EXISTS soporte_tickets (
                id_ticket INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                asunto VARCHAR(180) NOT NULL,
                prioridad ENUM('Crítica','Alta','Media','Baja') NOT NULL DEFAULT 'Media',
                estado ENUM('Abierto','En Proceso','Resuelto','Cerrado') NOT NULL DEFAULT 'Abierto',
                fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                vendedor VARCHAR(120) NOT NULL,
                comentario_solucion TEXT NULL,
                updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_prioridad (prioridad),
                INDEX idx_estado (estado),
                INDEX idx_fecha (fecha)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $conn->exec($sql);
}

function listarTicketsSoporte(PDO $conn): array {
    $stmt = $conn->query(
        "SELECT id_ticket, asunto, prioridad, estado, fecha, vendedor, comentario_solucion
         FROM soporte_tickets
         ORDER BY fecha DESC, id_ticket DESC"
    );

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

    return (int) $conn->lastInsertId();
}

function actualizarTicketSoporte(PDO $conn, int $idTicket, string $estado, ?string $comentario): bool {
    $sql = "UPDATE soporte_tickets
            SET estado = :estado,
                comentario_solucion = :comentario,
                updated_at = NOW()
            WHERE id_ticket = :id_ticket";

    $stmt = $conn->prepare($sql);
    return $stmt->execute([
        ':estado' => $estado,
        ':comentario' => $comentario,
        ':id_ticket' => $idTicket,
    ]);
}
