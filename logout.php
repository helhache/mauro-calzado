<?php
/**
 * LOGOUT.PHP - CERRAR SESIÓN (CON SISTEMA DE ROLES)
 * 
 * Funcionalidad:
 * - Destruye la sesión del usuario
 * - Limpia cookies
 * - Limpia todas las variables incluyendo rol_id y sucursal_id
 * - Redirige al inicio
 */

require_once('includes/config.php');

// Verificar que hay una sesión activa
if (estaLogueado()) {
    
    // Guardar nombre para mensaje de despedida (opcional)
    $nombre_usuario = $_SESSION['nombre'] ?? 'Usuario';
    
    // Limpiar TODAS las variables de sesión
    $_SESSION = array();
    
    // Destruir la sesión
    session_destroy();
    
    // Eliminar cookie de sesión del navegador
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Redirigir al inicio con mensaje
    header("Location: index.php?logout=success");
    exit();
    
} else {
    // No hay sesión activa, redirigir directamente
    redirigir('index.php');
}
?>
