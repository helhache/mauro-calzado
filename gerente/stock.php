<?php
require_once '../includes/config.php';
require_once '../includes/verificar-gerente.php';

$titulo_pagina = 'Gestión de Stock';
$sucursal_id   = obtenerSucursalGerente();

// ============================================================
// KPI CARDS
// ============================================================
$stmt = mysqli_prepare($conn,
    "SELECT
        COUNT(*)                                              AS total_productos,
        SUM(CASE WHEN ss.cantidad = 0 THEN 1 ELSE 0 END)    AS sin_stock,
        SUM(CASE WHEN ss.cantidad > 0 AND ss.cantidad <= ss.cantidad_minima THEN 1 ELSE 0 END) AS stock_bajo,
        SUM(CASE WHEN ss.cantidad > ss.cantidad_minima THEN 1 ELSE 0 END) AS stock_optimo
     FROM stock_sucursal ss
     INNER JOIN productos p ON ss.producto_id = p.id
     WHERE ss.sucursal_id = ? AND p.activo = 1");
mysqli_stmt_bind_param($stmt, "i", $sucursal_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$kpi = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

$total_productos = $kpi['total_productos'] ?? 0;
$sin_stock       = $kpi['sin_stock']       ?? 0;
$stock_bajo_qty  = $kpi['stock_bajo']      ?? 0;
$stock_optimo    = $kpi['stock_optimo']    ?? 0;

// ============================================================
// FILTROS
// ============================================================
$busqueda   = isset($_GET['busqueda'])  ? limpiarDato($_GET['busqueda'])  : '';
$filtro_estado = isset($_GET['estado']) ? limpiarDato($_GET['estado'])    : '';

// ============================================================
// QUERY PRINCIPAL de inventario
// ============================================================
$where_extra  = '';
$params_types = "i";
$params       = [$sucursal_id];

if ($busqueda !== '') {
    $where_extra  .= " AND p.nombre LIKE ?";
    $params_types .= "s";
    $params[]      = "%$busqueda%";
}

if ($filtro_estado === 'sin_stock') {
    $where_extra .= " AND ss.cantidad = 0";
} elseif ($filtro_estado === 'stock_bajo') {
    $where_extra .= " AND ss.cantidad > 0 AND ss.cantidad <= ss.cantidad_minima";
} elseif ($filtro_estado === 'optimo') {
    $where_extra .= " AND ss.cantidad > ss.cantidad_minima";
}

$sql_count = "SELECT COUNT(*) as total
              FROM stock_sucursal ss
              INNER JOIN productos p ON ss.producto_id = p.id
              WHERE ss.sucursal_id = ? AND p.activo = 1" . $where_extra;
$stmt = mysqli_prepare($conn, $sql_count);
mysqli_stmt_bind_param($stmt, $params_types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$total_rows = mysqli_fetch_assoc($res)['total'];
mysqli_free_result($res);
mysqli_stmt_close($stmt);

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;
$total_pages = max(1, ceil($total_rows / $limit));

$sql_inv = "SELECT ss.id, ss.producto_id, ss.cantidad, ss.cantidad_minima, ss.ultima_actualizacion,
                   p.nombre, p.imagen, p.precio, p.categoria_id,
                   c.nombre AS categoria_nombre
            FROM stock_sucursal ss
            INNER JOIN productos p ON ss.producto_id = p.id
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE ss.sucursal_id = ? AND p.activo = 1" . $where_extra . "
            ORDER BY ss.cantidad ASC, p.nombre ASC
            LIMIT ? OFFSET ?";
$params_pag   = array_merge($params, [$limit, $offset]);
$params_types_pag = $params_types . "ii";
$stmt = mysqli_prepare($conn, $sql_inv);
mysqli_stmt_bind_param($stmt, $params_types_pag, ...$params_pag);
mysqli_stmt_execute($stmt);
$productos_stock = [];
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $productos_stock[] = $row;
}
mysqli_free_result($res);
mysqli_stmt_close($stmt);

// ============================================================
// CATÁLOGO COMPLETO (todos los productos, con mi stock aunque sea 0)
// ============================================================
$busqueda_cat = isset($_GET['busqueda_cat']) ? limpiarDato($_GET['busqueda_cat']) : '';

$where_cat = '';
$params_cat_types = "i";
$params_cat = [$sucursal_id];
if ($busqueda_cat !== '') {
    $where_cat = " AND p.nombre LIKE ?";
    $params_cat_types .= "s";
    $params_cat[] = "%$busqueda_cat%";
}

$stmt = mysqli_prepare($conn,
    "SELECT COUNT(*) as total FROM productos p WHERE p.activo = 1" . $where_cat);
// Para este count no necesitamos sucursal_id
$stmt2 = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM productos p WHERE p.activo = 1" . ($busqueda_cat !== '' ? " AND p.nombre LIKE ?" : ""));
if ($busqueda_cat !== '') {
    $b = "%$busqueda_cat%";
    mysqli_stmt_bind_param($stmt2, "s", $b);
} else {
    // no extra params
}
mysqli_stmt_execute($stmt2);
$res = mysqli_stmt_get_result($stmt2);
$total_cat = mysqli_fetch_assoc($res)['total'];
mysqli_free_result($res);
mysqli_stmt_close($stmt2);

$page_cat   = max(1, (int)($_GET['page_cat'] ?? 1));
$limit_cat  = 25;
$offset_cat = ($page_cat - 1) * $limit_cat;
$total_pages_cat = max(1, ceil($total_cat / $limit_cat));

$sql_cat = "SELECT p.id, p.nombre, p.imagen, p.precio, p.activo,
                   c.nombre AS categoria_nombre,
                   COALESCE(ss.cantidad, 0) AS mi_stock,
                   COALESCE(ss.cantidad_minima, 10) AS cantidad_minima,
                   ss.ultima_actualizacion
            FROM productos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            LEFT JOIN stock_sucursal ss ON p.id = ss.producto_id AND ss.sucursal_id = ?
            WHERE p.activo = 1" . ($busqueda_cat !== '' ? " AND p.nombre LIKE ?" : "") . "
            ORDER BY p.nombre ASC
            LIMIT ? OFFSET ?";

$params_cat_full_types = "i" . ($busqueda_cat !== '' ? "s" : "") . "ii";
$params_cat_full = [$sucursal_id];
if ($busqueda_cat !== '') $params_cat_full[] = "%$busqueda_cat%";
$params_cat_full[] = $limit_cat;
$params_cat_full[] = $offset_cat;

$stmt = mysqli_prepare($conn, $sql_cat);
mysqli_stmt_bind_param($stmt, $params_cat_full_types, ...$params_cat_full);
mysqli_stmt_execute($stmt);
$catalogo_productos = [];
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $catalogo_productos[] = $row;
}
mysqli_free_result($res);
mysqli_stmt_close($stmt);

// ============================================================
// TRANSFERENCIAS ENTRANTES (hacia MI sucursal)
// ============================================================
$stmt = mysqli_prepare($conn,
    "SELECT t.id, t.cantidad, t.estado, t.motivo, t.fecha_solicitud, t.fecha_envio,
            p.nombre AS producto_nombre, p.imagen AS producto_imagen,
            so.nombre AS origen_nombre
     FROM transferencias_stock t
     INNER JOIN productos p ON t.producto_id = p.id
     INNER JOIN sucursales so ON t.sucursal_origen_id = so.id
     WHERE t.sucursal_destino_id = ?
       AND t.estado IN ('pendiente', 'en_transito')
     ORDER BY t.fecha_solicitud DESC");
mysqli_stmt_bind_param($stmt, 'i', $sucursal_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$transferencias_entrantes = [];
while ($row = mysqli_fetch_assoc($res)) {
    $transferencias_entrantes[] = $row;
}
mysqli_free_result($res);
mysqli_stmt_close($stmt);

// Obtener otras sucursales (para el modal "Solicitar pares")
$stmt = mysqli_prepare($conn, "SELECT id, nombre FROM sucursales WHERE id != ? AND activo = 1 ORDER BY nombre");
mysqli_stmt_bind_param($stmt, 'i', $sucursal_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$otras_sucursales = [];
while ($row = mysqli_fetch_assoc($res)) {
    $otras_sucursales[] = $row;
}
mysqli_free_result($res);
mysqli_stmt_close($stmt);

include('includes/header-gerente.php');
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light p-3 mb-4">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Stock</li>
    </ol>
</nav>

<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h2 class="mb-0"><i class="bi bi-boxes me-2"></i>Gestión de Stock</h2>
            <p class="text-muted mb-0">Controla el inventario y existencias de tu sucursal</p>
        </div>
        <div class="col-auto">
            <a href="transferencias.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left-right me-1"></i>Solicitar Transferencia
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <a href="stock.php" class="text-decoration-none">
                <div class="card border-0 shadow-sm <?php echo $filtro_estado === '' ? 'border-primary border-2' : ''; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Total Productos</p>
                                <h4 class="mb-0 fw-bold"><?php echo $total_productos; ?></h4>
                            </div>
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-box-seam text-primary fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="stock.php?estado=stock_bajo<?php echo $busqueda ? '&busqueda=' . urlencode($busqueda) : ''; ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm <?php echo $filtro_estado === 'stock_bajo' ? 'border-warning border-2' : ''; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Stock Bajo</p>
                                <h4 class="mb-0 fw-bold text-warning"><?php echo $stock_bajo_qty; ?></h4>
                            </div>
                            <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-exclamation-triangle text-warning fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="stock.php?estado=sin_stock<?php echo $busqueda ? '&busqueda=' . urlencode($busqueda) : ''; ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm <?php echo $filtro_estado === 'sin_stock' ? 'border-danger border-2' : ''; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Sin Stock</p>
                                <h4 class="mb-0 fw-bold text-danger"><?php echo $sin_stock; ?></h4>
                            </div>
                            <div class="bg-danger bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-x-circle text-danger fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="stock.php?estado=optimo<?php echo $busqueda ? '&busqueda=' . urlencode($busqueda) : ''; ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm <?php echo $filtro_estado === 'optimo' ? 'border-success border-2' : ''; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Stock Óptimo</p>
                                <h4 class="mb-0 fw-bold text-success"><?php echo $stock_optimo; ?></h4>
                            </div>
                            <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-check-circle text-success fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- TABS -->
    <?php $tab_activa = isset($_GET['tab']) ? limpiarDato($_GET['tab']) : 'inventario'; ?>
    <ul class="nav nav-tabs mb-0" id="tabsStock">
        <li class="nav-item">
            <a class="nav-link <?php echo $tab_activa === 'inventario' ? 'active' : ''; ?>"
               href="?tab=inventario<?php echo $busqueda ? '&busqueda=' . urlencode($busqueda) : ''; ?><?php echo $filtro_estado ? '&estado=' . urlencode($filtro_estado) : ''; ?>">
                <i class="bi bi-boxes me-1"></i>Mi Inventario
                <?php if ($stock_bajo_qty > 0 || $sin_stock > 0): ?>
                    <span class="badge bg-danger ms-1"><?php echo $stock_bajo_qty + $sin_stock; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab_activa === 'catalogo' ? 'active' : ''; ?>"
               href="?tab=catalogo<?php echo $busqueda_cat ? '&busqueda_cat=' . urlencode($busqueda_cat) : ''; ?>">
                <i class="bi bi-grid me-1"></i>Catálogo Completo
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab_activa === 'entrantes' ? 'active' : ''; ?>"
               href="?tab=entrantes">
                <i class="bi bi-inbox-fill me-1"></i>Transferencias Entrantes
                <?php if (!empty($transferencias_entrantes)): ?>
                    <span class="badge bg-warning text-dark ms-1"><?php echo count($transferencias_entrantes); ?></span>
                <?php endif; ?>
            </a>
        </li>
    </ul>

    <!-- TAB: Mi Inventario -->
    <?php if ($tab_activa === 'inventario'): ?>
    <div class="card border-0 shadow-sm border-top-0 rounded-top-0">
        <div class="card-header bg-white py-3">
            <!-- Búsqueda -->
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="tab" value="inventario">
                <?php if ($filtro_estado !== ''): ?>
                    <input type="hidden" name="estado" value="<?php echo htmlspecialchars($filtro_estado); ?>">
                <?php endif; ?>
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="busqueda"
                               value="<?php echo htmlspecialchars($busqueda); ?>"
                               placeholder="Buscar producto...">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
                    <a href="stock.php?tab=inventario" class="btn btn-outline-secondary btn-sm ms-1">Limpiar</a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width:60px;"></th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th class="text-center">Stock Actual</th>
                            <th class="text-center">Stock Mínimo</th>
                            <th>Estado</th>
                            <th>Última Act.</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($productos_stock)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No se encontraron productos<?php echo $busqueda !== '' ? ' para "' . htmlspecialchars($busqueda) . '"' : ''; ?>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($productos_stock as $prod): ?>
                        <?php
                            if ($prod['cantidad'] == 0) {
                                $estado_badge  = '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Sin Stock</span>';
                            } elseif ($prod['cantidad'] <= $prod['cantidad_minima']) {
                                $estado_badge  = '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Stock Bajo</span>';
                            } else {
                                $estado_badge  = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Óptimo</span>';
                            }
                        ?>
                        <tr>
                            <td class="ps-3">
                                <?php if ($prod['imagen']): ?>
                                <img src="../img/productos/<?php echo htmlspecialchars($prod['imagen']); ?>"
                                     alt="" class="rounded"
                                     style="width:48px;height:48px;object-fit:cover;"
                                     onerror="this.src='../img/default-product.jpg'">
                                <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                     style="width:48px;height:48px;">
                                    <i class="bi bi-image text-muted"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($prod['nombre']); ?></strong>
                                <br><small class="text-muted">$<?php echo number_format($prod['precio'], 2, ',', '.'); ?></small>
                            </td>
                            <td>
                                <span class="text-muted"><?php echo htmlspecialchars($prod['categoria_nombre'] ?? '-'); ?></span>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold fs-5 <?php echo $prod['cantidad'] == 0 ? 'text-danger' : ($prod['cantidad'] <= $prod['cantidad_minima'] ? 'text-warning' : 'text-success'); ?>">
                                    <?php echo $prod['cantidad']; ?>
                                </span>
                                <br><small class="text-muted">unidades</small>
                            </td>
                            <td class="text-center text-muted"><?php echo $prod['cantidad_minima']; ?></td>
                            <td><?php echo $estado_badge; ?></td>
                            <td>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y H:i', strtotime($prod['ultima_actualizacion'])); ?>
                                </small>
                            </td>
                            <td class="text-end pe-3">
                                <button class="btn btn-sm btn-outline-primary"
                                        onclick="abrirModalStock(<?php echo $prod['producto_id']; ?>, '<?php echo addslashes(htmlspecialchars($prod['nombre'])); ?>', <?php echo $prod['cantidad']; ?>, <?php echo $sucursal_id; ?>)">
                                    <i class="bi bi-pencil me-1"></i>Actualizar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
            <div class="px-3 py-3 border-top d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Página <?php echo $page; ?> de <?php echo $total_pages; ?> (<?php echo $total_rows; ?> productos)
                </small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">&laquo;</a>
                        </li>
                        <?php endif; ?>
                        <?php for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++): ?>
                        <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $p])); ?>"><?php echo $p; ?></a>
                        </li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">&raquo;</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; /* fin tab inventario */ ?>

    <!-- TAB: Catálogo Completo -->
    <?php if ($tab_activa === 'catalogo'): ?>
    <div class="card border-0 shadow-sm border-top-0 rounded-top-0">
        <div class="card-header bg-white py-3">
            <p class="text-muted small mb-2">Todos los productos del catálogo. Podés solicitar pares de cualquier producto aunque no lo tengas en stock.</p>
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="tab" value="catalogo">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="busqueda_cat"
                               value="<?php echo htmlspecialchars($busqueda_cat); ?>"
                               placeholder="Buscar en catálogo...">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
                    <a href="stock.php?tab=catalogo" class="btn btn-outline-secondary btn-sm ms-1">Limpiar</a>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width:60px;"></th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th class="text-center">Mi Stock</th>
                            <th>Estado en Sucursal</th>
                            <th class="text-end pe-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($catalogo_productos)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No se encontraron productos<?php echo $busqueda_cat !== '' ? ' para "' . htmlspecialchars($busqueda_cat) . '"' : ''; ?>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($catalogo_productos as $prod): ?>
                        <?php
                            if ($prod['mi_stock'] == 0) {
                                $est = '<span class="badge bg-secondary"><i class="bi bi-dash-circle me-1"></i>Sin registrar</span>';
                            } elseif ($prod['mi_stock'] <= $prod['cantidad_minima']) {
                                $est = '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Stock Bajo ('.$prod['mi_stock'].')</span>';
                            } else {
                                $est = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Óptimo ('.$prod['mi_stock'].')</span>';
                            }
                        ?>
                        <tr>
                            <td class="ps-3">
                                <?php if ($prod['imagen']): ?>
                                <img src="../img/productos/<?php echo htmlspecialchars($prod['imagen']); ?>"
                                     alt="" class="rounded"
                                     style="width:48px;height:48px;object-fit:cover;"
                                     onerror="this.src='../img/default-product.jpg'">
                                <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                                    <i class="bi bi-image text-muted"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($prod['nombre']); ?></strong>
                                <br><small class="text-muted">$<?php echo number_format($prod['precio'], 2, ',', '.'); ?></small>
                            </td>
                            <td><span class="text-muted"><?php echo htmlspecialchars($prod['categoria_nombre'] ?? '-'); ?></span></td>
                            <td class="text-center">
                                <strong class="<?php echo $prod['mi_stock'] == 0 ? 'text-muted' : 'text-success'; ?>">
                                    <?php echo $prod['mi_stock']; ?>
                                </strong>
                            </td>
                            <td><?php echo $est; ?></td>
                            <td class="text-end pe-3">
                                <button class="btn btn-sm btn-success"
                                        onclick="abrirModalPedirPares(<?php echo $prod['id']; ?>, '<?php echo addslashes(htmlspecialchars($prod['nombre'])); ?>', <?php echo $prod['mi_stock']; ?>)">
                                    <i class="bi bi-box-arrow-in-down me-1"></i>Solicitar Pares
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Paginación catálogo -->
            <?php if ($total_pages_cat > 1): ?>
            <div class="px-3 py-3 border-top d-flex justify-content-between align-items-center">
                <small class="text-muted">Página <?php echo $page_cat; ?> de <?php echo $total_pages_cat; ?> (<?php echo $total_cat; ?> productos)</small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page_cat > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page_cat' => $page_cat - 1])); ?>">&laquo;</a>
                        </li>
                        <?php endif; ?>
                        <?php for ($p = max(1, $page_cat - 2); $p <= min($total_pages_cat, $page_cat + 2); $p++): ?>
                        <li class="page-item <?php echo $p === $page_cat ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page_cat' => $p])); ?>"><?php echo $p; ?></a>
                        </li>
                        <?php endfor; ?>
                        <?php if ($page_cat < $total_pages_cat): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page_cat' => $page_cat + 1])); ?>">&raquo;</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; /* fin tab catalogo */ ?>

    <!-- TAB: Transferencias Entrantes -->
    <?php if ($tab_activa === 'entrantes'): ?>
    <div class="card border-0 shadow-sm border-top-0 rounded-top-0">
        <div class="card-header bg-white py-3">
            <p class="text-muted small mb-0">
                Transferencias que vienen hacia tu sucursal. Cuando recibas físicamente los pares, hacé clic en <strong>Confirmar recepción</strong> para que se sumen al stock.
            </p>
        </div>
        <div class="card-body p-0">
            <?php if (empty($transferencias_entrantes)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                <p>No hay transferencias pendientes hacia tu sucursal</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Producto</th>
                            <th>Desde</th>
                            <th class="text-center">Cantidad</th>
                            <th>Estado</th>
                            <th>Solicitado</th>
                            <th>Motivo</th>
                            <th class="text-end pe-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($transferencias_entrantes as $t): ?>
                    <tr>
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($t['producto_imagen']): ?>
                                <img src="../img/productos/<?php echo htmlspecialchars($t['producto_imagen']); ?>"
                                     alt="" class="rounded"
                                     style="width:40px;height:40px;object-fit:cover;"
                                     onerror="this.src='../img/default-product.jpg'">
                                <?php endif; ?>
                                <strong><?php echo htmlspecialchars($t['producto_nombre']); ?></strong>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($t['origen_nombre']); ?></td>
                        <td class="text-center fw-bold fs-5"><?php echo $t['cantidad']; ?></td>
                        <td>
                            <?php if ($t['estado'] === 'pendiente'): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass me-1"></i>Pendiente de aprobación</span>
                            <?php else: ?>
                                <span class="badge bg-info text-dark"><i class="bi bi-truck me-1"></i>En tránsito</span>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-muted"><?php echo date('d/m/Y', strtotime($t['fecha_solicitud'])); ?></small></td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($t['motivo'] ?? '-'); ?></small></td>
                        <td class="text-end pe-3">
                            <?php if ($t['estado'] === 'en_transito'): ?>
                            <button class="btn btn-sm btn-success"
                                    onclick="confirmarRecepcion(<?php echo $t['id']; ?>, '<?php echo addslashes(htmlspecialchars($t['producto_nombre'])); ?>', <?php echo $t['cantidad']; ?>)">
                                <i class="bi bi-check2-circle me-1"></i>Confirmar recepción
                            </button>
                            <?php else: ?>
                            <span class="text-muted small">Esperando aprobación del admin</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; /* fin tab entrantes */ ?>

</div>

<!-- Modal: Actualizar Stock -->
<div class="modal fade" id="modalActualizarStock" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-boxes me-2"></i>Actualizar Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formActualizarStock">
                <div class="modal-body">
                    <input type="hidden" name="producto_id" id="modal_producto_id">
                    <input type="hidden" name="sucursal_id" id="modal_sucursal_id">

                    <div class="mb-3">
                        <label class="form-label">Producto</label>
                        <input type="text" class="form-control" id="modal_producto_nombre" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Stock actual</label>
                        <input type="text" class="form-control" id="modal_stock_actual" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Operación <span class="text-danger">*</span></label>
                        <select class="form-select" name="operacion" id="modal_operacion" required>
                            <option value="agregar">Agregar unidades (sumar al actual)</option>
                            <option value="reemplazar">Reemplazar (poner cantidad exacta)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="cantidad" id="modal_cantidad"
                               min="0" required>
                        <small class="text-muted" id="modal_ayuda_cantidad">Unidades a agregar al stock actual</small>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Nota (opcional)</label>
                        <textarea class="form-control" name="nota" rows="2"
                                  placeholder="Ej: Recepción de mercadería, inventario, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalStock(productoId, productoNombre, stockActual, sucursalId) {
    document.getElementById('modal_producto_id').value    = productoId;
    document.getElementById('modal_sucursal_id').value    = sucursalId;
    document.getElementById('modal_producto_nombre').value = productoNombre;
    document.getElementById('modal_stock_actual').value   = stockActual + ' unidades';
    document.getElementById('modal_cantidad').value       = '';
    document.getElementById('modal_operacion').value      = 'agregar';
    document.getElementById('modal_ayuda_cantidad').textContent = 'Unidades a agregar al stock actual (' + stockActual + ')';
    new bootstrap.Modal(document.getElementById('modalActualizarStock')).show();
}

document.getElementById('modal_operacion')?.addEventListener('change', function () {
    const stockActual = parseInt(document.getElementById('modal_stock_actual').value) || 0;
    const ayuda = document.getElementById('modal_ayuda_cantidad');
    if (this.value === 'agregar') {
        ayuda.textContent = 'Unidades a agregar al stock actual (' + stockActual + ')';
    } else {
        ayuda.textContent = 'Nueva cantidad exacta que tendrá el producto en la sucursal';
    }
});

document.getElementById('formActualizarStock')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = this.querySelector('[type=submit]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

    fetch('ajax/actualizar-stock.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalActualizarStock')).hide();
            location.reload();
        } else {
            MC.alert(data.message || 'Error al actualizar el stock', 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Guardar';
        }
    })
    .catch(() => {
        MC.alert('Error de conexión', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Guardar';
    });
});

function confirmarRecepcion(transferenciaId, productoNombre, cantidad) {
    const btn = event.target.closest('button');
    MC.confirm(`¿Confirmar recepción de ${cantidad} unidades de "${productoNombre}"? Se sumarán al stock de tu sucursal.`, function(ok) {
        if (!ok) return;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Confirmando...';

        const fd = new FormData();
        fd.append('transferencia_id', transferenciaId);

        fetch('ajax/confirmar-recepcion.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    MC.alert(data.mensaje, 'success');
                    location.reload();
                } else {
                    MC.alert(data.mensaje || 'Error al confirmar', 'danger');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Confirmar recepción';
                }
            })
            .catch(() => {
                MC.alert('Error de conexión', 'danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Confirmar recepción';
            });
    }, { tipo: 'warning', titulo: 'Confirmar recepción', btnOk: 'Sí, confirmar' });
}
</script>

<!-- Modal: Solicitar Pares (desde catálogo) -->
<div class="modal fade" id="modalPedirPares" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-box-arrow-in-down me-2"></i>Solicitar Pares</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    Pedís pares de otra sucursal hacia la tuya. El administrador debe aprobar la solicitud y coordinar el envío.
                </div>
                <input type="hidden" id="pedir_producto_id">

                <div class="mb-3">
                    <label class="form-label">Producto</label>
                    <input type="text" class="form-control" id="pedir_producto_nombre" readonly>
                </div>
                <div class="mb-1">
                    <label class="form-label">Mi stock actual</label>
                    <input type="text" class="form-control" id="pedir_mi_stock" readonly>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Sucursal de origen <span class="text-danger">*</span></label>
                    <select class="form-select" id="pedir_sucursal_origen" required>
                        <option value="">¿Desde qué sucursal?</option>
                        <?php foreach ($otras_sucursales as $suc): ?>
                        <option value="<?php echo $suc['id']; ?>"><?php echo htmlspecialchars($suc['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Cantidad a solicitar <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="pedir_cantidad" min="1" required placeholder="Ej: 10">
                </div>
                <div class="mb-0">
                    <label class="form-label">Motivo (opcional)</label>
                    <textarea class="form-control" id="pedir_motivo" rows="2"
                              placeholder="Ej: Stock agotado, demanda alta"></textarea>
                </div>
                <div id="pedir_alerta" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn_enviar_pedido" onclick="enviarPedidoPares()">
                    <i class="bi bi-send me-2"></i>Enviar Solicitud
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function abrirModalPedirPares(productoId, productoNombre, miStock) {
    document.getElementById('pedir_producto_id').value       = productoId;
    document.getElementById('pedir_producto_nombre').value   = productoNombre;
    document.getElementById('pedir_mi_stock').value          = miStock + ' unidades';
    document.getElementById('pedir_sucursal_origen').value   = '';
    document.getElementById('pedir_cantidad').value          = '';
    document.getElementById('pedir_motivo').value            = '';
    document.getElementById('pedir_alerta').innerHTML        = '';
    new bootstrap.Modal(document.getElementById('modalPedirPares')).show();
}

function enviarPedidoPares() {
    const productoId   = parseInt(document.getElementById('pedir_producto_id').value);
    const origenId     = parseInt(document.getElementById('pedir_sucursal_origen').value);
    const cantidad     = parseInt(document.getElementById('pedir_cantidad').value);
    const motivo       = document.getElementById('pedir_motivo').value.trim();
    const alerta       = document.getElementById('pedir_alerta');

    if (!origenId || !cantidad || cantidad < 1) {
        alerta.innerHTML = '<div class="alert alert-danger py-2">Completá sucursal de origen y cantidad.</div>';
        return;
    }

    const btn = document.getElementById('btn_enviar_pedido');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enviando...';

    fetch('ajax/solicitar-pedido-stock.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ producto_id: productoId, sucursal_origen_id: origenId, cantidad, motivo })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alerta.innerHTML = `<div class="alert alert-success py-2"><i class="bi bi-check-circle me-1"></i>${data.mensaje}</div>`;
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('modalPedirPares')).hide();
                // Redirigir a pestaña entrantes
                window.location.href = 'stock.php?tab=entrantes';
            }, 1800);
        } else {
            alerta.innerHTML = `<div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle me-1"></i>${data.mensaje}</div>`;
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send me-2"></i>Enviar Solicitud';
        }
    })
    .catch(() => {
        alerta.innerHTML = '<div class="alert alert-danger py-2">Error de conexión</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send me-2"></i>Enviar Solicitud';
    });
}
</script>

<?php include('includes/footer-gerente.php'); ?>
