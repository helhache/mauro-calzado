<?php
require_once '../includes/config.php';
require_once '../includes/verificar-gerente.php';

$titulo_pagina = 'Gestión de Ventas';
$sucursal_id   = obtenerSucursalGerente();

// ============================================================
// FILTROS
// ============================================================
$fecha_desde = isset($_GET['fecha_desde']) && $_GET['fecha_desde'] !== '' ? limpiarDato($_GET['fecha_desde']) : date('Y-m-01');
$fecha_hasta = isset($_GET['fecha_hasta']) && $_GET['fecha_hasta'] !== '' ? limpiarDato($_GET['fecha_hasta']) : date('Y-m-d');
$metodo_pago = isset($_GET['metodo_pago']) ? limpiarDato($_GET['metodo_pago']) : '';

// ============================================================
// KPI CARDS
// ============================================================
// Ventas hoy
$stmt = mysqli_prepare($conn,
    "SELECT COALESCE(SUM(subtotal),0) as total, COUNT(*) as qty
     FROM ventas_diarias
     WHERE sucursal_id = ? AND DATE(fecha_venta) = CURDATE()");
mysqli_stmt_bind_param($stmt, "i", $sucursal_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);
$ventas_hoy       = $row['total'];
$transac_hoy      = $row['qty'];

// Ventas mes actual
$stmt = mysqli_prepare($conn,
    "SELECT COALESCE(SUM(subtotal),0) as total, COUNT(*) as qty
     FROM ventas_diarias
     WHERE sucursal_id = ?
       AND YEAR(fecha_venta) = YEAR(CURDATE())
       AND MONTH(fecha_venta) = MONTH(CURDATE())");
mysqli_stmt_bind_param($stmt, "i", $sucursal_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);
$ventas_mes       = $row['total'];
$transac_mes      = $row['qty'];
$ticket_promedio  = $transac_mes > 0 ? $ventas_mes / $transac_mes : 0;

// Pares vendidos este mes
$stmt = mysqli_prepare($conn,
    "SELECT COALESCE(SUM(cantidad),0) as pares
     FROM ventas_diarias
     WHERE sucursal_id = ?
       AND YEAR(fecha_venta) = YEAR(CURDATE())
       AND MONTH(fecha_venta) = MONTH(CURDATE())");
mysqli_stmt_bind_param($stmt, "i", $sucursal_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$pares_mes = mysqli_fetch_assoc($res)['pares'];
mysqli_free_result($res);
mysqli_stmt_close($stmt);

// ============================================================
// TOP PRODUCTOS MÁS VENDIDOS (rango filtrado)
// ============================================================
$stmt = mysqli_prepare($conn,
    "SELECT producto_nombre, SUM(cantidad) as pares, SUM(subtotal) as total_vendido
     FROM ventas_diarias
     WHERE sucursal_id = ? AND DATE(fecha_venta) BETWEEN ? AND ?
     GROUP BY producto_nombre
     ORDER BY pares DESC
     LIMIT 8");
mysqli_stmt_bind_param($stmt, "iss", $sucursal_id, $fecha_desde, $fecha_hasta);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$top_productos_labels = [];
$top_productos_data   = [];
while ($r = mysqli_fetch_assoc($res)) {
    $top_productos_labels[] = $r['producto_nombre'];
    $top_productos_data[]   = (int)$r['pares'];
}
mysqli_free_result($res);
mysqli_stmt_close($stmt);

// ============================================================
// TOP CLIENTES QUE MÁS COMPRAN (pedidos online, rango filtrado)
// ============================================================
$stmt = mysqli_prepare($conn,
    "SELECT CONCAT(u.nombre, ' ', u.apellido) as cliente,
            COUNT(p.id) as pedidos_qty,
            SUM(p.total) as total_comprado
     FROM pedidos p
     INNER JOIN usuarios u ON p.usuario_id = u.id
     WHERE p.sucursal_id = ?
       AND p.estado != 'cancelado'
       AND DATE(p.fecha_pedido) BETWEEN ? AND ?
     GROUP BY p.usuario_id
     ORDER BY total_comprado DESC
     LIMIT 8");
mysqli_stmt_bind_param($stmt, "iss", $sucursal_id, $fecha_desde, $fecha_hasta);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$top_clientes_labels = [];
$top_clientes_data   = [];
while ($r = mysqli_fetch_assoc($res)) {
    $top_clientes_labels[] = $r['cliente'];
    $top_clientes_data[]   = (float)$r['total_comprado'];
}
mysqli_free_result($res);
mysqli_stmt_close($stmt);

// ============================================================
// GRÁFICO: ventas diarias en el rango filtrado
// ============================================================
$stmt = mysqli_prepare($conn,
    "SELECT DATE(fecha_venta) as dia, COALESCE(SUM(subtotal),0) as total
     FROM ventas_diarias
     WHERE sucursal_id = ? AND DATE(fecha_venta) BETWEEN ? AND ?
     GROUP BY DATE(fecha_venta)
     ORDER BY dia ASC");
mysqli_stmt_bind_param($stmt, "iss", $sucursal_id, $fecha_desde, $fecha_hasta);
mysqli_stmt_execute($stmt);
$res_grafico = mysqli_stmt_get_result($stmt);
$grafico_labels = [];
$grafico_data   = [];
while ($g = mysqli_fetch_assoc($res_grafico)) {
    $grafico_labels[] = date('d/m', strtotime($g['dia']));
    $grafico_data[]   = (float)$g['total'];
}
mysqli_free_result($res_grafico);
mysqli_stmt_close($stmt);

// ============================================================
// VENTAS POR MÉTODO DE PAGO (rango filtrado)
// ============================================================
$stmt = mysqli_prepare($conn,
    "SELECT metodo_pago, COALESCE(SUM(subtotal),0) as total, COUNT(*) as qty
     FROM ventas_diarias
     WHERE sucursal_id = ? AND DATE(fecha_venta) BETWEEN ? AND ?
     GROUP BY metodo_pago");
mysqli_stmt_bind_param($stmt, "iss", $sucursal_id, $fecha_desde, $fecha_hasta);
mysqli_stmt_execute($stmt);
$res_metodos = mysqli_stmt_get_result($stmt);
$ventas_por_metodo = [];
while ($m = mysqli_fetch_assoc($res_metodos)) {
    $ventas_por_metodo[$m['metodo_pago']] = $m;
}
mysqli_free_result($res_metodos);
mysqli_stmt_close($stmt);

// ============================================================
// TABLA: ventas filtradas (paginación simple)
// ============================================================
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where_metodo = '';
$params_types = "iss";
$params = [$sucursal_id, $fecha_desde, $fecha_hasta];

if ($metodo_pago !== '') {
    $where_metodo = " AND vd.metodo_pago = ?";
    $params_types .= "s";
    $params[] = $metodo_pago;
}

$sql_count = "SELECT COUNT(*) as total
              FROM ventas_diarias vd
              WHERE vd.sucursal_id = ? AND DATE(vd.fecha_venta) BETWEEN ? AND ?" . $where_metodo;
$stmt = mysqli_prepare($conn, $sql_count);
mysqli_stmt_bind_param($stmt, $params_types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$total_rows = mysqli_fetch_assoc($res)['total'];
mysqli_free_result($res);
mysqli_stmt_close($stmt);
$total_pages = max(1, ceil($total_rows / $limit));

$sql_ventas = "SELECT vd.*, tc.turno
               FROM ventas_diarias vd
               LEFT JOIN turnos_caja tc ON vd.turno_id = tc.id
               WHERE vd.sucursal_id = ? AND DATE(vd.fecha_venta) BETWEEN ? AND ?" . $where_metodo . "
               ORDER BY vd.fecha_venta DESC
               LIMIT ? OFFSET ?";
$params_types_pag = $params_types . "ii";
$params_pag = array_merge($params, [$limit, $offset]);
$stmt = mysqli_prepare($conn, $sql_ventas);
mysqli_stmt_bind_param($stmt, $params_types_pag, ...$params_pag);
mysqli_stmt_execute($stmt);
$res_ventas = mysqli_stmt_get_result($stmt);
$ventas = [];
while ($v = mysqli_fetch_assoc($res_ventas)) {
    $ventas[] = $v;
}
mysqli_free_result($res_ventas);
mysqli_stmt_close($stmt);

// Total del rango filtrado
$stmt = mysqli_prepare($conn,
    "SELECT COALESCE(SUM(subtotal),0) as total, COUNT(*) as qty, COALESCE(SUM(cantidad),0) as pares
     FROM ventas_diarias
     WHERE sucursal_id = ? AND DATE(fecha_venta) BETWEEN ? AND ?" . $where_metodo);
mysqli_stmt_bind_param($stmt, $params_types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row_rango = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);
$total_rango       = $row_rango['total'];
$transac_rango     = $row_rango['qty'];
$pares_rango       = $row_rango['pares'];
$ticket_rango      = $transac_rango > 0 ? $total_rango / $transac_rango : 0;

include('includes/header-gerente.php');

$metodos_labels = [
    'efectivo'     => 'Efectivo',
    'tarjeta'      => 'Tarjeta',
    'transferencia'=> 'Transferencia',
    'go_cuotas'    => 'Go Cuotas',
    'credito'      => 'Crédito',
];
$metodos_colors = [
    'efectivo'     => 'success',
    'tarjeta'      => 'primary',
    'transferencia'=> 'info',
    'go_cuotas'    => 'warning',
    'credito'      => 'danger',
];
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light p-3 mb-4">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Ventas</li>
    </ol>
</nav>

<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h2 class="mb-0"><i class="bi bi-graph-up me-2"></i>Gestión de Ventas</h2>
            <p class="text-muted mb-0">Reportes y análisis de ventas de tu sucursal</p>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Ventas Hoy</p>
                            <h4 class="mb-0 fw-bold">$<?php echo number_format($ventas_hoy, 2, ',', '.'); ?></h4>
                            <small class="text-muted"><?php echo $transac_hoy; ?> transacciones</small>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-cash-coin text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Ventas Este Mes</p>
                            <h4 class="mb-0 fw-bold">$<?php echo number_format($ventas_mes, 2, ',', '.'); ?></h4>
                            <small class="text-muted"><?php echo $transac_mes; ?> transacciones</small>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-calendar-month text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Pares Vendidos (mes)</p>
                            <h4 class="mb-0 fw-bold"><?php echo $pares_mes; ?></h4>
                            <small class="text-muted">pares este mes</small>
                        </div>
                        <div class="bg-info bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-shoe text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Ticket Promedio (mes)</p>
                            <h4 class="mb-0 fw-bold">$<?php echo number_format($ticket_promedio, 2, ',', '.'); ?></h4>
                            <small class="text-muted">por transacción</small>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-calculator text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico + Métodos de pago -->
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Ventas Diarias del Período</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($grafico_labels)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-1"></i>
                            <p class="mt-2">Sin ventas en el período seleccionado</p>
                        </div>
                    <?php else: ?>
                        <canvas id="graficoVentas" style="max-height: 280px;"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Por Método de Pago (período)</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($metodos_labels as $key => $label): ?>
                        <?php $m = $ventas_por_metodo[$key] ?? ['total' => 0, 'qty' => 0]; ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-<?php echo $metodos_colors[$key]; ?> me-2"><?php echo $label; ?></span>
                                <small class="text-muted"><?php echo $m['qty']; ?> transacc.</small>
                            </div>
                            <strong>$<?php echo number_format($m['total'], 2, ',', '.'); ?></strong>
                        </li>
                        <?php endforeach; ?>
                        <li class="list-group-item bg-light d-flex justify-content-between align-items-center">
                            <strong>TOTAL</strong>
                            <strong class="text-success">$<?php echo number_format($total_rango, 2, ',', '.'); ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos: Top Productos + Top Clientes -->
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-trophy me-2 text-warning"></i>Top Productos Más Vendidos</h6>
                    <small class="text-muted">pares vendidos en el período</small>
                </div>
                <div class="card-body">
                    <?php if (empty($top_productos_labels)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>Sin datos en el período
                        </div>
                    <?php else: ?>
                        <canvas id="graficoTopProductos" style="max-height: 300px;"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-people me-2 text-info"></i>Top Clientes por Compras</h6>
                    <small class="text-muted">pedidos online en el período</small>
                </div>
                <div class="card-body">
                    <?php if (empty($top_clientes_labels)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>Sin pedidos online en el período
                        </div>
                    <?php else: ?>
                        <canvas id="graficoTopClientes" style="max-height: 300px;"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros + Tabla -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-3"><i class="bi bi-table me-2"></i>Detalle de Ventas</h5>
            <!-- Filtros -->
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Desde</label>
                    <input type="date" class="form-control form-control-sm" name="fecha_desde"
                           value="<?php echo htmlspecialchars($fecha_desde); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Hasta</label>
                    <input type="date" class="form-control form-control-sm" name="fecha_hasta"
                           value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Método de pago</label>
                    <select class="form-select form-select-sm" name="metodo_pago">
                        <option value="">Todos</option>
                        <?php foreach ($metodos_labels as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $metodo_pago === $key ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="ventas.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle me-1"></i>Limpiar
                    </a>
                    <a href="ventas.php?<?php echo http_build_query(array_filter(['fecha_desde' => $fecha_desde, 'fecha_hasta' => $fecha_hasta, 'metodo_pago' => $metodo_pago])); ?>&export=csv"
                       class="btn btn-outline-success btn-sm">
                        <i class="bi bi-file-earmark-excel me-1"></i>CSV
                    </a>
                </div>
            </form>
        </div>

        <?php if (isset($_GET['export']) && $_GET['export'] === 'csv'): ?>
        <?php
        // Exportar CSV
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="ventas_' . $fecha_desde . '_' . $fecha_hasta . '.csv"');
        echo "\xEF\xBB\xBF"; // BOM UTF-8
        echo "ID,Fecha,Producto,Talle,Color,Cantidad,Precio Unit.,Subtotal,Método Pago,Tipo Venta,Turno,Notas\n";
        foreach ($ventas as $v) {
            printf('%d,"%s","%s","%s","%s",%d,%.2f,%.2f,"%s","%s","%s","%s"' . "\n",
                $v['id'],
                date('d/m/Y H:i', strtotime($v['fecha_venta'])),
                addslashes($v['producto_nombre']),
                $v['talle'] ?? '',
                $v['color'] ?? '',
                $v['cantidad'],
                $v['precio_unitario'],
                $v['subtotal'],
                $metodos_labels[$v['metodo_pago']] ?? $v['metodo_pago'],
                $v['tipo_venta'],
                ucfirst($v['turno'] ?? ''),
                addslashes($v['notas'] ?? '')
            );
        }
        exit;
        ?>
        <?php endif; ?>

        <div class="card-body p-0">
            <!-- Resumen del rango -->
            <div class="px-3 py-2 bg-light border-bottom">
                <small class="text-muted">
                    <strong><?php echo $transac_rango; ?></strong> transacciones &middot;
                    <strong><?php echo $pares_rango; ?></strong> pares &middot;
                    Total: <strong class="text-success">$<?php echo number_format($total_rango, 2, ',', '.'); ?></strong> &middot;
                    Ticket promedio: <strong>$<?php echo number_format($ticket_rango, 2, ',', '.'); ?></strong>
                </small>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Fecha / Hora</th>
                            <th>Producto</th>
                            <th>Talle</th>
                            <th>Color</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-end">P. Unit.</th>
                            <th class="text-end">Subtotal</th>
                            <th>Método</th>
                            <th>Turno</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ventas)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No hay ventas en el período seleccionado
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($ventas as $v): ?>
                        <tr>
                            <td class="ps-3 text-muted"><?php echo $v['id']; ?></td>
                            <td>
                                <span class="fw-semibold"><?php echo date('d/m/Y', strtotime($v['fecha_venta'])); ?></span>
                                <br><small class="text-muted"><?php echo date('H:i', strtotime($v['fecha_venta'])); ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($v['producto_nombre']); ?>
                                <?php if ($v['tipo_venta'] === 'online'): ?>
                                    <span class="badge bg-info ms-1">Online</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($v['talle'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($v['color'] ?? '-'); ?></td>
                            <td class="text-center"><?php echo $v['cantidad']; ?></td>
                            <td class="text-end">$<?php echo number_format($v['precio_unitario'], 2, ',', '.'); ?></td>
                            <td class="text-end fw-bold">$<?php echo number_format($v['subtotal'], 2, ',', '.'); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $metodos_colors[$v['metodo_pago']] ?? 'secondary'; ?>">
                                    <?php echo $metodos_labels[$v['metodo_pago']] ?? $v['metodo_pago']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?php echo ucfirst($v['turno'] ?? '-'); ?>
                                </span>
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
                    Página <?php echo $page; ?> de <?php echo $total_pages; ?> (<?php echo $total_rows; ?> registros)
                </small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                &laquo;
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++): ?>
                        <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $p])); ?>">
                                <?php echo $p; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                &raquo;
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($grafico_labels)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('graficoVentas').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($grafico_labels); ?>,
            datasets: [{
                label: 'Ventas ($)',
                data: <?php echo json_encode($grafico_data); ?>,
                backgroundColor: 'rgba(25, 135, 84, 0.7)',
                borderColor: 'rgba(25, 135, 84, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(c) {
                            return ' $' + c.parsed.y.toLocaleString('es-AR', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(v) {
                            return '$' + v.toLocaleString('es-AR');
                        }
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php if (!empty($top_productos_labels)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const colores = [
        'rgba(25,135,84,0.75)', 'rgba(13,110,253,0.75)', 'rgba(255,193,7,0.75)',
        'rgba(220,53,69,0.75)', 'rgba(13,202,240,0.75)', 'rgba(108,117,125,0.75)',
        'rgba(111,66,193,0.75)', 'rgba(253,126,20,0.75)'
    ];
    new Chart(document.getElementById('graficoTopProductos').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($top_productos_labels); ?>,
            datasets: [{
                label: 'Pares vendidos',
                data: <?php echo json_encode($top_productos_data); ?>,
                backgroundColor: colores,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
});
</script>
<?php endif; ?>

<?php if (!empty($top_clientes_labels)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const colores = [
        'rgba(13,202,240,0.75)', 'rgba(25,135,84,0.75)', 'rgba(13,110,253,0.75)',
        'rgba(255,193,7,0.75)', 'rgba(220,53,69,0.75)', 'rgba(108,117,125,0.75)',
        'rgba(111,66,193,0.75)', 'rgba(253,126,20,0.75)'
    ];
    new Chart(document.getElementById('graficoTopClientes').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($top_clientes_labels); ?>,
            datasets: [{
                label: 'Total comprado ($)',
                data: <?php echo json_encode($top_clientes_data); ?>,
                backgroundColor: colores,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: c => ' $' + c.parsed.x.toLocaleString('es-AR', { minimumFractionDigits: 2 })
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { callback: v => '$' + v.toLocaleString('es-AR') }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php include('includes/footer-gerente.php'); ?>
