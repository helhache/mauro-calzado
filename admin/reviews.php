<?php
/**
 * ADMIN/REVIEWS.PHP
 * Panel de moderación de reseñas de productos
 */

require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

$titulo_pagina = "Reseñas de Productos";

// Aprobar reseña
if (isset($_GET['aprobar'])) {
    $id = intval($_GET['aprobar']);
    $stmt = mysqli_prepare($conn, "UPDATE reviews SET aprobada = 1 WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: reviews.php?filtro=' . ($_GET['filtro'] ?? 'pendiente'));
    exit;
}

// Rechazar/eliminar reseña
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $stmt = mysqli_prepare($conn, "DELETE FROM reviews WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: reviews.php?filtro=' . ($_GET['filtro'] ?? 'pendiente'));
    exit;
}

// Filtro
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'pendiente';
$filtros_validos = ['pendiente' => 0, 'aprobada' => 1, 'todas' => null];
if (!array_key_exists($filtro, $filtros_validos)) {
    $filtro = 'pendiente';
}

// Query con filtro
if ($filtro === 'todas') {
    $stmt = mysqli_prepare($conn,
        "SELECT r.*, u.nombre AS nombre_usuario, u.email,
                p.nombre AS nombre_producto, p.id AS prod_id
         FROM reviews r
         INNER JOIN usuarios u ON r.usuario_id = u.id
         INNER JOIN productos p ON r.producto_id = p.id
         ORDER BY r.aprobada ASC, r.fecha_creacion DESC"
    );
    mysqli_stmt_execute($stmt);
} else {
    $aprobada_val = $filtros_validos[$filtro];
    $stmt = mysqli_prepare($conn,
        "SELECT r.*, u.nombre AS nombre_usuario, u.email,
                p.nombre AS nombre_producto, p.id AS prod_id
         FROM reviews r
         INNER JOIN usuarios u ON r.usuario_id = u.id
         INNER JOIN productos p ON r.producto_id = p.id
         WHERE r.aprobada = ?
         ORDER BY r.fecha_creacion DESC"
    );
    mysqli_stmt_bind_param($stmt, 'i', $aprobada_val);
    mysqli_stmt_execute($stmt);
}

$result  = mysqli_stmt_get_result($stmt);
$reviews = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Contador pendientes para badge
$stmt_pend = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM reviews WHERE aprobada = 0");
mysqli_stmt_execute($stmt_pend);
$pendientes = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_pend))['total'];
mysqli_stmt_close($stmt_pend);

require_once('includes/header-admin.php');
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light p-3 mb-4">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Reseñas</li>
    </ol>
</nav>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-star-half me-2"></i>Reseñas de Productos</h2>
            <p class="text-muted">Modera las reseñas enviadas por los clientes</p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex gap-2 flex-wrap">
            <a href="reviews.php?filtro=pendiente"
               class="btn <?php echo $filtro === 'pendiente' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                <i class="bi bi-clock me-1"></i>Pendientes
                <?php if ($pendientes > 0): ?>
                    <span class="badge bg-danger ms-1"><?php echo $pendientes; ?></span>
                <?php endif; ?>
            </a>
            <a href="reviews.php?filtro=aprobada"
               class="btn <?php echo $filtro === 'aprobada' ? 'btn-success' : 'btn-outline-success'; ?>">
                <i class="bi bi-check-circle me-1"></i>Aprobadas
            </a>
            <a href="reviews.php?filtro=todas"
               class="btn <?php echo $filtro === 'todas' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="bi bi-list-ul me-1"></i>Todas
            </a>
        </div>
    </div>

    <?php if (empty($reviews)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-star display-1 text-muted d-block mb-3"></i>
                <h5>No hay reseñas <?php echo $filtro === 'pendiente' ? 'pendientes' : ($filtro === 'aprobada' ? 'aprobadas' : ''); ?></h5>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th>Cliente</th>
                                <th>Calificación</th>
                                <th>Comentario</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviews as $r): ?>
                                <tr>
                                    <td>
                                        <a href="../producto.php?id=<?php echo $r['prod_id']; ?>" target="_blank" class="text-decoration-none">
                                            <?php echo htmlspecialchars($r['nombre_producto']); ?>
                                            <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($r['nombre_usuario']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($r['email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="text-warning">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star<?php echo $i <= $r['calificacion'] ? '-fill' : ''; ?>"></i>
                                            <?php endfor; ?>
                                        </span>
                                        <span class="ms-1 fw-bold"><?php echo $r['calificacion']; ?>/5</span>
                                    </td>
                                    <td style="max-width:300px;">
                                        <?php if (!empty($r['comentario'])): ?>
                                            <span title="<?php echo htmlspecialchars($r['comentario']); ?>">
                                                <?php echo htmlspecialchars(mb_strimwidth($r['comentario'], 0, 100, '...')); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">Sin comentario</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <small><?php echo date('d/m/Y H:i', strtotime($r['fecha_creacion'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($r['aprobada'] == 1): ?>
                                            <span class="badge bg-success">Aprobada</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <?php if ($r['aprobada'] == 0): ?>
                                            <a href="reviews.php?aprobar=<?php echo $r['id']; ?>&filtro=<?php echo $filtro; ?>"
                                               class="btn btn-sm btn-success me-1"
                                               data-confirm="¿Aprobar esta reseña?" data-confirm-tipo="success" data-confirm-ok="Sí, aprobar" data-confirm-titulo="Aprobar reseña">
                                                <i class="bi bi-check-lg"></i> Aprobar
                                            </a>
                                        <?php endif; ?>
                                        <a href="reviews.php?eliminar=<?php echo $r['id']; ?>&filtro=<?php echo $filtro; ?>"
                                           class="btn btn-sm btn-danger"
                                           data-confirm="¿Eliminar esta reseña? Esta acción no se puede deshacer." data-confirm-tipo="danger" data-confirm-ok="Sí, eliminar" data-confirm-titulo="Eliminar reseña">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once('includes/footer-admin.php'); ?>
