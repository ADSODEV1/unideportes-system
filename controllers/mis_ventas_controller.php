<?php
// controllers/mis_ventas_controler.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['vendedor', 'colaborador', 'admin']);

// Inicializar conexión
$pdo = app();
$vendedorId = $_SESSION['id']; 

// Instanciar Modelo
require_once __DIR__ . '/../models/VentaModel.php';
$ventaModel = new VentaModel($pdo);

// 1. CONTROL DE RANGOS DE FECHAS (Por defecto: Mes Actual)
// Si el usuario envía fechas específicas por formulario (GET), las usa; si no, calcula el mes actual.
$fecha_inicio = !empty($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01'); // Primer día del mes
$fecha_fin    = !empty($_GET['fecha_fin'])    ? $_GET['fecha_fin']    : date('Y-m-t');  // Último día del mes

// 2. OBTENER DATOS FILTRADOS POR RANGO
// Pasamos las fechas como argumentos para que el modelo haga la magia en la consulta SQL
$ventas  = $ventaModel->getVentasByVendedorYFecha($vendedorId, $fecha_inicio, $fecha_fin);
$resumen = $ventaModel->getResumenVendedorYFecha($vendedorId, $fecha_inicio, $fecha_fin);

// Cargar vista
include __DIR__ . '/../views/mis_ventas.php';