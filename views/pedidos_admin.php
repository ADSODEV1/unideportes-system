<?php
// views/pedidos_admin.php
require_once __DIR__ . '/../config/bootstrap.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// 🔒 SOLO admin y colaborador pueden gestionar estados atípicos
require_login(['admin', 'colaborador']);

$pdo = app();
$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;
$filtro = $_GET['filtro'] ?? 'activos'; // activos | vencidos | pausados | cancelados

// =========================================================================
// PROCESAR CAMBIO DE ESTADO + NOTAS
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $pedido_id    = intval($_POST['pedido_id']);
    $nuevo_estado = trim($_POST['estado_fabrica'] ?? '');
    $nota         = trim($_POST['nota_admin'] ?? '');
    
    // ✅ Todos los estados válidos (incluyendo los nuevos)
    $estados_validos = [
        'En Corte', 'En Costura', 'Terminado', 'Entregado',
        'Cancelado por Cliente', 'Pausado', 'Vencido'
    ];
    
    // 🔒 VALIDACIÓN DE ROLES: Estados delicados solo para admin
    $estados_delicados = ['Cancelado por Cliente', 'Entregado'];
    if (in_array($nuevo_estado, $estados_delicados) && ($_SESSION['role'] ?? '') !== 'admin') {
        header("Location: pedidos_admin.php?error=solo_admin&t=" . time());
        exit();
    }
    
    if (in_array($nuevo_estado, $estados_validos)) {
        try {
            // Si el estado requiere nota obligatoria, validarla
            $requiere_nota = ['Cancelado por Cliente', 'Pausado', 'Vencido'];
            if (in_array($nuevo_estado, $requiere_nota) && $nota === '') {
                header("Location: pedidos_admin.php?error=nota_requerida&t=" . time());
                exit();
            }
            
            // Construir consulta de actualización
            if ($nota !== '') {
                // Agregar nota con timestamp y usuario
                $usuario = $_SESSION['username'] ?? 'Sistema';
                $fecha_nota = date('d/m/Y H:i');
                $nota_completa = "[$fecha_nota - $usuario] $nota";
                
                // Si ya había notas, agregar al final
                $stmtCheck = $pdo->prepare("SELECT notas_admin FROM pedidos WHERE id = ?");
                $stmtCheck->execute([$pedido_id]);
                $notas_existentes = $stmtCheck->fetchColumn();
                
                if ($notas_existentes) {
                    $nota_final = $notas_existentes . "\n" . $nota_completa;
                } else {
                    $nota_final = $nota_completa;
                }
                
                $stmt = $pdo->prepare("UPDATE pedidos 
                    SET estado = ?, notas_admin = ?, modificado_por = ?, fecha_actualizacion = NOW() 
                    WHERE id = ?");
                $stmt->execute([$nuevo_estado, $nota_final, $_SESSION['user_id'] ?? null, $pedido_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE pedidos 
                    SET estado = ?, modificado_por = ?, fecha_actualizacion = NOW() 
                    WHERE id = ?");
                $stmt->execute([$nuevo_estado, $_SESSION['user_id'] ?? null, $pedido_id]);
            }
            
            if ($stmt->rowCount() > 0) {
                $msg = ($nuevo_estado === 'Terminado') ? 'pedido_listo_pos' : 'estado_actualizado';
                header("Location: pedidos_admin.php?success=" . $msg . "&t=" . time());
                exit();
            } else {
                header("Location: pedidos_admin.php?error=no_cambio&t=" . time());
                exit();
            }
        } catch (Exception $e) {
            error_log("Error al actualizar pedido ID $pedido_id: " . $e->getMessage());
            header("Location: pedidos_admin.php?error=db_error&t=" . time());
            exit();
        }
    } else {
        header("Location: pedidos_admin.php?error=estado_invalido&t=" . time());
        exit();
    }
}

// =========================================================================
// CONSULTA DE ÓRDENES CON FILTROS
// =========================================================================
$where_extra = "";
switch ($filtro) {
    case 'vencidos':
        $where_extra = "AND p.estado NOT IN ('Entregado', 'Cancelado por Cliente') AND p.fecha_entrega < CURDATE()";
        break;
    case 'pausados':
        $where_extra = "AND p.estado = 'Pausado'";
        break;
    case 'cancelados':
        $where_extra = "AND p.estado = 'Cancelado por Cliente'";
        break;
    case 'activos':
    default:
        $where_extra = "AND p.estado NOT IN ('Entregado', 'Cancelado por Cliente')";
        break;
}

$stmt = $pdo->query("SELECT p.*, c.nombre_completo, c.nit_cedula
    FROM pedidos p
    INNER JOIN clientes c ON p.cliente_id = c.id
    WHERE 1=1 $where_extra
    ORDER BY
        CASE p.estado
            WHEN 'Vencido' THEN 0
            WHEN 'En Corte' THEN 1
            WHEN 'En Costura' THEN 2
            WHEN 'Pausado' THEN 3
            WHEN 'Terminado' THEN 4
            ELSE 5
        END ASC,
        p.fecha_entrega ASC");
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =========================================================================
// FUNCIONES AUXILIARES
// =========================================================================
$dias_semana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$hoy_timestamp = strtotime('today');

function calcular_urgencia($fecha_entrega, $hoy_timestamp, $dias_semana, $estado) {
    // Si ya está en estado especial, devolver su badge propio
    if ($estado === 'Vencido') {
        return [
            'badge' => '⚠️ VENCIDO REGISTRADO',
            'badge_class' => 'report-badge report-badge--danger',
            'color' => 'var(--danger)',
            'detalle' => 'Marcado como vencido por el admin',
            'fecha_completa' => date('d-m-Y', strtotime($fecha_entrega))
        ];
    }
    
    $fecha_ts = strtotime($fecha_entrega);
    $diferencia_dias = (int)floor(($fecha_ts - $hoy_timestamp) / 86400);
    $dia_semana = $dias_semana[date('w', $fecha_ts)];
    $fecha_formateada = date('d-m-Y', $fecha_ts);
    
    if ($diferencia_dias < 0) {
        return [
            'badge' => '⚠️ VENCIDO',
            'badge_class' => 'report-badge report-badge--danger',
            'color' => 'var(--danger)',
            'detalle' => 'Hace ' . abs($diferencia_dias) . ' día' . (abs($diferencia_dias) === 1 ? '' : 's'),
            'fecha_completa' => "$dia_semana $fecha_formateada"
        ];
    } elseif ($diferencia_dias === 0) {
        return [
            'badge' => '🔥 HOY',
            'badge_class' => 'report-badge report-badge--danger',
            'color' => 'var(--danger)',
            'detalle' => 'Entrega hoy mismo',
            'fecha_completa' => "$dia_semana $fecha_formateada"
        ];
    } elseif ($diferencia_dias <= 3) {
        return [
            'badge' => '⏰ URGENTE',
            'badge_class' => 'report-badge report-badge--warning',
            'color' => 'var(--warning)',
            'detalle' => "Faltan $diferencia_dias día" . ($diferencia_dias === 1 ? '' : 's'),
            'fecha_completa' => "$dia_semana $fecha_formateada"
        ];
    } elseif ($diferencia_dias <= 7) {
        return [
            'badge' => '📅 Próximo',
            'badge_class' => 'report-badge report-badge--info',
            'color' => 'var(--primary)',
            'detalle' => "Faltan $diferencia_dias días",
            'fecha_completa' => "$dia_semana $fecha_formateada"
        ];
    } else {
        return [
            'badge' => '✅ En tiempo',
            'badge_class' => 'report-badge report-badge--success',
            'color' => 'var(--success)',
            'detalle' => "Faltan $diferencia_dias días",
            'fecha_completa' => "$dia_semana $fecha_formateada"
        ];
    }
}

include(__DIR__ . "/header.php");
?>
<div class="container admin-layout">
<?php include(__DIR__ . "/sidebar_control.php"); ?>
<main class="main-content-panel">

<div class="page-header">
    <div>
        <h1 class="page-header__title">🏭 Control de Producción Taller</h1>
        <p class="page-header__subtitle">Gestión de órdenes de confección. Documenta cancelaciones, pausas y vencimientos.</p>
    </div>
</div>

<!-- 🔘 FILTROS RÁPIDOS -->
<div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
    <a href="?filtro=activos" class="btn-filtro <?= $filtro === 'activos' ? 'activo' : '' ?>">
        🏭 En Producción
    </a>
    <a href="?filtro=vencidos" class="btn-filtro <?= $filtro === 'vencidos' ? 'activo' : '' ?>">
        ⚠️ Vencidos
    </a>
    <a href="?filtro=pausados" class="btn-filtro <?= $filtro === 'pausados' ? 'activo' : '' ?>">
        ⏸️ Pausados
    </a>
    <a href="?filtro=cancelados" class="btn-filtro <?= $filtro === 'cancelados' ? 'activo' : '' ?>">
        ❌ Cancelados
    </a>
</div>

<!-- MENSAJES -->
<?php if ($success === 'estado_actualizado'): ?>
    <div class="alert alert-success">🔄 Estado del pedido actualizado correctamente.</div>
<?php elseif ($success === 'pedido_listo_pos'): ?>
    <div class="alert alert-success">✅ Pedido marcado como <strong>Terminado</strong>.</div>
<?php elseif ($error === 'no_cambio'): ?>
    <div class="alert alert-warning">ℹ️ El estado ya estaba seleccionado.</div>
<?php elseif ($error === 'nota_requerida'): ?>
    <div class="alert alert-error">⚠️ Debes escribir una nota explicando el motivo del cambio.</div>
<?php elseif ($error === 'solo_admin'): ?>
    <div class="alert alert-error">🔒 Solo el administrador puede cancelar o marcar como entregado.</div>
<?php elseif ($error): ?>
    <div class="alert alert-error">❌ Ocurrió un error. Inténtalo de nuevo.</div>
<?php endif; ?>

<!-- TABLA DE PEDIDOS -->
<div class="table-responsive">
<table class="tabla-maestra">
    <thead>
        <tr>
            <th>Cliente</th>
            <th>Detalles</th>
            <th style="text-align: center;">Cant.</th>
            <th>Fecha Límite</th>
            <th style="text-align: center;">Estado</th>
            <th style="text-align: center;">📝 Notas</th>
        </tr>
    </thead>
    <tbody>
    <?php if (count($pedidos) > 0): ?>
        <?php foreach ($pedidos as $row):
            $urgencia = calcular_urgencia($row['fecha_entrega'], $hoy_timestamp, $dias_semana, $row['estado']);
        ?>
        <tr>
            <td>
                <strong style="display: block;"><?= htmlspecialchars($row['nombre_completo']) ?></strong>
                <span style="font-size: 0.82rem; color: var(--text-light);">NIT: <?= htmlspecialchars($row['nit_cedula']) ?></span>
            </td>
            <td>
                <span style="font-weight: 600;"><?= htmlspecialchars($row['detalle']) ?></span>
                <?php if (!empty($row['descripcion'])): ?>
                <div style="margin-top: 8px; padding: 8px; background: var(--input-bg); border-left: 3px solid var(--warning); border-radius: var(--radius-sm); font-size: 0.85rem;">
                    <strong>📝 Obs:</strong> <?= htmlspecialchars($row['descripcion']) ?>
                </div>
                <?php endif; ?>
            </td>
            <td style="text-align: center; font-weight: 700; font-size: 1.1rem;">
                <?= (int)$row['cantidad'] ?>
            </td>
            <td>
                <div style="margin-bottom: 6px;">
                    <span class="<?= $urgencia['badge_class'] ?>" style="font-size: 0.78rem;">
                        <?= $urgencia['badge'] ?>
                    </span>
                </div>
                <div style="font-weight: 600; color: <?= $urgencia['color'] ?>; font-size: 0.92rem;">
                    📅 <?= $urgencia['fecha_completa'] ?>
                </div>
                <div style="font-size: 0.78rem; color: var(--text-light); margin-top: 2px;">
                    <?= $urgencia['detalle'] ?>
                </div>
            </td>
            <td style="text-align: center;">
                <form method="POST" action="" class="form-estado" onsubmit="return manejarEnvio(this)">
                    <input type="hidden" name="pedido_id" value="<?= $row['id'] ?>">
                    <input type="hidden" name="actualizar_estado" value="1">
                    <select name="estado_fabrica" class="form-control" style="max-width: 220px; margin: 0 auto; cursor: pointer;" onchange="pedirNota(this, '<?= $row['id'] ?>')">
                        <option value="En Corte" <?= $row['estado'] === 'En Corte' ? 'selected' : '' ?>>✂️ En Corte</option>
                        <option value="En Costura" <?= $row['estado'] === 'En Costura' ? 'selected' : '' ?>>🪡 En Costura</option>
                        <option value="Terminado" <?= $row['estado'] === 'Terminado' ? 'selected' : '' ?>>✅ Terminado</option>
                        <option value="Entregado" <?= $row['estado'] === 'Entregado' ? 'selected' : '' ?>>📦 Entregado (Solo Admin)</option>
                        <option value="Pausado" <?= $row['estado'] === 'Pausado' ? 'selected' : '' ?>>⏸️ Pausado</option>
                        <option value="Vencido" <?= $row['estado'] === 'Vencido' ? 'selected' : '' ?>>⚠️ Vencido</option>
                        <option value="Cancelado por Cliente" <?= $row['estado'] === 'Cancelado por Cliente' ? 'selected' : '' ?>>❌ Cancelado (Solo Admin)</option>
                    </select>
                </form>
            </td>
            <td>
                <?php if (!empty($row['notas_admin'])): ?>
                    <button onclick="verNotas(<?= $row['id'] ?>)" class="btn-notas" title="Ver notas">
                        📝 <?= substr_count($row['notas_admin'], '[') ?> nota(s)
                    </button>
                    <div id="notas-<?= $row['id'] ?>" style="display: none; margin-top: 8px; padding: 8px; background: #fef3c7; border-left: 3px solid #f59e0b; border-radius: 4px; font-size: 0.8rem; white-space: pre-line; max-height: 150px; overflow-y: auto;">
                        <?= htmlspecialchars($row['notas_admin']) ?>
                    </div>
                <?php else: ?>
                    <span style="color: var(--text-light); font-size: 0.85rem;">Sin notas</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="6" class="empty-state">
                🎉 No hay pedidos en esta categoría.
            </td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

</main>
</div>

<!-- MODAL PARA AGREGAR NOTA -->
<div id="modalNota" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
    <div style="background: white; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
        <h3 style="margin-top: 0;">📝 Agregar nota administrativa</h3>
        <p style="color: var(--text-light); font-size: 0.9rem;">Explica el motivo del cambio de estado. Esta nota quedará registrada con tu usuario y fecha.</p>
        <form id="formNota" method="POST" action="">
            <input type="hidden" name="pedido_id" id="nota_pedido_id">
            <input type="hidden" name="estado_fabrica" id="nota_estado">
            <input type="hidden" name="actualizar_estado" value="1">
            <textarea name="nota_admin" id="nota_texto" rows="4" required placeholder="Ej: Cliente no pagó el saldo pendiente después de 3 intentos. Se cancela el pedido." style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px; font-family: inherit; margin-bottom: 15px;"></textarea>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="cerrarModalNota()" style="padding: 10px 20px; border: 1px solid var(--border); background: white; border-radius: 6px; cursor: pointer;">Cancelar</button>
                <button type="submit" style="padding: 10px 20px; background: var(--primary); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Guardar y continuar</button>
            </div>
        </form>
    </div>
</div>

<style>
.btn-filtro {
    padding: 8px 16px;
    border: 1px solid var(--border);
    background: white;
    border-radius: 6px;
    text-decoration: none;
    color: var(--text);
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s;
}
.btn-filtro:hover {
    background: var(--input-bg);
}
.btn-filtro.activo {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}
.btn-notas {
    padding: 4px 10px;
    background: #fef3c7;
    border: 1px solid #fde68a;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.85rem;
    color: #92400e;
}
.btn-notas:hover {
    background: #fde68a;
}
</style>

<script>
// Estados que requieren nota obligatoria
const estadosConNota = ['Pausado', 'Vencido', 'Cancelado por Cliente'];

function pedirNota(select, pedidoId) {
    const estadoSeleccionado = select.value;
    
    if (estadosConNota.includes(estadoSeleccionado)) {
        // Abrir modal para pedir nota
        document.getElementById('nota_pedido_id').value = pedidoId;
        document.getElementById('nota_estado').value = estadoSeleccionado;
        document.getElementById('nota_texto').value = '';
        document.getElementById('modalNota').style.display = 'flex';
        
        // Revertir el select temporalmente hasta que confirme
        setTimeout(() => {
            // Volver al estado anterior
            const estadoOriginal = '<?= $row['estado'] ?? 'En Corte' ?>';
        }, 100);
    } else {
        // Enviar directamente sin nota
        select.closest('form').submit();
    }
    return false;
}

function cerrarModalNota() {
    document.getElementById('modalNota').style.display = 'none';
}

function verNotas(id) {
    const div = document.getElementById('notas-' + id);
    div.style.display = div.style.display === 'none' ? 'block' : 'none';
}

function manejarEnvio(form) {
    const select = form.querySelector('select[name="estado_fabrica"]');
    select.disabled = true;
    const opcionActual = select.options[select.selectedIndex];
    opcionActual.text = "⏳ Guardando...";
    return true;
}
</script>

<?php include(__DIR__ . "/footer.php"); ?>