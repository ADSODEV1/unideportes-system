<?php
session_start();
include("connection.php");

// 1. SEGURIDAD: Cualquier usuario autenticado puede realizar ventas
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include("header.php");

// 2. CONSULTAS: Traemos los datos necesarios
// Solo traemos productos con stock disponible
$query_clientes  = mysqli_query($conn, "SELECT id, nombre_completo FROM clientes ORDER BY nombre_completo ASC");
$query_productos = mysqli_query($conn, "SELECT id, nombre, precio, stock, talla FROM productos WHERE stock > 0 ORDER BY nombre ASC");
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg" style="border-radius: 20px;">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold" style="color: #1A2B4C;">💰 Registrar Nueva Venta</h2>
                        <p class="text-muted">Complete los datos para la transacción de Unideportes.</p>
                    </div>

                    <form action="procesar_venta.php" method="POST">
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-uppercase">1. Seleccionar Cliente</label>
                            <select name="cliente_id" class="form-select border-0 bg-light" style="border-radius: 12px;" required>
                                <option value="" disabled selected>-- Buscar Cliente --</option>
                                <?php while($c = mysqli_fetch_array($query_clientes)): ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['nombre_completo'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold text-uppercase">2. Producto / Prenda</label>
                                <select name="producto_id" class="form-select border-0 bg-light" style="border-radius: 12px;" required>
                                    <option value="" disabled selected>-- Seleccionar Prenda --</option>
                                    <?php while($p = mysqli_fetch_array($query_productos)): ?>
                                        <option value="<?= $p['id'] ?>">
                                            <?= $p['nombre'] ?> (Talla: <?= $p['talla'] ?>) - $<?= number_format($p['precio'], 0, ',', '.') ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">3. Cantidad</label>
                                <input type="number" name="cantidad" min="1" class="form-control border-0 bg-light" style="border-radius: 12px;" placeholder="0" required>
                            </div>
                        </div>

                        <hr class="my-4 text-muted">

                        <div class="alert alert-info border-0 small" style="border-radius: 10px;">
                            <strong>Nota:</strong> Al procesar, el sistema restará automáticamente las unidades del inventario y registrará la fecha actual.
                        </div>

                        <button type="submit" class="btn btn-lg w-100 text-white fw-bold shadow-sm" style="background-color: #E61E2A; border-radius: 15px; padding: 15px;">
                            CONFIRMAR VENTA
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>