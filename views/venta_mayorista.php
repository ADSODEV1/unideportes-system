<?php
// views/venta_mayorista.php
require_once __DIR__ . '/../config/bootstrap.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_login(['vendedor', 'colaborador', 'admin']);
$pdo = app();

$stmtClientes = $pdo->query("SELECT id, nombre_completo, nit_cedula, direccion, barrio, ciudad, referencia_entrega FROM clientes ORDER BY nombre_completo ASC");
$res_clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

$stmtProductos = $pdo->query("SELECT id, nombre, precio, stock FROM productos WHERE stock > 0 ORDER BY nombre ASC");
$res_productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

include(__DIR__ . '/header.php');
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>
    <main class="main-content-panel">
        <div class="page-header">
            <div>
                <h1>🧵 Venta Mayorista</h1>
                <p>Ventas por volumen con descuento automático (10+ unid. = 5%, 20+ unid. = 10%).</p>
            </div>
        </div>

        <form action="../controllers/procesar_venta.php" method="POST" id="formVentaMayorista" autocomplete="off">
            
            <!-- FILA 1: CLIENTE Y ENTREGA -->
            <div class="venta-container" style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                <!-- SECCIÓN IZQUIERDA: CLIENTE -->
                <div style="flex: 1; min-width: 300px;">
                    <h3 style="margin-top: 0; color: var(--text); font-size: 1.1rem;">👤 Datos del Cliente</h3>
                    <div>
                        <label><strong>Cliente mayorista:</strong></label>
                        <input type="text" list="listaClientes" id="clienteInput" placeholder="Buscar cliente..." style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;" autocomplete="off">
                        <datalist id="listaClientes">
                            <?php foreach ($res_clientes as $cli): ?>
                                <option data-id="<?= $cli['id'] ?>" data-direccion="<?= htmlspecialchars($cli['direccion'] ?? '') ?>" data-barrio="<?= htmlspecialchars($cli['barrio'] ?? '') ?>" data-ciudad="<?= htmlspecialchars($cli['ciudad'] ?: 'Sogamoso') ?>" data-referencia="<?= htmlspecialchars($cli['referencia_entrega'] ?? '') ?>" value="<?= htmlspecialchars($cli['nombre_completo']) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <input type="hidden" name="cliente_id" id="cliente_id_hidden">
                        <input type="hidden" name="vendedor_id" value="<?= htmlspecialchars($_SESSION['user_id'] ?? 0) ?>">
                    </div>
                    
                    <div style="margin-top: 10px; display: flex; gap: 10px; align-items: center;">
                        <button type="button" id="btnToggleNuevoCliente" style="padding: 8px 12px; background: var(--primary); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9rem;">Crear cliente nuevo</button>
                    </div>

                    <div id="nuevoClienteSection" style="display: none; margin-top: 15px; padding: 15px; background: var(--input-bg); border: 1px solid var(--border); border-radius: 10px;">
                        <h4 style="margin-top: 0; color: var(--text); margin-bottom: 10px; font-size: 1rem;">Datos de Registro Rápido</h4>
                        <div style="display: grid; gap: 10px;">
                            <div><label style="font-size: 0.9rem;">Nombre completo *</label><input type="text" name="nuevo_cliente_nombre_completo" style="width:100%; padding: 8px; border: 1px solid var(--border); border-radius: 6px;"></div>
                            <div><label style="font-size: 0.9rem;">NIT / Cédula *</label><input type="text" name="nuevo_cliente_nit_cedula" style="width:100%; padding: 8px; border: 1px solid var(--border); border-radius: 6px;"></div>
                            <div><label style="font-size: 0.9rem;">Teléfono</label><input type="text" name="nuevo_cliente_telefono" style="width:100%; padding: 8px; border: 1px solid var(--border); border-radius: 6px;"></div>
                            <div>
                                <label style="font-size: 0.9rem;">Tipo de cliente</label>
                                <select name="nuevo_cliente_tipo_cliente" style="width:100%; padding: 8px; border: 1px solid var(--border); border-radius: 6px;">
                                    <option value="Individual">Individual</option><option value="Equipo">Equipo</option><option value="Colegio">Colegio</option><option value="Empresa">Empresa</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN DERECHA: ENTREGA -->
                <div style="flex: 1; min-width: 300px; display: flex; flex-direction: column; gap: 15px;">
                    <h3 style="margin-top: 0; color: var(--text); font-size: 1.1rem;">🚚 Tipo de Entrega</h3>
                    <div>
                        <label><strong>Tipo de Entrega:</strong></label>
                        <select name="tipo_entrega" id="tipo_entrega" required style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;">
                            <option value="Tienda">Retiro en Tienda</option>
                            <option value="Domicilio">Envío a Domicilio (+ $5.000)</option>
                        </select>
                    </div>

                    <!-- SECCIÓN DOMICILIO -->
                    <div id="seccionDomicilio" style="display: none; padding: 15px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px;">
                        <h4 style="margin-top: 0; color: #b45309; margin-bottom: 12px; font-size: 1rem;">📍 Dirección de Envío</h4>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <div>
                                <label style="font-size: 0.85rem; font-weight: 600; color: #78350f;">Dirección exacta *</label>
                                <input type="text" name="direccion_entrega" id="direccion_entrega" placeholder="Ej: Calle 15 # 12-34" style="width:100%; padding: 10px; margin-top: 4px; border: 1px solid #d97706; border-radius: 6px;">
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <div style="flex: 1;">
                                    <label style="font-size: 0.85rem; font-weight: 600; color: #78350f;">Barrio</label>
                                    <input type="text" name="barrio_entrega" id="barrio_entrega" placeholder="Ej: El Rosario" style="width:100%; padding: 10px; margin-top: 4px; border: 1px solid #fcd34d; border-radius: 6px;">
                                </div>
                                <div style="flex: 1;">
                                    <label style="font-size: 0.85rem; font-weight: 600; color: #78350f;">Ciudad *</label>
                                    <input type="text" name="ciudad_entrega" id="ciudad_entrega" value="Sogamoso" style="width:100%; padding: 10px; margin-top: 4px; border: 1px solid #d97706; border-radius: 6px;">
                                </div>
                            </div>
                            <div>
                                <label style="font-size: 0.85rem; font-weight: 600; color: #78350f;">Indicaciones opcionales</label>
                                <textarea name="observaciones_entrega" id="observaciones_entrega" rows="2" style="width:100%; padding: 8px; margin-top: 4px; border: 1px solid #fcd34d; border-radius: 6px; resize: none;" placeholder="Apto, torre, conjunto..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILA 2: AGREGAR PRODUCTOS -->
            <div class="venta-container" style="display: grid; gap: 12px; margin-bottom: 20px; padding: 20px; background: var(--card); border: 1px solid var(--border); border-radius: 10px;">
                <h3 style="margin: 0; font-size: 1.1rem; color: var(--text);">➕ Agregar ítem a la orden</h3>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                    <div style="flex: 2; min-width: 200px;">
                        <label><strong>Producto:</strong></label>
                        <input type="text" list="listaProductos" id="productoInput" placeholder="Ej: Camiseta Dry-Fit" style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;" autocomplete="off">
                        <datalist id="listaProductos">
                            <?php foreach ($res_productos as $prod): ?>
                                <option data-id="<?= $prod['id'] ?>" data-precio="<?= $prod['precio'] ?>" data-stock="<?= $prod['stock'] ?>" value="<?= htmlspecialchars($prod['nombre']) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div style="flex: 1; min-width: 120px;"><label><strong>Precio:</strong></label><input type="number" id="productoPrecio" readonly style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; background: var(--input-bg);"></div>
                    <div id="wrapperProductoColor" style="flex: 1; min-width: 120px;"><label><strong>Color:</strong></label><input type="text" id="productoColor" disabled style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; background: var(--input-bg);"></div>
                    <div id="wrapperProductoTalla" style="flex: 1; min-width: 120px;"><label><strong>Talla:</strong></label><input type="text" id="productoTalla" disabled style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; background: var(--input-bg);"></div>
                    <div style="flex: 1; min-width: 100px;"><label><strong>Cantidad:</strong></label><input type="number" id="productoCantidad" min="1" value="1" style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; text-align: center;"></div>
                    <button type="button" id="btnAgregar" style="padding: 11px 20px; background: var(--success); color: white; border: none; border-radius: 6px; font-weight: bold; cursor:pointer; height: 42px; margin-top: 5px;">+ Añadir</button>
                </div>
            </div>

          <!-- FILA 3: TABLA DEL CARRITO -->
<div class="venta-container" style="margin-bottom: 20px; overflow-x: auto;">
    <table class="tabla-maestra">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Color</th>
                <th>Talla</th>
                <th>Precio Unit.</th>
                <th style="text-align: center;">Cant</th>
                <th style="text-align: right;">Desc.</th>
                <th style="text-align: right;">Subtotal</th>
                <th style="text-align: center;">Acciones</th>
            </tr>
        </thead>
        <tbody id="carritoBody">
            <tr>
                <td colspan="8" style="text-align: center; padding: 30px; color: var(--text-light);">🛒 No hay productos agregados aún.</td>
            </tr>
        </tbody>
    </table>
</div>

            <!-- FILA 4: TOTALES Y MÉTODO DE PAGO (AL FINAL, DONDE TIENE SENTIDO LÓGICO) -->
            <div class="venta-container" style="padding: 25px; background: var(--input-bg); border-radius: 12px; border: 1px solid var(--border); display: flex; flex-wrap: wrap; gap: 30px; max-width: 900px; margin-left: auto;">
                
                <!-- Columna Izquierda: Resumen de Totales -->
                <div style="flex: 1; min-width: 250px; display: grid; gap: 15px;">
                    <h3 style="margin: 0 0 10px 0; font-size: 1.1rem; color: var(--text);">💰 Resumen de la Venta</h3>
                    <div style="display: flex; justify-content: space-between;"><span style="font-weight: 600;">SubTotal</span><span id="txtTotal" style="font-weight: 700;">$0.00</span></div>
                    <div style="display: flex; justify-content: space-between;"><span style="font-weight: 600;">Descuento mayorista</span><span id="txtDescuento" style="font-weight: 700; color: var(--warning);">$0.00</span></div>
                    <div style="display: flex; justify-content: space-between; border-top: 2px dashed var(--border); padding-top: 15px; margin-top: 5px;">
                        <span style="font-weight: 700; font-size: 1.2rem;">TOTAL A PAGAR</span>
                        <span id="txtTotalFinal" style="font-size: 1.5rem; font-weight: 800; color: var(--primary);">$0.00</span>
                    </div>
                </div>

                <!-- Columna Derecha: Método de Pago y Acciones -->
                <div style="flex: 1.2; min-width: 300px; display: flex; flex-direction: column; gap: 15px;">
                    <h3 style="margin: 0 0 5px 0; font-size: 1.1rem; color: var(--text);">💳 Método de Pago</h3>
                    <div>
                        <select name="metodo_pago" id="metodo_pago" required style="width:100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px; font-weight: 600;">
                            <option value="Efectivo">💵 Efectivo</option>
                            <option value="Tarjeta">💳 Tarjeta</option>
                            <option value="Transferencia">📱 Transferencia</option>
                        </select>
                    </div>

                    <!-- SECCIÓN EFECTIVO (PAGA CON Y CAMBIO) -->
                    <div id="seccionCambio" style="display: none; background: var(--card); padding: 15px; border-radius: 8px; border: 1px solid var(--border);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <label style="font-weight: 700; color: var(--text);">Paga con:</label>
                            <input type="number" id="inputPagaCon" name="paga_con" min="0" step="0.01" style="width: 150px; padding: 8px; border: 1px solid var(--border); border-radius: 6px; text-align: right; font-weight: bold;" placeholder="$0.00">
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 700; color: var(--text);">Cambio:</span>
                            <span id="txtCambio" style="font-size: 1.2rem; font-weight: 800; color: var(--success);">$0.00</span>
                        </div>
                    </div>

                    <!-- SECCIÓN TRANSFERENCIA -->
                    <div id="seccionTransferencia" style="display: none; background: var(--card); padding: 15px; border-radius: 8px; border: 1px solid var(--border);">
                        <label style="font-size: 0.9rem; font-weight: 600; color: var(--text);">Plataforma Virtual:</label>
                        <select id="tipo_transferencia_select" style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;">
                            <option value="">Selecciona plataforma...</option>
                            <option value="Nequi">Nequi</option><option value="Daviplata">Daviplata</option><option value="Bancolombia">Bancolombia</option><option value="Otro">Otro ¿Cuál?</option>
                        </select>
                        <input type="text" id="otra_plataforma_input" placeholder="Ej: Davivienda..." style="display: none; width: 100%; padding: 8px; margin-top: 8px; border: 1px solid var(--border); border-radius: 6px;">
                        <div style="margin-top: 12px;">
                            <label style="font-size: 0.9rem; font-weight: 600; color: var(--text);">Número de Referencia <span style="color: var(--danger);">*</span></label>
                            <input type="text" id="referencia_pago_input" name="referencia_pago" placeholder="Ej: REF-123456789" style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;">
                        </div>
                        <input type="hidden" name="tipo_transferencia" id="tipo_transferencia_final">
                    </div>

                    <!-- SECCIÓN TARJETA -->
                    <div id="seccionTarjeta" style="display: none; background: var(--card); padding: 15px; border-radius: 8px; border: 1px solid var(--border);">
                        <label style="font-size: 0.9rem; font-weight: 600; color: var(--text);">Datos de la Tarjeta</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 8px;">
                            <div><label style="font-size: 0.85rem; color: var(--text-light);">Últimos 4 dígitos</label><input type="text" id="ultimos_4_digitos" name="ultimos_4_digitos" placeholder="Ej: 1234" maxlength="4" style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;"></div>
                            <div><label style="font-size: 0.85rem; color: var(--text-light);">Banco emisor</label><input type="text" id="banco_emisor" name="banco_emisor" placeholder="Ej: Bancolombia" style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;"></div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                        <a href="panel_vendedor.php" style="flex: 1; text-align: center; color: var(--text-light); text-decoration: none; padding: 12px 16px; border: 1px solid var(--border); border-radius: 8px; font-weight: 600; background: var(--card);">Cancelar</a>
                        <button type="submit" class="btn-primary" style="flex: 2; border: none; cursor: pointer; padding: 12px; font-size: 1rem; font-weight: bold;">✅ Procesar Venta Mayorista</button>
                    </div>
                </div>

                <!-- Campos ocultos necesarios -->
                <input type="hidden" id="ventaJSON" name="venta_json">
                <input type="hidden" id="inputTotal" name="total_venta">
                <input type="hidden" name="venta_tipo" value="mayorista">
            </div>
        </form>
    </main>
</div>
<script src="/unideportes-system/public/js/venta_mayorista.js?v=<?= time() ?>"></script>
<?php include(__DIR__ . '/footer.php'); ?>