<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login(['admin']);

$error = '';

if (!empty($_GET['error'])) {
    if ($_GET['error'] === 'datos_invalidos') {
        $error = 'Por favor completa todos los campos requeridos correctamente.';
    } elseif ($_GET['error'] === 'referencia_duplicada') {
        $error = 'Ya existe un producto con esa referencia automática. Intenta cambiar levemente el nombre.';
    } elseif ($_GET['error'] === 'fallo_en_registro') {
        $error = 'No se pudo registrar el producto. Intenta de nuevo.';
    }
}

include(__DIR__ . "/header.php");
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>

    <main class="main-content-panel">
        <div class="page-header" style="margin-bottom: 25px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
            <h1 style="margin: 0; font-size: 1.8rem; color: #0f172a;">Nueva Mercancía</h1>
            <p style="color: #64748b; margin: 5px 0 0 0;">Ingrese los detalles de la prenda para expandir el catálogo de producción.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error" style="background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="users-form" style="background: #ffffff; padding: 30px; border-radius: 8px; border: 1px solid #e2e8f0; max-width: 600px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <form action="../controllers/insert_product.php" method="POST" style="display: flex; flex-direction: column; gap: 18px;">
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #334155;">Nombre de la prenda</label>
                    <input type="text" id="txtNombre" name="nombre" required placeholder="Ej: Camiseta Polo Azul" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 0.95rem;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #334155;">Talla</label>
                        <select id="cmbTalla" name="talla" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 0.95rem; background: white;">
                            <option value="S">S</option>
                            <option value="M" selected>M</option>
                            <option value="L">L</option>
                            <option value="XL">XL</option>
                            <option value="Unica">Única</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #334155;">Categoría / Línea</label>
                        <select name="categoria" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 0.95rem; background: white;">
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

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #334155;">Color</label>
                        <input type="text" name="color" placeholder="Ej: Azul, Negro, Verde" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 0.95rem;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #334155;">Material</label>
                        <input type="text" name="material" placeholder="Ej: Algodón, Poliéster" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 0.95rem;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #334155;">Género</label>
                        <select name="genero" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 0.95rem; background: white;">
                            <option value="Unisex" selected>Unisex</option>
                            <option value="Hombre">Hombre</option>
                            <option value="Mujer">Mujer</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #334155;">Estado</label>
                        <select name="estado" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 0.95rem; background: white;">
                            <option value="activo" selected>Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #334155;">Descripción</label>
                    <textarea name="descripcion" rows="3" placeholder="Descripción corta para tienda y vendedor..." style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 0.95rem;"></textarea>
                </div>

                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 4px; color: #334155;">Referencia / Código Autogenerado</label>
                    <small style="display: block; color: #64748b; margin-bottom: 6px;">Este código lo calcula el sistema para evitar duplicados en fábrica.</small>
                    <input type="text" id="txtReferencia" name="referencia" readonly required placeholder="Se generará automáticamente..." style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 0.95rem; background: #f1f5f9; font-family: monospace; font-weight: bold; color: #1e3a8a;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #334155;">Stock Inicial</label>
                        <input type="number" name="stock" value="0" min="0" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #334155;">Precio de Venta ($)</label>
                        <input type="number" name="precio" step="0.01" min="0" required placeholder="0.00" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                    </div>
                </div>

                <button type="submit" class="btn-principal" style="padding: 12px; background: #1e3a8a; color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 1rem; cursor: pointer; margin-top: 10px; transition: background 0.2s;">
                    💾 Registrar Producto
                </button>
            </form>

            <div style="margin-top: 20px; border-top: 1px solid #edf2f7; padding-top: 15px; text-align: center;">
                <a href="inventario.php" style="color: #475569; text-decoration: none; font-size: 0.9rem; font-weight: 500;">← Volver al panel de inventario</a>
            </div>
        </div>
    </main>
</div>

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