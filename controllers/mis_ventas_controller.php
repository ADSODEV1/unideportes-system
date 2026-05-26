<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['vendedor', 'colaborador', 'admin']);

// Inicializar conexión
$pdo = app();
$vendedorId = $_SESSION['id']; // Asumo que guardas el ID en la sesión al loguear

// Instanciar Modelo
require_once __DIR__ . '/../models/VentaModel.php';
$ventaModel = new VentaModel($pdo);

// Obtener datos
$ventas = $ventaModel->getVentasByVendedor($vendedorId);
$resumen = $ventaModel->getResumenVendedor($vendedorId);

// Cargar vista
include __DIR__ . '/../views/mis_ventas.php';