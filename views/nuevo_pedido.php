<?php
// views/nuevo_pedido.php
require_once __DIR__ . '/../config/bootstrap.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_login(['admin', 'colaborador', 'vendedor']);
$pdo = app();
$error = null;
$vendedor_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 1;

// 1. PROCESAR CREACIÓN RÁPIDA DE CLIENTE VÍA AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_crear_cliente'])) {
    header('Content-Type: application/json');
    $nombre = trim($_POST['nombre_completo'] ?? '');
    $nit = trim($_POST['nit_cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    
    if (empty($nombre) || empty($nit)) {
        echo json_encode(['status' => 'error', 'message' => 'Nombre y NIT/Cédula son obligatorios.']);
        exit;
    }
    
    try {
        $check = $pdo->prepare("SELECT id FROM clientes WHERE nit_cedula = ?");
        $check->execute([$nit]);
        if ($check->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El NIT o Cédula ya está registrado.']);
            exit;
        }

        $stmtMax = $pdo->query("SELECT MAX(id) FROM clientes");
        $nextId = (int)$stmtMax->fetchColumn() + 1;
        $codigo = 'CLI-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("INSERT INTO clientes (codigo_descriptivo, nombre_completo, nit_cedula, telefono, estado) VALUES (?, ?, ?, ?, 'activo')");
        $stmt->execute([$codigo, $nombre, $nit, $telefono]);
        
        echo json_encode([
            'status' => 'success', 
            'id' => $pdo->lastInsertId(), 
            'nombre' => $nombre . ' (' . $nit . ')'
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar: ' . $e->getMessage()]);
        exit;
    }
}

// 2. PROCESAR CREACIÓN DE LA ORDEN DE FABRICACIÓN CON CARRITO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_pedido'])) {
    $cliente_id = intval($_POST['cliente_id'] ?? 0);
    $detalle = trim($_POST['detalle'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? ''); // ✅ AHORA SÍ SE USA
    $ajuste_precio = floatval($_POST['ajuste_precio'] ?? 0);
    $abono = floatval($_POST['abono'] ?? 0);
    $fecha_entrega = $_POST['fecha_entrega'] ?? date('Y-m-d', strtotime('+15 days'));
    
    $metodo_pago_abono = $_POST['metodo_pago_abono'] ?? 'Efectivo';
    $plataforma_pago = !empty($_POST['plataforma_pago']) ? trim($_POST['plataforma_pago']) : null;
    $referencia_pago = !empty($_POST['referencia_pago']) ? trim($_POST['referencia_pago']) : null;
    
    // Decodificar el carrito JSON
    $carrito_json = $_POST['carrito_json'] ?? '[]';
    $carrito = json_decode($carrito_json, true);
    
    // ✅ VALIDACIONES MEJORADAS
    if (!$carrito || count($carrito) === 0) {
        $error = "Debes agregar al menos un producto al carrito.";
    } elseif ($cliente_id <= 0) {
        $error = "Debes seleccionar un cliente válido.";
    } elseif (empty($detalle)) {
        $error = "El título del pedido es obligatorio.";
    } else {
        // ✅ VALIDAR QUE EL CLIENTE EXISTA
        $stmtCliente = $pdo->prepare("SELECT id FROM clientes WHERE id = ? AND estado = 'activo'");
        $stmtCliente->execute([$cliente_id]);
        if (!$stmtCliente->fetch()) {
            $error = "El cliente seleccionado no existe o está inactivo.";
        }
    }
    
    if (!$error) {
        try {
            $pdo->beginTransaction();

            // Calcular total del pedido sumando todos los items del carrito
            $total_pedido = 0;
            $cantidad_total = 0;
            
            foreach ($carrito as $item) {
                $total_pedido += ($item['precio'] * $item['cantidad']);
                $cantidad_total += $item['cantidad'];
            }
            
            $total_pedido += $ajuste_precio;
            
            // Validar abono mínimo si el pedido es mayor a $500,000
            if ($total_pedido > 500000 && $abono < ($total_pedido * 0.30)) {
                throw new Exception("Para pedidos mayores a $500,000, se requiere un abono mínimo del 30% ($" . number_format($total_pedido * 0.30, 0, ',', '.') . ")");
            }

            // Guardar la orden principal en pedidos
            $stmtPed = $pdo->prepare("
                INSERT INTO pedidos 
                (cliente_id, vendedor_id, detalle, descripcion, cantidad, total_pedido, abono, saldo_pendiente, estado, fecha_entrega) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'En Corte', ?)
            ");
            $stmtPed->execute([
                $cliente_id, 
                $vendedor_id,
                $detalle, 
                $descripcion, // ✅ AHORA GUARDA LA DESCRIPCIÓN REAL
                $cantidad_total, 
                $total_pedido,
                $abono,
                $total_pedido - $abono,
                $fecha_entrega
            ]);
            $pedido_id = $pdo->lastInsertId();

            // Guardar cada item del carrito usando 'tipo_prenda_id'
            foreach ($carrito as $item) {
                $stmtDet = $pdo->prepare("
                    INSERT INTO detalle_pedido 
                    (pedido_id, tipo_prenda_id, cantidad, precio_unitario, comentario_vendedor) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtDet->execute([
                    $pedido_id,
                    $item['tipo_prenda_id'], 
                    $item['cantidad'],
                    $item['precio'],
                    $item['comentario'] ?? ''
                ]);
            }

            // Registrar el abono inicial en pagos
            if ($abono > 0) {
                $stmtPag = $pdo->prepare("
                    INSERT INTO pagos (id_pg_pedido, monto, metodo_pago, plataforma, referencia, fecha) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmtPag->execute([
                    $pedido_id, 
                    $abono,
                    $metodo_pago_abono,
                    $plataforma_pago,
                    $referencia_pago
                ]);
            }

            $pdo->commit();
            header("Location: ver_ticket_pedido.php?id=" . $pedido_id);
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error en el sistema al registrar la orden: " . $e->getMessage();
        }
    }
}

// Cargar clientes y precios base
$clientes = $pdo->query("SELECT id, nombre_completo, nit_cedula FROM clientes WHERE estado = 'activo' ORDER BY nombre_completo ASC")->fetchAll(PDO::FETCH_ASSOC);
$precios_base = $pdo->query("SELECT id, tipo_prenda, precio_base, descripcion FROM precios_base_confeccion WHERE activo = 1 ORDER BY tipo_prenda ASC")->fetchAll(PDO::FETCH_ASSOC);

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . "/sidebar_control.php"); ?>

    <main class="main-content-panel">
        <div class="page-header">
            <div>
                <h1>📝 Nueva Orden de Fabricación Mayorista</h1>
                <p>Genera la orden de taller y emite el ticket físico de abono para el cliente.</p>
            </div>
            <a href="pedidos_admin.php" class="btn-secondary">← Volver</a>
        </div>

        <?php if ($error): ?>
            <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="formPedido">
            <input type="hidden" name="crear_pedido" value="1">
            
            <!-- SECCIÓN 1: CLIENTE Y TÍTULO -->
            <div class="pedido-card">
                <h2 class="section-subtitle">👤 Datos del Cliente y Pedido</h2>
                <div class="form-grid-2">
                    <div>
                        <label class="form-label">Seleccionar Cliente *</label>
                        <div style="display: flex; gap: 10px;">
                            <select name="cliente_id" id="cliente_id" required class="form-select" style="flex: 1;">
                                <option value="">-- Seleccione un cliente --</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre_completo']) ?> (<?= htmlspecialchars($c['nit_cedula']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn-secondary" onclick="abrirModalCliente()" style="white-space: nowrap; padding: 0 15px;">➕ Nuevo</button>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Título del Pedido / Referencia *</label>
                        <input type="text" name="detalle" placeholder="Ej: Uniformes Combo Inter - Microfútbol" required class="form-input">
                    </div>
                </div>
                
                <!-- ✅ NUEVO: ESPECIFICACIONES GENERALES DEL PEDIDO -->
                <div style="margin-top: 15px;">
                    <label class="form-label">📋 Especificaciones Generales del Pedido</label>
                    <textarea name="descripcion" rows="3" class="form-input" placeholder="Ej: Pedido para el torneo intercolegiados. Todos los uniformes deben llevar escudo bordado en el pecho izquierdo. Entregar empacados individualmente..."></textarea>
                    <small style="color: #64748b; font-size: 0.8rem;">Observaciones generales que aplican a todo el pedido</small>
                </div>
            </div>

            <!-- SECCIÓN 2: AGREGAR PRODUCTOS AL CARRITO -->
            <div class="pedido-card">
                <h2 class="section-subtitle">🛒 Agregar Prendas al Pedido</h2>
                <div class="form-grid-3">
                    <div>
                        <label class="form-label">Tipo de Prenda *</label>
                        <select id="selectTipoPrenda" class="form-select">
                            <option value="">-- Seleccione --</option>
                            <?php foreach ($precios_base as $p): ?>
                                <option value="<?= $p['id'] ?>" data-precio="<?= $p['precio_base'] ?>" data-nombre="<?= htmlspecialchars($p['tipo_prenda']) ?>">
                                    <?= htmlspecialchars($p['tipo_prenda']) ?> - $<?= number_format($p['precio_base'], 0, ',', '.') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Cantidad *</label>
                        <input type="number" id="inputCantidad" min="1" value="1" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Comentario (Opcional)</label>
                        <input type="text" id="inputComentario" placeholder="Ej: Tallas M y L, color azul" class="form-input">
                    </div>
                </div>
                <button type="button" id="btnAgregarCarrito" class="btn-success" style="margin-top: 15px; width: 100%;">➕ Agregar al Carrito</button>
            </div>

            <!-- SECCIÓN 3: CARRITO DE PRODUCTOS -->
            <div class="pedido-card">
                <h2 class="section-subtitle">📦 Prendas del Pedido</h2>
                <div class="tabla-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tipo de Prenda</th>
                                <th>Cantidad</th>
                                <th>Precio Unit.</th>
                                <th>Subtotal</th>
                                <th>Comentario</th>
                                <th style="width: 70px;">Quitar</th>
                            </tr>
                        </thead>
                        <tbody id="carritoBody">
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: #94a3b8;">
                                    🛒 El carrito está vacío. Agrega prendas arriba.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECCIÓN 4: CÁLCULO AUTOMÁTICO -->
            <div class="pedido-card" style="background: #f0fdf4; border-color: #10b981;">
                <h2 class="section-subtitle">💰 Cálculo del Total</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <strong>Subtotal:</strong>
                        <span id="display_subtotal" style="font-size: 1.1rem; color: #059669;">$0</span>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.85rem;">Ajuste Manual ($)</label>
                        <input type="number" name="ajuste_precio" id="ajuste_precio" value="0" step="1000" class="form-input" oninput="actualizarTotal()" placeholder="0">
                    </div>
                    <div style="text-align: right;">
                        <strong style="font-size: 1.3rem; color: #065f46;">TOTAL:</strong>
                        <span id="display_total" style="font-size: 1.3rem; color: #065f46;">$0</span>
                    </div>
                </div>
                <small style="color: #64748b;">💡 Usa el ajuste manual para incrementos por tela especial, bordados extras, etc.</small>
            </div>

            <!-- SECCIÓN 5: MÉTODO DE PAGO Y ABONO -->
            <div class="pedido-card" style="background: #eff6ff; border-color: #3b82f6;">
                <h2 class="section-subtitle">💳 Método de Pago del Abono</h2>
                <div class="form-grid-2">
                    <div>
                        <label class="form-label">¿Cómo paga el cliente? *</label>
                        <select name="metodo_pago_abono" id="metodo_pago_abono" required class="form-select" onchange="toggleCamposPago()">
                            <option value="Efectivo">💵 Efectivo</option>
                            <option value="Transferencia">📱 Transferencia</option>
                            <option value="Tarjeta">💳 Tarjeta</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Monto de Abono Inicial ($) *</label>
                        <input type="number" name="abono" id="abono" min="0" value="0" step="1000" required class="form-input">
                        <div id="sugerenciaAbono" style="margin-top: 6px; font-size: 0.85rem; color: #059669; display: none;"></div>
                    </div>
                </div>

                <div id="seccionPlataforma" style="display: none; margin-top: 15px;">
                    <label class="form-label">Plataforma de Transferencia *</label>
                    <select name="plataforma_pago" id="plataforma_pago" class="form-select">
                        <option value="Nequi">Nequi</option>
                        <option value="Daviplata">Daviplata</option>
                        <option value="Bancolombia">Bancolombia</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>

                <div id="seccionReferencia" style="display: none; margin-top: 15px;">
                    <label class="form-label">Número de Comprobante / Referencia *</label>
                    <input type="text" name="referencia_pago" id="referencia_pago" class="form-input" placeholder="Ej: ID de transacción o últimos 4 dígitos">
                </div>
            </div>

            <!-- SECCIÓN 6: FECHA DE ENTREGA -->
            <div class="pedido-card">
                <h2 class="section-subtitle">📅 Fecha de Entrega</h2>
                <label class="form-label">Fecha Comprometida de Entrega *</label>
                <input type="date" name="fecha_entrega" required value="<?= date('Y-m-d', strtotime('+15 days')) ?>" class="form-input">
                <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display: block;">⏰ Tiempo estándar de confección: 12-15 días hábiles</small>
            </div>

            <!-- BOTONES FINALES -->
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <input type="hidden" id="carritoJSON" name="carrito_json">
                <button type="submit" class="btn-submit">🚀 Guardar e Imprimir Ticket</button>
                <a href="pedidos_admin.php" class="btn-cancel">Cancelar</a>
            </div>
        </form>
    </main>
</div>

<!-- MODAL NUEVO CLIENTE -->
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
                <input type="text" id="m_nit" class="form-input" placeholder="Ej: 900123456-7">
            </div>
            <div>
                <label class="form-label">Teléfono de Contacto</label>
                <input type="text" id="m_telefono" class="form-input" placeholder="Ej: 3123456789">
            </div>
        </div>
        <div style="display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end;">
            <button type="button" class="btn-cancel" onclick="cerrarModalCliente()" style="margin: 0;">Cancelar</button>
            <button type="button" class="btn-submit" onclick="guardarClienteAjax()" style="max-width: 150px; margin: 0;">Guardar</button>
        </div>
    </div>
</div>

<style>
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
.section-subtitle {
    color: #475569;
    font-size: 1.05rem;
    font-weight: 600;
    margin: 0 0 15px 0;
    padding-bottom: 8px;
    border-bottom: 2px solid #e2e8f0;
}
.pedido-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}
.form-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}
.form-grid-3 {
    display: grid;
    grid-template-columns: 2fr 1fr 2fr;
    gap: 15px;
}
.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    color: #334155;
    font-size: 0.9rem;
}
.form-input, .form-select {
    width: 100%;
    padding: 10px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.9rem;
    box-sizing: border-box;
}
.form-input:focus, .form-select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
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
}
.data-table th {
    padding: 12px;
    text-align: left;
    font-weight: 600;
    font-size: 0.9rem;
}
.data-table td {
    padding: 12px;
    border-bottom: 1px solid #e2e8f0;
}
.btn-submit {
    flex: 1;
    background: #1e293b;
    color: white;
    border: none;
    padding: 12px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    text-align: center;
    font-size: 1rem;
}
.btn-submit:hover {
    background: #0f172a;
}
.btn-secondary {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    padding: 10px 15px;
}
.btn-success {
    background: #10b981;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
}
.btn-cancel {
    background: #e2e8f0;
    color: #334155;
    text-decoration: none;
    padding: 12px 20px;
    border-radius: 6px;
    font-weight: 600;
    text-align: center;
}
.btn-quitar {
    background: #ef4444;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
}
.alert-danger {
    padding: 12px;
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
    margin-bottom: 20px;
    border-radius: 4px;
    font-size: 0.9rem;
    font-weight: 500;
}
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
.modal-box {
    background: white;
    padding: 25px;
    border-radius: 12px;
    max-width: 450px;
    width: 90%;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
}
.modal-box h3 {
    margin: 0 0 5px 0;
    color: #1e293b;
    font-size: 1.2rem;
}
@media (max-width: 768px) {
    .form-grid-2, .form-grid-3 {
        grid-template-columns: 1fr;
    }
    .page-header {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script src="../public/js/pedidos.js"></script>
<?php include(__DIR__ . "/footer.php"); ?>