<?php
session_start();
include("connection.php");

// 1. SEGURIDAD: Solo usuarios logueados
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// 2. CONSULTAS DE ESTADO (KPIs Dinámicos)
// Estas consultas cuentan cuántos pedidos hay en cada estado
$c_corte   = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as t FROM pedidos WHERE estado = 'En Corte'"))['t'] ?? 0;
$c_costura = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as t FROM pedidos WHERE estado = 'En Costura'"))['t'] ?? 0;
$c_termino = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as t FROM pedidos WHERE estado = 'Terminado'"))['t'] ?? 0;

// 3. LISTADO COMPLETO (CORREGIDO CON INNER JOIN)
// Traemos los datos del pedido y el nombre del cliente desde la tabla clientes
$sql = "SELECT pedidos.*, clientes.nombre_completo 
        FROM pedidos 
        INNER JOIN clientes ON pedidos.cliente_id = clientes.id 
        ORDER BY pedidos.fecha_entrega ASC";

$query = mysqli_query($conn, $sql);

include("header.php");
?>

<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold" style="color: #1A2B4C;">🧵 Ordenes de Producción</h2>
            <p class="text-muted mb-0">Seguimiento de prendas en taller y entregas.</p>
        </div>
        <?php if($_SESSION['role'] == 'admin'): ?>
        <a href="nuevo_pedido.php" class="btn text-white fw-bold shadow-sm" style="background-color: #E61E2A; border-radius: 10px;">
            + NUEVA ORDEN
        </a>
        <?php endif; ?>
    </div>

    <div class="row g-3 mb-4 text-center">
        <div class="col-6 col-md-4">
            <div class="p-3 border-bottom border-4 border-warning bg-white shadow-sm rounded-3">
                <small class="text-muted fw-bold d-block text-uppercase">En Corte</small>
                <span class="h4 fw-bold"><?= str_pad($c_corte, 2, "0", STR_PAD_LEFT) ?></span>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="p-3 border-bottom border-4 border-primary bg-white shadow-sm rounded-3">
                <small class="text-muted fw-bold d-block text-uppercase">En Costura</small>
                <span class="h4 fw-bold"><?= str_pad($c_costura, 2, "0", STR_PAD_LEFT) ?></span>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="p-3 border-bottom border-4 border-success bg-white shadow-sm rounded-3">
                <small class="text-muted fw-bold d-block text-uppercase">Terminados</small>
                <span class="h4 fw-bold"><?= str_pad($c_termino, 2, "0", STR_PAD_LEFT) ?></span>
            </div>
        </div>
    </div>

    <div class="card shadow-lg border-0" style="border-radius: 20px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead style="background-color: #1A2B4C; color: white;">
                    <tr class="small text-uppercase">
                        <th class="p-3">Cliente / ID</th>
                        <th class="p-3">Detalle</th>
                        <th class="p-3 text-center">Cantidad</th>
                        <th class="p-3 text-center">Estado</th>
                        <th class="p-3 text-center">Entrega</th>
                        <th class="p-3 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_array($query)): ?>
                    <tr>
                        <td class="p-3">
                            <div class="fw-bold"><?= $row['nombre_completo'] ?></div>
                            <small class="text-muted">#ORD-<?= $row['id'] ?></small>
                        </td>
                        <td class="p-3"><?= $row['detalle'] ?></td>
                        <td class="p-3 text-center"><?= $row['cantidad'] ?> unid.</td>
                        <td class="p-3 text-center">
                            <?php 
                                $color = "bg-secondary";
                                if($row['estado'] == 'En Corte') $color = "bg-warning text-dark";
                                if($row['estado'] == 'En Costura') $color = "bg-primary";
                                if($row['estado'] == 'Terminado') $color = "bg-success";
                            ?>
                            <span class="badge rounded-pill <?= $color ?> px-3"><?= $row['estado'] ?></span>
                        </td>
                        <td class="p-3 text-center fw-bold">
                            <?= date("d M Y", strtotime($row['fecha_entrega'])) ?>
                        </td>
                        <td class="p-3 text-center">
                            <a href="detalle_pedido.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-light border shadow-sm">📋</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>