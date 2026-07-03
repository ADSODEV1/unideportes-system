document.addEventListener('DOMContentLoaded', function() {
    // --- DECLARACIÓN DE VARIABLES ---
    const clienteInput = document.getElementById('clienteInput');
    const listaClientes = document.getElementById('listaClientes');
    const clienteIdHidden = document.getElementById('cliente_id_hidden');
    const btnToggleNuevoCliente = document.getElementById('btnToggleNuevoCliente');
    const nuevoClienteSection = document.getElementById('nuevoClienteSection');
    const btnAgregar = document.getElementById('btnAgregar');
    const productoInput = document.getElementById('productoInput');
    const wrapperProductoColor = document.getElementById('wrapperProductoColor');
    const wrapperProductoTalla = document.getElementById('wrapperProductoTalla');
    let productoColor = document.getElementById('productoColor');
    let productoTalla = document.getElementById('productoTalla');
    const productoComentario = document.getElementById('productoComentario');
    const productoCantidad = document.getElementById('productoCantidad');
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
            minimumFractionDigits: 2,
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

    function clearWrapper(wrapper, labelText, placeholder, disabled = true) {
        wrapper.innerHTML = `
            <label><strong>${labelText}</strong></label>
            <input type="text" id="${wrapper.id === 'wrapperProductoColor' ? 'productoColor' : 'productoTalla'}" placeholder="${placeholder}" ${disabled ? 'disabled' : ''} style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px; background: ${disabled ? '#f8fafc' : 'white'};">`;
        if (wrapper.id === 'wrapperProductoColor') {
            productoColor = document.getElementById('productoColor');
        } else {
            productoTalla = document.getElementById('productoTalla');
        }
    }

    function renderField(wrapper, items, labelText, placeholder, fieldId) {
        const useSelect = items.length > 0 && items.length <= 8;
        let html = `<label><strong>${labelText}</strong></label>`;

        if (useSelect) {
            html += `<select id="${fieldId}" style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px; background: white;">`;
            html += `<option value="">${placeholder}</option>`;
            items.forEach(item => {
                const safe = item === '' ? 'Sin valor' : item;
                html += `<option value="${safe}">${safe}</option>`;
            });
            html += `</select>`;
        } else {
            html += `<input type="text" list="list_${fieldId}" id="${fieldId}" placeholder="${placeholder}" style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px; background: white;">`;
            html += `<datalist id="list_${fieldId}">`;
            items.forEach(item => {
                const safe = item === '' ? 'Sin valor' : item;
                html += `<option value="${safe}"></option>`;
            });
            html += `</datalist>`;
        }

        wrapper.innerHTML = html;
        if (fieldId === 'productoColor') {
            productoColor = document.getElementById('productoColor');
            if (productoColor) {
                productoColor.addEventListener('input', onColorChange);
                productoColor.addEventListener('change', onColorChange);
            }
        } else {
            productoTalla = document.getElementById('productoTalla');
            if (productoTalla) {
                productoTalla.addEventListener('input', onTallaChange);
                productoTalla.addEventListener('change', onTallaChange);
            }
        }
    }

    function getSelectedProductName() {
        return productoInput ? productoInput.value.trim() : '';
    }

    function normalizeComparableText(value) {
        return (value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();
    }

    function getSelectedProductOption() {
        const productList = document.getElementById('listaProductos');
        if (!productList || !productoInput) return null;

        const inputNormalized = normalizeComparableText(getSelectedProductName());
        if (!inputNormalized) return null;

        return Array.from(productList.options).find(opt => normalizeComparableText(opt.value) === inputNormalized) || null;
    }

    function isSelectedProductValid() {
        return !!getSelectedProductOption();
    }

    async function fetchProductColors(productName) {
        if (!productName) {
            clearWrapper(wrapperProductoColor, 'Color:', 'Selecciona primero un producto', true);
            clearWrapper(wrapperProductoTalla, 'Talla:', 'Selecciona primero un color', true);
            return;
        }

        const url = `../controllers/get_variantes_producto.php?nombre=${encodeURIComponent(productName)}`;
        try {
            const res = await fetch(url);
            const data = await res.json();
            if (data.colors && data.colors.length > 0) {
                renderField(wrapperProductoColor, data.colors, 'Color:', 'Selecciona color', 'productoColor');
                clearWrapper(wrapperProductoTalla, 'Talla:', 'Selecciona primero un color', true);
            } else {
                clearWrapper(wrapperProductoColor, 'Color:', 'No hay colores disponibles', true);
                clearWrapper(wrapperProductoTalla, 'Talla:', 'No hay tallas disponibles', true);
            }
        } catch (error) {
            console.error(error);
            clearWrapper(wrapperProductoColor, 'Color:', 'No hay colores disponibles', true);
            clearWrapper(wrapperProductoTalla, 'Talla:', 'No hay tallas disponibles', true);
        }
    }

    async function fetchProductTallas(productName, colorValue) {
        if (!productName || !colorValue) {
            clearWrapper(wrapperProductoTalla, 'Talla:', 'Selecciona primero un color', true);
            return;
        }

        const url = `../controllers/get_variantes_producto.php?nombre=${encodeURIComponent(productName)}&color=${encodeURIComponent(colorValue)}`;
        try {
            const res = await fetch(url);
            const data = await res.json();
            if (data.tallas && data.tallas.length > 0) {
                renderField(wrapperProductoTalla, data.tallas, 'Talla:', 'Selecciona talla', 'productoTalla');
            } else {
                clearWrapper(wrapperProductoTalla, 'Talla:', 'No hay tallas disponibles', true);
            }
        } catch (error) {
            console.error(error);
            clearWrapper(wrapperProductoTalla, 'Talla:', 'No hay tallas disponibles', true);
        }
    }

    function onColorChange() {
        const productName = getSelectedProductName();
        const colorValue = productoColor ? productoColor.value.trim() : '';
        if (productName && colorValue) {
            fetchProductTallas(productName, colorValue);
        } else {
            clearWrapper(wrapperProductoTalla, 'Talla:', 'Selecciona primero un color', true);
        }
    }

    function onTallaChange() {
        // Intentionally left blank for future behavior changes.
    }

    if (productoInput) {
        productoInput.addEventListener('input', function() {
            const selectedOption = getSelectedProductOption();
            if (selectedOption) {
                fetchProductColors(selectedOption.value);
            } else {
                clearWrapper(wrapperProductoColor, 'Color:', 'Selecciona primero un producto', true);
                clearWrapper(wrapperProductoTalla, 'Talla:', 'Selecciona primero un color', true);
            }
        });
    }

    async function agregarProductoAlCarrito() {
            const selectedOption = getSelectedProductOption();
            if (!selectedOption) return alert('Por favor, selecciona un producto válido de la lista.');
            const productName = selectedOption.value;

            const colorValue = productoColor ? (productoColor.value.trim() || 'Sin color') : 'Sin color';
            const tallaValue = productoTalla ? (productoTalla.value.trim() || 'Sin talla') : 'Sin talla';

            const cantidadSolicitada = parseInt(productoCantidad.value) || 1;
            if (cantidadSolicitada < 1) return alert('La cantidad debe ser al menos 1.');

            let variant;
            try {
                const res = await fetch(`../controllers/get_variantes_producto.php?nombre=${encodeURIComponent(productName)}&color=${encodeURIComponent(colorValue)}&talla=${encodeURIComponent(tallaValue)}`);
                const data = await res.json();
                if (data && data.error) {
                    return alert(data.error);
                }
                variant = data.variant;
            } catch (error) {
                console.error(error);
                return alert('No se pudo verificar la variante seleccionada. Intenta de nuevo.');
            }

            if (!variant) {
                return alert('No se encontró una variante válida en stock para el producto, color y talla seleccionados.');
            }

            if (variant.stock <= 0) {
                return alert('La variante seleccionada no tiene stock disponible.');
            }

            let cantidadFinal = cantidadSolicitada;
            if (cantidadSolicitada > variant.stock) {
                const deseaAjustar = confirm(`Stock insuficiente. Disponibles: ${variant.stock}. ¿Deseas agregar solo la cantidad disponible?`);
                if (!deseaAjustar) {
                    return;
                }
                cantidadFinal = parseInt(variant.stock, 10);
            }
            if (document.querySelector(`input[data-id="${variant.id}"]`)) return alert('El producto ya se encuentra en el pedido.');

            const colorText = colorValue === 'Sin color' ? '' : colorValue;
            const tallaText = tallaValue === 'Sin talla' ? '' : tallaValue;
            const comentario = productoComentario ? productoComentario.value.trim() : '';
            const comentarioSafe = comentario.replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #dee2e6';
            const subtotal = cantidadFinal * parseFloat(variant.precio);
            tr.innerHTML = `
                <td style="padding: 10px;">${productName}</td>
                <td style="padding: 10px;">${colorText || 'N/D'}</td>
                <td style="padding: 10px;">${tallaText || 'N/D'}</td>
                <td style="padding: 10px;">${comentarioSafe || '-'}</td>
                <td style="padding: 10px;">$${formatoCOP(parseFloat(variant.precio))}</td>
                <td style="padding: 10px;">
                    <input type="number" class="cant-input" value="${cantidadFinal}" min="1" max="${variant.stock}"
                        data-id="${variant.id}"
                        data-precio="${parseFloat(variant.precio)}"
                        data-stock="${variant.stock}"
                        data-color="${colorText}"
                        data-talla="${tallaText}"
                        data-comentario="${comentarioSafe}"
                        style="width: 60px; padding: 4px; text-align: center;" onchange="validarYCalcular(this)">
                </td>
                <td class="subtotal-txt" style="padding: 10px; text-align: right;">$${formatoCOP(subtotal)}</td>
                <td style="padding: 10px; text-align: center;">
                    <button type="button" onclick="this.closest('tr').remove(); calcularTotales();" style="background:#ef4444; color:white; border:none; padding:4px 8px; border-radius:4px; cursor:pointer;">❌</button>
                </td>
            `;

            carritoBody.appendChild(tr);
            productoInput.value = '';
            clearWrapper(wrapperProductoColor, 'Color:', 'Selecciona primero un producto', true);
            clearWrapper(wrapperProductoTalla, 'Talla:', 'Selecciona primero un color', true);
            if (productoComentario) productoComentario.value = '';
            productoCantidad.value = '1';
            calcularTotales();
    }

    window.agregarProductoAlCarrito = agregarProductoAlCarrito;

    if (btnAgregar && productoInput && carritoBody) {
        btnAgregar.addEventListener('click', function() {
            agregarProductoAlCarrito();
        });

        productoInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                agregarProductoAlCarrito();
            }
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
                    precio: parseFloat(input.dataset.precio),
                    color: input.dataset.color || '',
                    talla: input.dataset.talla || '',
                    comentario: input.dataset.comentario || ''
                });
            });
            ventaJSONInput.value = JSON.stringify(datos);
        });
    }
});