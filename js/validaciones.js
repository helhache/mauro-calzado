/**
 * VALIDACIONES.JS - VALIDACIÓN DE FORMULARIOS
 *
 * Validaciones para:
 * - Login y registro
 * - Checkout
 * - Formularios de contacto
 * - Datos de usuario
 *
 * Dependencias: Bootstrap 5
 */

'use strict';

// =============================================================================
// VALIDACIONES GENERALES
// =============================================================================

const Validaciones = {

    /**
     * Validar email
     */
    email(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    },

    /**
     * Validar teléfono argentino
     */
    telefono(telefono) {
        // Formatos aceptados:
        // +54 11 1234-5678
        // 011 1234-5678
        // 11 12345678
        const regex = /^(\+?54)?[\s\-]?(\d{2,4})[\s\-]?(\d{6,8})$/;
        return regex.test(telefono);
    },

    /**
     * Validar DNI argentino
     */
    dni(dni) {
        const regex = /^\d{7,8}$/;
        return regex.test(dni.toString());
    },

    /**
     * Validar código postal argentino
     */
    codigoPostal(cp) {
        // Formato: 1234 o A1234BCD
        const regex = /^[A-Z]?\d{4}[A-Z]{0,3}$/i;
        return regex.test(cp);
    },

    /**
     * Validar CUIT/CUIL
     */
    cuit(cuit) {
        // Remover guiones y espacios
        cuit = cuit.replace(/[-\s]/g, '');

        if (cuit.length !== 11) return false;

        // Validar dígito verificador
        const digitos = cuit.split('').map(Number);
        const multiplicadores = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        let suma = 0;

        for (let i = 0; i < 10; i++) {
            suma += digitos[i] * multiplicadores[i];
        }

        const resto = suma % 11;
        const digitoVerificador = resto === 0 ? 0 : resto === 1 ? 9 : 11 - resto;

        return digitoVerificador === digitos[10];
    },

    /**
     * Validar contraseña segura
     */
    password(password, opciones = {}) {
        const config = {
            minLength: opciones.minLength || 6,
            requiereNumero: opciones.requiereNumero || false,
            requiereMayuscula: opciones.requiereMayuscula || false,
            requiereEspecial: opciones.requiereEspecial || false
        };

        if (password.length < config.minLength) {
            return {
                valido: false,
                mensaje: `La contraseña debe tener al menos ${config.minLength} caracteres`
            };
        }

        if (config.requiereNumero && !/\d/.test(password)) {
            return {
                valido: false,
                mensaje: 'La contraseña debe contener al menos un número'
            };
        }

        if (config.requiereMayuscula && !/[A-Z]/.test(password)) {
            return {
                valido: false,
                mensaje: 'La contraseña debe contener al menos una mayúscula'
            };
        }

        if (config.requiereEspecial && !/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            return {
                valido: false,
                mensaje: 'La contraseña debe contener al menos un carácter especial'
            };
        }

        return { valido: true, mensaje: 'Contraseña válida' };
    },

    /**
     * Validar que dos contraseñas coincidan
     */
    passwordsCoinciden(password1, password2) {
        return password1 === password2;
    },

    /**
     * Validar solo letras
     */
    soloLetras(texto) {
        const regex = /^[a-záéíóúñüA-ZÁÉÍÓÚÑÜ\s]+$/;
        return regex.test(texto);
    },

    /**
     * Validar solo números
     */
    soloNumeros(texto) {
        const regex = /^\d+$/;
        return regex.test(texto);
    },

    /**
     * Validar rango numérico
     */
    enRango(numero, min, max) {
        return numero >= min && numero <= max;
    },

    /**
     * Validar longitud de texto
     */
    longitudValida(texto, min, max = Infinity) {
        const longitud = texto.trim().length;
        return longitud >= min && longitud <= max;
    }
};

// =============================================================================
// VALIDACIÓN DE FORMULARIOS
// =============================================================================

const FormularioValidator = {

    /**
     * Validar formulario de registro
     */
    registro(formData) {
        const errores = [];

        // Nombre
        const nombre = formData.get('nombre')?.trim();
        if (!nombre || !Validaciones.longitudValida(nombre, 2)) {
            errores.push('El nombre debe tener al menos 2 caracteres');
        } else if (!Validaciones.soloLetras(nombre)) {
            errores.push('El nombre solo puede contener letras');
        }

        // Apellido
        const apellido = formData.get('apellido')?.trim();
        if (!apellido || !Validaciones.longitudValida(apellido, 2)) {
            errores.push('El apellido debe tener al menos 2 caracteres');
        } else if (!Validaciones.soloLetras(apellido)) {
            errores.push('El apellido solo puede contener letras');
        }

        // Email
        const email = formData.get('email')?.trim();
        if (!email || !Validaciones.email(email)) {
            errores.push('Email inválido');
        }

        // Teléfono (opcional pero si está, debe ser válido)
        const telefono = formData.get('telefono')?.trim();
        if (telefono && !Validaciones.telefono(telefono)) {
            errores.push('Teléfono inválido. Formato: +54 11 1234-5678');
        }

        // Contraseña
        const password = formData.get('password');
        const resultadoPassword = Validaciones.password(password, {
            minLength: 6,
            requiereNumero: false
        });

        if (!resultadoPassword.valido) {
            errores.push(resultadoPassword.mensaje);
        }

        // Confirmar contraseña
        const passwordConfirm = formData.get('password_confirm');
        if (!Validaciones.passwordsCoinciden(password, passwordConfirm)) {
            errores.push('Las contraseñas no coinciden');
        }

        return {
            valido: errores.length === 0,
            errores: errores
        };
    },

    /**
     * Validar formulario de login
     */
    login(formData) {
        const errores = [];

        const email = formData.get('email')?.trim();
        if (!email || !Validaciones.email(email)) {
            errores.push('Email inválido');
        }

        const password = formData.get('password');
        if (!password || password.length < 1) {
            errores.push('La contraseña es requerida');
        }

        return {
            valido: errores.length === 0,
            errores: errores
        };
    },

    /**
     * Validar formulario de checkout
     */
    checkout(formData) {
        const errores = [];

        // Datos de envío
        const direccion = formData.get('direccion')?.trim();
        if (!direccion || !Validaciones.longitudValida(direccion, 5)) {
            errores.push('La dirección debe tener al menos 5 caracteres');
        }

        const ciudad = formData.get('ciudad')?.trim();
        if (!ciudad || !Validaciones.longitudValida(ciudad, 2)) {
            errores.push('La ciudad es requerida');
        }

        const codigoPostal = formData.get('codigo_postal')?.trim();
        if (!codigoPostal || !Validaciones.codigoPostal(codigoPostal)) {
            errores.push('Código postal inválido');
        }

        const telefono = formData.get('telefono')?.trim();
        if (!telefono || !Validaciones.telefono(telefono)) {
            errores.push('Teléfono inválido');
        }

        return {
            valido: errores.length === 0,
            errores: errores
        };
    },

    /**
     * Validar formulario de contacto
     */
    contacto(formData) {
        const errores = [];

        const nombre = formData.get('nombre')?.trim();
        if (!nombre || !Validaciones.longitudValida(nombre, 2)) {
            errores.push('El nombre es requerido');
        }

        const email = formData.get('email')?.trim();
        if (!email || !Validaciones.email(email)) {
            errores.push('Email inválido');
        }

        const mensaje = formData.get('mensaje')?.trim();
        if (!mensaje || !Validaciones.longitudValida(mensaje, 10)) {
            errores.push('El mensaje debe tener al menos 10 caracteres');
        }

        return {
            valido: errores.length === 0,
            errores: errores
        };
    }
};

// =============================================================================
// VALIDACIÓN EN TIEMPO REAL
// =============================================================================

const ValidacionTiempoReal = {

    /**
     * Inicializar validaciones en tiempo real
     */
    init() {
        this.configurarEventos();
    },

    /**
     * Configurar event listeners
     */
    configurarEventos() {
        // Validar email en blur
        document.querySelectorAll('input[type="email"]').forEach(input => {
            input.addEventListener('blur', function () {
                ValidacionTiempoReal.validarCampo(this, Validaciones.email(this.value), 'Email inválido');
            });
        });

        // Validar teléfono en blur
        document.querySelectorAll('input[type="tel"], input[name="telefono"]').forEach(input => {
            input.addEventListener('blur', function () {
                if (this.value.trim()) { // Solo validar si hay valor
                    ValidacionTiempoReal.validarCampo(this, Validaciones.telefono(this.value), 'Teléfono inválido');
                }
            });
        });

        // Validar confirmación de contraseña
        const passwordConfirm = document.getElementById('password_confirm');
        if (passwordConfirm) {
            passwordConfirm.addEventListener('input', function () {
                const password = document.getElementById('password')?.value;
                const coinciden = Validaciones.passwordsCoinciden(password, this.value);
                ValidacionTiempoReal.validarCampo(this, coinciden, 'Las contraseñas no coinciden');
            });
        }
    },

    /**
     * Validar campo individual
     */
    validarCampo(input, esValido, mensajeError) {
        // Remover clases y mensajes previos
        input.classList.remove('is-valid', 'is-invalid');
        const feedbackExistente = input.nextElementSibling;
        if (feedbackExistente && feedbackExistente.classList.contains('invalid-feedback')) {
            feedbackExistente.remove();
        }

        if (!input.value.trim()) {
            // Campo vacío, no mostrar nada
            return;
        }

        // Aplicar clase según validación
        if (esValido) {
            input.classList.add('is-valid');
        } else {
            input.classList.add('is-invalid');

            // Agregar mensaje de error
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = mensajeError;
            input.parentNode.appendChild(feedback);
        }
    }
};

// =============================================================================
// INICIALIZACIÓN
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    ValidacionTiempoReal.init();
});

// Exponer funciones globales
window.Validaciones = Validaciones;
window.FormularioValidator = FormularioValidator;
window.ValidacionTiempoReal = ValidacionTiempoReal;
