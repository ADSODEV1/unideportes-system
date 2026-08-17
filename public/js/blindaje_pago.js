// public/js/blindaje_pago.js (v5: vueltas máximas escalables)
(function () {
    'use strict';
    var CAMBIO_BASE = 100000;      // vueltas máximas en ventas pequeñas

    var form = document.getElementById('ventaForm') || document.getElementById('formVentaMayorista');
    var inputPagaCon = document.getElementById('inputPagaCon');
    var inputTotal = document.getElementById('inputTotal');
    var metodoSelect = document.getElementById('metodo_pago');
    if (!form || !inputPagaCon || !inputTotal) return;

    var caja = document.getElementById('alertaPago');
    if (!caja) {
        caja = document.createElement('div');
        caja.id = 'alertaPago';
        caja.style.cssText = 'display:none;margin:0 0 10px 0;padding:8px 10px;border-radius:6px;font-size:0.85rem;font-weight:600;';
        var sec = document.getElementById('seccionCambio');
        if (sec && sec.parentNode) sec.parentNode.insertBefore(caja, sec); else form.appendChild(caja);
    }

    function fmt(n) { return Math.round(n).toLocaleString('es-CO'); }
        function sugerirBilletes(cambio) {
        var B = [100000, 50000, 20000, 10000, 5000, 2000, 1000, 500, 200, 100, 50];
        var out = [];
        var rest = Math.round(cambio);
        for (var i = 0; i < B.length && rest > 0; i++) {
            var q = Math.floor(rest / B[i]);
            if (q > 0) { out.push(q + '×$' + B[i].toLocaleString('es-CO')); rest -= q * B[i]; }
        }
        return out.join(' + ');
    }
    function boton() { return form.querySelector('button[type="submit"]'); }
    function billetesOK(m) { return Number.isInteger(m) && m % 50 === 0; }
    function totalActual() { return parseFloat(inputTotal.value) || 0; }
    function cambioMaximo(t) { return Math.max(CAMBIO_BASE, Math.round(t * 0.05)); }
    function maxPermitido() {
        var t = totalActual();
        if (t <= 0) return 0;
                return t + cambioMaximo(t);
    }
    function pintar(msg, tipo) {
        if (!msg) { caja.style.display = 'none'; return; }
        var c = { danger: ['#fee2e2','#991b1b','#ef4444'], warning: ['#fef3c7','#92400e','#f59e0b'], success: ['#d1fae5','#065f46','#10b981'] }[tipo] || ['#fef3c7','#92400e','#f59e0b'];
        caja.style.display = 'block';
        caja.style.background = c[0]; caja.style.color = c[1]; caja.style.borderLeft = '4px solid ' + c[2];
        caja.textContent = msg;
    }
    function colorCambioDespues(ok) {
        setTimeout(function () {
            var el = document.getElementById('txtCambio');
            if (el) el.style.color = ok ? 'var(--success)' : 'var(--danger)';
        }, 0);
    }

    function validar() {
        var total = totalActual();
        var v = parseFloat(inputPagaCon.value) || 0;
        var metodo = metodoSelect ? metodoSelect.value : 'Efectivo';
        var b = boton();
        if (metodo !== 'Efectivo') { pintar('', ''); if (b) b.disabled = false; return true; }
        if (total <= 0) {
            if (v > 0) inputPagaCon.value = '';
            pintar('⚠️ El total aún es $0: agrega productos al carrito antes de recibir el pago.', 'warning');
            colorCambioDespues(false); if (b) b.disabled = true; return false;
        }
        if (v <= 0) { pintar('⚠️ Ingresa el monto recibido del cliente.', 'warning'); colorCambioDespues(false); if (b) b.disabled = true; return false; }
        if (!billetesOK(v)) { pintar('❌ El monto no coincide con el efectivo de Colombia: debe ser un valor terminado en 00 o 50 (ej. $50.000, $100.050).', 'danger'); colorCambioDespues(false); if (b) b.disabled = true; return false; }
        if (v < total) { pintar('❌ Falta dinero: faltan $' + fmt(total - v) + '.', 'danger'); colorCambioDespues(false); if (b) b.disabled = true; return false; }
                var msgExito = '✅ Pago válido. Cambio: $' + fmt(v - total);
        var billetes = sugerirBilletes(v - total);
        if (billetes) msgExito += ' → 💡 ' + billetes;
        pintar(msgExito, 'success');
        colorCambioDespues(true); if (b) b.disabled = false;
        return true;
    }

    var ultimoValido = 0;
    function revisar() {
        var total = totalActual();
        var v = parseFloat(inputPagaCon.value) || 0;
        var metodo = metodoSelect ? metodoSelect.value : 'Efectivo';
        var max = maxPermitido();
        if (metodo !== 'Efectivo') { ultimoValido = v; validar(); return; }
        if (ultimoValido > max) ultimoValido = 0;
        if (total <= 0) { ultimoValido = 0; validar(); return; }
        if (v > max || (ultimoValido >= total && v > ultimoValido)) {
            inputPagaCon.value = ultimoValido > 0 ? String(ultimoValido) : '';
            validar();
            pintar('⚠️ No registré ' + fmt(v) + ': lo máximo recibible es $' + fmt(max) + ' (total $' + fmt(total) + ' + vueltas hasta $' + fmt(cambioMaximo(total)) + '). Valor conservado: $' + fmt(parseFloat(inputPagaCon.value) || 0) + '.', 'warning'); setTimeout(function(){ validar(); }, 4000);
            return;
        }
        ultimoValido = v;
        validar();
    }
    inputPagaCon.addEventListener('input', revisar);
    if (metodoSelect) metodoSelect.addEventListener('change', function () { setTimeout(validar, 50); });

    form.addEventListener('submit', function (e) {
        if (!validar()) { e.preventDefault(); e.stopImmediatePropagation(); inputPagaCon.focus(); }
    }, true);

    setInterval(function () {
        var v = parseFloat(inputPagaCon.value) || 0;
        var t = totalActual();
        if ((t <= 0 && v > 0) || (t > 0 && v > maxPermitido())) revisar();
    }, 600);

    document.addEventListener('DOMContentLoaded', function () {
        if (!document.getElementById('ventaForm')) return;
        if (typeof window.calcularTotales === 'function') {
            var original = window.calcularTotales;
            window.calcularTotales = function () {
                original.apply(this, arguments);
                var t = document.getElementById('txtTotal');
                var f = document.getElementById('txtTotalFinal');
                if (t && f) f.textContent = t.textContent;
                setTimeout(revisar, 0);
            };
        }
    });
})();