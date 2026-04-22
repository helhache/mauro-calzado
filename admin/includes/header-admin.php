<?php
/**
 * HEADER ADMIN - Panel de Administración
 * Sidebar + Topbar estilo moderno
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
    <title><?php echo isset($titulo_pagina) ? $titulo_pagina . ' - ' : ''; ?>Admin - Mauro Calzado</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="admin-panel">

<!-- SIDEBAR -->
<aside class="admin-sidebar" id="sidebar">
    <!-- Logo -->
    <div class="sidebar-logo">
        <img src="../img/logo.jpg" alt="Logo" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22%3E%3Crect fill=%22%233C50E0%22 width=%2240%22 height=%2240%22 rx=%228%22/%3E%3Ctext x=%2220%22 y=%2227%22 text-anchor=%22middle%22 fill=%22white%22 font-size=%2218%22 font-weight=%22bold%22%3EMC%3C/text%3E%3C/svg%3E'">
        <h4>Mauro Calzado</h4>
    </div>
    
    <!-- Menu -->
    <ul class="sidebar-menu">
        <li class="menu-label">MENÚ PRINCIPAL</li>
        
        <li>
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-grid-fill"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <li>
            <a href="productos.php">
                <i class="bi bi-box-seam"></i>
                <span>Productos</span>
            </a>
        </li>
        
        <li>
            <a href="usuarios.php">
                <i class="bi bi-people"></i>
                <span>Usuarios</span>
            </a>
        </li>
        
        <li>
            <a href="sucursales.php">
                <i class="bi bi-shop"></i>
                <span>Sucursales</span>
            </a>
        </li>
        
        <li>
            <a href="pedidos.php">
                <i class="bi bi-bag-check"></i>
                <span>Pedidos</span>
            </a>
        </li>
        
        <li>
            <a href="cajas.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'cajas.php' ? 'active' : ''; ?>">
                <i class="bi bi-cash-register"></i>
                <span>Cajas / Turnos</span>
            </a>
        </li>

        <li class="menu-label">OTROS</li>
        
        <li>
            <a href="transferencias.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'transferencias.php' ? 'active' : ''; ?>">
                <i class="bi bi-arrow-left-right"></i>
                <span>Transferencias</span>
            </a>
        </li>

        <li>
            <a href="bajas-productos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'bajas-productos.php' ? 'active' : ''; ?>">
                <i class="bi bi-box-arrow-down"></i>
                <span>Bajas de Productos</span>
            </a>
        </li>

        <li>
            <a href="reviews.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>">
                <i class="bi bi-star-half"></i>
                <span>Reseñas</span>
            </a>
        </li>

        <li>
            <a href="mensajes.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'mensajes.php' ? 'active' : ''; ?>">
                <i class="bi bi-chat-left-dots"></i>
                <span>Mensajes Gerentes</span>
            </a>
        </li>

        <li>
            <a href="mensajes-contacto.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'mensajes-contacto.php' ? 'active' : ''; ?>">
                <i class="bi bi-envelope-at"></i>
                <span>Contacto Clientes</span>
            </a>
        </li>
        
        <li>
            <a href="configuracion.php">
                <i class="bi bi-gear"></i>
                <span>Configuración</span>
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
        
        <!-- Breadcrumb o título de página -->
        <h5 class="mb-0 fw-semibold d-none d-md-block text-dark">
            <?php echo isset($titulo_pagina) ? $titulo_pagina : 'Panel de Control'; ?>
        </h5>
    </div>
    
    <div class="topbar-right">
        <!-- Notificaciones -->
        <?php
        // Obtener notificaciones no leídas
        $query_notif = "SELECT COUNT(*) as total 
                        FROM notificaciones 
                        WHERE leida = 0 AND visible_para IN ('admin', 'ambos')";
        $result_notif = mysqli_query($conn, $query_notif);
        $notif_count = mysqli_fetch_assoc($result_notif)['total'] ?? 0;
        
        // Obtener últimas 5 notificaciones
        $query_list = "SELECT * FROM notificaciones 
                       WHERE visible_para IN ('admin', 'ambos')
                       ORDER BY fecha_creacion DESC 
                       LIMIT 5";
        $result_list = mysqli_query($conn, $query_list);
        ?>
        <div class="dropdown">
            <div class="topbar-icon" data-bs-toggle="dropdown" title="Notificaciones">
                <i class="bi bi-bell fs-5"></i>
                <?php if ($notif_count > 0): ?>
                    <span class="badge bg-danger"><?php echo $notif_count; ?></span>
                <?php endif; ?>
            </div>
            <div class="dropdown-menu dropdown-menu-end shadow-lg" style="width: 380px; max-height: 500px; overflow-y: auto;">
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <h6 class="mb-0 fw-bold">Notificaciones</h6>
                    <?php if ($notif_count > 0): ?>
                        <a href="notificaciones.php?marcar_todas=1" class="btn btn-sm btn-link text-primary">Marcar todas</a>
                    <?php endif; ?>
                </div>
                <?php if (mysqli_num_rows($result_list) > 0): ?>
                    <?php while ($notif = mysqli_fetch_assoc($result_list)): ?>
                        <a href="<?php echo $notif['url'] ?? '#'; ?>?notif_id=<?php echo $notif['id']; ?>" 
                           class="dropdown-item px-3 py-3 <?php echo $notif['leida'] == 0 ? 'bg-light' : ''; ?>">
                            <div class="d-flex gap-3">
                                <div>
                                    <?php
                                    $icon_class = '';
                                    switch($notif['tipo']) {
                                        case 'pedido':
                                            $icon_class = 'bg-success';
                                            $icon = 'bi-bag-check';
                                            break;
                                        case 'stock_bajo':
                                            $icon_class = 'bg-warning';
                                            $icon = 'bi-exclamation-triangle';
                                            break;
                                        case 'nuevo_usuario':
                                            $icon_class = 'bg-primary';
                                            $icon = 'bi-person-plus';
                                            break;
                                        case 'review':
                                            $icon_class = 'bg-info';
                                            $icon = 'bi-star';
                                            break;
                                        case 'mensaje_cliente':
                                        case 'mensaje_gerente':
                                        case 'respuesta_admin':
                                            $icon_class = 'bg-info';
                                            $icon = 'bi-chat-dots';
                                            break;
                                        case 'cambio_estado':
                                        case 'sistema':
                                        default:
                                            $icon_class = 'bg-secondary';
                                            $icon = 'bi-bell';
                                            break;
                                    }
                                    ?>
                                    <div class="<?php echo $icon_class; ?> text-white rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                        <i class="bi <?php echo $icon; ?>"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold small"><?php echo htmlspecialchars($notif['titulo']); ?></h6>
                                    <p class="mb-1 text-muted small"><?php echo htmlspecialchars($notif['mensaje']); ?></p>
                                    <span class="text-muted" style="font-size: 11px;">
                                        <i class="bi bi-clock me-1"></i>
                                        <?php 
                                        $tiempo = time() - strtotime($notif['fecha_creacion']);
                                        if ($tiempo < 60) echo 'Hace un momento';
                                        elseif ($tiempo < 3600) echo 'Hace ' . floor($tiempo/60) . ' min';
                                        elseif ($tiempo < 86400) echo 'Hace ' . floor($tiempo/3600) . ' hrs';
                                        else echo date('d/m/Y', strtotime($notif['fecha_creacion']));
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                    <div class="text-center py-2 border-top">
                        <a href="notificaciones.php" class="btn btn-sm btn-link text-primary">Ver todas</a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
                        <p class="mb-0 small">No hay notificaciones</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Usuario -->
        <div class="dropdown">
            <div class="user-menu" data-bs-toggle="dropdown">
                <div class="user-info text-end d-none d-md-block">
                    <h6><?php echo $_SESSION['nombre']; ?></h6>
                    <span>Administrador</span>
                </div>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['nombre'], 0, 1)); ?>
                </div>
                <i class="bi bi-chevron-down"></i>
            </div>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="../mi-cuenta.php"><i class="bi bi-person me-2"></i>Mi Perfil</a></li>
                <li><a class="dropdown-item" href="configuracion.php"><i class="bi bi-gear me-2"></i>Configuración</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
            </ul>
        </div>
    </div>
</header>

<!-- CONTENT -->
<main class="admin-content">
