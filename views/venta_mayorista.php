<?php
// views/venta_mayorista.php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login(['vendedor', 'colaborador', 'admin']);

$pdo = app();

// CONSULTAS PRELIMINARES
$stmtClientes = $pdo->query("
    SELECT id, nombre_completo, nit_cedula, direccion, barrio, ciudad, referencia_entrega 
    FROM clientes 
    ORDER BY nombre_completo ASC
");
$res_clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

$stmtProductos = $pdo->query("
    SELECT id, nombre, precio, stock 
    FROM productos 
    WHERE stock > 0 
    ORDER BY nombre ASC
");
$res_productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

include(__DIR__ . '/header.php');
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">
        
        <!-- ENCABEZADO -->
        <div class="page-header">
            <div>
                <h1>Venta Mayorista</h1>
                <p>Ventas por volumen: Descuento por cantidad (10+ unid. = 5%, 20+ unid. = 10%).</p>
            </div>
            <a href="/unideportes-system/views/nueva_venta.php" class="btn-secondary">
                ← Volver a Venta Directa
            </a>
        </div>

        <!-- ALERTA DE MENSAJES -->
        <div id="mensajeAlerta" class="mensaje-alerta"></div>

        <!-- FORMULARIO PRINCIPAL -->
        <form action="../controllers/procesar_venta.php" method="POST" id="formVentaMayorista">
            
            <!-- SECCIÓN 1: CLIENTE Y CONFIGURACIÓN -->
            <div class="venta-dos-columnas">
                
                <!-- COLUMNA IZQUIERDA: Cliente -->
                <div class="venta-card">
                    <h2 class="section-subtitle">Datos del Cliente</h2>
                    
                    <label for="clienteInput">Cliente mayorista</label>
                    <input 
                        type="text" 
                        list="listaClientes" 
                        id="clienteInput" 
                        class="form-input"
                        placeholder="Buscar cliente..." 
                        autocomplete="off"
                    >
                    <datalist id="listaClientes">
                        <?php foreach ($res_clientes as $cli): ?>
                            <option 
                                data-id="<?= $cli['id'] ?>"
                                data-direccion="<?= htmlspecialchars($cli['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-barrio="<?= htmlspecialchars($cli['barrio'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-ciudad="<?= htmlspecialchars($cli['ciudad'] ?: 'Sogamoso', ENT_QUOTES, 'UTF-8') ?>"
                                data-referencia="<?= htmlspecialchars($cli['referencia_entrega'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                value="<?= htmlspecialchars($cli['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>">
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
                    <div id="nuevoClienteSection" class="form-section">
                        <h3>Registro Rápido de Cliente</h3>
                        <div class="form-grid">
                            <div>
                                <label for="nuevo_cliente_nombre_completo">Nombre completo *</label>
                                <input type="text" name="nuevo_cliente_nombre_completo" id="nuevo_cliente_nombre_completo" class="form-input">
                            </div>
                            <div>
                                <label for="nuevo_cliente_nit_cedula">NIT / Cédula *</label>
                                <input type="text" name="nuevo_cliente_nit_cedula" id="nuevo_cliente_nit_cedula" class="form-input">
                            </div>
                            <div>
                                <label for="nuevo_cliente_telefono">Teléfono</label>
                                <input type="text" name="nuevo_cliente_telefono" id="nuevo_cliente_telefono" class="form-input">
                            </div>
                            <div>
                                <label for="nuevo_cliente_tipo_cliente">Tipo de cliente</label>
                                <select name="nuevo_cliente_tipo_cliente" id="nuevo_cliente_tipo_cliente" class="form-input">
                                    <option value="Individual">Individual</option>
                                    <option value="Equipo">Equipo</option>
                                    <option value="Colegio">Colegio</option>
                                    <option value="Empresa">Empresa</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Comentarios -->
                    <div class="form-section" style="background: #eff6ff; border-color: #bfdbfe;">
                        <h3>📝 Comentarios de la Venta</h3>
                        <textarea 
                            name="observaciones_venta_mayor" 
                            id="observaciones_venta_mayor" 
                            class="form-input"
                            rows="3" 
                            placeholder="Ej: Facturar a nombre de la empresa, empacar por separado..."
                        ></textarea>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: Pago y entrega -->
                <div class="venta-card">
                    <h2 class="section-subtitle">Pago y Entrega</h2>
                    
                    <label for="metodo_pago">Método de Pago</label>
                    <select name="metodo_pago" id="metodo_pago" class="form-input" required>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Tarjeta">Tarjeta</option>
                        <option value="Transferencia">Transferencia</option>
                    </select>

                    <!-- Transferencia -->
                    <div id="seccionTransferencia" class="form-section">
                        <label for="tipo_transferencia_select">Plataforma Virtual</label>
                        <select id="tipo_transferencia_select" class="form-input">
                            <option value="Nequi">Nequi</option>
                            <option value="Daviplata">Daviplata</option>
                            <option value="Otro">Otro ¿Cuál?</option>
                        </select>
                        <input 
                            type="text" 
                            id="otra_plataforma_input" 
                            class="form-input venta-hidden"
                            placeholder="Ej: Bancolombia, Davivienda..."
                        >
                        <input type="hidden" name="tipo_transferencia" id="tipo_transferencia_final">
                    </div>

                    <label for="tipo_entrega" style="margin-top: 15px;">Tipo de Entrega</label>
                    <select name="tipo_entrega" id="tipo_entrega" class="form-input" required>
                        <option value="Tienda">Retiro en Tienda</option>
                        <option value="Domicilio">Envío a Domicilio</option>
                    </select>

                    <label for="fecha_entrega" style="margin-top: 15px;">📅 Fecha de Entrega</label>
                    <input 
                        type="date" 
                        name="fecha_entrega" 
                        id="fecha_entrega" 
                        class="form-input"
                        required 
                        value="<?= date('Y-m-d', strtotime('+7 days')) ?>"
                    >
                    <small class="form-hint">Fecha límite para entregar al cliente</small>

                    <!-- Domicilio -->
                    <div id="seccionDomicilio" class="form-section">
                        <h3>📍 Datos de Envío</h3>
                        <div class="form-grid">
                            <div class="form-group-full">
                                <label for="direccion_entrega">Dirección exacta *</label>
                                <input type="text" name="direccion_entrega" id="direccion_entrega" class="form-input" placeholder="Ej: Calle 15 # 12-34">
                            </div>
                            <div>
                                <label for="barrio_entrega">Barrio</label>
                                <input type="text" name="barrio_entrega" id="barrio_entrega" class="form-input" placeholder="Ej: El Rosario">
                            </div>
                            <div>
                                <label for="ciudad_entrega">Ciudad *</label>
                                <input type="text" name="ciudad_entrega" id="ciudad_entrega" class="form-input" value="Sogamoso">
                            </div>
                            <div class="form-group-full">
                                <label for="observaciones_entrega">Observaciones</label>
                                <textarea name="observaciones_entrega" id="observaciones_entrega" class="form-input" rows="2" placeholder="Indicaciones para el domiciliario..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: PRODUCTOS -->
            <div class="venta-card">
                <h2 class="section-subtitle">Agregar Productos</h2>
                
                <div class="buscador-fila">
                    <div style="flex: 2;">
                        <label for="productoInput">Producto</label>
                        <input 
                            type="text" 
                            list="listaProductos" 
                            id="productoInput" 
                            class="form-input"
                            placeholder="Buscar producto..." 
                            autocomplete="off"
                        >
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
                        <input type="text" id="productoColor" class="form-input input-disabled" placeholder="Selecciona producto" disabled>
                    </div>
                    <div id="wrapperProductoTalla">
                        <label for="productoTalla">Talla</label>
                        <input type="text" id="productoTalla" class="form-input input-disabled" placeholder="Selecciona color" disabled>
                    </div>
                    <button type="button" id="btnAgregar" class="btn-success">
                        + Añadir
                    </button>
                </div>
            </div>

            <!-- SECCIÓN 3: BARRA DE PROGRESO -->
            <div class="barra-progreso-container">
                <div class="barra-progreso-header">
                    <strong>Progreso hacia el descuento</strong>
                    <span id="textoProgreso">0 unidades</span>
                </div>
                <div class="barra-progreso-track">
                    <div id="barraProgreso" class="barra-progreso-fill">0%</div>
                </div>
                <p id="mensajeProgreso" class="form-hint">
                    Agrega productos para obtener descuentos
                </p>
            </div>

            <!-- SECCIÓN 4: CARRITO -->
            <div class="venta-card">
                <h2 class="section-subtitle">Carrito de Compra</h2>
                
                <div class="tabla-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Color</th>
                                <th>Talla</th>
                                <th>Precio Base</th>
                                <th style="width: 80px; text-align: center;">Cant</th>
                                <th style="width: 110px;">Descuento</th>
                                <th>Subtotal</th>
                                <th style="width: 70px; text-align: center;">Quitar</th>
                            </tr>
                        </thead>
                        <tbody id="carritoBody"></tbody>
                    </table>
                </div>
            </div>

            <!-- SECCIÓN 5: RESUMEN -->
            <div class="resumen-venta">
                <div class="resumen-fila">
                    <span class="resumen-label">SubTotal</span>
                    <span id="txtTotal" class="resumen-valor texto-azul">$0.00</span>
                </div>
                <div class="resumen-fila">
                    <span class="resumen-label">Descuento mayorista</span>
                    <span id="txtDescuento" class="resumen-valor texto-amarillo">$0.00</span>
                </div>
                <div class="resumen-fila">
                    <span class="resumen-total">Total final</span>
                    <span id="txtTotalFinal" class="resumen-total-final texto-verde">$0.00</span>
                </div>

                <div class="resumen-separador">
                    <div class="resumen-fila" style="margin-bottom: 12px;">
                        <label for="inputAbono" class="resumen-label">Abono (pago inicial):</label>
                        <input 
                            type="number" 
                            id="inputAbono" 
                            name="abono" 
                            class="input-abono"
                            min="0" 
                            step="0.01" 
                            placeholder="$0.00"
                        >
                    </div>
                    <small class="texto-advertencia">
                        * El abono mínimo debe ser el 50% del total para procesar la venta.
                    </small>
                    <div class="resumen-fila">
                        <span class="resumen-label">Saldo pendiente:</span>
                        <span id="txtSaldoPendiente" class="resumen-total-final texto-rojo">$0.00</span>
                    </div>
                </div>

                <input type="hidden" id="ventaJSON" name="venta_json">
                <input type="hidden" id="inputTotal" name="total_venta">
                <input type="hidden" name="tipo_venta" value="mayorista">

                <div class="form-actions">
                    <a href="panel_vendedor.php" class="btn-secondary">Cancelar</a>
                    <button type="submit" class="btn-primary">Procesar Venta Mayorista</button>
                </div>
            </div>
        </form>
    </main>
</div>

<style>
/* ============================================
   VENTA MAYORISTA - ESTILOS SIMPLIFICADOS
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

/* Encabezado */
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

/* Subtítulos */
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

/* Layout de dos columnas */
.venta-dos-columnas {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.venta-dos-columnas > div {
    flex: 1;
    min-width: 280px;
}

/* Inputs */
.form-input {
    width: 100%;
    padding: 9px 12px;
    margin-top: 4px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.95rem;
    background: white;
    box-sizing: border-box;
    transition: border-color 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.input-disabled {
    background: #f1f5f9;
    color: #64748b;
    cursor: not-allowed;
}

label {
    display: block;
    font-size: 0.9rem;
    font-weight: 500;
    color: #334155;
    margin-top: 8px;
}

.form-hint {
    font-size: 0.85rem;
    color: #64748b;
    margin-top: 4px;
    display: block;
}

.venta-hidden {
    display: none;
}

/* Secciones colapsables */
.form-section {
    display: none;
    margin-top: 15px;
    padding: 15px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
}

.form-section h3 {
    margin: 0 0 12px 0;
    color: #1e293b;
    font-size: 1rem;
    font-weight: 600;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.form-group-full {
    grid-column: 1 / -1;
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
    padding: 9px 18px;
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

/* Acciones */
.venta-actions {
    margin-top: 12px;
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

/* Buscador de productos */
.buscador-fila {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.buscador-fila > div {
    flex: 1;
    min-width: 130px;
}

/* Barra de progreso */
.barra-progreso-container {
    margin-bottom: 20px;
    padding: 15px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
}

.barra-progreso-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.barra-progreso-header strong,
.barra-progreso-header span {
    color: #1e40af;
}

.barra-progreso-track {
    background: #cbd5e1;
    border-radius: 10px;
    height: 25px;
    overflow: hidden;
}

.barra-progreso-fill {
    background: linear-gradient(90deg, #3b82f6, #10b981);
    height: 100%;
    width: 0%;
    transition: width 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 0.9rem;
}

/* Tabla */
.tabla-wrapper {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: #1e293b;
    color: white;
    text-align: left;
}

.data-table th {
    padding: 10px;
    font-weight: 600;
    font-size: 0.9rem;
}

.data-table td {
    padding: 10px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.9rem;
}

.data-table tbody tr:hover {
    background: #f8fafc;
}

/* Resumen de venta */
.resumen-venta {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    max-width: 550px;
    margin-left: auto;
}

.resumen-fila {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 0;
}

.resumen-label {
    font-weight: 600;
    color: #334155;
}

.resumen-valor {
    font-weight: 700;
}

.resumen-total {
    font-weight: 700;
    font-size: 1.1rem;
    color: #1e293b;
}

.resumen-total-final {
    font-size: 1.4rem;
    font-weight: 800;
}

.texto-azul { color: #2563eb; }
.texto-amarillo { color: #ca8a04; }
.texto-verde { color: #047857; }
.texto-rojo { color: #dc2626; }

.resumen-separador {
    border-top: 2px dashed #cbd5e1;
    padding-top: 15px;
    margin-top: 15px;
}

.input-abono {
    width: 150px;
    padding: 8px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    text-align: right;
    font-size: 1rem;
}

.texto-advertencia {
    color: #dc2626;
    display: block;
    margin-bottom: 10px;
    font-size: 0.85rem;
}

/* Acciones del formulario */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

/* Alertas */
.mensaje-alerta {
    display: none;
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 500;
}

.mensaje-alerta.visible {
    display: block;
}

.mensaje-alerta.error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

.mensaje-alerta.success {
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
}

.mensaje-alerta.warning {
    background: #fef3c7;
    color: #92400e;
    border-left: 4px solid #f59e0b;
}

/* Estados del botón agregar */
.btn-agregar-active {
    background: #10b981 !important;
}

.btn-agregar-disabled {
    background: #94a3b8 !important;
    cursor: not-allowed !important;
}

/* Carrito vacío */
.carrito-vacio {
    text-align: center;
    padding: 30px;
    color: #94a3b8;
}

/* Controles de cantidad */
.control-cantidad {
    display: flex;
    gap: 5px;
    justify-content: center;
    align-items: center;
}

.btn-cantidad {
    padding: 2px 8px;
    background: #cbd5e1;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-cantidad:hover {
    background: #94a3b8;
}

.cantidad-valor {
    min-width: 25px;
    font-weight: bold;
    text-align: center;
}

.btn-quitar {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.1rem;
    transition: transform 0.2s;
}

.btn-quitar:hover {
    transform: scale(1.2);
}

.texto-semibold { font-weight: 500; }
.texto-bold { font-weight: bold; color: #1e293b; }
.text-center { text-align: center; }

/* Responsive */
@media (max-width: 768px) {
    .venta-dos-columnas {
        flex-direction: column;
    }
    
    .buscador-fila {
        flex-direction: column;
    }
    
    .buscador-fila > div {
        width: 100%;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-group-full {
        grid-column: 1;
    }
    
    .resumen-venta {
        max-width: 100%;
    }
    
    .form-actions {
        flex-direction: column-reverse;
    }
    
    .form-actions > * {
        width: 100%;
        text-align: center;
    }
}
</style>

<script src="../public/js/venta_mayorista.js"></script>

<?php include(__DIR__ . '/footer.php'); ?>