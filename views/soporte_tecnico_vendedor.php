<?php
// views/soporte_tecnico_vendedor.php

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/SoporteTecnicoModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login(['vendedor', 'colaborador', 'admin']);
$conn = app();
asegurarTablaSoporteTecnico($conn);

$rol_usuario = $_SESSION['role'] ?? '';
if ($rol_usuario === 'admin') {
    header('Location: soporte_tecnico.php');
    exit();
}

$success = trim($_GET['success'] ?? '');
$error = trim($_GET['error'] ?? '');
$usuarioSesion = $_SESSION['username'] ?? 'Sistema';

include(__DIR__ . '/header.php');
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">
        <div class="page-header" style="margin-bottom: 20px;">
            <h1 style="margin: 0;">Soporte Técnico</h1>
            <p style="margin: 6px 0 0 0; color: #64748b;">Envía un ticket de incidencia desde tu panel.</p>
        </div>

        <?php if ($success === 'ticket_creado'): ?>
            <div class="alert-success" style="margin-bottom: 14px;">Registro de ticket exitoso</div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert-danger" style="margin-bottom: 14px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <section class="form-container">
            <form action="../controllers/soporte_tecnico_controller.php" method="POST" class="form-grid-vendedor">
                <input type="hidden" name="accion" value="crear">
                <div class="form-field">
                    <label class="form-label">Asunto</label>
                    <input type="text" name="asunto" required maxlength="180" placeholder="Describe el problema" class="form-input">
                </div>
                <div class="form-field">
                    <label class="form-label">Prioridad</label>
                    <select name="prioridad" required class="form-input">
                        <option value="Crítica">Crítica</option>
                        <option value="Alta">Alta</option>
                        <option value="Media" selected>Media</option>
                        <option value="Baja">Baja</option>
                    </select>
                </div>
                <div class="form-field">
                    <label class="form-label">Comentario / Solución</label>
                    <input type="text" name="comentario_solucion" maxlength="255" placeholder="Agrega detalle adicional" class="form-input">
                </div>
                <div class="form-field form-submit">
                    <button type="submit" class="btn-primary">Enviar Ticket</button>
                </div>
            </form>

            <div class="badges-container">
                <span class="badge-prioridad critica">🔴 Crítica</span>
                <span class="badge-prioridad alta">🟠 Alta</span>
                <span class="badge-prioridad media">🟡 Media</span>
                <span class="badge-prioridad baja">🟢 Baja</span>
            </div>
        </section>
    </main>
</div>

<style>
/* ============================================
   SOPORTE TÉCNICO VENDEDOR - ESTILOS RESPONSIVE
   ============================================ */

/* Contenedor del formulario */
.form-container {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 20px;
}

/* Formulario grid */
.form-grid-vendedor {
    display: grid;
    grid-template-columns: 2fr 1fr 2fr auto;
    gap: 12px;
    align-items: end;
}

.form-field {
    display: flex;
    flex-direction: column;
}

.form-label {
    display: block;
    font-size: 0.85rem;
    color: #334155;
    margin-bottom: 6px;
    font-weight: 500;
}

.form-input {
    width: 100%;
    padding: 9px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.9rem;
    box-sizing: border-box;
    transition: border-color 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-submit {
    display: flex;
    align-items: flex-end;
}

/* Badges de prioridad */
.badges-container {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 16px;
}

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

/* Botón principal */
.btn-primary {
    background: #1d4ed8;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 10px 18px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: background 0.2s;
    font-size: 0.9rem;
}

.btn-primary:hover {
    background: #1e40af;
}

/* Alertas */
.alert-success {
    padding: 12px 16px;
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
    border-radius: 6px;
    font-weight: 600;
}

.alert-danger {
    padding: 12px 16px;
    background: #fef2f2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
    border-radius: 6px;
    font-weight: 600;
}

/* ============================================
   RESPONSIVE - TABLET (1024px)
   ============================================ */
@media (max-width: 1024px) {
    .form-grid-vendedor {
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }
    
    .form-submit {
        grid-column: 1 / -1;
    }
    
    .btn-primary {
        width: 100%;
        padding: 12px;
    }
}

/* ============================================
   RESPONSIVE - MÓVIL (768px)
   ============================================ */
@media (max-width: 768px) {
    .form-container {
        padding: 16px;
    }
    
    /* Formulario se apila */
    .form-grid-vendedor {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .form-submit {
        grid-column: 1;
    }
    
    .btn-primary {
        width: 100%;
        padding: 12px;
        font-size: 1rem;
    }
    
    /* Badges más pequeños */
    .badge-prioridad {
        font-size: 0.75rem;
        padding: 5px 8px;
    }
    
    .badges-container {
        gap: 6px;
    }
}

/* ============================================
   RESPONSIVE - MÓVIL PEQUEÑO (480px)
   ============================================ */
@media (max-width: 480px) {
    .page-header h1 {
        font-size: 1.3rem;
    }
    
    .badge-prioridad {
        font-size: 0.7rem;
        padding: 4px 6px;
    }
    
    .form-label {
        font-size: 0.8rem;
    }
    
    .form-input {
        font-size: 0.85rem;
        padding: 8px;
    }
}
</style>

<?php include(__DIR__ . '/footer.php'); ?>