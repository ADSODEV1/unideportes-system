<?php
// views/pedido_exitoso.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['vendedor', 'colaborador', 'admin']);

$pdo = app();
$conn = connection(); // Usamos tu función nativa de conexión

$rol_usuario = $_SESSION['role'] ?? '';
$usuario_nombre = $_SESSION['username'] ?? 'Usuario';
$pagina_actual = basename($_SERVER['PHP_SELF']);
$base = "/unideportes-system";

// Validar que llegue el ID del pedido
$pedido_id = !empty($_GET['id']) ? intval($_GET['id']) : 0;
if ($pedido_id === 0) {
    header("Location: linea_confeccion.php");
    exit();
}

// 1. Obtener datos del pedido y del cliente utilizando PDO (tu estándar)
try {
    $stmt = $conn->prepare("SELECT p.*, c.nombre as cliente_nombre, c.telefono, c.email 
                            FROM pedidos p 
                            LEFT JOIN clientes c ON p.cliente_id = c.id 
                            WHERE p.id = ?");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        die("El pedido solicitado no existe.");
    }

    // 2. Obtener el detalle de los productos del taller
    $stmtD = $conn->prepare("SELECT dp.*, prod.nombre as producto_nombre 
                             FROM detalle_pedido dp
                             LEFT JOIN productos prod ON dp.producto_id = prod.id
                             WHERE dp.pedido_id = ?");
    $stmtD->execute([$pedido_id]);
    $detalles = $stmtD->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error al consultar la base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unideportes - Pedido Registrado #<?= $pedido_id ?></title>
    <link rel="stylesheet" href="/unideportes-system/assets/CSS/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        /* Ajuste para que tu menú original no se altere por Bootstrap */
        .main-header a { text-decoration: none; }
        .nav-list { padding-left: 0; margin-bottom: 0; }
    </style>
</head>
<body class="bg-light">

<header class="main-header bg-dark text-white">
    <div class="nav-container d-flex justify-content-between align-items-center px-4 py-2">
        <a class="logo text-white" href="<?= ($rol_usuario == 'admin') ? 'panel_admin.php' : 'panel_vendedor.php' ?>">
            <img src="/unideportes-system/assets/imagenes/logo-unideportes.png" alt="Logo Unideportes" class="logo-img" style="height:40px;">
            UNI<span style="color: var(--primary, #007bff);">DEPORTES</span>
        </a>
        <nav class="main-nav">
            <ul class="nav-list d-flex list-unstyled gap-3 mb-0">
                <li><a href="inventario.php" class="text-white">Inventario</a></li>
                <li><a href="mis_pedidos.php" class="text-white">Producción</a></li>
                <li><a href="clientes.php" class="text-white">Clientes</a></li>
                <li><a href="reportes_ventas.php" class="text-white">Reportes</a></li>
                <?php if ($rol_usuario == 'admin'): ?>
                    <li><a href="admin_user.php" class="text-white">Personal</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="user-info text-white">
            <span>Hola, <strong><?= ucfirst($usuario_nombre) ?></strong></span>
            <a href="/unideportes-system/controllers/auth.php?logout=1" class="btn btn-sm btn-danger ms-2">Salir</a>
        </div>
    </div>
</header>

<div class="container my-5">
    <div class="card shadow-lg border-0 mx-auto" style="max-width: 750px;">
        <div class="card-header bg-success text-white text-center py-4">
            <h2 class="mb-1">¡Pedido Registrado con Éxito! 🎉</h2>
            <p class="mb-0">La orden ha sido enviada a la Línea de Confección</p>
        </div>
        <div class="card-body p-4 text-dark">
            
            <div class="row text-center mb-4 bg-white p-3 rounded border mx-0 shadow-sm">
                <div class="col-4 border-end">
                    <small class="text-muted text-uppercase d-block">Total</small>
                    <strong class="fs-4 text-dark">$<?= number_format($pedido['total_pedido'], 2); ?></strong>
                </div>
                <div class="col-4 border-end">
                    <small class="text-muted text-uppercase d-block">Abono</small>
                    <strong class="fs-4 text-success">$<?= number_format($pedido['abono'], 2); ?></strong>
                </div>
                <div class="col-4">
                    <small class="text-muted text-uppercase d-block">Saldo Pendiente</small>
                    <strong class="fs-4 text-danger">$<?= number_format($pedido['saldo_pendiente'], 2); ?></strong>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Datos del Cliente</h5>
                    <p class="mb-1"><strong>Nombre:</strong> <?= htmlspecialchars($pedido['cliente_nombre'] ?? 'Cliente General'); ?></p>
                    <p class="mb-1"><strong>Teléfono:</strong> <?= htmlspecialchars($pedido['telefono'] ?? 'N/A'); ?></p>
                </div>
                <div class="col-md-6">
                    <h5>Datos de Entrega</h5>
                    <p class="mb-1"><strong>Tipo:</strong> <?= htmlspecialchars($pedido['tipo_entrega']); ?></p>
                    <p class="mb-1"><strong>Fecha Estimada:</strong> <?= date('d/m/Y', strtotime($pedido['fecha_entrega'])); ?></p>
                </div>
            </div>

            <h5>Detalle de Fabricación (Taller)</h5>
            <div class="table-responsive mb-4">
                <table class="table table-bordered bg-white align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Talla</th>
                            <th class="text-center">Color</th>
                            <th class="text-center">Cant.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($detalles as $row): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($row['producto_nombre']); ?></strong>
                                <?php if(!empty($row['comentario_vendedor'])): ?>
                                    <small class="text-muted d-block">* Nota: <?= htmlspecialchars($row['comentario_vendedor']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><span class="badge bg-secondary"><?= htmlspecialchars($row['talla']); ?></span></td>
                            <td class="text-center"><?= htmlspecialchars($row['color']); ?></td>
                            <td class="text-center"><?= $row['cantidad']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end pt-3 border-top">
                <button onclick="window.print();" class="btn btn-outline-primary">Imprimir Ticket 🖨️</button>
                <a href="linea_confeccion.php" class="btn btn-success">Nueva Orden ➕</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>