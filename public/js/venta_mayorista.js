// Archivo: public/js/venta_mayorista.js
document.addEventListener("DOMContentLoaded", () => {
    // 1. ELEMENTOS DEL DOM
    const clienteInput = document.getElementById("clienteInput");
    const clienteIdHidden = document.getElementById("cliente_id_hidden");
    const listaClientes = document.getElementById("listaClientes");
    const btnToggleNuevoCliente = document.getElementById("btnToggleNuevoCliente");
    const nuevoClienteSection = document.getElementById("nuevoClienteSection");

    const metodoPago = document.getElementById("metodo_pago");
    const seccionCambio = document.getElementById("seccionCambio");
    const inputPagaCon = document.getElementById("inputPagaCon");
    const txtCambio = document.getElementById("txtCambio");
    const seccionTransferencia = document.getElementById("seccionTransferencia");
    const seccionTarjeta = document.getElementById("seccionTarjeta");
    const tipoTransferenciaSelect = document.getElementById("tipo_transferencia_select");
    const otraPlataformaInput = document.getElementById("otra_plataforma_input");
    const tipoTransferenciaFinal = document.getElementById("tipo_transferencia_final");
    const referenciaPagoInput = document.getElementById("referencia_pago_input");
    const ultimos4DigitosInput = document.getElementById("ultimos_4_digitos");
    const bancoEmisorInput = document.getElementById("banco_emisor");

    const tipoEntrega = document.getElementById("tipo_entrega");
    const seccionDomicilio = document.getElementById("seccionDomicilio");
    const direccionEntrega = document.getElementById("direccion_entrega");
    const barrioEntrega = document.getElementById("barrio_entrega");
    const ciudadEntrega = document.getElementById("ciudad_entrega");

    const productoInput = document.getElementById("productoInput");
    const listaProductos = document.getElementById("listaProductos");
    const productoPrecio = document.getElementById("productoPrecio");
    const productoCantidad = document.getElementById("productoCantidad");
    const btnAgregar = document.getElementById("btnAgregar");
    const carritoBody = document.getElementById("carritoBody");
    const ventaJSON = document.getElementById("ventaJSON");
    const inputTotal = document.getElementById("inputTotal");
    const formVentaMayorista = document.getElementById("formVentaMayorista");

    const wrapperColor = document.getElementById("wrapperProductoColor");
    const wrapperTalla = document.getElementById("wrapperProductoTalla");

    let carrito = [];
    let prodSeleccionado = { id: "", nombre: "", precio: 0, stock: 0 };
    let varianteActual = null;

    // 2. FUNCIONES AUXILIARES
    function mostrarMensajeAlerta(msg, tipo) {
        const alerta = document.getElementById("mensajeAlerta");
        if (!alerta) { alert(msg); return; }
        alerta.innerText = msg;
        alerta.style.display = "block";
        alerta.style.backgroundColor = tipo === "danger" ? "#fee2e2" : (tipo === "success" ? "#d1fae5" : "#fef3c7");
        alerta.style.color = tipo === "danger" ? "#991b1b" : (tipo === "success" ? "#065f46" : "#92400e");
        alerta.style.borderLeft = `4px solid ${tipo === "danger" ? "#ef4444" : (tipo === "success" ? "#10b981" : "#f59e0b")}`;
        setTimeout(() => { alerta.style.display = "none"; }, 6000);
    }

    function actualizarEstadoBtnAgregar(habilitado, stock = null) {
        if (!btnAgregar) return;
        btnAgregar.disabled = !habilitado;
        btnAgregar.style.opacity = habilitado ? "1" : "0.6";
        btnAgregar.style.cursor = habilitado ? "pointer" : "not-allowed";
        btnAgregar.innerHTML = (habilitado && stock !== null) ? `+ Añadir (${stock} disp.)` : "+ Añadir";
    }

    // 3. CONTROLADORES DE CLIENTE
    if (clienteInput && listaClientes) {
        clienteInput.addEventListener("input", () => {
            const val = clienteInput.value;
            let encontrado = false;
            for (let i = 0; i < listaClientes.options.length; i++) {
                if (listaClientes.options[i].value === val) {
                    clienteIdHidden.value = listaClientes.options[i].getAttribute("data-id");
                    if (tipoEntrega && tipoEntrega.value === "Domicilio") {
                        if (direccionEntrega) direccionEntrega.value = listaClientes.options[i].getAttribute("data-direccion") || "";
                        if (barrioEntrega) barrioEntrega.value = listaClientes.options[i].getAttribute("data-barrio") || "";
                        if (ciudadEntrega) ciudadEntrega.value = listaClientes.options[i].getAttribute("data-ciudad") || "Sogamoso";
                    }
                    encontrado = true;
                    break;
                }
            }
            if (!encontrado) clienteIdHidden.value = "";
        });
    }

    if (btnToggleNuevoCliente) {
        btnToggleNuevoCliente.addEventListener("click", () => {
            const estaOculto = nuevoClienteSection.style.display === "none" || nuevoClienteSection.style.display === "";
            nuevoClienteSection.style.display = estaOculto ? "block" : "none";
            btnToggleNuevoCliente.innerText = estaOculto ? "Usar cliente existente" : "Crear cliente nuevo";
            if (estaOculto) {
                clienteInput.value = "";
                clienteInput.disabled = true;
                clienteIdHidden.value = "NUEVO";
            } else {
                clienteInput.disabled = false;
                clienteIdHidden.value = "";
            }
        });
    }

    // 4. LÓGICA DE PAGO (EFECTIVO, TRANSFERENCIA, TARJETA)
    function mostrarSeccionPago() {
        // Ocultar TODAS las secciones primero
        if (seccionCambio) seccionCambio.style.display = "none";
        if (seccionTransferencia) seccionTransferencia.style.display = "none";
        if (seccionTarjeta) seccionTarjeta.style.display = "none";
        if (referenciaPagoInput) referenciaPagoInput.required = false;

        const metodo = metodoPago.value;

        // Mostrar la sección correspondiente
        if (metodo === "Efectivo") {
            if (seccionCambio) seccionCambio.style.display = "block";
            if (inputPagaCon) inputPagaCon.value = "";
            if (txtCambio) txtCambio.innerText = "$0";
        } else if (metodo === "Transferencia") {
            if (seccionTransferencia) seccionTransferencia.style.display = "block";
            if (referenciaPagoInput) referenciaPagoInput.required = true;
            actualizarTipoTransferencia();
        } else if (metodo === "Tarjeta") {
            if (seccionTarjeta) seccionTarjeta.style.display = "block";
        }
    }

    if (metodoPago) {
        metodoPago.addEventListener("change", mostrarSeccionPago);
    }

    if (tipoTransferenciaSelect) tipoTransferenciaSelect.addEventListener("change", actualizarTipoTransferencia);
    if (otraPlataformaInput) otraPlataformaInput.addEventListener("input", actualizarTipoTransferencia);

    function actualizarTipoTransferencia() {
        if (!tipoTransferenciaSelect) return;
        if (tipoTransferenciaSelect.value === "Otro") {
            otraPlataformaInput.style.display = "block";
            tipoTransferenciaFinal.value = otraPlataformaInput.value;
        } else {
            otraPlataformaInput.style.display = "none";
            tipoTransferenciaFinal.value = tipoTransferenciaSelect.value;
        }
    }

    // Calcular cambio en tiempo real
    if (inputPagaCon) {
        inputPagaCon.addEventListener("input", () => {
            const total = parseFloat(inputTotal.value) || 0;
            const pagaCon = parseFloat(inputPagaCon.value) || 0;
            const cambio = pagaCon - total;
            if (txtCambio) {
                if (cambio >= 0) {
                    txtCambio.innerText = `$${cambio.toLocaleString('es-CO')}`;
                    txtCambio.style.color = "var(--success)";
                } else {
                    txtCambio.innerText = `Falta $${Math.abs(cambio).toLocaleString('es-CO')}`;
                    txtCambio.style.color = "var(--danger)";
                }
            }
        });
    }

    // 5. LÓGICA DE DOMICILIO
    if (tipoEntrega && seccionDomicilio) {
        tipoEntrega.addEventListener("change", function() {
            if (this.value === "Domicilio") {
                seccionDomicilio.style.display = "block";
                if (clienteInput && listaClientes && clienteInput.value.trim()) {
                    const val = clienteInput.value.trim();
                    for (let i = 0; i < listaClientes.options.length; i++) {
                        const opt = listaClientes.options[i];
                        if (opt.value === val) {
                            if (direccionEntrega) direccionEntrega.value = opt.getAttribute("data-direccion") || "";
                            if (barrioEntrega) barrioEntrega.value = opt.getAttribute("data-barrio") || "";
                            if (ciudadEntrega) ciudadEntrega.value = opt.getAttribute("data-ciudad") || "Sogamoso";
                            break;
                        }
                    }
                }
            } else {
                seccionDomicilio.style.display = "none";
                if (direccionEntrega) direccionEntrega.value = "";
                if (barrioEntrega) barrioEntrega.value = "";
                if (ciudadEntrega) ciudadEntrega.value = "";
            }
            actualizarRenderCarrito();
        });
    }

    // 6. LÓGICA DEL BUSCADOR DE PRODUCTOS
    if (productoInput && listaProductos) {
        productoInput.addEventListener("input", () => {
            const val = productoInput.value;
            let encontrado = false;
            for (let i = 0; i < listaProductos.options.length; i++) {
                if (listaProductos.options[i].value === val) {
                    encontrado = true;
                    prodSeleccionado.id = listaProductos.options[i].getAttribute("data-id");
                    prodSeleccionado.nombre = listaProductos.options[i].value;
                    prodSeleccionado.precio = parseFloat(listaProductos.options[i].getAttribute("data-precio")) || 0;
                    prodSeleccionado.stock = parseInt(listaProductos.options[i].getAttribute("data-stock")) || 0;
                    if (productoPrecio) productoPrecio.value = prodSeleccionado.precio;
                    cargarColoresDisponibles(prodSeleccionado.nombre);
                    break;
                }
            }
            if (!encontrado) {
                desactivarCamposProducto();
                if (productoPrecio) productoPrecio.value = "";
            }
        });
    }

    function cargarColoresDisponibles(nombreProducto) {
        fetch(`../controllers/get_variantes_producto.php?nombre=${encodeURIComponent(nombreProducto)}`)
            .then(res => res.json())
            .then(data => {
                if (data.colors && data.colors.length > 0) {
                    wrapperColor.innerHTML = `<label><strong>Color:</strong></label><select id="productoColor" style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;"><option value="">-- Seleccione Color --</option>${data.colors.map(c => `<option value="${c}">${c}</option>`).join('')}</select>`;
                    wrapperTalla.innerHTML = `<label><strong>Talla:</strong></label><input type="text" disabled placeholder="Seleccione color primero" style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; background: var(--input-bg);">`;
                    document.getElementById("productoColor").addEventListener("change", (e) => {
                        if (e.target.value) cargarTallasDisponibles(nombreProducto, e.target.value);
                        else desactivarTalla();
                    });
                } else {
                    wrapperColor.innerHTML = `<label><strong>Color:</strong></label><input type="text" value="Sin color" disabled style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; background: var(--input-bg);">`;
                    cargarTallasDisponibles(nombreProducto, "Sin color");
                }
            })
            .catch(err => {
                console.error("Error cargando colores:", err);
                mostrarMensajeAlerta("Error al cargar los colores del producto.", "danger");
                desactivarCamposProducto();
            });
    }

    function cargarTallasDisponibles(nombreProducto, color) {
        fetch(`../controllers/get_variantes_producto.php?nombre=${encodeURIComponent(nombreProducto)}&color=${encodeURIComponent(color)}`)
            .then(res => res.json())
            .then(data => {
                if (data.tallas && data.tallas.length > 0) {
                    wrapperTalla.innerHTML = `<label><strong>Talla:</strong></label><select id="productoTalla" style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px;"><option value="">-- Seleccione Talla --</option>${data.tallas.map(t => `<option value="${t}">${t}</option>`).join('')}</select>`;
                    document.getElementById("productoTalla").addEventListener("change", (e) => {
                        if (e.target.value) consultarStockVariante(nombreProducto, color, e.target.value);
                    });
                } else {
                    wrapperTalla.innerHTML = `<label><strong>Talla:</strong></label><input type="text" value="Sin talla" disabled style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; background: var(--input-bg);">`;
                    consultarStockVariante(nombreProducto, color, "Sin talla");
                }
            })
            .catch(err => {
                console.error("Error cargando tallas:", err);
                mostrarMensajeAlerta("Error al cargar las tallas del producto.", "danger");
            });
    }

    function desactivarTalla() {
        wrapperTalla.innerHTML = `<label><strong>Talla:</strong></label><input type="text" disabled placeholder="Seleccione color primero" style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; background: var(--input-bg);">`;
        varianteActual = null;
        actualizarEstadoBtnAgregar(false);
    }

    function consultarStockVariante(nombre, color, talla) {
        fetch(`../controllers/get_variantes_producto.php?nombre=${encodeURIComponent(nombre)}&color=${encodeURIComponent(color)}&talla=${encodeURIComponent(talla)}`)
            .then(res => res.json())
            .then(data => {
                if (data.variant) {
                    varianteActual = data.variant;
                    prodSeleccionado.id = data.variant.id;
                    prodSeleccionado.precio = parseFloat(data.variant.precio);
                    const stock = parseInt(data.variant.stock);
                     // 🔒 Limita la cantidad al stock disponible (stock - carrito)
                const enCarrito = carrito.filter(i => i.id === data.variant.id).reduce((t, i) => t + i.cantidad, 0);
                const disponibles = Math.max(0, stock - enCarrito);
                if (productoCantidad) {
                    productoCantidad.max = disponibles;
                    if ((parseInt(productoCantidad.value, 10) || 1) > disponibles) productoCantidad.value = disponibles;
                }
                if (stock > 0) actualizarEstadoBtnAgregar(true, stock);
                    else {
                        actualizarEstadoBtnAgregar(false);
                        mostrarMensajeAlerta("⚠️ No hay stock de esta variante", "danger");
                    }
                } else {
                    varianteActual = null;
                    actualizarEstadoBtnAgregar(false);
                }
            })
            .catch(err => {
                console.error("Error consultando stock:", err);
                mostrarMensajeAlerta("Error al consultar el stock del producto.", "danger");
                actualizarEstadoBtnAgregar(false);
            });
    }

    function desactivarCamposProducto() {
        wrapperColor.innerHTML = `<label><strong>Color:</strong></label><input type="text" disabled placeholder="Selecciona producto" style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; background: var(--input-bg);">`;
        wrapperTalla.innerHTML = `<label><strong>Talla:</strong></label><input type="text" disabled placeholder="Selecciona color" style="width:100%; padding: 10px; margin-top: 5px; border: 1px solid var(--border); border-radius: 6px; background: var(--input-bg);">`;
        varianteActual = null;
        actualizarEstadoBtnAgregar(false);
    }

    // 🔒 BLOQUEO FÍSICO: la Cantidad no pasa del stock disponible
    if (productoCantidad) {
        productoCantidad.addEventListener("input", function () {
            const max = parseInt(this.max, 10);
            const v = parseInt(this.value, 10) || 0;
            if (!isNaN(max) && v > max) { this.value = max; }
            if (v < 1) this.value = 1;
        });
    }
    // 7. BOTÓN "+ AÑADIR"
    actualizarEstadoBtnAgregar(false);
    if (btnAgregar) {
        btnAgregar.addEventListener("click", () => {
            if (!productoInput.value.trim() || !varianteActual) {
                mostrarMensajeAlerta("Selecciona un producto, color y talla válidos.", "danger");
                return;
            }
            const cantidadSolicitada = parseInt(productoCantidad.value) || 1;
            if (cantidadSolicitada < 1) {
                mostrarMensajeAlerta("La cantidad debe ser al menos 1.", "danger");
                return;
            }
            const stockDisponible = parseInt(varianteActual.stock);
            const cantidadEnCarrito = carrito.filter(item => item.id === varianteActual.id).reduce((total, item) => total + item.cantidad, 0);

            if (cantidadEnCarrito + cantidadSolicitada > stockDisponible) {
                mostrarMensajeAlerta(`⚠️ Stock insuficiente. Solo hay ${stockDisponible} unidades disponibles.`, "danger");
                return;
            }

            const itemExistente = carrito.find(item => item.id === varianteActual.id);
            if (itemExistente) {
                itemExistente.cantidad += cantidadSolicitada;
            } else {
                carrito.push({
                    id: varianteActual.id,
                    nombre: prodSeleccionado.nombre,
                    color: varianteActual.color || "Sin color",
                    talla: varianteActual.talla || "Sin talla",
                    precio: prodSeleccionado.precio,
                    cantidad: cantidadSolicitada,
                    comentario: ""
                });
            }
            productoInput.value = "";
            if (productoPrecio) productoPrecio.value = "";
            productoCantidad.value = "1";
            desactivarCamposProducto();
            actualizarRenderCarrito();

            // IMPORTANTE: Asegurar que la sección de pago activa siga visible después de añadir
            if (metodoPago) {
                mostrarSeccionPago();
            }
        });
    }

    // 8. RENDER DEL CARRITO Y TOTALES
    function actualizarRenderCarrito() {
        // Limpiar completamente el body de la tabla
        carritoBody.innerHTML = "";
        let subtotalAcumulado = 0;
        const totalUnidades = carrito.reduce((acc, item) => acc + item.cantidad, 0);

        let factorDescuento = 0;
        if (totalUnidades >= 20) factorDescuento = 0.10;
        else if (totalUnidades >= 10) factorDescuento = 0.05;

        if (carrito.length === 0) {
            // colspan="8" porque la tabla tiene 8 columnas
            carritoBody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 30px; color: var(--text-light);">🛒 El carrito está vacío.</td></tr>`;
        } else {
            // Construir todas las filas primero para mejor rendimiento
            let filasHTML = "";
            carrito.forEach((item, index) => {
                const descItem = item.precio * factorDescuento;
                const subtotalItem = (item.precio - descItem) * item.cantidad;
                subtotalAcumulado += item.precio * item.cantidad;

                filasHTML += `
                    <tr>
                        <td style="padding: 10px;">${item.nombre}</td>
                        <td style="padding: 10px;">${item.color}</td>
                        <td style="padding: 10px;">${item.talla}</td>
                        <td style="padding: 10px; text-align: right;">$${item.precio.toLocaleString('es-CO')}</td>
                        <td style="padding: 10px; text-align: center;">
                            <button type="button" onclick="cambiarCantidad(${index}, -1)" style="padding: 2px 8px;">-</button>
                            <span style="margin: 0 8px;">${item.cantidad}</span>
                            <button type="button" onclick="cambiarCantidad(${index}, 1)" style="padding: 2px 8px;">+</button>
                        </td>
                        <td style="padding: 10px; text-align: right; color: var(--warning);">$${(descItem * item.cantidad).toLocaleString('es-CO')}</td>
                        <td style="padding: 10px; text-align: right; font-weight: bold;">$${subtotalItem.toLocaleString('es-CO')}</td>
                        <td style="padding: 10px; text-align: center;">
                            <button type="button" onclick="quitarDelCarrito(${index})" style="background: var(--danger); color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer;">❌</button>
                        </td>
                    </tr>`;
            });
            carritoBody.innerHTML = filasHTML;
        }

        const descuentoTotal = subtotalAcumulado * factorDescuento;
        let totalFinal = subtotalAcumulado - descuentoTotal;

        if (tipoEntrega && tipoEntrega.value === "Domicilio") {
            totalFinal += 5000;
        }

        const txtTotal = document.getElementById("txtTotal");
        const txtDescuento = document.getElementById("txtDescuento");
        const txtTotalFinal = document.getElementById("txtTotalFinal");

        if (txtTotal) txtTotal.innerText = `$${subtotalAcumulado.toLocaleString('es-CO')}`;
        if (txtDescuento) txtDescuento.innerText = `$${descuentoTotal.toLocaleString('es-CO')}`;
        if (txtTotalFinal) txtTotalFinal.innerText = `$${totalFinal.toLocaleString('es-CO')}`;

        inputTotal.value = totalFinal.toFixed(2);
        ventaJSON.value = JSON.stringify(carrito);

        // Recalcular cambio si ya hay algo escrito
        if (inputPagaCon && txtCambio && inputPagaCon.value) {
            const pagaCon = parseFloat(inputPagaCon.value) || 0;
            const cambio = pagaCon - totalFinal;
            if (cambio >= 0) {
                txtCambio.innerText = `$${cambio.toLocaleString('es-CO')}`;
                txtCambio.style.color = "var(--success)";
            } else {
                txtCambio.innerText = `Falta $${Math.abs(cambio).toLocaleString('es-CO')}`;
                txtCambio.style.color = "var(--danger)";
            }
        }
    }

    // 9. CONTROLES DE CANTIDAD
    window.cambiarCantidad = (index, cambio) => {
        const item = carrito[index];
        if (cambio > 0) {
            fetch(`../controllers/get_variantes_producto.php?nombre=${encodeURIComponent(item.nombre)}&color=${encodeURIComponent(item.color)}&talla=${encodeURIComponent(item.talla)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.variant) {
                        const stockDisponible = parseInt(data.variant.stock);
                        const cantidadEnCarrito = carrito.filter(i => i.id === item.id).reduce((t, i) => t + i.cantidad, 0);
                        if (cantidadEnCarrito + 1 > stockDisponible) {
                            mostrarMensajeAlerta(`⚠️ Stock insuficiente. Disponible: ${stockDisponible}.`, "danger");
                            return;
                        }
                        carrito[index].cantidad += cambio;
                        actualizarRenderCarrito();
                    }
                })
                .catch(err => {
                    console.error("Error cambiando cantidad:", err);
                    mostrarMensajeAlerta("Error al validar stock.", "danger");
                });
        } else {
            carrito[index].cantidad += cambio;
            if (carrito[index].cantidad <= 0) carrito.splice(index, 1);
            actualizarRenderCarrito();
        }
    };

    window.quitarDelCarrito = (index) => {
        carrito.splice(index, 1);
        actualizarRenderCarrito();
    };

    // 10. VALIDACIÓN FINAL (SIN ABONOS)
    if (formVentaMayorista) {
        formVentaMayorista.addEventListener("submit", (e) => {
            if (carrito.length === 0) {
                e.preventDefault();
                mostrarMensajeAlerta("⚠️ Debes agregar al menos un producto al carrito.", "danger");
                return false;
            }
            if (!clienteIdHidden.value || clienteIdHidden.value === "") {
                e.preventDefault();
                mostrarMensajeAlerta("⚠️ Debes seleccionar un cliente o crear uno nuevo.", "danger");
                return false;
            }

            const total = parseFloat(inputTotal.value) || 0;

            // Validar pago en efectivo
            if (metodoPago.value === "Efectivo") {
                const pagaCon = parseFloat(inputPagaCon.value) || 0;
                if (pagaCon < total) {
                    e.preventDefault();
                    mostrarMensajeAlerta(`⚠️ El monto pagado ($${pagaCon.toLocaleString('es-CO')}) es menor al total ($${total.toLocaleString('es-CO')}).`, "danger");
                    if (inputPagaCon) inputPagaCon.focus();
                    return false;
                }
            }

            // Validar dirección si es domicilio
            if (tipoEntrega && tipoEntrega.value === "Domicilio") {
                if (!direccionEntrega || !direccionEntrega.value.trim()) {
                    e.preventDefault();
                    mostrarMensajeAlerta("⚠️ Debes ingresar la dirección de entrega.", "danger");
                    return false;
                }
            }

            // Validar campos de Transferencia
            if (metodoPago.value === "Transferencia") {
                if (!referenciaPagoInput || !referenciaPagoInput.value.trim()) {
                    e.preventDefault();
                    mostrarMensajeAlerta("⚠️ Debes ingresar el número de referencia de la transferencia.", "danger");
                    return false;
                }
            }

            // Validar campos de Tarjeta
            if (metodoPago.value === "Tarjeta") {
                if (!ultimos4DigitosInput || !ultimos4DigitosInput.value.trim()) {
                    e.preventDefault();
                    mostrarMensajeAlerta("⚠️ Debes ingresar los últimos 4 dígitos de la tarjeta.", "danger");
                    return false;
                }
                if (!bancoEmisorInput || !bancoEmisorInput.value.trim()) {
                    e.preventDefault();
                    mostrarMensajeAlerta("⚠️ Debes ingresar el banco emisor de la tarjeta.", "danger");
                    return false;
                }
            }

            if (!confirm(`¿Confirmas procesar esta venta mayorista por un total de $${total.toLocaleString('es-CO')}?`)) {
                e.preventDefault();
                return false;
            }
        });
    }

    // ============================================
    // INICIALIZACIÓN CRÍTICA 
    // ============================================
    actualizarRenderCarrito();

    // Mostrar la sección de pago correspondiente a la opción seleccionada por defecto
    if (metodoPago) {
        mostrarSeccionPago();
    }
});