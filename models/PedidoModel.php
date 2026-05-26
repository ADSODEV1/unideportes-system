<?php
// models/Pedido.php (Fracción del modelo de datos)

class Pedido {
    private $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    /**
     * Liquida el saldo de un pedido y lo marca como entregado
     */
    public function entregarYLiquidarPedido($id) {
        try {
            $this->db->beginTransaction();

            // Calcular saldo
            $sql = "SELECT total_pedido, IFNULL((SELECT SUM(monto) FROM pagos WHERE id_pg_pedido = pedidos.id), 0) as pagado 
                    FROM pedidos WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$res) return false;

            $saldo = $res['total_pedido'] - $res['pagado'];

            // Registrar pago final si aplica
            if ($saldo > 0) {
                $sqlPago = "INSERT INTO pagos (id_pg_pedido, monto, fecha_pago, metodo_pago) VALUES (:id, :monto, NOW(), 'Efectivo')";
                $this->db->prepare($sqlPago)->execute([':id' => $id, ':monto' => $saldo]);
            }

            // Cambiar estado
            $sqlUpdate = "UPDATE pedidos SET estado = 'Entregado' WHERE id = :id";
            $this->db->prepare($sqlUpdate)->execute([':id' => $id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return false;
        }
    }
}