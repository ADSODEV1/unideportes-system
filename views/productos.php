<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['admin']);

$error = '';

if (!empty($_GET['error'])) {
    if ($_GET['error'] === 'datos_invalidos') {
        $error = 'Por favor completa todos los campos requeridos correctamente.';
    } elseif ($_GET['error'] === 'referencia_duplicada') {
        $error = 'Ya existe un producto con esa referencia.';
    } elseif ($_GET['error'] === 'fallo_en_registro') {
        $error = 'No se pudo registrar el producto. Intenta de nuevo.';
    }
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">
        <div class="page-header">
            <h1>Nueva Mercancía</h1>
            <p>Ingrese los detalles de la prenda que desea registrar.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="users-form">
            <form action="../controllers/insert_product.php" method="POST">
                <label>Nombre de la prenda</label>
                <input type="text" name="nombre" required>

                <label>Referencia / Código</label>
                <input type="text" name="referencia" required>

                <label>Talla</label>
                <select name="talla">
                    <option value="S">S</option>
                    <option value="M" selected>M</option>
                    <option value="L">L</option>
                    <option value="XL">XL</option>
                    <option value="Unica">Única</option>
                </select>

                <label>Stock inicial</label>
                <input type="number" name="stock" value="0" min="0" required>

                <label>Precio</label>
                <input type="number" name="precio" step="0.01" min="0" required>

                <button type="submit" class="btn-principal">Registrar producto</button>
            </form>

            <a href="inventario.php" class="btn-secondary" style="margin-top: 20px; display: inline-block;">← Volver al inventario</a>
        </div>
    </main>
</div>

<?php include(__DIR__ . "/footer.php"); ?>