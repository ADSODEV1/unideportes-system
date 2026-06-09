<?php
// ZONA 1: LOGIN (Filtros de Sesión y Consultas Preliminares)
session_start();
require_once __DIR__ . '/../config/bootstrap.php';
$pdo = app();

require_login(['vendedor', 'colaborador', 'admin']);

// Se añade 'nit_cedula' a la consulta para que esté disponible en los data-attributes de JavaScript
$stmtClientes = $pdo->query("SELECT id, nombre_completo, nit_cedula, direccion, barrio, ciudad, referencia_entrega FROM clientes ORDER BY nombre_completo ASC");
$res_clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

// Usamos nombres únicos de producto para que el vendedor seleccione la familia de artículo
$stmtProductos = $pdo->query("SELECT DISTINCT nombre FROM productos WHERE stock > 0 ORDER BY nombre ASC");
$res_productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    
<?php include(__DIR__ . "/sidebar_control.php"); ?>

    <main class="main-content-panel">
        <h1>Nueva Venta Directa</h1>
        <hr class="divider">

        <form action="../controllers/procesar_venta.php" method="POST" id="ventaForm">
            
            <div class="venta-container" style="display: flex; gap: 20px; margin-bottom: 20px;">
                <div style="flex: 1;">
                    <label><strong>Cliente:</strong></label>
                    <input type="text" list="listaClientes" id="clienteInput" placeholder="Buscar cliente..." style="width:100%; padding: 8px; margin-top: 5px;">
                    <datalist id="listaClientes">
                        <?php foreach ($res_clientes as $cli): ?>
                            <option 
                            data-id="<?= $cli['id'] ?>" 
                            data-direccion="<?= htmlspecialchars($cli['direccion'] ?? '') ?>"
                            data-barrio="<?= htmlspecialchars($cli['barrio'] ?? '') ?>"
                            data-ciudad="<?= htmlspecialchars($cli['ciudad'] ?: 'Sogamoso') ?>"
                            data-referencia="<?= htmlspecialchars($cli['referencia_entrega'] ?? '') ?>"
                            value="<?= htmlspecialchars($cli['nombre_completo']) ?>">
                            </option>
                        <?php endforeach; ?>
                    </datalist>
                    <input type="hidden" name="cliente_id" id="cliente_id_hidden">
                    
                    <div style="margin-top: 10px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <button type="button" id="btnToggleNuevoCliente" style="padding: 8px 12px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer;">Crear cliente nuevo</button>
                        <span style="font-size: 0.9rem; color: #4b5563;">Usa esta opción para crear un nuevo cliente.</span>
                    </div>

                    <div id="nuevoClienteSection" style="display: none; margin-top: 15px; padding: 15px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px;">
                        <h4 style="margin-top: 0; color: #1e293b;">Datos de Registro Rápido</h4>
                        <div style="display: grid; gap: 10px;">
                            <div>
                                <label>Nombre completo *</label>
                                <input type="text" name="nuevo_cliente_nombre_completo" id="nuevo_cliente_nombre_completo" style="width:100%; padding: 8px; margin-top: 5px;">
                            </div>
                            <div>
                                <label>NIT / Cédula *</label>
                                <input type="text" name="nuevo_cliente_nit_cedula" id="nuevo_cliente_nit_cedula" style="width:100%; padding: 8px; margin-top: 5px;">
                            </div>
                            <div>
                                <label>Teléfono</label>
                                <input type="text" name="nuevo_cliente_telefono" id="nuevo_cliente_telefono" style="width:100%; padding: 8px; margin-top: 5px;">
                            </div>
                            <div>
                                <label>Email</label>
                                <input type="email" name="nuevo_cliente_email" id="nuevo_cliente_email" style="width:100%; padding: 8px; margin-top: 5px;">
                            </div>
                            <div>
                                <label>Tipo de cliente</label>
                                <select name="nuevo_cliente_tipo_cliente" id="nuevo_cliente_tipo_cliente" style="width:100%; padding: 8px; margin-top: 5px;">
                                    <option value="Individual">Individual</option>
                                    <option value="Equipo">Equipo</option>
                                    <option value="Colegio">Colegio</option>
                                    <option value="Empresa">Empresa</option>
                                </select>
                            </div>

                            <div id="bloqueDireccionNuevoCliente" style="display: none; border-top: 1px dashed #cbd5e1; padding-top: 10px; margin-top: 5px;">
                                <div style="display: grid; gap: 10px;">
                                    <div>
                                        <label><strong>Dirección base de envío</strong></label>
                                        <input type="text" name="nuevo_cliente_direccion" id="nuevo_cliente_direccion" style="width:100%; padding: 8px; margin-top: 5px;" placeholder="Calle, Carrera, #">
                                    </div>
                                    <div>
                                        <label>Barrio</label>
                                        <input type="text" name="nuevo_cliente_barrio" id="nuevo_cliente_barrio" style="width:100%; padding: 8px; margin-top: 5px;">
                                    </div>
                                    <div>
                                        <label>Ciudad</label>
                                        <input type="text" name="nuevo_cliente_ciudad" id="nuevo_cliente_ciudad" value="Sogamoso" style="width:100%; padding: 8px; margin-top: 5px;">
                                    </div>
                                    <div>
                                        <label>Referencia de Entrega</label>
                                        <textarea name="nuevo_cliente_referencia_entrega" id="nuevo_cliente_referencia_entrega" rows="2" style="width:100%; padding: 8px; margin-top: 5px;" placeholder="Ej: Frente al parque..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label><strong>Método de Pago:</strong></label>
                    <select name="metodo_pago" id="metodo_pago" required style="width:100%; padding: 8px; margin-top: 5px;">
                        <option value="Efectivo">Efectivo</option>
                        <option value="Tarjeta">Tarjeta</option>
                        <option value="Transferencia">Transferencia</option>
                    </select>

                    <div id="seccionTransferencia" style="display: none; margin-top: 10px; background: #f8fafc; padding: 10px; border-radius: 4px; border: 1px solid #e2e8f0;">
                        <label><small><strong>Plataforma Virtual:</strong></small></label>
                        <select id="tipo_transferencia_select" style="width:100%; padding: 6px; margin-top: 5px;">
                            <option value="Nequi">Nequi</option>
                            <option value="Daviplata">Daviplata</option>
                            <option value="Otro">Otro ¿Cuál?</option>
                        </select>

                        <input type="text" id="otra_plataforma_input" placeholder="Ej: Breve, Bancolombia..." style="display: none; width: 100%; padding: 6px; margin-top: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                        <input type="hidden" name="tipo_transferencia" id="tipo_transferencia_final">
                    </div>

                    <div style="margin-top: 20px;">
                        <label><strong>Tipo de Entrega:</strong></label>
                        <select name="tipo_entrega" id="tipo_entrega" required style="width:100%; padding: 8px; margin-top: 5px;">
                            <option value="Tienda">Retiro en Tienda</option>
                            <option value="Domicilio">Envío a Domicilio</option>
                        </select>
                    </div>

                    <div id="seccionDomicilio" style="display: none; margin-top: 15px; padding: 15px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px;">
                        <h4 style="margin-top: 0; color: #b45309;">Datos de Envío para esta Venta</h4>
                        <div style="display: grid; gap: 10px;">
                            <div>
                                <label style="font-size: 0.85rem;">Dirección de Entrega *</label>
                                <input type="text" name="direccion_entrega" id="direccion_entrega" style="width:100%; padding: 6px; margin-top: 3px;">
                            </div>
                            <div>
                                <label style="font-size: 0.85rem;">Barrio</label>
                                <input type="text" name="barrio_entrega" id="barrio_entrega" style="width:100%; padding: 6px; margin-top: 3px;">
                            </div>
                            <div>
                                <label style="font-size: 0.85rem;">Ciudad *</label>
                                <input type="text" name="ciudad_entrega" id="ciudad_entrega" value="Sogamoso" style="width:100%; padding: 6px; margin-top: 3px;">
                            </div>
                            <div>
                                <label style="font-size: 0.85rem;">Observaciones / Referencias de Envío</label>
                                <textarea name="observaciones_entrega" id="observaciones_entrega" rows="2" style="width:100%; padding: 6px; margin-top: 3px;" placeholder="Indicaciones para el domiciliario..."></textarea>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="venta-container" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto; gap: 10px; margin-bottom: 20px; align-items: flex-end;">
                <div>
                    <label><strong>Producto:</strong></label>
                    <input type="text" list="listaProductos" id="productoInput" placeholder="Buscar producto..." style="width:100%; padding: 8px; margin-top: 5px;">
                    <datalist id="listaProductos">
                        <?php foreach ($res_productos as $prod): ?>
                            <option value="<?= htmlspecialchars($prod['nombre']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div id="wrapperProductoColor">
                    <label><strong>Color:</strong></label>
                    <input type="text" id="productoColor" placeholder="Selecciona primero un producto" disabled style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px; background: #f8fafc;">
                </div>

                <div id="wrapperProductoTalla">
                    <label><strong>Talla:</strong></label>
                    <input type="text" id="productoTalla" placeholder="Selecciona primero un color" disabled style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px; background: #f8fafc;">
                </div>

                <div>
                    <label><strong>Comentario:</strong></label>
                    <input type="text" id="productoComentario" placeholder="Ej: Cliente prefiere algodón" style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div>
                    <label><strong>Cantidad:</strong></label>
                    <input type="number" id="productoCantidad" value="1" min="1" style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center;">
                </div>

                <button type="button" id="btnAgregar" style="padding: 8px 15px; background: #10b981; color: white; border: none; border-radius: 4px; font-weight: bold; cursor:pointer;">+ Añadir</button>
            </div>

            <div class="venta-container" style="margin-bottom: 20px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #1A2B4C; color: white; text-align: left;">
                            <th style="padding: 10px;">Producto</th>
                            <th style="padding: 10px;">Color</th>
                            <th style="padding: 10px;">Talla</th>
                            <th style="padding: 10px;">Comentario</th>
                            <th style="padding: 10px;">Precio</th>
                            <th style="padding: 10px; width: 80px;">Cant</th>
                            <th style="padding: 10px;">Subtotal</th>
                            <th style="padding: 10px; text-align: center;">Quitar</th>
                        </tr>
                    </thead>
                    <tbody id="carritoBody"></tbody>
                </table>
            </div>

            <div class="venta-container" style="padding: 15px; text-align: right; background: #f8fafc;">
                <h3>Total: <span id="txtTotal" style="color: #E61E2A;">$0.00</span></h3>
                
                <div id="seccionCambio" style="margin-bottom: 15px; text-align: left; width: 250px; margin-left: auto;">
                    <label>Paga con:</label>
                    <input type="number" id="inputPagaCon" name="paga_con" style="width: 100%; padding: 6px;" min="0" step="0.01">
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

<script src="../public/js/ventas.js"></script>

<?php include(__DIR__ . "/footer.php"); ?>