/**
 * GERENTE.JS - FUNCIONALIDAD DEL PANEL DE GERENTE
 *
 * Gestión de:
 * - Caja y turnos
 * - Ventas
 * - Gastos
 * - Cobros de cuotas
 *
 * Dependencias: Bootstrap 5, SweetAlert2
 */

'use strict';

// =============================================================================
// GESTIÓN DE CAJA Y TURNOS
// =============================================================================

const CajaManager = {

    /**
     * Inicializar módulo de caja
     */
    init() {
        this.inicializarEventos();
        this.verificarEstadoTurno();
    },

    /**
     * Configurar event listeners
     */
    inicializarEventos() {
        // Abrir turno
        const btnAbrirTurno = document.getElementById('btnAbrirTurno');
        if (btnAbrirTurno) {
            btnAbrirTurno.addEventListener('click', () => this.abrirTurno());
        }

        // Cerrar turno
        const btnCerrarTurno = document.getElementById('btnCerrarTurno');
        if (btnCerrarTurno) {
            btnCerrarTurno.addEventListener('click', () => this.cerrarTurno());
        }

        // Registrar venta
        const formVenta = document.getElementById('formRegistrarVenta');
        if (formVenta) {
            formVenta.addEventListener('submit', (e) => {
                e.preventDefault();
                this.registrarVenta();
            });
        }

        // Registrar gasto
        const formGasto = document.getElementById('formRegistrarGasto');
        if (formGasto) {
            formGasto.addEventListener('submit', (e) => {
                e.preventDefault();
                this.registrarGasto();
            });
        }

        // Registrar cobro de cuota
        const formCobro = document.getElementById('formCobrarCuota');
        if (formCobro) {
            formCobro.addEventListener('submit', (e) => {
                e.preventDefault();
                this.registrarCobroCuota();
            });
        }

        // Buscar productos
        const inputBusqueda = document.getElementById('buscarProducto');
        if (inputBusqueda) {
            inputBusqueda.addEventListener('input', (e) => {
                this.buscarProductos(e.target.value);
            });
        }
    },

    /**
     * Verificar estado del turno actual
     */
    async verificarEstadoTurno() {
        try {
            const response = await fetch('gerente/ajax/verificar-turno.php');
            const data = await response.json();

            if (data.turno_abierto) {
                this.mostrarPanelCaja(data.turno);
            } else {
                this.mostrarPanelAbrirTurno();
            }

        } catch (error) {
            console.error('Error al verificar turno:', error);
        }
    },

    /**
     * Abrir turno de caja
     */
    async abrirTurno() {
        const { value: montoInicial } = await Swal.fire({
            title: 'Abrir Turno de Caja',
            input: 'number',
            inputLabel: 'Monto inicial en caja',
            inputPlaceholder: 'Ingrese el monto inicial',
            inputAttributes: {
                min: 0,
                step: 0.01
            },
            showCancelButton: true,
            confirmButtonText: 'Abrir Turno',
            cancelButtonText: 'Cancelar',
            inputValidator: (value) => {
                if (!value || parseFloat(value) < 0) {
                    return 'Debe ingresar un monto válido';
                }
            }
        });

        if (!montoInicial) return;

        try {
            const response = await fetch('gerente/ajax/abrir-turno.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ monto_inicial: parseFloat(montoInicial) })
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Turno Abierto',
                    text: data.mensaje,
                    confirmButtonColor: '#10B981'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                throw new Error(data.mensaje);
            }

        } catch (error) {
            console.error('Error:', error);
            this.mostrarAlerta(error.message, 'error');
        }
    },

    /**
     * Cerrar turno de caja
     */
    async cerrarTurno() {
        const { value: formValues } = await Swal.fire({
            title: 'Cerrar Turno de Caja',
            html: `
                <div class="text-start">
                    <div class="mb-3">
                        <label class="form-label">Monto Final en Efectivo</label>
                        <input type="number" id="monto-efectivo" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea id="observaciones" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Cerrar Turno',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#EF4444',
            preConfirm: () => {
                const montoEfectivo = document.getElementById('monto-efectivo').value;
                const observaciones = document.getElementById('observaciones').value;

                if (!montoEfectivo || parseFloat(montoEfectivo) < 0) {
                    Swal.showValidationMessage('Ingrese un monto válido');
                    return false;
                }

                return {
                    monto_efectivo: parseFloat(montoEfectivo),
                    observaciones: observaciones
                };
            }
        });

        if (!formValues) return;

        try {
            const response = await fetch('gerente/ajax/cerrar-turno.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formValues)
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Turno Cerrado',
                    html: `
                        <div class="text-start">
                            <p><strong>Monto Esperado:</strong> $${data.resumen.monto_esperado.toLocaleString('es-AR')}</p>
                            <p><strong>Monto Real:</strong> $${data.resumen.monto_real.toLocaleString('es-AR')}</p>
                            <p><strong>Diferencia:</strong> <span class="${data.resumen.diferencia >= 0 ? 'text-success' : 'text-danger'}">
                                $${data.resumen.diferencia.toLocaleString('es-AR')}
                            </span></p>
                        </div>
                    `,
                    confirmButtonColor: '#10B981'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                throw new Error(data.mensaje);
            }

        } catch (error) {
            console.error('Error:', error);
            this.mostrarAlerta(error.message, 'error');
        }
    },

    /**
     * Registrar venta
     */
    async registrarVenta() {
        const form = document.getElementById('formRegistrarVenta');
        const formData = new FormData(form);

        try {
            const response = await fetch('gerente/ajax/registrar-venta.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarAlerta(data.mensaje, 'success');
                form.reset();
                this.actualizarResumenCaja();
            } else {
                throw new Error(data.mensaje);
            }

        } catch (error) {
            console.error('Error:', error);
            this.mostrarAlerta(error.message, 'error');
        }
    },

    /**
     * Registrar gasto
     */
    async registrarGasto() {
        const form = document.getElementById('formRegistrarGasto');
        const formData = new FormData(form);

        try {
            const response = await fetch('gerente/ajax/registrar-gasto.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarAlerta(data.mensaje, 'success');
                form.reset();
                this.actualizarResumenCaja();

                // Cerrar modal si existe
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalRegistrarGasto'));
                if (modal) modal.hide();
            } else {
                throw new Error(data.mensaje);
            }

        } catch (error) {
            console.error('Error:', error);
            this.mostrarAlerta(error.message, 'error');
        }
    },

    /**
     * Registrar cobro de cuota
     */
    async registrarCobroCuota() {
        const form = document.getElementById('formCobrarCuota');
        const formData = new FormData(form);

        try {
            const response = await fetch('gerente/ajax/registrar-cobro-cuota.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarAlerta(data.mensaje, 'success');
                form.reset();
                this.actualizarResumenCaja();

                // Cerrar modal si existe
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalCobrarCuota'));
                if (modal) modal.hide();
            } else {
                throw new Error(data.mensaje);
            }

        } catch (error) {
            console.error('Error:', error);
            this.mostrarAlerta(error.message, 'error');
        }
    },

    /**
     * Buscar productos
     */
    async buscarProductos(termino) {
        if (termino.length < 2) {
            document.getElementById('resultadosBusqueda').innerHTML = '';
            return;
        }

        try {
            const response = await fetch(`gerente/ajax/buscar-productos.php?q=${encodeURIComponent(termino)}`);
            const data = await response.json();

            if (data.success && data.productos) {
                this.mostrarResultadosBusqueda(data.productos);
            }

        } catch (error) {
            console.error('Error al buscar productos:', error);
        }
    },

    /**
     * Mostrar resultados de búsqueda
     */
    mostrarResultadosBusqueda(productos) {
        const contenedor = document.getElementById('resultadosBusqueda');
        if (!contenedor) return;

        if (productos.length === 0) {
            contenedor.innerHTML = '<div class="alert alert-info">No se encontraron productos</div>';
            return;
        }

        const html = productos.map(producto => `
            <div class="list-group-item list-group-item-action" onclick="agregarProductoVenta(${producto.id}, '${producto.nombre}', ${producto.precio})">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">${producto.nombre}</h6>
                        <small class="text-muted">Stock: ${producto.stock}</small>
                    </div>
                    <span class="badge bg-primary">$${producto.precio.toLocaleString('es-AR')}</span>
                </div>
            </div>
        `).join('');

        contenedor.innerHTML = `<div class="list-group">${html}</div>`;
    },

    /**
     * Actualizar resumen de caja
     */
    async actualizarResumenCaja() {
        try {
            const response = await fetch('gerente/ajax/obtener-resumen-caja.php');
            const data = await response.json();

            if (data.success) {
                // Actualizar elementos del DOM
                const elementos = {
                    'total-ventas': data.total_ventas,
                    'total-gastos': data.total_gastos,
                    'total-cobros': data.total_cobros,
                    'saldo-caja': data.saldo
                };

                Object.entries(elementos).forEach(([id, valor]) => {
                    const elemento = document.getElementById(id);
                    if (elemento) {
                        elemento.textContent = '$' + valor.toLocaleString('es-AR');
                    }
                });
            }

        } catch (error) {
            console.error('Error al actualizar resumen:', error);
        }
    },

    /**
     * Mostrar panel de caja abierta
     */
    mostrarPanelCaja(turno) {
        const contenedor = document.getElementById('contenedor-caja');
        if (contenedor) {
            contenedor.classList.remove('d-none');
        }

        const panelAbrir = document.getElementById('panel-abrir-turno');
        if (panelAbrir) {
            panelAbrir.classList.add('d-none');
        }
    },

    /**
     * Mostrar panel para abrir turno
     */
    mostrarPanelAbrirTurno() {
        const contenedor = document.getElementById('contenedor-caja');
        if (contenedor) {
            contenedor.classList.add('d-none');
        }

        const panelAbrir = document.getElementById('panel-abrir-turno');
        if (panelAbrir) {
            panelAbrir.classList.remove('d-none');
        }
    },

    /**
     * Mostrar alerta
     */
    mostrarAlerta(mensaje, tipo = 'info') {
        const iconos = {
            success: 'success',
            error: 'error',
            warning: 'warning',
            info: 'info'
        };

        Swal.fire({
            icon: iconos[tipo] || 'info',
            title: tipo === 'success' ? 'Éxito' : tipo === 'error' ? 'Error' : 'Atención',
            text: mensaje,
            confirmButtonColor: '#10B981',
            timer: tipo === 'success' ? 2000 : undefined
        });
    }
};

// =============================================================================
// FUNCIONES AUXILIARES
// =============================================================================

/**
 * Agregar producto a la venta actual
 */
function agregarProductoVenta(id, nombre, precio) {
    const tabla = document.getElementById('tabla-productos-venta');
    if (!tabla) return;

    // Verificar si el producto ya está en la lista
    const filaExistente = tabla.querySelector(`tr[data-producto-id="${id}"]`);
    if (filaExistente) {
        const inputCantidad = filaExistente.querySelector('input[name="cantidad[]"]');
        if (inputCantidad) {
            inputCantidad.value = parseInt(inputCantidad.value) + 1;
            calcularTotalVenta();
        }
        return;
    }

    // Agregar nueva fila
    const fila = `
        <tr data-producto-id="${id}">
            <td>
                ${nombre}
                <input type="hidden" name="producto_id[]" value="${id}">
                <input type="hidden" name="precio[]" value="${precio}">
            </td>
            <td>
                <input type="number" name="cantidad[]" class="form-control form-control-sm" value="1" min="1" style="width:80px;" onchange="calcularTotalVenta()">
            </td>
            <td class="text-end">$${precio.toLocaleString('es-AR')}</td>
            <td class="text-end subtotal">$${precio.toLocaleString('es-AR')}</td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="eliminarProductoVenta(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `;

    tabla.insertAdjacentHTML('beforeend', fila);
    calcularTotalVenta();

    // Limpiar búsqueda
    document.getElementById('buscarProducto').value = '';
    document.getElementById('resultadosBusqueda').innerHTML = '';
}

/**
 * Eliminar producto de la venta
 */
function eliminarProductoVenta(btn) {
    btn.closest('tr').remove();
    calcularTotalVenta();
}

/**
 * Calcular total de la venta
 */
function calcularTotalVenta() {
    const filas = document.querySelectorAll('#tabla-productos-venta tr');
    let total = 0;

    filas.forEach(fila => {
        const precio = parseFloat(fila.querySelector('input[name="precio[]"]')?.value) || 0;
        const cantidad = parseInt(fila.querySelector('input[name="cantidad[]"]')?.value) || 0;
        const subtotal = precio * cantidad;

        const elementoSubtotal = fila.querySelector('.subtotal');
        if (elementoSubtotal) {
            elementoSubtotal.textContent = '$' + subtotal.toLocaleString('es-AR');
        }

        total += subtotal;
    });

    const elementoTotal = document.getElementById('total-venta');
    if (elementoTotal) {
        elementoTotal.textContent = '$' + total.toLocaleString('es-AR');
    }
}

// =============================================================================
// INICIALIZACIÓN
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    // Inicializar si estamos en la página de caja
    if (document.getElementById('contenedor-caja') ||
        document.getElementById('panel-abrir-turno')) {
        CajaManager.init();
    }
});

// Exponer funciones globales
window.CajaManager = CajaManager;
window.agregarProductoVenta = agregarProductoVenta;
window.eliminarProductoVenta = eliminarProductoVenta;
window.calcularTotalVenta = calcularTotalVenta;
