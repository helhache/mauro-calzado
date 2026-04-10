<?php
/**
 * NOTIFICACIONES - Ver todas las notificaciones
 */

require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

$titulo_pagina = "Notificaciones";

// Marcar todas como leídas
if (isset($_GET['marcar_todas'])) {
    mysqli_query($conn, "UPDATE notificaciones SET leida = 1 WHERE visible_para IN ('admin', 'ambos')");
    header('Location: notificaciones.php');
    exit;
}

// Marcar una como leída
if (isset($_GET['marcar'])) {
    $id = intval($_GET['marcar']);
    $stmt = mysqli_prepare($conn, "UPDATE notificaciones SET leida = 1 WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    header('Location: notificaciones.php');
    exit;
}

// Eliminar notificación
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $stmt = mysqli_prepare($conn, "DELETE FROM notificaciones WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    header('Location: notificaciones.php');
    exit;
}

// Obtener todas las notificaciones
$filtro = $_GET['filtro'] ?? 'todas';
$where = "visible_para IN ('admin', 'ambos')";

if ($filtro == 'no_leidas') {
    $where .= " AND leida = 0";
} elseif ($filtro != 'todas') {
    // Permitir solo letras, números y guiones bajos (nombres de tipo de notificación)
    if (preg_match('/^[a-z0-9_]+$/', $filtro)) {
        $where .= " AND tipo = '{$filtro}'";
    }
}

$query = "SELECT * FROM notificaciones 
          WHERE $where
          ORDER BY fecha_creacion DESC";
$result = mysqli_query($conn, $query);

require_once('includes/header-admin.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-2 fw-bold text-dark">Notificaciones</h1>
        <p class="text-muted mb-0">Gestiona todas las notificaciones del sistema</p>
    </div>
    <div>
        <a href="?marcar_todas=1" class="btn btn-primary">
            <i class="bi bi-check-all me-2"></i>Marcar todas como leídas
        </a>
    </div>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="btn-group" role="group">
            <a href="?filtro=todas" class="btn btn-<?php echo $filtro == 'todas' ? 'primary' : 'outline-secondary'; ?>">
                Todas
            </a>
            <a href="?filtro=no_leidas" class="btn btn-<?php echo $filtro == 'no_leidas' ? 'primary' : 'outline-secondary'; ?>">
                No leídas
            </a>
            <a href="?filtro=pedido" class="btn btn-<?php echo $filtro == 'pedido' ? 'primary' : 'outline-secondary'; ?>">
                Pedidos
            </a>
            <a href="?filtro=stock_bajo" class="btn btn-<?php echo $filtro == 'stock_bajo' ? 'primary' : 'outline-secondary'; ?>">
                Stock bajo
            </a>
            <a href="?filtro=nuevo_usuario" class="btn btn-<?php echo $filtro == 'nuevo_usuario' ? 'primary' : 'outline-secondary'; ?>">
                Nuevos usuarios
            </a>
            <a href="?filtro=review" class="btn btn-<?php echo $filtro == 'review' ? 'primary' : 'outline-secondary'; ?>">
                Reviews
            </a>
        </div>
    </div>
</div>

<!-- Lista de Notificaciones -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="list-group list-group-flush">
                <?php while ($notif = mysqli_fetch_assoc($result)): ?>
                    <div class="list-group-item <?php echo $notif['leida'] == 0 ? 'bg-light' : ''; ?>">
                        <div class="d-flex gap-3 align-items-start">
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
                                <div class="<?php echo $icon_class; ?> text-white rounded-circle d-flex align-items-center justify-content-center" style="width:50px;height:50px;">
                                    <i class="bi <?php echo $icon; ?> fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0 fw-semibold"><?php echo htmlspecialchars($notif['titulo']); ?></h6>
                                    <div class="d-flex gap-2">
                                        <?php if ($notif['leida'] == 0): ?>
                                            <a href="?marcar=<?php echo $notif['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?eliminar=<?php echo $notif['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar esta notificación?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                                <p class="mb-2 text-muted"><?php echo htmlspecialchars($notif['mensaje']); ?></p>
                                <div class="d-flex gap-3 small text-muted">
                                    <span>
                                        <i class="bi bi-clock me-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($notif['fecha_creacion'])); ?>
                                    </span>
                                    <?php if ($notif['url']): ?>
                                        <a href="<?php echo $notif['url']; ?>" class="text-primary">
                                            <i class="bi bi-link-45deg me-1"></i>Ver detalles
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-bell-slash fs-1 d-block mb-3"></i>
                <h5>No hay notificaciones</h5>
                <p>Cuando haya nuevas notificaciones, aparecerán aquí</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once('includes/footer-admin.php'); ?>
