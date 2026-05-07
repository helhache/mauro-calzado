<?php
/**
 * HEADER GERENTE - Panel de Gerencia
 */
if (!defined('DB_HOST')) {
    require_once(__DIR__ . '/../config.php');
}

// Notificaciones del gerente
$notif_count = 0;
$notif_lista = [];
$stmt_n = mysqli_prepare($conn,
    "SELECT id, tipo, titulo, mensaje, url, fecha_creacion
     FROM notificaciones
     WHERE visible_para IN ('gerente','ambos') AND leida = 0
     ORDER BY fecha_creacion DESC LIMIT 6");
if ($stmt_n) {
    mysqli_stmt_execute($stmt_n);
    $res_n = mysqli_stmt_get_result($stmt_n);
    while ($n = mysqli_fetch_assoc($res_n)) { $notif_lista[] = $n; }
    $notif_count = count($notif_lista);
    mysqli_free_result($res_n);
    mysqli_stmt_close($stmt_n);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titulo_pagina) ? htmlspecialchars($titulo_pagina) . ' - ' : ''; ?>Gerente - Mauro Calzado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body class="gerente-panel">

    <!-- SIDEBAR -->
    <aside class="admin-sidebar" id="sidebar">
        <div class="sidebar-logo">
            <img src="../img/logo.jpg" alt="Logo"
                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22%3E%3Crect fill=%22%2310B981%22 width=%2240%22 height=%2240%22 rx=%228%22/%3E%3Ctext x=%2220%22 y=%2227%22 text-anchor=%22middle%22 fill=%22white%22 font-size=%2218%22 font-weight=%22bold%22%3EMC%3C/text%3E%3C/svg%3E'">
            <h4>Mauro Calzado</h4>
        </div>

        <ul class="sidebar-menu">
            <li class="menu-label">MI SUCURSAL</li>
            <li>
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-grid-fill"></i><span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="caja.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'caja.php' ? 'active' : ''; ?>">
                    <i class="bi bi-cash-coin"></i><span>Caja</span>
                </a>
            </li>
            <li>
                <a href="ventas.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'ventas.php' ? 'active' : ''; ?>">
                    <i class="bi bi-graph-up"></i><span>Ventas</span>
                </a>
            </li>
            <li>
                <a href="stock.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'stock.php' ? 'active' : ''; ?>">
                    <i class="bi bi-boxes"></i><span>Stock</span>
                </a>
            </li>
            <li>
                <a href="productos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'productos.php' ? 'active' : ''; ?>">
                    <i class="bi bi-box-seam"></i><span>Productos</span>
                </a>
            </li>
            <li>
                <a href="pedidos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'pedidos.php' ? 'active' : ''; ?>">
                    <i class="bi bi-bag-check"></i><span>Pedidos</span>
                </a>
            </li>
            <li>
                <a href="transferencias.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'transferencias.php' ? 'active' : ''; ?>">
                    <i class="bi bi-arrow-left-right"></i><span>Transferencias</span>
                </a>
            </li>
            <li class="menu-label">OTROS</li>
            <li>
                <a href="mensajes.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'mensajes.php' ? 'active' : ''; ?>">
                    <i class="bi bi-chat-dots"></i><span>Mensajes</span>
                </a>
            </li>
            <li>
                <a href="../index.php">
                    <i class="bi bi-house-door"></i><span>Ver Tienda</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- BACKDROP mobile -->
    <div class="sidebar-backdrop" id="backdrop" onclick="toggleSidebar()"></div>

    <!-- TOPBAR -->
    <header class="admin-topbar">

        <!-- IZQUIERDA: hamburguesa (siempre visible) -->
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
                    <?php if ($notif_count > 0): ?>
                        <span class="topbar-badge"><?php echo $notif_count; ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow" style="width:320px; max-height:400px; overflow-y:auto;">
                    <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                        <strong class="small">Notificaciones</strong>
                        <?php if ($notif_count > 0): ?>
                            <span class="badge bg-danger"><?php echo $notif_count; ?> nuevas</span>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($notif_lista)): ?>
                        <div class="text-center text-muted py-4 small">
                            <i class="bi bi-bell-slash d-block fs-3 mb-1"></i>Sin notificaciones
                        </div>
                    <?php else: ?>
                        <?php foreach ($notif_lista as $notif): ?>
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
