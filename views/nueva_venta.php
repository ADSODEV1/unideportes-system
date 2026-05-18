<?php
session_start();
require_once __DIR__ . '/../config/connection.php';
$conn = connection();

if (!isset($_SESSION['username'])) {
    header("Location: /unideportes-system/public/index.php?error=acceso_denegado");
    exit();
}

$res_clientes = mysqli_query($conn, "SELECT id, nombre_completo FROM clientes ORDER BY nombre_completo ASC");
$res_productos = mysqli_query($conn, "SELECT id, nombre, referencia, precio, stock FROM productos WHERE stock > 0 ORDER BY nombre ASC");

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <!-- SIDEBAR -->
    <aside class="sidebar-panel">
        <div class="sidebar-section">
            <h3>Área Vendedor</h3>
            <p>Vendedor: <strong><?= htmlspecialchars($_SESSION['username']); ?></strong></p>
        </div>
        <nav class="sidebar-nav">
            <a href="panel_vendedor.php" class="nav-link">📊 Panel</a>
            <a href="nueva_venta.php" class="nav-link active">🛒 Nueva Venta</a>
        </nav>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content-panel">
        <h1>Nueva Venta Directa</h1>
        <hr class="divider">

        <form action="../controllers/procesar_venta.php" method="POST" id="ventaForm">
            
            <!-- DATOS DE VENTA -->
            <div class="venta-container" style="display: flex; gap: 20px; margin-bottom: 20px;">
                <!-- Columna Cliente -->
                <div style="flex: 1;">
                    <label><strong>Cliente:</strong></label>
                    <input type="text" list="listaClientes" id="clienteInput" placeholder="Buscar cliente..." required style="width:100%; padding: 8px; margin-top: 5px;">
                    <datalist id="listaClientes">
                        <?php while($cli = mysqli_fetch_assoc($res_clientes)): ?>
                            <option data-id="<?= $cli['id'] ?>" value="<?= htmlspecialchars($cli['nombre_completo']) ?>"></option>
                        <?php endwhile; ?>
                    </datalist>
                    <input type="hidden" name="cliente_id" id="cliente_id_hidden" required>
                </div>
                
                <!-- Columna Método de Pago -->
                <div style="flex: 1;">
                    <label><strong>Método de Pago:</strong></label>
                    <select name="metodo_pago" id="metodo_pago" required style="width:100%; padding: 8px; margin-top: 5px;">
                        <option value="Efectivo">💵 Efectivo</option>
                        <option value="Tarjeta">💳 Tarjeta</option>
                        <option value="Transferencia">📱 Transferencia</option>
                    </select>

                    <!-- SECCIÓN TRANSFERENCIA -->
                    <div id="seccionTransferencia" style="display: none; margin-top: 10px; background: #f8fafc; padding: 10px; border-radius: 4px; border: 1px solid #e2e8f0;">
                        <label><small><strong>Plataforma Virtual:</strong></small></label>
                        <select id="tipo_transferencia_select" style="width:100%; padding: 6px; margin-top: 5px;">
                            <option value="Nequi">Nequi</option>
                            <option value="Daviplata">Daviplata</option>
                            <option value="Otro">Otro ¿Cuál?</option>
                        </select>

                        <!-- Campo dinámico para escribir el nombre de otra plataforma -->
                        <input type="text" id="otra_plataforma_input" placeholder="Ej: Breve, Bancolombia..." style="display: none; width: 100%; padding: 6px; margin-top: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                        
                        <!-- Este input oculto  VIAJARÁ REALMENTE a PHP con el valor final -->
                        <input type="hidden" name="tipo_transferencia" id="tipo_transferencia_final">
                    </div>
                </div>
            </div>

            <!-- AGREGAR PRODUCTO -->
            <div class="venta-container" style="display: flex; gap: 10px; margin-bottom: 20px; align-items: flex-end;">
                <div style="flex: 1;">
                    <label><strong>Producto:</strong></label>
                    <input type="text" list="listaProductos" id="productoInput" placeholder="Buscar producto..." style="width:100%; padding: 8px; margin-top: 5px;">
                    <datalist id="listaProductos">
                        <?php while($prod = mysqli_fetch_assoc($res_productos)): ?>
                            <option value="<?= htmlspecialchars($prod['nombre']) ?> (Ref: <?= $prod['referencia'] ?>)" 
                                    data-id="<?= $prod['id'] ?>" 
                                    data-nombre="<?= htmlspecialchars($prod['nombre']) ?>" 
                                    data-precio="<?= $prod['precio'] ?>" 
                                    data-stock="<?= $prod['stock'] ?>"></option>
                        <?php endwhile; ?>
                    </datalist>
                </div>
                <button type="button" id="btnAgregar" style="padding: 8px 15px; background: #10b981; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">+ Añadir</button>
            </div>

            <!-- TABLA CARRITO -->
            <div class="venta-container" style="margin-bottom: 20px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #1A2B4C; color: white; text-align: left;">
                            <th style="padding: 10px;">Producto</th>
                            <th style="padding: 10px;">Precio</th>
                            <th style="padding: 10px; width: 80px;">Cant</th>
                            <th style="padding: 10px;">Subtotal</th>
                            <th style="padding: 10px; text-align: center;">Quitar</th>
                        </tr>
                    </thead>
                    <tbody id="carritoBody"></tbody>
                </table>
            </div>

            <!-- TOTALES -->
            <div class="venta-container" style="padding: 15px; text-align: right; background: #f8fafc;">
                <h3>Total: <span id="txtTotal" style="color: #E61E2A;">$0.00</span></h3>
                
                <div id="seccionCambio" style="margin-bottom: 15px; text-align: left; width: 250px; margin-left: auto;">
                    <label>Paga con:</label>
                    <input type="number" id="inputPagaCon" style="width: 100%; padding: 6px;" min="0" step="0.01">
                    <h4 style="margin-top: 5px; text-align: right;">Cambio: <span id="txtCambio" style="color: #10b981;">$0.00</span></h4>
                </div>
                
                <input type="hidden" id="ventaJSON" name="venta_json">
                <input type="hidden" id="inputTotal" name="total_venta">

                <a href="panel_vendedor.php" style="margin-right: 15px; color: #666; text-decoration: none;">Cancelar</a>
                <button type="submit" style="padding: 10px 20px; background: #1A2B4C; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">Procesar Venta</button>
            </div>
        </form>
    </main>
</div>

<!-- js-ventas funciones -->
<script src="/unideportes-system/public/js/ventas.js"></script>

<?php include(__DIR__ . "/footer.php"); ?>