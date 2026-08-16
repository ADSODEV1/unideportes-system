<?php
// views/nuevo_pedido.php — Entrada ÚNICA de pedidos de confección a medida
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/precios_helper.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_login(['admin', 'colaborador', 'vendedor']);

$pdo = app();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $cliente_id    = intval($_POST['cliente_id'] ?? 0);
        $fecha_entrega = trim($_POST['fecha_entrega'] ?? '');
        $tipo_entrega  = trim($_POST['tipo_entrega'] ?? 'Tienda');
        $direccion     = trim($_POST['direccion_entrega'] ?? '');
        $barrio        = trim($_POST['barrio_entrega'] ?? '');
        $ciudad        = trim($_POST['ciudad_entrega'] ?? '');
        $obs_entrega   = trim($_POST['observaciones_entrega'] ?? '');
        $descripcion   = trim($_POST['descripcion'] ?? '');
        $abono_inicial = round(floatval($_POST['abono'] ?? 0), 2);
        $metodo        = trim($_POST['metodo_pago'] ?? 'Efectivo');
        $plataforma    = trim($_POST['plataforma'] ?? '');
        $referencia    = trim($_POST['referencia'] ?? '');

        // ¿Viene registro rápido de cliente nuevo?
        $nc_nombre     = trim($_POST['nuevo_cliente_nombre_completo'] ?? '');
        $nc_nit        = trim($_POST['nuevo_cliente_nit_cedula'] ?? '');
        $crear_cliente = ($nc_nombre !== '' && $nc_nit !== '');

        if (!$crear_cliente && $cliente_id <= 0)
            throw new Exception("Busca un cliente o crea uno nuevo.");
        if ($fecha_entrega === '' || strtotime($fecha_entrega) === false)
            throw new Exception("Fecha de entrega obligatoria y válida.");
        if (!in_array($metodo, ['Efectivo', 'Tarjeta', 'Transferencia', 'Otro'], true)) $metodo = 'Efectivo';

        // ⚙️ Reglas de negocio EN SERVIDOR: líneas, mínimo 35, foto de precios, total
        $lineas       = construir_lineas_pedido($pdo, $_POST);
        $total_pedido = total_de_lineas($lineas);
        $unidades     = array_sum(array_column($lineas, 'cantidad'));

        // 💰 Regla de negocio: abono inicial >= 50% del total
        $abono_minimo = abono_minimo_de($total_pedido);
        if ($abono_inicial < $abono_minimo) {
            throw new Exception("El abono inicial debe ser al menos el " . CONFECCION_ABONO_MINIMO_PORCENTAJE
                . "% del total ($" . number_format($abono_minimo, 0, ',', '.') . ").");
        }

        if ($abono_inicial > $total_pedido)
            throw new Exception("El abono inicial no puede superar el total del pedido.");
        if ($tipo_entrega === 'Domicilio' && $direccion === '')
            throw new Exception("Indica la dirección de entrega.");

        $pdo->beginTransaction();

        // 🧑 Cliente nuevo: se crea dentro de la misma transacción (nunca queda huérfano)
        if ($crear_cliente) {
            $stmtCli = $pdo->prepare("INSERT INTO clientes
                (nombre_completo, nit_cedula, telefono, email, tipo_cliente,
                 direccion, barrio, ciudad, referencia_entrega)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtCli->execute([
                $nc_nombre,
                $nc_nit,
                trim($_POST['nuevo_cliente_telefono'] ?? '') ?: null,
                trim($_POST['nuevo_cliente_email'] ?? '') ?: null,
                trim($_POST['nuevo_cliente_tipo_cliente'] ?? 'Individual'),
                trim($_POST['nuevo_cliente_direccion'] ?? '') ?: null,
                trim($_POST['nuevo_cliente_barrio'] ?? '') ?: null,
                trim($_POST['nuevo_cliente_ciudad'] ?? '') ?: 'Sogamoso',
                trim($_POST['nuevo_cliente_referencia_entrega'] ?? '') ?: null,
            ]);
            $cliente_id = intval($pdo->lastInsertId());
        }

        $stmt = $pdo->prepare("INSERT INTO pedidos
            (cliente_id, detalle, descripcion, cantidad, total_pedido, estado,
             tipo_entrega, direccion_entrega, barrio_entrega, ciudad_entrega,
             observaciones_entrega, fecha_entrega, vendedor_id, abono, saldo_pendiente)
            VALUES (?, ?, ?, ?, ?, 'En Corte', ?, ?, ?, ?, ?, ?, ?, 0, ?)");
        $stmt->execute([
            $cliente_id,
            resumen_de_lineas($lineas),
            $descripcion !== '' ? $descripcion : null,
            $unidades,
            $total_pedido,
            $tipo_entrega,
            $direccion !== '' ? $direccion : null,
            $barrio !== '' ? $barrio : null,
            $ciudad !== '' ? $ciudad : null,
            $obs_entrega !== '' ? $obs_entrega : null,
            $fecha_entrega,
            $_SESSION['user_id'] ?? null,
            $total_pedido,
        ]);
        $pedido_id = intval($pdo->lastInsertId());

        $stmtDet = $pdo->prepare("INSERT INTO detalle_pedido
            (pedido_id, tipo_prenda_id, cantidad, precio_unitario, color, talla, comentario_vendedor)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($lineas as $l) {
            $stmtDet->execute([
                $pedido_id,
                $l['tipo_prenda_id'],
                $l['cantidad'],
                $l['precio_unitario'],
                $l['color'] !== '' ? $l['color'] : null,
                $l['talla'] !== '' ? $l['talla'] : null,
                $l['comentario'] !== '' ? $l['comentario'] : null,
            ]);
        }

        if ($abono_inicial > 0) {
            $stmtPago = $pdo->prepare("INSERT INTO pagos (id_pg_pedido, monto, metodo_pago, plataforma, referencia)
                                       VALUES (?, ?, ?, ?, ?)");
            $stmtPago->execute([$pedido_id, $abono_inicial, $metodo,
                $plataforma !== '' ? $plataforma : null,
                $referencia !== '' ? $referencia : null]);
        }

        sincronizar_saldo_pedido($pdo, $pedido_id);
        $pdo->commit();

        header("Location: ver_ticket_pedido.php?id=" . $pedido_id);
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $e->getMessage();
    }
}

$clientes = $pdo->query("SELECT id, nombre_completo, nit_cedula, direccion, barrio, ciudad, referencia_entrega
                         FROM clientes ORDER BY nombre_completo ASC")->fetchAll(PDO::FETCH_ASSOC);
$prendas  = precios_base_activos($pdo);

$lineas_post = [];
foreach (($_POST['tipo_prenda_id'] ?? []) as $i => $id) {
    $lineas_post[] = [
        'id'   => intval($id),
        'cant' => intval($_POST['cantidad_linea'][$i] ?? 0),
        'color'=> trim($_POST['color_linea'][$i] ?? ''),
        'talla'=> trim($_POST['talla_linea'][$i] ?? ''),
        'com'  => trim($_POST['comentario_linea'][$i] ?? ''),
    ];
}
$cliente_busqueda = trim($_POST['cliente_busqueda'] ?? '');

$rol = $_SESSION['role'] ?? 'vendedor';
$panel_volver = ($rol === 'admin') ? 'panel_admin.php' : 'panel_vendedor.php';

include(__DIR__ . "/header.php");
?>
<div class="container admin-layout">
<?php include(__DIR__ . "/sidebar_control.php"); ?>
<main class="main-content-panel">

<h1>Nuevo Pedido de Confección</h1>
<hr class="divider">

<?php if ($error): ?>
<div style="margin-bottom: 18px; padding: 12px; background: #fee2e2; color: #991b1b; border-radius: 6px; border-left: 4px solid #ef4444;">
    ⚠️ <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<form method="POST" id="form-pedido">

    <!-- SECCIÓN SUPERIOR: CLIENTE / ENTREGA -->
    <div class="venta-container" style="display: flex; gap: 20px; margin-bottom: 20px;">
        <div style="flex: 1;">
            <label><strong>Cliente:</strong></label>
            <input type="text" list="listaClientes" id="clienteInput" name="cliente_busqueda"
                   placeholder="Buscar cliente..." value="<?= htmlspecialchars($cliente_busqueda) ?>"
                   style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; background: var(--input-bg);">
            <datalist id="listaClientes">
                <?php foreach ($clientes as $cli): ?>
                <option data-id="<?= $cli['id'] ?>"
                        data-direccion="<?= htmlspecialchars($cli['direccion'] ?? '') ?>"
                        data-barrio="<?= htmlspecialchars($cli['barrio'] ?? '') ?>"
                        data-ciudad="<?= htmlspecialchars($cli['ciudad'] ?: 'Sogamoso') ?>"
                        data-referencia="<?= htmlspecialchars($cli['referencia_entrega'] ?? '') ?>"
                        value="<?= htmlspecialchars($cli['nombre_completo']) ?>"></option>
                <?php endforeach; ?>
            </datalist>
            <input type="hidden" name="cliente_id" id="cliente_id_hidden" value="<?= intval($_POST['cliente_id'] ?? 0) ?>">

            <div style="margin-top: 10px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <button type="button" id="btnToggleNuevoCliente" style="padding: 8px 12px; background: var(--primary); color: white; border: none; border-radius: 6px; cursor: pointer;">Crear cliente nuevo</button>
                <span style="font-size: 0.9rem; color: var(--text-light);">Usa esta opción si el cliente no existe.</span>
            </div>

            <div id="nuevoClienteSection" style="display: none; margin-top: 15px; padding: 15px; background: var(--input-bg); border: 1px solid var(--border); border-radius: 10px;">
                <h4 style="margin-top: 0; color: var(--text);">Datos de Registro Rápido</h4>
                <div style="display: grid; gap: 10px;">
                    <div><label>Nombre completo *</label><input type="text" name="nuevo_cliente_nombre_completo" value="<?= htmlspecialchars($_POST['nuevo_cliente_nombre_completo'] ?? '') ?>" style="width:100%; padding: 8px; margin-top: 5px;"></div>
                    <div><label>NIT / Cédula *</label><input type="text" name="nuevo_cliente_nit_cedula" value="<?= htmlspecialchars($_POST['nuevo_cliente_nit_cedula'] ?? '') ?>" style="width:100%; padding: 8px; margin-top: 5px;"></div>
                    <div><label>Teléfono</label><input type="text" name="nuevo_cliente_telefono" value="<?= htmlspecialchars($_POST['nuevo_cliente_telefono'] ?? '') ?>" style="width:100%; padding: 8px; margin-top: 5px;"></div>
                    <div><label>Email</label><input type="email" name="nuevo_cliente_email" value="<?= htmlspecialchars($_POST['nuevo_cliente_email'] ?? '') ?>" style="width:100%; padding: 8px; margin-top: 5px;"></div>
                    <div>
                        <label>Tipo de cliente</label>
                        <select name="nuevo_cliente_tipo_cliente" id="nuevo_cliente_tipo_cliente" style="width:100%; padding: 8px; margin-top: 5px;">
                            <?php foreach (['Individual','Equipo','Colegio','Empresa'] as $t): ?>
                            <option <?= ($_POST['nuevo_cliente_tipo_cliente'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="bloqueDireccionNuevoCliente" style="display: none; border-top: 1px dashed var(--border); padding-top: 10px; margin-top: 5px;">
                        <div style="display: grid; gap: 10px;">
                            <div><label><strong>Dirección base de envío</strong></label><input type="text" name="nuevo_cliente_direccion" placeholder="Calle, Carrera, #" value="<?= htmlspecialchars($_POST['nuevo_cliente_direccion'] ?? '') ?>" style="width:100%; padding: 8px; margin-top: 5px;"></div>
                            <div><label>Barrio</label><input type="text" name="nuevo_cliente_barrio" value="<?= htmlspecialchars($_POST['nuevo_cliente_barrio'] ?? '') ?>" style="width:100%; padding: 8px; margin-top: 5px;"></div>
                            <div><label>Ciudad</label><input type="text" name="nuevo_cliente_ciudad" value="Sogamoso" style="width:100%; padding: 8px; margin-top: 5px;"></div>
                            <div><label>Referencia de Entrega</label><textarea name="nuevo_cliente_referencia_entrega" rows="2" placeholder="Ej: Frente al parque..." style="width:100%; padding: 8px; margin-top: 5px;"><?= htmlspecialchars($_POST['nuevo_cliente_referencia_entrega'] ?? '') ?></textarea></div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 15px;">
                <label><strong>Fecha de entrega *</strong></label>
                <input type="date" name="fecha_entrega" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['fecha_entrega'] ?? '') ?>" required
                       style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; background: var(--input-bg);">
            </div>
        </div>

        <div style="flex: 1;">
            <label><strong>Tipo de Entrega:</strong></label>
            <select name="tipo_entrega" id="tipo_entrega" required style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; background: var(--input-bg);">
                <option value="Tienda" <?= ($_POST['tipo_entrega'] ?? '') === 'Tienda' ? 'selected' : '' ?>>Retiro en Tienda</option>
                <option value="Domicilio" <?= ($_POST['tipo_entrega'] ?? '') === 'Domicilio' ? 'selected' : '' ?>>Envío a Domicilio</option>
            </select>
            <div id="seccionDomicilio" style="display: none; margin-top: 15px; padding: 15px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px;">
                <h4 style="margin-top: 0; color: #b45309;">Datos de Envío para este Pedido</h4>
                <div style="display: grid; gap: 10px;">
                    <div><label style="font-size: 0.85rem;">Dirección de Entrega *</label><input type="text" id="direccion_entrega" name="direccion_entrega" value="<?= htmlspecialchars($_POST['direccion_entrega'] ?? '') ?>" style="width:100%; padding: 6px; margin-top: 3px;"></div>
                    <div><label style="font-size: 0.85rem;">Barrio</label><input type="text" id="barrio_entrega" name="barrio_entrega" value="<?= htmlspecialchars($_POST['barrio_entrega'] ?? '') ?>" style="width:100%; padding: 6px; margin-top: 3px;"></div>
                    <div><label style="font-size: 0.85rem;">Ciudad</label><input type="text" id="ciudad_entrega" name="ciudad_entrega" value="<?= htmlspecialchars($_POST['ciudad_entrega'] ?? '') ?: 'Sogamoso' ?>" style="width:100%; padding: 6px; margin-top: 3px;"></div>
                    <div><label style="font-size: 0.85rem;">Observaciones / Referencias de Envío</label><textarea id="observaciones_entrega" name="observaciones_entrega" rows="2" style="width:100%; padding: 6px; margin-top: 3px;"><?= htmlspecialchars($_POST['observaciones_entrega'] ?? '') ?></textarea></div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN PRENDAS -->
    <div class="venta-container" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1.5fr 90px auto; gap: 10px; margin-bottom: 20px; align-items: flex-end;">
        <div>
            <label><strong>Tipo de Prenda:</strong></label>
            <select id="selPrenda" style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; background: var(--input-bg);">
                <option value="">-- Seleccione --</option>
                <?php foreach ($prendas as $p): ?>
                <option value="<?= (int)$p['id'] ?>" data-precio="<?= $p['precio_base'] ?>" data-nombre="<?= htmlspecialchars($p['tipo_prenda']) ?>">
                    <?= htmlspecialchars($p['tipo_prenda']) ?> — $<?= number_format($p['precio_base'], 0, ',', '.') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><label><strong>Color:</strong></label><input type="text" id="inpColor" maxlength="50" placeholder="Ej: Rojo" style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;"></div>
        <div><label><strong>Talla:</strong></label><input type="text" id="inpTalla" maxlength="10" placeholder="Ej: M" style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;"></div>
        <div><label><strong>Comentario:</strong></label><input type="text" id="inpCom" maxlength="500" placeholder="Ej: Logo bordado" style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;"></div>
        <div><label><strong>Cantidad:</strong></label><input type="number" id="inpCant" value="1" min="1" style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; text-align: center;"></div>
        <button type="button" id="btnAgregarLinea" style="padding: 8px 15px; background: var(--success); color: white; border: none; border-radius: 4px; font-weight: bold; cursor:pointer;">+ Añadir</button>
    </div>

    <!-- TABLA CARRITO DE PRENDAS -->
    <div class="venta-container" style="margin-bottom: 20px;">
        <div class="table-responsive">
            <table class="tabla-maestra">
                <thead>
                    <tr>
                        <th>Prenda</th><th>Color</th><th>Talla</th><th>Comentario</th>
                        <th>Precio</th><th style="width: 80px;">Cant</th><th>Subtotal</th><th style="text-align: center;">Quitar</th>
                    </tr>
                </thead>
                <tbody id="lineasBody"></tbody>
            </table>
        </div>
    </div>

    <!-- RESUMEN + PAGO INICIAL -->
    <div class="venta-container" style="padding: 20px; background: var(--input-bg); border-radius: 12px; border: 1px solid var(--border); display: flex; flex-wrap: wrap; gap: 30px; max-width: 900px; margin-left: auto;">
        <div style="flex: 1; min-width: 250px; display: grid; gap: 15px;">
            <h3 style="margin: 0 0 10px 0; font-size: 1.1rem; color: var(--text);">🧵 Resumen del Pedido</h3>
            <div style="display: flex; justify-content: space-between;"><span style="font-weight: 600;">Unidades</span><span id="est-unidades" style="font-weight: 700;">0</span></div>
            <div id="est-min" style="font-size: 0.9rem; font-weight: 700; color: var(--danger, #dc2626);">⚠ Mínimo <?= CONFECCION_CANTIDAD_MINIMA ?> unidades</div>
            <div style="display: flex; justify-content: space-between; border-top: 2px dashed var(--border); padding-top: 15px; margin-top: 5px;">
                <span style="font-weight: 700; font-size: 1.2rem;">TOTAL ESTIMADO</span>
                <span id="est-total" style="font-size: 1.5rem; font-weight: 800; color: var(--primary);">$0</span>
            </div>
        </div>
        <div style="flex: 1.2; min-width: 300px; display: grid; gap: 12px;">
            <h3 style="margin: 0 0 5px 0; font-size: 1.1rem; color: var(--text);">💳 Abono Inicial</h3>
            <div>
                <label style="font-size: 0.9rem; font-weight: 600;">💰 Abono * (mínimo <?= CONFECCION_ABONO_MINIMO_PORCENTAJE ?>%)</label>
                <input type="number" min="0" step="100" name="abono" id="inpAbono" value="<?= htmlspecialchars($_POST['abono'] ?? '') ?>"
                       style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;">
                <small id="hint-abono" style="display:block; margin-top:4px; font-weight:700; color: var(--danger,#dc2626);">
                    Agrega prendas para ver el abono mínimo.
                </small>
            </div>
            <div>
                <label style="font-size: 0.9rem; font-weight: 600;">Método</label>
                <select name="metodo_pago" style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;">
                    <?php foreach (['Efectivo','Tarjeta','Transferencia','Otro'] as $m): ?>
                    <option <?= ($_POST['metodo_pago'] ?? '') === $m ? 'selected' : '' ?>><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div><label style="font-size: 0.9rem; font-weight: 600;">Plataforma</label><input type="text" name="plataforma" placeholder="Nequi, Daviplata…" value="<?= htmlspecialchars($_POST['plataforma'] ?? '') ?>" style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;"></div>
                <div><label style="font-size: 0.9rem; font-weight: 600;">Referencia</label><input type="text" name="referencia" value="<?= htmlspecialchars($_POST['referencia'] ?? '') ?>" style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;"></div>
            </div>
            <div><label style="font-size: 0.9rem; font-weight: 600;">Observaciones de confección</label><input type="text" name="descripcion" value="<?= htmlspecialchars($_POST['descripcion'] ?? '') ?>" style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;"></div>
        </div>
        <div style="display: flex; gap: 10px; margin-top: 15px; width: 100%;">
            <a href="<?= $panel_volver ?>" style="flex: 1; text-align: center; color: var(--text-light); text-decoration: none; padding: 12px 16px; border: 1px solid var(--border); border-radius: 8px; font-weight: 600; background: var(--card);">Cancelar</a>
            <button type="submit" class="btn-primary" style="flex: 2; border: none; cursor: pointer; padding: 12px; font-size: 1rem; font-weight: bold;">💾 Crear Pedido y Generar Ticket</button>
        </div>
    </div>
</form>

</main>
</div>

<script>
var MIN = <?= (int)CONFECCION_CANTIDAD_MINIMA ?>;
var POSTED = <?= json_encode($lineas_post) ?>;
var selPrenda = document.getElementById('selPrenda');
var tbody = document.getElementById('lineasBody');

/* ---------- CLIENTE: búsqueda con datalist + autocompletado de envío ---------- */
var clienteInput = document.getElementById('clienteInput');
var clienteHidden = document.getElementById('cliente_id_hidden');

function sincronizarCliente() {
    var opts = document.querySelectorAll('#listaClientes option');
    var encontrado = null;
    for (var i = 0; i < opts.length; i++) {
        if (opts[i].value === clienteInput.value) { encontrado = opts[i]; break; }
    }
    if (encontrado) {
        clienteHidden.value = encontrado.getAttribute('data-id');
        var dirEntrega = document.getElementById('direccion_entrega');
        var barrioEntrega = document.getElementById('barrio_entrega');
        var ciudadEntrega = document.getElementById('ciudad_entrega');
        var obsEntrega = document.getElementById('observaciones_entrega');
        if (dirEntrega) dirEntrega.value = encontrado.getAttribute('data-direccion') || '';
        if (barrioEntrega) barrioEntrega.value = encontrado.getAttribute('data-barrio') || '';
        if (ciudadEntrega) ciudadEntrega.value = encontrado.getAttribute('data-ciudad') || 'Sogamoso';
        if (obsEntrega) obsEntrega.value = encontrado.getAttribute('data-referencia') || '';
    } else {
        clienteHidden.value = 0;
    }
}
if (clienteInput) {
    clienteInput.addEventListener('input', sincronizarCliente);
    clienteInput.addEventListener('change', sincronizarCliente);
}

/* ---------- Toggle registro rápido de cliente ---------- */
var secNuevo = document.getElementById('nuevoClienteSection');
var btnToggleNuevo = document.getElementById('btnToggleNuevoCliente');
if (btnToggleNuevo && secNuevo) {
    btnToggleNuevo.addEventListener('click', function () {
        secNuevo.style.display = (secNuevo.style.display === 'none') ? 'block' : 'none';
    });
}
var tipoCliente = document.getElementById('nuevo_cliente_tipo_cliente');
function toggleDirNuevo() {
    var bloque = document.getElementById('bloqueDireccionNuevoCliente');
    if (bloque && tipoCliente) {
        bloque.style.display = (tipoCliente.value !== 'Individual') ? 'block' : 'none';
    }
}
if (tipoCliente) {
    tipoCliente.addEventListener('change', toggleDirNuevo);
    toggleDirNuevo();
}

/* ---------- Toggle domicilio del pedido ---------- */
var tipoEntrega = document.getElementById('tipo_entrega');
function toggleDomicilio() {
    var seccion = document.getElementById('seccionDomicilio');
    if (seccion && tipoEntrega) {
        seccion.style.display = (tipoEntrega.value === 'Domicilio') ? 'block' : 'none';
    }
}
if (tipoEntrega) {
    tipoEntrega.addEventListener('change', toggleDomicilio);
    toggleDomicilio();
}

/* ---------- Carrito de prendas ---------- */
function esc(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function fmt(n) { return '$' + Number(n).toLocaleString('es-CO'); }

function agregarLinea(l) {
    l = l || {};
    var id    = l.id || parseInt(selPrenda.value, 10) || 0;
    var cant  = l.cant || parseInt(document.getElementById('inpCant').value, 10) || 0;
    var color = (l.color !== undefined ? l.color : document.getElementById('inpColor').value).trim();
    var talla = (l.talla !== undefined ? l.talla : document.getElementById('inpTalla').value).trim();
    var com   = (l.com   !== undefined ? l.com   : document.getElementById('inpCom').value).trim();

    if (!id || cant <= 0) {
        selPrenda.style.borderColor = id ? 'var(--border)' : 'var(--danger, #dc2626)';
        return;
    }
    var opt = selPrenda.querySelector('option[value="' + id + '"]');
    if (!opt) return;
    var nombre = opt.getAttribute('data-nombre');
    var precio = parseFloat(opt.getAttribute('data-precio')) || 0;
    var sub = precio * cant;

    var tr = document.createElement('tr');
    tr.setAttribute('data-cant', cant);
    tr.setAttribute('data-sub', sub);
    tr.innerHTML =
        '<td>' + esc(nombre) +
            '<input type="hidden" name="tipo_prenda_id[]" value="' + id + '">' +
            '<input type="hidden" name="cantidad_linea[]" value="' + cant + '">' +
            '<input type="hidden" name="color_linea[]" value="' + esc(color) + '">' +
            '<input type="hidden" name="talla_linea[]" value="' + esc(talla) + '">' +
            '<input type="hidden" name="comentario_linea[]" value="' + esc(com) + '"></td>' +
        '<td>' + (esc(color) || '—') + '</td>' +
        '<td>' + (esc(talla) || '—') + '</td>' +
        '<td>' + (esc(com) || '') + '</td>' +
        '<td style="text-align:right;">' + fmt(precio) + '</td>' +
        '<td style="text-align:center;">' + cant + '</td>' +
        '<td style="text-align:right; font-weight:700;">' + fmt(sub) + '</td>' +
        '<td style="text-align:center;"><button type="button" class="btn-quitar" style="background: var(--danger, #dc2626); color:#fff; border:none; border-radius:4px; padding:4px 9px; cursor:pointer;">✕</button></td>';
    tbody.appendChild(tr);

    selPrenda.value = '';
    document.getElementById('inpCant').value = 1;
    document.getElementById('inpColor').value = '';
    document.getElementById('inpTalla').value = '';
    document.getElementById('inpCom').value = '';
    recalc();
}

var PCT_ABONO = <?= (int)CONFECCION_ABONO_MINIMO_PORCENTAJE ?>;
var TOTAL_ACTUAL = 0;

function money(n) { return '$' + Math.round(n).toLocaleString('es-CO'); }

function pintarAbono() {
    var inp = document.getElementById('inpAbono');
    var hint = document.getElementById('hint-abono');
    if (!inp) return;

    var min = TOTAL_ACTUAL * PCT_ABONO / 100;
    inp.min = min.toFixed(2);
    var val = parseFloat(inp.value) || 0;

    if (!hint) return;
    if (TOTAL_ACTUAL <= 0) {
        hint.textContent = 'Agrega prendas para ver el abono mínimo.';
        hint.style.color = 'var(--danger, #dc2626)';
    } else if (val < min) {
        hint.textContent = '⚠ Abono mínimo (' + PCT_ABONO + '%): ' + money(min);
        hint.style.color = 'var(--danger, #dc2626)';
    } else {
        hint.textContent = '✔ Abono mínimo (' + PCT_ABONO + '%): ' + money(min);
        hint.style.color = '#059669';
    }
}

function recalc() {
    var unidades = 0, total = 0;
    var rows = tbody.querySelectorAll('tr');
    for (var i = 0; i < rows.length; i++) {
        unidades += parseInt(rows[i].getAttribute('data-cant'), 10) || 0;
        total += parseFloat(rows[i].getAttribute('data-sub')) || 0;
    }
    document.getElementById('est-unidades').textContent = unidades;
    document.getElementById('est-total').textContent = fmt(total);
    var badge = document.getElementById('est-min');
    if (unidades >= MIN) {
        badge.textContent = '✔ Cumple mínimo de ' + MIN + ' unidades';
        badge.style.color = '#059669';
    } else {
        badge.textContent = '⚠ Mínimo ' + MIN + ' unidades';
        badge.style.color = 'var(--danger, #dc2626)';
    }
    TOTAL_ACTUAL = total;
    pintarAbono();
}

var btnAgregarLinea = document.getElementById('btnAgregarLinea');
if (btnAgregarLinea) {
    btnAgregarLinea.addEventListener('click', function () { agregarLinea(); });
}
tbody.addEventListener('click', function (e) {
    if (e.target.classList.contains('btn-quitar')) { e.target.closest('tr').remove(); recalc(); }
});

var inpAbono = document.getElementById('inpAbono');
if (inpAbono) {
    inpAbono.addEventListener('input', pintarAbono);
}

if (POSTED.length) { POSTED.forEach(agregarLinea); }
recalc();
</script>

<?php include(__DIR__ . "/footer.php"); ?>