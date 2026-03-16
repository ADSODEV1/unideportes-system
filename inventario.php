<?php
session_start();
include("connection.php");

// 1. SEGURIDAD: Cualquier usuario logueado puede ver el inventario
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// 2. CONSULTAS DE RESUMEN (KPIs)
$total_prod = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as t FROM productos"))['t'];
$stock_bajo = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as b FROM productos WHERE stock > 0 AND stock <= 5"))['b'];
$agotados   = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) as a FROM productos WHERE stock = 0"))['a'];

// 3. OBTENER LISTADO COMPLETO
$query = mysqli_query($conn, "SELECT * FROM productos");

include("header.php");
?>

<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold" style="color: #1A2B4C;">📦 Inventario Unideportes</h2>
            <p class="text-muted">Control de existencias y precios.</p>
        </div>
        <?php if($_SESSION['role'] == 'admin'): ?>
            <a href="productos_nuevo.php" class="btn text-white fw-bold px-4" style="background-color: #E61E2A; border-radius: 10px;">
                + NUEVA PRENDA
            </a>
        <?php endif; ?>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 text-center" style="border-radius: 15px;">
                <small class="text-muted fw-bold">TOTAL REFERENCIAS</small>
                <h4 class="fw-bold mb-0"><?= $total_prod ?></h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 text-center" style="border-radius: 15px; border-bottom: 4px solid #ffc107;">
                <small class="text-muted fw-bold">STOCK BAJO (<=5)</small>
                <h4 class="fw-bold mb-0 text-warning"><?= $stock_bajo ?></h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 text-center" style="border-radius: 15px; border-bottom: 4px solid #dc3545;">
                <small class="text-muted fw-bold">AGOTADOS</small>
                <h4 class="fw-bold mb-0 text-danger"><?= $agotados ?></h4>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
        <div class="card-body py-2">
            <input type="text" id="inputBusqueda" class="form-control border-0 bg-transparent" placeholder="🔍 Buscar por nombre, talla o referencia...">
        </div>
    </div>

    <div class="card shadow-lg border-0" style="border-radius: 20px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead style="background-color: #1A2B4C; color: white;">
                    <tr>
                        <th class="p-4">Producto</th>
                        <th class="p-4 text-center">Talla</th>
                        <th class="p-4 text-center">Disponibilidad</th>
                        <th class="p-4 text-end">Precio</th>
                        <th class="p-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaProductos">
                    <?php while($row = mysqli_fetch_array($query)): ?>
                    <tr>
                        <td class="p-4">
                            <div class="fw-bold"><?= $row['nombre'] ?></div>
                            <code class="text-muted">#<?= $row['referencia'] ?></code>
                        </td>
                        <td class="p-4 text-center">
                            <span class="badge rounded-pill bg-light text-dark border"><?= $row['talla'] ?></span>
                        </td>
                        <td class="p-4 text-center">
                            <?php 
                                $s = $row['stock'];
                                if($s == 0) { $c = "bg-danger"; $m = "AGOTADO"; }
                                elseif($s <= 5) { $c = "bg-warning text-dark"; $m = "BAJO ($s)"; }
                                else { $c = "bg-success"; $m = "STOCK ($s)"; }
                            ?>
                            <span class="badge <?= $c ?> px-3 py-2" style="min-width: 100px;"><?= $m ?></span>
                        </td>
                        <td class="p-4 text-end fw-bold">$<?= number_format($row['precio'], 0, ',', '.') ?></td>
                        <td class="p-4 text-center">
                            <div class="btn-group">
                                <a href="detalle_prod.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary">👁️</a>
                                <?php if($_SESSION['role'] == 'admin'): ?>
                                    <a href="editar_prod.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary">✏️</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Filtro de búsqueda optimizado
document.getElementById('inputBusqueda').addEventListener('keyup', function() {
    let filtro = this.value.toLowerCase();
    let filas = document.querySelectorAll('#tablaProductos tr');

    filas.forEach(fila => {
        let texto = fila.innerText.toLowerCase();
        fila.style.display = texto.includes(filtro) ? "" : "none";
    });
});
</script>

<?php include("footer.php"); ?>