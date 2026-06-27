<?php
// views/registrar_productos.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['admin']);

$error = '';

if (!empty($_GET['error'])) {
    $error = match($_GET['error']) {
        'datos_invalidos' => '⚠️ Por favor completa todos los campos requeridos correctamente.',
        'referencia_duplicada' => '⚠️ Ya existe un producto con esa referencia automática. Intenta cambiar levemente el nombre.',
        'fallo_en_registro' => '⚠️ No se pudo registrar el producto. Intenta de nuevo.',
        default => ''
    };
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">
        
        <!-- ENCABEZADO -->
        <div class="page-header">
            <div>
                <h1>Registrar Nuevo Producto</h1>
                <p>Ingresa los detalles de la prenda para expandir el catálogo de Unideportes.</p>
            </div>
            <a href="inventario.php" class="btn-secondary">← Volver al Inventario</a>
        </div>

        <!-- ALERTA DE ERROR -->
        <?php if ($error): ?>
            <div class="alert-error"><?= $error ?></div>
        <?php endif; ?>

        <!-- FORMULARIO -->
        <form action="../controllers/insert_product.php" method="POST" class="form-producto">
            
            <!-- SECCIÓN 1: INFORMACIÓN BÁSICA -->
            <div class="form-section">
                <h2 class="section-subtitle">📋 Información Básica</h2>
                
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label for="txtNombre">Nombre de la prenda *</label>
                        <input type="text" id="txtNombre" name="nombre" required 
                               class="form-input" placeholder="Ej: Camiseta Polo Azul">
                    </div>
                    
                    <div class="form-group">
                        <label for="cmbTalla">Talla</label>
                        <select id="cmbTalla" name="talla" class="form-input">
                            <option value="S">S</option>
                            <option value="M" selected>M</option>
                            <option value="L">L</option>
                            <option value="XL">XL</option>
                            <option value="Unica">Única</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="categoria">Categoría / Línea</label>
                        <select name="categoria" id="categoria" class="form-input">
                            <option value="Camisetas">Camisetas</option>
                            <option value="Pantalonetas">Pantalonetas</option>
                            <option value="Sudaderas">Sudaderas</option>
                            <option value="Chaquetas">Chaquetas</option>
                            <option value="Calzado">Calzado</option>
                            <option value="Accesorios">Accesorios</option>
                            <option value="Selección">Selección</option>
                        </select>
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
                               class="form-input" placeholder="Ej: Azul, Negro, Verde">
                    </div>
                    
                    <div class="form-group">
                        <label for="material">Material</label>
                        <input type="text" name="material" id="material" 
                               class="form-input" placeholder="Ej: Algodón, Poliéster">
                    </div>
                    
                    <div class="form-group">
                        <label for="genero">Género</label>
                        <select name="genero" id="genero" class="form-input">
                            <option value="Unisex" selected>Unisex</option>
                            <option value="Hombre">Hombre</option>
                            <option value="Mujer">Mujer</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select name="estado" id="estado" class="form-input">
                            <option value="activo" selected>Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group form-group-full" style="margin-top: 15px;">
                    <label for="descripcion">Descripción</label>
                    <textarea name="descripcion" id="descripcion" rows="3" 
                              class="form-input" 
                              placeholder="Descripción corta para tienda y vendedor..."></textarea>
                </div>
            </div>

            <!-- SECCIÓN 3: REFERENCIA Y PRECIO -->
            <div class="form-section">
                <h2 class="section-subtitle">💰 Código, Stock y Precio</h2>
                
                <div class="form-group form-group-full">
                    <label for="txtReferencia">Referencia / Código Autogenerado</label>
                    <small class="form-hint">Este código lo calcula el sistema para evitar duplicados en fábrica.</small>
                    <input type="text" id="txtReferencia" name="referencia" readonly required 
                           class="form-input form-input-readonly" 
                           placeholder="Se generará automáticamente al escribir el nombre...">
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="stock">Stock Inicial</label>
                        <input type="number" name="stock" id="stock" value="0" min="0" required 
                               class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="precio">Precio de Venta ($)</label>
                        <input type="number" name="precio" id="precio" step="0.01" min="0" required 
                               class="form-input" placeholder="Ej: 85000">
                    </div>
                </div>
            </div>

            <!-- BOTONES DE ACCIÓN -->
            <div class="form-actions">
                <a href="inventario.php" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-primary">💾 Registrar Producto</button>
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

.form-hint {
    font-size: 0.85rem;
    color: #64748b;
    margin-bottom: 5px;
    display: block;
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

.form-input-readonly {
    background: #f1f5f9;
    color: #1e293b;
    font-family: monospace;
    font-weight: bold;
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

<script>
document.addEventListener("DOMContentLoaded", function() {
    const txtNombre = document.getElementById("txtNombre");
    const cmbTalla = document.getElementById("cmbTalla");
    const txtReferencia = document.getElementById("txtReferencia");

    function generarCodigo() {
        let nombre = txtNombre.value.trim();
        let talla = cmbTalla.value;

        if (nombre.length < 3) {
            txtReferencia.value = "";
            return;
        }

        let limpio = nombre.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        let palabras = limpio.toUpperCase().split(/\s+/).filter(p => p.length > 2);
        let prefijo = "";
        
        if (palabras.length >= 2) {
            prefijo = palabras[0].substring(0, 3) + palabras[1].substring(0, 3);
        } else if (palabras.length === 1) {
            prefijo = palabras[0].substring(0, 4) + "XX";
        } else {
            prefijo = limpio.substring(0, 4).toUpperCase() + "XX";
        }

        prefijo = prefijo.replace(/[^A-Z]/g, "X");
        let hashAlterno = Math.floor(100 + Math.random() * 900);

        txtReferencia.value = `${prefijo}-${talla.toUpperCase()}-${hashAlterno}`;
    }

    txtNombre.addEventListener("input", generarCodigo);
    cmbTalla.addEventListener("change", generarCodigo);
});
</script>

<?php include(__DIR__ . "/footer.php"); ?>