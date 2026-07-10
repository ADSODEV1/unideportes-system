<?php
// views/editar_prod.php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ProductoModel.php';
require_login(['admin']);

$pdo = app();

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: inventario.php?error=id_invalido');
    exit();
}

$id = intval($_GET['id']);
$producto = obtenerProductoPorId($pdo, $id);

if (!$producto) {
    header('Location: inventario.php?error=producto_no_encontrado');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'referencia' => trim($_POST['referencia'] ?? ''),
        'categoria' => trim($_POST['categoria'] ?? ''),
        'color' => trim($_POST['color'] ?? ''),
        'material' => trim($_POST['material'] ?? ''),
        'genero' => trim($_POST['genero'] ?? 'Unisex'),
        'estado' => trim($_POST['estado'] ?? 'activo'),
        'descripcion' => trim($_POST['descripcion'] ?? ''),
        'talla' => trim($_POST['talla'] ?? ''),
        'stock' => intval($_POST['stock'] ?? 0),
        'precio' => floatval($_POST['precio'] ?? 0),
    ];

    if ($data['nombre'] === '' || $data['referencia'] === '' || $data['precio'] <= 0) {
        $error = 'Nombre, referencia y precio son obligatorios.';
    } elseif (existeReferenciaProducto($pdo, $data['referencia'], $id)) {
        $error = 'Ya existe otro producto con la misma referencia.';
    } elseif (!actualizarProducto($pdo, $id, $data)) {
        $error = 'Error al actualizar el producto.';
    } else {
        header('Location: inventario.php?success=producto_actualizado');
        exit();
    }

    $producto = array_merge($producto, $data);
}

include(__DIR__ . '/header.php');
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">
        
        <!-- ENCABEZADO -->
        <div class="page-header">
            <div>
                <h1>Editar Producto</h1>
                <p>Modifica los datos del producto: <strong><?= htmlspecialchars($producto['nombre']) ?></strong></p>
            </div>
            <a href="inventario.php" class="btn-secondary">← Volver al Inventario</a>
        </div>

        <!-- ALERTA DE ERROR -->
        <?php if ($error): ?>
            <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- FORMULARIO -->
        <form action="editar_prod.php?id=<?= $producto['id'] ?>" method="POST" class="form-producto">
            
            <!-- SECCIÓN 1: INFORMACIÓN BÁSICA -->
            <div class="form-section">
                <h2 class="section-subtitle">📋 Información Básica</h2>
                
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label for="nombre">Nombre del Producto *</label>
                        <input type="text" name="nombre" id="nombre" required 
                               class="form-input" 
                               value="<?= htmlspecialchars($producto['nombre']) ?>"
                               placeholder="Ej: Camiseta Polo Azul">
                    </div>
                    
                    <div class="form-group">
                        <label for="referencia">Referencia *</label>
                        <input type="text" name="referencia" id="referencia" required 
                               class="form-input" 
                               value="<?= htmlspecialchars($producto['referencia']) ?>"
                               placeholder="Ej: CAMPOL-M-001">
                    </div>
                    
                    <div class="form-group">
                        <label for="categoria">Categoría / Línea</label>
                        <input type="text" name="categoria" id="categoria" 
                               class="form-input" 
                               value="<?= htmlspecialchars($producto['categoria'] ?? '') ?>"
                               placeholder="Ej: Camisetas, Pantalones">
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: CARACTERÍSTICAS -->
            <div class="form-section">
                <h2 class="section-subtitle">🎨 Características del Producto</h2>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="color">Color</label>
                        <input type="text" name="color" id="color" 
                               class="form-input" 
                               value="<?= htmlspecialchars($producto['color'] ?? '') ?>"
                               placeholder="Ej: Azul, Negro">
                    </div>
                    
                    <div class="form-group">
                        <label for="material">Material</label>
                        <input type="text" name="material" id="material" 
                               class="form-input" 
                               value="<?= htmlspecialchars($producto['material'] ?? '') ?>"
                               placeholder="Ej: Algodón, Poliéster">
                    </div>
                    
                    <div class="form-group">
                        <label for="genero">Género</label>
                        <select name="genero" id="genero" class="form-input">
                            <option value="Unisex" <?= ($producto['genero'] ?? '') === 'Unisex' ? 'selected' : '' ?>>Unisex</option>
                            <option value="Hombre" <?= ($producto['genero'] ?? '') === 'Hombre' ? 'selected' : '' ?>>Hombre</option>
                            <option value="Mujer" <?= ($producto['genero'] ?? '') === 'Mujer' ? 'selected' : '' ?>>Mujer</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="talla">Talla</label>
                        <select name="talla" id="talla" class="form-input">
                            <?php 
                            $tallas = ['Única', 'XS', 'S', 'M', 'L', 'XL', 'XXL'];
                            $tallaActual = $producto['talla'] ?? 'Única';
                            foreach ($tallas as $t): 
                            ?>
                                <option value="<?= $t ?>" <?= $tallaActual === $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select name="estado" id="estado" class="form-input">
                            <option value="activo" <?= ($producto['estado'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>✅ Activo</option>
                            <option value="inactivo" <?= ($producto['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>⏸️ Inactivo</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group form-group-full" style="margin-top: 15px;">
                    <label for="descripcion">Descripción</label>
                    <textarea name="descripcion" id="descripcion" rows="3" 
                              class="form-input" 
                              placeholder="Descripción detallada del producto..."><?= htmlspecialchars($producto['descripcion'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- SECCIÓN 3: INVENTARIO Y PRECIO -->
            <div class="form-section">
                <h2 class="section-subtitle">💰 Inventario y Precio</h2>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="stock">Stock Actual</label>
                        <input type="number" name="stock" id="stock" min="0" required 
                               class="form-input" 
                               value="<?= intval($producto['stock']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="precio">Precio de Venta ($) *</label>
                        <input type="number" name="precio" id="precio" step="1" min="0" required 
                               class="form-input" 
                               value="<?= htmlspecialchars($producto['precio']) ?>"
                               placeholder="Ej: 85000">
                    </div>
                </div>
            </div>

            <!-- BOTONES DE ACCIÓN -->
            <div class="form-actions">
                <a href="inventario.php" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-primary">💾 Guardar Cambios</button>
            </div>
            
        </form>
    </main>
</div>

<style>
/* ============================================
   EDITAR PRODUCTO - ESTILOS SIMPLIFICADOS
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

/* Alerta de error */
.alert-error {
    padding: 12px 16px;
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 500;
}

/* Formulario */
.form-producto {
    background: white;
}

.form-section {
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

/* Grid del formulario */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group-full {
    grid-column: 1 / -1;
}

/* Labels */
.form-group label {
    display: block;
    font-size: 0.9rem;
    font-weight: 500;
    color: #334155;
    margin-bottom: 5px;
}

/* Inputs */
.form-input {
    width: 100%;
    padding: 10px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.95rem;
    background: white;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}

.form-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-input::placeholder {
    color: #94a3b8;
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

/* Acciones del formulario */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 10px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

/* Responsive */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-group-full {
        grid-column: 1;
    }
    
    .page-header {
        flex-direction: column;
        text-align: center;
    }
    
    .form-actions {
        flex-direction: column-reverse;
    }
    
    .form-actions > * {
        width: 100%;
        text-align: center;
    }
}
</style>

<?php include(__DIR__ . '/footer.php'); ?>