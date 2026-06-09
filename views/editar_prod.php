<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ProductoModel.php';
require_login(['admin']);
$conn = app();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: inventario.php?error=id_invalido');
    exit();
}

$id = intval($_GET['id']);
$producto = obtenerProductoPorId($conn, $id);
if (!$producto) {
    header('Location: inventario.php?error=producto_no_encontrado');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'referencia' => trim($_POST['referencia'] ?? ''),
        'categoria' => trim($_POST['categoria'] ?? ''),
        'color' => trim($_POST['color'] ?? ''),
        'material' => trim($_POST['material'] ?? ''),
        'genero' => trim($_POST['genero'] ?? 'Unisex'),
        'estado' => trim($_POST['estado'] ?? 'activo'),
        'descripcion' => trim($_POST['descripcion'] ?? ''),
        'talla' => trim($_POST['talla'] ?? ''),
        'stock' => intval($_POST['stock'] ?? 0),
        'precio' => floatval($_POST['precio'] ?? 0),
    ];

    if ($data['nombre'] === '' || $data['referencia'] === '' || $data['precio'] <= 0) {
        $error = 'Nombre, referencia y precio son obligatorios.';
    } elseif (existeReferenciaProducto($conn, $data['referencia'], $id)) {
        $error = 'Ya existe un producto con la misma referencia.';
    } elseif (!actualizarProducto($conn, $id, $data)) {
        $error = 'Error al actualizar el producto.';
    } else {
        header('Location: inventario.php?success=producto_actualizado');
        exit();
    }

    $producto = array_merge($producto, $data);
}

include(__DIR__ . '/header.php');
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>

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

                <label>Categoría / Línea:</label>
                <input type="text" name="categoria" value="<?= htmlspecialchars($producto['categoria'] ?? '') ?>">

                <label>Color:</label>
                <input type="text" name="color" value="<?= htmlspecialchars($producto['color'] ?? '') ?>">

                <label>Material:</label>
                <input type="text" name="material" value="<?= htmlspecialchars($producto['material'] ?? '') ?>">

                <label>Género:</label>
                <select name="genero">
                    <option value="Unisex" <?= ($producto['genero'] ?? '') === 'Unisex' ? 'selected' : '' ?>>Unisex</option>
                    <option value="Hombre" <?= ($producto['genero'] ?? '') === 'Hombre' ? 'selected' : '' ?>>Hombre</option>
                    <option value="Mujer" <?= ($producto['genero'] ?? '') === 'Mujer' ? 'selected' : '' ?>>Mujer</option>
                </select>

                <label>Estado:</label>
                <select name="estado">
                    <option value="activo" <?= ($producto['estado'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo</option>
                    <option value="inactivo" <?= ($producto['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                </select>

                <label>Descripción:</label>
                <textarea name="descripcion" rows="3"><?= htmlspecialchars($producto['descripcion'] ?? '') ?></textarea>

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