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

// Mapa de colores para los círculos del modal
const MAPA_COLORES = {
    'Negro':'#000000','Blanco':'#FFFFFF','Rojo':'#DC143C','Azul':'#0047AB',
    'Gris':'#808080','Marrón':'#8B4513','Beige':'#F5F5DC','Rosa':'#FFC0CB',
    'Verde':'#228B22','Amarillo':'#FFD700'
};

// Estado del modal de carrito
let _modalCarritoData = null;

function inicializarCarrito() {
    document.addEventListener('click', function(e) {
        const boton = e.target.closest('.btn-add-cart');
        if (!boton) return;
        e.preventDefault();

        const productoId = boton.dataset.id;
        if (!productoId) return;

        // Cargar info del producto y mostrar modal
        boton.disabled = true;
        const textoOriginal = boton.innerHTML;
        boton.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        fetch(`${window.BASE_URL}ajax/obtener-producto-carrito.php?id=${productoId}`)
            .then(r => r.json())
            .then(data => {
                boton.disabled = false;
                boton.innerHTML = textoOriginal;
                if (!data.success) { mostrarAlerta(data.mensaje || 'Error', 'danger'); return; }
                abrirModalCarrito(data);
            })
            .catch(() => {
                boton.disabled = false;
                boton.innerHTML = textoOriginal;
                mostrarAlerta('Error de conexión', 'danger');
            });
    });

    // Confirmar desde el modal
    const btnConfirmar = document.getElementById('btn-confirmar-carrito');
    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', confirmarAgregarCarrito);
    }
}

function abrirModalCarrito(producto) {
    _modalCarritoData = { ...producto, colorSeleccionado: null, talleSeleccionado: null };

    document.getElementById('modal-carrito-nombre').textContent = producto.nombre;
    document.getElementById('modal-carrito-precio').textContent =
        '$' + new Intl.NumberFormat('es-AR').format(producto.precio);

    const img = document.getElementById('modal-carrito-imagen');
    img.src = producto.imagen ? `img/productos/${producto.imagen}` : '';

    // Colores
    const bloqueColor = document.getElementById('modal-bloque-color');
    const selectorColores = document.getElementById('modal-selector-colores');
    selectorColores.innerHTML = '';
    if (producto.colores && producto.colores.length > 0) {
        bloqueColor.style.display = '';
        producto.colores.forEach(color => {
            const codigo = MAPA_COLORES[color] || '#CCCCCC';
            const div = document.createElement('div');
            div.className = 'color-option';
            div.style.backgroundColor = codigo;
            div.title = color;
            div.dataset.color = color;
            div.addEventListener('click', function() {
                selectorColores.querySelectorAll('.color-option').forEach(el => {
                    el.style.border = '3px solid #dee2e6';
                    el.style.transform = 'scale(1)';
                });
                this.style.border = '3px solid #0047AB';
                this.style.transform = 'scale(1.1)';
                _modalCarritoData.colorSeleccionado = color;
                document.getElementById('modal-color-texto').textContent = color;
                document.getElementById('modal-color-texto').className = 'text-success';
            });
            selectorColores.appendChild(div);
        });
    } else {
        bloqueColor.style.display = 'none';
    }
    document.getElementById('modal-color-texto').textContent = 'Seleccioná uno';
    document.getElementById('modal-color-texto').className = 'text-primary';

    // Talles
    const bloqueTalle = document.getElementById('modal-bloque-talle');
    const selectorTalles = document.getElementById('modal-selector-talles');
    selectorTalles.innerHTML = '';
    if (producto.talles && producto.talles.length > 0) {
        bloqueTalle.style.display = '';
        producto.talles.forEach(talle => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-outline-secondary talle-option';
            btn.textContent = talle;
            btn.dataset.talle = talle;
            btn.addEventListener('click', function() {
                selectorTalles.querySelectorAll('.talle-option').forEach(b => {
                    b.classList.remove('btn-primary');
                    b.classList.add('btn-outline-secondary');
                });
                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-primary');
                _modalCarritoData.talleSeleccionado = talle;
                document.getElementById('modal-talle-texto').textContent = talle;
                document.getElementById('modal-talle-texto').className = 'text-success';
            });
            selectorTalles.appendChild(btn);
        });
    } else {
        bloqueTalle.style.display = 'none';
    }
    document.getElementById('modal-talle-texto').textContent = 'Seleccioná uno';
    document.getElementById('modal-talle-texto').className = 'text-primary';

    document.getElementById('modal-carrito-error').classList.add('d-none');

    const modal = new bootstrap.Modal(document.getElementById('modalSeleccionCarrito'));
    modal.show();
}

function confirmarAgregarCarrito() {
    if (!_modalCarritoData) return;

    const errorDiv = document.getElementById('modal-carrito-error');
    errorDiv.classList.add('d-none');

    // Validar color si el producto tiene colores
    const selectorColores = document.getElementById('modal-selector-colores');
    if (selectorColores.children.length > 0 && !_modalCarritoData.colorSeleccionado) {
        errorDiv.textContent = 'Por favor seleccioná un color.';
        errorDiv.classList.remove('d-none');
        return;
    }

    // Validar talle si el producto tiene talles
    const selectorTalles = document.getElementById('modal-selector-talles');
    if (selectorTalles.children.length > 0 && !_modalCarritoData.talleSeleccionado) {
        errorDiv.textContent = 'Por favor seleccioná un talle.';
        errorDiv.classList.remove('d-none');
        return;
    }

    const btn = document.getElementById('btn-confirmar-carrito');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Agregando...';

    const payload = {
        id:      _modalCarritoData.id,
        cantidad: 1,
        color:   _modalCarritoData.colorSeleccionado || '',
        talle:   _modalCarritoData.talleSeleccionado || '',
    };

    fetch(window.BASE_URL + 'ajax/agregar-carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cart-check me-2"></i>Agregar al Carrito';

        if (data.requiere_login) {
            bootstrap.Modal.getInstance(document.getElementById('modalSeleccionCarrito')).hide();
            mostrarAlerta(data.mensaje, 'warning');
            setTimeout(() => { window.location.href = data.redirect || window.BASE_URL + 'login.php'; }, 1500);
            return;
        }

        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalSeleccionCarrito')).hide();
            mostrarAlerta('Producto agregado al carrito', 'success');
            actualizarContadorCarrito(data.cantidad_total);
        } else {
            errorDiv.textContent = data.mensaje || 'Error al agregar';
            errorDiv.classList.remove('d-none');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cart-check me-2"></i>Agregar al Carrito';
        errorDiv.textContent = 'Error de conexión. Intentá de nuevo.';
        errorDiv.classList.remove('d-none');
    });
}

function agregarAlCarrito(producto, boton) {
    // Función legacy — redirige al nuevo flujo con modal
    if (producto.id) {
        const fakeBtn = boton || document.createElement('button');
        fakeBtn.dataset.id = producto.id;
        fetch(`${window.BASE_URL}ajax/obtener-producto-carrito.php?id=${producto.id}`)
            .then(r => r.json())
            .then(data => { if (data.success) abrirModalCarrito(data); })
            .catch(() => mostrarAlerta('Error de conexión', 'danger'));
    }
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
    
    fetch(window.BASE_URL + 'ajax/agregar-favorito.php', {
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
                window.location.href = window.BASE_URL + 'login.php';
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
