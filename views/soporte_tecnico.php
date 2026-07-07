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

        <section class="form-container" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; margin-bottom: 18px;">
            <h3 style="margin-top: 0;">Registrar Incidencia</h3>
            <form action="../controllers/soporte_tecnico_controller.php" method="POST" class="form-grid">
                <input type="hidden" name="accion" value="crear">
                <div class="form-field">
                    <label style="display:block; font-size:0.85rem; color:#334155; margin-bottom:4px;">Asunto</label>
                    <input type="text" name="asunto" required maxlength="180" placeholder="Describe la incidencia" style="width:100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                </div>
                <div class="form-field">
                    <label style="display:block; font-size:0.85rem; color:#334155; margin-bottom:4px;">Prioridad</label>
                    <select name="prioridad" required style="width:100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                        <option value="Crítica">Crítica</option>
                        <option value="Alta">Alta</option>
                        <option value="Media" selected>Media</option>
                        <option value="Baja">Baja</option>
                    </select>
                </div>
                <div class="form-field">
                    <label style="display:block; font-size:0.85rem; color:#334155; margin-bottom:4px;">Vendedor</label>
                    <input type="text" name="vendedor" value="<?= htmlspecialchars($usuarioSesion) ?>" required maxlength="120" style="width:100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                </div>
                <div class="form-field">
                    <label style="display:block; font-size:0.85rem; color:#334155; margin-bottom:4px;">Comentario / Solución</label>
                    <input type="text" name="comentario_solucion" maxlength="255" placeholder="Respuesta inicial (opcional)" style="width:100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                </div>
                <div class="form-field form-submit">
                    <button type="submit" class="btn-primary">Crear Ticket</button>
                </div>
            </form>
        </section>

        <div class="badges-container" style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom: 12px;">
            <span class="badge-prioridad critica">🔴 Crítica - Sistema caído o bloqueado</span>
            <span class="badge-prioridad alta">🟠 Alta - Error que impide trabajar</span>
            <span class="badge-prioridad media">🟡 Media - Problema con solución alternativa</span>
            <span class="badge-prioridad baja">🟢 Baja - Sugerencia o consulta menor</span>
        </div>

        <div class="badges-container" style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom: 18px;">
            <span class="badge-estado label">Estados disponibles:</span>
            <span class="badge-estado">Abierto</span>
            <span class="badge-estado">En Proceso</span>
            <span class="badge-estado">Resuelto</span>
            <span class="badge-estado">Cerrado</span>
        </div>

        <div class="table-responsive">
            <table class="tabla-maestra">
                <thead>
                    <tr>
                        <th>ID</th>
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

                        $badge = "<span class='badge-prioridad media'>🟡 Media</span>";
                        if ($prioridad === 'Crítica') {
                            $badge = "<span class='badge-prioridad critica'>🔴 Crítica</span>";
                        } elseif ($prioridad === 'Alta') {
                            $badge = "<span class='badge-prioridad alta'>🟠 Alta</span>";
                        } elseif ($prioridad === 'Baja') {
                            $badge = "<span class='badge-prioridad baja'>🟢 Baja</span>";
                        }

                        $estadoBadge = "<span class='badge-estado-table abierto'>Abierto</span>";
                        if ($estado === 'En Proceso') {
                            $estadoBadge = "<span class='badge-estado-table proceso'>En Proceso</span>";
                        } elseif ($estado === 'Resuelto') {
                            $estadoBadge = "<span class='badge-estado-table resuelto'>Resuelto</span>";
                        } elseif ($estado === 'Cerrado') {
                            $estadoBadge = "<span class='badge-estado-table cerrado'>Cerrado</span>";
                        }
                        ?>
                        <tr>
                            <td><strong>#<?= (int) $t['id_ticket'] ?></strong></td>
                            <td><?= htmlspecialchars($t['asunto']) ?></td>
                            <td>
                                <?= $badge ?>
                                <?php if ($comentario !== ''): ?>
                                    <div class="comentario-ticket"><strong>Comentario:</strong> <?= htmlspecialchars($comentario) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $estadoBadge ?>
                            </td>
                            <td class="fecha-cell"><?= date('d/m/Y H:i', strtotime($t['fecha'])) ?></td>
                            <td><?= htmlspecialchars($t['vendedor']) ?></td>
                            <td>
                                <form action="../controllers/soporte_tecnico_controller.php" method="POST" class="form-acciones">
                                    <input type="hidden" name="accion" value="actualizar">
                                    <input type="hidden" name="id_ticket" value="<?= (int) $t['id_ticket'] ?>">
                                    <select name="estado" class="select-estado">
                                        <?php $estado = $t['estado'] ?? 'Abierto'; ?>
                                        <option value="Abierto" <?= $estado === 'Abierto' ? 'selected' : '' ?>>Abierto</option>
                                        <option value="En Proceso" <?= $estado === 'En Proceso' ? 'selected' : '' ?>>En Proceso</option>
                                        <option value="Resuelto" <?= $estado === 'Resuelto' ? 'selected' : '' ?>>Resuelto</option>
                                        <option value="Cerrado" <?= $estado === 'Cerrado' ? 'selected' : '' ?>>Cerrado</option>
                                    </select>
                                    <input type="text" name="comentario_solucion" maxlength="255" value="<?= htmlspecialchars($comentario) ?>" placeholder="Comentario" class="input-comentario">
                                    <button type="submit" class="btn-actualizar">Actualizar</button>
                                </form>
                                <form action="../controllers/soporte_tecnico_controller.php" method="POST" class="form-resuelto">
                                    <input type="hidden" name="accion" value="actualizar">
                                    <input type="hidden" name="id_ticket" value="<?= (int) $t['id_ticket'] ?>">
                                    <input type="hidden" name="estado" value="Resuelto">
                                    <input type="hidden" name="comentario_solucion" value="<?= htmlspecialchars($comentario) ?>">
                                    <button type="submit" class="btn-resuelto">✓ Marcar Resuelto</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<style>
/* ============================================
   SOPORTE TÉCNICO - ESTILOS RESPONSIVE
   ============================================ */

/* Formulario de registro */
.form-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1.5fr auto;
    gap: 10px;
    align-items: end;
}

.form-field {
    display: flex;
    flex-direction: column;
}

.form-submit {
    display: flex;
    align-items: flex-end;
}

/* Badges de prioridad */
.badge-prioridad {
    display: inline-block;
    border-radius: 999px;
    padding: 6px 10px;
    font-size: 0.82rem;
    font-weight: 700;
    border: 1px solid;
}

.badge-prioridad.critica {
    background: #fee2e2;
    color: #991b1b;
    border-color: #fecaca;
}

.badge-prioridad.alta {
    background: #ffedd5;
    color: #9a3412;
    border-color: #fdba74;
}

.badge-prioridad.media {
    background: #fef9c3;
    color: #854d0e;
    border-color: #fde047;
}

.badge-prioridad.baja {
    background: #dcfce7;
    color: #166534;
    border-color: #86efac;
}

/* Badges de estado */
.badge-estado {
    display: inline-block;
    background: #f8fafc;
    color: #334155;
    border: 1px solid #cbd5e1;
    border-radius: 999px;
    padding: 6px 10px;
    font-size: 0.82rem;
    font-weight: 600;
}

.badge-estado.label {
    background: #e0f2fe;
    color: #075985;
    border-color: #bae6fd;
    font-weight: 700;
}

/* Badges en tabla */
.badge-estado-table {
    display: inline-block;
    border-radius: 999px;
    padding: 5px 9px;
    font-size: 0.78rem;
    font-weight: 700;
    border: 1px solid;
}

.badge-estado-table.abierto {
    background: #e2e8f0;
    color: #334155;
    border-color: #cbd5e1;
}

.badge-estado-table.proceso {
    background: #fef3c7;
    color: #92400e;
    border-color: #fde68a;
}

.badge-estado-table.resuelto {
    background: #dcfce7;
    color: #166534;
    border-color: #86efac;
}

.badge-estado-table.cerrado {
    background: #f1f5f9;
    color: #475569;
    border-color: #cbd5e1;
}

/* Comentario en ticket */
.comentario-ticket {
    margin-top: 6px;
    font-size: 0.78rem;
    color: #334155;
}

/* Tabla responsive */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.tabla-maestra {
    width: 100%;
    min-width: 900px;
    border-collapse: collapse;
}

.tabla-maestra th,
.tabla-maestra td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.tabla-maestra th {
    background: #f8fafc;
    font-weight: 600;
    color: #334155;
    font-size: 0.9rem;
}

.fecha-cell {
    white-space: nowrap;
    font-size: 0.85rem;
}

/* Formularios de acciones */
.form-acciones {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 6px;
    align-items: center;
    margin-bottom: 6px;
}

.select-estado,
.input-comentario {
    padding: 6px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.8rem;
    width: 100%;
    box-sizing: border-box;
}

.btn-actualizar {
    background: #0f766e;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 7px 10px;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: background 0.2s;
}

.btn-actualizar:hover {
    background: #0d5f58;
}

.form-resuelto {
    display: flex;
    gap: 6px;
    align-items: center;
    flex-wrap: wrap;
}

.btn-resuelto {
    background: #16a34a;
    color: #fff;
    border: none;
    border-radius: 999px;
    padding: 6px 10px;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: background 0.2s;
}

.btn-resuelto:hover {
    background: #15803d;
}

.btn-primary {
    background: #1d4ed8;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 10px 14px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: background 0.2s;
}

.btn-primary:hover {
    background: #1e40af;
}

/* ============================================
   RESPONSIVE - TABLET (768px)
   ============================================ */
@media (max-width: 1024px) {
    .form-grid {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    
    .form-submit {
        grid-column: 1 / -1;
    }
    
    .btn-primary {
        width: 100%;
    }
    
    .tabla-maestra {
        min-width: 800px;
    }
}

/* ============================================
   RESPONSIVE - MÓVIL (768px)
   ============================================ */
@media (max-width: 768px) {
    /* Formulario se apila */
    .form-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .form-submit {
        grid-column: 1;
    }
    
    .btn-primary {
        width: 100%;
        padding: 12px;
    }
    
    /* Badges más pequeños */
    .badge-prioridad,
    .badge-estado {
        font-size: 0.75rem;
        padding: 5px 8px;
    }
    
    /* Tabla con scroll horizontal */
    .tabla-maestra {
        min-width: 700px;
        font-size: 0.85rem;
    }
    
    .tabla-maestra th,
    .tabla-maestra td {
        padding: 8px;
    }
    
    /* Formularios de acciones se apilan */
    .form-acciones {
        grid-template-columns: 1fr;
        gap: 8px;
    }
    
    .select-estado,
    .input-comentario {
        width: 100%;
    }
    
    .btn-actualizar {
        width: 100%;
        padding: 8px;
    }
    
    .btn-resuelto {
        width: 100%;
        text-align: center;
        padding: 8px;
    }
    
    /* Ocultar columnas menos importantes en móvil */
    .tabla-maestra th:nth-child(5),
    .tabla-maestra td:nth-child(5) {
        display: none;
    }
}

/* ============================================
   RESPONSIVE - MÓVIL PEQUEÑO (480px)
   ============================================ */
@media (max-width: 480px) {
    .page-header h1 {
        font-size: 1.3rem;
    }
    
    .badge-prioridad,
    .badge-estado {
        font-size: 0.7rem;
        padding: 4px 6px;
    }
    
    .tabla-maestra {
        min-width: 600px;
        font-size: 0.8rem;
    }
    
    /* Ocultar más columnas en pantallas muy pequeñas */
    .tabla-maestra th:nth-child(6),
    .tabla-maestra td:nth-child(6) {
        display: none;
    }
}
</style>

<?php include(__DIR__ . '/footer.php'); ?>