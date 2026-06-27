<?php
// views/nueva_venta.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['vendedor', 'colaborador', 'admin']);

$pdo = app();

// CONSULTAS PRELIMINARES
$stmtClientes = $pdo->query("
    SELECT id, nombre_completo, nit_cedula, direccion, barrio, ciudad, referencia_entrega 
    FROM clientes 
    ORDER BY nombre_completo ASC
");
$res_clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

// ✅ CORREGIDO: Ahora trae id, precio y stock
$stmtProductos = $pdo->query("
    SELECT id, nombre, precio, stock 
    FROM productos 
    WHERE stock > 0 
    ORDER BY nombre ASC
");
$res_productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . "/sidebar_control.php"); ?>

    <main class="main-content-panel">
        
        <!-- ENCABEZADO -->
        <div class="page-header">
            <div>
                <h1>Nueva Venta Directa</h1>
                <p>Registra una venta de productos disponibles en inventario.</p>
            </div>
            <a href="panel_vendedor.php" class="btn-secondary">
                ← Volver al Panel
            </a>
        </div>

        <form action="../controllers/procesar_venta.php" method="POST" id="ventaForm">
            
            <!-- SECCIÓN 1: CLIENTE Y MÉTODO DE PAGO -->
            <div class="venta-fila-superior">
                
                <!-- COLUMNA IZQUIERDA: CLIENTE -->
                <div class="venta-col">
                    <h2 class="section-subtitle">Datos del Cliente</h2>
                    
                    <label for="clienteInput">Cliente</label>
                    <input type="text" 
                           list="listaClientes" 
                           id="clienteInput" 
                           placeholder="Buscar cliente..." 
                           class="venta-input">
                    
                    <datalist id="listaClientes">
                        <?php foreach ($res_clientes as $cli): ?>
                            <option 
                                data-id="<?= htmlspecialchars($cli['id']) ?>" 
                                data-direccion="<?= htmlspecialchars($cli['direccion'] ?? '') ?>"
                                data-barrio="<?= htmlspecialchars($cli['barrio'] ?? '') ?>"
                                data-ciudad="<?= htmlspecialchars($cli['ciudad'] ?: 'Sogamoso') ?>"
                                data-referencia="<?= htmlspecialchars($cli['referencia_entrega'] ?? '') ?>"
                                value="<?= htmlspecialchars($cli['nombre_completo']) ?>">
                            </option>
                        <?php endforeach; ?>
                    </datalist>
                    <input type="hidden" name="cliente_id" id="cliente_id_hidden">
                    
                    <div class="venta-actions">
                        <button type="button" id="btnToggleNuevoCliente" class="btn-primary">
                            + Crear cliente nuevo
                        </button>
                        <button type="button" id="btnRecargarClientes" class="btn-outline">
                            🔄 Actualizar lista
                        </button>
                    </div>

                    <!-- Formulario de nuevo cliente -->
                    <div id="nuevoClienteSection" class="venta-section">
                        <h3>Registro Rápido de Cliente</h3>
                        <div class="venta-grid-form">
                            <div>
                                <label for="nuevo_cliente_nombre_completo">Nombre completo *</label>
                                <input type="text" name="nuevo_cliente_nombre_completo" id="nuevo_cliente_nombre_completo" class="venta-input">
                            </div>
                            <div>
                                <label for="nuevo_cliente_nit_cedula">NIT / Cédula *</label>
                                <input type="text" name="nuevo_cliente_nit_cedula" id="nuevo_cliente_nit_cedula" class="venta-input">
                            </div>
                            <div>
                                <label for="nuevo_cliente_telefono">Teléfono</label>
                                <input type="text" name="nuevo_cliente_telefono" id="nuevo_cliente_telefono" class="venta-input">
                            </div>
                            <div>
                                <label for="nuevo_cliente_email">Email</label>
                                <input type="email" name="nuevo_cliente_email" id="nuevo_cliente_email" class="venta-input">
                            </div>
                            <div>
                                <label for="nuevo_cliente_tipo_cliente">Tipo de cliente</label>
                                <select name="nuevo_cliente_tipo_cliente" id="nuevo_cliente_tipo_cliente" class="venta-select">
                                    <option value="Individual">Individual</option>
                                    <option value="Equipo">Equipo</option>
                                    <option value="Colegio">Colegio</option>
                                    <option value="Empresa">Empresa</option>
                                </select>
                            </div>
                        </div>

                        <!-- Dirección del nuevo cliente -->
                        <div id="bloqueDireccionNuevoCliente" class="venta-section">
                            <h3>Dirección Base de Envío</h3>
                            <div class="venta-grid-form">
                                <div>
                                    <label for="nuevo_cliente_direccion">Dirección</label>
                                    <input type="text" name="nuevo_cliente_direccion" id="nuevo_cliente_direccion" class="venta-input" placeholder="Calle, Carrera, #">
                                </div>
                                <div>
                                    <label for="nuevo_cliente_barrio">Barrio</label>
                                    <input type="text" name="nuevo_cliente_barrio" id="nuevo_cliente_barrio" class="venta-input">
                                </div>
                                <div>
                                    <label for="nuevo_cliente_ciudad">Ciudad</label>
                                    <input type="text" name="nuevo_cliente_ciudad" id="nuevo_cliente_ciudad" value="Sogamoso" class="venta-input">
                                </div>
                                <div>
                                    <label for="nuevo_cliente_referencia_entrega">Referencia</label>
                                    <textarea name="nuevo_cliente_referencia_entrega" id="nuevo_cliente_referencia_entrega" rows="2" class="venta-textarea" placeholder="Ej: Frente al parque..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- COLUMNA DERECHA: PAGO Y ENTREGA -->
                <div class="venta-col">
                    <h2 class="section-subtitle">Pago y Entrega</h2>
                    
                    <label for="metodo_pago">Método de Pago</label>
                    <select name="metodo_pago" id="metodo_pago" required class="venta-select">
                        <option value="Efectivo">💵 Efectivo</option>
                        <option value="Tarjeta">💳 Tarjeta</option>
                        <option value="Transferencia">📱 Transferencia</option>
                    </select>

                    <!-- Sección de transferencia -->
                    <div id="seccionTransferencia" class="venta-section">
                        <label for="tipo_transferencia_select">Plataforma Virtual</label>
                        <select id="tipo_transferencia_select" class="venta-select">
                            <option value="Nequi">Nequi</option>
                            <option value="Daviplata">Daviplata</option>
                            <option value="Bancolombia">Bancolombia</option>
                            <option value="Otro">Otro</option>
                        </select>

                        <input type="text" 
                               id="otra_plataforma_input" 
                               placeholder="Ej: Bancolombia..." 
                               class="venta-input venta-hidden">
                        <input type="hidden" name="tipo_transferencia" id="tipo_transferencia_final">
                    </div>

                    <label for="tipo_entrega" class="mt-15">Tipo de Entrega</label>
                    <select name="tipo_entrega" id="tipo_entrega" required class="venta-select">
                        <option value="Tienda">🏪 Retiro en Tienda</option>
                        <option value="Domicilio">🚚 Envío a Domicilio</option>
                    </select>

                    <!-- Sección de domicilio -->
                    <div id="seccionDomicilio" class="venta-section">
                        <h3>Datos de Envío</h3>
                        <div class="venta-grid-form">
                            <div>
                                <label for="direccion_entrega">Dirección de Entrega *</label>
                                <input type="text" name="direccion_entrega" id="direccion_entrega" class="venta-input">
                            </div>
                            <div>
                                <label for="barrio_entrega">Barrio</label>
                                <input type="text" name="barrio_entrega" id="barrio_entrega" class="venta-input">
                            </div>
                            <div>
                                <label for="ciudad_entrega">Ciudad *</label>
                                <input type="text" name="ciudad_entrega" id="ciudad_entrega" value="Sogamoso" class="venta-input">
                            </div>
                            <div>
                                <label for="observaciones_entrega">Observaciones</label>
                                <textarea name="observaciones_entrega" id="observaciones_entrega" rows="2" class="venta-textarea" placeholder="Indicaciones para el domiciliario..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: SELECCIÓN DE PRODUCTOS -->
            <div class="venta-card">
                <h2 class="section-subtitle">Agregar Productos</h2>
                
                <div class="venta-grid-productos">
                    <div>
                        <label for="productoInput">Producto</label>
                        <input type="text" list="listaProductos" id="productoInput" placeholder="Buscar producto..." class="venta-input">
                        <!-- ✅ CORREGIDO: Ahora muestra precio y stock -->
                        <datalist id="listaProductos">
                            <?php foreach ($res_productos as $prod): ?>
                                <option 
                                    value="<?= htmlspecialchars($prod['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-id="<?= $prod['id'] ?>"
                                    data-precio="<?= $prod['precio'] ?>"
                                    data-stock="<?= $prod['stock'] ?>">
                                    Precio: $<?= number_format($prod['precio'], 0, ',', '.') ?> | Stock: <?= $prod['stock'] ?>
                                </option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div id="wrapperProductoColor">
                        <label for="productoColor">Color</label>
                        <input type="text" id="productoColor" placeholder="Selecciona producto" disabled class="venta-input-disabled">
                    </div>

                    <div id="wrapperProductoTalla">
                        <label for="productoTalla">Talla</label>
                        <input type="text" id="productoTalla" placeholder="Selecciona color" disabled class="venta-input-disabled">
                    </div>

                    <div>
                        <label for="productoComentario">Comentario</label>
                        <input type="text" id="productoComentario" placeholder="Ej: Algodón" class="venta-input">
                    </div>

                    <div>
                        <label for="productoCantidad">Cantidad</label>
                        <input type="number" id="productoCantidad" value="1" min="1" class="venta-input-center">
                    </div>

                    <button type="button" id="btnAgregar" class="btn-success">
                        + Añadir
                    </button>
                </div>
            </div>

            <!-- SECCIÓN 3: CARRITO DE COMPRA -->
            <div class="venta-card">
                <h2 class="section-subtitle">Carrito de Compra</h2>
                
                <div class="venta-tabla-wrapper">
                    <table class="venta-tabla">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Color</th>
                                <th>Talla</th>
                                <th>Comentario</th>
                                <th>Precio</th>
                                <th class="venta-th-width">Cant</th>
                                <th>Subtotal</th>
                                <th class="venta-th-center">Quitar</th>
                            </tr>
                        </thead>
                        <tbody id="carritoBody"></tbody>
                    </table>
                </div>
            </div>

            <!-- SECCIÓN 4: TOTAL Y ACCIONES FINALES -->
            <div class="venta-total-section">
                <div class="total-display">
                    <span>Total a pagar:</span>
                    <span id="txtTotal" class="total-amount">$0.00</span>
                </div>
                
                <!-- ✅ NUEVO: Sección de cambio/vuelto -->
                <div id="seccionCambio" class="seccionCambio">
                    <label for="inputPagaCon">💵 Paga con:</label>
                    <input type="number" id="inputPagaCon" name="paga_con" class="venta-input" min="0" step="1000" placeholder="Monto recibido">
                    <div class="cambio-display">
                        <span>Cambio:</span>
                        <span id="txtCambio" class="cambio-amount">$0.00</span>
                    </div>
                </div>
                
                <input type="hidden" id="ventaJSON" name="venta_json">
                <input type="hidden" id="inputTotal" name="total_venta">

                <div class="venta-actions-final">
                    <a href="panel_vendedor.php" class="btn-outline">Cancelar</a>
                    <button type="submit" class="btn-primary">💾 Procesar Venta</button>
                </div>
            </div>
        </form>
    </main>
</div>

<style>
/* ============================================
   NUEVA VENTA - ESTILOS SIMPLIFICADOS
   ============================================ */

/* Layout principal */
.admin-layout {
    display: flex;
    gap: 20px;
}

.main-content-panel {
    flex: 1;
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Encabezado de página */
.page-header {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.page-header h1 {
    color: #1e293b;
    font-size: 1.6rem;
    font-weight: 700;
    margin: 0;
}

.page-header p {
    color: #64748b;
    margin: 5px 0 0 0;
    font-size: 0.95rem;
}

/* Subtítulos de sección */
.section-subtitle {
    color: #475569;
    font-size: 1.05rem;
    font-weight: 600;
    margin: 0 0 15px 0;
    padding-bottom: 8px;
    border-bottom: 2px solid #e2e8f0;
}

/* Tarjetas contenedoras */
.venta-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

/* Fila superior (cliente + pago) */
.venta-fila-superior {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.venta-col {
    flex: 1;
    min-width: 0;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
}

/* Inputs y selects */
.venta-input,
.venta-select,
.venta-textarea {
    width: 100%;
    padding: 8px 10px;
    margin-top: 4px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.95rem;
    background: white;
    transition: border-color 0.2s;
}

.venta-input:focus,
.venta-select:focus,
.venta-textarea:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.venta-input-disabled {
    width: 100%;
    padding: 8px 10px;
    margin-top: 4px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    background: #f1f5f9;
    color: #64748b;
    cursor: not-allowed;
}

.venta-input-center {
    width: 100%;
    padding: 8px 10px;
    margin-top: 4px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    text-align: center;
}

label {
    display: block;
    font-size: 0.9rem;
    font-weight: 500;
    color: #334155;
    margin-top: 8px;
}

.mt-15 { margin-top: 15px; }

.venta-hidden { display: none; }

/* Secciones colapsables */
.venta-section {
    display: none;
    margin-top: 15px;
    padding: 15px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
}

.venta-section h3 {
    margin: 0 0 12px 0;
    color: #1e293b;
    font-size: 1rem;
    font-weight: 600;
}

.venta-grid-form {
    display: grid;
    gap: 10px;
}

/* Botones */
.btn-primary {
    padding: 9px 18px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.95rem;
    text-decoration: none;
    display: inline-block;
    transition: background 0.2s;
}

.btn-primary:hover {
    background: #1d4ed8;
}

.btn-secondary {
    padding: 9px 18px;
    background: #64748b;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.95rem;
    text-decoration: none;
    display: inline-block;
    transition: background 0.2s;
}

.btn-secondary:hover {
    background: #475569;
}

.btn-outline {
    padding: 9px 18px;
    background: white;
    color: #475569;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.95rem;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
}

.btn-outline:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}

.btn-success {
    padding: 8px 15px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-success:hover {
    background: #059669;
}

/* Acciones de cliente */
.venta-actions {
    margin-top: 12px;
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

/* Grid de productos */
.venta-grid-productos {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
    gap: 10px;
    align-items: flex-end;
}

/* Tabla del carrito */
.venta-tabla-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.venta-tabla {
    width: 100%;
    border-collapse: collapse;
    min-width: 700px;
}

.venta-tabla thead tr {
    background: #1e293b;
    color: white;
    text-align: left;
}

.venta-tabla th {
    padding: 10px;
    font-weight: 600;
    font-size: 0.9rem;
}

.venta-tabla td {
    padding: 10px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.9rem;
}

.venta-tabla tbody tr:hover {
    background: #f8fafc;
}

.venta-th-width { width: 80px; }
.venta-th-center { text-align: center; }

/* Total y acciones finales */
.venta-total-section {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    text-align: right;
}

.total-display {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 15px;
    font-size: 1.3rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 15px;
}

.total-amount {
    color: #2563eb;
    font-size: 1.5rem;
}

.seccionCambio {
    display: inline-block;
    text-align: left;
    width: 280px;
    margin-bottom: 20px;
    padding: 15px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
}

.cambio-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
    font-weight: 600;
}

.cambio-amount {
    color: #10b981;
    font-size: 1.1rem;
}

.venta-actions-final {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    align-items: center;
}

/* Responsive */
@media (max-width: 992px) {
    .venta-grid-productos {
        grid-template-columns: 1fr 1fr;
    }
    
    .venta-grid-productos #btnAgregar {
        grid-column: 1 / -1;
        width: 100%;
        padding: 12px;
    }
}

@media (max-width: 768px) {
    .venta-fila-superior {
        flex-direction: column;
    }

    .venta-grid-productos {
        grid-template-columns: 1fr;
    }

    .venta-total-section {
        text-align: center;
    }
    
    .total-display,
    .cambio-display {
        justify-content: center;
    }
    
    .seccionCambio {
        width: 100%;
        max-width: 350px;
    }

    .venta-actions-final {
        flex-direction: column;
    }
    
    .venta-actions-final > * {
        width: 100%;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .venta-fila-superior input,
    .venta-fila-superior select,
    .venta-grid-productos input,
    .venta-grid-productos select {
        font-size: 16px;
    }
}
</style>

<script src="../public/js/ventas.js"></script>

<?php include(__DIR__ . "/footer.php"); ?>