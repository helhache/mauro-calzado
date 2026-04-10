<?php
/**
 * ADMIN/BAJAS-PRODUCTOS.PHP
 * Historial completo de bajas de productos registradas por los gerentes
 */

require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

$titulo_pagina = "Historial de Bajas de Productos";

// Filtros
$filtro_sucursal = isset($_GET['sucursal']) ? intval($_GET['sucursal']) : 0;
$filtro_motivo   = isset($_GET['motivo'])   ? limpiarDato($_GET['motivo'])  : '';
$motivos_validos = ['mal_estado', 'vencido', 'dañado', 'robo', 'extravío', 'otro'];
if ($filtro_motivo && !in_array($filtro_motivo, $motivos_validos, true)) {
    $filtro_motivo = '';
}

// Obtener sucursales para filtro
$sucursales = mysqli_fetch_all(
    mysqli_query($conn, "SELECT id, nombre FROM sucursales WHERE activo = 1 ORDER BY nombre"),
    MYSQLI_ASSOC
);

// Build query con filtros opcionales
$where = ['1=1'];
$params = [];
$types  = '';

if ($filtro_sucursal > 0) {
    $where[] = 'b.sucursal_id = ?';
    $params[] = $filtro_sucursal;
    $types   .= 'i';
}
if ($filtro_motivo) {
    $where[] = 'b.motivo = ?';
    $params[] = $filtro_motivo;
    $types   .= 's';
}

$where_sql = implode(' AND ', $where);

$stmt = mysqli_prepare($conn,
    "SELECT b.id, b.cantidad, b.motivo, b.descripcion, b.fecha_baja, b.notificado_admin,
            p.nombre AS producto_nombre, p.imagen AS producto_imagen,
            s.nombre AS sucursal_nombre,
            u.nombre AS gerente_nombre
     FROM bajas_productos b
     INNER JOIN productos p  ON b.producto_id  = p.id
     INNER JOIN sucursales s ON b.sucursal_id  = s.id
     INNER JOIN usuarios u   ON b.usuario_id   = u.id
     WHERE $where_sql
     ORDER BY b.fecha_baja DESC"
);

if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$bajas = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Totales rápidos
$total_unidades = array_sum(array_column($bajas, 'cantidad'));

$label_motivos = [
    'mal_estado' => 'Mal estado',
    'vencido'    => 'Vencido',
    'dañado'     => 'Dañado',
    'robo'       => 'Robo',
    'extravío'   => 'Extravío',
    'otro'       => 'Otro',
];

$badge_motivos = [
    'mal_estado' => 'bg-secondary',
    'vencido'    => 'bg-warning text-dark',
    'dañado'     => 'bg-orange text-white',
    'robo'       => 'bg-danger',
    'extravío'   => 'bg-info text-dark',
    'otro'       => 'bg-secondary',
];

require_once('includes/header-admin.php');
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light p-3 mb-4">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Bajas de Productos</li>
    </ol>
</nav>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-box-arrow-down me-2"></i>Historial de Bajas de Productos</h2>
            <p class="text-muted">Registro de productos dados de baja por los gerentes de sucursal</p>
        </div>
    </div>

    <!-- Tarjeta resumen -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Total registros</p>
                            <h3 class="fw-bold mb-0"><?php echo count($bajas); ?></h3>
                        </div>
                        <div class="bg-danger bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-clipboard-x text-danger fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Unidades dadas de baja</p>
                            <h3 class="fw-bold mb-0"><?php echo $total_unidades; ?></h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-box-seam text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Sucursal</label>
                    <select name="sucursal" class="form-select">
                        <option value="0">Todas las sucursales</option>
                        <?php foreach ($sucursales as $suc): ?>
                            <option value="<?php echo $suc['id']; ?>"
                                <?php echo $filtro_sucursal === $suc['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($suc['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Motivo</label>
                    <select name="motivo" class="form-select">
                        <option value="">Todos los motivos</option>
                        <?php foreach ($label_motivos as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $filtro_motivo === $key ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="bajas-productos.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i>Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de bajas -->
    <?php if (empty($bajas)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-clipboard-check display-1 text-muted d-block mb-3"></i>
                <h5>No hay bajas registradas</h5>
                <?php if ($filtro_sucursal || $filtro_motivo): ?>
                    <p class="text-muted">Intenta cambiar los filtros</p>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Producto</th>
                                <th>Sucursal</th>
                                <th>Cantidad</th>
                                <th>Motivo</th>
                                <th>Descripción</th>
                                <th>Gerente</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bajas as $baja): ?>
                                <tr>
                                    <td class="text-muted small"><?php echo $baja['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if ($baja['producto_imagen']): ?>
                                                <img src="../img/productos/<?php echo htmlspecialchars($baja['producto_imagen']); ?>"
                                                     width="40" height="40" class="rounded object-fit-cover"
                                                     onerror="this.style.display='none'">
                                            <?php endif; ?>
                                            <span><?php echo htmlspecialchars($baja['producto_nombre']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($baja['sucursal_nombre']); ?></td>
                                    <td><strong><?php echo $baja['cantidad']; ?></strong> unid.</td>
                                    <td>
                                        <span class="badge <?php echo $badge_motivos[$baja['motivo']] ?? 'bg-secondary'; ?>">
                                            <?php echo $label_motivos[$baja['motivo']] ?? $baja['motivo']; ?>
                                        </span>
                                    </td>
                                    <td style="max-width:200px;">
                                        <?php if (!empty($baja['descripcion'])): ?>
                                            <span title="<?php echo htmlspecialchars($baja['descripcion']); ?>">
                                                <?php echo htmlspecialchars(mb_strimwidth($baja['descripcion'], 0, 80, '...')); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($baja['gerente_nombre']); ?></td>
                                    <td class="text-nowrap">
                                        <small><?php echo date('d/m/Y H:i', strtotime($baja['fecha_baja'])); ?></small>
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
