document.addEventListener('DOMContentLoaded', function() {
    // 1. DECLARACIÓN DE VARIABLES
    const elements = {
        clienteInput: document.getElementById('clienteInput'),
        listaClientes: document.getElementById('listaClientes'),
        clienteIdHidden: document.getElementById('cliente_id_hidden'),
        btnToggleNuevoCliente: document.getElementById('btnToggleNuevoCliente'),
        nuevoClienteSection: document.getElementById('nuevoClienteSection'),
        tipoEntregaSelect: document.getElementById('tipo_entrega'),
        seccionDomicilio: document.getElementById('seccionDomicilio'),
        avisoDomicilio: document.getElementById('avisoDomicilio'),
        direccionEntrega: document.getElementById('direccion_entrega'),
        barrioEntrega: document.getElementById('barrio_entrega'),
        ciudadEntrega: document.getElementById('ciudad_entrega'),
        observacionesEntrega: document.getElementById('observaciones_entrega'),
        productoInput: document.getElementById('productoInput'),
        wrapperColor: document.getElementById('wrapperProductoColor'),
        wrapperTalla: document.getElementById('wrapperProductoTalla'),
        productoComentario: document.getElementById('productoComentario'),
        productoCantidad: document.getElementById('productoCantidad'),
        btnAgregar: document.getElementById('btnAgregar'),
        carritoBody: document.getElementById('carritoBody'),
        metodoPagoSelect: document.querySelector('select[name="metodo_pago"]'),
        seccionCambio: document.getElementById('seccionCambio'),
        seccionTransferencia: document.getElementById('seccionTransferencia'),
        seccionTarjeta: document.getElementById('seccionTarjeta'),
        tipoTransferenciaSelect: document.getElementById('tipo_transferencia_select'),
        otraPlataformaInput: document.getElementById('otra_plataforma_input'),
        tipoTransferenciaFinal: document.getElementById('tipo_transferencia_final'),
        referenciaPagoInput: document.getElementById('referencia_pago_input'),
        inputPagaCon: document.getElementById('inputPagaCon'),
        txtTotal: document.getElementById('txtTotal'),
        txtCambio: document.getElementById('txtCambio'),
        inputTotal: document.getElementById('inputTotal'),
        ventaForm: document.getElementById('ventaForm'),
        ventaJSONInput: document.getElementById('ventaJSON')
    };

    // 2. FUNCIONES DE UTILIDAD
    const formatoCOP = (numero) => new Intl.NumberFormat('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(numero);
    const normalizeText = (value) => (value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, ' ').trim().toLowerCase();
    
    const clearWrapper = (wrapper, labelText, placeholder, disabled = true) => {
        const fieldId = wrapper.id === 'wrapperProductoColor' ? 'productoColor' : 'productoTalla';
        wrapper.innerHTML = `<label><strong>${labelText}</strong></label><input type="text" id="${fieldId}" placeholder="${placeholder}" ${disabled ? 'disabled' : ''} style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; background: ${disabled ? 'var(--input-bg)' : 'white'};">`;
    };

    const renderField = (wrapper, items, labelText, placeholder, fieldId) => {
        const useSelect = items.length > 0 && items.length <= 8;
        let html = `<label><strong>${labelText}</strong></label>`;
        if (useSelect) {
            html += `<select id="${fieldId}" style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; background: white;"><option value="">${placeholder}</option>`;
            items.forEach(item => html += `<option value="${item === '' ? 'Sin valor' : item}">${item === '' ? 'Sin valor' : item}</option>`);
            html += `</select>`;
        } else {
            html += `<input type="text" list="list_${fieldId}" id="${fieldId}" placeholder="${placeholder}" style="width:100%; padding: 8px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; background: white;"><datalist id="list_${fieldId}">`;
            items.forEach(item => html += `<option value="${item === '' ? 'Sin valor' : item}"></option>`);
            html += `</datalist>`;
        }
        wrapper.innerHTML = html;
    };

    // 3. LÓGICA DE CLIENTE
    const cargarDatosCliente = () => {
        if (!elements.clienteInput || !elements.listaClientes || !elements.direccionEntrega) return;
        const val = elements.clienteInput.value.trim();
        for (let i = 0; i < elements.listaClientes.options.length; i++) {
            const opt = elements.listaClientes.options[i];
            if (opt.value === val) {
                elements.clienteIdHidden.value = opt.dataset.id;
                elements.direccionEntrega.value = opt.dataset.direccion || '';
                elements.barrioEntrega.value = opt.dataset.barrio || '';
                elements.ciudadEntrega.value = opt.dataset.ciudad || 'Sogamoso';
                elements.observacionesEntrega.value = opt.dataset.referencia || '';
                break;
            }
        }
    };

    if (elements.clienteInput && elements.listaClientes) {
        elements.clienteInput.addEventListener('input', () => { elements.clienteIdHidden.value = ''; cargarDatosCliente(); });
        elements.clienteInput.addEventListener('blur', cargarDatosCliente);
    }

    if (elements.btnToggleNuevoCliente && elements.nuevoClienteSection) {
        elements.btnToggleNuevoCliente.addEventListener('click', function() {
            const isHidden = elements.nuevoClienteSection.style.display === 'none' || elements.nuevoClienteSection.style.display === '';
            elements.nuevoClienteSection.style.display = isHidden ? 'block' : 'none';
            this.textContent = isHidden ? 'Ocultar formulario de cliente' : 'Crear cliente nuevo';
            if (isHidden) {
                elements.clienteInput.value = ''; elements.clienteIdHidden.value = '';
                if (elements.direccionEntrega) { elements.direccionEntrega.value = ''; elements.barrioEntrega.value = ''; elements.ciudadEntrega.value = 'Sogamoso'; elements.observacionesEntrega.value = ''; }
                const firstInput = document.getElementById('nuevo_cliente_nombre_completo');
                if (firstInput) firstInput.focus();
            }
        });
    }

    // 4. LÓGICA DE ENTREGA
    if (elements.tipoEntregaSelect && elements.seccionDomicilio) {
        elements.tipoEntregaSelect.addEventListener('change', function() {
            if (this.value === 'Domicilio') {
                elements.seccionDomicilio.style.display = 'block';
                if (elements.avisoDomicilio) elements.avisoDomicilio.style.display = 'block';
                cargarDatosCliente();
            } else {
                elements.seccionDomicilio.style.display = 'none';
                if (elements.avisoDomicilio) elements.avisoDomicilio.style.display = 'none';
                elements.direccionEntrega.value = ''; elements.barrioEntrega.value = ''; elements.ciudadEntrega.value = ''; elements.observacionesEntrega.value = '';
            }
            calcularTotales();
        });
    }

    // 5. LÓGICA DE PRODUCTOS
    const getSelectedProductOption = () => {
        const productList = document.getElementById('listaProductos');
        if (!productList || !elements.productoInput) return null;
        const inputNormalized = normalizeText(elements.productoInput.value);
        if (!inputNormalized) return null;
        return Array.from(productList.options).find(opt => normalizeText(opt.value) === inputNormalized) || null;
    };

    const fetchProductColors = async (productName) => {
        if (!productName) { clearWrapper(elements.wrapperColor, 'Color:', 'Selecciona primero un producto', true); clearWrapper(elements.wrapperTalla, 'Talla:', 'Selecciona primero un color', true); return; }
        try {
            const res = await fetch(`../controllers/get_variantes_producto.php?nombre=${encodeURIComponent(productName)}`);
            const data = await res.json();
            if (data.colors && data.colors.length > 0) {
                renderField(elements.wrapperColor, data.colors, 'Color:', 'Selecciona color', 'productoColor');
                clearWrapper(elements.wrapperTalla, 'Talla:', 'Selecciona primero un color', true);
                document.getElementById('productoColor').addEventListener('change', onColorChange);
                document.getElementById('productoColor').addEventListener('input', onColorChange);
            } else { clearWrapper(elements.wrapperColor, 'Color:', 'No hay colores', true); clearWrapper(elements.wrapperTalla, 'Talla:', 'No hay tallas', true); }
        } catch (error) { console.error(error); clearWrapper(elements.wrapperColor, 'Color:', 'Error', true); clearWrapper(elements.wrapperTalla, 'Talla:', 'Error', true); }
    };

    const fetchProductTallas = async (productName, colorValue) => {
        if (!productName || !colorValue) { clearWrapper(elements.wrapperTalla, 'Talla:', 'Selecciona primero un color', true); return; }
        try {
            const res = await fetch(`../controllers/get_variantes_producto.php?nombre=${encodeURIComponent(productName)}&color=${encodeURIComponent(colorValue)}`);
            const data = await res.json();
            if (data.tallas && data.tallas.length > 0) renderField(elements.wrapperTalla, data.tallas, 'Talla:', 'Selecciona talla', 'productoTalla');
            else clearWrapper(elements.wrapperTalla, 'Talla:', 'No hay tallas', true);
        } catch (error) { console.error(error); clearWrapper(elements.wrapperTalla, 'Talla:', 'Error', true); }
    };

    const onColorChange = function() {
        const productName = elements.productoInput ? elements.productoInput.value.trim() : '';
        const colorValue = this.value.trim();
        if (productName && colorValue) fetchProductTallas(productName, colorValue);
        else clearWrapper(elements.wrapperTalla, 'Talla:', 'Selecciona primero un color', true);
    };

    if (elements.productoInput) {
        elements.productoInput.addEventListener('input', function() {
            const selectedOption = getSelectedProductOption();
            if (selectedOption) fetchProductColors(selectedOption.value);
            else { clearWrapper(elements.wrapperColor, 'Color:', 'Selecciona primero un producto', true); clearWrapper(elements.wrapperTalla, 'Talla:', 'Selecciona primero un color', true); }
        });
    }

    // 6. LÓGICA DEL CARRITO
    const agregarProductoAlCarrito = async () => {
        const selectedOption = getSelectedProductOption();
        if (!selectedOption) return alert('Selecciona un producto válido de la lista.');
        const productName = selectedOption.value;
        const colorInput = document.getElementById('productoColor');
        const tallaInput = document.getElementById('productoTalla');
        const colorValue = colorInput ? (colorInput.value.trim() || 'Sin color') : 'Sin color';
        const tallaValue = tallaInput ? (tallaInput.value.trim() || 'Sin talla') : 'Sin talla';
        const cantidadSolicitada = parseInt(elements.productoCantidad.value) || 1;
        if (cantidadSolicitada < 1) return alert('La cantidad debe ser al menos 1.');

        let variant;
        try {
            const res = await fetch(`../controllers/get_variantes_producto.php?nombre=${encodeURIComponent(productName)}&color=${encodeURIComponent(colorValue)}&talla=${encodeURIComponent(tallaValue)}`);
            const data = await res.json();
            if (data && data.error) return alert(data.error);
            variant = data.variant;
        } catch (error) { return alert('No se pudo verificar la variante.'); }

        if (!variant) return alert('No se encontró una variante válida.');
        if (variant.stock <= 0) return alert('Sin stock disponible.');

        let cantidadFinal = cantidadSolicitada;
        if (cantidadSolicitada > variant.stock) {
            if (!confirm(`Stock insuficiente. Disponibles: ${variant.stock}. ¿Agregar solo lo disponible?`)) return;
            cantidadFinal = parseInt(variant.stock, 10);
        }
        if (document.querySelector(`input[data-id="${variant.id}"]`)) return alert('El producto ya está en el carrito.');

        const colorText = colorValue === 'Sin color' ? '' : colorValue;
        const tallaText = tallaValue === 'Sin talla' ? '' : tallaValue;
        const comentario = elements.productoComentario ? elements.productoComentario.value.trim() : '';
        const comentarioSafe = comentario.replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

        const tr = document.createElement('tr');
        const subtotal = cantidadFinal * parseFloat(variant.precio);
        tr.innerHTML = `
            <td style="padding: 10px;">${productName}</td><td style="padding: 10px;">${colorText || 'N/D'}</td>
            <td style="padding: 10px;">${tallaText || 'N/D'}</td><td style="padding: 10px;">${comentarioSafe || '-'}</td>
            <td style="padding: 10px;">$${formatoCOP(parseFloat(variant.precio))}</td>
            <td style="padding: 10px;"><input type="number" class="cant-input" value="${cantidadFinal}" min="1" max="${variant.stock}" data-id="${variant.id}" data-precio="${parseFloat(variant.precio)}" data-stock="${variant.stock}" data-color="${colorText}" data-talla="${tallaText}" data-comentario="${comentarioSafe}" style="width: 60px; padding: 4px; text-align: center;" onchange="validarYCalcular(this)"></td>
            <td class="subtotal-txt" style="padding: 10px; text-align: right;">$${formatoCOP(subtotal)}</td>
            <td style="padding: 10px; text-align: center;"><button type="button" onclick="this.closest('tr').remove(); calcularTotales();" style="background:var(--danger); color:white; border:none; padding:4px 8px; border-radius:4px; cursor:pointer;">❌</button></td>`;
        elements.carritoBody.appendChild(tr);
        
        elements.productoInput.value = ''; clearWrapper(elements.wrapperColor, 'Color:', 'Selecciona primero un producto', true);
        clearWrapper(elements.wrapperTalla, 'Talla:', 'Selecciona primero un color', true);
        if (elements.productoComentario) elements.productoComentario.value = '';
        elements.productoCantidad.value = '1';
        calcularTotales();
    };

    if (elements.btnAgregar) elements.btnAgregar.addEventListener('click', agregarProductoAlCarrito);
    if (elements.productoInput) elements.productoInput.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); agregarProductoAlCarrito(); } });

    window.validarYCalcular = function(input) {
        const cant = parseInt(input.value) || 0;
        const maxStock = parseInt(input.dataset.stock);
        if (cant > maxStock) { alert(`Stock insuficiente. Máximo: ${maxStock}`); input.value = maxStock; }
        if (cant < 1) input.value = 1;
        calcularTotales();
    };

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
        if (elements.tipoEntregaSelect && elements.tipoEntregaSelect.value === 'Domicilio') granTotal += 5000;
        if (elements.txtTotal) elements.txtTotal.textContent = '$' + formatoCOP(granTotal);
        if (elements.inputTotal) elements.inputTotal.value = granTotal.toFixed(2);
        recalcularCambio();
    };

    // 7. LÓGICA DE PAGO
    const actualizarValorTransferencia = () => {
        if (!elements.tipoTransferenciaFinal || !elements.metodoPagoSelect) return;
        const metodo = elements.metodoPagoSelect.value;
        const selectValue = elements.tipoTransferenciaSelect ? elements.tipoTransferenciaSelect.value : '';
        const inputValue = elements.otraPlataformaInput ? elements.otraPlataformaInput.value.trim() : '';
        elements.tipoTransferenciaFinal.value = (metodo === 'Transferencia') ? ((selectValue === 'Otro') ? inputValue : selectValue) : '';
    };

    const validarCamposPago = () => {
        const metodo = elements.metodoPagoSelect ? elements.metodoPagoSelect.value : '';
        if (metodo === 'Transferencia') {
            if (elements.seccionTransferencia) elements.seccionTransferencia.style.display = 'block';
            if (elements.seccionTarjeta) elements.seccionTarjeta.style.display = 'none';
            if (elements.referenciaPagoInput) elements.referenciaPagoInput.required = true;
        } else if (metodo === 'Tarjeta') {
            if (elements.seccionTransferencia) elements.seccionTransferencia.style.display = 'none';
            if (elements.seccionTarjeta) elements.seccionTarjeta.style.display = 'block';
            if (elements.referenciaPagoInput) elements.referenciaPagoInput.required = false;
        } else {
            if (elements.seccionTransferencia) elements.seccionTransferencia.style.display = 'none';
            if (elements.seccionTarjeta) elements.seccionTarjeta.style.display = 'none';
            if (elements.referenciaPagoInput) elements.referenciaPagoInput.required = false;
        }
    };

    const recalcularCambio = () => {
        if (!elements.inputPagaCon || !elements.inputTotal || !elements.txtCambio) return;
        const pagaCon = parseFloat(elements.inputPagaCon.value) || 0;
        const total = parseFloat(elements.inputTotal.value) || 0;
        const cambio = pagaCon - total;
        if (pagaCon === 0) { elements.txtCambio.textContent = '$' + formatoCOP(0); elements.txtCambio.style.color = '#000'; return; }
        if (cambio >= 0) { elements.txtCambio.textContent = '$' + formatoCOP(cambio); elements.txtCambio.style.color = 'var(--success)'; }
        else { elements.txtCambio.textContent = 'Falta dinero'; elements.txtCambio.style.color = 'var(--danger)'; }
    };

    if (elements.metodoPagoSelect) {
        elements.metodoPagoSelect.addEventListener('change', function() {
            if (elements.inputPagaCon) elements.inputPagaCon.value = '';
            if (elements.txtCambio) elements.txtCambio.textContent = '$' + formatoCOP(0);
            validarCamposPago();
            if (this.value === 'Efectivo') { if (elements.seccionCambio) elements.seccionCambio.style.display = 'block'; }
            else if (this.value === 'Transferencia') { if (elements.seccionCambio) elements.seccionCambio.style.display = 'none'; actualizarValorTransferencia(); }
            else { if (elements.seccionCambio) elements.seccionCambio.style.display = 'none'; }
        });
    }

    if (elements.tipoTransferenciaSelect && elements.otraPlataformaInput) {
        elements.tipoTransferenciaSelect.addEventListener('change', function() {
            if (this.value === 'Otro') { elements.otraPlataformaInput.style.display = 'block'; elements.otraPlataformaInput.required = true; elements.otraPlataformaInput.value = ''; elements.otraPlataformaInput.focus(); }
            else { elements.otraPlataformaInput.style.display = 'none'; elements.otraPlataformaInput.required = false; }
            actualizarValorTransferencia();
        });
        elements.otraPlataformaInput.addEventListener('input', actualizarValorTransferencia);
    }
    if (elements.inputPagaCon) elements.inputPagaCon.addEventListener('input', recalcularCambio);

           // 8. ENVÍO DEL FORMULARIO
if (elements.ventaForm && elements.ventaJSONInput && elements.inputTotal) {
    elements.ventaForm.addEventListener('submit', function(e) {
        const inputs = document.querySelectorAll('.cant-input');
        // 1. Validar carrito vacío
        if (inputs.length === 0) { 
            e.preventDefault(); 
            return alert('El carrito está vacío.'); 
        }
        // 2. Validar cliente
        if (!elements.clienteIdHidden.value && (!elements.nuevoClienteSection || elements.nuevoClienteSection.style.display !== 'block')) {
            e.preventDefault(); 
            elements.clienteInput.focus(); 
            return alert('Selecciona o registra un cliente.');
        }
        // 3. Validar dirección si es domicilio
        if (elements.tipoEntregaSelect && elements.tipoEntregaSelect.value === 'Domicilio') {
            if (!elements.direccionEntrega.value.trim()) { 
                e.preventDefault(); 
                elements.direccionEntrega.focus(); 
                return alert('Digita la dirección de entrega.'); 
            }
            if (!elements.barrioEntrega.value.trim()) { 
                e.preventDefault(); 
                elements.barrioEntrega.focus(); 
                return alert('Digita el barrio de entrega.'); 
            }
        }
        // 4. Validar pago en efectivo
        if (elements.metodoPagoSelect && elements.metodoPagoSelect.value === 'Efectivo') {
            const pagaConValue = parseFloat(elements.inputPagaCon.value) || 0;
            const totalVentaValue = parseFloat(elements.inputTotal.value) || 0;
            if (pagaConValue < totalVentaValue) { 
                e.preventDefault(); 
                elements.inputPagaCon.focus(); 
                return alert('El monto pagado no puede ser menor al total.'); 
            }
        }
        // 5. Validar referencia de transferencia
        if (elements.metodoPagoSelect && elements.metodoPagoSelect.value === 'Transferencia') {
            if (elements.referenciaPagoInput && !elements.referenciaPagoInput.value.trim()) {
                e.preventDefault(); 
                elements.referenciaPagoInput.focus(); 
                return alert('Debes ingresar el número de referencia de la transferencia.');
            }
            // ✅ Asegurar que tipo_transferencia se guarde
            const selectValue = elements.tipoTransferenciaSelect ? elements.tipoTransferenciaSelect.value : '';
            const inputValue = elements.otraPlataformaInput ? elements.otraPlataformaInput.value.trim() : '';
            const tipoTransferenciaFinal = document.getElementById('tipo_transferencia_final');
            if (tipoTransferenciaFinal) {
                tipoTransferenciaFinal.value = (selectValue === 'Otro') ? inputValue : selectValue;
            }
        }
        // 6. Validar campos de tarjeta
        if (elements.metodoPagoSelect && elements.metodoPagoSelect.value === 'Tarjeta') {
            const ultimos4Input = document.getElementById('ultimos_4_digitos');
            const bancoInput = document.getElementById('banco_emisor');
            if (ultimos4Input && !ultimos4Input.value.trim()) {
                e.preventDefault(); 
                ultimos4Input.focus(); 
                return alert('Debes ingresar los últimos 4 dígitos de la tarjeta.');
            }
            if (bancoInput && !bancoInput.value.trim()) {
                e.preventDefault(); 
                bancoInput.focus(); 
                return alert('Debes ingresar el banco emisor de la tarjeta.');
            }
        }
        // 7. Serializar datos del carrito
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
        elements.ventaJSONInput.value = JSON.stringify(datos);
    });
}
});