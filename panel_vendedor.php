<?php
session_start();
include("connection.php");

// 1. SEGURIDAD: Solo usuarios logueados
if (!isset($_SESSION['username'])) {
    header("Location: index.php?error=no_sesion");
    exit();
}

$user_actual = $_SESSION['username'];

// 2. CONSULTAS RÁPIDAS
// Cambio: Usamos 'fecha_venta' en lugar de 'fecha' para que coincida con la DB
$fecha_hoy = date('Y-m-d');
$sql_ventas = "SELECT COUNT(*) as total FROM ventas WHERE DATE(fecha_venta) = '$fecha_hoy'";
$res_mis_ventas = mysqli_query($conn, $sql_ventas);

// Verificación de seguridad para evitar el Fatal Error
if($res_mis_ventas){
    $mis_ventas_hoy = mysqli_fetch_array($res_mis_ventas)['total'];
} else {
    $mis_ventas_hoy = 0; // Si falla la consulta, muestra 0 en lugar de error
}

// Consultar stock bajo (Se mantiene igual, solo aseguramos que la tabla 'productos' exista)
$res_alerta = mysqli_query($conn, "SELECT COUNT(*) as bajo FROM productos WHERE stock > 0 AND stock <= 3");
if($res_alerta){
    $alertas_stock = mysqli_fetch_array($res_alerta)['bajo'];
} else {
    $alertas_stock = 0;
}

include("header.php");
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12 text-center text-md-start">
            <h2 class="fw-bold" style="color: #1A2B4C;">¡Hola, <?= ucfirst($user_actual) ?>! 👋</h2>
            <p class="text-muted">Panel de Operaciones - <strong>Unideportes Store</strong></p>
        </div>
    </div>

    <div class="row g-3 mb-5">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 text-white" style="background-color: #28a745; border-radius: 15px;">
                <small class="opacity-75 text-uppercase fw-bold">Ventas del Día (Global)</small>
                <h3 class="fw-bold"><?= $mis_ventas_hoy ?> Realizadas</h3>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 text-white" style="background-color: #ffc107; border-radius: 15px;">
                <small class="opacity-75 text-uppercase fw-bold text-dark">Alertas de Inventario</small>
                <h3 class="fw-bold text-dark"><?= $alertas_stock ?> Por agotarse</h3>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-md-4">
            <a href="nueva_venta.php" class="text-decoration-none">
                <div class="card h-100 shadow border-0 p-4 module-card text-center" style="border-top: 5px solid #E61E2A;">
                    <div class="fs-1 mb-2">💵</div>
                    <h4 class="fw-bold text-dark">Nueva Venta</h4>
                    <p class="small text-muted">Facturar a cliente.</p>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4">
            <a href="clientes.php" class="text-decoration-none">
                <div class="card h-100 shadow-sm border-0 p-4 module-card text-center">
                    <div class="fs-1 mb-2">🏆</div>
                    <h5 class="fw-bold text-dark">Clientes</h5>
                    <p class="small text-muted">Directorio y registros.</p>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4">
            <a href="inventario.php" class="text-decoration-none">
                <div class="card h-100 shadow-sm border-0 p-4 module-card text-center">
                    <div class="fs-1 mb-2">📦</div>
                    <h5 class="fw-bold text-dark">Stock</h5>
                    <p class="small text-muted">Consultar tallas.</p>
                </div>
            </a>
        </div>
    </div>
</div>

<style>
    .module-card {
        border-radius: 20px;
        transition: all 0.3s ease;
    }
    .module-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
</style>

<?php include("footer.php"); ?>