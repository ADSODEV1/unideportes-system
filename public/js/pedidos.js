// ========================================
// ARCHIVO: public/js/pedidos.js
// Controla el carrito de pedidos de confección
// ========================================

// Estado global del carrito
let carrito = [];

// ========================================
// INICIALIZACIÓN AL CARGAR LA PÁGINA
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    renderCarrito();
    toggleCamposPago();
    configurarEventListeners();
});

// ========================================
// CONFIGURAR EVENT LISTENERS
// ========================================
function configurarEventListeners() {
    // Botón agregar al carrito
    const btnAgregar = document.getElementById('btnAgregarCarrito');
    if (btnAgregar) {
        btnAgregar.addEventListener('click', agregarAlCarrito);
    }
    
    // Validación del formulario
    const formPedido = document.getElementById('formPedido');
    if (formPedido) {
        formPedido.addEventListener('submit', validarYEnviarPedido);
    }
    
    // Cambio en método de pago
    const metodoPago = document.getElementById('metodo_pago_abono');
    if (metodoPago) {
        metodoPago.addEventListener('change', toggleCamposPago);
    }
}

// ========================================
// CARRITO: AGREGAR PRODUCTO
// ========================================
function agregarAlCarrito() {
    const select = document.getElementById('selectTipoPrenda');
    const cantidad = parseInt(document.getElementById('inputCantidad').value) || 0;
    const comentario = document.getElementById('inputComentario').value.trim();
    
    // Validaciones
    if (select.selectedIndex <= 0) {
        alert('⚠️ Por favor, selecciona un tipo de prenda.');
        return;
    }
    
    if (cantidad < 1) {
        alert('⚠️ La cantidad debe ser al menos 1.');
        return;
    }
    
    // Obtener datos del producto seleccionado
    const tipoPrendaId = select.value;
    const nombrePrenda = select.options[select.selectedIndex].dataset.nombre;
    const precio = parseFloat(select.options[select.selectedIndex].dataset.precio);
    
    // Verificar si ya existe en el carrito
    const existente = carrito.find(item => item.tipo_prenda_id == tipoPrendaId);
    
    if (existente) {
        existente.cantidad += cantidad;
        if (comentario) {
            existente.comentario += (existente.comentario ? '; ' : '') + comentario;
        }
    } else {
        carrito.push({
            tipo_prenda_id: tipoPrendaId,
            nombre: nombrePrenda,
            precio: precio,
            cantidad: cantidad,
            comentario: comentario
        });
    }
    
    // Actualizar vista
    renderCarrito();
    
    // Limpiar campos
    select.selectedIndex = 0;
    document.getElementById('inputCantidad').value = '1';
    document.getElementById('inputComentario').value = '';
    
    // Mensaje de éxito
    mostrarNotificacion('✅ Prenda agregada al carrito', 'success');
}

// ========================================
// CARRITO: RENDERIZAR TABLA
// ========================================
function renderCarrito() {
    const tbody = document.getElementById('carritoBody');
    
    if (!tbody) return;
    
    if (carrito.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 30px; color: #94a3b8;">
                    🛒 El carrito está vacío. Agrega prendas arriba.
                </td>
            </tr>
        `;
    } else {
        tbody.innerHTML = '';
        carrito.forEach((item, index) => {
            const subtotal = item.precio * item.cantidad;
            tbody.innerHTML += `
                <tr>
                    <td><strong>${item.nombre}</strong></td>
                    <td>${item.cantidad}</td>
                    <td>$${item.precio.toLocaleString('es-CO')}</td>
                    <td><strong>$${subtotal.toLocaleString('es-CO')}</strong></td>
                    <td style="font-size: 0.85rem; color: #64748b;">${item.comentario || '-'}</td>
                    <td>
                        <button type="button" class="btn-quitar" onclick="quitarDelCarrito(${index})">❌</button>
                    </td>
                </tr>
            `;
        });
    }
    
    actualizarTotal();
}

// ========================================
// CARRITO: QUITAR PRODUCTO
// ========================================
function quitarDelCarrito(index) {
    if (confirm('¿Estás seguro de quitar esta prenda del pedido?')) {
        carrito.splice(index, 1);
        renderCarrito();
        mostrarNotificacion('🗑️ Prenda removida del carrito', 'warning');
    }
}

// ========================================
// CÁLCULO AUTOMÁTICO DEL TOTAL
// ========================================
function actualizarTotal() {
    let subtotal = 0;
    carrito.forEach(item => {
        subtotal += item.precio * item.cantidad;
    });
    
    const ajuste = parseFloat(document.getElementById('ajuste_precio').value) || 0;
    const total = subtotal + ajuste;
    
    document.getElementById('display_subtotal').textContent = '$' + subtotal.toLocaleString('es-CO');
    document.getElementById('display_total').textContent = '$' + total.toLocaleString('es-CO');
    
    // Sugerencia de abono mínimo
    const sugerenciaDiv = document.getElementById('sugerenciaAbono');
    if (sugerenciaDiv) {
        if (total > 500000) {
            const minimo = total * 0.30;
            sugerenciaDiv.innerHTML = `💡 Para este pedido se requiere abono mínimo de <strong>$${minimo.toLocaleString('es-CO')}</strong> (30%)`;
            sugerenciaDiv.style.display = 'block';
        } else {
            sugerenciaDiv.style.display = 'none';
        }
    }
}

// ========================================
// CONTROL DE CAMPOS DE PAGO
// ========================================
function toggleCamposPago() {
    const metodo = document.getElementById('metodo_pago_abono').value;
    const seccionPlataforma = document.getElementById('seccionPlataforma');
    const seccionReferencia = document.getElementById('seccionReferencia');
    const plataformaInput = document.getElementById('plataforma_pago');
    const referenciaInput = document.getElementById('referencia_pago');
    
    // Ocultar todo primero
    seccionPlataforma.style.display = 'none';
    seccionReferencia.style.display = 'none';
    plataformaInput.required = false;
    referenciaInput.required = false;
    
    // Mostrar según el método
    if (metodo === 'Transferencia') {
        seccionPlataforma.style.display = 'block';
        seccionReferencia.style.display = 'block';
        plataformaInput.required = true;
        referenciaInput.required = true;
    } else if (metodo === 'Tarjeta') {
        seccionReferencia.style.display = 'block';
        referenciaInput.required = true;
    } else if (metodo === 'Otro') {
        seccionReferencia.style.display = 'block';
        referenciaInput.required = false;
    }
    // Efectivo: no mostrar nada adicional
}

// ========================================
// MODAL DE NUEVO CLIENTE
// ========================================
function abrirModalCliente() {
    document.getElementById('modalError').style.display = 'none';
    document.getElementById('modalCliente').style.display = 'flex';
}

function cerrarModalCliente() {
    document.getElementById('modalCliente').style.display = 'none';
    document.getElementById('m_nombre').value = '';
    document.getElementById('m_nit').value = '';
    document.getElementById('m_telefono').value = '';
}

function guardarClienteAjax() {
    const nombre = document.getElementById('m_nombre').value.trim();
    const nit = document.getElementById('m_nit').value.trim();
    const telefono = document.getElementById('m_telefono').value.trim();
    const errorDiv = document.getElementById('modalError');

    if (!nombre || !nit) {
        errorDiv.textContent = "⚠️ El nombre y el NIT/Cédula son obligatorios.";
        errorDiv.style.display = 'block';
        return;
    }

    const formData = new FormData();
    formData.append('ajax_crear_cliente', '1');
    formData.append('nombre_completo', nombre);
    formData.append('nit_cedula', nit);
    formData.append('telefono', telefono);

    fetch('nuevo_pedido.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const select = document.getElementById('cliente_id');
            const nuevaOpcion = document.createElement('option');
            nuevaOpcion.value = data.id;
            nuevaOpcion.textContent = data.nombre;
            nuevaOpcion.selected = true;
            select.appendChild(nuevaOpcion);
            cerrarModalCliente();
            mostrarNotificacion('✅ Cliente registrado exitosamente', 'success');
        } else {
            errorDiv.textContent = data.message;
            errorDiv.style.display = 'block';
        }
    })
    .catch(error => {
        errorDiv.textContent = "⚠️ Error de comunicación con el servidor.";
        errorDiv.style.display = 'block';
    });
}

// ========================================
// VALIDACIÓN Y ENVÍO DEL FORMULARIO
// ========================================
function validarYEnviarPedido(e) {
    // Validar cliente seleccionado
    const clienteSelect = document.getElementById('cliente_id');
    if (!clienteSelect.value) {
        e.preventDefault();
        alert('⚠️ Debes seleccionar un cliente.');
        clienteSelect.focus();
        return false;
    }
    
    // Validar carrito vacío
    if (carrito.length === 0) {
        e.preventDefault();
        alert('⚠️ Debes agregar al menos un producto al carrito.');
        return false;
    }
    
    // Guardar carrito en JSON
    document.getElementById('carritoJSON').value = JSON.stringify(carrito);
    
    // Validar abono mínimo
    const totalTexto = document.getElementById('display_total').textContent;
    const total = parseFloat(totalTexto.replace(/[$,.]/g, '')) || 0;
    const abono = parseFloat(document.getElementById('abono').value) || 0;
    
    if (total > 500000 && abono < (total * 0.30)) {
        e.preventDefault();
        alert(`⚠️ Para pedidos mayores a $500,000, se requiere un abono mínimo del 30% ($${(total * 0.30).toLocaleString('es-CO')})`);
        return false;
    }
    
    // Confirmación
    const clienteNombre = clienteSelect.options[clienteSelect.selectedIndex].text;
    
    const confirmar = confirm(
        `¿Confirmas procesar este pedido?\n\n` +
        `👤 Cliente: ${clienteNombre}\n` +
        `📦 Prendas: ${carrito.length} tipo(s)\n` +
        `💰 Total: $${total.toLocaleString('es-CO')}\n` +
        `💵 Abono: $${abono.toLocaleString('es-CO')}`
    );
    
    if (!confirmar) {
        e.preventDefault();
        return false;
    }
}

// ========================================
// NOTIFICACIONES VISUALES
// ========================================
function mostrarNotificacion(mensaje, tipo = 'success') {
    // Crear elemento de notificación
    const notificacion = document.createElement('div');
    notificacion.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        font-weight: 600;
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
    `;
    
    // Colores según tipo
    if (tipo === 'success') {
        notificacion.style.background = '#d1fae5';
        notificacion.style.color = '#065f46';
        notificacion.style.borderLeft = '4px solid #10b981';
    } else if (tipo === 'warning') {
        notificacion.style.background = '#fef3c7';
        notificacion.style.color = '#92400e';
        notificacion.style.borderLeft = '4px solid #f59e0b';
    } else if (tipo === 'error') {
        notificacion.style.background = '#fee2e2';
        notificacion.style.color = '#991b1b';
        notificacion.style.borderLeft = '4px solid #ef4444';
    }
    
    notificacion.textContent = mensaje;
    document.body.appendChild(notificacion);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        notificacion.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notificacion.remove(), 300);
    }, 3000);
}

// Animaciones CSS (inyectadas dinámicamente)
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(400px); opacity: 0; }
    }
`;
document.head.appendChild(style);