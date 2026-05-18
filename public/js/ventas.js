// ZONA 1: DETECTORES DE BÚSQUEDA (Y Buscador Inteligente)
// Evento: Capturar Id cliente desde el buscador inteligente
document.getElementById('clienteInput').addEventListener('input', function() {
    const val = this.value;
    const opts = document.getElementById('listaClientes').options;
    const hiddenInput = document.getElementById('cliente_id_hidden');

    hiddenInput.value = ""; // Resetea por seguridad
    for (let i = 0; i < opts.length; i++) {
        if (opts[i].value === val) {
            hiddenInput.value = opts[i].dataset.id; // Asigna el id real
            break;
        }
    }
});

// ZONA 2: GESTIÓN DEL CARRITO (Agregar, Renderizar y Eliminar Filas)
// Evento: Agregar producto seleccionado
document.getElementById('btnAgregar').addEventListener('click', function() {
    const input = document.getElementById('productoInput');
    const value = input.value;
    const opts = document.getElementById('listaProductos').options;
    
    let objetoProducto = null;
    
    // Buscar coincidencia exacta en el datalist
    for (let i = 0; i < opts.length; i++) {
        if (opts[i].value === value) {
            objetoProducto = {
                id: opts[i].dataset.id,
                nombre: opts[i].dataset.nombre,
                precio: opts[i].dataset.precio,
                stock: opts[i].dataset.stock
            };
            break;
        }
    }
    
    if (!objetoProducto) {
        return alert("Por favor, seleccione un producto válido de la lista.");
    }

    if (document.querySelector(`input[data-id="${objetoProducto.id}"]`)) {
        return alert('El producto ya se encuentra en el pedido.');
    }

    // Realizar Validaciones con la Propiedad "max"
    const tr = document.createElement('tr');
    tr.style.borderBottom = "1px solid #dee2e6";
    tr.innerHTML = `
        <td style="padding: 10px;">${objetoProducto.nombre}</td>
        <td style="padding: 10px;">$${parseFloat(objetoProducto.precio).toFixed(2)}</td>
        <td style="padding: 10px;">
            <input type="number" class="cant-input" value="1" min="1" max="${objetoProducto.stock}" 
            data-id="${objetoProducto.id}" data-precio="${objetoProducto.precio}" style="width: 60px; 
            padding: 4px;" onchange="calcularTotales()">
        </td>
        <td class="subtotal-txt" style="padding: 10px;">$${parseFloat(objetoProducto.precio).toFixed(2)}</td>
        <td style="padding: 10px; text-align: center;">
            <button type="button" onclick="this.closest('tr').remove(); calcularTotales();" style="background:#ef4444; color:white; border:none; padding:4px 8px; border-radius:4px; cursor:pointer;">❌</button>
        </td>
    `;
    
    document.getElementById('carritoBody').appendChild(tr);
    input.value = ''; // Vaciar caja de búsqueda
    calcularTotales();
});

// ZONA 3: MATEMÁTICA INTERNA (Cálculo Automatizado de Totales)
// Funcion: Calcular totales de la venta
function calcularTotales() {
    let granTotal = 0;
    
    document.querySelectorAll('.cant-input').forEach(input => {
        const cant = parseInt(input.value) || 0;
        const precio = parseFloat(input.dataset.precio);
        const subtotal = cant * precio;
        
        input.closest('tr').querySelector('.subtotal-txt').textContent = '$' + subtotal.toFixed(2);
        granTotal += subtotal;
    });

    document.getElementById('txtTotal').textContent = '$' + granTotal.toFixed(2);
    document.getElementById('inputTotal').value = granTotal.toFixed(2);
    
    if (typeof recalcularCambio === 'function') recalcularCambio();
}


// ZONA 4: PASARELA DINÁMICA (Control de Secciones de Pago y Transferencias)
// Evento: Escuchar el selector del método de pago principal
document.querySelector('select[name="metodo_pago"]').addEventListener('change', function() {
    const seccionCambio = document.getElementById('seccionCambio');
    const seccionTransfe = document.getElementById('seccionTransferencia');
    
    // Resetear los campos de vuelto por seguridad al cambiar de método
    document.getElementById('inputPagaCon').value = '';
    document.getElementById('txtCambio').textContent = '$0.00';

    if (this.value === 'Efectivo') {
        seccionCambio.style.display = 'block';   // Muestra la calculadora de vueltas
        seccionTransfe.style.display = 'none';   // Esconde Nequi/Daviplata
    } else if (this.value === 'Transferencia') {
        seccionCambio.style.display = 'none';    // Esconde las vueltas
        seccionTransfe.style.display = 'block';  // MUESTRA Nequi/Daviplata
        actualizarValorTransferencia();          // Sincroniza el valor de inmediato
    } else {
        // Si es Tarjeta u Otro método
        seccionCambio.style.display = 'none';
        seccionTransfe.style.display = 'none';
    }
});

// Escuchar el select de Nequi / Daviplata / Otro ¿Cuál?
document.getElementById('tipo_transferencia_select').addEventListener('change', function() {
    const inputOtro = document.getElementById('otra_plataforma_input');
    
    if (this.value === 'Otro') {
        inputOtro.style.display = 'block';
        inputOtro.required = true;
        inputOtro.value = '';
        inputOtro.focus(); // Pone el cursor adentro automáticamente
    } else {
        inputOtro.style.display = 'none';
        inputOtro.required = false;
    }
    actualizarValorTransferencia();
});

//Escuchar si el vendedor escribe en la casilla "Otro ¿Cuál?"
document.getElementById('otra_plataforma_input').addEventListener('input', actualizarValorTransferencia);

// Funcion inteligente para unificar el dato que viaja a PHP
function actualizarValorTransferencia() {
    const metodo = document.querySelector('select[name="metodo_pago"]').value;
    const selectValue = document.getElementById('tipo_transferencia_select').value;
    const inputValue = document.getElementById('otra_plataforma_input').value.trim();
    const inputFinal = document.getElementById('tipo_transferencia_final');

    if (metodo !== 'Transferencia') {
        inputFinal.value = ''; // Si no es transferencia, se va vacío (NULL en BD)
    } else {
        // Si eligio 'Otro' manda lo que escribio, si no, manda 'Nequi' o 'Daviplata'
        inputFinal.value = (selectValue === 'Otro') ? inputValue : selectValue;
    }
}

// ZONA 5: CALCULADORA EN VIVO (Caja registradora de Efectivo/Cambio)
// Evento: Preparacion y serialización de los datos antes de enviar a PHP
document.getElementById('inputPagaCon').addEventListener('input', recalcularCambio);

// CALCULADORA DE CAMBIO
function recalcularCambio() {
    const inputPaga = document.getElementById('inputPagaCon');
    if (!inputPaga) return;

    const pagaCon = parseFloat(inputPaga.value) || 0;
    const total = parseFloat(document.getElementById('inputTotal').value) || 0;
    
    if (pagaCon === 0) {
        document.getElementById('txtCambio').textContent = '$0.00';
        return;
    }

    const cambio = pagaCon - total;
    const txt = document.getElementById('txtCambio');
    
    if (cambio >= 0) {
        txt.textContent = '$' + cambio.toFixed(2);
        txt.style.color = '#10b981';
    } else {
        txt.textContent = 'Falta dinero';
        txt.style.color = '#ef4444';
    }
}

// ZONA 6: EMPAQUETADO Y ENVÍO (Interceptador del Formulario y Conversión JSON)
// Preparacion de los datos antes de enviar a PHP
document.getElementById('ventaForm').addEventListener('submit', function(e) {
    const inputs = document.querySelectorAll('.cant-input');
    if (inputs.length === 0) {
        e.preventDefault();
        return alert('El carrito está vacío.');
    }

    const datos = [];
    inputs.forEach(input => {
        datos.push({
            producto_id: input.dataset.id,
            cantidad: input.value,
            precio_unitario: input.dataset.precio
        });
    });

    document.getElementById('ventaJSON').value = JSON.stringify(datos);
});