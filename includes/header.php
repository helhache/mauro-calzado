<?php

/**
 * HEADER - BARRA DE NAVEGACIÓN SUPERIOR (CON SISTEMA DE ROLES)
 * 
 * MODIFICACIONES FASE 2 y 3:
 * - Menús dinámicos según rol del usuario
 * - Admin ve: "Panel Admin"
 * - Gerente ve: "Mi Sucursal"
 * - Cliente ve: menú normal
 */

// Si no se ha incluido config.php, incluirlo
if (!defined('DB_HOST')) {
    require_once('config.php');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Mauro Calzado - Zapatería con amplio catálogo de calzado infantil, mujer y hombre. Compra online con envío a domicilio.">

    <title><?php echo isset($titulo_pagina) ? $titulo_pagina . ' - ' : ''; ?>Mauro Calzado</title>

    <!-- BOOTSTRAP 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- BOOTSTRAP ICONS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- ESTILOS PERSONALIZADOS -->
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container-fluid px-4">

            <!-- LOGO -->
            <a class="navbar-brand" href="index.php">
                <img src="img/logo.jpg" alt="Logo Mauro Calzado" height="60" class="logo-img">
            </a>

            <!-- BOTÓN HAMBURGUESA -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- MENÚ COLAPSABLE -->
            <div class="collapse navbar-collapse" id="navbarMain">

                <!-- NAVEGACIÓN PRINCIPAL -->
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link text-dark fw-semibold" href="infantiles.php">Infantiles</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark fw-semibold" href="mujer.php">Mujer</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark fw-semibold" href="hombre.php">Hombre</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark fw-semibold" href="ofertas.php">
                            <i class="bi bi-tag-fill text-danger"></i> Ofertas
                        </a>
                    </li>

                    <?php
                    // ============================================================================
                    // MENÚ DINÁMICO SEGÚN ROL
                    // ============================================================================
                    if (estaLogueado()):
                        if (esAdmin()):
                            // MENÚ PARA ADMIN
                    ?>
                            <li class="nav-item">
                                <a class="nav-link text-primary fw-bold" href="admin/dashboard.php">
                                    <i class="bi bi-speedometer2 me-1"></i>Panel Admin
                                </a>
                            </li>
                        <?php
                        elseif (esGerente()):
                            // MENÚ PARA GERENTE
                        ?>
                            <li class="nav-item">
                                <a class="nav-link text-success fw-bold" href="gerente/dashboard.php">
                                    <i class="bi bi-shop me-1"></i>Mi Sucursal
                                </a>
                            </li>
                    <?php
                        endif;
                    endif;
                    ?>

                    <li class="nav-item">
                        <a class="nav-link text-dark fw-semibold" href="nosotros.php">Nosotros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark fw-semibold" href="contactanos.php">Contáctanos</a>
                    </li>
                </ul>

                <!-- ACCIONES DERECHA -->
                <div class="d-flex align-items-center gap-3">

                    <!-- BUSCADOR -->
                    <form class="d-flex" role="search" method="GET" action="buscar.php">
                        <div class="input-group">
                            <input class="form-control form-control-sm" type="search" name="q" placeholder="Buscar productos..." aria-label="Buscar">
                            <button class="btn btn-sm btn-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>

                    <!-- CARRITO (solo para clientes) -->
                    <?php if (!estaLogueado() || esCliente()): ?>
                        <a href="carrito.php" class="btn btn-outline-danger position-relative">
                            <i class="bi bi-cart3 fs-5"></i>
                            <?php if (isset($_SESSION['carrito']) && count($_SESSION['carrito']) > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo count($_SESSION['carrito']); ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

                    <!-- FAVORITOS (solo para clientes) -->
                    <?php if (!estaLogueado() || esCliente()): ?>
                        <a href="favoritos.php" class="btn btn-outline-primary">
                            <i class="bi bi-heart fs-5"></i>
                        </a>
                    <?php endif; ?>

                    <!-- USUARIO -->
                    <?php if (estaLogueado()): ?>
                        <!-- Usuario logueado: Mostrar dropdown con opciones -->
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle"
                                type="button"
                                id="dropdownUsuario"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <i class="bi bi-person-circle fs-5 me-1"></i>
                                <?php echo obtenerDatoUsuario('nombre'); ?>
                                <span class="badge bg-secondary ms-1"><?php echo obtenerNombreRol(); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownUsuario">
                                <?php if (esAdmin()): ?>
                                    <!-- Opciones de Admin -->
                                    <li>
                                        <a class="dropdown-item" href="admin/dashboard.php">
                                            <i class="bi bi-speedometer2 me-2"></i>Dashboard Admin
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="admin/productos.php">
                                            <i class="bi bi-box-seam me-2"></i>Gestionar Productos
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="admin/usuarios.php">
                                            <i class="bi bi-people me-2"></i>Gestionar Usuarios
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                <?php elseif (esGerente()): ?>
                                    <!-- Opciones de Gerente -->
                                    <li>
                                        <a class="dropdown-item" href="gerente/dashboard.php">
                                            <i class="bi bi-shop me-2"></i>Dashboard Sucursal
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                <?php else: ?>
                                    <!-- Opciones de Cliente -->
                                    <li>
                                        <a class="dropdown-item" href="mi-cuenta.php">
                                            <i class="bi bi-person me-2"></i>Mi Perfil
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="mis-pedidos.php">
                                            <i class="bi bi-bag-check me-2"></i>Mis Pedidos
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="mensajes-internos.php">
                                            <i class="bi bi-chat-dots me-2"></i>Mensajes
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="favoritos.php">
                                            <i class="bi bi-heart me-2"></i>Mis Favoritos
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                <?php endif; ?>

                                <li>
                                    <a class="dropdown-item text-danger" href="logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- Usuario NO logueado: Botón de login -->
                        <a href="login.php" class="btn btn-primary">
                            <i class="bi bi-person-circle fs-5"></i> Ingresar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido de la página irá aquí -->