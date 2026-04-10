<?php
/**
 * VERIFICAR-GERENTE-ADMIN.PHP
 * Protege páginas que pueden ver GERENTES o ADMINISTRADORES
 */

if (!defined('DB_HOST')) {
    require_once('config.php');
}

// Si no está logueado, redirigir a login
if (!estaLogueado()) {
    $_SESSION['mensaje_error'] = "Debes iniciar sesión para acceder";
    redirigir('login.php');
}

// Si no es gerente NI admin, bloquear
if (!esGerente() && !esAdmin()) {
    $_SESSION['mensaje_error'] = "No tienes permisos para acceder a esta página";
    redirigirSegunRol();
}
?>
