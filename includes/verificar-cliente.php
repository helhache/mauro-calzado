<?php
/**
 * VERIFICAR-CLIENTE.PHP
 * Protege páginas que solo pueden ver CLIENTES
 */

if (!defined('DB_HOST')) {
    require_once('config.php');
}

// Si no está logueado, redirigir a login
if (!estaLogueado()) {
    $_SESSION['mensaje_error'] = "Debes iniciar sesión para acceder";
    redirigir(SITE_URL . 'login.php');
}

// Si no es cliente, bloquear
if (!esCliente()) {
    $_SESSION['mensaje_error'] = "No tienes permisos para acceder a esta página";
    redirigirSegunRol();
}
?>
