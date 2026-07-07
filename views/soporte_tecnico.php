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
    $success = 'Ticket actualizado correctamente';
} elseif (!empty($_GET['success']) && $_GET['success'] === 'ticket_creado') {
    $success = 'Ticket creado correctamente';
}

$usuarioSesion = $_SESSION['username'] ?? 'Sistema';

include(__DIR__ . '/header.php');
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">
        
        <!-- Encabezado simple -->
        <div class="page-title">
            <h1>Soporte Técnico</h1>
            <p>Gestión de tickets e incidencias del sistema</p>
        </div>

        <!-- Alertas -->
        <?php if ($success !== ''): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Formulario -->
        <div class="card">
            <h2 class="card-title">Crear nuevo ticket</h2>
            <form action="../controllers/soporte_tecnico_controller.php" method="POST" class="form-simple">
                <input type="hidden" name="accion" value="crear">
                
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label>Asunto *</label>
                        <input type="text" name="asunto" required maxlength="180" placeholder="Describe la incidencia">
                    </div>
                    <div class="form-group">
                        <label>Prioridad *</label>
                        <select name="prioridad" required>
                            <option value="Crítica">🔴 Crítica</option>
                            <option value="Alta">🟠 Alta</option>
                            <option value="Media" selected>🟡 Media</option>
                            <option value="Baja">🟢 Baja</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 2;">
                        <label>Comentario</label>
                        <input type="text" name="comentario_solucion" maxlength="255" placeholder="Detalle adicional (opcional)">
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">Crear Ticket</button>
            </form>
        </div>

        <!-- Tabla de tickets -->
        <div class="card">
            <h2 class="card-title">Tickets registrados (<?= count($tickets) ?>)</h2>
            
            <div class="table-responsive">
                <table class="table-simple">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Asunto</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Respuesta</th>
                            <th>Vendedor</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($tickets) === 0): ?>
                            <tr>
                                <td colspan="8" class="empty">No hay tickets registrados</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($tickets as $t): ?>
                            <?php
                            $prioridad = $t['prioridad'] ?? 'Media';
                            $estado = $t['estado'] ?? 'Abierto';
                            $comentario = trim((string) ($t['comentario_solucion'] ?? ''));
                            $fecha = $t['fecha'] ?? '';
                            $updated_at = $t['updated_at'] ?? null;

                            // Tiempo de respuesta
                            $tiempoRespuesta = '—';
                            if ($updated_at && $estado === 'Resuelto' && $fecha) {
                                try {
                                    $f1 = new DateTime($fecha);
                                    $f2 = new DateTime($updated_at);
                                    $diff = $f1->diff($f2);
                                    
                                    if ($diff->days > 0) {
                                        $tiempoRespuesta = $diff->days . ' día(s)';
                                    } elseif ($diff->h > 0) {
                                        $tiempoRespuesta = $diff->h . ' hora(s)';
                                    } else {
                                        $tiempoRespuesta = $diff->i . ' min';
                                    }
                                } catch (Exception $e) {
                                    $tiempoRespuesta = '—';
                                }
                            }
                            ?>
                            <tr>
                                <td><strong>#<?= (int) $t['id_ticket'] ?></strong></td>
                                <td>
                                    <?= htmlspecialchars($t['asunto']) ?>
                                    <?php if ($comentario !== ''): ?>
                                        <div class="comentario"><?= htmlspecialchars($comentario) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-<?= strtolower($prioridad === 'Crítica' ? 'critica' : strtolower($prioridad)) ?>"><?= htmlspecialchars($prioridad) ?></span></td>
                                <td><span class="badge badge-estado-<?= strtolower(str_replace(' ', '', $estado)) ?>"><?= htmlspecialchars($estado) ?></span></td>
                                <td class="fecha"><?= $fecha ? date('d/m/Y H:i', strtotime($fecha)) : '—' ?></td>
                                <td><?= $tiempoRespuesta ?></td>
                                <td><?= htmlspecialchars($t['vendedor']) ?></td>
                                <td>
                                    <form action="../controllers/soporte_tecnico_controller.php" method="POST" class="form-inline">
                                        <input type="hidden" name="accion" value="actualizar">
                                        <input type="hidden" name="id_ticket" value="<?= (int) $t['id_ticket'] ?>">
                                        <select name="estado">
                                            <option value="Abierto" <?= $estado === 'Abierto' ? 'selected' : '' ?>>Abierto</option>
                                            <option value="En Proceso" <?= $estado === 'En Proceso' ? 'selected' : '' ?>>En Proceso</option>
                                            <option value="Resuelto" <?= $estado === 'Resuelto' ? 'selected' : '' ?>>Resuelto</option>
                                            <option value="Cerrado" <?= $estado === 'Cerrado' ? 'selected' : '' ?>>Cerrado</option>
                                        </select>
                                        <button type="submit" class="btn-small">Guardar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<style>
/* ============================================
   SOPORTE TÉCNICO - DISEÑO MINIMALISTA
   ============================================ */

.main-content-panel {
    padding: 24px;
    max-width: 1400px;
}

/* Título */
.page-title {
    margin-bottom: 24px;
}

.page-title h1 {
    margin: 0 0 4px 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #0f172a;
}

.page-title p {
    margin: 0;
    color: #64748b;
    font-size: 0.9rem;
}

/* Alertas */
.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 16px;
    font-size: 0.9rem;
}

.alert-success {
    background: #f0fdf4;
    color: #166534;
    border-left: 3px solid #16a34a;
}

.alert-danger {
    background: #fef2f2;
    color: #991b1b;
    border-left: 3px solid #dc2626;
}

/* Cards */
.card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.card-title {
    margin: 0 0 16px 0;
    font-size: 1.05rem;
    font-weight: 600;
    color: #0f172a;
}

/* Formulario */
.form-simple {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.form-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.form-group {
    flex: 1;
    min-width: 200px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group label {
    font-size: 0.85rem;
    color: #334155;
    font-weight: 500;
}

.form-group input,
.form-group select {
    padding: 8px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.9rem;
    background: white;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #2563eb;
}

.btn-primary {
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 10px 20px;
    font-weight: 500;
    font-size: 0.9rem;
    cursor: pointer;
    align-self: flex-start;
}

.btn-primary:hover {
    background: #1d4ed8;
}

/* Tabla */
.table-responsive {
    overflow-x: auto;
}

.table-simple {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.table-simple th {
    background: #f8fafc;
    padding: 10px 12px;
    text-align: left;
    font-weight: 600;
    color: #475569;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    border-bottom: 1px solid #e2e8f0;
}

.table-simple td {
    padding: 12px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: top;
    color: #334155;
}

.table-simple tbody tr:hover {
    background: #f8fafc;
}

.comentario {
    margin-top: 6px;
    font-size: 0.8rem;
    color: #64748b;
    font-style: italic;
}

.fecha {
    font-size: 0.85rem;
    color: #64748b;
    white-space: nowrap;
}

.empty {
    text-align: center;
    color: #94a3b8;
    padding: 32px 12px !important;
}

/* Badges - SIMPLES Y LIMPIOS */
.badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Prioridades */
.badge-critica { background: #fee2e2; color: #991b1b; }
.badge-alta    { background: #ffedd5; color: #9a3412; }
.badge-media   { background: #fef9c3; color: #854d0e; }
.badge-baja    { background: #dcfce7; color: #166534; }

/* Estados */
.badge-estado-abierto   { background: #e2e8f0; color: #475569; }
.badge-estado-enproceso { background: #dbeafe; color: #1e40af; }
.badge-estado-resuelto  { background: #dcfce7; color: #166534; }
.badge-estado-cerrado   { background: #f1f5f9; color: #64748b; }

/* Formulario inline en tabla */
.form-inline {
    display: flex;
    gap: 6px;
    align-items: center;
    flex-wrap: wrap;
}

.form-inline select {
    padding: 5px 8px;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    font-size: 0.8rem;
    background: white;
}

.btn-small {
    background: #475569;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 5px 10px;
    font-size: 0.8rem;
    cursor: pointer;
    font-weight: 500;
}

.btn-small:hover {
    background: #334155;
}

/* Responsive */
@media (max-width: 768px) {
    .main-content-panel {
        padding: 16px;
    }
    
    .form-row {
        flex-direction: column;
    }
    
    .form-group {
        min-width: 100%;
    }
    
    .btn-primary {
        width: 100%;
    }
}
</style>

<?php include(__DIR__ . '/footer.php'); ?>