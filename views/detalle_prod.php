<?php
session_start();
require_once __DIR__ . '/../config/connection.php';
$conn = connection();

// Seguridad básica: sesión activa requerida
if (!isset($_SESSION['username'])) {
    header('Location: /unideportes-system/public/index.php?error=acceso_denegado');
    exit();
}

// Obtener ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: inventario.php?error=id_invalido');
    exit();
}

$id = intval($_GET['id']);

// Consultar producto
$sql = 'SELECT * FROM productos WHERE id = ?';
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: inventario.php?error=producto_no_encontrado');
    exit();
}

$producto = $result->fetch_assoc();

include(__DIR__ . '/header.php');
?>

<div class="container admin-layout">
    <aside class="sidebar-panel">
        <div class="sidebar-section">
            <h3>Producto</h3>
            <p><strong><?= htmlspecialchars($producto['nombre']) ?></strong></p>
            <p>Ref: <?= htmlspecialchars($producto['referencia']) ?></p>
        </div>
    </aside>

    <main class="main-content-panel">
        <h1>Detalle de Producto</h1>
        <div class="detalle-producto-card">
            <div class="detalle-grid">
                <div>
                    <p><strong>Nombre:</strong></p>
                    <p><?= htmlspecialchars($producto['nombre']) ?></p>
                </div>
                <div>
                    <p><strong>Referencia:</strong></p>
                    <p><?= htmlspecialchars($producto['referencia']) ?></p>
                </div>
                <div>
                    <p><strong>Talla:</strong></p>
                    <p><?= htmlspecialchars($producto['talla'] ?: 'N/A') ?></p>
                </div>
                <div>
                    <p><strong>Stock:</strong></p>
                    <p><?= intval($producto['stock']) ?></p>
                </div>
                <div>
                    <p><strong>Precio:</strong></p>
                    <p>$<?= number_format($producto['precio'], 2, ',', '.') ?></p>
                </div>
                <div>
                    <p><strong>Creado:</strong></p>
                    <p><?= htmlspecialchars($producto['created_at']) ?></p>
                </div>
            </div>

            <div class="detalle-actions">
                <a href="inventario.php" class="btn-cancelar">← Volver al Inventario</a>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <a href="editar_prod.php?id=<?= $producto['id'] ?>" class="btn-finalizar">✎ Editar producto</a>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<style>
.detalle-producto-card {
    background: #fff;
    padding: 24px;
    border-radius: 14px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

.detalle-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 18px;
    margin-bottom: 24px;
}

.detalle-grid div {
    padding: 16px;
    background: #f8fafc;
    border-radius: 12px;
}

.detalle-grid p {
    margin: 0;
}

.detalle-grid p:first-child {
    color: #6b7280;
    font-size: 0.9rem;
}

.detalle-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn-finalizar,
.btn-cancelar {
    display: inline-block;
    padding: 12px 20px;
    border-radius: 10px;
    text-decoration: none;
    color: white;
    font-weight: 700;
}

.btn-finalizar {
    background: var(--primary);
}

.btn-cancelar {
    background: #6b7280;
}
</style>

<?php include(__DIR__ . '/footer.php'); ?>