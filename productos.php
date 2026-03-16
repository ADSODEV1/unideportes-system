<?php
session_start();
include("connection.php");

// 1. SEGURIDAD: Solo el Admin puede registrar productos nuevos
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php?error=acceso_denegado");
    exit();
}

include("header.php");
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-lg" style="border-radius: 20px;">
                <div class="card-body p-4 text-center">
                    <div class="mb-4">
                        <div class="display-6 mb-2">📦</div>
                        <h3 class="fw-bold" style="color: #1A2B4C;">Nueva Mercancía</h3>
                        <p class="text-muted small">Ingrese los detalles de las prendas para Unideportes.</p>
                    </div>
                    
                    <form action="insert_product.php" method="POST" class="text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nombre de la Prenda</label>
                            <input type="text" name="nombre" class="form-control bg-light border-0" placeholder="Ej: Camiseta Polo Azul" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Referencia / Código</label>
                                <input type="text" name="referencia" class="form-control bg-light border-0" placeholder="REF-001" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Talla</label>
                                <select name="talla" class="form-select bg-light border-0">
                                    <option value="S">Talla S</option>
                                    <option value="M" selected>Talla M</option>
                                    <option value="L">Talla L</option>
                                    <option value="XL">Talla XL</option>
                                    <option value="Unica">Talla Única</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Stock Inicial</label>
                                <input type="number" name="stock" class="form-control bg-light border-0" value="0" min="0" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Precio de Venta</label>
                                <div class="input-group">
                                    <span class="input-group-text border-0 bg-light">$</span>
                                    <input type="number" name="precio" class="form-control bg-light border-0" placeholder="45000" min="0" required>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-lg w-100 text-white fw-bold shadow-sm" style="background-color: #E61E2A; border-radius: 12px;">
                            REGISTRAR PRODUCTO
                        </button>
                    </form>
                </div>
            </div>
            <div class="text-center mt-3">
                <a href="inventario.php" class="text-muted small text-decoration-none">← Volver al Inventario General</a>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>