<?php
// views/registrar_productos.php
require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login(['admin']); // Solo admin puede registrar productos

$pdo = app();
$error = '';
$success = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validación de campos obligatorios
        $nombre = trim($_POST['nombre'] ?? '');
        $referencia = trim($_POST['referencia'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        
        if (empty($nombre) || empty($referencia) || $precio <= 0) {
            throw new Exception("Nombre, referencia y precio son obligatorios");
        }
        
        // Verificar si la referencia ya existe
        $stmt = $pdo->prepare("SELECT id FROM productos WHERE referencia = ?");
        $stmt->execute([$referencia]);
        if ($stmt->fetch()) {
            throw new Exception("La referencia '{$referencia}' ya existe en el sistema");
        }
        
        // Generar código descriptivo automáticamente
        $stmt = $pdo->query("SELECT MAX(id) FROM productos");
        $nextId = ($stmt->fetchColumn() ?? 0) + 1;
        $codigo = 'PROD-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        
        // Insertar producto
        $sql = "INSERT INTO productos (
                    codigo_descriptivo, nombre, referencia, categoria, color, 
                    material, genero, estado, descripcion, talla, stock, precio
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $codigo,
            $nombre,
            $referencia,
            trim($_POST['categoria'] ?? '') ?: null,
            trim($_POST['color'] ?? '') ?: null,
            trim($_POST['material'] ?? '') ?: null,
            $_POST['genero'] ?? 'Unisex',
            $_POST['estado'] ?? 'activo',
            trim($_POST['descripcion'] ?? '') ?: null,
            $_POST['talla'] ?? 'Única',
            intval($_POST['stock'] ?? 0),
            $precio
        ]);
        
        $success = "✅ Producto registrado exitosamente con código: {$codigo}";
        
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . "/sidebar_control.php"); ?>
    
    <main class="main-content-panel">
        
        <!-- ENCABEZADO -->
        <div class="page-header">
            <div>
                <h1>Registrar Nuevo Producto</h1>
                <p>Agrega un nuevo producto al catálogo de Unideportes.</p>
            </div>
            <a href="inventario.php" class="btn-secondary">← Volver al Inventario</a>
        </div>

        <!-- ALERTAS -->
        <?php if ($error): ?>
            <div class="alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert-success"><?= $success ?></div>
        <?php endif; ?>

        <!-- FORMULARIO -->
        <form method="POST" class="form-producto">
            
            <!-- SECCIÓN 1: INFORMACIÓN BÁSICA -->
            <div class="form-section">
                <h2 class="section-subtitle">📋 Información Básica</h2>
                
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label for="nombre">Nombre del Producto *</label>
                        <input type="text" name="nombre" id="nombre" required 
                               class="form-input" placeholder="Ej: Camiseta Polo Sport">
                    </div>
                    
                    <div class="form-group">
                        <label for="referencia">Referencia *</label>
                        <input type="text" name="referencia" id="referencia" required 
                               class="form-input" placeholder="Ej: CAMPOL-M-001">
                    </div>
                    
                    <div class="form-group">
                        <label for="categoria">Categoría</label>
                        <input type="text" name="categoria" id="categoria" 
                               class="form-input" placeholder="Ej: Camisetas, Pantalones">
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
                               class="form-input" placeholder="Ej: Azul, Rojo, Negro">
                    </div>
                    
                    <div class="form-group">
                        <label for="material">Material</label>
                        <input type="text" name="material" id="material" 
                               class="form-input" placeholder="Ej: Algodón, Poliéster">
                    </div>
                    
                    <div class="form-group">
                        <label for="genero">Género</label>
                        <select name="genero" id="genero" class="form-input">
                            <option value="Unisex">Unisex</option>
                            <option value="Hombre">Hombre</option>
                            <option value="Mujer">Mujer</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="talla">Talla</label>
                        <select name="talla" id="talla" class="form-input">
                            <option value="Única">Única</option>
                            <option value="XS">XS</option>
                            <option value="S">S</option>
                            <option value="M">M</option>
                            <option value="L">L</option>
                            <option value="XL">XL</option>
                            <option value="XXL">XXL</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group form-group-full" style="margin-top: 15px;">
                    <label for="descripcion">Descripción</label>
                    <textarea name="descripcion" id="descripcion" rows="3" 
                              class="form-input" 
                              placeholder="Descripción detallada del producto para vendedores y catálogo..."></textarea>
                </div>
            </div>

            <!-- SECCIÓN 3: INVENTARIO Y PRECIO -->
            <div class="form-section">
                <h2 class="section-subtitle">💰 Inventario y Precio</h2>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="stock">Stock Inicial</label>
                        <input type="number" name="stock" id="stock" min="0" value="0" 
                               class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="precio">Precio ($) *</label>
                        <input type="number" name="precio" id="precio" step="1000" min="0" required 
                               class="form-input" placeholder="Ej: 85000">
                    </div>
                    
                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select name="estado" id="estado" class="form-input">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- BOTONES DE ACCIÓN -->
            <div class="form-actions">
                <a href="inventario.php" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-primary">💾 Guardar Producto</button>
            </div>
            
        </form>
    </main>
</div>

<style>
/* ============================================
   REGISTRAR PRODUCTO - ESTILOS SIMPLIFICADOS
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

/* Secciones del formulario */
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

<?php include(__DIR__ . "/footer.php"); ?>