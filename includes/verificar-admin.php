<?php
/**
 * VERIFICAR-ADMIN.PHP
 * Protege páginas que solo pueden ver ADMINISTRADORES
 */

if (!defined('DB_HOST')) {
    require_once('config.php');
}

// Si no está logueado, redirigir a login
if (!estaLogueado()) {
    $_SESSION['mensaje_error'] = "Debes iniciar sesión para acceder";
    redirigir('login.php');
}

// Si no es admin, bloquear
if (!esAdmin()) {
    $_SESSION['mensaje_error'] = "Solo administradores pueden acceder a esta página";
    redirigirSegunRol();
}
?>
