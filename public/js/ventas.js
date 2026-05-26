document.addEventListener('DOMContentLoaded', function() {
    // --- DECLARACIÓN DE VARIABLES ---
    const clienteInput = document.getElementById('clienteInput');
    const listaClientes = document.getElementById('listaClientes');
    const clienteIdHidden = document.getElementById('cliente_id_hidden');
    const btnToggleNuevoCliente = document.getElementById('btnToggleNuevoCliente');
    const nuevoClienteSection = document.getElementById('nuevoClienteSection');
    const btnAgregar = document.getElementById('btnAgregar');
    const productoInput = document.getElementById('productoInput');
    const carritoBody = document.getElementById('carritoBody');
    const metodoPagoSelect = document.querySelector('select[name="metodo_pago"]');
    const tipoTransferenciaSelect = document.getElementById('tipo_transferencia_select');
    const otraPlataformaInput = document.getElementById('otra_plataforma_input');
    const tipoTransferenciaFinal = document.getElementById('tipo_transferencia_final');
    const inputPagaCon = document.getElementById('inputPagaCon');
    const ventaForm = document.getElementById('ventaForm');
    const ventaJSONInput = document.getElementById('ventaJSON');
    const inputTotal = document.getElementById('inputTotal');
    const txtTotal = document.getElementById('txtTotal');
    const txtCambio = document.getElementById('txtCambio');
    const seccionCambio = document.getElementById('seccionCambio');
    const seccionTransferencia = document.getElementById('seccionTransferencia');
    const tipoEntregaSelect = document.getElementById('tipo_entrega');
    const seccionDomicilio = document.getElementById('seccionDomicilio');
    const direccionEntregaInput = document.getElementById('direccion_entrega');
    const barrioEntregaInput = document.getElementById('barrio_entrega');
    const ciudadEntregaInput = document.getElementById('ciudad_entrega');
    const observacionesEntregaInput = document.getElementById('observaciones_entrega');

    // --- FUNCIÓN DE FORMATO MONEDA COLOMBIA (COP) ---
    const formatoCOP = (numero) => {
        return new Intl.NumberFormat('es-CO', {
            minimumFractionDigits: 0, 
            maximumFractionDigits: 2
        }).format(numero);
    };

    // --- SELECCIÓN DE CLIENTE DESDE DATALIST ---
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
                        // SE CORRIGIÓ: Sincronización exacta con el data-attribute 'data-referencia' de la vista
                        observacionesEntregaInput.value = opts[i].dataset.referencia || '';
                    }
                    break;
                }
            }
        });
    }

    // --- DESPLEGAR / OCULTAR FORMULARIO NUEVO CLIENTE ---
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

    // --- MANEJO DE SECCIÓN DOMICILIO ---
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
                        // SE CORRIGIÓ: Sincronización exacta con 'data-referencia'
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

    // --- AGREGAR PRODUCTOS AL CARRITO DE COMPRAS ---
    if (btnAgregar && productoInput && carritoBody) {
        btnAgregar.addEventListener('click', function() {
            const value = productoInput.value.trim();
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
                <td style="padding: 10px;">$${formatoCOP(objetoProducto.precio)}</td>
                <td style="padding: 10px;">
                    <input type="number" class="cant-input" value="1" min="1" max="${objetoProducto.stock}" 
                    data-id="${objetoProducto.id}" data-precio="${objetoProducto.precio}" data-stock="${objetoProducto.stock}" style="width: 60px; padding: 4px;" onchange="validarYCalcular(this)">
                </td>
                <td class="subtotal-txt" style="padding: 10px;">$${formatoCOP(objetoProducto.precio)}</td>
                <td style="padding: 10px; text-align: center;">
                    <button type="button" onclick="this.closest('tr').remove(); calcularTotales();" style="background:#ef4444; color:white; border:none; padding:4px 8px; border-radius:4px; cursor:pointer;">❌</button>
                </td>
            `;
            carritoBody.appendChild(tr);
            productoInput.value = '';
            calcularTotales();
        });
    }

    // --- INTERFAZ DINÁMICA DE MÉTODOS DE PAGO ---
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

    // --- FUNCIÓN INTERNA PARA EVITAR SOBREPASAR STOCK MANUALMENTE ---
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

    // --- CÁLCULO GENERAL DE VALORES ---
    window.calcularTotales = function() {
        let granTotal = 0;
        document.querySelectorAll('.cant-input').forEach(input => {
            const cant = parseInt(input.value) || 0;
            const precio = parseFloat(input.dataset.precio) || 0;
            const subtotal = cant * precio;
            const subtotalTd = input.closest('tr').querySelector('.subtotal-txt');
            if (subtotalTd) subtotalTd.textContent = '$' + formatoCOP(subtotal);
            granTotal += subtotal;
        });

        if (txtTotal) txtTotal.textContent = '$' + formatoCOP(granTotal);
        if (inputTotal) inputTotal.value = granTotal.toFixed(2);
        recalcularCambio();
    };

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

    // --- SUBMIT DEL FORMULARIO CON CONVENIOS JSON ---
    if (ventaForm && ventaJSONInput && inputTotal) {
        ventaForm.addEventListener('submit', function(e) {
            const inputs = document.querySelectorAll('.cant-input');
            if (inputs.length === 0) { 
                e.preventDefault(); 
                return alert('El carrito de compras está vacío.'); 
            }

            // Validar que se asigne un cliente o se marque la creación de uno nuevo
            if (!clienteIdHidden.value && (!nuevoClienteSection || nuevoClienteSection.style.display !== 'block')) {
                e.preventDefault();
                clienteInput.focus();
                return alert('Debe seleccionar un cliente de la lista o registrar uno nuevo.');
            }

            if (tipoEntregaSelect && tipoEntregaSelect.value === 'Domicilio') {
                if (!direccionEntregaInput.value.trim()) { e.preventDefault(); direccionEntregaInput.focus(); return alert('Por favor, digite la dirección de entrega para el domicilio.'); }
                if (!barrioEntregaInput.value.trim()) { e.preventDefault(); barrioEntregaInput.focus(); return alert('Por favor, digite el barrio de entrega para el domicilio.'); }
            }

            // SE AÑADIÓ: Bloqueo de envío si el dinero en efectivo es insuficiente
            if (metodoPagoSelect && metodoPagoSelect.value === 'Efectivo') {
                const pagaConValue = parseFloat(inputPagaCon.value) || 0;
                const totalVentaValue = parseFloat(inputTotal.value) || 0;
                if (pagaConValue < totalVentaValue) {
                    e.preventDefault();
                    inputPagaCon.focus();
                    return alert('El monto con el que paga el cliente no puede ser menor al total de la venta.');
                }
            }

            const datos = [];
            inputs.forEach(input => {
                datos.push({
                    id: parseInt(input.dataset.id),
                    cantidad: parseInt(input.value),
                    precio: parseFloat(input.dataset.precio)
                });
            });
            ventaJSONInput.value = JSON.stringify(datos);
        });
    }
});