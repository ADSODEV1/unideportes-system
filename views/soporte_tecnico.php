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

include(__DIR__ . '/header.php');
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">
        
        <div class="page-title">
            <h1>Soporte Técnico</h1>
            <p>Gestión de tickets e incidencias del sistema</p>
        </div>

        <?php if ($success !== ''): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Formulario crear ticket -->
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
                </div>
                
                <div class="form-group">
                    <label>Comentario inicial (opcional)</label>
                    <input type="text" name="comentario_solucion" maxlength="255" placeholder="Detalle adicional">
                </div>
                
                <button type="submit" class="btn-primary">Crear Ticket</button>
            </form>
        </div>

        <!-- Lista de tickets -->
        <div class="card">
            <h2 class="card-title">Tickets registrados (<?= count($tickets) ?>)</h2>
            
            <?php if (count($tickets) === 0): ?>
                <p class="empty">No hay tickets registrados</p>
            <?php else: ?>
                <div class="tickets-list">
                    <?php foreach ($tickets as $t): ?>
                        <?php
                        $prioridad = $t['prioridad'] ?? 'Media';
                        $estado = $t['estado'] ?? 'Abierto';
                        $fecha = $t['fecha'] ?? '';
                        $updated_at = $t['updated_at'] ?? null;
                        $comentarios = listarComentariosTicket($conn, (int) $t['id_ticket']);

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
                        <div class="ticket-item">
                            <div class="ticket-header">
                                <div class="ticket-info">
                                    <strong class="ticket-id">#<?= (int) $t['id_ticket'] ?></strong>
                                    <span class="ticket-asunto"><?= htmlspecialchars($t['asunto']) ?></span>
                                    <span class="badge badge-<?= strtolower($prioridad === 'Crítica' ? 'critica' : strtolower($prioridad)) ?>"><?= htmlspecialchars($prioridad) ?></span>
                                    <span class="badge badge-estado-<?= strtolower(str_replace(' ', '', $estado)) ?>"><?= htmlspecialchars($estado) ?></span>
                                </div>
                                <div class="ticket-meta">
                                    <span class="fecha"><?= $fecha ? date('d/m/Y H:i', strtotime($fecha)) : '—' ?></span>
                                    <span class="vendedor">👤 <?= htmlspecialchars($t['vendedor']) ?></span>
                                    <span class="tiempo">⏱️ <?= $tiempoRespuesta ?></span>
                                </div>
                            </div>

                            <!-- Historial de comentarios -->
                            <?php if (count($comentarios) > 0): ?>
                                <div class="comentarios-lista">
                                    <?php foreach ($comentarios as $c): ?>
                                        <div class="comentario-item">
                                            <div class="comentario-header">
                                                <strong><?= htmlspecialchars($c['autor']) ?></strong>
                                                <span class="comentario-fecha"><?= date('d/m/Y H:i', strtotime($c['fecha'])) ?></span>
                                            </div>
                                            <p><?= nl2br(htmlspecialchars($c['mensaje'])) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Formulario de respuesta -->
                            <form action="../controllers/soporte_tecnico_controller.php" method="POST" class="form-respuesta">
                                <input type="hidden" name="accion" value="actualizar">
                                <input type="hidden" name="id_ticket" value="<?= (int) $t['id_ticket'] ?>">
                                
                                <div class="respuesta-row">
                                    <select name="estado" class="select-estado">
                                        <option value="Abierto" <?= $estado === 'Abierto' ? 'selected' : '' ?>>Abierto</option>
                                        <option value="En Proceso" <?= $estado === 'En Proceso' ? 'selected' : '' ?>>En Proceso</option>
                                        <option value="Resuelto" <?= $estado === 'Resuelto' ? 'selected' : '' ?>>Resuelto</option>
                                        <option value="Cerrado" <?= $estado === 'Cerrado' ? 'selected' : '' ?>>Cerrado</option>
                                    </select>
                                    <input type="text" name="nuevo_comentario" placeholder="Escribe una respuesta..." class="input-respuesta">
                                    <button type="submit" class="btn-small">Enviar</button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<style>
.main-content-panel {
    padding: 24px;
    max-width: 1200px;
}

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

.empty {
    text-align: center;
    color: #94a3b8;
    padding: 32px 12px;
    font-style: italic;
}

/* Lista de tickets (formato tarjeta) */
.tickets-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.ticket-item {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px;
    background: #fafbfc;
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 12px;
}

.ticket-info {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.ticket-id {
    color: #2563eb;
    font-size: 0.95rem;
}

.ticket-asunto {
    color: #0f172a;
    font-size: 0.95rem;
}

.ticket-meta {
    display: flex;
    gap: 12px;
    font-size: 0.8rem;
    color: #64748b;
    flex-wrap: wrap;
}

.fecha, .vendedor, .tiempo {
    white-space: nowrap;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-critica { background: #fee2e2; color: #991b1b; }
.badge-alta    { background: #ffedd5; color: #9a3412; }
.badge-media   { background: #fef9c3; color: #854d0e; }
.badge-baja    { background: #dcfce7; color: #166534; }

.badge-estado-abierto   { background: #e2e8f0; color: #475569; }
.badge-estado-enproceso { background: #dbeafe; color: #1e40af; }
.badge-estado-resuelto  { background: #dcfce7; color: #166534; }
.badge-estado-cerrado   { background: #f1f5f9; color: #64748b; }

/* Historial de comentarios */
.comentarios-lista {
    border-left: 3px solid #e2e8f0;
    padding-left: 12px;
    margin: 12px 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.comentario-item {
    background: white;
    padding: 10px 12px;
    border-radius: 6px;
    border: 1px solid #f1f5f9;
}

.comentario-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 4px;
    font-size: 0.8rem;
}

.comentario-header strong {
    color: #2563eb;
}

.comentario-fecha {
    color: #94a3b8;
    font-size: 0.75rem;
}

.comentario-item p {
    margin: 0;
    font-size: 0.88rem;
    color: #334155;
    line-height: 1.5;
}

/* Formulario de respuesta */
.form-respuesta {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #e2e8f0;
}

.respuesta-row {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

.select-estado {
    padding: 7px 10px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.85rem;
    background: white;
}

.input-respuesta {
    flex: 1;
    min-width: 200px;
    padding: 7px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.88rem;
}

.input-respuesta:focus,
.select-estado:focus {
    outline: none;
    border-color: #2563eb;
}

.btn-small {
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 7px 16px;
    font-size: 0.85rem;
    cursor: pointer;
    font-weight: 500;
    white-space: nowrap;
}

.btn-small:hover {
    background: #1d4ed8;
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
    
    .ticket-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .respuesta-row {
        flex-direction: column;
    }
    
    .select-estado,
    .input-respuesta,
    .btn-small {
        width: 100%;
    }
}
</style>

<?php include(__DIR__ . '/footer.php'); ?>