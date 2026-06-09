document.addEventListener('DOMContentLoaded', function() {
    const clienteInput = document.getElementById('clienteInput');
    const listaClientes = document.getElementById('listaClientes');
    const clienteIdHidden = document.getElementById('cliente_id_hidden');
    const btnToggleNuevoCliente = document.getElementById('btnToggleNuevoCliente');
    const nuevoClienteSection = document.getElementById('nuevoClienteSection');
    const btnAgregar = document.getElementById('btnAgregar');
    const productoInput = document.getElementById('productoInput');
    const productoColor = document.getElementById('productoColor');
    const productoTalla = document.getElementById('productoTalla');
    const carritoBody = document.getElementById('carritoBody');
    const metodoPagoSelect = document.querySelector('select[name="metodo_pago"]');
    const tipoTransferenciaSelect = document.getElementById('tipo_transferencia_select');
    const otraPlataformaInput = document.getElementById('otra_plataforma_input');
    const tipoTransferenciaFinal = document.getElementById('tipo_transferencia_final');
    const inputPagaCon = document.getElementById('inputPagaCon');
    const inputAbono = document.getElementById('inputAbono');
    const ventaForm = document.getElementById('ventaForm');
    const ventaJSONInput = document.getElementById('ventaJSON');
    const inputTotal = document.getElementById('inputTotal');
    const txtTotal = document.getElementById('txtTotal');
    const txtDescuento = document.getElementById('txtDescuento');
    const txtTotalFinal = document.getElementById('txtTotalFinal');
    const txtSaldoPendiente = document.getElementById('txtSaldoPendiente');
    const txtCambio = document.getElementById('txtCambio');
    const seccionCambio = document.getElementById('seccionCambio');
    const seccionTransferencia = document.getElementById('seccionTransferencia');
    const tipoEntregaSelect = document.getElementById('tipo_entrega');
    const seccionDomicilio = document.getElementById('seccionDomicilio');
    const direccionEntregaInput = document.getElementById('direccion_entrega');
    const barrioEntregaInput = document.getElementById('barrio_entrega');
    const ciudadEntregaInput = document.getElementById('ciudad_entrega');
    const observacionesEntregaInput = document.getElementById('observaciones_entrega');

    const formatoCOP = (numero) => {
        return new Intl.NumberFormat('es-CO', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }).format(numero);
    };

    function getDescuentoMayorista(cantidad) {
        if (cantidad >= 20) return 0.10;
        if (cantidad >= 10) return 0.05;
        return 0;
    }

    if (clienteInput && listaClientes && clienteIdHidden) {
        clienteInput.addEventListener('input', function() {
            const val = this.value.trim();
            const opts = listaClientes.options;
            clienteIdHidden.value = '';
            for (let i = 0; i < opts.length; i++) {
                if (opts[i].value === val) {
                    clienteIdHidden.value = opts[i].dataset.id;
                    if (direccionEntregaInput) {
                        direccionEntregaInput.value = opts[i].dataset.direccion || '';
                        barrioEntregaInput.value = opts[i].dataset.barrio || '';
                        ciudadEntregaInput.value = opts[i].dataset.ciudad || 'Sogamoso';
                        observacionesEntregaInput.value = opts[i].dataset.referencia || '';
                    }
                    break;
                }
            }
        });
    }

    if (btnToggleNuevoCliente && nuevoClienteSection && clienteInput && clienteIdHidden) {
        btnToggleNuevoCliente.addEventListener('click', function() {
            const isHidden = nuevoClienteSection.style.display === 'none' || nuevoClienteSection.style.display === '';
            nuevoClienteSection.style.display = isHidden ? 'block' : 'none';
            this.textContent = isHidden ? 'Ocultar formulario de cliente' : 'Crear cliente nuevo';
            if (isHidden) {
                clienteInput.value = '';
                clienteIdHidden.value = '';
                if (direccionEntregaInput) {
                    direccionEntregaInput.value = '';
                    barrioEntregaInput.value = '';
                    ciudadEntregaInput.value = 'Sogamoso';
                    observacionesEntregaInput.value = '';
                }
                const firstNewClient = document.getElementById('nuevo_cliente_nombre_completo');
                if (firstNewClient) firstNewClient.focus();
            }
        });
    }

    if (tipoEntregaSelect && seccionDomicilio) {
        tipoEntregaSelect.addEventListener('change', function() {
            if (this.value === 'Domicilio') {
                seccionDomicilio.style.display = 'block';
                const val = clienteInput.value.trim();
                const opts = listaClientes.options;
                for (let i = 0; i < opts.length; i++) {
                    if (opts[i].value === val) {
                        direccionEntregaInput.value = opts[i].dataset.direccion || '';
                        barrioEntregaInput.value = opts[i].dataset.barrio || '';
                        ciudadEntregaInput.value = opts[i].dataset.ciudad || 'Sogamoso';
                        observacionesEntregaInput.value = opts[i].dataset.referencia || '';
                        break;
                    }
                }
            } else {
                seccionDomicilio.style.display = 'none';
                direccionEntregaInput.value = '';
                barrioEntregaInput.value = '';
                ciudadEntregaInput.value = '';
                observacionesEntregaInput.value = '';
            }
        });
    }

    if (btnAgregar && productoInput && carritoBody) {
        btnAgregar.addEventListener('click', function() {
            const value = productoInput.value.trim();
            const color = productoColor.value.trim() || 'Sin especificar';
            const talla = productoTalla.value.trim() || 'Sin especificar';
            const opts = document.getElementById('listaProductos').options;
            let objetoProducto = null;
            for (let i = 0; i < opts.length; i++) {
                if (opts[i].value.trim() === value) {
                    objetoProducto = {
                        id: parseInt(opts[i].dataset.id),
                        nombre: opts[i].dataset.nombre,
                        precio: parseFloat(opts[i].dataset.precio),
                        stock: parseInt(opts[i].dataset.stock)
                    };
                    break;
                }
            }
            if (!objetoProducto || isNaN(objetoProducto.id)) return alert('Por favor, seleccione un producto válido de la lista.');
            if (objetoProducto.stock <= 0) return alert('El producto seleccionado no cuenta con existencias en inventario.');
            if (document.querySelector(`input[data-id="${objetoProducto.id}"]`)) return alert('El producto ya se encuentra en el pedido.');
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #dee2e6';
            tr.innerHTML = `
                <td style="padding: 10px;">${objetoProducto.nombre}</td>
                <td style="padding: 10px;">${color}</td>
                <td style="padding: 10px;">${talla}</td>
                <td style="padding: 10px;">$${formatoCOP(objetoProducto.precio)}</td>
                <td style="padding: 10px;">
                    <input type="number" class="cant-input" value="10" min="1" max="${objetoProducto.stock}" 
                        data-id="${objetoProducto.id}" 
                        data-color="${color}"
                        data-talla="${talla}"
                        data-precio="${objetoProducto.precio}" 
                        data-stock="${objetoProducto.stock}" 
                        style="width: 60px; padding: 4px;" onchange="validarYCalcular(this)">
                </td>
                <td class="descuento-txt" style="padding: 10px;">0%</td>
                <td class="subtotal-txt" style="padding: 10px;">$${formatoCOP(objetoProducto.precio * 10)}</td>
                <td style="padding: 10px; text-align: center;">
                    <button type="button" onclick="this.closest('tr').remove(); calcularTotales();" style="background:#ef4444; color:white; border:none; padding:4px 8px; border-radius:4px; cursor:pointer;">❌</button>
                </td>
            `;
            carritoBody.appendChild(tr);
            productoInput.value = '';
            productoColor.value = '';
            productoTalla.value = '';
            calcularTotales();
        });
    }

    if (metodoPagoSelect && seccionCambio && seccionTransferencia && inputPagaCon && txtCambio) {
        metodoPagoSelect.addEventListener('change', function() {
            inputPagaCon.value = '';
            txtCambio.textContent = '$' + formatoCOP(0);
            if (this.value === 'Efectivo') {
                seccionCambio.style.display = 'block';
                seccionTransferencia.style.display = 'none';
            } else if (this.value === 'Transferencia') {
                seccionCambio.style.display = 'none';
                seccionTransferencia.style.display = 'block';
                actualizarValorTransferencia();
            } else {
                seccionCambio.style.display = 'none';
                seccionTransferencia.style.display = 'none';
            }
        });
    }

    if (tipoTransferenciaSelect && otraPlataformaInput) {
        tipoTransferenciaSelect.addEventListener('change', function() {
            if (this.value === 'Otro') {
                otraPlataformaInput.style.display = 'block';
                otraPlataformaInput.required = true;
                otraPlataformaInput.value = '';
                otraPlataformaInput.focus();
            } else {
                otraPlataformaInput.style.display = 'none';
                otraPlataformaInput.required = false;
            }
            actualizarValorTransferencia();
        });
        otraPlataformaInput.addEventListener('input', actualizarValorTransferencia);
    }

    if (inputPagaCon) inputPagaCon.addEventListener('input', recalcularCambio);

    if (inputAbono) inputAbono.addEventListener('input', function() {
        recalcularSaldoPendiente();
    });

    window.validarYCalcular = function(input) {
        const cant = parseInt(input.value) || 0;
        const maxStock = parseInt(input.dataset.stock);
        if (cant > maxStock) {
            alert(`Stock insuficiente. El inventario actual de este artículo es de ${maxStock} unidades.`);
            input.value = maxStock;
        }
        if (cant < 1) {
            input.value = 1;
        }
        calcularTotales();
    };

    window.calcularTotales = function() {
        let subtotal = 0;
        let totalDescuento = 0;
        document.querySelectorAll('.cant-input').forEach(input => {
            const cant = parseInt(input.value) || 0;
            const precio = parseFloat(input.dataset.precio) || 0;
            const descuentoRate = getDescuentoMayorista(cant);
            const lineaTotal = cant * precio;
            const descuentoLinea = lineaTotal * descuentoRate;
            const totalLinea = lineaTotal - descuentoLinea;
            const subtotalTd = input.closest('tr').querySelector('.subtotal-txt');
            const descuentoTd = input.closest('tr').querySelector('.descuento-txt');
            if (subtotalTd) subtotalTd.textContent = '$' + formatoCOP(totalLinea);
            if (descuentoTd) descuentoTd.textContent = descuentoRate > 0 ? `${Math.round(descuentoRate * 100)}%` : '0%';
            subtotal += totalLinea;
            totalDescuento += descuentoLinea;
        });
        if (txtTotal) txtTotal.textContent = '$' + formatoCOP(subtotal + totalDescuento);
        if (txtDescuento) txtDescuento.textContent = '$' + formatoCOP(totalDescuento);
        if (txtTotalFinal) txtTotalFinal.textContent = '$' + formatoCOP(subtotal);
        if (inputTotal) inputTotal.value = subtotal.toFixed(2);
        recalcularSaldoPendiente();
    };

    function recalcularSaldoPendiente() {
        if (!inputAbono || !txtSaldoPendiente || !inputTotal) return;
        const totalFinal = parseFloat(inputTotal.value) || 0;
        const abono = parseFloat(inputAbono.value) || 0;
        const saldo = totalFinal - abono;
        if (txtSaldoPendiente) txtSaldoPendiente.textContent = '$' + formatoCOP(Math.max(0, saldo));
        if (inputAbono.value !== '') {
            seccionCambio.style.display = 'none';
        }
    }

    function actualizarValorTransferencia() {
        if (!tipoTransferenciaFinal || !metodoPagoSelect) return;
        const metodo = metodoPagoSelect.value;
        const selectValue = tipoTransferenciaSelect ? tipoTransferenciaSelect.value : '';
        const inputValue = otraPlataformaInput ? otraPlataformaInput.value.trim() : '';
        if (metodo !== 'Transferencia') {
            tipoTransferenciaFinal.value = '';
        } else {
            tipoTransferenciaFinal.value = (selectValue === 'Otro') ? inputValue : selectValue;
        }
    }

    function recalcularCambio() {
        if (!inputPagaCon || !inputTotal || !txtCambio) return;
        const pagaCon = parseFloat(inputPagaCon.value) || 0;
        const total = parseFloat(inputTotal.value) || 0;
        const cambio = pagaCon - total;
        if (pagaCon === 0) {
            txtCambio.textContent = '$' + formatoCOP(0);
            txtCambio.style.color = '#000';
            return;
        }
        if (cambio >= 0) {
            txtCambio.textContent = '$' + formatoCOP(cambio);
            txtCambio.style.color = '#10b981';
        } else {
            txtCambio.textContent = 'Falta dinero';
            txtCambio.style.color = '#ef4444';
        }
    }

    if (ventaForm && ventaJSONInput && inputTotal) {
        ventaForm.addEventListener('submit', function(e) {
            const inputs = document.querySelectorAll('.cant-input');
            if (inputs.length === 0) {
                e.preventDefault();
                return alert('El carrito de compras está vacío.');
            }
            if (!clienteIdHidden.value && (!nuevoClienteSection || nuevoClienteSection.style.display !== 'block')) {
                e.preventDefault();
                clienteInput.focus();
                return alert('Debe seleccionar un cliente de la lista o registrar uno nuevo.');
            }
            if (tipoEntregaSelect && tipoEntregaSelect.value === 'Domicilio') {
                if (!direccionEntregaInput.value.trim()) { e.preventDefault(); direccionEntregaInput.focus(); return alert('Por favor, digite la dirección de entrega para el domicilio.'); }
                if (!barrioEntregaInput.value.trim()) { e.preventDefault(); barrioEntregaInput.focus(); return alert('Por favor, digite el barrio de entrega para el domicilio.'); }
            }
            const datos = [];
            inputs.forEach(input => {
                const cantidad = parseInt(input.value);
                const precioBase = parseFloat(input.dataset.precio);
                const descuentoRate = getDescuentoMayorista(cantidad);
                const precioConDescuento = precioBase - (precioBase * descuentoRate);
                datos.push({
                    id: parseInt(input.dataset.id),
                    cantidad: cantidad,
                    color: input.dataset.color,
                    talla: input.dataset.talla,
                    precio: parseFloat(precioConDescuento.toFixed(2))
                });
            });
            ventaJSONInput.value = JSON.stringify(datos);
        });
    }
});
