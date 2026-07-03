<?php
// views/soporte_tecnico.php

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/SoporteTecnicoModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login(['admin']);
$conn = app();

asegurarTablaSoporteTecnico($conn);
$tickets = listarTicketsSoporte($conn);

$success = '';
$error = trim($_GET['error'] ?? '');
if (!empty($_GET['success']) && $_GET['success'] === 'ticket_actualizado') {
    $success = 'Ticket actualizado y guardado exitosamente';
} elseif (!empty($_GET['success']) && $_GET['success'] === 'ticket_creado') {
    $success = 'Ticket creado exitosamente';
}

$usuarioSesion = $_SESSION['username'] ?? 'Sistema';

include(__DIR__ . '/header.php');
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">
        <div class="page-header" style="margin-bottom: 20px;">
            <h1 style="margin: 0;">Soporte Técnico - Tickets / Incidencias</h1>
            <p style="margin: 6px 0 0 0; color: #64748b;">Gestiona incidencias y seguimiento operativo del sistema.</p>
        </div>

        <?php if ($success !== ''): ?>
            <div class="alert alert-success" style="margin-bottom: 12px;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" style="margin-bottom: 12px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <section style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; margin-bottom: 18px;">
            <h3 style="margin-top: 0;">Registrar Incidencia</h3>
            <form action="../controllers/soporte_tecnico_controller.php" method="POST" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1.5fr auto; gap: 10px; align-items: end;">
                <input type="hidden" name="accion" value="crear">
                <div>
                    <label style="display:block; font-size:0.85rem; color:#334155; margin-bottom:4px;">Asunto</label>
                    <input type="text" name="asunto" required maxlength="180" placeholder="Describe la incidencia" style="width:100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div>
                    <label style="display:block; font-size:0.85rem; color:#334155; margin-bottom:4px;">Prioridad</label>
                    <select name="prioridad" required style="width:100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="Crítica">Crítica</option>
                        <option value="Alta">Alta</option>
                        <option value="Media" selected>Media</option>
                        <option value="Baja">Baja</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:0.85rem; color:#334155; margin-bottom:4px;">Vendedor</label>
                    <input type="text" name="vendedor" value="<?= htmlspecialchars($usuarioSesion) ?>" required maxlength="120" style="width:100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div>
                    <label style="display:block; font-size:0.85rem; color:#334155; margin-bottom:4px;">Comentario / Solución</label>
                    <input type="text" name="comentario_solucion" maxlength="255" placeholder="Respuesta inicial (opcional)" style="width:100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div>
                    <button type="submit" style="background:#1d4ed8; color:#fff; border:none; border-radius:6px; padding:10px 14px; font-weight:700; cursor:pointer;">Crear Ticket</button>
                </div>
            </form>
        </section>

        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom: 12px;">
            <span style="background:#fee2e2; color:#991b1b; border:1px solid #fecaca; border-radius:999px; padding:6px 10px; font-size:0.82rem; font-weight:700;">🔴 Crítica - Sistema caído o bloqueado</span>
            <span style="background:#ffedd5; color:#9a3412; border:1px solid #fdba74; border-radius:999px; padding:6px 10px; font-size:0.82rem; font-weight:700;">🟠 Alta - Error que impide trabajar</span>
            <span style="background:#fef9c3; color:#854d0e; border:1px solid #fde047; border-radius:999px; padding:6px 10px; font-size:0.82rem; font-weight:700;">🟡 Media - Problema con solución alternativa</span>
            <span style="background:#dcfce7; color:#166534; border:1px solid #86efac; border-radius:999px; padding:6px 10px; font-size:0.82rem; font-weight:700;">🟢 Baja - Sugerencia o consulta menor</span>
        </div>

        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom: 18px;">
            <span style="background:#e0f2fe; color:#075985; border:1px solid #bae6fd; border-radius:999px; padding:6px 10px; font-size:0.82rem; font-weight:700;">Estados disponibles:</span>
            <span style="background:#f8fafc; color:#334155; border:1px solid #cbd5e1; border-radius:999px; padding:6px 10px; font-size:0.82rem; font-weight:600;">Abierto</span>
            <span style="background:#f8fafc; color:#334155; border:1px solid #cbd5e1; border-radius:999px; padding:6px 10px; font-size:0.82rem; font-weight:600;">En Proceso</span>
            <span style="background:#f8fafc; color:#334155; border:1px solid #cbd5e1; border-radius:999px; padding:6px 10px; font-size:0.82rem; font-weight:600;">Resuelto</span>
            <span style="background:#f8fafc; color:#334155; border:1px solid #cbd5e1; border-radius:999px; padding:6px 10px; font-size:0.82rem; font-weight:600;">Cerrado</span>
        </div>

        <div class="table-responsive" style="overflow-x:auto;">
            <table class="tabla-maestra" style="min-width: 1100px;">
                <thead>
                    <tr>
                        <th>ID Ticket</th>
                        <th>Asunto</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Vendedor</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($tickets) === 0): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; color:#64748b; padding: 24px;">No hay tickets registrados.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($tickets as $t): ?>
                        <?php
                        $prioridad = $t['prioridad'] ?? 'Media';
                        $estado = $t['estado'] ?? 'Abierto';
                        $comentario = trim((string) ($t['comentario_solucion'] ?? ''));

                        $badge = "<span style='display:inline-block; border-radius:999px; padding:5px 9px; font-size:0.78rem; font-weight:700; background:#fef9c3; color:#854d0e; border:1px solid #fde047;'>🟡 Media</span>";
                        if ($prioridad === 'Crítica') {
                            $badge = "<span style='display:inline-block; border-radius:999px; padding:5px 9px; font-size:0.78rem; font-weight:700; background:#fee2e2; color:#991b1b; border:1px solid #fecaca;'>🔴 Crítica</span>";
                        } elseif ($prioridad === 'Alta') {
                            $badge = "<span style='display:inline-block; border-radius:999px; padding:5px 9px; font-size:0.78rem; font-weight:700; background:#ffedd5; color:#9a3412; border:1px solid #fdba74;'>🟠 Alta</span>";
                        } elseif ($prioridad === 'Baja') {
                            $badge = "<span style='display:inline-block; border-radius:999px; padding:5px 9px; font-size:0.78rem; font-weight:700; background:#dcfce7; color:#166534; border:1px solid #86efac;'>🟢 Baja</span>";
                        }

                        $estadoBadge = "<span style='display:inline-block; border-radius:999px; padding:5px 9px; font-size:0.78rem; font-weight:700; background:#e2e8f0; color:#334155; border:1px solid #cbd5e1;'>Abierto</span>";
                        if ($estado === 'En Proceso') {
                            $estadoBadge = "<span style='display:inline-block; border-radius:999px; padding:5px 9px; font-size:0.78rem; font-weight:700; background:#fef3c7; color:#92400e; border:1px solid #fde68a;'>En Proceso</span>";
                        } elseif ($estado === 'Resuelto') {
                            $estadoBadge = "<span style='display:inline-block; border-radius:999px; padding:5px 9px; font-size:0.78rem; font-weight:700; background:#dcfce7; color:#166534; border:1px solid #86efac;'>Resuelto</span>";
                        } elseif ($estado === 'Cerrado') {
                            $estadoBadge = "<span style='display:inline-block; border-radius:999px; padding:5px 9px; font-size:0.78rem; font-weight:700; background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;'>Cerrado</span>";
                        }
                        ?>
                        <tr>
                            <td><strong>#<?= (int) $t['id_ticket'] ?></strong></td>
                            <td><?= htmlspecialchars($t['asunto']) ?></td>
                            <td>
                                <?= $badge ?>
                                <?php if ($comentario !== ''): ?>
                                    <div style="margin-top:6px; font-size:0.78rem; color:#334155;"><strong>Comentario/Solución:</strong> <?= htmlspecialchars($comentario) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $estadoBadge ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($t['fecha'])) ?></td>
                            <td><?= htmlspecialchars($t['vendedor']) ?></td>
                            <td>
                                <form action="../controllers/soporte_tecnico_controller.php" method="POST" style="display:grid; grid-template-columns: 1fr 1fr auto; gap:6px; align-items:center;">
                                    <input type="hidden" name="accion" value="actualizar">
                                    <input type="hidden" name="id_ticket" value="<?= (int) $t['id_ticket'] ?>">
                                    <select name="estado" style="padding:6px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.8rem;">
                                        <?php $estado = $t['estado'] ?? 'Abierto'; ?>
                                        <option value="Abierto" <?= $estado === 'Abierto' ? 'selected' : '' ?>>Abierto</option>
                                        <option value="En Proceso" <?= $estado === 'En Proceso' ? 'selected' : '' ?>>En Proceso</option>
                                        <option value="Resuelto" <?= $estado === 'Resuelto' ? 'selected' : '' ?>>Resuelto</option>
                                        <option value="Cerrado" <?= $estado === 'Cerrado' ? 'selected' : '' ?>>Cerrado</option>
                                    </select>
                                    <input type="text" name="comentario_solucion" maxlength="255" value="<?= htmlspecialchars($comentario) ?>" placeholder="Comentario / Solución" style="padding:6px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.8rem;">
                                    <button type="submit" style="background:#0f766e; color:#fff; border:none; border-radius:6px; padding:7px 10px; font-size:0.8rem; font-weight:700; cursor:pointer;">Actualizar</button>
                                </form>
                                <form action="../controllers/soporte_tecnico_controller.php" method="POST" style="margin-top:6px; display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                                    <input type="hidden" name="accion" value="actualizar">
                                    <input type="hidden" name="id_ticket" value="<?= (int) $t['id_ticket'] ?>">
                                    <input type="hidden" name="estado" value="Resuelto">
                                    <input type="hidden" name="comentario_solucion" value="<?= htmlspecialchars($comentario) ?>">
                                    <button type="submit" style="background:#16a34a; color:#fff; border:none; border-radius:999px; padding:6px 10px; font-size:0.78rem; font-weight:700; cursor:pointer;">Marcar Resuelto</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php include(__DIR__ . '/footer.php'); ?>
