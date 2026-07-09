<?php
/**
 * Controlador de Reportes de Ventas y Cartera
 */
class ReportesVentasController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // ========================================
    // KPIs GENERALES DE VENTAS
    // ========================================
    public function obtenerKPIsVentas($fecha_inicio, $fecha_fin) {
        $sql = "SELECT 
                    COALESCE(SUM(total_venta), 0) as total_ingresos, 
                    COUNT(id) as total_transacciones,
                    COALESCE(AVG(total_venta), 0) as promedio_ticket
                 FROM ventas 
                 WHERE DATE(fecha_venta) BETWEEN :inicio AND :fin";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // ========================================
    // VENTAS POR MÉTODO DE PAGO
    // ========================================
    public function obtenerVentasPorMetodo($fecha_inicio, $fecha_fin) {
        $sql = "SELECT metodo_pago, 
                       SUM(total_venta) as total, 
                       COUNT(id) as cantidad
                FROM ventas 
                WHERE DATE(fecha_venta) BETWEEN :inicio AND :fin
                GROUP BY metodo_pago
                ORDER BY total DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ========================================
    // VENTAS DETALLADAS
    // ========================================
    public function obtenerVentasDetalladas($fecha_inicio, $fecha_fin) {
        $sql = "SELECT 
                    v.id,
                    v.ticket_numero,
                    v.fecha_venta,
                    c.nombre_completo as cliente,
                    u.username as vendedor,
                    v.metodo_pago,
                    v.tipo_entrega,
                    v.total_venta
                  FROM ventas v
                  INNER JOIN clientes c ON v.cliente_id = c.id
                  INNER JOIN usuarios u ON v.vendedor_id = u.id
                  WHERE DATE(v.fecha_venta) BETWEEN :inicio AND :fin
                  ORDER BY v.fecha_venta DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ========================================
    // TOP 5 PRODUCTOS
    // ========================================
    public function obtenerTopProductos($fecha_inicio, $fecha_fin) {
        $sql = "SELECT 
                    p.nombre, 
                    p.referencia, 
                    SUM(dv.cantidad) as total_vendido, 
                    SUM(dv.cantidad * dv.precio_unitario) as total_recaudado
                 FROM detalle_venta dv
                 INNER JOIN productos p ON dv.producto_id = p.id
                 INNER JOIN ventas v ON dv.venta_id = v.id
                 WHERE DATE(v.fecha_venta) BETWEEN :inicio AND :fin
                 GROUP BY p.id
                 ORDER BY total_vendido DESC
                 LIMIT 5";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ========================================
    // KPIs DE CARTERA (ABONOS) - CORREGIDO
    // ========================================
    public function obtenerKPIsCartera($fecha_inicio, $fecha_fin) {
        $sql = "SELECT 
                    COALESCE(SUM(total_pedido), 0) as total_facturado,
                    COALESCE(SUM(abono), 0) as total_abonos_iniciales,
                    COALESCE((SELECT SUM(monto) FROM pagos pa 
                              INNER JOIN pedidos p2 ON pa.id_pg_pedido = p2.id 
                              WHERE DATE(pa.fecha) BETWEEN :inicio AND :fin), 0) as total_abonos_periodo,
                    COALESCE(SUM(saldo_pendiente), 0) as saldo_pendiente_total,
                    COUNT(CASE WHEN saldo_pendiente > 0 THEN 1 END) as pedidos_por_cobrar,
                    COUNT(CASE WHEN saldo_pendiente <= 0 THEN 1 END) as pedidos_pagados
                 FROM pedidos
                 WHERE estado != 'Entregado'
                    OR (estado = 'Entregado' AND DATE(fecha_entrega) BETWEEN :inicio AND :fin)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // ========================================
    // PEDIDOS CON SALDO PENDIENTE - CORREGIDO
    // ========================================
    public function obtenerPedidosPendientes() {
        $sql = "SELECT 
                    p.id,
                    c.nombre_completo as cliente,
                    c.nit_cedula,
                    p.total_pedido,
                    COALESCE(p.abono, 0) as abono_inicial,
                    COALESCE((SELECT SUM(monto) FROM pagos WHERE id_pg_pedido = p.id), 0) as total_pagos,
                    COALESCE(p.saldo_pendiente, 0) as saldo_pendiente,
                    p.estado,
                    p.created_at
                FROM pedidos p
                INNER JOIN clientes c ON p.cliente_id = c.id
                WHERE p.saldo_pendiente > 0
                ORDER BY p.saldo_pendiente DESC
                LIMIT 20";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ========================================
    // ABONOS RECIENTES
    // ========================================
    public function obtenerAbonosRecientes($fecha_inicio, $fecha_fin) {
        $sql = "SELECT 
                    pa.id_pago,
                    pa.id_pg_pedido,
                    pa.monto,
                    pa.metodo_pago,
                    pa.fecha,
                    c.nombre_completo as cliente,
                    p.total_pedido
                FROM pagos pa
                INNER JOIN pedidos p ON pa.id_pg_pedido = p.id
                INNER JOIN clientes c ON p.cliente_id = c.id
                WHERE DATE(pa.fecha) BETWEEN :inicio AND :fin
                ORDER BY pa.fecha DESC
                LIMIT 50";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}