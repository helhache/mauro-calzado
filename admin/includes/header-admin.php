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
<?php
$admin_notif_count = 0;
$admin_notif_lista = [];
$stmt_an = mysqli_prepare($conn,
    "SELECT id, tipo, titulo, mensaje, url, fecha_creacion
     FROM notificaciones
     WHERE visible_para IN ('admin','ambos') AND leida = 0
     ORDER BY fecha_creacion DESC LIMIT 6");
if ($stmt_an) {
    mysqli_stmt_execute($stmt_an);
    $res_an = mysqli_stmt_get_result($stmt_an);
    while ($an = mysqli_fetch_assoc($res_an)) { $admin_notif_lista[] = $an; }
    $admin_notif_count = count($admin_notif_lista);
    mysqli_free_result($res_an);
    mysqli_stmt_close($stmt_an);
}
?>
<header class="admin-topbar">

    <!-- IZQUIERDA: hamburguesa -->
    <div class="topbar-left">
        <button class="topbar-hamburger" onclick="toggleSidebar()" title="Menú">
            <i class="bi bi-list"></i>
        </button>
    </div>

    <!-- DERECHA: campana + cerrar sesión -->
    <div class="topbar-right">

        <!-- Campana con dropdown de notificaciones -->
        <div class="dropdown">
            <button class="topbar-btn" data-bs-toggle="dropdown" title="Notificaciones">
                <i class="bi bi-bell"></i>
                <?php if ($admin_notif_count > 0): ?>
                    <span class="topbar-badge"><?php echo $admin_notif_count; ?></span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end shadow" style="width:320px; max-height:400px; overflow-y:auto;">
                <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                    <strong class="small">Notificaciones</strong>
                    <?php if ($admin_notif_count > 0): ?>
                        <span class="badge bg-danger"><?php echo $admin_notif_count; ?> nuevas</span>
                    <?php endif; ?>
                </div>
                <?php if (empty($admin_notif_lista)): ?>
                    <div class="text-center text-muted py-4 small">
                        <i class="bi bi-bell-slash d-block fs-3 mb-1"></i>Sin notificaciones
                    </div>
                <?php else: ?>
                    <?php foreach ($admin_notif_lista as $notif): ?>
                    <a href="<?php echo htmlspecialchars($notif['url'] ?? '#'); ?>"
                       class="dropdown-item border-bottom py-2 px-3">
                        <div class="small fw-semibold"><?php echo htmlspecialchars($notif['titulo']); ?></div>
                        <div class="text-muted" style="font-size:11px; white-space:normal;">
                            <?php echo htmlspecialchars(mb_substr($notif['mensaje'], 0, 80)) . (mb_strlen($notif['mensaje']) > 80 ? '…' : ''); ?>
                        </div>
                        <div class="text-muted" style="font-size:10px;">
                            <?php echo date('d/m/Y H:i', strtotime($notif['fecha_creacion'])); ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cerrar sesión -->
        <a href="../logout.php" class="topbar-btn topbar-btn-logout" title="Cerrar sesión">
            <i class="bi bi-box-arrow-right"></i>
            <span class="d-none d-md-inline ms-1">Salir</span>
        </a>

    </div>
</header>

<script>
// Auto-marcar notificaciones como leídas al abrir el dropdown
document.addEventListener('DOMContentLoaded', function() {
    var bellBtn = document.querySelector('.topbar-btn[data-bs-toggle="dropdown"]');
    if (bellBtn) {
        bellBtn.addEventListener('click', function() {
            var badge = bellBtn.querySelector('.topbar-badge');
            if (badge) {
                fetch('../ajax/marcar-notificaciones-leidas.php', { method: 'POST' })
                    .then(function(r) { return r.json(); })
                    .then(function() { badge.remove(); });
            }
        });
    }
});
</script>

<!-- CONTENT -->
<main class="admin-content">
