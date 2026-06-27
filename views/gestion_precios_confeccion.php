<?php
// views/gestion_precios_confeccion.php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login(['admin']); // Solo admin puede modificar precios

$pdo = app();
$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    try {
        switch ($accion) {
            case 'agregar':
                $tipo = trim($_POST['tipo_prenda']);
                $precio = floatval($_POST['precio_base']);
                $descripcion = trim($_POST['descripcion'] ?? '');
                
                if (empty($tipo) || $precio <= 0) {
                    throw new Exception("Tipo de prenda y precio son obligatorios");
                }
                
                $stmt = $pdo->prepare("INSERT INTO precios_base_confeccion (tipo_prenda, precio_base, descripcion) VALUES (?, ?, ?)");
                $stmt->execute([$tipo, $precio, $descripcion]);
                $mensaje = "✅ Precio base agregado exitosamente";
                $tipo_mensaje = 'success';
                break;
                
            case 'editar':
                $id = intval($_POST['id']);
                $precio = floatval($_POST['precio_base']);
                $descripcion = trim($_POST['descripcion'] ?? '');
                
                $stmt = $pdo->prepare("UPDATE precios_base_confeccion SET precio_base = ?, descripcion = ? WHERE id = ?");
                $stmt->execute([$precio, $descripcion, $id]);
                $mensaje = "✅ Precio actualizado exitosamente";
                $tipo_mensaje = 'success';
                break;
                
            case 'eliminar':
                $id = intval($_POST['id']);
                $stmt = $pdo->prepare("UPDATE precios_base_confeccion SET activo = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $mensaje = "✅ Precio desactivado correctamente";
                $tipo_mensaje = 'success';
                break;
        }
    } catch (Exception $e) {
        $mensaje = "❌ Error: " . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Cargar precios activos
$stmt = $pdo->query("SELECT * FROM precios_base_confeccion WHERE activo = 1 ORDER BY tipo_prenda ASC");
$precios = $stmt->fetchAll(PDO::FETCH_ASSOC);

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . "/sidebar_control.php"); ?>
    
    <main class="main-content-panel">
        
        <!-- ENCABEZADO -->
        <div class="page-header">
            <div>
                <h1>Gestión de Precios de Confección</h1>
                <p>Define los precios base para cada tipo de prenda. Estos precios se usarán automáticamente en los pedidos.</p>
            </div>
            <a href="panel_admin.php" class="btn-secondary">← Volver al Panel</a>
        </div>

        <!-- ALERTAS -->
        <?php if ($mensaje): ?>
            <div class="alert-<?= $tipo_mensaje ?>">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <!-- FORMULARIO AGREGAR NUEVO -->
        <div class="card-section">
            <h2 class="section-subtitle">➕ Agregar Nuevo Tipo de Prenda</h2>
            
            <form method="POST" class="form-agregar">
                <input type="hidden" name="accion" value="agregar">
                
                <div class="form-grid-4">
                    <div class="form-group">
                        <label for="tipo_prenda">Tipo de Prenda *</label>
                        <input type="text" name="tipo_prenda" id="tipo_prenda" required 
                               class="form-input" placeholder="Ej: Uniforme completo microfútbol">
                    </div>
                    <div class="form-group">
                        <label for="precio_base">Precio Base ($) *</label>
                        <input type="number" name="precio_base" id="precio_base" required 
                               min="0" step="1000" class="form-input" placeholder="180000">
                    </div>
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <input type="text" name="descripcion" id="descripcion" 
                               class="form-input" placeholder="Incluye camiseta, pantaloneta y medias">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn-primary btn-full">Agregar</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- LISTADO DE PRECIOS -->
        <div class="card-section">
            <h2 class="section-subtitle">📋 Precios Base Configurados</h2>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tipo de Prenda</th>
                            <th class="text-right">Precio Base</th>
                            <th>Descripción</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($precios)): ?>
                            <tr>
                                <td colspan="4" class="no-results">
                                    <span class="empty-icon">💰</span>
                                    <p>No hay precios configurados. Agrega el primero arriba.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($precios as $p): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($p['tipo_prenda']) ?></strong>
                                    </td>
                                    <td class="text-right">
                                        <span class="precio-valor">
                                            $<?= number_format($p['precio_base'], 0, ',', '.') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-small">
                                            <?= htmlspecialchars($p['descripcion'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="actions-cell">
                                            <button onclick="editarPrecio(<?= $p['id'] ?>, '<?= htmlspecialchars($p['tipo_prenda'], ENT_QUOTES) ?>', <?= $p['precio_base'] ?>, '<?= htmlspecialchars($p['descripcion'] ?? '', ENT_QUOTES) ?>')" 
                                                    class="btn-action" title="Editar">✏️</button>
                                            <form method="POST" class="inline-form" onsubmit="return confirm('¿Desactivar este precio?');">
                                                <input type="hidden" name="accion" value="eliminar">
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn-action btn-action-danger" title="Desactivar">🗑️</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- MODAL EDITAR -->
<div id="modalEditar" class="modal-overlay">
    <div class="modal-content">
        <h3 class="modal-title">✏️ Editar Precio Base</h3>
        <form method="POST">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label for="edit_tipo">Tipo de Prenda</label>
                <input type="text" id="edit_tipo" readonly class="form-input form-input-readonly">
            </div>
            
            <div class="form-group">
                <label for="edit_precio">Precio Base ($)</label>
                <input type="number" name="precio_base" id="edit_precio" required 
                       min="0" step="1000" class="form-input">
            </div>
            
            <div class="form-group">
                <label for="edit_descripcion">Descripción</label>
                <textarea name="descripcion" id="edit_descripcion" rows="3" class="form-input"></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" onclick="cerrarModal()" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<style>
/* ============================================
   GESTIÓN DE PRECIOS DE CONFECCIÓN
   ============================================ */

/* Encabezado */
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

/* Alertas */
.alert-success {
    padding: 12px 16px;
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 500;
}

.alert-error {
    padding: 12px 16px;
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 500;
}

/* Secciones tipo tarjeta */
.card-section {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.section-subtitle {
    color: #475569;
    font-size: 1.05rem;
    font-weight: 600;
    margin: 0 0 15px 0;
    padding-bottom: 8px;
    border-bottom: 2px solid #e2e8f0;
}

/* Formulario de agregar */
.form-agregar {
    margin-top: 15px;
}

.form-grid-4 {
    display: grid;
    grid-template-columns: 2fr 1fr 2fr auto;
    gap: 15px;
    align-items: end;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    display: block;
    font-size: 0.9rem;
    font-weight: 500;
    color: #334155;
    margin-bottom: 5px;
}

.form-input {
    width: 100%;
    padding: 10px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.95rem;
    box-sizing: border-box;
    transition: border-color 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-input-readonly {
    background: #f1f5f9;
    color: #64748b;
    cursor: not-allowed;
}

textarea.form-input {
    resize: vertical;
    font-family: inherit;
}

/* Botones */
.btn-primary {
    padding: 10px 20px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.95rem;
    text-decoration: none;
    display: inline-block;
    transition: background 0.2s;
}

.btn-primary:hover {
    background: #1d4ed8;
}

.btn-secondary {
    padding: 10px 20px;
    background: white;
    color: #475569;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.95rem;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
}

.btn-secondary:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}

.btn-full {
    width: 100%;
    padding: 10px;
}

/* Tabla */
.table-container {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: #f1f5f9;
    border-bottom: 2px solid #e2e8f0;
}

.data-table th {
    padding: 14px;
    text-align: left;
    color: #475569;
    font-weight: 600;
    font-size: 0.9rem;
}

.data-table td {
    padding: 14px;
    border-bottom: 1px solid #e2e8f0;
    color: #334155;
    vertical-align: middle;
}

.data-table tbody tr:hover {
    background: #f8fafc;
}

.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}

.text-small {
    font-size: 0.85rem;
    color: #64748b;
}

/* Precio destacado */
.precio-valor {
    font-weight: 700;
    color: #059669;
    font-size: 1rem;
}

/* Acciones */
.actions-cell {
    display: flex;
    gap: 6px;
    align-items: center;
    justify-content: center;
}

.btn-action {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.1rem;
    padding: 4px 6px;
    border-radius: 4px;
    transition: background 0.2s;
}

.btn-action:hover {
    background: #f1f5f9;
}

.btn-action-danger:hover {
    background: #fee2e2;
}

.inline-form {
    display: inline;
    margin: 0;
    padding: 0;
}

/* Sin resultados */
.no-results {
    text-align: center;
    padding: 40px !important;
    color: #94a3b8;
}

.no-results .empty-icon {
    font-size: 2rem;
    margin-bottom: 10px;
    display: block;
}

.no-results p {
    margin: 10px 0;
}

/* ============================================
   MODAL
   ============================================ */

.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: white;
    padding: 25px;
    border-radius: 12px;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.modal-title {
    margin: 0 0 20px 0;
    color: #1e293b;
    font-size: 1.2rem;
    font-weight: 700;
}

.modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        text-align: center;
    }
    
    .form-grid-4 {
        grid-template-columns: 1fr;
    }
    
    .data-table {
        font-size: 0.85rem;
    }
    
    .data-table th,
    .data-table td {
        padding: 8px;
    }
    
    .modal-actions {
        flex-direction: column-reverse;
    }
    
    .modal-actions button {
        width: 100%;
    }
}
</style>

<script>
function editarPrecio(id, tipo, precio, descripcion) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_tipo').value = tipo;
    document.getElementById('edit_precio').value = precio;
    document.getElementById('edit_descripcion').value = descripcion;
    document.getElementById('modalEditar').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalEditar').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
document.getElementById('modalEditar').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModal();
    }
});
</script>

<?php include(__DIR__ . "/footer.php"); ?>