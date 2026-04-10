/**
 * MAIN.JS - JAVASCRIPT PRINCIPAL (VERSIÓN CORREGIDA)
 * 
 * CORRECCIONES:
 * 1. Manejo de usuario no logueado en carrito
 * 2. Alertas mejoradas con iconos
 * 3. Funcionalidad de favoritos corregida
 */

// La inicialización completa está al final del archivo

// ===========================================
// FUNCIONALIDAD DE CARRITO (CORREGIDA)
// ===========================================

function inicializarCarrito() {
    const botonesAgregar = document.querySelectorAll('.btn-add-cart');
    
    botonesAgregar.forEach(boton => {
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productoId = this.dataset.id;
            const productoNombre = this.dataset.nombre;
            const productoPrecio = parseFloat(this.dataset.precio);
            const productoImagen = this.dataset.imagen;
            
            if (!productoId || !productoNombre || isNaN(productoPrecio)) {
                mostrarAlerta('Error al agregar el producto', 'danger');
                return;
            }
            
            const producto = {
                id: productoId,
                nombre: productoNombre,
                precio: productoPrecio,
                imagen: productoImagen,
                cantidad: 1
            };
            
            agregarAlCarrito(producto, this);
        });
    });
}

/**
 * CORRECCIÓN: Agregar producto al carrito con validación de login
 */
function agregarAlCarrito(producto, boton) {
    // Deshabilitar botón temporalmente
    boton.disabled = true;
    const textoOriginal = boton.innerHTML;
    boton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Agregando...';
    
    fetch('ajax/agregar-carrito.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(producto)
    })
    .then(response => response.json())
    .then(data => {
        boton.disabled = false;
        boton.innerHTML = textoOriginal;
        
        // CORRECCIÓN: Verificar si requiere login
        if (data.requiere_login) {
            mostrarAlerta(data.mensaje, 'warning');
            
            // Mostrar modal de confirmación
            if (confirm('¿Deseas crear una cuenta o iniciar sesión para agregar productos al carrito?')) {
                window.location.href = data.redirect;
            }
            return;
        }
        
        if (data.success) {
            // CORRECCIÓN: Alerta mejorada con icono
            mostrarAlerta('✓ ' + data.mensaje, 'success');
            actualizarContadorCarrito(data.cantidad_total);
            
            // Efecto visual en el botón
            const icono = boton.querySelector('i');
            if (icono) {
                const iconoOriginal = icono.className;
                icono.className = 'bi bi-check-circle-fill me-2';
                
                setTimeout(() => {
                    icono.className = iconoOriginal;
                }, 2000);
            }
        } else {
            mostrarAlerta(data.mensaje || 'Error al agregar producto', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        boton.disabled = false;
        boton.innerHTML = textoOriginal;
        mostrarAlerta('Error de conexión. Intenta nuevamente.', 'danger');
    });
}

/**
 * Actualizar contador visual del carrito
 */
function actualizarContadorCarrito(cantidad) {
    let badge = document.querySelector('.btn-outline-danger .badge');
    
    if (badge) {
        badge.textContent = cantidad;
        
        // Animación de pulso
        badge.classList.add('animate__animated', 'animate__pulse');
        setTimeout(() => {
            badge.classList.remove('animate__animated', 'animate__pulse');
        }, 1000);
    } else if (cantidad > 0) {
        // Si no existe el badge, crearlo
        const btnCarrito = document.querySelector('.btn-outline-danger');
        if (btnCarrito) {
            const nuevoBadge = document.createElement('span');
            nuevoBadge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
            nuevoBadge.textContent = cantidad;
            btnCarrito.appendChild(nuevoBadge);
        }
    }
}

// ===========================================
// FUNCIONALIDAD DE FAVORITOS (CORREGIDA)
// ===========================================

function inicializarFavoritos() {
    const botonesFavoritos = document.querySelectorAll('.btn-add-favorite');
    
    botonesFavoritos.forEach(boton => {
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productoId = this.dataset.id;
            
            if (!productoId) {
                mostrarAlerta('Error al agregar a favoritos', 'danger');
                return;
            }
            
            agregarAFavoritos(productoId, this);
        });
    });
}

/**
 * CORRECCIÓN: Agregar producto a favoritos
 */
function agregarAFavoritos(productoId, boton) {
    // Verificar si ya está en favoritos
    const esFavorito = boton.classList.contains('active');
    
    fetch('ajax/agregar-favorito.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            producto_id: productoId,
            accion: esFavorito ? 'eliminar' : 'agregar'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.requiere_login) {
            mostrarAlerta('Debes iniciar sesión para usar favoritos', 'warning');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1500);
            return;
        }
        
        if (data.success) {
            // CORRECCIÓN: Cambiar estado visual del corazón
            const icono = boton.querySelector('i');
            if (data.accion === 'agregado') {
                icono.classList.remove('bi-heart');
                icono.classList.add('bi-heart-fill');
                boton.classList.add('active', 'text-danger');
                mostrarAlerta('❤️ Agregado a favoritos', 'success');
            } else {
                icono.classList.remove('bi-heart-fill');
                icono.classList.add('bi-heart');
                boton.classList.remove('active', 'text-danger');
                mostrarAlerta('Eliminado de favoritos', 'info');
            }
        } else {
            mostrarAlerta(data.mensaje || 'Error al procesar favorito', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarAlerta('Error de conexión', 'danger');
    });
}

// ===========================================
// FUNCIONALIDAD DE BUSCADOR
// ===========================================

function inicializarBuscador() {
    const buscador = document.querySelector('input[name="q"]');
    if (buscador) {
        buscador.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const valor = this.value.trim();
                if (valor.length < 3) {
                    e.preventDefault();
                    mostrarAlerta('Ingresa al menos 3 caracteres para buscar', 'warning');
                }
            }
        });
    }
}

// ===========================================
// UTILIDADES Y VALIDACIONES
// ===========================================

/**
 * CORRECCIÓN: Mostrar alerta mejorada con Bootstrap
 */
function mostrarAlerta(mensaje, tipo = 'info') {
    // Remover alertas anteriores
    const alertasExistentes = document.querySelectorAll('.alert-flotante');
    alertasExistentes.forEach(alerta => alerta.remove());
    
    // Definir iconos según tipo
    const iconos = {
        'success': 'bi-check-circle-fill',
        'danger': 'bi-exclamation-triangle-fill',
        'warning': 'bi-exclamation-circle-fill',
        'info': 'bi-info-circle-fill'
    };
    
    // Crear alerta
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
    
    // Auto-eliminar después de 4 segundos
    setTimeout(() => {
        alerta.classList.remove('show');
        setTimeout(() => alerta.remove(), 300);
    }, 4000);
}

/**
 * Formatear número a moneda argentina
 */
function formatearMoneda(numero) {
    return '$' + new Intl.NumberFormat('es-AR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(numero);
}

/**
 * Validar email
 */
function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Validar teléfono argentino
 */
function validarTelefono(telefono) {
    const regex = /^(\+?54)?[\s\-]?(\d{2,4})[\s\-]?(\d{6,8})$/;
    return regex.test(telefono);
}

// ===========================================
// OBJETO GLOBAL
// ===========================================

window.MauroCalzado = {
    formatearMoneda: formatearMoneda,
    validarEmail: validarEmail,
    validarTelefono: validarTelefono,
    mostrarAlerta: mostrarAlerta
};

// ===========================================
// MANEJO DE ERRORES GLOBAL
// ===========================================

window.addEventListener('error', function(e) {
    console.error('Error capturado:', e.error);
    if (!e.error?.message?.includes('Script error')) {
        mostrarAlerta('Ocurrió un error inesperado.', 'danger');
    }
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('Promesa rechazada:', e.reason);
    mostrarAlerta('Error de conexión. Verifica tu internet.', 'warning');
});

// ===========================================
// SMOOTH SCROLL
// ===========================================

/**
 * Smooth scroll para enlaces con anclas
 */
function inicializarSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');

            // Ignorar enlaces que solo tienen #
            if (href === '#') return;

            e.preventDefault();
            const target = document.querySelector(href);

            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// ===========================================
// ANIMACIONES DE ENTRADA
// ===========================================

/**
 * Observador de intersección para animaciones
 */
function inicializarAnimaciones() {
    const elementos = document.querySelectorAll('.animate-on-scroll');

    if (elementos.length === 0) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });

    elementos.forEach(el => observer.observe(el));
}

// ===========================================
// INICIALIZACIÓN ADICIONAL
// ===========================================

document.addEventListener('DOMContentLoaded', function() {
    // Funcionalidades existentes
    inicializarCarrito();
    inicializarFavoritos();
    inicializarBuscador();

    // Nuevas funcionalidades
    inicializarSmoothScroll();
    inicializarAnimaciones();
});

// ===========================================
// MENSAJE DE CONSOLA
// ===========================================

console.log('%c¡Bienvenido a Mauro Calzado!', 'color: #0047AB; font-size: 20px; font-weight: bold;');
console.log('%cSistema corregido y optimizado - Versión 2.0', 'color: #DC143C; font-size: 12px;');
