<?php
session_start();
include("connection.php");

// 1. SEGURIDAD: Si no hay sesión, al index.
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// 2. LÓGICA: Consultas de conteo para los indicadores
$fecha_hoy = date('Y-m-d');

// Ventas del día
$res_ventas = mysqli_query($conn, "SELECT COUNT(*) as total FROM ventas WHERE DATE(fecha_venta) = '$fecha_hoy'");
$ventas_hoy = ($res_ventas) ? mysqli_fetch_array($res_ventas)['total'] : 0;

// Alertas de Stock bajo (3 o menos unidades)
$res_alerta = mysqli_query($conn, "SELECT COUNT(*) as bajo FROM productos WHERE stock > 0 AND stock <= 3");
$alertas_stock = ($res_alerta) ? mysqli_fetch_array($res_alerta)['bajo'] : 0;

include("header.php");
?>

<div class="panel-container">
    <h1>Panel de Control - Unideportes</h1>
    <p>Bienvenido: <strong><?php echo $_SESSION['username']; ?></strong></p>

    <div class="indicadores">
        <div class="cuadro">
            <span>Ventas hoy:</span>
            <strong><?php echo $ventas_hoy; ?></strong>
        </div>
        <div class="cuadro">
            <span>Stock bajo:</span>
            <strong><?php echo $alertas_stock; ?></strong>
        </div>
    </div>

    <hr>

    <nav class="menu-vendedor">
        <ul>
            <li><a href="nueva_venta.php">Nueva Venta</a></li>
            <li><a href="clientes.php">Ver Clientes</a></li>
            <li><a href="inventario.php">Consultar Stock</a></li>
            <li><a href="logout.php">Cerrar Sesión</a></li>
        </ul>
    </nav>
</div>

<?php include("footer.php"); 

?>