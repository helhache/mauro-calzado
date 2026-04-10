/**
 * CARRITO.JS - FUNCIONALIDAD DEL CARRITO DE COMPRAS
 *
 * Gestión de:
 * - Modificar cantidades
 * - Eliminar productos
 * - Actualizar totales
 * - Auto-submit de formularios
 *
 * Dependencias: Bootstrap 5
 */

'use strict';

// =============================================================================
// GESTIÓN DEL CARRITO
// =============================================================================

const CarritoManager = {

    /**
     * Inicializar módulo del carrito
     */
    init() {
        this.inicializarEventos();
        this.calcularTotales();
    },

    /**
     * Configurar event listeners
     */
    inicializarEventos() {
        // Auto-submit al cambiar cantidad
        const inputsCantidad = document.querySelectorAll('input[name="cantidad"]');
        inputsCantidad.forEach(input => {
            input.addEventListener('change', function () {
                if (this.value < 1) this.value = 1;

                // Debounce para evitar múltiples submits
                clearTimeout(this.submitTimer);
                this.submitTimer = setTimeout(() => {
                    this.closest('form').submit();
                }, 500);
            });

            // Prevenir valores negativos
            input.addEventListener('input', function () {
                if (this.value < 0) this.value = 0;
            });
        });

        // Confirmar eliminación
        const botonesEliminar = document.querySelectorAll('button[name="eliminar"]');
        botonesEliminar.forEach(boton => {
            boton.addEventListener('click', function (e) {
                const confirmacion = confirm('¿Eliminar este producto del carrito?');
                if (!confirmacion) {
                    e.preventDefault();
                }
            });
        });

        // Confirmar vaciar carrito
        const botonVaciar = document.querySelector('button[name="vaciar_carrito"]');
        if (botonVaciar) {
            botonVaciar.addEventListener('click', function (e) {
                const confirmacion = confirm('¿Estás seguro de vaciar el carrito? Esta acción no se puede deshacer.');
                if (!confirmacion) {
                    e.preventDefault();
                }
            });
        }

        // Botones de incremento/decremento
        this.inicializarControlesCantidad();
    },

    /**
     * Inicializar controles de cantidad (+/-)
     */
    inicializarControlesCantidad() {
        document.addEventListener('click', (e) => {
            // Botón decrementar
            if (e.target.closest('.btn-decrementar')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-decrementar');
                const input = btn.parentElement.querySelector('input[name="cantidad"]');

                if (input) {
                    let valor = parseInt(input.value) || 1;
                    if (valor > 1) {
                        input.value = valor - 1;
                        input.dispatchEvent(new Event('change'));
                    }
                }
            }

            // Botón incrementar
            if (e.target.closest('.btn-incrementar')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-incrementar');
                const input = btn.parentElement.querySelector('input[name="cantidad"]');

                if (input) {
                    const max = parseInt(input.getAttribute('max')) || 999;
                    let valor = parseInt(input.value) || 0;

                    if (valor < max) {
                        input.value = valor + 1;
                        input.dispatchEvent(new Event('change'));
                    } else {
                        this.mostrarAlerta('Stock máximo alcanzado', 'warning');
                    }
                }
            }
        });
    },

    /**
     * Calcular y mostrar totales
     */
    calcularTotales() {
        let subtotal = 0;
        const filas = document.querySelectorAll('.fila-producto');

        filas.forEach(fila => {
            const precio = parseFloat(fila.dataset.precio) || 0;
            const cantidad = parseInt(fila.querySelector('input[name="cantidad"]')?.value) || 0;
            const subtotalProducto = precio * cantidad;

            subtotal += subtotalProducto;

            // Actualizar subtotal del producto si existe el elemento
            const elementoSubtotal = fila.querySelector('.subtotal-producto');
            if (elementoSubtotal) {
                elementoSubtotal.textContent = this.formatearMoneda(subtotalProducto);
            }
        });

        // Actualizar subtotal general
        const elementoSubtotal = document.getElementById('subtotal-carrito');
        if (elementoSubtotal) {
            elementoSubtotal.textContent = this.formatearMoneda(subtotal);
        }

        // Calcular envío
        const costoEnvio = subtotal >= 50000 ? 0 : 5000;
        const elementoEnvio = document.getElementById('costo-envio');
        if (elementoEnvio) {
            elementoEnvio.textContent = costoEnvio === 0 ? '¡GRATIS!' : this.formatearMoneda(costoEnvio);
        }

        // Calcular total
        const total = subtotal + costoEnvio;
        const elementoTotal = document.getElementById('total-carrito');
        if (elementoTotal) {
            elementoTotal.textContent = this.formatearMoneda(total);
        }

        // Mostrar cuánto falta para envío gratis
        if (subtotal < 50000 && costoEnvio > 0) {
            const faltante = 50000 - subtotal;
            const mensajeEnvio = document.getElementById('mensaje-envio-gratis');
            if (mensajeEnvio) {
                mensajeEnvio.innerHTML = `
                    <i class="bi bi-info-circle me-1"></i>
                    Te faltan <strong>${this.formatearMoneda(faltante)}</strong> para envío gratis
                `;
                mensajeEnvio.classList.remove('d-none');
            }
        }
    },

    /**
     * Formatear número como moneda
     */
    formatearMoneda(numero) {
        return '$' + new Intl.NumberFormat('es-AR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(numero);
    },

    /**
     * Mostrar alerta
     */
    mostrarAlerta(mensaje, tipo = 'info') {
        // Remover alertas anteriores
        const alertasExistentes = document.querySelectorAll('.alert-flotante');
        alertasExistentes.forEach(alerta => alerta.remove());

        const iconos = {
            'success': 'bi-check-circle-fill',
            'danger': 'bi-exclamation-triangle-fill',
            'warning': 'bi-exclamation-circle-fill',
            'info': 'bi-info-circle-fill'
        };

        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo} alert-dismissible fade show alert-flotante position-fixed top-0 start-50 translate-middle-x mt-3 shadow-lg`;
        alerta.style.zIndex = '9999';
        alerta.style.minWidth = '300px';
        alerta.innerHTML = `
            <i class="bi ${iconos[tipo]} me-2"></i>
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(alerta);

        setTimeout(() => {
            alerta.classList.remove('show');
            setTimeout(() => alerta.remove(), 300);
        }, 4000);
    },

    /**
     * Actualizar contador del badge del carrito en el header
     */
    actualizarContador() {
        const filas = document.querySelectorAll('.fila-producto');
        let totalItems = 0;

        filas.forEach(fila => {
            const cantidad = parseInt(fila.querySelector('input[name="cantidad"]')?.value) || 0;
            totalItems += cantidad;
        });

        const badge = document.querySelector('.btn-outline-danger .badge');
        if (badge) {
            badge.textContent = totalItems;

            // Animación de pulso
            badge.classList.add('animate__animated', 'animate__pulse');
            setTimeout(() => {
                badge.classList.remove('animate__animated', 'animate__pulse');
            }, 1000);
        }
    }
};

// =============================================================================
// INICIALIZACIÓN
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    // Verificar si estamos en la página del carrito
    if (document.querySelector('.tabla-carrito') || document.querySelector('.carrito-contenedor')) {
        CarritoManager.init();
    }
});

// Exponer funciones globales
window.CarritoManager = CarritoManager;
