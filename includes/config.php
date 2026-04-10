<?php
/**
 * ARCHIVO DE CONFIGURACIÓN PRINCIPAL
 *
 * Propósito: Centralizar todas las configuraciones del sistema
 * - Conexión a base de datos
 * - Constantes globales
 * - Inicio de sesión PHP
 * - Sistema de roles y permisos
 */

// Iniciar sesión PHP para manejar login de usuarios
// session_start() debe estar antes de cualquier salida HTML
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================================
// CONFIGURACIÓN DE ENTORNO
// ============================================================================
// Cambiar a 'production' cuando el sitio esté en línea
define('ENVIRONMENT', 'development'); // 'development' o 'production'

// Configurar reporte de errores según el entorno
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php-errors.log');
}

// ============================================================================
// CONFIGURACIÓN DE BASE DE DATOS
// ============================================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mauro_calzado');
define('DB_CHARSET', 'utf8mb4'); // Soporte completo para emojis y caracteres especiales

// ============================================================================
// CONSTANTES DEL SITIO
// ============================================================================
define('SITE_URL', 'http://localhost/mauro-calzado/');
define('SITE_NAME', 'Mauro Calzado');

// ============================================================================
// CONSTANTES DE ROLES
// ============================================================================
define('ROL_CLIENTE', 1);
define('ROL_GERENTE', 2);
define('ROL_ADMIN', 3);

// ============================================================================
// FUNCIÓN DE CONEXIÓN A BASE DE DATOS
// ============================================================================
/**
 * Conectar a la base de datos
 *
 * @return mysqli Objeto de conexión
 * @throws Exception Si falla la conexión (solo en development)
 */
function conectarDB() {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if (!$conn) {
        // En desarrollo mostramos el error, en producción ocultamos detalles
        if (ENVIRONMENT === 'development') {
            die("Error de conexión: " . mysqli_connect_error());
        } else {
            // Loguear el error real para debugging
            error_log("Error de conexión BD: " . mysqli_connect_error());
            die("Error de conexión a la base de datos. Por favor, intente más tarde.");
        }
    }

    // Usar utf8mb4 para soporte completo de caracteres (emojis, etc.)
    mysqli_set_charset($conn, DB_CHARSET);

    return $conn;
}

// ============================================================================
// FUNCIONES DE SANITIZACIÓN
// ============================================================================
/**
 * Limpiar dato para prevenir XSS al MOSTRAR en HTML
 *
 * IMPORTANTE: Esta función es para MOSTRAR datos, no para guardar en BD.
 * Para BD, usar prepared statements.
 *
 * @param string $data Dato a limpiar
 * @return string Dato sanitizado para mostrar en HTML
 */
function limpiarParaHTML($data) {
    if ($data === null) {
        return '';
    }
    $data = trim($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Limpiar dato básico (trim solamente)
 *
 * Para usar ANTES de guardar en BD con prepared statements
 *
 * @param string $data Dato a limpiar
 * @return string Dato con espacios eliminados
 */
function limpiarDato($data) {
    if ($data === null) {
        return '';
    }
    return trim($data);
}

/**
 * Validar que un valor sea un entero positivo
 *
 * @param mixed $valor Valor a validar
 * @return int|false Entero positivo o false si no es válido
 */
function validarEnteroPositivo($valor) {
    $valor = filter_var($valor, FILTER_VALIDATE_INT);
    if ($valor === false || $valor < 1) {
        return false;
    }
    return $valor;
}

/**
 * Validar email
 *
 * @param string $email Email a validar
 * @return string|false Email válido o false
 */
function validarEmail($email) {
    $email = trim($email);
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// ============================================================================
// FUNCIONES DE SESIÓN Y AUTENTICACIÓN
// ============================================================================
/**
 * Verificar si el usuario está logueado
 *
 * @return bool True si está logueado
 */
function estaLogueado() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Obtener dato del usuario actual desde la sesión
 *
 * @param string $campo Campo a obtener
 * @return mixed Valor del campo o null
 */
function obtenerDatoUsuario($campo) {
    if (estaLogueado() && isset($_SESSION[$campo])) {
        return $_SESSION[$campo];
    }
    return null;
}

/**
 * Redirigir a una URL
 *
 * @param string $url URL destino
 */
function redirigir($url) {
    header("Location: " . $url);
    exit();
}

// ============================================================================
// FUNCIONES DE ROLES Y PERMISOS
// ============================================================================
/**
 * Obtener rol del usuario actual
 *
 * @return int|null ID del rol o null si no está logueado
 */
function obtenerRol() {
    if (estaLogueado() && isset($_SESSION['rol_id'])) {
        return (int)$_SESSION['rol_id'];
    }
    return null;
}

/**
 * Verificar si es cliente
 * @return bool
 */
function esCliente() {
    return obtenerRol() === ROL_CLIENTE;
}

/**
 * Verificar si es gerente
 * @return bool
 */
function esGerente() {
    return obtenerRol() === ROL_GERENTE;
}

/**
 * Verificar si es admin
 * @return bool
 */
function esAdmin() {
    return obtenerRol() === ROL_ADMIN;
}

/**
 * Obtener nombre del rol actual
 *
 * @return string Nombre del rol
 */
function obtenerNombreRol() {
    $rol = obtenerRol();
    switch($rol) {
        case ROL_CLIENTE:
            return "Cliente";
        case ROL_GERENTE:
            return "Gerente";
        case ROL_ADMIN:
            return "Administrador";
        default:
            return "Invitado";
    }
}

/**
 * Obtener sucursal del gerente actual
 *
 * @return int|null ID de sucursal o null
 */
function obtenerSucursalGerente() {
    if (esGerente() && isset($_SESSION['sucursal_id'])) {
        return (int)$_SESSION['sucursal_id'];
    }
    return null;
}

/**
 * Verificar si el usuario tiene uno de los roles permitidos
 *
 * @param array $roles_permitidos Roles que tienen permiso
 * @return bool True si tiene permiso
 */
function verificarPermiso($roles_permitidos) {
    $rol_actual = obtenerRol();
    if ($rol_actual === null) {
        return false;
    }
    return in_array($rol_actual, $roles_permitidos, true);
}

/**
 * Verificar acceso a una sucursal específica
 *
 * @param int $sucursal_id ID de la sucursal
 * @return bool True si tiene acceso
 */
function tieneAccesoSucursal($sucursal_id) {
    // Validar que sea un ID válido
    $sucursal_id = validarEnteroPositivo($sucursal_id);
    if ($sucursal_id === false) {
        return false;
    }

    // Admin tiene acceso a todas las sucursales
    if (esAdmin()) {
        return true;
    }

    // Gerente solo a su sucursal
    if (esGerente()) {
        return obtenerSucursalGerente() === $sucursal_id;
    }

    // Cliente no tiene acceso
    return false;
}

/**
 * Redirigir según el rol del usuario
 */
function redirigirSegunRol() {
    if (!estaLogueado()) {
        redirigir('login.php');
    }

    $rol = obtenerRol();

    switch($rol) {
        case ROL_ADMIN:
            redirigir('admin/dashboard.php');
            break;
        case ROL_GERENTE:
            redirigir('gerente/dashboard.php');
            break;
        case ROL_CLIENTE:
            redirigir('index.php');
            break;
        default:
            redirigir('login.php');
    }
}

/**
 * Bloquear acceso si no tiene el rol requerido
 *
 * @param array $roles_permitidos Roles que pueden acceder
 * @param string|null $mensaje_error Mensaje personalizado
 */
function bloquearAcceso($roles_permitidos, $mensaje_error = null) {
    // Si no está logueado, redirigir a login
    if (!estaLogueado()) {
        $_SESSION['mensaje_error'] = "Debes iniciar sesión para acceder a esta página";
        redirigir('login.php');
    }

    // Si no tiene el rol permitido, bloquear
    if (!verificarPermiso($roles_permitidos)) {
        if ($mensaje_error) {
            $_SESSION['mensaje_error'] = $mensaje_error;
        } else {
            $_SESSION['mensaje_error'] = "No tienes permisos para acceder a esta página";
        }

        // Redirigir al dashboard correspondiente
        redirigirSegunRol();
    }
}

// ============================================================================
// CONEXIÓN GLOBAL
// ============================================================================
$conn = conectarDB();
?>
