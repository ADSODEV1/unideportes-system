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

// 2. Traer productos disponibles con stock activo
$stmtProductos = $pdo->query("SELECT id, nombre, precio, stock FROM productos WHERE stock > 0 ORDER BY nombre ASC");
$res_productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

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
            <a href="/unideportes-system/views/nueva_venta.php" class="btn-primary btn-icon-gap">
                <span>🛒</span> Volver a Venta Directa
            </a>
        </div>

        <div id="mensajeAlerta" style="display: none; padding: 12px; margin-bottom: 20px; border-radius: 8px; font-weight: 500;"></div>

        <form action="../controllers/procesar_pedido.php" method="POST" id="formVentaMayorista">
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
                    <div style="flex: 1; min-width: 200px;">
                        <label><strong>Producto:</strong></label>
                        <input type="text" list="listaProductos" id="productoInput" placeholder="Buscar producto..." style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px;" autocomplete="off">
                        <datalist id="listaProductos">
                            <?php foreach ($res_productos as $prod): ?>
                                <option 
                                    value="<?= htmlspecialchars($prod['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-id="<?= $prod['id'] ?>"
                                    data-precio="<?= $prod['precio'] ?>"
                                    data-stock="<?= $prod['stock'] ?>">
                                    Precio Base: $<?= number_format($prod['precio'], 2) ?> | Stock: <?= $prod['stock'] ?>
                                </option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div id="wrapperProductoColor" style="min-width: 130px;">
                        <label><strong>Color:</strong></label>
                        <input type="text" id="productoColor" placeholder="Selecciona producto" disabled style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px; background: #f8fafc;">
                    </div>
                    <div id="wrapperProductoTalla" style="min-width: 130px;">
                        <label><strong>Talla:</strong></label>
                        <input type="text" id="productoTalla" placeholder="Selecciona color" disabled style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px; background: #f8fafc;">
                    </div>
                    <button type="button" id="btnAgregar" style="padding: 11px 20px; background: #0f766e; color: white; border: none; border-radius: 6px; font-weight: bold; cursor:pointer;">+ Añadir</button>
                </div>
            </div>

            <div class="venta-container" style="margin-bottom: 20px; overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #1A2B4C; color: white; text-align: left;">
                            <th style="padding: 12px;">Producto</th>
                            <th style="padding: 12px;">Color</th>
                            <th style="padding: 12px;">Talla</th>
                            <th style="padding: 12px;">Precio Base</th>
                            <th style="padding: 12px; width: 80px; text-align: center;">Cant</th>
                            <th style="padding: 12px; width: 110px;">Descuento</th>
                            <th style="padding: 12px;">Subtotal</th>
                            <th style="padding: 12px; text-align: center; width: 70px;">Quitar</th>
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
                <input type="hidden" name="venta_tipo" value="mayorista">

                <div style="display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
                    <a href="panel_vendedor.php" style="color: #475569; text-decoration: none; padding: 10px 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 500;">Cancelar</a>
                    <button type="submit" style="padding: 10px 20px; background: #1A2B4C; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Procesar Venta Mayorista</button>
                </div>
            </div>
        </form>
    </main>
</div>

<script src="../public/js/linea_confeccion.js"></script>

<?php include(__DIR__ . '/footer.php'); ?>