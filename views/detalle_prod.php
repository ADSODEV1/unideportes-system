<?php
// views/detalle_prod.php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ProductoModel.php';
require_login(['vendedor', 'colaborador', 'admin']);
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

// 🆕 Capturar mensajes de éxito/error
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Determinar estado del stock
$stock = intval($producto['stock']);
if ($stock == 0) {
    $estadoStock = 'agotado';
    $claseStock = 'badge-danger';
    $textoStock = 'Agotado';
} elseif ($stock <= 5) {
    $estadoStock = 'bajo';
    $claseStock = 'badge-warning';
    $textoStock = "Bajo ({$stock})";
} else {
    $estadoStock = 'disponible';
    $claseStock = 'badge-success';
    $textoStock = "Disponible ({$stock})";
}

// 🆕 Determinar si el usuario puede aumentar stock (admin o colaborador)
$puedeAumentarStock = in_array($_SESSION['role'] ?? '', ['admin', 'colaborador'], true);

include(__DIR__ . '/header.php');
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>
    <main class="main-content-panel">
        
        <?php if ($success === 'stock_actualizado'): ?>
            <div class="alert alert-success">✅ Stock actualizado correctamente.</div>
        <?php elseif ($error === 'cantidad_invalida'): ?>
            <div class="alert alert-error">❌ La cantidad debe ser un número mayor a cero.</div>
        <?php elseif ($error === 'acceso_denegado'): ?>
            <div class="alert alert-error">❌ No tienes permisos para realizar esta acción.</div>
        <?php endif; ?>

        <!-- ENCABEZADO -->
        <div class="page-header">
            <div>
                <h1><?= htmlspecialchars($producto['nombre']) ?></h1>
                <p>Ref: <strong><?= htmlspecialchars($producto['referencia']) ?></strong></p>
            </div>
            <div class="header-actions">
                <a href="inventario.php" class="btn-secondary">← Volver</a>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <a href="editar_prod.php?id=<?= $producto['id'] ?>" class="btn-primary">✏️ Editar</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- TARJETA PRINCIPAL -->
        <div class="producto-detalle">
            <!-- RESUMEN RÁPIDO -->
            <div class="resumen-rapido">
                <div class="resumen-item">
                    <span class="resumen-label">Precio</span>
                    <span class="resumen-valor precio">$<?= number_format($producto['precio'], 0, ',', '.') ?></span>
                </div>
                <div class="resumen-item">
                    <span class="resumen-label">Stock</span>
                    <span class="badge <?= $claseStock ?>"><?= $textoStock ?></span>
                </div>
                <div class="resumen-item">
                    <span class="resumen-label">Estado</span>
                    <span class="badge badge-<?= ($producto['estado'] ?? 'activo') === 'activo' ? 'success' : 'danger' ?>">
                        <?= ucfirst($producto['estado'] ?? 'activo') ?>
                    </span>
                </div>
            </div>

            <!-- 🆕 FORMULARIO PARA AUMENTAR STOCK (Solo admin y colaborador) -->
            <?php if ($puedeAumentarStock): ?>
            <div class="info-card" style="margin-bottom: 25px; border-left: 4px solid #2563eb; background: #eff6ff;">
                <h2 class="section-subtitle" style="color: #1e40af;">📦 Aumentar Stock</h2>
                <p style="color: #475569; font-size: 0.9rem; margin-bottom: 15px;">
                    Ingresa la cantidad de unidades que deseas <strong>sumar</strong> al stock actual.
                </p>
                <form action="../controllers/aumentar_stock.php" method="POST" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                    <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">
                    <div style="flex: 1; min-width: 200px;">
                        <label style="display: block; font-size: 0.85rem; color: #475569; margin-bottom: 5px; font-weight: 600;">
                            Cantidad a agregar:
                        </label>
                        <input type="number" name="cantidad" min="1" step="1" required placeholder="Ej: 10"
                               style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem;">
                    </div>
                    <button type="submit" class="btn-primary" style="background: #2563eb; padding: 10px 20px;">
                        ➕ Agregar al stock
                    </button>
                </form>
                <p style="color: #64748b; font-size: 0.8rem; margin-top: 10px;">
                    Stock actual: <strong><?= $stock ?></strong> unidades
                </p>
            </div>
            <?php endif; ?>

            <!-- INFORMACIÓN DETALLADA -->
            <div class="info-grid">
                <div class="info-card">
                    <h2 class="section-subtitle">📋 Información General</h2>
                    <div class="info-row">
                        <span class="info-label">Nombre:</span>
                        <span class="info-value"><?= htmlspecialchars($producto['nombre']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Referencia:</span>
                        <span class="info-value"><?= htmlspecialchars($producto['referencia']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Categoría:</span>
                        <span class="info-value"><?= htmlspecialchars($producto['categoria'] ?? 'Sin categoría') ?></span>
                    </div>
                    <?php if (!empty($producto['created_at'])): ?>
                    <div class="info-row">
                        <span class="info-label">Fecha de Registro:</span>
                        <span class="info-value"><?= date('d/m/Y', strtotime($producto['created_at'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="info-card">
                    <h2 class="section-subtitle">🎨 Características</h2>
                    <div class="info-row">
                        <span class="info-label">Color:</span>
                        <span class="info-value"><?= htmlspecialchars($producto['color'] ?: 'No especificado') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Material:</span>
                        <span class="info-value"><?= htmlspecialchars($producto['material'] ?: 'No especificado') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Género:</span>
                        <span class="info-value"><?= htmlspecialchars($producto['genero'] ?: 'Unisex') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Talla:</span>
                        <span class="info-value">
                            <span class="talla-badge"><?= htmlspecialchars($producto['talla'] ?: 'N/A') ?></span>
                        </span>
                    </div>
                </div>

                <?php if (!empty($producto['descripcion'])): ?>
                <div class="info-card info-card-full">
                    <h2 class="section-subtitle">📝 Descripción</h2>
                    <p class="descripcion-texto"><?= nl2br(htmlspecialchars($producto['descripcion'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<style>
/* ============================================
DETALLE DE PRODUCTO - ESTILOS SIMPLIFICADOS
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
.header-actions {
    display: flex;
    gap: 10px;
}

/* Tarjeta principal */
.producto-detalle {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 25px;
}

/* Resumen rápido */
.resumen-rapido {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
    padding-bottom: 25px;
    border-bottom: 2px solid #e2e8f0;
}
.resumen-item {
    text-align: center;
    padding: 15px;
    background: #f8fafc;
    border-radius: 8px;
}
.resumen-label {
    display: block;
    font-size: 0.8rem;
    color: #64748b;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 8px;
    letter-spacing: 0.5px;
}
.resumen-valor {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1e293b;
}
.resumen-valor.precio {
    color: #059669;
    font-size: 1.5rem;
}

/* Grid de información */
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
.info-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
}
.info-card-full {
    grid-column: 1 / -1;
}
.section-subtitle {
    color: #475569;
    font-size: 1.05rem;
    font-weight: 600;
    margin: 0 0 15px 0;
    padding-bottom: 8px;
    border-bottom: 2px solid #e2e8f0;
}
.info-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e2e8f0;
}
.info-row:last-child {
    border-bottom: none;
}
.info-label {
    color: #64748b;
    font-size: 0.9rem;
    font-weight: 500;
}
.info-value {
    color: #1e293b;
    font-size: 0.9rem;
    font-weight: 600;
    text-align: right;
}
.descripcion-texto {
    color: #334155;
    font-size: 0.95rem;
    line-height: 1.6;
    margin: 0;
}

/* Talla badge */
.talla-badge {
    display: inline-block;
    padding: 4px 12px;
    background: white;
    color: #334155;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 600;
    border: 1px solid #e2e8f0;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}
.badge-success {
    background: #d1fae5;
    color: #065f46;
}
.badge-warning {
    background: #fef3c7;
    color: #92400e;
}
.badge-danger {
    background: #fee2e2;
    color: #991b1b;
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

/* Alertas */
.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 500;
}
.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #22c55e;
}
.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #ef4444;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        text-align: center;
    }
    .header-actions {
        width: 100%;
        justify-content: center;
    }
    .info-grid {
        grid-template-columns: 1fr;
    }
    .info-card-full {
        grid-column: 1;
    }
    .resumen-rapido {
        grid-template-columns: 1fr;
    }
    .info-row {
        flex-direction: column;
        gap: 4px;
    }
    .info-value {
        text-align: left;
    }
}
</style>

<?php include(__DIR__ . '/footer.php'); ?>