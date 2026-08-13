<?php
// views/nuevo_pedido.php — Pedido de Confección (catálogo obligatorio)
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login(['admin', 'colaborador', 'vendedor']);

$pdo = app();
$error = null;

// ✅ REGLAS DE NEGOCIO
$CANTIDAD_MINIMA  = 35;   // mínimo de prendas por orden de confección
$ABONO_MINIMO_PCT = 50;   // % mínimo de abono inicial

// ✅ CATÁLOGO: solo prendas activas de Gestión de Precios de Confección
$catalogo = $pdo->query("SELECT id, tipo_prenda, precio_base FROM precios_base_confeccion WHERE activo = 1 ORDER BY tipo_prenda ASC")->fetchAll(PDO::FETCH_ASSOC);

// 1. CREACIÓN RÁPIDA DE CLIENTE VÍA AJAX (sin cambios)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_crear_cliente'])) {
    header('Content-Type: application/json');
    $nombre = trim($_POST['nombre_completo'] ?? '');
    $nit = trim($_POST['nit_cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    if (empty($nombre) || empty($nit)) {
        echo json_encode(['status' => 'error', 'message' => 'El nombre y el NIT/Cédula son obligatorios.']);
        exit;
    }

    try {
        $check = $pdo->prepare("SELECT id FROM clientes WHERE nit_cedula = ?");
        $check->execute([$nit]);
        if ($check->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El NIT o Cédula ya está registrado.']);
            exit;
        }

        $codigo = 'CLI-' . substr(md5(time()), 0, 4);
        $stmt = $pdo->prepare("INSERT INTO clientes (codigo_descriptivo, nombre_completo, nit_cedula, telefono, estado) VALUES (?, ?, ?, ?, 'activo')");
        $stmt->execute([$codigo, $nombre, $nit, $telefono]);

        echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId(), 'nombre' => $nombre . ' (' . $nit . ')']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar el cliente.']);
        exit;
    }
}

// 2. CREACIÓN DE LA ORDEN DE FABRICACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_pedido'])) {
    $cliente_id            = intval($_POST['cliente_id'] ?? 0);
    $vendedor_id           = intval($_SESSION['user_id'] ?? 0);
    $tipo_prenda_id        = intval($_POST['tipo_prenda_id'] ?? 0);
    $descripcion           = trim($_POST['descripcion'] ?? '');
    $cantidad              = intval($_POST['cantidad'] ?? 0);
    $abono                 = floatval($_POST['abono'] ?? 0);
    $fecha_entrega         = trim($_POST['fecha_entrega'] ?? '');
    $tipo_entrega          = trim($_POST['tipo_entrega'] ?? 'Tienda');
    $direccion_entrega     = trim($_POST['direccion_entrega'] ?? '');
    $barrio_entrega        = trim($_POST['barrio_entrega'] ?? '');
    $ciudad_entrega        = trim($_POST['ciudad_entrega'] ?? 'Sogamoso');
    $observaciones_entrega = trim($_POST['observaciones_entrega'] ?? '');

    if ($tipo_entrega === 'Domicilio' && (empty($direccion_entrega) || empty($barrio_entrega) || empty($ciudad_entrega))) {
        $error = 'Para envío a domicilio, por favor complete dirección, barrio y ciudad.';
    } elseif ($cliente_id <= 0) {
        $error = 'Seleccione un cliente registrado.';
    } elseif (!$fecha_entrega) {
        $error = 'Seleccione la fecha comprometida de entrega.';
    } elseif ($cantidad < $CANTIDAD_MINIMA) {
        $error = "La cantidad mínima para confección es {$CANTIDAD_MINIMA} prendas (recibió {$cantidad}).";
    } else {
        // ✅ SOLO CATÁLOGO: el servidor recalcula el precio desde Gestión de Precios
        $stCat = $pdo->prepare("SELECT tipo_prenda, precio_base FROM precios_base_confeccion WHERE id = ? AND activo = 1");
        $stCat->execute([$tipo_prenda_id]);
        $cat = $stCat->fetch(PDO::FETCH_ASSOC);

        if (!$cat) {
            $error = 'Solo pueden venderse prendas que existan en Precios de Confección.';
        } else {
            $precio_unitario = floatval($cat['precio_base']);
            $total_pedido   = $precio_unitario * $cantidad;

            // ✅ ABONO MÍNIMO 50%
            if ($abono < $total_pedido * ($ABONO_MINIMO_PCT / 100)) {
                $error = 'El abono inicial debe ser mínimo el ' . $ABONO_MINIMO_PCT . '% del total ($'
                       . number_format($total_pedido * 0.5, 0, ',', '.') . '). Recibido: $'
                       . number_format($abono, 0, ',', '.') . '.';
            } else {
                try {
                    $pdo->beginTransaction();

                    $detalle = $cat['tipo_prenda'] . ' x' . $cantidad;

                    $stmtPed = $pdo->prepare("INSERT INTO pedidos
                        (cliente_id, vendedor_id, detalle, descripcion, cantidad, total_pedido, abono, saldo_pendiente,
                         estado, fecha_entrega, tipo_entrega, direccion_entrega, barrio_entrega, ciudad_entrega, observaciones_entrega)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'En Corte', ?, ?, ?, ?, ?, ?)");
                    $stmtPed->execute([
                        $cliente_id, $vendedor_id, $detalle, $descripcion, $cantidad, $total_pedido,
                        $abono, $total_pedido - $abono,
                        $fecha_entrega, $tipo_entrega,
                        $direccion_entrega ?: null, $barrio_entrega ?: null, $ciudad_entrega ?: null, $observaciones_entrega ?: null
                    ]);
                    $pedido_id = $pdo->lastInsertId();

                    // ✅ Detalle con FK al catálogo (trazabilidad de producción)
                    $pdo->prepare("INSERT INTO detalle_pedido
                        (pedido_id, tipo_prenda_id, cantidad, precio_unitario, precio_base_ref, comentario_vendedor)
                        VALUES (?, ?, ?, ?, ?, ?)")
                        ->execute([$pedido_id, $tipo_prenda_id, $cantidad, $precio_unitario, $precio_unitario, $descripcion]);

                    $pdo->commit();
                    header("Location: ver_ticket_pedido.php?id=" . $pedido_id);
                    exit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Error en el sistema al registrar la orden: " . $e->getMessage();
                }
            }
        }
    }
}

// Clientes para el selector
$clientes = $pdo->query("SELECT id, nombre_completo, nit_cedula FROM clientes WHERE estado = 'activo' ORDER BY nombre_completo ASC")->fetchAll(PDO::FETCH_ASSOC);

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . "/sidebar_control.php"); ?>

    <main class="main-content-panel" style="max-width: 720px; margin: 0 auto;">
        <div class="page-header">
            <h1>📝 Nueva Orden de Fabricación Mayorista</h1>
            <p>Genera la orden de taller y emite el ticket físico de abono para el cliente.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="formulario-maestro" id="formConfeccion">
            <input type="hidden" name="crear_pedido" value="1">

            <div>
                <label class="form-label">Seleccionar Cliente *</label>
                <div style="display: flex; gap: 10px;">
                    <select name="cliente_id" id="cliente_id" required class="form-select" style="flex: 1;">
                        <option value="">-- Seleccione un cliente registrado --</option>
                        <?php foreach ($clientes as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre_completo']) ?> (<?= htmlspecialchars($c['nit_cedula']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn-secondary" onclick="abrirModalCliente()" style="white-space: nowrap; padding: 0 15px;">➕ Nuevo Cliente</button>
                </div>
            </div>

            <!-- ✅ CATÁLOGO OBLIGATORIO (reemplaza el texto libre) -->
            <div>
                <label class="form-label">Tipo de Prenda (Catálogo de Confección) *</label>
                <select name="tipo_prenda_id" id="tipo_prenda_id" required class="form-select">
                    <option value="">-- Seleccione un tipo de prenda --</option>
                    <?php foreach ($catalogo as $c): ?>
                        <option value="<?= $c['id'] ?>" data-precio="<?= $c['precio_base'] ?>">
                            <?= htmlspecialchars($c['tipo_prenda']) ?> · Base: $<?= number_format($c['precio_base'], 0, ',', '.') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color:#64748b;">Solo se pueden vender prendas activas en Gestión de Precios de Confección.</small>
            </div>

            <div>
                <label class="form-label">Especificaciones de Fabricación (Tallas, Colores, Escudos)</label>
                <textarea name="descripcion" rows="3" placeholder="Ej: Camisetas en tela Dry-Fit, números estampados en espalda. Tallas: 10 M, 15 L..."
                class="form-textarea"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label class="form-label">Cantidad de Prendas * (mín. <?= $CANTIDAD_MINIMA ?>)</label>
                    <input type="number" name="cantidad" id="cantidad" min="<?= $CANTIDAD_MINIMA ?>" value="<?= $CANTIDAD_MINIMA ?>" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Monto de Abono Inicial ($) * (mín. <?= $ABONO_MINIMO_PCT ?>%)</label>
                    <input type="number" name="abono" id="abono" min="0" step="1000" required class="form-input">
                    <small id="abonoMinInfo" style="color:#92400e;">Abono mínimo (50%): $0</small>
                </div>
            </div>

            <!-- ✅ TOTAL EN VIVO -->
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:12px; display:flex; justify-content:space-between; align-items:center;">
                <strong style="color:#166534;">💰 Total del Pedido:</strong>
                <strong id="totalPreview" style="color:#16a34a; font-size:1.1rem;">$0</strong>
            </div>

            <div>
                <label class="form-label">Fecha Comprometida de Entrega *</label>
                <input type="date" name="fecha_entrega" required value="<?= date('Y-m-d', strtotime('+12 days')) ?>" class="form-input">
            </div>

            <div>
                <label class="form-label">Tipo de Entrega *</label>
                <select name="tipo_entrega" id="tipo_entrega" required class="form-select" style="width:100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <option value="Tienda">Retiro en Tienda</option>
                    <option value="Domicilio">Envío a Domicilio</option>
                </select>
            </div>

            <div id="entregaDomicilio" style="display: none; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px; padding: 15px; gap: 12px; margin-bottom: 10px;">
                <div>
                    <label class="form-label">Dirección de Entrega</label>
                    <input type="text" name="direccion_entrega" id="direccion_entrega" class="form-input" placeholder="Ej: Calle 15 # 12-34">
                </div>
                <div>
                    <label class="form-label">Barrio</label>
                    <input type="text" name="barrio_entrega" id="barrio_entrega" class="form-input" placeholder="Ej: El Rosario">
                </div>
                <div>
                    <label class="form-label">Ciudad</label>
                    <input type="text" name="ciudad_entrega" id="ciudad_entrega" value="Sogamoso" class="form-input">
                </div>
                <div>
                    <label class="form-label">Observaciones de Entrega</label>
                    <textarea name="observaciones_entrega" id="observaciones_entrega" rows="2" class="form-textarea" placeholder="Ej: Dejar en la portería si no encuentra el apartamento."></textarea>
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                <button type="submit" class="btn-submit">🚀 Guardar e Imprimir Ticket</button>
                <a href="pedidos_admin.php" class="btn-cancel">Cancelar</a>
                <a href="mis_pedidos.php" class="btn-secondary" style="display: inline-flex; align-items: center;">📦 Ir a Despacho / Entregas</a>
            </div>
        </form>
    </main>
</div>

<div id="modalCliente" class="modal-overlay" style="display: none;">
    <div class="modal-box">
        <h3>👤 Registrar Nuevo Cliente</h3>
        <p style="color: #64748b; font-size: 0.85rem; margin-bottom: 15px;">Agrega los datos del cliente sin perder el progreso del pedido actual.</p>

        <div id="modalError" class="alert-danger" style="display: none; margin-bottom: 12px; padding: 8px; font-size: 0.85rem;"></div>

        <div style="display: flex; flex-direction: column; gap: 12px;">
            <div>
                <label class="form-label">Nombre Completo o Razón Social *</label>
                <input type="text" id="m_nombre" class="form-input" placeholder="Ej: Club Deportivo Real Sogamoso">
            </div>
            <div>
                <label class="form-label">NIT o Cédula *</label>
                <input type="text" id="m_nit" class="form-input" placeholder="Ej: 901234567-1">
            </div>
            <div>
                <label class="form-label">Teléfono de Contacto</label>
                <input type="text" id="m_telefono" class="form-input" placeholder="Ej: 3123456789">
            </div>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end;">
            <button type="button" class="btn-cancel" onclick="cerrarModalCliente()" style="margin: 0;">Cancelar</button>
            <button type="button" class="btn-submit" onclick="guardarClienteAjax()" style="max-width: 150px; margin: 0;">Guardar Cliente</button>
        </div>
    </div>
</div>

<script>
function abrirModalCliente() {
    document.getElementById('modalError').style.display = 'none';
    document.getElementById('modalCliente').style.display = 'flex';
}

function cerrarModalCliente() {
    document.getElementById('modalCliente').style.display = 'none';
    document.getElementById('m_nombre').value = '';
    document.getElementById('m_nit').value = '';
    document.getElementById('m_telefono').value = '';
}

function guardarClienteAjax() {
    const nombre = document.getElementById('m_nombre').value.trim();
    const nit = document.getElementById('m_nit').value.trim();
    const telefono = document.getElementById('m_telefono').value.trim();
    const errorDiv = document.getElementById('modalError');

    if (!nombre || !nit) {
        errorDiv.textContent = "El nombre y el NIT/Cédula son obligatorios.";
        errorDiv.style.display = 'block';
        return;
    }

    const formData = new FormData();
    formData.append('ajax_crear_cliente', '1');
    formData.append('nombre_completo', nombre);
    formData.append('nit_cedula', nit);
    formData.append('telefono', telefono);

    fetch('nuevo_pedido.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const select = document.getElementById('cliente_id');
            const nuevaOpcion = document.createElement('option');
            nuevaOpcion.value = data.id;
            nuevaOpcion.textContent = data.nombre;
            nuevaOpcion.selected = true;
            select.appendChild(nuevaOpcion);
            cerrarModalCliente();
        } else {
            errorDiv.textContent = data.message;
            errorDiv.style.display = 'block';
        }
    })
    .catch(error => {
        errorDiv.textContent = "Error de comunicación con el servidor.";
        errorDiv.style.display = 'block';
    });
}

// ✅ VALIDACIÓN EN VIVO: total, cantidad mínima y abono 50%
(function(){
    const CANT_MIN = <?= $CANTIDAD_MINIMA ?>;
    const select   = document.getElementById('tipo_prenda_id');
    const cantidad = document.getElementById('cantidad');
    const abono    = document.getElementById('abono');
    const totalPreview = document.getElementById('totalPreview');
    const abonoMinInfo = document.getElementById('abonoMinInfo');
    const form = document.getElementById('formConfeccion');
    const tipoEntrega = document.getElementById('tipo_entrega');
    const domiclio = document.getElementById('entregaDomicilio');
    const fmt = n => '$' + n.toLocaleString('co-CO', {maximumFractionDigits: 0});

    function recalcular(){
        const precio = parseFloat(select?.selectedOptions[0]?.dataset.precio || 0);
        const cant   = parseInt(cantidad?.value || 0, 10);
        const total  = precio * cant;
        if (totalPreview) totalPreview.textContent = fmt(total);
        if (abonoMinInfo) abonoMinInfo.textContent = 'Abono mínimo (50%): ' + fmt(total * 0.5);
        const ab = parseFloat(abono?.value || 0);
        if (abono && total > 0 && ab > 0 && ab < total * 0.5) {
            abono.style.borderColor = '#ef4444';
            abono.style.backgroundColor = '#fef2f2';
        } else if (abono) {
            abono.style.borderColor = '#cbd5e1';
            abono.style.backgroundColor = '';
        }
    }
    [select, cantidad, abono].forEach(el => el && el.addEventListener('input', recalcular));
    recalcular();

    if (tipoEntrega && domiclio) {
        tipoEntrega.addEventListener('change', function(){
            domiclio.style.display = (this.value === 'Domicilio') ? 'block' : 'none';
        });
    }

    form && form.addEventListener('submit', function(e){
        const precio = parseFloat(select?.selectedOptions[0]?.dataset.precio || 0);
        const cant   = parseInt(cantidad?.value || 0, 10);
        const total  = precio * cant;
        const ab     = parseFloat(abono?.value || 0);

        if (!select || !select.value) { alert('Seleccione un tipo de prenda del catálogo de confección.'); e.preventDefault(); return; }
        if (cant < CANT_MIN) { alert('La cantidad mínima para confección es ' + CANT_MIN + ' prendas.'); e.preventDefault(); return; }
        if (ab < total * 0.5) { alert('⚠️ El abono debe ser mínimo el 50% del total (' + fmt(total * 0.5) + ').'); e.preventDefault(); return; }
    });
})();
</script>

<style>
.formulario-maestro {
    background: white; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 18px;
}
.form-label { display: block; font-weight: 600; margin-bottom: 6px; color: #334155; font-size: 0.9rem; }
.form-input, .form-select, .form-textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; box-sizing: border-box; }
.form-textarea { resize: vertical; }

.btn-submit { flex: 1; background: #1e293b; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: 600; cursor: pointer; text-align: center; }
.btn-submit:hover { background: #0f172a; }
.btn-secondary { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 600; cursor: pointer; }
.btn-secondary:hover { background: #e2e8f0; }
.btn-cancel { background: #e2e8f0; color: #334155; text-decoration: none; padding: 12px 20px; border-radius: 6px; font-weight: 600; text-align: center; }
.btn-cancel:hover { background: #cbd5e1; }

.alert-danger { padding: 12px; background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; margin-bottom: 20px; border-radius: 4px; font-size: 0.9rem;
font-weight: 500; }

.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); display: flex; align-items: center; justify-content:
    center; z-index: 1000; }
.modal-box { background: white; padding: 25px; border-radius: 12px; max-width: 450px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
.modal-box h3 { margin: 0 0 5px 0; color: #1e293b; font-size: 1.2rem; }
</style>

<?php include(__DIR__ . "/footer.php"); ?>