<?php
session_start();
require_once __DIR__ . '/../config/connection.php';
$conn = connection();

// 1. SEGURIDAD: Solo vendedores/colaboradores pueden vender
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'] ?? '', ['vendedor', 'colaborador', 'admin'], true)) {
    header("Location: /unideportes-system/public/index.php?error=acceso_denegado");
    exit();
}

// 2. OBTENER CLIENTES PARA EL SELECTOR
$res_clientes = mysqli_query($conn, "SELECT id, nombre_completo FROM clientes ORDER BY nombre_completo ASC");

// 3. OBTENER PRODUCTOS CON STOCK
$res_productos = mysqli_query($conn, "SELECT id, nombre, referencia, precio, stock FROM productos WHERE stock > 0 ORDER BY nombre ASC");
$productos_json = [];
while ($prod = mysqli_fetch_assoc($res_productos)) {
    $productos_json[] = $prod;
}

include(__DIR__ . "/header.php");
?>

<div class="venta-container">
    <h1>Nueva Venta</h1>
    <p>Registra la venta de productos y el sistema descuenta automáticamente del inventario.</p>

    <form action="/unideportes-system/controllers/procesar_venta.php" method="POST" id="ventaForm">
        
        <div class="venta-section">
            <h3>Datos del Cliente</h3>
            <label>Cliente:</label>
            <select name="cliente_id" id="clienteSelect" required>
                <option value="">-- Seleccione un cliente --</option>
                <?php while($cli = mysqli_fetch_array($res_clientes)): ?>
                    <option value="<?= $cli['id'] ?>"><?= htmlspecialchars($cli['nombre_completo']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="venta-section">
            <h3>Productos a Vender</h3>
            <table id="productosTable" class="tabla-venta">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Referencia</th>
                        <th>Precio Unitario</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="productosBody">
                    <!-- Las filas se agregan con JavaScript -->
                </tbody>
            </table>

            <div class="agregar-producto">
                <select id="productoSelect">
                    <option value="">-- Agregar producto --</option>
                    <?php foreach($productos_json as $prod): ?>
                        <option value="<?= $prod['id'] ?>" 
                                data-nombre="<?= htmlspecialchars($prod['nombre']) ?>" 
                                data-referencia="<?= htmlspecialchars($prod['referencia']) ?>" 
                                data-precio="<?= $prod['precio'] ?>"
                                data-stock="<?= $prod['stock'] ?>">
                            <?= htmlspecialchars($prod['nombre']) ?> - Stock: <?= $prod['stock'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="agregarBtn" class="btn-agregar">+ Agregar Producto</button>
            </div>
        </div>

        <div class="venta-resumen">
            <h3>Resumen de Venta</h3>
            <p>Subtotal: <strong id="subtotalVenta">$0</strong></p>
            <p>Descuento (%): 
                <input type="number" id="descuentoPct" name="descuento_pct" value="0" min="0" max="100" step="0.01">
                = <strong id="descuentoMonto">$0</strong>
            </p>
            <hr>
            <p style="font-size: 1.3rem;">Total: <strong id="totalVenta">$0</strong></p>
        </div>

        <input type="hidden" id="ventaJSON" name="venta_json" value="">
        <input type="hidden" id="totalVentaInput" name="total_venta" value="">

        <button type="submit" class="btn-finalizar">✅ FINALIZAR VENTA</button>
        <a href="panel_vendedor.php" class="btn-cancelar">← Cancelar</a>
    </form>
</div>

<style>
.venta-container {
    max-width: 1000px;
    margin: 20px auto;
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.venta-section {
    margin-bottom: 25px;
}

.venta-section h3 {
    color: #1A2B4C;
    margin-bottom: 15px;
    border-bottom: 2px solid #E61E2A;
    padding-bottom: 10px;
}

.venta-section select,
.venta-section input {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    margin-bottom: 10px;
    font-size: 0.95rem;
}

.tabla-venta {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
}

.tabla-venta th {
    background: #1A2B4C;
    color: #fff;
    padding: 12px;
    text-align: left;
}

.tabla-venta td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}

.tabla-venta input {
    width: 90%;
    padding: 6px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.agregar-producto {
    display: flex;
    gap: 10px;
}

.agregar-producto select {
    flex: 1;
}

.btn-agregar {
    padding: 10px 16px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.btn-agregar:hover {
    background: #059669;
}

.btn-eliminar {
    padding: 6px 12px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.btn-eliminar:hover {
    background: #dc2626;
}

.venta-resumen {
    background: #f8fafc;
    padding: 18px;
    border-radius: 10px;
    margin: 20px 0;
}

.venta-resumen p {
    margin: 10px 0;
    font-size: 1.05rem;
}

.venta-resumen input {
    width: 80px;
    padding: 6px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.btn-finalizar,
.btn-cancelar {
    display: inline-block;
    padding: 12px 24px;
    margin: 10px 10px 10px 0;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    text-align: center;
}

.btn-finalizar {
    background: var(--primary);
    color: white;
}

.btn-finalizar:hover {
    background: #c91a25;
}

.btn-cancelar {
    background: #9ca3af;
    color: white;
}
</style>

<script>
const productosDisponibles = <?php echo json_encode($productos_json); ?>;
let filaContador = 0;

document.getElementById('agregarBtn').addEventListener('click', function(e) {
    e.preventDefault();
    const select = document.getElementById('productoSelect');
    const productoId = select.value;
    
    if (!productoId) {
        alert('Por favor, selecciona un producto');
        return;
    }
    
    const opcion = select.options[select.selectedIndex];
    const producto = {
        id: productoId,
        nombre: opcion.dataset.nombre,
        referencia: opcion.dataset.referencia,
        precio: parseFloat(opcion.dataset.precio),
        stock: parseInt(opcion.dataset.stock)
    };
    
    agregarFila(producto);
    select.value = '';
});

function agregarFila(producto) {
    const tbody = document.getElementById('productosBody');
    const fila = document.createElement('tr');
    fila.id = 'fila-' + filaContador;
    filaContador++;
    
    fila.innerHTML = `
        <td>${producto.nombre}</td>
        <td>${producto.referencia}</td>
        <td>$${parseFloat(producto.precio).toFixed(2)}</td>
        <td>
            <input type="number" class="cantidad" value="1" min="1" max="${producto.stock}" 
                   data-precio="${producto.precio}" data-id="${producto.id}">
        </td>
        <td class="subtotal">$${parseFloat(producto.precio).toFixed(2)}</td>
        <td>
            <button type="button" class="btn-eliminar" onclick="eliminarFila('${fila.id}')">Eliminar</button>
        </td>
    `;
    
    tbody.appendChild(fila);
    
    fila.querySelector('.cantidad').addEventListener('change', actualizarTotales);
    actualizarTotales();
}

function eliminarFila(filaId) {
    document.getElementById(filaId).remove();
    actualizarTotales();
}

function actualizarTotales() {
    let subtotal = 0;
    document.querySelectorAll('#productosBody tr').forEach(fila => {
        const cantidad = parseInt(fila.querySelector('.cantidad').value) || 0;
        const precio = parseFloat(fila.querySelector('.cantidad').dataset.precio);
        const subtotalFila = cantidad * precio;
        fila.querySelector('.subtotal').textContent = '$' + subtotalFila.toFixed(2);
        subtotal += subtotalFila;
    });
    
    const descuentoPct = parseFloat(document.getElementById('descuentoPct').value) || 0;
    const descuentoMonto = (subtotal * descuentoPct) / 100;
    const total = subtotal - descuentoMonto;
    
    document.getElementById('subtotalVenta').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('descuentoMonto').textContent = '$' + descuentoMonto.toFixed(2);
    document.getElementById('totalVenta').textContent = '$' + total.toFixed(2);
    document.getElementById('totalVentaInput').value = total.toFixed(2);
}

document.getElementById('descuentoPct').addEventListener('change', actualizarTotales);

document.getElementById('ventaForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!document.getElementById('clienteSelect').value) {
        alert('Selecciona un cliente');
        return;
    }
    
    const filas = document.querySelectorAll('#productosBody tr');
    if (filas.length === 0) {
        alert('Agrega al menos un producto');
        return;
    }
    
    const detalles = [];
    filas.forEach(fila => {
        const cantidad = parseInt(fila.querySelector('.cantidad').value);
        const precio = parseFloat(fila.querySelector('.cantidad').dataset.precio);
        const productoId = fila.querySelector('.cantidad').dataset.id;
        
        detalles.push({
            producto_id: productoId,
            cantidad: cantidad,
            precio_unitario: precio,
            subtotal: cantidad * precio
        });
    });
    
    document.getElementById('ventaJSON').value = JSON.stringify(detalles);
    this.submit();
});
</script>

<?php include(__DIR__ . "/footer.php"); ?>
