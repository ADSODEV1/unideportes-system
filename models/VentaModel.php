<?php
// models/VentaModel.php

class VentaModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene las ventas de un vendedor específico evitando fallos en LIMIT/OFFSET
     */
    public function getVentasByVendedor($vendedorId, $limit = 50, $offset = 0) {
        try {
            $sql = "SELECT v.*, c.nombre_completo as cliente_nombre
                    FROM ventas v
                    INNER JOIN clientes c ON v.cliente_id = c.id
                    WHERE v.vendedor_id = ?
                    ORDER BY v.fecha_venta DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->pdo->prepare($sql);
            
            // Bindeo manual explícito para garantizar compatibilidad con enteros en MySQL
            $stmt->bindValue(1, $vendedorId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->bindValue(3, $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo ventas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el resumen total de ventas por vendedor
     */
    public function getResumenVendedor($vendedorId) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_ventas,
                        SUM(total_venta) as total_ingresos,
                        AVG(total_venta) as promedio_venta
                    FROM ventas
                    WHERE vendedor_id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$vendedorId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['total_ventas' => 0, 'total_ingresos' => 0, 'promedio_venta' => 0];
        }
    }
}