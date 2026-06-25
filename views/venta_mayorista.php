<?php
// views/linea_confeccion.php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login(['vendedor', 'colaborador', 'admin']);

$pdo = app();

// 1. Traer datos de los clientes mayoristas
$stmtClientes = $pdo->query("SELECT id, nombre_completo, nit_cedula, direccion, barrio, ciudad, referencia_entrega FROM clientes ORDER BY nombre_completo ASC");
$res_clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

// Nota: la venta mayorista es independiente del inventario; no cargamos el catálogo aquí.

include(__DIR__ . '/header.php');
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">
        <div class="page-header header-dashboard">
            <div>
                <h1>Compra Mayorista</h1>
                <p>Ventas por volumen: Descuento por cantidad (10+ unid. = 5%, 20+ unid. = 10%).</p>
                <p style="margin-top: 10px; color: #475569;">
                    <strong>Vendedor:</strong> <?= htmlspecialchars($_SESSION['username'] ?? 'Desconocido') ?>
                    <span style="margin-left: 12px;"><strong>Rol:</strong> <?= htmlspecialchars($_SESSION['role'] ?? 'N/A') ?></span>
                </p>
            </div>
        </div>

        <div id="mensajeAlerta" style="display: none; padding: 12px; margin-bottom: 20px; border-radius: 8px; font-weight: 500;"></div>

        <form action="../controllers/procesar_pedido.php" method="POST" id="formVentaMayorista" autocomplete="off">
            <div class="venta-container" style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                
                <div style="flex: 1; min-width: 280px; display: flex; flex-direction: column; gap: 12px;">
                    <div>
                        <label><strong>Cliente mayorista:</strong></label>
                        <input type="text" list="listaClientes" id="clienteInput" placeholder="Buscar cliente..." style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px;" autocomplete="off">
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
                        <input type="hidden" name="vendedor_id" id="vendedor_id_hidden" value="<?= htmlspecialchars($_SESSION['user_id'] ?? $_SESSION['vendedor_id'] ?? 0) ?>">
                    </div>

                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button type="button" id="btnToggleNuevoCliente" style="padding: 8px 12px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9rem;">Crear cliente nuevo</button>
                        <span style="font-size: 0.85rem; color: #4b5563;">Registro rápido para entidades o colegios.</span>
                    </div>

                    <div id="nuevoClienteSection" style="display: none; padding: 15px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px;">
                        <h4 style="margin-top: 0; color: #1e293b; margin-bottom: 10px;">Datos de Registro Rápido</h4>
                        <div style="display: grid; gap: 10px;">
                            <div>
                                <label style="font-size: 0.85rem;">Nombre completo *</label>
                                <input type="text" name="nuevo_cliente_nombre_completo" id="nuevo_cliente_nombre_completo" style="width:100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="font-size: 0.85rem;">NIT / Cédula *</label>
                                <input type="text" name="nuevo_cliente_nit_cedula" id="nuevo_cliente_nit_cedula" style="width:100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="font-size: 0.85rem;">Teléfono</label>
                                <input type="text" name="nuevo_cliente_telefono" id="nuevo_cliente_telefono" style="width:100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="font-size: 0.85rem;">Tipo de cliente</label>
                                <select name="nuevo_cliente_tipo_cliente" id="nuevo_cliente_tipo_cliente" style="width:100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                                    <option value="Individual">Individual</option>
                                    <option value="Equipo">Equipo</option>
                                    <option value="Colegio">Colegio</option>
                                    <option value="Empresa">Empresa</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div style="padding: 15px; background: #f0f9ff; border: 1px solid #bfdbfe; border-radius: 10px; margin-top: 5px;">
                        <h4 style="margin-top: 0; color: #1e40af; margin-bottom: 5px;">Especificaciones de la Orden</h4>
                        <textarea name="observaciones_pedido" id="observaciones_pedido" rows="3" style="width:100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; resize: vertical;" placeholder="Ej: Bordados específicos, insignias de colegios, estampados de números..."></textarea>
                        <div style="margin-top: 14px;">
                            <label><strong>Fecha de entrega:</strong></label>
                            <input type="date" name="fecha_entrega" id="fecha_entrega" required value="<?= date('Y-m-d', strtotime('+15 days')) ?>" style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                    </div>
                </div>

                <div style="flex: 1; min-width: 280px; display: flex; flex-direction: column; gap: 15px;">
                    <div>
                        <label><strong>Método de Pago:</strong></label>
                        <select name="metodo_pago" id="metodo_pago" required style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            <option value="Efectivo">Efectivo</option>
                            <option value="Tarjeta">Tarjeta</option>
                            <option value="Transferencia">Transferencia</option>
                        </select>
                    </div>

                    <div id="seccionTransferencia" style="display: none; background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                        <label style="font-size: 0.85rem;"><strong>Plataforma Virtual:</strong></label>
                        <select id="tipo_transferencia_select" style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            <option value="Nequi">Nequi</option>
                            <option value="Daviplata">Daviplata</option>
                            <option value="Otro">Otro ¿Cuál?</option>
                        </select>
                        <input type="text" id="otra_plataforma_input" placeholder="Ej: Bancolombia, Davivienda..." style="display: none; width: 100%; padding: 8px; margin-top: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                        <input type="hidden" name="tipo_transferencia" id="tipo_transferencia_final">
                    </div>

                    <div>
                        <label><strong>Tipo de Entrega:</strong></label>
                        <select name="tipo_entrega" id="tipo_entrega" required style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            <option value="Tienda">Retiro en Tienda</option>
                            <option value="Domicilio">Envío a Domicilio</option>
                        </select>
                    </div>

                    <div id="seccionDomicilio" style="display: none; padding: 15px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px;">
                        <h4 style="margin-top: 0; color: #b45309; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                            <span>📍</span> Dirección de Envío / Domicilio
                        </h4>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <div>
                                <label style="font-size: 0.85rem; font-weight: 600; color: #78350f;">Dirección exacta *</label>
                                <input type="text" name="direccion_entrega" id="direccion_entrega" placeholder="Ej: Calle 15 # 12-34" style="width:100%; padding: 10px; margin-top: 4px; border: 1px solid #d97706; border-radius: 6px; background-color: #fff;">
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <div style="flex: 1;">
                                    <label style="font-size: 0.85rem; font-weight: 600; color: #78350f;">Barrio</label>
                                    <input type="text" name="barrio_entrega" id="barrio_entrega" placeholder="Ej: El Rosario" style="width:100%; padding: 10px; margin-top: 4px; border: 1px solid #fcd34d; border-radius: 6px; background-color: #fff;">
                                </div>
                                <div style="flex: 1;">
                                    <label style="font-size: 0.85rem; font-weight: 600; color: #78350f;">Ciudad *</label>
                                    <input type="text" name="ciudad_entrega" id="ciudad_entrega" value="Sogamoso" style="width:100%; padding: 10px; margin-top: 4px; border: 1px solid #d97706; border-radius: 6px; background-color: #fff;">
                                </div>
                            </div>
                            <div>
                                <label style="font-size: 0.85rem; font-weight: 600; color: #78350f;">Indicaciones opcionales</label>
                                <textarea name="observaciones_entrega" id="observaciones_entrega" rows="2" style="width:100%; padding: 8px; margin-top: 4px; border: 1px solid #fcd34d; border-radius: 6px; background-color: #fff; resize: none;" placeholder="Apto, torre, conjunto o frente a algún local conocido..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="venta-container" style="display: grid; gap: 12px; margin-bottom: 20px;">
                <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                    <div style="flex: 1; min-width: 250px;">
                        <label><strong>Producto / Prenda:</strong></label>
                        <input type="text" id="productoInput" placeholder="Nombre del producto o servicio" style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px;" autocomplete="off">
                    </div>
                    <div style="min-width: 160px;">
                        <label><strong>Precio unitario:</strong></label>
                        <input type="number" id="productoPrecio" min="0" step="0.01" placeholder="Ej: 45000" style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                    <div id="wrapperProductoColor" style="min-width: 130px;">
                        <label><strong>Color:</strong></label>
                        <input type="text" id="productoColor" placeholder="Ej: Azul Rey / Negro" style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                    <div id="wrapperProductoTalla" style="min-width: 130px;">
                        <label><strong>Talla:</strong></label>
                        <input type="text" id="productoTalla" placeholder="Ej: XL o Talla 14" style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                    <div style="min-width: 120px;">
                        <label><strong>Cantidad:</strong></label>
                        <input type="number" id="productoCantidad" min="1" value="1" style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                    <button type="button" id="btnAgregar" style="padding: 11px 20px; background: #0f766e; color: white; border: none; border-radius: 6px; font-weight: bold; cursor:pointer;">+ Añadir</button>
                </div>
            </div>

            <div class="venta-container" style="margin-bottom: 20px; overflow-x: auto;">
                <h3 style="margin-bottom: 12px; font-size: 1rem; color: #1e293b;">Productos pedidos en línea de confección</h3>
                <div id="carritoStatus" style="margin-bottom: 12px; font-size: 0.95rem; color: #475569;">Carrito: 0 productos.</div>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #1A2B4C; color: white; text-align: left;">
                            <th style="padding: 12px;">Producto</th>
                            <th style="padding: 12px;">Color</th>
                            <th style="padding: 12px;">Talla</th>
                            <th style="padding: 12px;">Precio Base</th>
                            <th style="padding: 12px; width: 80px; text-align: center;">Cant</th>
                            <th style="padding: 12px; text-align: center; width: 120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="carritoBody"></tbody>
                </table>
            </div>

            <div class="venta-container" style="padding: 20px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; display: grid; gap: 15px; max-width: 550px; margin-left: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 600;">SubTotal</span>
                    <span id="txtTotal" style="font-weight: 700; color: #1d4ed8;">$0.00</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 600;">Descuento mayorista</span>
                    <span id="txtDescuento" style="font-weight: 700; color: #ca8a04;">$0.00</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 700; font-size: 1.1rem;">Total final</span>
                    <span id="txtTotalFinal" style="font-size: 1.4rem; font-weight: 800; color: #047857;">$0.00</span>
                </div>

                <div style="border-top: 2px dashed #cbd5e1; padding-top: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <label style="font-weight: 700;">Abono (pago inicial):</label>
                        <input type="number" id="inputAbono" name="abono" min="0" step="0.01" style="width: 150px; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right;" placeholder="$0.00">
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 700;">Saldo pendiente:</span>
                        <span id="txtSaldoPendiente" style="font-size: 1.2rem; font-weight: 800; color: #dc2626;">$0.00</span>
                    </div>
                </div>

                <input type="hidden" id="ventaJSON" name="venta_json">
                <input type="hidden" id="inputTotal" name="total_venta">
                <input type="hidden" id="pedido_id_hidden" name="pedido_id">
                <input type="hidden" name="venta_tipo" value="mayorista">

                <div style="display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
                    <a href="panel_vendedor.php" style="color: #475569; text-decoration: none; padding: 10px 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 500;">Cancelar</a>
                    <button type="submit" style="padding: 10px 20px; background: #1A2B4C; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Procesar Venta Mayorista</button>
                </div>
            </div>
        </form>
        <div id="pedidoBanner" style="position: fixed; right: 20px; bottom: 20px; background: #0ea5a4; color: white; padding: 10px 14px; border-radius: 8px; display: none; box-shadow: 0 6px 18px rgba(0,0,0,0.08);">
            Orden creada: <span id="pedidoBadge">OP #</span>
        </div>

        <!-- Modal de edición de ítem del carrito -->
        <div id="modalEditarItem" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:9999; align-items:center; justify-content:center;">
            <div style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:440px; box-shadow:0 20px 40px rgba(0,0,0,0.18); margin:16px;">
                <h3 style="margin:0 0 18px; color:#1A2B4C; font-size:1.1rem;">✏️ Editar producto</h3>
                <div style="display:grid; gap:12px;">
                    <div>
                        <label style="font-size:0.85rem; font-weight:600;">Producto / Prenda</label>
                        <input id="editNombre" type="text" style="width:100%; padding:9px 10px; margin-top:4px; border:1px solid #cbd5e1; border-radius:6px;">
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div>
                            <label style="font-size:0.85rem; font-weight:600;">Color</label>
                            <input id="editColor" type="text" style="width:100%; padding:9px 10px; margin-top:4px; border:1px solid #cbd5e1; border-radius:6px;">
                        </div>
                        <div>
                            <label style="font-size:0.85rem; font-weight:600;">Talla</label>
                            <input id="editTalla" type="text" style="width:100%; padding:9px 10px; margin-top:4px; border:1px solid #cbd5e1; border-radius:6px;">
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div>
                            <label style="font-size:0.85rem; font-weight:600;">Precio unitario ($)</label>
                            <input id="editPrecio" type="number" min="0" step="0.01" style="width:100%; padding:9px 10px; margin-top:4px; border:1px solid #cbd5e1; border-radius:6px;">
                        </div>
                        <div>
                            <label style="font-size:0.85rem; font-weight:600;">Cantidad</label>
                            <input id="editCantidad" type="number" min="1" style="width:100%; padding:9px 10px; margin-top:4px; border:1px solid #cbd5e1; border-radius:6px;">
                        </div>
                    </div>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:22px;">
                    <button type="button" id="btnCancelarEdicion" style="padding:9px 18px; background:#fff; border:1px solid #cbd5e1; border-radius:8px; font-weight:600; cursor:pointer; color:#475569;">Cancelar</button>
                    <button type="button" id="btnGuardarEdicion" style="padding:9px 18px; background:#1A2B4C; color:#fff; border:none; border-radius:8px; font-weight:700; cursor:pointer;">Guardar cambios</button>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="/unideportes-system/public/js/linea_confeccion.js?v=<?= time() ?>"></script>

<?php include(__DIR__ . '/footer.php'); ?>