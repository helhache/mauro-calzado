/**
 * PRODUCTO-DETALLE.JS - PÁGINA DE DETALLE DEL PRODUCTO
 *
 * Funcionalidad para:
 * - Galería de imágenes
 * - Selector de color y talle
 * - Control de cantidad
 * - Agregar al carrito con validaciones
 * - Favoritos
 *
 * Dependencias: Bootstrap 5
 */

'use strict';

// =============================================================================
// GESTIÓN DE PRODUCTO INDIVIDUAL
// =============================================================================

const ProductoDetalle = {

    // Estado del producto
    colorSeleccionado: null,
    talleSeleccionado: null,

    /**
     * Inicializar módulo
     */
    init() {
        this.inicializarEventos();
        this.inicializarGaleria();
    },

    /**
     * Configurar event listeners
     */
    inicializarEventos() {
        // Submit del formulario de agregar al carrito
        const form = document.getElementById('form-agregar-carrito');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.agregarAlCarrito();
            });
        }

        // Botones de cantidad
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-action="decrementar"]')) {
                e.preventDefault();
                this.cambiarCantidad(-1);
            }

            if (e.target.closest('[data-action="incrementar"]')) {
                e.preventDefault();
                this.cambiarCantidad(1);
            }
        });

        // Smooth scroll para guía de talles
        const enlaceGuia = document.querySelector('a[href="#guia-talles"]');
        if (enlaceGuia) {
            enlaceGuia.addEventListener('click', (e) => {
                e.preventDefault();
                const modal = document.getElementById('modalGuiaTalles');
                if (modal) {
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show();
                }
            });
        }
    },

    /**
     * Inicializar galería de imágenes
     */
    inicializarGaleria() {
        const miniaturas = document.querySelectorAll('.miniatura-producto');

        miniaturas.forEach(miniatura => {
            miniatura.addEventListener('click', function () {
                ProductoDetalle.cambiarImagenPrincipal(this.src);
            });
        });

        // Zoom en imagen principal (opcional - requiere librería externa)
        const imagenPrincipal = document.getElementById('imagen-principal');
        if (imagenPrincipal) {
            imagenPrincipal.addEventListener('click', function () {
                // Aquí se podría implementar un lightbox
                console.log('Click en imagen - implementar lightbox si se desea');
            });
        }
    },

    /**
     * Cambiar imagen principal de la galería
     */
    cambiarImagenPrincipal(src) {
        const imagenPrincipal = document.getElementById('imagen-principal');
        if (!imagenPrincipal) return;

        imagenPrincipal.src = src;

        // Actualizar clase active en miniaturas
        document.querySelectorAll('.miniatura-producto').forEach(img => {
            img.classList.remove('active');
            if (img.src === src) {
                img.classList.add('active');
            }
        });

        // Animación de fade
        imagenPrincipal.style.opacity = '0';
        setTimeout(() => {
            imagenPrincipal.style.opacity = '1';
        }, 100);
    },

    /**
     * Seleccionar color
     */
    seleccionarColor(elemento, color) {
        // Remover selección anterior
        document.querySelectorAll('.color-option').forEach(el => {
            el.style.border = '3px solid #dee2e6';
            el.style.transform = 'scale(1)';
        });

        // Aplicar selección
        elemento.style.border = '3px solid var(--color-azul, #0047AB)';
        elemento.style.transform = 'scale(1.1)';

        // Guardar selección
        this.colorSeleccionado = color;
        const inputColor = document.getElementById('input-color');
        if (inputColor) {
            inputColor.value = color;
        }

        const textoColor = document.getElementById('color-seleccionado');
        if (textoColor) {
            textoColor.textContent = color;
            textoColor.classList.remove('text-primary');
            textoColor.classList.add('text-success');
        }
    },

    /**
     * Seleccionar talle
     */
    seleccionarTalle(elemento, talle) {
        // Remover selección anterior
        document.querySelectorAll('.talle-option').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-secondary');
        });

        // Aplicar selección
        elemento.classList.remove('btn-outline-secondary');
        elemento.classList.add('btn-primary');

        // Guardar selección
        this.talleSeleccionado = talle;
        const inputTalle = document.getElementById('input-talle');
        if (inputTalle) {
            inputTalle.value = talle;
        }

        const textoTalle = document.getElementById('talle-seleccionado');
        if (textoTalle) {
            textoTalle.textContent = talle;
            textoTalle.classList.remove('text-primary');
            textoTalle.classList.add('text-success');
        }
    },

    /**
     * Cambiar cantidad
     */
    cambiarCantidad(delta) {
        const input = document.getElementById('cantidad');
        if (!input) return;

        let valor = parseInt(input.value) + delta;
        const max = parseInt(input.max) || 999;

        if (valor < 1) valor = 1;
        if (valor > max) {
            valor = max;
            this.mostrarAlerta('Stock máximo alcanzado', 'warning');
        }

        input.value = valor;
    },

    /**
     * Agregar producto al carrito
     */
    async agregarAlCarrito() {
        // Validar color
        if (!this.colorSeleccionado) {
            this.mostrarAlerta('Por favor selecciona un color', 'warning');
            const selectorColores = document.getElementById('selector-colores');
            if (selectorColores) {
                selectorColores.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        // Validar talle
        if (!this.talleSeleccionado) {
            this.mostrarAlerta('Por favor selecciona un talle', 'warning');
            const selectorTalles = document.getElementById('selector-talles');
            if (selectorTalles) {
                selectorTalles.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        // Obtener datos del producto
        const productoId = document.getElementById('producto_id')?.value;
        const cantidad = parseInt(document.getElementById('cantidad')?.value) || 1;

        if (!productoId) {
            this.mostrarAlerta('Error: Producto no válido', 'danger');
            return;
        }

        const producto = {
            id: productoId,
            cantidad: cantidad,
            talle: this.talleSeleccionado,
            color: this.colorSeleccionado
        };

        // Enviar al servidor
        try {
            const response = await fetch('ajax/agregar-carrito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(producto)
            });

            const data = await response.json();

            if (data.requiere_login) {
                this.mostrarAlerta(data.mensaje, 'warning');
                setTimeout(() => {
                    const redirect = encodeURIComponent(window.location.href);
                    window.location.href = `login.php?redirect=${redirect}`;
                }, 2000);
                return;
            }

            if (data.success) {
                this.mostrarAlerta(data.mensaje, 'success');

                // Actualizar contador del carrito
                if (typeof actualizarContadorCarrito === 'function') {
                    actualizarContadorCarrito(data.cantidad_total);
                }

                // Preguntar si desea ir al carrito
                setTimeout(() => {
                    const irCarrito = confirm('Producto agregado correctamente. ¿Deseas ir al carrito?');
                    if (irCarrito) {
                        window.location.href = 'carrito.php';
                    }
                }, 1000);
            } else {
                this.mostrarAlerta(data.mensaje || 'Error al agregar al carrito', 'danger');
            }

        } catch (error) {
            console.error('Error:', error);
            this.mostrarAlerta('Error de conexión. Intenta nuevamente.', 'danger');
        }
    },

    /**
     * Agregar a favoritos
     */
    async agregarAFavoritos(productoId) {
        try {
            const response = await fetch('ajax/agregar-favorito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ producto_id: productoId })
            });

            const data = await response.json();

            if (data.requiere_login) {
                this.mostrarAlerta('Debes iniciar sesión para usar favoritos', 'warning');
                setTimeout(() => {
                    const redirect = encodeURIComponent(window.location.href);
                    window.location.href = `login.php?redirect=${redirect}`;
                }, 1500);
                return;
            }

            if (data.success) {
                this.mostrarAlerta(data.mensaje, 'success');
            } else {
                this.mostrarAlerta(data.mensaje || 'Error al procesar favorito', 'danger');
            }

        } catch (error) {
            console.error('Error:', error);
            this.mostrarAlerta('Error de conexión', 'danger');
        }
    },

    /**
     * Mostrar alerta
     */
    mostrarAlerta(mensaje, tipo = 'info') {
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
    }
};

// =============================================================================
// INICIALIZACIÓN
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    // Verificar si estamos en la página de producto
    if (document.getElementById('form-agregar-carrito') ||
        document.querySelector('.producto-detalle')) {
        ProductoDetalle.init();
    }
});

// Exponer funciones globales para compatibilidad
window.cambiarImagenPrincipal = (src) => ProductoDetalle.cambiarImagenPrincipal(src);
window.seleccionarColor = (el, color) => ProductoDetalle.seleccionarColor(el, color);
window.seleccionarTalle = (el, talle) => ProductoDetalle.seleccionarTalle(el, talle);
window.cambiarCantidad = (delta) => ProductoDetalle.cambiarCantidad(delta);
window.agregarAFavoritos = (id) => ProductoDetalle.agregarAFavoritos(id);
