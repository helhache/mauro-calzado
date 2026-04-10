HEADER GERENTE - Panel de GerenciaSidebar + Topbar adaptado para gerentes<?php
                                                                            /**
                                                                             * HEADER GERENTE - Panel de Gerencia
                                                                             * Sidebar + Topbar adaptado para gerentes
                                                                             */

                                                                            if (!defined('DB_HOST')) {
                                                                                require_once(__DIR__ . '/../config.php');
                                                                            }
                                                                            ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titulo_pagina) ? $titulo_pagina . ' - ' : ''; ?>Gerente - Mauro Calzado</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body class="gerente-panel">

    <!-- SIDEBAR -->
    <aside class="admin-sidebar" id="sidebar">
        <!-- Logo -->
        <div class="sidebar-logo">
            <img src="../img/logo.jpg" alt="Logo" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22%3E%3Crect fill=%22%2310B981%22 width=%2240%22 height=%2240%22 rx=%228%22/%3E%3Ctext x=%2220%22 y=%2227%22 text-anchor=%22middle%22 fill=%22white%22 font-size=%2218%22 font-weight=%22bold%22%3EMC%3C/text%3E%3C/svg%3E'">
            <h4>Mauro Calzado</h4>
        </div>

        <!-- Menu -->
        <ul class="sidebar-menu">
            <li class="menu-label">MI SUCURSAL</li>

            <li>
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-grid-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="caja.php">
                    <i class="bi bi-cash-coin"></i>
                    <span>Caja</span>
                </a>
            </li>
            <li>
                <a href="ventas.php">
                    <i class="bi bi-graph-up"></i>
                    <span>Ventas</span>
                </a>
            </li>

            <li>
                <a href="stock.php">
                    <i class="bi bi-boxes"></i>
                    <span>Stock</span>
                </a>
            </li>

            <li>
                <a href="pedidos.php">
                    <i class="bi bi-bag-check"></i>
                    <span>Pedidos</span>
                </a>
            </li>

            <li>
                <a href="transferencias.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'transferencias.php' ? 'active' : ''; ?>">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>Transferencias</span>
                </a>
            </li>

            <li class="menu-label">OTROS</li>

            <li>
                <a href="mensajes.php">
                    <i class="bi bi-file-earmark-richtext"></i>
                    <span>Mensajes</span>
                </a>
            </li>

            <li>
                <a href="../index.php">
                    <i class="bi bi-house-door"></i>
                    <span>Ver Tienda</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- BACKDROP -->
    <div class="sidebar-backdrop" id="backdrop" onclick="toggleSidebar()"></div>

    <!-- TOPBAR -->
    <header class="admin-topbar">
        <div class="topbar-left">
            <!-- Toggle Sidebar (mobile) -->
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list fs-4"></i>
            </button>

            <!-- Buscador 
            <div class="topbar-search">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Buscar..." class="form-control">
            </div>
        </div>-->

        <div class="topbar-right">
            <!-- Notificaciones  PROBLEMA NOTIFICACIONES-->
            <div class="topbar-icon" title="Notificaciones">
                <i class="bi bi-bell fs-5"></i>
                <span class="badge bg-success">2</span>
            </div>

            <!-- Usuario -->
            <div class="dropdown">
                <div class="user-menu" data-bs-toggle="dropdown">
                    <div class="user-info text-end d-none d-md-block">
                        <h6><?php echo $_SESSION['nombre']; ?></h6>
                        <span>Gerente</span>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['nombre'], 0, 1)); ?>
                    </div>
                    <i class="bi bi-chevron-down"></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                    <!-- <li><a class="dropdown-item" href="../mi-cuenta.php"><i class="bi bi-person me-2"></i>Mi Perfil</a></li> -->
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- CONTENT -->
    <main class="admin-content">