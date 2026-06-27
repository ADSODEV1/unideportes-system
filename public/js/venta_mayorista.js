// Archivo: venta_mayorista.js
// Controla el carrito mayorista con validación de stock por variante (color+talla)
document.addEventListener("DOMContentLoaded", () => {
    // 1. ELEMENTOS DEL DOM
    
    // Cliente
    const clienteInput = document.getElementById("clienteInput");
    const clienteIdHidden = document.getElementById("cliente_id_hidden");
    const listaClientes = document.getElementById("listaClientes");
    const btnToggleNuevoCliente = document.getElementById("btnToggleNuevoCliente");
    const nuevoClienteSection = document.getElementById("nuevoClienteSection");

    // Método de pago
    const metodoPago = document.getElementById("metodo_pago");
    const seccionTransferencia = document.getElementById("seccionTransferencia");
    const tipoTransferenciaSelect = document.getElementById("tipo_transferencia_select");
    const otraPlataformaInput = document.getElementById("otra_plataforma_input");
    const tipoTransferenciaFinal = document.getElementById("tipo_transferencia_final");

    // Tipo de entrega
    const tipoEntrega = document.getElementById("tipo_entrega");
    const seccionDomicilio = document.getElementById("seccionDomicilio");
    const direccionEntrega = document.getElementById("direccion_entrega");
    const barrioEntrega = document.getElementById("barrio_entrega");
    const ciudadEntrega = document.getElementById("ciudad_entrega");

    // Productos
    const productoInput = document.getElementById("productoInput");
    const listaProductos = document.getElementById("listaProductos");
    const btnAgregar = document.getElementById("btnAgregar");
    const carritoBody = document.getElementById("carritoBody");
    const ventaJSON = document.getElementById("ventaJSON");
    const inputTotal = document.getElementById("inputTotal");
    const inputAbono = document.getElementById("inputAbono");
    const formVentaMayorista = document.getElementById("formVentaMayorista");

    // Contenedores de color y talla
    const wrapperColor = document.getElementById("wrapperProductoColor");
    const wrapperTalla = document.getElementById("wrapperProductoTalla");

    // Estado
    let carrito = [];
    let prodSeleccionado = { id: "", nombre: "", precio: 0, stock: 0 };
    let varianteActual = null;

    // 2. FUNCIONES AUXILIARES

    function toggleElement(element, show) {
        if (!element) return;
        if (show) {
            element.classList.add('active');
            element.classList.remove('hidden');
        } else {
            element.classList.remove('active');
            element.classList.add('hidden');
        }
    }

    function mostrarMensajeAlerta(msg, tipo) {
        const alerta = document.getElementById("mensajeAlerta");
        if (!alerta) {
            alert(msg);
            return;
        }
        
        alerta.innerText = msg;
        alerta.classList.add('visible');
        alerta.classList.remove('success', 'error', 'warning');
        
        if (tipo === "danger" || tipo === "error") {
            alerta.classList.add('error');
        } else if (tipo === "warning") {
            alerta.classList.add('warning');
        } else {
            alerta.classList.add('success');
        }
        
        setTimeout(() => {
            alerta.classList.remove('visible');
        }, 6000);
    }

    function ocultarMensajeAlerta() {
        const alerta = document.getElementById("mensajeAlerta");
        if (alerta) {
            alerta.classList.remove('visible');
        }
    }

    function actualizarEstadoBtnAgregar(habilitado, stock = null) {
        if (!btnAgregar) return;
        
        btnAgregar.disabled = !habilitado;
        btnAgregar.classList.toggle('btn-agregar-disabled', !habilitado);
        btnAgregar.classList.toggle('btn-agregar-active', habilitado);
        
        if (habilitado && stock !== null) {
            btnAgregar.innerHTML = `+ Añadir (${stock} disp.)`;
        } else {
            btnAgregar.innerHTML = "+ Añadir";
        }
    }

    // 3. CONTROLADORES DE CLIENTE Y PAGO

    if (clienteInput && listaClientes) {
        clienteInput.addEventListener("input", () => {
            const val = clienteInput.value;
            const opciones = listaClientes.options;
            let encontrado = false;

            for (let i = 0; i < opciones.length; i++) {
                if (opciones[i].value === val) {
                    clienteIdHidden.value = opciones[i].getAttribute("data-id");
                    if (direccionEntrega) direccionEntrega.value = opciones[i].getAttribute("data-direccion") || "";
                    if (barrioEntrega) barrioEntrega.value = opciones[i].getAttribute("data-barrio") || "";
                    if (ciudadEntrega) ciudadEntrega.value = opciones[i].getAttribute("data-ciudad") || "Sogamoso";
                    encontrado = true;
                    break;
                }
            }
            if (!encontrado) clienteIdHidden.value = "";
        });
    }

    if (btnToggleNuevoCliente) {
        btnToggleNuevoCliente.addEventListener("click", () => {
            const estaOculto = !nuevoClienteSection.classList.contains('active');
            
            toggleElement(nuevoClienteSection, estaOculto);
            
            if (estaOculto) {
                btnToggleNuevoCliente.innerText = "Usar cliente existente";
                clienteInput.value = "";
                clienteInput.disabled = true;
                clienteIdHidden.value = "NUEVO";
            } else {
                btnToggleNuevoCliente.innerText = "Crear cliente nuevo";
                clienteInput.disabled = false;
                clienteIdHidden.value = "";
            }
        });
    }

    if (metodoPago) {
        metodoPago.addEventListener("change", () => {
            if (metodoPago.value === "Transferencia") {
                toggleElement(seccionTransferencia, true);
                actualizarTipoTransferencia();
            } else {
                toggleElement(seccionTransferencia, false);
                tipoTransferenciaFinal.value = "";
            }
        });
    }

    if (tipoTransferenciaSelect) {
        tipoTransferenciaSelect.addEventListener("change", actualizarTipoTransferencia);
    }

    if (otraPlataformaInput) {
        otraPlataformaInput.addEventListener("input", actualizarTipoTransferencia);
    }

    function actualizarTipoTransferencia() {
        if (!tipoTransferenciaSelect) return;
        
        if (tipoTransferenciaSelect.value === "Otro") {
            toggleElement(otraPlataformaInput, true);
            tipoTransferenciaFinal.value = otraPlataformaInput.value;
        } else {
            toggleElement(otraPlataformaInput, false);
            tipoTransferenciaFinal.value = tipoTransferenciaSelect.value;
        }
    }

    if (tipoEntrega) {
        tipoEntrega.addEventListener("change", () => {
            if (tipoEntrega.value === "Domicilio") {
                toggleElement(seccionDomicilio, true);
            } else {
                toggleElement(seccionDomicilio, false);
            }
        });
    }

    // 4. LÓGICA DEL BUSCADOR DE PRODUCTOS

    if (productoInput && listaProductos) {
        productoInput.addEventListener("input", () => {
            const val = productoInput.value;
            const opciones = listaProductos.options;
            let encontrado = false;

            for (let i = 0; i < opciones.length; i++) {
                if (opciones[i].value === val) {
                    encontrado = true;
                    prodSeleccionado.id = opciones[i].getAttribute("data-id");
                    prodSeleccionado.nombre = opciones[i].value;
                    prodSeleccionado.precio = parseFloat(opciones[i].getAttribute("data-precio")) || 0;
                    prodSeleccionado.stock = parseInt(opciones[i].getAttribute("data-stock")) || 0;

                    cargarColoresDisponibles(prodSeleccionado.nombre);
                    break;
                }
            }
            if (!encontrado) {
                desactivarCamposProducto();
            }
        });
    }

    function cargarColoresDisponibles(nombreProducto) {
        fetch(`../controllers/get_variantes_producto.php?nombre=${encodeURIComponent(nombreProducto)}&color=&talla=`)
            .then(response => response.json())
            .then(data => {
                if (data.colors && data.colors.length > 0) {
                    wrapperColor.innerHTML = `
                        <label for="productoColor" class="form-label"><strong>Color:</strong></label>
                        <select id="productoColor" class="select-estandar">
                            <option value="">-- Seleccione Color --</option>
                            ${data.colors.map(c => `<option value="${c}">${c}</option>`).join('')}
                        </select>
                    `;
                    
                    wrapperTalla.innerHTML = `
                        <label for="productoTalla" class="form-label"><strong>Talla:</strong></label>
                        <select id="productoTalla" class="select-estandar input-disabled" disabled>
                            <option value="">-- Primero seleccione color --</option>
                        </select>
                    `;

                    document.getElementById("productoColor").addEventListener("change", () => {
                        const colorSel = document.getElementById("productoColor").value;
                        if (colorSel) {
                            cargarTallasDisponibles(nombreProducto, colorSel);
                        } else {
                            desactivarTalla();
                        }
                    });
                } else {
                    wrapperColor.innerHTML = `
                        <label for="productoColor" class="form-label"><strong>Color:</strong></label>
                        <input type="text" id="productoColor" class="input-estandar input-disabled" value="Sin color" disabled>
                    `;
                    cargarTallasDisponibles(nombreProducto, "Sin color");
                }
            })
            .catch(error => {
                console.error("Error cargando colores: ", error);
                mostrarMensajeAlerta("Error al cargar variantes del producto", "danger");
            });
    }

    function cargarTallasDisponibles(nombreProducto, color) {
        fetch(`../controllers/get_variantes_producto.php?nombre=${encodeURIComponent(nombreProducto)}&color=${encodeURIComponent(color)}&talla=`)
            .then(response => response.json())
            .then(data => {
                if (data.tallas && data.tallas.length > 0) {
                    wrapperTalla.innerHTML = `
                        <label for="productoTalla" class="form-label"><strong>Talla:</strong></label>
                        <select id="productoTalla" class="select-estandar">
                            <option value="">-- Seleccione Talla --</option>
                            ${data.tallas.map(t => `<option value="${t}">${t}</option>`).join('')}
                        </select>
                    `;

                    document.getElementById("productoTalla").addEventListener("change", () => {
                        const tallaSel = document.getElementById("productoTalla").value;
                        if (tallaSel) {
                            consultarStockVariante(nombreProducto, color, tallaSel);
                        } else {
                            varianteActual = null;
                            actualizarEstadoBtnAgregar(false);
                        }
                    });
                } else {
                    wrapperTalla.innerHTML = `
                        <label for="productoTalla" class="form-label"><strong>Talla:</strong></label>
                        <input type="text" id="productoTalla" class="input-estandar input-disabled" value="Sin talla" disabled>
                    `;
                    consultarStockVariante(nombreProducto, color, "Sin talla");
                }
            })
            .catch(error => {
                console.error("Error cargando tallas: ", error);
            });
    }

    function desactivarTalla() {
        wrapperTalla.innerHTML = `
            <label for="productoTalla" class="form-label"><strong>Talla:</strong></label>
            <select id="productoTalla" class="select-estandar input-disabled" disabled>
                <option value="">-- Primero seleccione color --</option>
            </select>
        `;
        varianteActual = null;
    }

    function consultarStockVariante(nombre, color, talla) {
        fetch(`../controllers/get_variantes_producto.php?nombre=${encodeURIComponent(nombre)}&color=${encodeURIComponent(color)}&talla=${encodeURIComponent(talla)}`)
            .then(response => response.json())
            .then(data => {
                if (data.variant) {
                    varianteActual = data.variant;
                    prodSeleccionado.id = data.variant.id;
                    prodSeleccionado.precio = parseFloat(data.variant.precio);
                    
                    const stock = parseInt(data.variant.stock);
                    if (stock > 0) {
                        actualizarEstadoBtnAgregar(true, stock);
                    } else {
                        actualizarEstadoBtnAgregar(false);
                        mostrarMensajeAlerta("⚠️ No hay stock de esta variante", "danger");
                    }
                } else {
                    varianteActual = null;
                    actualizarEstadoBtnAgregar(false);
                    mostrarMensajeAlerta("⚠️ No se encontró stock para esta combinación", "danger");
                }
            })
            .catch(error => {
                console.error("Error consultando stock: ", error);
                varianteActual = null;
                actualizarEstadoBtnAgregar(false);
            });
    }

    function desactivarCamposProducto() {
        wrapperColor.innerHTML = `
            <label for="productoColor" class="form-label"><strong>Color:</strong></label>
            <input type="text" id="productoColor" class="input-estandar input-disabled" placeholder="Selecciona producto" disabled>
        `;
        wrapperTalla.innerHTML = `
            <label for="productoTalla" class="form-label"><strong>Talla:</strong></label>
            <input type="text" id="productoTalla" class="input-estandar input-disabled" placeholder="Selecciona color" disabled>
        `;
        prodSeleccionado = { id: "", nombre: "", precio: 0, stock: 0 };
        varianteActual = null;
        actualizarEstadoBtnAgregar(false);
    }

    // 5. BOTÓN "+ AÑADIR" CON VALIDACIÓN DE STOCK

    actualizarEstadoBtnAgregar(false);

    if (btnAgregar) {
        btnAgregar.addEventListener("click", () => {
            const nombre = productoInput.value.trim();
            
            if (!nombre || !varianteActual) {
                mostrarMensajeAlerta("Por favor, selecciona un producto, color y talla válidos.", "danger");
                return;
            }

            const id = varianteActual.id;
            const precio = parseFloat(varianteActual.precio);
            const color = varianteActual.color;
            const talla = varianteActual.talla;
            const stockDisponible = parseInt(varianteActual.stock);

            // ✅ VALIDACIÓN DE STOCK
            const cantidadEnCarrito = carrito
                .filter(item => 
                    item.id === id &&
                    item.color.toLowerCase() === color.toLowerCase() && 
                    item.talla.toLowerCase() === talla.toLowerCase()
                )
                .reduce((total, item) => total + item.cantidad, 0);

            if (cantidadEnCarrito + 1 > stockDisponible) {
                mostrarMensajeAlerta(
                    `⚠️ Stock insuficiente. Solo hay ${stockDisponible} unidades de "${nombre}" (${color}/${talla}). Ya tienes ${cantidadEnCarrito} en el carrito.`,
                    "danger"
                );
                return;
            }

            const comentarioTextarea = document.getElementById("observaciones_venta_mayor");
            const comentario = comentarioTextarea ? comentarioTextarea.value.trim() : "";

            // ✅ BUSCAR SI YA EXISTE
            const itemExistente = carrito.find(item =>
                item.id === id &&
                item.color.toLowerCase() === color.toLowerCase() && 
                item.talla.toLowerCase() === talla.toLowerCase()
            );

            if (itemExistente) {
                itemExistente.cantidad += 1;
            } else {
                carrito.push({
                    id: id,
                    nombre: nombre,
                    color: color,
                    talla: talla,
                    precio: precio,
                    cantidad: 1, 
                    comentario: comentario
                });
            }

            // Limpiar campos
            productoInput.value = "";
            desactivarCamposProducto();
            ocultarMensajeAlerta();
            actualizarRenderCarrito();
        });
    }

    // 6. RENDER DEL CARRITO

    function actualizarRenderCarrito() {
        if (!carritoBody) return;
        
        carritoBody.innerHTML = "";
        let subtotalAcumulado = 0;

        const totalUnidades = carrito.reduce((acc, item) => acc + item.cantidad, 0);

        // ✅ CORRECCIÓN: Lógica de descuentos CORRECTA
        let factorDescuento = 0;
        if (totalUnidades >= 20) {
            factorDescuento = 0.10;  // 10% para 20+ unidades
        } else if (totalUnidades >= 10) {
            factorDescuento = 0.05;  // 5% para 10-19 unidades
        }
        // Menos de 10 unidades = 0% descuento

        actualizarBarraProgreso(totalUnidades, factorDescuento);

        if (carrito.length === 0) {
            carritoBody.innerHTML = `
                <tr>
                    <td colspan="8" class="carrito-vacio">
                        🛒 El carrito está vacío. Agrega productos para comenzar.
                    </td>
                </tr>
            `;
        } else {
            carrito.forEach((item, index) => {
                const descItem = item.precio * factorDescuento;
                const subtotalItem = (item.precio - descItem) * item.cantidad;
                subtotalAcumulado += item.precio * item.cantidad;

                carritoBody.innerHTML += `
                    <tr>
                        <td>${item.nombre}</td>
                        <td class="texto-semibold">${item.color}</td>
                        <td class="texto-bold">${item.talla}</td>
                        <td>$${item.precio.toLocaleString('co-CO', {minimumFractionDigits: 2})}</td>
                        <td class="text-center">
                            <div class="control-cantidad">
                                <button type="button" class="btn-cantidad" onclick="cambiarCantidad(${index}, -1)">-</button>
                                <span class="cantidad-valor">${item.cantidad}</span>
                                <button type="button" class="btn-cantidad" onclick="cambiarCantidad(${index}, 1)">+</button>
                            </div>
                        </td>
                        <td class="texto-amarillo">$${(descItem * item.cantidad).toLocaleString('co-CO', {minimumFractionDigits: 2})}</td>
                        <td class="texto-bold">$${subtotalItem.toLocaleString('co-CO', {minimumFractionDigits: 2})}</td>
                        <td class="text-center">
                            <button type="button" class="btn-quitar" onclick="quitarDelCarrito(${index})">❌</button>
                        </td>
                    </tr>
                `;
            });
        }

        const descuentoTotal = subtotalAcumulado * factorDescuento;
        const totalFinal = subtotalAcumulado - descuentoTotal;

        document.getElementById("txtTotal").innerText = `$${subtotalAcumulado.toLocaleString('co-CO', {minimumFractionDigits: 2})}`;
        document.getElementById("txtDescuento").innerText = `$${descuentoTotal.toLocaleString('co-CO', {minimumFractionDigits: 2})}`;
        document.getElementById("txtTotalFinal").innerText = `$${totalFinal.toLocaleString('co-CO', {minimumFractionDigits: 2})}`;

        inputTotal.value = totalFinal.toFixed(2);
        ventaJSON.value = JSON.stringify(carrito);

        recalcularSaldos(totalFinal);
    }

    function actualizarBarraProgreso(totalUnidades, factorDescuento) {
        const barra = document.getElementById("barraProgreso");
        const texto = document.getElementById("textoProgreso");
        const mensaje = document.getElementById("mensajeProgreso");

        if (!barra || !texto || !mensaje) return;

        let porcentaje = 0;
        let unidadesFaltantes = 10;

        if (totalUnidades >= 20) {
            porcentaje = 100;
            unidadesFaltantes = 0;
        } else if (totalUnidades >= 10) {
            porcentaje = (totalUnidades / 20) * 100;
            unidadesFaltantes = 20 - totalUnidades;
        } else {
            porcentaje = (totalUnidades / 10) * 100;
            unidadesFaltantes = 10 - totalUnidades;
        }

        barra.style.width = porcentaje + "%";
        barra.innerText = Math.round(porcentaje) + "%";
        texto.innerText = totalUnidades + " unidades";

        if (totalUnidades === 0) {
            mensaje.innerText = "🛒 Agrega productos para obtener descuentos por volumen.";
        } else if (totalUnidades >= 20) {
            mensaje.innerText = "🎉 ¡Excelente! Tienes el descuento máximo del 10%.";
        } else if (totalUnidades >= 10) {
            mensaje.innerText = `✅ Tienes 5% de descuento. Te faltan ${unidadesFaltantes} unidades para el 10%.`;
        } else {
            mensaje.innerText = `Te faltan ${unidadesFaltantes} unidades para obtener 5% de descuento.`;
        }
    }

    // 7. CONTROLES DE CANTIDAD (globales para onclick)

    window.cambiarCantidad = (index, cambio) => {
        const item = carrito[index];
        if (!item) return;

        if (cambio > 0) {
            fetch(`../controllers/get_variantes_producto.php?nombre=${encodeURIComponent(item.nombre)}&color=${encodeURIComponent(item.color)}&talla=${encodeURIComponent(item.talla)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.variant) {
                        const stockDisponible = parseInt(data.variant.stock);
                        const cantidadEnCarrito = carrito
                            .filter(i => 
                                i.id === item.id &&
                                i.color.toLowerCase() === item.color.toLowerCase() && 
                                i.talla.toLowerCase() === item.talla.toLowerCase()
                            )
                            .reduce((t, i) => t + i.cantidad, 0);

                        if (cantidadEnCarrito + 1 > stockDisponible) {
                            mostrarMensajeAlerta(
                                `⚠️ No hay más stock de "${item.nombre}" (${item.color}/${item.talla}). Disponible: ${stockDisponible}.`,
                                "danger"
                            );
                            return;
                        }

                        carrito[index].cantidad += cambio;
                        actualizarRenderCarrito();
                    }
                })
                .catch(error => console.error("Error: ", error));
            return;
        }

        carrito[index].cantidad += cambio;
        if (carrito[index].cantidad <= 0) {
            carrito.splice(index, 1);
        }
        actualizarRenderCarrito();
    };

    window.quitarDelCarrito = (index) => {
        carrito.splice(index, 1);
        actualizarRenderCarrito();
    };

    // 8. CÁLCULO DEL SALDO Y VUELTO (CORREGIDO)

    function recalcularSaldos(totalFinal) {
        const abono = parseFloat(inputAbono.value) || 0;
        const txtSaldo = document.getElementById("txtSaldoPendiente");
        
        if (!txtSaldo) return;
        
        const diferencia = abono - totalFinal;
        
        if (diferencia > 0) {
            // ✅ HAY VUELTO/CAMBIO (Cliente pagó de más)
            txtSaldo.innerText = `-$${diferencia.toLocaleString('co-CO', {minimumFractionDigits: 2})}`;
            txtSaldo.className = 'resumen-total-final texto-verde';
            txtSaldo.title = 'Vuelto/Cambio para el cliente';
        } else if (diferencia < 0) {
            // ❌ FALTA PLATA (Saldo pendiente)
            txtSaldo.innerText = `$${Math.abs(diferencia).toLocaleString('co-CO', {minimumFractionDigits: 2})}`;
            txtSaldo.className = 'resumen-total-final texto-rojo';
            txtSaldo.title = 'Saldo pendiente por pagar';
        } else {
            // ✅ PAGO EXACTO
            txtSaldo.innerText = '$0,00';
            txtSaldo.className = 'resumen-total-final texto-verde';
            txtSaldo.title = 'Pago exacto';
        }
    }

    if (inputAbono) {
        inputAbono.addEventListener("input", () => {
            const total = parseFloat(inputTotal.value) || 0;
            recalcularSaldos(total);
        });
    }

    // 9. VALIDACIÓN FINAL ANTES DE ENVIAR

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
            const abono = parseFloat(inputAbono.value) || 0;
            const minimoAbono = total * 0.50;

            // ✅ VALIDAR 50% MÍNIMO
            if (abono < minimoAbono) {
                e.preventDefault();
                mostrarMensajeAlerta(
                    `⚠️ El abono mínimo debe ser el 50% del total. Para esta venta, el mínimo es: $${minimoAbono.toLocaleString('co-CO', {minimumFractionDigits: 2})}`,
                    "danger"
                );
                return false;
            }

            // ✅ CONFIRMACIÓN CON INFORMACIÓN CORRECTA
            const diferencia = abono - total;
            let mensajeSaldo = '';
             
            if (diferencia > 0) {
                mensajeSaldo = `Vuelto: -$${diferencia.toLocaleString('co-CO', {minimumFractionDigits: 2})}`;
            } else if (diferencia < 0) {
                mensajeSaldo = `Saldo pendiente: $${Math.abs(diferencia).toLocaleString('co-CO', {minimumFractionDigits: 2})}`;
            } else {
                mensajeSaldo = 'Pago exacto';
            }

            const confirmar = confirm(
                `¿Confirmas procesar esta venta mayorista?\n\n` +
                `Cliente: ${clienteInput.value}\n` +
                `Productos: ${carrito.length} item(s)\n` +
                `Total: $${total.toLocaleString('co-CO', {minimumFractionDigits: 2})}\n` +
                `Abono: $${abono.toLocaleString('co-CO', {minimumFractionDigits: 2})}\n` +
                `${mensajeSaldo}`
            );

            if (!confirmar) {
                e.preventDefault();
                return false;
            }
        });
    }

    // 10. RECARGAR LISTA DE CLIENTES VÍA AJAX

    const btnRecargar = document.getElementById('btnRecargarClientes');
    if (btnRecargar) {
        btnRecargar.addEventListener('click', async () => {
            const textoOriginal = btnRecargar.innerHTML;
            btnRecargar.innerHTML = '⏳ Cargando...';
            btnRecargar.disabled = true;
            
            try {
                const res = await fetch('../controllers/get_clientes_ajax.php');
                const data = await res.json();
                
                if (data.success && data.clientes) {
                    const datalist = document.getElementById('listaClientes');
                    datalist.innerHTML = '';
                    
                    data.clientes.forEach(cli => {
                        const option = document.createElement('option');
                        option.value = cli.nombre_completo;
                        option.dataset.id = cli.id;
                        option.dataset.direccion = cli.direccion || '';
                        option.dataset.barrio = cli.barrio || '';
                        option.dataset.ciudad = cli.ciudad || 'Sogamoso';
                        option.dataset.referencia = cli.referencia_entrega || '';
                        datalist.appendChild(option);
                    });
                      
                    btnRecargar.innerHTML = '✅ Actualizado';
                    setTimeout(() => {
                        btnRecargar.innerHTML = textoOriginal;
                        btnRecargar.disabled = false;
                    }, 1500);
                } else {
                    throw new Error('Respuesta inválida');
                }
            } catch (error) {
                console.error('Error recargando clientes:', error);
                btnRecargar.innerHTML = '❌ Error';
                setTimeout(() => {
                    btnRecargar.innerHTML = textoOriginal;
                    btnRecargar.disabled = false;
                }, 2000);
            }
        });
    }

    // 11. INICIALIZACIÓN
    actualizarRenderCarrito();
    console.log("Venta Mayorista JS cargado correctamente");
});