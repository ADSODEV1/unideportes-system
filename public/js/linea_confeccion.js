// public/js/linea_confeccion.js

console.log('linea_confeccion.js cargado');

function inicializarLineaConfeccion() {
    console.log('Inicializando linea_confeccion.js');

    const btnAgregar = document.getElementById('btnAgregar');
    const productoInput = document.getElementById('productoInput');
    const productoPrecio = document.getElementById('productoPrecio');
    const productoColor = document.getElementById('productoColor');
    const productoTalla = document.getElementById('productoTalla');
    const productoCantidad = document.getElementById('productoCantidad');
    const carritoBody = document.getElementById('carritoBody');
    const inputTotal = document.getElementById('inputTotal');
    const inputAbono = document.getElementById('inputAbono');
    const ventaJSON = document.getElementById('ventaJSON');
    const formVentaMayorista = document.getElementById('formVentaMayorista');
    const mensajeAlerta = document.getElementById('mensajeAlerta');
    const txtTotal = document.getElementById('txtTotal');
    const txtDescuento = document.getElementById('txtDescuento');
    const txtTotalFinal = document.getElementById('txtTotalFinal');
    const txtSaldoPendiente = document.getElementById('txtSaldoPendiente');
    const clienteInput = document.getElementById('clienteInput');
    const listaClientes = document.getElementById('listaClientes');
    const clienteIdHidden = document.getElementById('cliente_id_hidden');
    const btnToggleNuevoCliente = document.getElementById('btnToggleNuevoCliente');
    const nuevoClienteSection = document.getElementById('nuevoClienteSection');
    const nuevoClienteNombre = document.getElementById('nuevo_cliente_nombre_completo');
    const nuevoClienteNit = document.getElementById('nuevo_cliente_nit_cedula');
    const fechaEntregaInput = document.getElementById('fecha_entrega');
    const tipoEntrega = document.getElementById('tipo_entrega');
    const direccionEntrega = document.getElementById('direccion_entrega');
    const barrioEntrega = document.getElementById('barrio_entrega');
    const ciudadEntrega = document.getElementById('ciudad_entrega');
    const seccionDomicilio = document.getElementById('seccionDomicilio');
    const observacionesEntregaInput = document.getElementById('observaciones_entrega');

    let carrito = [];

    function mostrarAlerta(msg, tipo = 'info') {
        if (mensajeAlerta) {
            mensajeAlerta.innerText = msg;
            mensajeAlerta.style.display = 'block';
            mensajeAlerta.style.backgroundColor = tipo === 'danger' ? '#fee2e2' : '#fef3c7';
            mensajeAlerta.style.color = tipo === 'danger' ? '#991b1b' : '#92400e';
            mensajeAlerta.style.border = `1px solid ${tipo === 'danger' ? '#fca5a5' : '#fde68a'}`;
        } else {
            alert(msg);
        }
    }

    function ocultarAlerta() {
        if (mensajeAlerta) {
            mensajeAlerta.style.display = 'none';
        }
    }

    const carritoStatus = document.getElementById('carritoStatus');
    const modalEditar    = document.getElementById('modalEditarItem');
    const editNombre     = document.getElementById('editNombre');
    const editColor      = document.getElementById('editColor');
    const editTalla      = document.getElementById('editTalla');
    const editPrecio     = document.getElementById('editPrecio');
    const editCantidad   = document.getElementById('editCantidad');
    const btnGuardar     = document.getElementById('btnGuardarEdicion');
    const btnCancelar    = document.getElementById('btnCancelarEdicion');

    let editandoIndex = -1;

    function abrirModalEdicion(index) {
        if (index < 0 || index >= carrito.length) return;
        editandoIndex = index;
        const item = carrito[index];
        if (editNombre)   editNombre.value   = item.nombre;
        if (editColor)    editColor.value    = item.color;
        if (editTalla)    editTalla.value    = item.talla;
        if (editPrecio)   editPrecio.value   = item.precio_unitario;
        if (editCantidad) editCantidad.value = item.cantidad;
        if (modalEditar)  modalEditar.style.display = 'flex';
    }

    function cerrarModalEdicion() {
        editandoIndex = -1;
        if (modalEditar) modalEditar.style.display = 'none';
    }

    if (btnGuardar) {
        btnGuardar.addEventListener('click', () => {
            if (editandoIndex < 0 || editandoIndex >= carrito.length) return;
            const nombre   = (editNombre?.value   || '').trim();
            const precio   = parseFloat(editPrecio?.value   || '0');
            const cantidad = parseInt(editCantidad?.value  || '1', 10);
            if (!nombre) { mostrarAlerta('El nombre no puede estar vacío', 'danger'); return; }
            if (precio <= 0) { mostrarAlerta('El precio debe ser mayor a 0', 'danger'); return; }
            if (cantidad <= 0) { mostrarAlerta('La cantidad debe ser al menos 1', 'danger'); return; }
            carrito[editandoIndex].nombre          = nombre;
            carrito[editandoIndex].color           = (editColor?.value   || '').trim() || 'Sin color';
            carrito[editandoIndex].talla           = (editTalla?.value   || '').trim() || 'Sin talla';
            carrito[editandoIndex].precio_unitario = precio;
            carrito[editandoIndex].cantidad        = cantidad;
            cerrarModalEdicion();
            actualizarCarritoUI();
            mostrarAlerta('✓ Producto actualizado', 'success');
            setTimeout(ocultarAlerta, 1400);
        });
    }

    if (btnCancelar) btnCancelar.addEventListener('click', cerrarModalEdicion);
    if (modalEditar) modalEditar.addEventListener('click', (e) => { if (e.target === modalEditar) cerrarModalEdicion(); });

    window.__editarDelCarrito = (index) => abrirModalEdicion(index);

    function actualizarCarritoUI() {
        console.log('Actualizando carrito UI. items:', carrito.length);
        if (!carritoBody) return;

        carritoBody.innerHTML = '';
        let subtotal = 0;
        let totalUnidades = 0;

        carrito.forEach((item, index) => {
            subtotal += item.precio_unitario * item.cantidad;
            totalUnidades += item.cantidad;

            const row = document.createElement('tr');
            row.innerHTML = `
                <td style="padding:10px;">${item.nombre}</td>
                <td style="padding:10px;">${item.color}</td>
                <td style="padding:10px;">${item.talla}</td>
                <td style="padding:10px;">$${item.precio_unitario.toLocaleString('co-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0})}</td>
                <td style="padding:10px; text-align:center;">${item.cantidad}</td>
                <td style="padding:10px; text-align:center;">
                    <button type="button" onclick="window.__editarDelCarrito(${index})" title="Editar" style="background:none; border:none; cursor:pointer; font-size:1rem; margin-right:4px;">✏️</button>
                    <button type="button" onclick="window.__quitarDelCarrito(${index})" title="Quitar" style="background:none; border:none; cursor:pointer; font-size:1rem;">❌</button>
                </td>
            `;
            carritoBody.appendChild(row);
        });

        let descuento = 0;
        if (totalUnidades >= 20) descuento = subtotal * 0.10;
        else if (totalUnidades >= 10) descuento = subtotal * 0.05;

        const totalFinal = subtotal - descuento;

        if (txtTotal) txtTotal.innerText = `$${subtotal.toLocaleString('co-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0})}`;
        if (txtDescuento) txtDescuento.innerText = `$${descuento.toLocaleString('co-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0})}`;
        if (txtTotalFinal) txtTotalFinal.innerText = `$${totalFinal.toLocaleString('co-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0})}`;
        if (inputTotal) inputTotal.value = totalFinal.toFixed(2);
        if (ventaJSON) ventaJSON.value = JSON.stringify(carrito);
        if (carritoStatus) carritoStatus.innerText = `Carrito: ${carrito.length} producto${carrito.length === 1 ? '' : 's'}.`;

        recalcularSaldo();
    }

    function recalcularSaldo() {
        if (!inputTotal || !inputAbono || !txtSaldoPendiente) return;
        const total = parseFloat(inputTotal.value) || 0;
        const abono = parseFloat(inputAbono.value) || 0;
        const saldo = Math.max(0, total - abono);
        txtSaldoPendiente.innerText = `$${saldo.toLocaleString('co-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0})}`;
    }

    window.__quitarDelCarrito = (index) => {
        if (index >= 0 && index < carrito.length) {
            carrito.splice(index, 1);
            actualizarCarritoUI();
        }
    };

    if (btnAgregar) {
        btnAgregar.addEventListener('click', (event) => {
            event.preventDefault();
            console.log('btnAgregar clicked');

            const nombre = productoInput ? (productoInput.value || '').trim() : '';
            const precio = productoPrecio ? parseFloat(productoPrecio.value) || 0 : 0;
            const color = productoColor ? (productoColor.value || '').trim() : 'Sin color';
            const talla = productoTalla ? (productoTalla.value || '').trim() : 'Sin talla';
            const cantidad = productoCantidad ? parseInt(productoCantidad.value, 10) || 0 : 0;

            console.log({ nombre, precio, color, talla, cantidad });

            if (!nombre) {
                mostrarAlerta('Ingresa el nombre del producto', 'danger');
                return;
            }
            if (precio <= 0) {
                mostrarAlerta('Ingresa un precio válido', 'danger');
                return;
            }
            if (cantidad <= 0) {
                mostrarAlerta('Ingresa una cantidad válida', 'danger');
                return;
            }

            carrito.push({
                producto_id: 0,
                nombre,
                color,
                talla,
                precio_unitario: precio,
                cantidad,
                comentario_vendedor: ''
            });

            if (productoInput) productoInput.value = '';
            if (productoPrecio) productoPrecio.value = '';
            if (productoColor) productoColor.value = '';
            if (productoTalla) productoTalla.value = '';
            if (productoCantidad) productoCantidad.value = '1';

            mostrarAlerta('✓ Producto agregado', 'success');
            setTimeout(ocultarAlerta, 1400);
            actualizarCarritoUI();
        });
    } else {
        console.error('btnAgregar no encontrado');
    }

    if (clienteInput && listaClientes && clienteIdHidden) {
        clienteInput.addEventListener('input', function() {
            const value = this.value.trim();
            clienteIdHidden.value = '';

            for (let i = 0; i < listaClientes.options.length; i++) {
                const opt = listaClientes.options[i];
                if (opt.value === value) {
                    clienteIdHidden.value = opt.dataset.id || '';
                    if (direccionEntrega) direccionEntrega.value = opt.dataset.direccion || '';
                    if (barrioEntrega) barrioEntrega.value = opt.dataset.barrio || '';
                    if (ciudadEntrega) ciudadEntrega.value = opt.dataset.ciudad || 'Sogamoso';
                    if (observacionesEntregaInput) observacionesEntregaInput.value = opt.dataset.referencia || '';
                    break;
                }
            }
        });
    }

    if (btnToggleNuevoCliente && nuevoClienteSection && clienteIdHidden) {
        btnToggleNuevoCliente.addEventListener('click', function() {
            const isHidden = nuevoClienteSection.style.display === 'none' || nuevoClienteSection.style.display === '';
            nuevoClienteSection.style.display = isHidden ? 'block' : 'none';
            this.textContent = isHidden ? 'Ocultar formulario de cliente' : 'Crear cliente nuevo';
            clienteIdHidden.value = isHidden ? 'NUEVO' : '';

            if (isHidden) {
                if (clienteInput) clienteInput.value = '';
                if (direccionEntrega) direccionEntrega.value = '';
                if (barrioEntrega) barrioEntrega.value = '';
                if (ciudadEntrega) ciudadEntrega.value = 'Sogamoso';
                if (observacionesEntregaInput) observacionesEntregaInput.value = '';
                const firstNewClient = document.getElementById('nuevo_cliente_nombre_completo');
                if (firstNewClient) firstNewClient.focus();
            }
        });
    }

    if (tipoEntrega && seccionDomicilio) {
        tipoEntrega.addEventListener('change', function() {
            if (this.value === 'Domicilio') {
                seccionDomicilio.style.display = 'block';
            } else {
                seccionDomicilio.style.display = 'none';
                if (direccionEntrega) direccionEntrega.value = '';
                if (barrioEntrega) barrioEntrega.value = '';
                if (ciudadEntrega) ciudadEntrega.value = '';
                if (observacionesEntregaInput) observacionesEntregaInput.value = '';
            }
        });
    }

    if (inputAbono) {
        inputAbono.addEventListener('input', recalcularSaldo);
    }

    if (formVentaMayorista) {
        formVentaMayorista.addEventListener('submit', (event) => {
            console.log('formVentaMayorista submit');
            console.log('carrito length:', carrito.length);

            if (carrito.length === 0) {
                mostrarAlerta('Agregue al menos un producto al pedido.', 'danger');
                event.preventDefault();
                return;
            }

            const clienteId = clienteIdHidden ? clienteIdHidden.value.trim() : '';
            if (!clienteId) {
                mostrarAlerta('Seleccione un cliente mayorista antes de procesar el pedido.', 'danger');
                event.preventDefault();
                return;
            }

            if (!fechaEntregaInput || !fechaEntregaInput.value) {
                mostrarAlerta('Seleccione la fecha de entrega del pedido.', 'danger');
                event.preventDefault();
                return;
            }

            const total = inputTotal ? parseFloat(inputTotal.value) || 0 : 0;
            if (total <= 0) {
                mostrarAlerta('El total del pedido debe ser mayor a cero.', 'danger');
                event.preventDefault();
                return;
            }

            console.log('Validación exitosa, enviando formulario');
        });
    } else {
        console.error('formVentaMayorista no encontrado');
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarLineaConfeccion);
} else {
    inicializarLineaConfeccion();
}