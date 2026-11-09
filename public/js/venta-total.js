/**
 * Total en vivo del formulario de venta.
 *
 * El cajero ve cuanto va a cobrar antes de confirmar, y el navegador
 * avisa si la cantidad supera el stock disponible. Es la prevencion de
 * errores mencionado en el documento de diseno: evita el error de dedo.
 */
(function () {
    'use strict';

    const form = document.getElementById('form-venta');
    if (!form) {
        return;
    }

    const inputs = form.querySelectorAll('input[name^="cantidad["]');
    const totalEl = document.getElementById('total-venta');
    const avisoEl = document.getElementById('aviso-venta');
    const btnCobrar = document.getElementById('btn-cobrar');

    // Formatea pesos colombianos sin depender de la configuracion regional.
    function formatear(valor) {
        return '$' + valor.toLocaleString('es-CO', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function calcular() {
        let total = 0;
        let hayError = false;
        let articulos = 0;

        inputs.forEach((input) => {
            const fila = input.closest('tr');
            const cantidad = parseInt(input.value, 10) || 0;
            const precio = parseFloat(input.dataset.precio) || 0;
            const stock = parseInt(input.dataset.stock, 10) || 0;
            const celdaSubtotal = fila.querySelector('.subtotal');

            if (cantidad > stock) {
                fila.classList.add('fila-error');
                hayError = true;
            } else {
                fila.classList.remove('fila-error');
            }

            const subtotal = cantidad * precio;

            if (celdaSubtotal) {
                celdaSubtotal.textContent = cantidad > 0 ? formatear(subtotal) : '';
            }

            if (cantidad > 0) {
                articulos++;
            }

            total += subtotal;
        });

        if (totalEl) {
            totalEl.textContent = formatear(total);
        }

        if (avisoEl) {
            if (hayError) {
                avisoEl.textContent = '⚠️ Hay una cantidad que supera el stock disponible.';
                avisoEl.className = 'alert-error';
                avisoEl.style.display = 'block';
            } else if (articulos === 0) {
                avisoEl.textContent = 'Selecciona al menos un producto para cobrar.';
                avisoEl.className = 'alert-warn';
                avisoEl.style.display = 'block';
            } else {
                avisoEl.textContent = '';
                avisoEl.style.display = 'none';
            }
        }

        if (btnCobrar) {
            btnCobrar.disabled = hayError || articulos === 0;
        }
    }

    inputs.forEach((input) => {
        input.addEventListener('input', calcular);
    });

    calcular();
})();