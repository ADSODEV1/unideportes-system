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
            <div class="alert alert-success" style="margin-bottom: 14px;">Registro de ticket exitoso</div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" style="margin-bottom: 14px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <section style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
            <form action="../controllers/soporte_tecnico_controller.php" method="POST" style="display: grid; grid-template-columns: 2fr 1fr 2fr auto; gap: 10px; align-items: end;">
                <input type="hidden" name="accion" value="crear">
                <div>
                    <label style="display:block; font-size:0.85rem; color:#334155; margin-bottom:4px;">Asunto</label>
                    <input type="text" name="asunto" required maxlength="180" placeholder="Describe el problema" style="width:100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
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
                    <label style="display:block; font-size:0.85rem; color:#334155; margin-bottom:4px;">Comentario / Solución</label>
                    <input type="text" name="comentario_solucion" maxlength="255" placeholder="Agrega detalle adicional" style="width:100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div>
                    <button type="submit" style="background:#1d4ed8; color:#fff; border:none; border-radius:6px; padding:10px 14px; font-weight:700; cursor:pointer;">Enviar Ticket</button>
                </div>
            </form>

            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top: 14px;">
                <span style="background:#fee2e2; color:#991b1b; border:1px solid #fecaca; border-radius:999px; padding:6px 10px; font-size:0.82rem; font-weight:700;">🔴 Crítica</span>
                <span style="background:#ffedd5; color:#9a3412; border:1px solid #fdba74; border-radius:999px; padding:6px 10px; font-size:0.82rem; font-weight:700;">🟠 Alta</span>
                <span style="background:#fef9c3; color:#854d0e; border:1px solid #fde047; border-radius:999px; padding:6px 10px; font-size:0.82rem; font-weight:700;">🟡 Media</span>
                <span style="background:#dcfce7; color:#166534; border:1px solid #86efac; border-radius:999px; padding:6px 10px; font-size:0.82rem; font-weight:700;">🟢 Baja</span>
            </div>
        </section>
    </main>
</div>

<?php include(__DIR__ . '/footer.php'); ?>
