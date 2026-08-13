<?php
// views/venta_mayorista.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['admin', 'colaborador', 'vendedor']);
$pdo = app();

// ✅ Catálogo de precios y piso por rol
$catalogo = $pdo->query("SELECT id, tipo_prenda, precio_base FROM precios_base_confeccion WHERE activo = 1 ORDER BY tipo_prenda ASC")->fetchAll(PDO::FETCH_ASSOC);
$maxDescRol = ['admin'=>1.0,'colaborador'=>0.10,'vendedor'=>0.05][$_SESSION['rol'] ?? 'vendedor'] ?? 0.05;

// Clientes para el datalist
$stmtClientes = $pdo->query("SELECT id, nombre_completo, nit_cedula, direccion, barrio, ciudad, referencia_entrega FROM clientes WHERE estado = 'activo' ORDER BY nombre_completo ASC");
$res_clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

// Mostrar errores del controller
if (!empty($_GET['error'])) {
    echo '<div style="padding:12px;background:#fee2e2;color:#991b1b;border-left:4px solid #ef4444;border-radius:4px;margin:10px 0;">' 
         . htmlspecialchars($_GET['error']) . '</div>';
}

include(__DIR__ . '/header.php');
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>
  <main class="main-content-panel">
    <!-- Contenedor de alertas del JS -->
    <div id="mensajeAlerta" style="display:none; padding:12px; margin-bottom:15px; border-radius:6px; font-weight:600;"></div>
    
    <div class="page-header">
            <div>
                <h1>🧵 Venta Mayorista</h1>
                <p>Negociación por prenda desde catálogo. Abono mínimo 50%.</p>
            </div>
        </div>

        <form id="formVentaMayorista" method="POST" action="../controllers/procesar_pedido_confeccion.php">
            
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
                        <input type="hidden" name="cliente_id_hidden" id="cliente_id_hidden">
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
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN DERECHA: ENTREGA -->
                <div style="flex: 1; min-width: 300px; display: flex; flex-direction: column; gap: 15px;">
                    <h3 style="margin-top: 0; color: var(--text); font-size: 1.1rem;">🚚 Tipo de Entrega</h3>
                    
                    <!-- ✅ NUEVO: Fecha de entrega -->
                    <div>
                        <label><strong>Fecha de Entrega *</strong></label>
                        <input type="date" name="fecha_entrega" id="fecha_entrega" required value="<?= date('Y-m-d', strtotime('+12 days')) ?>" style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;">
                    </div>
                    
                    <div>
                        <label><strong>Tipo de Entrega:</strong></label>
                        <select name="tipo_entrega" id="tipo_entrega" required style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;">
                            <option value="Tienda">Retiro en Tienda</option>
                            <option value="Domicilio">Envío a Domicilio</option>
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

            <!-- FILA 2: AGREGAR PRODUCTOS (✅ REEMPLAZADO POR CATÁLOGO) -->
            <div class="venta-container" style="display: grid; gap: 12px; margin-bottom: 20px; padding: 20px; background: var(--card); border: 1px solid var(--border); border-radius: 10px;">
                <h3 style="margin: 0; font-size: 1.1rem; color: var(--text);">➕ Agregar ítem a la orden</h3>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                    
                    <!-- ✅ NUEVO: Select de catálogo en lugar de input libre -->
                    <div style="flex: 2; min-width: 200px;">
                        <label><strong>Tipo de Prenda (Catálogo) *</strong></label>
                        <select id="selectTipoPrenda" data-max-desc="<?= $maxDescRol ?>" required style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;">
                            <option value="">-- Seleccione un tipo de prenda --</option>
                            <?php foreach ($catalogo as $c): ?>
                            <option value="<?= $c['id'] ?>" 
                                    data-precio="<?= $c['precio_base'] ?>" 
                                    data-nombre="<?= htmlspecialchars($c['tipo_prenda'], ENT_QUOTES) ?>">
                                <?= htmlspecialchars($c['tipo_prenda']) ?> · Base: $<?= number_format($c['precio_base'], 0, ',', '.') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- ✅ NUEVO: Precio negociado -->
                    <div style="flex: 1; min-width: 150px;">
                        <label><strong>Precio Negociado *</strong></label>
                        <input type="number" id="inputPrecioNegociado" min="0" step="500" required style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;">
                        <small id="pisoInfo" style="color:#92400e; display:block; margin-top:5px; font-size:0.85rem;"></small>
                    </div>
                    
                    <div style="flex: 1; min-width: 120px;">
                        <label><strong>Color:</strong></label>
                        <input type="text" id="productoColor" placeholder="Ej: Rojo" style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;">
                    </div>
                    
                    <div style="flex: 1; min-width: 120px;">
                        <label><strong>Talla:</strong></label>
                        <input type="text" id="productoTalla" placeholder="Ej: M" style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;">
                    </div>
                    
                    <div style="flex: 1; min-width: 100px;">
                        <label><strong>Cantidad:</strong></label>
                        <input type="number" id="productoCantidad" min="1" value="1" style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; text-align: center;">
                    </div>
                    
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
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="carritoBody">
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-light);">🛒 No hay productos agregados aún.</td>
                        </tr>
                    </tbody>
                </table>
                <p id="carritoStatus" style="text-align: right; font-size: 0.85rem; color: var(--text-light); margin-top: 10px;">Carrito: 0 productos.</p>
            </div>

            <!-- FILA 4: TOTALES Y MÉTODO DE PAGO -->
            <div class="venta-container" style="padding: 25px; background: var(--input-bg); border-radius: 12px; border: 1px solid var(--border); display: flex; flex-wrap: wrap; gap: 30px; max-width: 900px; margin-left: auto;">
                
                <!-- Columna Izquierda: Resumen de Totales -->
                <div style="flex: 1; min-width: 250px; display: grid; gap: 15px;">
                    <h3 style="margin: 0 0 10px 0; font-size: 1.1rem; color: var(--text);">💰 Resumen de la Venta</h3>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="font-weight: 600;">TOTAL A PAGAR</span>
                        <span id="txtTotalFinal" style="font-size: 1.5rem; font-weight: 800; color: var(--primary);">$0</span>
                    </div>
                    
                    <!-- ✅ NUEVO: Abono (50% mínimo) -->
                    <div>
                        <label style="font-weight: 700; color: var(--text);">Abono Inicial (mínimo 50%) *</label>
                        <input type="number" id="inputAbono" name="abono" min="0" step="1000" required style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; font-size: 1.1rem; font-weight: 700;">
                        <small id="infoAbonoMin" style="color:#d97706; display:block; margin-top:5px; font-size:0.85rem;">Abono mínimo: $0</small>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; border-top: 2px dashed var(--border); padding-top: 15px;">
                        <span style="font-weight: 700;">Saldo Pendiente:</span>
                        <span id="txtSaldoPendiente" style="font-size: 1.3rem; font-weight: 800; color: var(--danger);">$0</span>
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

                    <!-- Botones de acción -->
                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                        <a href="panel_vendedor.php" style="flex: 1; text-align: center; color: var(--text-light); text-decoration: none; padding: 12px 16px; border: 1px solid var(--border); border-radius: 8px; font-weight: 600; background: var(--card);">Cancelar</a>
                        <button type="submit" class="btn-primary" style="flex: 2; border: none; cursor: pointer; padding: 12px; font-size: 1rem; font-weight: bold;">✅ Procesar Venta Mayorista</button>
                    </div>
                </div>

                <!-- ✅ Campos ocultos CORREGIDOS -->
                <input type="hidden" id="ventaJSON" name="ventaJSON">
                <input type="hidden" id="inputTotal" name="total_venta">
                <input type="hidden" name="venta_tipo" value="mayorista">
            </div>
                </form>
    </main>
</div>

<!-- Modal FUERA del main -->
<div id="modalEditarItem" class="modal-overlay">
    <div class="modal-box">
        <h3 class="modal-title">✏️ Editar Prenda</h3>
        <div class="form-group">
            <label class="form-label">Tipo de Prenda</label>
            <input type="text" id="editNombre" class="form-control" readonly>
        </div>
        <div class="modal-grid-2">
            <div class="form-group">
                <label class="form-label">Color</label>
                <input type="text" id="editColor" class="form-control" placeholder="Ej: Rojo">
            </div>
            <div class="form-group">
                <label class="form-label">Talla</label>
                <input type="text" id="editTalla" class="form-control" placeholder="Ej: M">
            </div>
        </div>
        <div class="modal-grid-2">
            <div class="form-group">
                <label class="form-label">Precio Unitario</label>
                <input type="number" id="editPrecio" class="form-control" readonly>
                <small class="form-hint">⚠️ El precio no se edita. Para cambiarlo, elimina y vuelve a agregar la prenda.</small>
            </div>
            <div class="form-group">
                <label class="form-label">Cantidad</label>
                <input type="number" id="editCantidad" min="1" class="form-control">
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" id="btnCancelarEdicion" class="btn-secondary">Cancelar</button>
            <button type="button" id="btnGuardarEdicion" class="btn-primary">Guardar Cambios</button>
        </div>
    </div>
</div>

<!-- ✅ Cargar linea_confeccion.js en lugar de venta_mayorista.js -->
<script src="/unideportes-system/public/js/linea_confeccion.js?v=<?= time() ?>"></script>

<?php include(__DIR__ . '/footer.php'); ?>