<?php
/**
 * VERIFICAR-GERENTE.PHP
 * Protege páginas que solo pueden ver GERENTES
 */

if (!defined('DB_HOST')) {
    require_once('config.php');
}

// Si no está logueado, redirigir a login
if (!estaLogueado()) {
    $_SESSION['mensaje_error'] = "Debes iniciar sesión para acceder";
    redirigir('login.php');
}

// Si no es gerente, bloquear
if (!esGerente()) {
    $_SESSION['mensaje_error'] = "Solo gerentes pueden acceder a esta página";
    redirigirSegunRol();
}
?>
