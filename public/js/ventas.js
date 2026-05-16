// 1. EVENTO: Capturar Id cliente desde el buscador inteligente
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

// 2. EVENTO: Agregar producto seleccionado
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
    
    // Texto envuelto en comillas correctamente
    if (!objetoProducto) {
        return alert("Por favor, seleccione un producto válido de la lista.");
    }

    // Selector exacto con acentos graves ` y atributo data-id
    if (document.querySelector(`input[data-id="${objetoProducto.id}"]`)) {
        return alert('El producto ya se encuentra en el pedido.');
    }

    // Insertar fila con el stock máximo controlado por el atributo "max"
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

// 3. FUNCIONES COMPLEMENTARIAS: Para que el módulo calcule dinero de verdad
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

// Control del despliegue del bloque de cambios (Efectivo)
document.querySelector('select[name="metodo_pago"]').addEventListener('change', function() {
    const seccion = document.getElementById('seccionCambio');
    if (this.value === 'Efectivo') {
        seccion.style.display = 'block';
    } else {
        seccion.style.display = 'none';
        document.getElementById('inputPagaCon').value = '';
        document.getElementById('txtCambio').textContent = '$0.00';
    }
});

document.getElementById('inputPagaCon').addEventListener('input', recalcularCambio);

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

// Preparación de los datos antes de enviar a PHP
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