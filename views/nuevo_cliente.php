<?php
// views/nuevo_cliente.php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/ClienteModel.php';

require_login(['vendedor', 'colaborador', 'admin']);
$conn = app();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nombre_completo'    => request('nombre_completo'),
        'nit_cedula'         => request('nit_cedula'),
        'telefono'           => request('telefono'),
        'email'              => request('email'),
        'tipo_cliente'       => request('tipo_cliente') ?: 'Individual',
        'direccion'          => request('direccion') ?: null,
        'barrio'             => request('barrio') ?: null,
        'ciudad'             => request('ciudad') ?: 'Sogamoso',
        'referencia_entrega' => request('referencia_entrega') ?: null,
    ];

    if (trim($data['nombre_completo']) === '' || trim($data['nit_cedula']) === '') {
        $error = 'Nombre y NIT/Cédula son obligatorios.';
    } elseif (!crearCliente($conn, $data)) {
        $error = 'No fue posible crear el cliente.';
    } else {
        redirect('clientes.php?msj=cliente_creado');
    }
}

include(__DIR__ . '/header.php');
?>

<div class="container admin-layout">
    <?php include(__DIR__ . '/sidebar_control.php'); ?>
    
    <main class="main-content-panel">
        
        <!-- ENCABEZADO CONSISTENTE -->
        <div class="page-header">
            <div>
                <h1>Registrar Nuevo Cliente</h1>
                <p>Completa la información del cliente para agregarlo al sistema.</p>
            </div>
            <a href="clientes.php" class="btn-secondary">
                ← Volver a Clientes
            </a>
        </div>

        <!-- MENSAJE DE ERROR -->
        <?php if ($error): ?>
            <div class="alert-error">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- FORMULARIO -->
        <form action="nuevo_cliente.php" method="POST" class="form-cliente">
            
            <!-- DATOS BÁSICOS -->
            <div class="form-section">
                <h2 class="section-subtitle">Información Básica</h2>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre_completo">Nombre completo *</label>
                        <input type="text" 
                               name="nombre_completo" 
                               id="nombre_completo" 
                               class="form-input" 
                               required 
                               placeholder="Ej: Juan Pérez">
                    </div>
                    
                    <div class="form-group">
                        <label for="nit_cedula">NIT / Cédula *</label>
                        <input type="text" 
                               name="nit_cedula" 
                               id="nit_cedula" 
                               class="form-input" 
                               required 
                               placeholder="Ej: 1234567890">
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="text" 
                               name="telefono" 
                               id="telefono" 
                               class="form-input" 
                               placeholder="Ej: 3101234567">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               class="form-input" 
                               placeholder="Ej: cliente@email.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo_cliente">Tipo de cliente</label>
                        <select name="tipo_cliente" id="tipo_cliente" class="form-input">
                            <option value="Individual">Persona Individual</option>
                            <option value="Equipo">Equipo Deportivo</option>
                            <option value="Colegio">Institución Educativa</option>
                            <option value="Empresa">Empresa</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- DATOS DE DOMICILIO -->
            <div class="form-section">
                <h2 class="section-subtitle">Información de Envío (Opcional)</h2>
                <p class="section-hint">Estos datos se usarán por defecto cuando el cliente solicite domicilio.</p>
                
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label for="direccion">Dirección base</label>
                        <input type="text" 
                               name="direccion" 
                               id="direccion" 
                               class="form-input" 
                               placeholder="Ej: Calle 11 # 12-34">
                    </div>
                    
                    <div class="form-group">
                        <label for="barrio">Barrio</label>
                        <input type="text" 
                               name="barrio" 
                               id="barrio" 
                               class="form-input" 
                               placeholder="Ej: Centro">
                    </div>
                    
                    <div class="form-group">
                        <label for="ciudad">Ciudad</label>
                        <input type="text" 
                               name="ciudad" 
                               id="ciudad" 
                               class="form-input" 
                               value="Sogamoso">
                    </div>
                    
                    <div class="form-group form-group-full">
                        <label for="referencia_entrega">Referencia de entrega</label>
                        <textarea name="referencia_entrega" 
                                  id="referencia_entrega" 
                                  class="form-input" 
                                  rows="2"
                                  placeholder="Ej: Frente al parque principal, casa de rejas negras"></textarea>
                    </div>
                </div>
            </div>

            <!-- BOTONES DE ACCIÓN -->
            <div class="form-actions">
                <a href="clientes.php" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-primary">Guardar Cliente</button>
            </div>
            
        </form>
    </main>
</div>

<style>
/* ============================================
   FORMULARIO DE NUEVO CLIENTE
   ============================================ */

/* Encabezado de página */
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

/* Secciones del formulario */
.form-cliente {
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
    margin: 0 0 5px 0;
    padding-bottom: 8px;
    border-bottom: 2px solid #e2e8f0;
}

.section-hint {
    color: #64748b;
    font-size: 0.85rem;
    margin: 0 0 15px 0;
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
    padding: 9px 12px;
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