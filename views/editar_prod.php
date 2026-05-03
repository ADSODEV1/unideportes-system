<?php
session_start();
require_once __DIR__ . '/../config/connection.php';
$conn = connection();

// Seguridad: Solo admin puede editar producto
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /unideportes-system/public/index.php?error=acceso_denegado');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: inventario.php?error=id_invalido');
    exit();
}

$id = intval($_GET['id']);

// Obtener datos del producto
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
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre'] ?? '');
    $referencia = mysqli_real_escape_string($conn, $_POST['referencia'] ?? '');
    $talla = mysqli_real_escape_string($conn, $_POST['talla'] ?? '');
    $stock = intval($_POST['stock'] ?? 0);
    $precio = floatval($_POST['precio'] ?? 0);

    if ($nombre === '' || $referencia === '' || $precio <= 0) {
        $error = 'Nombre, referencia y precio son obligatorios.';
    } else {
        $sql_check = 'SELECT id FROM productos WHERE referencia = ? AND id != ?';
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param('si', $referencia, $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error = 'Ya existe un producto con la misma referencia.';
        } else {
            $sql_update = 'UPDATE productos SET nombre = ?, referencia = ?, talla = ?, stock = ?, precio = ? WHERE id = ?';
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param('sssidi', $nombre, $referencia, $talla, $stock, $precio, $id);

            if ($stmt_update->execute()) {
                header('Location: inventario.php?success=producto_actualizado');
                exit();
            }

            $error = 'Error al actualizar el producto: ' . $conn->error;
        }
    }

    // Mantener datos enviados para no perderlos
    $producto['nombre'] = $nombre;
    $producto['referencia'] = $referencia;
    $producto['talla'] = $talla;
    $producto['stock'] = $stock;
    $producto['precio'] = $precio;
}

include(__DIR__ . '/header.php');
?>

<div class="container admin-layout">
    <aside class="sidebar-panel">
        <div class="sidebar-section">
            <h3>Editar producto</h3>
            <p>ID: <?= $producto['id'] ?></p>
            <p>Ref: <?= htmlspecialchars($producto['referencia']) ?></p>
        </div>
    </aside>

    <main class="main-content-panel">
        <h1>✏️ Editar Producto</h1>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="users-form">
            <form action="editar_prod.php?id=<?= $producto['id'] ?>" method="POST">
                <label>Nombre:</label>
                <input type="text" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>" required>

                <label>Referencia:</label>
                <input type="text" name="referencia" value="<?= htmlspecialchars($producto['referencia']) ?>" required>

                <label>Talla:</label>
                <input type="text" name="talla" value="<?= htmlspecialchars($producto['talla']) ?>">

                <label>Stock:</label>
                <input type="number" name="stock" value="<?= intval($producto['stock']) ?>" min="0" required>

                <label>Precio:</label>
                <input type="number" step="0.01" name="precio" value="<?= htmlspecialchars($producto['precio']) ?>" min="0" required>

                <button type="submit" class="btn-guardar">Guardar cambios</button>
                <a href="inventario.php" class="btn-cancelar">← Volver al inventario</a>
            </form>
        </div>
    </main>
</div>

<style>
.btn-guardar,
.btn-cancelar {
    display: inline-block;
    padding: 12px 20px;
    margin: 10px 10px 10px 0;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    text-align: center;
}

.btn-guardar {
    background: var(--primary);
    color: white;
}

.btn-cancelar {
    background: #9ca3af;
    color: white;
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #dc2626;
}
</style>

<?php include(__DIR__ . '/footer.php'); ?>