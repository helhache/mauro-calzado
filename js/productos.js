/**
 * PRODUCTOS.JS - GESTIÓN DE PRODUCTOS
 *
 * Funcionalidad para:
 * - Admin: CRUD completo de productos
 * - Gerente: Gestión de stock, precios y promociones
 *
 * Dependencias: Bootstrap 5, SweetAlert2
 */

'use strict';

// =============================================================================
// NAMESPACE PARA PRODUCTOS
// =============================================================================

const ProductosManager = {

    /**
     * Detectar contexto (admin o gerente)
     */
    obtenerContexto() {
        // Detecta si estamos en admin/ o gerente/ por la URL
        const path = window.location.pathname;
        if (path.includes('/admin/')) {
            return 'admin';
        } else if (path.includes('/gerente/')) {
            return 'gerente';
        }
        return 'admin'; // Por defecto
    },

    /**
     * Obtener ruta AJAX según contexto
     */
    getRutaAjax(archivo) {
        const contexto = this.obtenerContexto();
        return `${contexto}/ajax/${archivo}`;
    },

    /**
     * Inicializar módulo de productos
     */
    init() {
        this.inicializarEventos();
        this.cargarColores();
        this.inicializarVistaPrevia();
    },

    /**
     * Configurar event listeners
     */
    inicializarEventos() {
        // Delegación de eventos para elementos dinámicos
        document.addEventListener('click', (e) => {
            // Botón editar
            if (e.target.closest('.btn-editar-producto')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-editar-producto');
                const productoId = btn.dataset.id;
                this.abrirModalEditar(productoId);
            }

            // Botón eliminar/cambiar estado
            if (e.target.closest('.btn-eliminar-producto')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-eliminar-producto');
                const productoId = btn.dataset.id;
                this.cambiarEstadoProducto(productoId);
            }

            // Botón crear producto
            if (e.target.closest('#btnCrearProducto')) {
                e.preventDefault();
                this.abrirModalCrear();
            }
        });

        // Submit del formulario
        const form = document.getElementById('formProducto');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.guardarProducto();
            });
        }

        // Cambios en imagen
        const inputImagen = document.getElementById('imagen');
        if (inputImagen) {
            inputImagen.addEventListener('change', (e) => {
                this.previsualizarImagen(e.target);
            });
        }

        // Toggle de promoción
        const checkPromocion = document.getElementById('en_promocion');
        if (checkPromocion) {
            checkPromocion.addEventListener('change', (e) => {
                this.togglePromocion(e.target.checked);
            });
        }
    },

    /**
     * Abrir modal para crear producto
     */
    abrirModalCrear() {
        const modal = document.getElementById('modalProducto');
        const form = document.getElementById('formProducto');

        if (!modal || !form) return;

        // Resetear formulario
        form.reset();
        document.getElementById('producto_id').value = '';

        // Cambiar título
        const modalTitle = modal.querySelector('.modal-title');
        if (modalTitle) {
            modalTitle.textContent = 'Nuevo Producto';
        }

        // Resetear vista previa
        const preview = document.getElementById('preview-imagen');
        if (preview) {
            preview.src = '../img/placeholder-producto.jpg';
        }

        // Ocultar sección de descuento
        this.togglePromocion(false);

        // Mostrar modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    },

    /**
     * Abrir modal para editar producto
     */
    async abrirModalEditar(productoId) {
        try {
            const response = await fetch(`${this.getRutaAjax('obtener-producto.php')}?id=${productoId}`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.mensaje || 'Error al cargar el producto');
            }

            const producto = data.producto;

            // Llenar formulario
            document.getElementById('producto_id').value = producto.id;
            document.getElementById('nombre').value = producto.nombre;
            document.getElementById('descripcion').value = producto.descripcion || '';
            document.getElementById('precio').value = producto.precio;
            document.getElementById('stock').value = producto.stock;
            document.getElementById('categoria_id').value = producto.categoria_id;
            document.getElementById('genero').value = producto.genero;
            document.getElementById('marca').value = producto.marca || '';
            document.getElementById('material').value = producto.material || '';

            // Talles y colores
            document.getElementById('talles').value = producto.talles || '';
            document.getElementById('colores').value = producto.colores || '';

            // Promoción
            const enPromocion = producto.en_promocion == 1;
            document.getElementById('en_promocion').checked = enPromocion;
            this.togglePromocion(enPromocion);

            if (enPromocion) {
                document.getElementById('descuento_porcentaje').value = producto.descuento_porcentaje;
            }

            // Vista previa de imagen
            const preview = document.getElementById('preview-imagen');
            if (preview && producto.imagen) {
                preview.src = `../img/productos/${producto.imagen}`;
            }

            // Cambiar título del modal
            const modal = document.getElementById('modalProducto');
            const modalTitle = modal.querySelector('.modal-title');
            if (modalTitle) {
                modalTitle.textContent = 'Editar Producto';
            }

            // Mostrar modal
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();

        } catch (error) {
            console.error('Error:', error);
            this.mostrarAlerta(error.message, 'error');
        }
    },

    /**
     * Guardar producto (crear o editar)
     */
    async guardarProducto() {
        const form = document.getElementById('formProducto');
        const formData = new FormData(form);

        const productoId = document.getElementById('producto_id').value;
        const url = productoId ? this.getRutaAjax('editar-producto.php') : this.getRutaAjax('crear-producto.php');

        // Validar formulario
        if (!this.validarFormulario(formData)) {
            return;
        }

        try {
            // Mostrar loading
            const btnGuardar = form.querySelector('button[type="submit"]');
            const textoOriginal = btnGuardar.innerHTML;
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarAlerta(data.mensaje, 'success');

                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalProducto'));
                modal.hide();

                // Recargar tabla después de un breve delay
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                throw new Error(data.mensaje || 'Error al guardar el producto');
            }

        } catch (error) {
            console.error('Error:', error);
            this.mostrarAlerta(error.message, 'error');

            // Restaurar botón
            const btnGuardar = form.querySelector('button[type="submit"]');
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = textoOriginal;
        }
    },

    /**
     * Cambiar estado de producto (activar/desactivar)
     */
    async cambiarEstadoProducto(productoId) {
        const confirmacion = await Swal.fire({
            title: '¿Estás seguro?',
            text: '¿Deseas cambiar el estado de este producto?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmacion.isConfirmed) return;

        try {
            const response = await fetch(this.getRutaAjax('cambiar-estado-producto.php'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ producto_id: productoId })
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarAlerta(data.mensaje, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(data.mensaje || 'Error al cambiar el estado');
            }

        } catch (error) {
            console.error('Error:', error);
            this.mostrarAlerta(error.message, 'error');
        }
    },

    /**
     * Validar formulario de producto
     */
    validarFormulario(formData) {
        const nombre = formData.get('nombre')?.trim();
        const precio = parseFloat(formData.get('precio'));
        const stock = parseInt(formData.get('stock'));

        if (!nombre || nombre.length < 3) {
            this.mostrarAlerta('El nombre debe tener al menos 3 caracteres', 'warning');
            return false;
        }

        if (isNaN(precio) || precio <= 0) {
            this.mostrarAlerta('El precio debe ser mayor a 0', 'warning');
            return false;
        }

        if (isNaN(stock) || stock < 0) {
            this.mostrarAlerta('El stock no puede ser negativo', 'warning');
            return false;
        }

        // Validar descuento si está en promoción
        const enPromocion = formData.get('en_promocion') === '1';
        if (enPromocion) {
            const descuento = parseInt(formData.get('descuento_porcentaje'));
            if (isNaN(descuento) || descuento <= 0 || descuento > 100) {
                this.mostrarAlerta('El descuento debe estar entre 1 y 100', 'warning');
                return false;
            }
        }

        return true;
    },

    /**
     * Toggle de sección de promoción
     */
    togglePromocion(mostrar) {
        const seccionDescuento = document.getElementById('seccion-descuento');
        if (seccionDescuento) {
            seccionDescuento.style.display = mostrar ? 'block' : 'none';

            const inputDescuento = document.getElementById('descuento_porcentaje');
            if (inputDescuento) {
                inputDescuento.required = mostrar;
                if (!mostrar) {
                    inputDescuento.value = '';
                }
            }
        }
    },

    /**
     * Previsualizar imagen antes de subir
     */
    previsualizarImagen(input) {
        const preview = document.getElementById('preview-imagen');
        if (!preview) return;

        if (input.files && input.files[0]) {
            const file = input.files[0];

            // Validar tipo de archivo
            if (!file.type.match('image.*')) {
                this.mostrarAlerta('Por favor selecciona una imagen válida', 'warning');
                input.value = '';
                return;
            }

            // Validar tamaño (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                this.mostrarAlerta('La imagen no debe superar los 5MB', 'warning');
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                preview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    },

    /**
     * Inicializar vista previa de imagen
     */
    inicializarVistaPrevia() {
        const preview = document.getElementById('preview-imagen');
        if (preview && !preview.src) {
            preview.src = '../img/placeholder-producto.jpg';
        }
    },

    /**
     * Cargar y mostrar colores disponibles
     */
    cargarColores() {
        const contenedorColores = document.getElementById('colores-disponibles');
        if (!contenedorColores) return;

        const coloresComunes = [
            'Negro', 'Blanco', 'Rojo', 'Azul', 'Gris',
            'Marrón', 'Beige', 'Rosa', 'Verde', 'Amarillo'
        ];

        const html = coloresComunes.map(color =>
            `<span class="badge bg-secondary me-1 mb-1">${color}</span>`
        ).join('');

        contenedorColores.innerHTML = `
            <small class="text-muted d-block mb-2">Colores comunes:</small>
            ${html}
        `;
    },

    /**
     * Mostrar alerta con SweetAlert2
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
            confirmButtonColor: '#3C50E0',
            timer: tipo === 'success' ? 2000 : undefined
        });
    }
};

// =============================================================================
// FUNCIONES PARA GERENTE (GESTIÓN DE STOCK)
// =============================================================================

const GerenteProductos = {

    /**
     * Actualizar stock de producto
     */
    async actualizarStock(productoId, nuevaCantidad) {
        try {
            const response = await fetch(this.getRutaAjax('actualizar-stock.php'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    producto_id: productoId,
                    cantidad: nuevaCantidad
                })
            });

            const data = await response.json();

            if (data.success) {
                ProductosManager.mostrarAlerta(data.mensaje, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(data.mensaje);
            }

        } catch (error) {
            console.error('Error:', error);
            ProductosManager.mostrarAlerta(error.message, 'error');
        }
    },

    /**
     * Editar precio de producto
     */
    async editarPrecio(productoId, nuevoPrecio) {
        try {
            const response = await fetch(this.getRutaAjax('editar-precio-producto.php'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    producto_id: productoId,
                    precio: nuevoPrecio
                })
            });

            const data = await response.json();

            if (data.success) {
                ProductosManager.mostrarAlerta(data.mensaje, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(data.mensaje);
            }

        } catch (error) {
            console.error('Error:', error);
            ProductosManager.mostrarAlerta(error.message, 'error');
        }
    },

    /**
     * Activar/desactivar promoción
     */
    async togglePromocion(productoId, activar, descuento = 0) {
        try {
            const response = await fetch(this.getRutaAjax('activar-promocion.php'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    producto_id: productoId,
                    activar: activar ? 1 : 0,
                    descuento_porcentaje: descuento
                })
            });

            const data = await response.json();

            if (data.success) {
                ProductosManager.mostrarAlerta(data.mensaje, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(data.mensaje);
            }

        } catch (error) {
            console.error('Error:', error);
            ProductosManager.mostrarAlerta(error.message, 'error');
        }
    }
};

// =============================================================================
// INICIALIZACIÓN
// =============================================================================

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    // Verificar si estamos en la página de productos
    if (document.getElementById('formProducto') ||
        document.querySelector('.tabla-productos')) {
        ProductosManager.init();
    }
});

// Exponer funciones globales para compatibilidad con código legacy
window.abrirModalCrear = () => ProductosManager.abrirModalCrear();
window.abrirModalEditar = (id) => ProductosManager.abrirModalEditar(id);
window.cambiarEstadoProducto = (id) => ProductosManager.cambiarEstadoProducto(id);
window.actualizarStock = (id, cantidad) => GerenteProductos.actualizarStock(id, cantidad);
window.editarPrecio = (id, precio) => GerenteProductos.editarPrecio(id, precio);
