<?php
// controllers/mis_ventas_controller.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['vendedor', 'colaborador', 'admin']);

// Inicializar conexión
$pdo = app();
$vendedorId = $_SESSION['id'] ?? ($_SESSION['user_id'] ?? 1);

// Instanciar Modelo
require_once __DIR__ . '/../models/VentaModel.php';
$ventaModel = new VentaModel($pdo);

// 1. CONTROL DE RANGOS DE FECHAS (Por defecto: Mes Actual)
$fecha_inicio = !empty($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = !empty($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-t');

// 2. OBTENER DATOS FILTRADOS POR RANGO
$ventas = $ventaModel->getVentasByVendedorYFecha($vendedorId, $fecha_inicio, $fecha_fin);
$resumen = $ventaModel->getResumenVendedorYFecha($vendedorId, $fecha_inicio, $fecha_fin);

// Cargar vista
include __DIR__ . '/../views/mis_ventas.php';
