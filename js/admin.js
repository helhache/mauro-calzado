/**
 * ADMIN.JS - FUNCIONALIDAD DEL PANEL DE ADMINISTRACIÓN
 *
 * Gestión de:
 * - Usuarios (CRUD)
 * - Sucursales (CRUD)
 * - Notificaciones
 * - Estadísticas
 *
 * Dependencias: Bootstrap 5, SweetAlert2
 */

'use strict';

// =============================================================================
// GESTIÓN DE USUARIOS
// =============================================================================

const UsuariosManager = {

    /**
     * Inicializar módulo de usuarios
     */
    init() {
        this.inicializarEventos();
    },

    /**
     * Configurar event listeners
     */
    inicializarEventos() {
        document.addEventListener('click', (e) => {
            // Botón editar usuario
            if (e.target.closest('.btn-editar-usuario')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-editar-usuario');
                const usuarioId = btn.dataset.id;
                this.abrirModalEditar(usuarioId);
            }

            // Botón cambiar estado
            if (e.target.closest('.btn-cambiar-estado-usuario')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-cambiar-estado-usuario');
                const usuarioId = btn.dataset.id;
                this.cambiarEstado(usuarioId);
            }

            // Botón resetear password
            if (e.target.closest('.btn-resetear-password')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-resetear-password');
                const usuarioId = btn.dataset.id;
                this.resetearPassword(usuarioId);
            }

            // Botón crear usuario
            if (e.target.closest('#btnCrearUsuario')) {
                e.preventDefault();
                this.abrirModalCrear();
            }
        });

        // Submit del formulario
        const form = document.getElementById('formUsuario');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.guardarUsuario();
            });
        }
    },

    /**
     * Abrir modal para crear usuario
     */
    abrirModalCrear() {
        const modal = document.getElementById('modalUsuario');
        const form = document.getElementById('formUsuario');

        if (!modal || !form) return;

        form.reset();
        document.getElementById('usuario_id').value = '';

        // Cambiar título
        const modalTitle = modal.querySelector('.modal-title');
        if (modalTitle) {
            modalTitle.textContent = 'Nuevo Usuario';
        }

        // Mostrar campo de contraseña
        const campoPassword = document.getElementById('campo-password');
        if (campoPassword) {
            campoPassword.style.display = 'block';
            document.getElementById('password').required = true;
        }

        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    },

    /**
     * Abrir modal para editar usuario
     */
    async abrirModalEditar(usuarioId) {
        try {
            const response = await fetch(`admin/ajax/obtener-usuario.php?id=${usuarioId}`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.mensaje || 'Error al cargar el usuario');
            }

            const usuario = data.usuario;

            // Llenar formulario
            document.getElementById('usuario_id').value = usuario.id;
            document.getElementById('nombre').value = usuario.nombre;
            document.getElementById('apellido').value = usuario.apellido;
            document.getElementById('email').value = usuario.email;
            document.getElementById('telefono').value = usuario.telefono || '';
            document.getElementById('rol_id').value = usuario.rol_id;

            // Si es gerente, mostrar selector de sucursal
            if (usuario.rol_id == 2) {
                this.mostrarSelectorSucursal(true);
                document.getElementById('sucursal_id').value = usuario.sucursal_id || '';
            } else {
                this.mostrarSelectorSucursal(false);
            }

            // Ocultar campo de contraseña en edición
            const campoPassword = document.getElementById('campo-password');
            if (campoPassword) {
                campoPassword.style.display = 'none';
                document.getElementById('password').required = false;
            }

            // Cambiar título del modal
            const modal = document.getElementById('modalUsuario');
            const modalTitle = modal.querySelector('.modal-title');
            if (modalTitle) {
                modalTitle.textContent = 'Editar Usuario';
            }

            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();

        } catch (error) {
            console.error('Error:', error);
            this.mostrarAlerta(error.message, 'error');
        }
    },

    /**
     * Guardar usuario (crear o editar)
     */
    async guardarUsuario() {
        const form = document.getElementById('formUsuario');
        const formData = new FormData(form);

        const usuarioId = document.getElementById('usuario_id').value;
        const url = usuarioId ? 'admin/ajax/editar-usuario.php' : 'admin/ajax/crear-usuario.php';

        // Validar
        if (!this.validarFormulario(formData)) {
            return;
        }

        try {
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

                const modal = bootstrap.Modal.getInstance(document.getElementById('modalUsuario'));
                modal.hide();

                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(data.mensaje || 'Error al guardar el usuario');
            }

        } catch (error) {
            console.error('Error:', error);
            this.mostrarAlerta(error.message, 'error');

            const btnGuardar = form.querySelector('button[type="submit"]');
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = 'Guardar';
        }
    },

    /**
     * Cambiar estado del usuario (activar/desactivar)
     */
    async cambiarEstado(usuarioId) {
        const confirmacion = await Swal.fire({
            title: '¿Cambiar estado del usuario?',
            text: 'El usuario será activado o desactivado',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmacion.isConfirmed) return;

        try {
            const response = await fetch('admin/ajax/cambiar-estado-usuario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ usuario_id: usuarioId })
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarAlerta(data.mensaje, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(data.mensaje);
            }

        } catch (error) {
            console.error('Error:', error);
            this.mostrarAlerta(error.message, 'error');
        }
    },

    /**
     * Resetear contraseña del usuario
     */
    async resetearPassword(usuarioId) {
        const confirmacion = await Swal.fire({
            title: '¿Resetear contraseña?',
            text: 'Se generará una nueva contraseña temporal',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, resetear',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmacion.isConfirmed) return;

        try {
            const response = await fetch('admin/ajax/resetear-password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ usuario_id: usuarioId })
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Contraseña reseteada',
                    html: `Nueva contraseña temporal: <br><strong>${data.nueva_password}</strong><br><small>Comunícala al usuario</small>`,
                    confirmButtonColor: '#3C50E0'
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
     * Mostrar/ocultar selector de sucursal según el rol
     */
    mostrarSelectorSucursal(mostrar) {
        const contenedor = document.getElementById('contenedor-sucursal');
        if (contenedor) {
            contenedor.style.display = mostrar ? 'block' : 'none';
            const select = document.getElementById('sucursal_id');
            if (select) {
                select.required = mostrar;
            }
        }
    },

    /**
     * Validar formulario de usuario
     */
    validarFormulario(formData) {
        const nombre = formData.get('nombre')?.trim();
        const email = formData.get('email')?.trim();
        const usuarioId = formData.get('usuario_id');

        if (!nombre || nombre.length < 2) {
            this.mostrarAlerta('El nombre debe tener al menos 2 caracteres', 'warning');
            return false;
        }

        if (!this.validarEmail(email)) {
            this.mostrarAlerta('Email inválido', 'warning');
            return false;
        }

        // Validar password solo si es creación
        if (!usuarioId) {
            const password = formData.get('password');
            if (!password || password.length < 6) {
                this.mostrarAlerta('La contraseña debe tener al menos 6 caracteres', 'warning');
                return false;
            }
        }

        return true;
    },

    /**
     * Validar formato de email
     */
    validarEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
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
            confirmButtonColor: '#3C50E0',
            timer: tipo === 'success' ? 2000 : undefined
        });
    }
};

// =============================================================================
// GESTIÓN DE SUCURSALES
// =============================================================================

const SucursalesManager = {

    /**
     * Inicializar módulo de sucursales
     */
    init() {
        this.inicializarEventos();
    },

    /**
     * Configurar event listeners
     */
    inicializarEventos() {
        document.addEventListener('click', (e) => {
            // Botón editar sucursal
            if (e.target.closest('.btn-editar-sucursal')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-editar-sucursal');
                const sucursalId = btn.dataset.id;
                this.abrirModalEditar(sucursalId);
            }

            // Botón cambiar estado
            if (e.target.closest('.btn-cambiar-estado-sucursal')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-cambiar-estado-sucursal');
                const sucursalId = btn.dataset.id;
                this.cambiarEstado(sucursalId);
            }

            // Botón crear sucursal
            if (e.target.closest('#btnCrearSucursal')) {
                e.preventDefault();
                this.abrirModalCrear();
            }
        });

        // Submit del formulario
        const form = document.getElementById('formSucursal');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.guardarSucursal();
            });
        }
    },

    /**
     * Abrir modal para crear sucursal
     */
    abrirModalCrear() {
        const modal = document.getElementById('modalSucursal');
        const form = document.getElementById('formSucursal');

        if (!modal || !form) return;

        form.reset();
        document.getElementById('sucursal_id').value = '';

        const modalTitle = modal.querySelector('.modal-title');
        if (modalTitle) {
            modalTitle.textContent = 'Nueva Sucursal';
        }

        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    },

    /**
     * Abrir modal para editar sucursal
     */
    async abrirModalEditar(sucursalId) {
        try {
            const response = await fetch(`admin/ajax/obtener-sucursal.php?id=${sucursalId}`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.mensaje || 'Error al cargar la sucursal');
            }

            const sucursal = data.sucursal;

            // Llenar formulario
            document.getElementById('sucursal_id').value = sucursal.id;
            document.getElementById('nombre').value = sucursal.nombre;
            document.getElementById('direccion').value = sucursal.direccion;
            document.getElementById('ciudad').value = sucursal.ciudad;
            document.getElementById('provincia').value = sucursal.provincia;
            document.getElementById('telefono').value = sucursal.telefono || '';
            document.getElementById('email').value = sucursal.email || '';

            // Cambiar título del modal
            const modal = document.getElementById('modalSucursal');
            const modalTitle = modal.querySelector('.modal-title');
            if (modalTitle) {
                modalTitle.textContent = 'Editar Sucursal';
            }

            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();

        } catch (error) {
            console.error('Error:', error);
            UsuariosManager.mostrarAlerta(error.message, 'error');
        }
    },

    /**
     * Guardar sucursal
     */
    async guardarSucursal() {
        const form = document.getElementById('formSucursal');
        const formData = new FormData(form);

        try {
            const btnGuardar = form.querySelector('button[type="submit"]');
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

            const response = await fetch('admin/ajax/guardar-sucursal.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                UsuariosManager.mostrarAlerta(data.mensaje, 'success');

                const modal = bootstrap.Modal.getInstance(document.getElementById('modalSucursal'));
                modal.hide();

                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(data.mensaje);
            }

        } catch (error) {
            console.error('Error:', error);
            UsuariosManager.mostrarAlerta(error.message, 'error');
        }
    },

    /**
     * Cambiar estado de sucursal
     */
    async cambiarEstado(sucursalId) {
        const confirmacion = await Swal.fire({
            title: '¿Cambiar estado de la sucursal?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmacion.isConfirmed) return;

        try {
            const response = await fetch('admin/ajax/cambiar-estado-sucursal.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sucursal_id: sucursalId })
            });

            const data = await response.json();

            if (data.success) {
                UsuariosManager.mostrarAlerta(data.mensaje, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(data.mensaje);
            }

        } catch (error) {
            console.error('Error:', error);
            UsuariosManager.mostrarAlerta(error.message, 'error');
        }
    }
};

// =============================================================================
// INICIALIZACIÓN
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    // Inicializar según la página
    if (document.getElementById('formUsuario')) {
        UsuariosManager.init();
    }

    if (document.getElementById('formSucursal')) {
        SucursalesManager.init();
    }

    // Event listener para cambio de rol (mostrar sucursal si es gerente)
    const selectRol = document.getElementById('rol_id');
    if (selectRol) {
        selectRol.addEventListener('change', function () {
            const esGerente = this.value == '2';
            UsuariosManager.mostrarSelectorSucursal(esGerente);
        });
    }
});

// Exponer funciones globales
window.abrirModalCrearUsuario = () => UsuariosManager.abrirModalCrear();
window.abrirModalEditarUsuario = (id) => UsuariosManager.abrirModalEditar(id);
window.cambiarEstadoUsuario = (id) => UsuariosManager.cambiarEstado(id);
window.resetearPassword = (id) => UsuariosManager.resetearPassword(id);
window.abrirModalCrearSucursal = () => SucursalesManager.abrirModalCrear();
window.abrirModalEditarSucursal = (id) => SucursalesManager.abrirModalEditar(id);
