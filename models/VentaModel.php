<?php
// models/VentaModel.php

class VentaModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene las ventas de un vendedor específico evitando fallos en LIMIT/OFFSET (Histórico total)
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


     // Obtiene las ventas filtradas por un rango de fechas (ej: Mes actual)
  
    public function getVentasByVendedorYFecha($vendedorId, $fechaInicio, $fechaFin, $limit = 50, $offset = 0) {
        try {
            // Usamos DATE(v.fecha_venta) para comparar solo Año-Mes-Día sin importar las horas
            $sql = "SELECT v.*, c.nombre_completo as cliente_nombre
                    FROM ventas v
                    INNER JOIN clientes c ON v.cliente_id = c.id
                    WHERE v.vendedor_id = ?
                      AND DATE(v.fecha_venta) BETWEEN ? AND ?
                    ORDER BY v.fecha_venta DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->pdo->prepare($sql);
            
            // Bindeo posicional ordenado cuidando los tipos de datos
            $stmt->bindValue(1, $vendedorId, PDO::PARAM_INT);
            $stmt->bindValue(2, $fechaInicio, PDO::PARAM_STR);
            $stmt->bindValue(3, $fechaFin, PDO::PARAM_STR);
            $stmt->bindValue(4, $limit, PDO::PARAM_INT);
            $stmt->bindValue(5, $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo ventas por fecha: " . $e->getMessage());
            return [];
        }
    }


    // Obtiene el resumen total histórico de ventas por vendedor
  
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


     // Obtiene el resumen financiero acotado al mes o rango seleccionado
  
    public function getResumenVendedorYFecha($vendedorId, $fechaInicio, $fechaFin) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_ventas,
                        SUM(total_venta) as total_ingresos,
                        AVG(total_venta) as promedio_venta
                    FROM ventas
                    WHERE vendedor_id = ?
                      AND DATE(fecha_venta) BETWEEN ? AND ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$vendedorId, $fechaInicio, $fechaFin]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Si en ese mes no hay ventas, SUM() dará NULL. Esto asegura que devuelva 0 en vez de NULLs.
            return [
                'total_ventas'    => intval($resultado['total_ventas'] ?? 0),
                'total_ingresos'  => floatval($resultado['total_ingresos'] ?? 0.0),
                'promedio_venta'  => floatval($resultado['promedio_venta'] ?? 0.0)
            ];
        } catch (PDOException $e) {
            return ['total_ventas' => 0, 'total_ingresos' => 0, 'promedio_venta' => 0];
        }
    }

}