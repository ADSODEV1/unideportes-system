// public/js/linea_confeccion.js

document.addEventListener("DOMContentLoaded", () => {
    // Elementos de Clientes y Opciones de Formulario
    const clienteInput = document.getElementById("clienteInput");
    const clienteIdHidden = document.getElementById("cliente_id_hidden");
    const listaClientes = document.getElementById("listaClientes");
    const btnToggleNuevoCliente = document.getElementById("btnToggleNuevoCliente");
    const nuevoClienteSection = document.getElementById("nuevoClienteSection");
    
    const metodoPago = document.getElementById("metodo_pago");
    const seccionTransferencia = document.getElementById("seccionTransferencia");
    const tipoTransferenciaSelect = document.getElementById("tipo_transferencia_select");
    const otraPlataformaInput = document.getElementById("otra_plataforma_input");
    const tipoTransferenciaFinal = document.getElementById("tipo_transferencia_final");
    
    const tipoEntrega = document.getElementById("tipo_entrega");
    const seccionDomicilio = document.getElementById("seccionDomicilio");
    const direccionEntrega = document.getElementById("direccion_entrega");
    const barrioEntrega = document.getElementById("barrio_entrega");
    const ciudadEntrega = document.getElementById("ciudad_entrega");

    // Elementos del Buscador de Productos y Carrito
    const productoInput = document.getElementById("productoInput");
    const listaProductos = document.getElementById("listaProductos");
    const productoColor = document.getElementById("productoColor");
    const productoTalla = document.getElementById("productoTalla");
    const btnAgregar = document.getElementById("btnAgregar");
    const carritoBody = document.getElementById("carritoBody");
    const ventaJSON = document.getElementById("ventaJSON");
    const inputTotal = document.getElementById("inputTotal");
    const inputAbono = document.getElementById("inputAbono");

    let carrito = [];

    let productoSeleccionadoData = {
        id: "",
        precio: 0,
        stock: 0
    };

    // ==========================================
    // CONTROLADORES DE INTERFAZ DE CLIENTES Y PAGO
    // ==========================================

    clienteInput.addEventListener("input", () => {
        const val = clienteInput.value;
        const opciones = listaClientes.options;
        let encontrado = false;

        for (let i = 0; i < opciones.length; i++) {
            if (opciones[i].value === val) {
                clienteIdHidden.value = opciones[i].getAttribute("data-id");
                direccionEntrega.value = opciones[i].getAttribute("data-direccion") || "";
                barrioEntrega.value = opciones[i].getAttribute("data-barrio") || "";
                ciudadEntrega.value = opciones[i].getAttribute("data-ciudad") || "Sogamoso";
                encontrado = true;
                break;
            }
        }
        if (!encontrado) clienteIdHidden.value = "";
    });

    btnToggleNuevoCliente.addEventListener("click", () => {
        if (nuevoClienteSection.style.display === "none" || nuevoClienteSection.style.display === "") {
            nuevoClienteSection.style.display = "block";
            btnToggleNuevoCliente.innerText = "Usar cliente existente";
            clienteInput.value = "";
            clienteInput.disabled = true;
            clienteIdHidden.value = "NUEVO";
        } else {
            nuevoClienteSection.style.display = "none";
            btnToggleNuevoCliente.innerText = "Crear cliente nuevo";
            clienteInput.disabled = false;
            clienteIdHidden.value = "";
        }
    });

    metodoPago.addEventListener("change", () => {
        if (metodoPago.value === "Transferencia") {
            seccionTransferencia.style.display = "block";
            actualizarTipoTransferencia();
        } else {
            seccionTransferencia.style.display = "none";
            tipoTransferenciaFinal.value = "";
        }
    });

    tipoTransferenciaSelect.addEventListener("change", actualizarTipoTransferencia);
    otraPlataformaInput.addEventListener("input", actualizarTipoTransferencia);

    function actualizarTipoTransferencia() {
        if (tipoTransferenciaSelect.value === "Otro") {
            otraPlataformaInput.style.display = "block";
            tipoTransferenciaFinal.value = otraPlataformaInput.value;
        } else {
            otraPlataformaInput.style.display = "none";
            tipoTransferenciaFinal.value = tipoTransferenciaSelect.value;
        }
    }

    tipoEntrega.addEventListener("change", () => {
        if (tipoEntrega.value === "Domicilio") {
            seccionDomicilio.style.display = "block";
        } else {
            seccionDomicilio.style.display = "none";
        }
    });


    // ==========================================
    // LÓGICA DEL BUSCADOR Y ENTRADA DE DATOS
    // ==========================================

    // Al seleccionar el producto base habilitamos los campos para edición personalizada
    productoInput.addEventListener("input", () => {
        const val = productoInput.value;
        const opciones = listaProductos.options;
        let encontrado = false;

        for (let i = 0; i < opciones.length; i++) {
            if (opciones[i].value === val) {
                encontrado = true;
                
                productoSeleccionadoData.id = opciones[i].getAttribute("data-id");
                productoSeleccionadoData.precio = parseFloat(opciones[i].getAttribute("data-precio")) || 0;
                productoSeleccionadoData.stock = parseInt(opciones[i].getAttribute("data-stock")) || 0;

                // ACTIVAR los campos para que confección digite libremente
                productoColor.disabled = false;
                productoTalla.disabled = false;
                productoColor.style.background = "#fff";
                productoTalla.style.background = "#fff";
                
                productoColor.value = ""; // Vacío para que escriban el color deseado
                productoTalla.value = ""; // Vacío para que escriban la talla deseada (Ej: M, L, 16)
                productoColor.placeholder = "Ej: Azul Rey / Negro";
                productoTalla.placeholder = "Ej: XL o Talla 14";
                break;
            }
        }

        if (!encontrado) {
            desactivarCamposProducto();
        }
    });

    function desactivarCamposProducto() {
        productoColor.disabled = true;
        productoTalla.disabled = true;
        productoColor.style.background = "#f8fafc";
        productoTalla.style.background = "#f8fafc";
        productoColor.value = "";
        productoTalla.value = "";
        productoColor.placeholder = "Selecciona producto";
        productoTalla.placeholder = "Selecciona color";
        productoSeleccionadoData = { id: "", precio: 0, stock: 0 };
    }

    // Al darle Clic a "+ Añadir"
    btnAgregar.addEventListener("click", () => {
        const nombre = productoInput.value.trim();
        const color = productoColor.value.trim() || "Por definir";
        const talla = productoTalla.value.trim() || "Estándar";

        if (!nombre || !productoSeleccionadoData.id) {
            mostrarMensajeAlerta("Por favor, selecciona un producto base de la lista.", "danger");
            return;
        }

        const id = productoSeleccionadoData.id;
        const precio = productoSeleccionadoData.precio;
        
        const comentarioTextarea = document.getElementById("observaciones_pedido");
        const comentario = comentarioTextarea ? comentarioTextarea.value.trim() : "";

        // Para órdenes de taller de confección, permitimos agrupar si repiten Producto + Color + Talla
        const itemExistente = carrito.find(item => item.producto_id === id && item.color === color && item.talla === talla);

        if (itemExistente) {
            itemExistente.cantidad += 1;
        } else {
            carrito.push({
                producto_id: id,
                nombre: nombre,
                color: color,
                talla: talla, // Guarda la talla escrita manualmente
                precio_unitario: precio,
                cantidad: 1,
                comentario_vendedor: comentario
            });
        }

        // Limpiar buscador
        productoInput.value = "";
        desactivarCamposProducto();
        ocultarMensajeAlerta();
        actualizarRenderCarrito();
    });

    // ==========================================
    // RENDER DEL CARRITO Y CONTROLES DE CANTIDAD
    // ==========================================

    function actualizarRenderCarrito() {
        carritoBody.innerHTML = "";
        let subtotalAcumulado = 0;
        
        // Calcular total unidades confeccionadas para el escalafón de descuentos
        let totalUnidades = carrito.reduce((acc, item) => acc + item.cantidad, 0);

        let factorDescuento = 0;
        if (totalUnidades >= 20) factorDescuento = 0.10;
        else if (totalUnidades >= 10) factorDescuento = 0.05;

        carrito.forEach((item, index) => {
            let descItem = item.precio_unitario * factorDescuento;
            let subtotalItem = (item.precio_unitario - descItem) * item.cantidad;
            subtotalAcumulado += item.precio_unitario * item.cantidad;

            carritoBody.innerHTML += `
                <tr>
                    <td style="padding:10px;">${item.nombre}</td>
                    <td style="padding:10px; font-weight: 500;">${item.color}</td>
                    <td style="padding:10px; font-weight: bold; color: #1e293b;">${item.talla}</td>
                    <td style="padding:10px;">$${item.precio_unitario.toLocaleString('co-CO', {minimumFractionDigits: 2})}</td>
                    <td style="padding:10px; text-align:center;">
                        <div style="display: flex; gap: 5px; justify-content: center; align-items: center;">
                            <button type="button" onclick="cambiarCantidad(${index}, -1)" style="padding: 2px 8px; background: #cbd5e1; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">-</button>
                            <span style="min-width: 25px; font-weight: bold;">${item.cantidad}</span>
                            <button type="button" onclick="cambiarCantidad(${index}, 1)" style="padding: 2px 8px; background: #cbd5e1; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">+</button>
                        </div>
                    </td>
                    <td style="padding:10px; color:#ca8a04;">$${(descItem * item.cantidad).toLocaleString('co-CO', {minimumFractionDigits: 2})}</td>
                    <td style="padding:10px; font-weight:bold;">$${subtotalItem.toLocaleString('co-CO', {minimumFractionDigits: 2})}</td>
                    <td style="padding:10px; text-align:center;">
                        <button type="button" onclick="quitarDelCarrito(${index})" style="background:none; border:none; cursor:pointer; font-size:1.1rem;">❌</button>
                    </td>
                </tr>
            `;
        });

        let descuentoTotal = subtotalAcumulado * factorDescuento;
        let totalFinal = subtotalAcumulado - descuentoTotal;

        document.getElementById("txtTotal").innerText = `$${subtotalAcumulado.toLocaleString('co-CO', {minimumFractionDigits: 2})}`;
        document.getElementById("txtDescuento").innerText = `$${descuentoTotal.toLocaleString('co-CO', {minimumFractionDigits: 2})}`;
        document.getElementById("txtTotalFinal").innerText = `$${totalFinal.toLocaleString('co-CO', {minimumFractionDigits: 2})}`;
        
        inputTotal.value = totalFinal.toFixed(2);
        ventaJSON.value = JSON.stringify(carrito);
        
        recalcularSaldos(totalFinal);
    }

    // Función global para los botones + y - de cantidad
    window.cambiarCantidad = (index, cambio) => {
        carrito[index].cantidad += cambio;
        
        // Si la cantidad llega a cero, removemos el producto automáticamente
        if (carrito[index].cantidad <= 0) {
            carrito.splice(index, 1);
        }
        actualizarRenderCarrito();
    };

    window.quitarDelCarrito = (index) => {
        carrito.splice(index, 1);
        actualizarRenderCarrito();
    };

    function recalcularSaldos(totalFinal) {
        const abono = parseFloat(inputAbono.value) || 0;
        const saldo = Math.max(0, totalFinal - abono);
        document.getElementById("txtSaldoPendiente").innerText = `$${saldo.toLocaleString('co-CO', {minimumFractionDigits: 2})}`;
    }

    inputAbono.addEventListener("input", () => {
        const total = parseFloat(inputTotal.value) || 0;
        recalcularSaldos(total);
    });

    function mostrarMensajeAlerta(msg, tipo) {
        const alerta = document.getElementById("mensajeAlerta");
        if (alerta) {
            alerta.innerText = msg;
            alerta.style.display = "block";
            alerta.style.backgroundColor = tipo === "danger" ? "#fee2e2" : "#fef3c7";
            alerta.style.color = tipo === "danger" ? "#991b1b" : "#92400e";
            alerta.style.border = `1px solid ${tipo === "danger" ? "#fca5a5" : "#fde68a"}`;
        } else {
            alert(msg);
        }
    }

    function ocultarMensajeAlerta() {
        const alerta = document.getElementById("mensajeAlerta");
        if (alerta) alerta.style.display = "none";
    }
});