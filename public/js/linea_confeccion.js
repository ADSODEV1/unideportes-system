// public/js/linea_confeccion.js v2 — Catálogo obligatorio + piso por rol + 50% mínimo
console.log('linea_confeccion.js v2 cargado');

function inicializarLineaConfeccion() {
    console.log('Inicializando linea_confeccion.js v2');

    // ✅ CATÁLOGO: reemplaza productoInput/productoPrecio
    const selectPrenda = document.getElementById('selectTipoPrenda');
    const inputPrecioNeg = document.getElementById('inputPrecioNegociado');
    const pisoInfo = document.getElementById('pisoInfo');

    const btnAgregar = document.getElementById('btnAgregar');
    const productoColor = document.getElementById('productoColor');
    const productoTalla = document.getElementById('productoTalla');
    const productoCantidad = document.getElementById('productoCantidad');
    const carritoBody = document.getElementById('carritoBody');
    const inputTotal = document.getElementById('inputTotal');
    const inputAbono = document.getElementById('inputAbono');
    const infoAbonoMin = document.getElementById('infoAbonoMin');
    const ventaJSON = document.getElementById('ventaJSON');
    const formVentaMayorista = document.getElementById('formVentaMayorista');
    const mensajeAlerta = document.getElementById('mensajeAlerta');
    const txtTotalFinal = document.getElementById('txtTotalFinal');
    const txtSaldoPendiente = document.getElementById('txtSaldoPendiente');
    const clienteInput = document.getElementById('clienteInput');
    const listaClientes = document.getElementById('listaClientes');
    const clienteIdHidden = document.getElementById('cliente_id_hidden');
    const btnToggleNuevoCliente = document.getElementById('btnToggleNuevoCliente');
    const nuevoClienteSection = document.getElementById('nuevoClienteSection');
    const fechaEntregaInput = document.getElementById('fecha_entrega');
    const tipoEntrega = document.getElementById('tipo_entrega');
    const direccionEntrega = document.getElementById('direccion_entrega');
    const barrioEntrega = document.getElementById('barrio_entrega');
    const ciudadEntrega = document.getElementById('ciudad_entrega');
    const seccionDomicilio = document.getElementById('seccionDomicilio');
    const observacionesEntregaInput = document.getElementById('observaciones_entrega');
    const carritoStatus = document.getElementById('carritoStatus');
    const modalEditar = document.getElementById('modalEditarItem');
    const editNombre = document.getElementById('editNombre');
    const editColor = document.getElementById('editColor');
    const editTalla = document.getElementById('editTalla');
    const editPrecio = document.getElementById('editPrecio');
    const editCantidad = document.getElementById('editCantidad');
    const btnGuardar = document.getElementById('btnGuardarEdicion');
    const btnCancelar = document.getElementById('btnCancelarEdicion');

    let carrito = [];
    let editandoIndex = -1;
    const fmt = n => '$' + n.toLocaleString('co-CO', {minimumFractionDigits:0, maximumFractionDigits:0});

    function mostrarAlerta(msg, tipo = 'info') {
        if (mensajeAlerta) {
            mensajeAlerta.innerText = msg;
            mensajeAlerta.style.display = 'block';
            mensajeAlerta.style.backgroundColor = tipo === 'danger' ? '#fee2e2' : (tipo === 'success' ? '#d1fae5' : '#fef3c7');
            mensajeAlerta.style.color = tipo === 'danger' ? '#991b1b' : (tipo === 'success' ? '#065f46' : '#92400e');
            mensajeAlerta.style.border = `1px solid ${tipo === 'danger' ? '#fca5a5' : (tipo === 'success' ? '#6ee7b7' : '#fde68a')}`;
        } else { alert(msg); }
    }
    function ocultarAlerta() { if (mensajeAlerta) mensajeAlerta.style.display = 'none'; }

    // ✅ CATÁLOGO: lee precio base y piso según rol desde el <select>
    function infoPrenda() {
        if (!selectPrenda || selectPrenda.selectedIndex <= 0) return null;
        const opt = selectPrenda.selectedOptions[0];
        const base = parseFloat(opt.dataset.precio) || 0;
        const maxDesc = parseFloat(selectPrenda.dataset.maxDesc || '0.05');
        return { id: opt.value, nombre: (opt.dataset.nombre || opt.textContent.trim()), base, piso: base * (1 - maxDesc) };
    }
    if (selectPrenda) {
        selectPrenda.addEventListener('change', () => {
            const i = infoPrenda();
            if (i) {
                if (inputPrecioNeg) inputPrecioNeg.value = i.base;
                if (pisoInfo) pisoInfo.textContent = `Precio mínimo para tu rol: ${fmt(i.piso)}`;
            } else {
                if (inputPrecioNeg) inputPrecioNeg.value = '';
                if (pisoInfo) pisoInfo.textContent = '';
            }
        });
    }

    // ✅ MODAL EDICIÓN: precio y nombre CONGELADOS
    function abrirModalEdicion(index) {
        if (index < 0 || index >= carrito.length) return;
        editandoIndex = index;
        const item = carrito[index];
        if (editNombre) { editNombre.value = item.nombre; editNombre.readOnly = true; }
        if (editColor) editColor.value = item.color;
        if (editTalla) editTalla.value = item.talla;
        if (editPrecio) { editPrecio.value = item.precio_unitario; editPrecio.readOnly = true; }
        if (editCantidad) editCantidad.value = item.cantidad;
        if (modalEditar) modalEditar.style.display = 'flex';
    }
    function cerrarModalEdicion() { editandoIndex = -1; if (modalEditar) modalEditar.style.display = 'none'; }

    if (btnGuardar) {
        btnGuardar.addEventListener('click', () => {
            if (editandoIndex < 0 || editandoIndex >= carrito.length) return;
            const cantidad = parseInt(editCantidad?.value || '1', 10);
            if (cantidad <= 0) { mostrarAlerta('La cantidad debe ser al menos 1', 'danger'); return; }
            // ❌ Ya NO se aplican nombre ni precio (están congelados)
            carrito[editandoIndex].color = (editColor?.value || '').trim() || 'Sin color';
            carrito[editandoIndex].talla = (editTalla?.value || '').trim() || 'Sin talla';
            carrito[editandoIndex].cantidad = cantidad;
            cerrarModalEdicion();
            actualizarCarritoUI();
            mostrarAlerta('✓ Producto actualizado', 'success');
            setTimeout(ocultarAlerta, 1400);
        });
    }
    if (btnCancelar) btnCancelar.addEventListener('click', cerrarModalEdicion);
    if (modalEditar) modalEditar.addEventListener('click', (e) => { if (e.target === modalEditar) cerrarModalEdicion(); });
    window.__editarDelCarrito = (index) => abrirModalEdicion(index);

    // ✅ CARRITO: SIN descuentos automáticos (total = suma de negociados)
    function actualizarCarritoUI() {
        if (!carritoBody) return;
        carritoBody.innerHTML = '';
        let subtotal = 0;
        carrito.forEach((item, index) => {
            subtotal += item.precio_unitario * item.cantidad;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td style="padding:10px;">${item.nombre}</td>
                <td style="padding:10px;">${item.color}</td>
                <td style="padding:10px;">${item.talla}</td>
                <td style="padding:10px;">${fmt(item.precio_unitario)}</td>
                <td style="padding:10px; text-align:center;">${item.cantidad}</td>
                <td style="padding:10px; text-align:center;">
                    <button type="button" onclick="window.__editarDelCarrito(${index})" style="background:none; border:none; cursor:pointer; font-size:1rem;">✏️</button>
                    <button type="button" onclick="window.__quitarDelCarrito(${index})" style="background:none; border:none; cursor:pointer; font-size:1rem;">❌</button>
                </td>`;
            carritoBody.appendChild(row);
        });
        const totalFinal = subtotal;
        if (txtTotalFinal) txtTotalFinal.innerText = fmt(totalFinal);
        if (inputTotal) inputTotal.value = totalFinal.toFixed(2);
        if (ventaJSON) ventaJSON.value = JSON.stringify(carrito);
        if (carritoStatus) carritoStatus.innerText = `Carrito: ${carrito.length} producto(s).`;
        if (infoAbonoMin) infoAbonoMin.textContent = `Abono mínimo (50%): ${fmt(totalFinal * 0.5)}`;
        recalcularSaldo();
    }

    function recalcularSaldo() {
        if (!inputTotal || !inputAbono || !txtSaldoPendiente) return;
        const total = parseFloat(inputTotal.value) || 0;
        const abono = parseFloat(inputAbono.value) || 0;
        const saldo = Math.max(0, total - abono);
        txtSaldoPendiente.innerText = fmt(saldo);
        // Feedback visual del abono
        if (infoAbonoMin && total > 0) {
            const min = total * 0.5;
            if (abono > 0 && abono < min) {
                inputAbono.style.borderColor = '#ef4444';
                inputAbono.style.backgroundColor = '#fef2f2';
                infoAbonoMin.style.color = '#ef4444';
                infoAbonoMin.textContent = `⚠️ El abono debe ser mínimo el 50% (${fmt(min)})`;
            } else {
                inputAbono.style.borderColor = '#cbd5e1';
                inputAbono.style.backgroundColor = '';
                infoAbonoMin.style.color = '#d97706';
                infoAbonoMin.textContent = `Abono mínimo (50%): ${fmt(min)}`;
            }
        }
    }

    window.__quitarDelCarrito = (index) => {
        if (index >= 0 && index < carrito.length) { carrito.splice(index, 1); actualizarCarritoUI(); }
    };

    // ✅ AGREGAR: SOLO desde catálogo y con precio >= piso
    if (btnAgregar) {
        btnAgregar.addEventListener('click', (event) => {
            event.preventDefault();
            const i = infoPrenda();
            if (!i) { mostrarAlerta('Seleccione un tipo de prenda del catálogo.', 'danger'); return; }
            const neg = inputPrecioNeg ? parseFloat(inputPrecioNeg.value) || 0 : 0;
            const cantidad = productoCantidad ? parseInt(productoCantidad.value, 10) || 0 : 0;
            if (neg < i.piso) {
                mostrarAlerta(`Precio bajo el mínimo permitido: ${fmt(i.piso)}`, 'danger');
                return;
            }
            if (cantidad <= 0) { mostrarAlerta('Ingresa una cantidad válida', 'danger'); return; }
            const color = productoColor ? (productoColor.value || '').trim() : 'Sin color';
            const talla = productoTalla ? (productoTalla.value || '').trim() : 'Sin talla';

            const igual = carrito.find(x => x.tipo_prenda_id == i.id && x.precio_unitario === neg && x.color === color && x.talla === talla);
            if (igual) igual.cantidad += cantidad;
            else carrito.push({
                tipo_prenda_id: i.id, nombre: i.nombre, color, talla,
                precio_unitario: neg, precio_base_ref: i.base, cantidad, comentario_vendedor: ''
            });

            if (inputPrecioNeg) inputPrecioNeg.value = '';
            if (productoColor) productoColor.value = '';
            if (productoTalla) productoTalla.value = '';
            if (productoCantidad) productoCantidad.value = '1';
            mostrarAlerta('✓ Prenda agregada', 'success');
            setTimeout(ocultarAlerta, 1400);
            actualizarCarritoUI();
        });
    }

    // Cliente / cliente nuevo / domicilio (SIN cambios)
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
                const first = document.getElementById('nuevo_cliente_nombre_completo');
                if (first) first.focus();
            }
        });
    }
    if (tipoEntrega && seccionDomicilio) {
        tipoEntrega.addEventListener('change', function() {
            if (this.value === 'Domicilio') seccionDomicilio.style.display = 'block';
            else {
                seccionDomicilio.style.display = 'none';
                if (direccionEntrega) direccionEntrega.value = '';
                if (barrioEntrega) barrioEntrega.value = '';
                if (ciudadEntrega) ciudadEntrega.value = '';
                if (observacionesEntregaInput) observacionesEntregaInput.value = '';
            }
        });
    }
    if (inputAbono) inputAbono.addEventListener('input', recalcularSaldo);

    // ✅ ENVÍO: validaciones + ABONO ≥ 50% obligatorio
    if (formVentaMayorista) {
        formVentaMayorista.addEventListener('submit', (event) => {
            if (carrito.length === 0) { mostrarAlerta('Agregue al menos un producto al pedido.', 'danger'); event.preventDefault(); return; }
            const clienteId = clienteIdHidden ? clienteIdHidden.value.trim() : '';
            if (!clienteId) { mostrarAlerta('Seleccione o cree un cliente mayorista.', 'danger'); event.preventDefault(); return; }
            if (!fechaEntregaInput || !fechaEntregaInput.value) { mostrarAlerta('Seleccione la fecha de entrega.', 'danger'); event.preventDefault(); return; }
            const total = inputTotal ? parseFloat(inputTotal.value) || 0 : 0;
            if (total <= 0) { mostrarAlerta('El total del pedido debe ser mayor a cero.', 'danger'); event.preventDefault(); return; }
            const abono = inputAbono ? parseFloat(inputAbono.value) || 0 : 0;
            if (abono < total * 0.5) {
                mostrarAlerta(`⚠️ Abono mínimo 50% del total: ${fmt(total*0.5)} (lleva ${fmt(abono)}).`, 'danger');
                event.preventDefault();
                inputAbono.focus();
                return;
            }
        });
    }

    actualizarCarritoUI();
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', inicializarLineaConfeccion);
else inicializarLineaConfeccion();