<?php
session_start();
include("connection.php");

// 1. SEGURIDAD: Solo el Administrador puede ver el Panel Maestro
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php?error=acceso_denegado");
    exit();
}

// 2. CONSULTAS DINÁMICAS (KPIs)
// Contar colaboradores activos
$res_users = mysqli_query($conn, "SELECT COUNT(*) as total FROM usuarios WHERE role = 'colaborador'");
$total_colab = mysqli_fetch_array($res_users)['total'];

// Sumar total de ventas (Ejemplo de consulta de suma)
$res_ventas = mysqli_query($conn, "SELECT SUM(total) as ingresos FROM ventas"); 
$ingresos_data = mysqli_fetch_array($res_ventas);
$total_ingresos = $ingresos_data['ingresos'] ?? 0;

include("header.php");
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12 text-center text-md-start">
            <h2 class="fw-bold" style="color: #1A2B4C;">Panel Maestro Administrativo 🚀</h2>
            <p class="text-muted">Gestión global de la fábrica y tienda <strong>Unideportes</strong>.</p>
        </div>
    </div>

    <div class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 text-white" style="background-color: #1A2B4C; border-radius: 15px;">
                <small class="opacity-75 text-uppercase fw-bold">Ventas Totales</small>
                <h3 class="fw-bold">$<?= number_format($total_ingresos, 0, ',', '.') ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 text-white" style="background-color: #E61E2A; border-radius: 15px;">
                <small class="opacity-75 text-uppercase fw-bold">Pedidos en Confección</small>
                <h3 class="fw-bold">48 Ordenes</h3> </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 text-white" style="background-color: #333333; border-radius: 15px;">
                <small class="opacity-75 text-uppercase fw-bold">Colaboradores</small>
                <h3 class="fw-bold"><?= $total_colab ?> Activos</h3>
            </div>
        </div>
    </div>

    <div class="row g-4 justify-content-center">
        
        <div class="col-6 col-lg-3 text-center">
            <a href="admin_user.php" class="text-decoration-none">
                <div class="card h-100 shadow-sm border-0 p-4 module-card">
                    <div class="fs-1 mb-2">👥</div>
                    <h5 class="fw-bold text-dark">Personal</h5>
                    <p class="small text-muted d-none d-md-block">Roles y accesos.</p>
                </div>
            </a>
        </div>

        <div class="col-6 col-lg-3 text-center">
            <a href="inventario.php" class="text-decoration-none">
                <div class="card h-100 shadow-sm border-0 p-4 module-card">
                    <div class="fs-1 mb-2">📦</div>
                    <h5 class="fw-bold text-dark">Inventario</h5>
                    <p class="small text-muted d-none d-md-block">Stock de prendas.</p>
                </div>
            </a>
        </div>

        <div class="col-6 col-lg-3 text-center">
            <a href="clientes.php" class="text-decoration-none">
                <div class="card h-100 shadow-sm border-0 p-4 module-card">
                    <div class="fs-1 mb-2">🏆</div>
                    <h5 class="fw-bold text-dark">Clientes</h5>
                    <p class="small text-muted d-none d-md-block">Base de datos.</p>
                </div>
            </a>
        </div>

        <div class="col-6 col-lg-3 text-center">
            <a href="reportes.php" class="text-decoration-none">
                <div class="card h-100 shadow-sm border-0 p-4 module-card">
                    <div class="fs-1 mb-2">📊</div>
                    <h5 class="fw-bold text-dark">Ventas</h5>
                    <p class="small text-muted d-none d-md-block">Historial de facturas.</p>
                </div>
            </a>
        </div>

    </div>
</div>

<style>
    .module-card {
        border-radius: 20px;
        transition: transform 0.3s ease, background-color 0.3s ease;
    }
    .module-card:hover {
        background-color: #fcfcfc;
        transform: translateY(-10px);
        border-bottom: 5px solid #E61E2A !important;
    }
</style>

<?php include("footer.php"); ?>