<?php
require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

$titulo_pagina = 'Cajas y Turnos';

// ─── Filtros ─────────────────────────────────────────────────────────────────
$filtro_sucursal = (int)($_GET['sucursal_id'] ?? 0);
$filtro_estado   = in_array($_GET['estado'] ?? '', ['abierto','cerrado']) ? $_GET['estado'] : '';
$filtro_turno    = in_array($_GET['turno']   ?? '', ['manana','tarde'])   ? $_GET['turno']  : '';
$filtro_desde    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['desde'] ?? '') ? $_GET['desde'] : date('Y-m-01');
$filtro_hasta    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['hasta'] ?? '') ? $_GET['hasta'] : date('Y-m-d');

// ─── Sucursales para el filtro ────────────────────────────────────────────────
$sucursales = mysqli_fetch_all(mysqli_query($conn, "SELECT id, nombre FROM sucursales ORDER BY nombre"), MYSQLI_ASSOC);

// ─── Build where ──────────────────────────────────────────────────────────────
$where  = ["DATE(tc.fecha_apertura) BETWEEN ? AND ?"];
$params = [$filtro_desde, $filtro_hasta];
$types  = "ss";

if ($filtro_sucursal > 0) { $where[] = "tc.sucursal_id = ?"; $params[] = $filtro_sucursal; $types .= "i"; }
if ($filtro_estado !== '') { $where[] = "tc.estado = ?";     $params[] = $filtro_estado;   $types .= "s"; }
if ($filtro_turno  !== '') { $where[] = "tc.turno = ?";      $params[] = $filtro_turno;    $types .= "s"; }

$where_sql = implode(' AND ', $where);

// ─── Listado de turnos ────────────────────────────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT tc.*,
            s.nombre AS sucursal_nombre,
            CONCAT(u.nombre, ' ', u.apellido) AS gerente_nombre,
            COUNT(vd.id) AS qty_ventas
     FROM turnos_caja tc
     LEFT JOIN sucursales s  ON tc.sucursal_id  = s.id
     LEFT JOIN usuarios   u  ON tc.gerente_id   = u.id
     LEFT JOIN ventas_diarias vd ON vd.turno_id = tc.id
     WHERE $where_sql
     GROUP BY tc.id
     ORDER BY tc.fecha_apertura DESC");
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$turnos = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

// ─── KPI cards (solo turnos cerrados del período) ─────────────────────────────
$stmt_kpi = mysqli_prepare($conn,
    "SELECT COUNT(*) as total_turnos,
            COALESCE(SUM(venta_total_dia),0)  as total_ventas,
            COALESCE(SUM(pares_vendidos),0)   as total_pares,
            COALESCE(AVG(venta_total_dia),0)  as promedio_turno,
            COALESCE(SUM(gastos_dia),0)       as total_gastos,
            SUM(CASE WHEN diferencia_caja < -0.01 THEN 1 ELSE 0 END) as turnos_faltante
     FROM turnos_caja tc
     WHERE DATE(fecha_apertura) BETWEEN ? AND ? AND estado = 'cerrado'");
mysqli_stmt_bind_param($stmt_kpi, 'ss', $filtro_desde, $filtro_hasta);
mysqli_stmt_execute($stmt_kpi);
$kpi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_kpi));

// ─── Ventas por sucursal para gráfico ─────────────────────────────────────────
$stmt_graf = mysqli_prepare($conn,
    "SELECT s.nombre,
            COALESCE(SUM(tc.venta_total_dia),0) as total
     FROM sucursales s
     LEFT JOIN turnos_caja tc ON tc.sucursal_id = s.id
                              AND DATE(tc.fecha_apertura) BETWEEN ? AND ?
                              AND tc.estado = 'cerrado'
     GROUP BY s.id, s.nombre
     ORDER BY total DESC");
mysqli_stmt_bind_param($stmt_graf, 'ss', $filtro_desde, $filtro_hasta);
mysqli_stmt_execute($stmt_graf);
$graf_rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt_graf), MYSQLI_ASSOC);
$graf_labels = array_column($graf_rows, 'nombre');
$graf_datos  = array_map('floatval', array_column($graf_rows, 'total'));

include('includes/header-admin.php');
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Cajas y Turnos</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 fw-bold mb-1"><i class="bi bi-cash-register text-primary me-2"></i>Cajas y Turnos</h2>
        <p class="text-muted mb-0">Supervisión de todos los turnos de todas las sucursales</p>
    </div>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Desde</label>
                <input type="date" class="form-control form-control-sm" name="desde" value="<?php echo $filtro_desde; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Hasta</label>
                <input type="date" class="form-control form-control-sm" name="hasta" value="<?php echo $filtro_hasta; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Sucursal</label>
                <select class="form-select form-select-sm" name="sucursal_id">
                    <option value="">Todas</option>
                    <?php foreach ($sucursales as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo $filtro_sucursal == $s['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Turno</label>
                <select class="form-select form-select-sm" name="turno">
                    <option value="">Todos</option>
                    <option value="manana" <?php echo $filtro_turno === 'manana' ? 'selected' : ''; ?>>Mañana</option>
                    <option value="tarde"  <?php echo $filtro_turno === 'tarde'  ? 'selected' : ''; ?>>Tarde</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Estado</label>
                <select class="form-select form-select-sm" name="estado">
                    <option value="">Todos</option>
                    <option value="cerrado" <?php echo $filtro_estado === 'cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                    <option value="abierto" <?php echo $filtro_estado === 'abierto' ? 'selected' : ''; ?>>Abierto</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
                <a href="cajas.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-primary bg-opacity-10 rounded">
                        <i class="bi bi-calendar-check text-primary fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-0">Turnos cerrados</p>
                        <h3 class="fw-bold mb-0"><?php echo number_format($kpi['total_turnos']); ?></h3>
                        <small class="text-muted">en el período</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-success bg-opacity-10 rounded">
                        <i class="bi bi-cash-stack text-success fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-0">Total ventas</p>
                        <h3 class="fw-bold mb-0">$<?php echo number_format($kpi['total_ventas'], 0, ',', '.'); ?></h3>
                        <small class="text-muted"><?php echo number_format($kpi['total_pares']); ?> pares vendidos</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-info bg-opacity-10 rounded">
                        <i class="bi bi-graph-up text-info fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-0">Promedio por turno</p>
                        <h3 class="fw-bold mb-0">$<?php echo number_format($kpi['promedio_turno'], 0, ',', '.'); ?></h3>
                        <small class="text-muted">gastos: $<?php echo number_format($kpi['total_gastos'], 0, ',', '.'); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 <?php echo $kpi['turnos_faltante'] > 0 ? 'bg-danger' : 'bg-success'; ?> bg-opacity-10 rounded">
                        <i class="bi bi-exclamation-triangle <?php echo $kpi['turnos_faltante'] > 0 ? 'text-danger' : 'text-success'; ?> fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-0">Turnos con faltante</p>
                        <h3 class="fw-bold mb-0 <?php echo $kpi['turnos_faltante'] > 0 ? 'text-danger' : 'text-success'; ?>"><?php echo $kpi['turnos_faltante']; ?></h3>
                        <small class="text-muted">diferencias en caja</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gráfico ventas por sucursal -->
<?php if (!empty($graf_rows)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart me-2 text-primary"></i>Ventas por Sucursal — período seleccionado</h6>
        <canvas id="graficoCajas" height="60"></canvas>
    </div>
</div>
<?php endif; ?>

<!-- Tabla de turnos -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-3">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-list-ul me-2"></i>Turnos
            <span class="badge bg-secondary ms-2"><?php echo count($turnos); ?></span>
        </h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($turnos)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                <p>No hay turnos en el período seleccionado</p>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Sucursal</th>
                        <th>Turno</th>
                        <th>Gerente</th>
                        <th>Apertura</th>
                        <th>Cierre</th>
                        <th class="text-end">Ventas</th>
                        <th class="text-end">Pares</th>
                        <th class="text-center">Diferencia</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($turnos as $t): ?>
                    <tr>
                        <td class="fw-semibold"><?php echo htmlspecialchars($t['sucursal_nombre'] ?? '—'); ?></td>
                        <td>
                            <span class="badge <?php echo $t['turno'] === 'manana' ? 'bg-warning text-dark' : 'bg-info'; ?>">
                                <i class="bi <?php echo $t['turno'] === 'manana' ? 'bi-sun' : 'bi-moon'; ?> me-1"></i>
                                <?php echo $t['turno'] === 'manana' ? 'Mañana' : 'Tarde'; ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?php echo htmlspecialchars($t['gerente_nombre'] ?? '—'); ?></td>
                        <td class="small"><?php echo date('d/m/Y H:i', strtotime($t['fecha_apertura'])); ?></td>
                        <td class="small text-muted">
                            <?php echo $t['fecha_cierre'] ? date('d/m/Y H:i', strtotime($t['fecha_cierre'])) : '—'; ?>
                        </td>
                        <td class="text-end fw-semibold">$<?php echo number_format($t['venta_total_dia'], 2, ',', '.'); ?></td>
                        <td class="text-end"><?php echo $t['pares_vendidos']; ?></td>
                        <td class="text-center">
                            <?php if ($t['estado'] === 'abierto'): ?>
                                <span class="text-muted small">—</span>
                            <?php else:
                                $dif = floatval($t['diferencia_caja']);
                                if (abs($dif) < 0.01): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Cuadrada</span>
                                <?php elseif ($dif > 0): ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-arrow-up me-1"></i>+$<?php echo number_format($dif, 2, ',', '.'); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-arrow-down me-1"></i>-$<?php echo number_format(abs($dif), 2, ',', '.'); ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge <?php echo $t['estado'] === 'abierto' ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo $t['estado'] === 'abierto' ? 'Abierto' : 'Cerrado'; ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="verDetalle(<?php echo $t['id']; ?>)" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if ($t['estado'] === 'cerrado'): ?>
                                <a href="cierre-caja-pdf.php?id=<?php echo $t['id']; ?>" target="_blank" class="btn btn-outline-danger" title="Exportar PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal detalle turno -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-cash-register me-2"></i><span id="modal-titulo">Detalle del Turno</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modal-body">
                <div class="text-center py-5">
                    <span class="spinner-border text-primary"></span>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="btn-pdf-modal" target="_blank" class="btn btn-danger d-none">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Exportar PDF
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Gráfico ventas por sucursal
const labels = <?php echo json_encode($graf_labels); ?>;
const datos  = <?php echo json_encode($graf_datos);  ?>;
const colores = ['#3C50E0','#10B981','#F59E0B','#EF4444','#8B5CF6','#14B8A6','#EC4899'];

if (document.getElementById('graficoCajas') && labels.length > 0) {
    new Chart(document.getElementById('graficoCajas').getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Ventas ($)',
                data: datos,
                backgroundColor: labels.map((_, i) => colores[i % colores.length]),
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => '$' + ctx.parsed.y.toLocaleString('es-AR', {minimumFractionDigits: 2})
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => '$' + v.toLocaleString('es-AR') }
                },
                x: { grid: { display: false } }
            }
        }
    });
}

// Ver detalle turno
async function verDetalle(turnoId) {
    document.getElementById('modal-titulo').textContent = 'Detalle del Turno';
    document.getElementById('modal-body').innerHTML = '<div class="text-center py-5"><span class="spinner-border text-primary"></span></div>';
    document.getElementById('btn-pdf-modal').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('modalDetalle')).show();

    try {
        const resp = await fetch(`ajax/obtener-detalle-turno.php?id=${turnoId}`);
        const data = await resp.json();
        if (!data.success) { document.getElementById('modal-body').innerHTML = '<div class="alert alert-danger">Error al cargar el turno.</div>'; return; }

        const t = data.turno;
        const v = data.ventas;
        const g = data.gastos;
        const c = data.cobros;

        // Título
        document.getElementById('modal-titulo').textContent =
            `${t.sucursal_nombre} — ${t.turno === 'manana' ? 'Mañana' : 'Tarde'} — ${formatFecha(t.fecha_apertura)}`;

        // Diferencia badge
        const dif = parseFloat(t.diferencia_caja || 0);
        let difBadge = '';
        if (t.estado === 'cerrado') {
            if (Math.abs(dif) < 0.01) difBadge = '<span class="badge bg-success fs-6"><i class="bi bi-check-circle me-1"></i>Caja cuadrada</span>';
            else if (dif > 0)         difBadge = `<span class="badge bg-warning text-dark fs-6"><i class="bi bi-arrow-up me-1"></i>Sobrante $${fmt(dif)}</span>`;
            else                      difBadge = `<span class="badge bg-danger fs-6"><i class="bi bi-arrow-down me-1"></i>Faltante $${fmt(Math.abs(dif))}</span>`;
        }

        // Ventas por método
        let tablaVentas = '';
        if (v.length > 0) {
            tablaVentas = v.map(vd => `
                <tr>
                    <td>${formatHora(vd.fecha_venta)}</td>
                    <td>${esc(vd.producto_nombre)}${vd.talle ? ' T:'+vd.talle : ''}${vd.color ? ' C:'+vd.color : ''}</td>
                    <td class="text-center">${vd.cantidad}</td>
                    <td class="text-center"><span class="badge bg-secondary">${esc(vd.metodo_pago)}</span></td>
                    <td class="text-end fw-semibold">$${fmt(vd.subtotal)}</td>
                </tr>`).join('');
        } else {
            tablaVentas = '<tr><td colspan="5" class="text-center text-muted py-3">Sin ventas registradas</td></tr>';
        }

        let tablaGastos = '';
        if (g.length > 0) {
            tablaGastos = g.map(gd => `
                <tr>
                    <td>${esc(gd.concepto)}</td>
                    <td><span class="badge bg-secondary">${esc(gd.tipo)}</span></td>
                    <td class="text-end text-danger fw-semibold">-$${fmt(gd.monto)}</td>
                </tr>`).join('');
        } else {
            tablaGastos = '<tr><td colspan="3" class="text-center text-muted py-3">Sin gastos</td></tr>';
        }

        let tablaCobros = '';
        if (c.length > 0) {
            tablaCobros = c.map(cc => `
                <tr>
                    <td>${esc(cc.cliente_nombre)}${cc.cliente_dni ? ' <small class="text-muted">('+cc.cliente_dni+')</small>' : ''}</td>
                    <td class="text-center">${cc.numero_cuota ? 'Cuota #'+cc.numero_cuota : '—'}</td>
                    <td class="text-end text-info fw-semibold">$${fmt(cc.monto_cobrado)}</td>
                </tr>`).join('');
        } else {
            tablaCobros = '<tr><td colspan="3" class="text-center text-muted py-3">Sin cobros de cuotas</td></tr>';
        }

        const efEsperado = parseFloat(t.monto_inicial) + parseFloat(t.efectivo_ventas) + parseFloat(t.credito_ventas) + parseFloat(t.cobro_cuotas_credito) - parseFloat(t.gastos_dia);

        document.getElementById('modal-body').innerHTML = `
            <!-- Info general -->
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <div class="card border">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <small class="text-muted d-block">Sucursal</small>
                                    <strong>${esc(t.sucursal_nombre)}</strong>
                                </div>
                                <div class="col-sm-6">
                                    <small class="text-muted d-block">Gerente</small>
                                    <strong>${esc(t.gerente_nombre)}</strong>
                                </div>
                                <div class="col-sm-6">
                                    <small class="text-muted d-block">Apertura</small>
                                    <strong>${formatFechaHora(t.fecha_apertura)}</strong>
                                </div>
                                <div class="col-sm-6">
                                    <small class="text-muted d-block">Cierre</small>
                                    <strong>${t.fecha_cierre ? formatFechaHora(t.fecha_cierre) : '<span class="text-success">Abierto</span>'}</strong>
                                </div>
                                ${t.notas_apertura ? `<div class="col-12"><small class="text-muted d-block">Notas apertura</small><span class="small">${esc(t.notas_apertura)}</span></div>` : ''}
                                ${t.notas_cierre   ? `<div class="col-12"><small class="text-muted d-block">Notas cierre</small><span class="small">${esc(t.notas_cierre)}</span></div>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 d-flex flex-column gap-2 justify-content-center align-items-center">
                    ${difBadge}
                    <div class="text-center">
                        <div class="display-6 fw-bold text-success">$${fmt(t.venta_total_dia)}</div>
                        <small class="text-muted">Total ventas del turno</small>
                    </div>
                    <div class="text-center">
                        <strong class="fs-5">${t.pares_vendidos}</strong>
                        <small class="text-muted d-block">pares vendidos</small>
                    </div>
                </div>
            </div>

            <!-- Resumen financiero -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card border h-100">
                        <div class="card-header bg-light fw-semibold"><i class="bi bi-arrow-up-circle text-success me-2"></i>Ingresos del turno</div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Monto inicial caja</span>
                                <strong>$${fmt(t.monto_inicial)}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Ventas efectivo</span>
                                <strong>$${fmt(t.efectivo_ventas)}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Ventas tarjeta</span>
                                <strong>$${fmt(t.tarjeta_ventas)}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Ventas transferencia</span>
                                <strong>$${fmt(t.transferencia_ventas)}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Ventas Go Cuotas</span>
                                <strong>$${fmt(t.go_cuotas_ventas)}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Crédito (1ra cuota)</span>
                                <strong>$${fmt(t.credito_ventas)}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted text-info">Cobro cuotas crédito</span>
                                <strong class="text-info">$${fmt(t.cobro_cuotas_credito)}</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border h-100">
                        <div class="card-header bg-light fw-semibold"><i class="bi bi-arrow-down-circle text-danger me-2"></i>Egresos y control</div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted text-danger">Gastos del día</span>
                                <strong class="text-danger">-$${fmt(t.gastos_dia)}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Retiros</span>
                                <strong>$${fmt(t.retiros)}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Depósitos a banco</span>
                                <strong>$${fmt(t.depositos_banco)}</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Efectivo esperado en caja</span>
                                <strong>$${fmt(efEsperado)}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Efectivo contado al cierre</span>
                                <strong>${t.efectivo_cierre !== null ? '$'+fmt(t.efectivo_cierre) : '<span class="text-muted">—</span>'}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <strong>Diferencia de caja</strong>
                                <strong class="${Math.abs(dif)<0.01 ? 'text-success' : dif>0 ? 'text-warning' : 'text-danger'}">
                                    ${Math.abs(dif)<0.01 ? '$0,00' : (dif>0?'+':'-')+'$'+fmt(Math.abs(dif))}
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ventas del turno -->
            <div class="mb-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-cart-check me-2 text-primary"></i>Ventas registradas (${v.length})</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover border">
                        <thead class="table-light">
                            <tr><th>Hora</th><th>Producto</th><th class="text-center">Cant.</th><th class="text-center">Método</th><th class="text-end">Subtotal</th></tr>
                        </thead>
                        <tbody>${tablaVentas}</tbody>
                    </table>
                </div>
            </div>

            <!-- Gastos del turno -->
            <div class="row g-3">
                <div class="col-md-6">
                    <h6 class="fw-bold mb-3"><i class="bi bi-receipt me-2 text-warning"></i>Gastos (${g.length})</h6>
                    <div class="table-responsive">
                        <table class="table table-sm border">
                            <thead class="table-light">
                                <tr><th>Concepto</th><th>Tipo</th><th class="text-end">Monto</th></tr>
                            </thead>
                            <tbody>${tablaGastos}</tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold mb-3"><i class="bi bi-cash-stack me-2 text-info"></i>Cobros de cuotas (${c.length})</h6>
                    <div class="table-responsive">
                        <table class="table table-sm border">
                            <thead class="table-light">
                                <tr><th>Cliente</th><th class="text-center">Cuota</th><th class="text-end">Monto</th></tr>
                            </thead>
                            <tbody>${tablaCobros}</tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;

        if (t.estado === 'cerrado') {
            const btnPdf = document.getElementById('btn-pdf-modal');
            btnPdf.href = `cierre-caja-pdf.php?id=${turnoId}`;
            btnPdf.classList.remove('d-none');
        }
    } catch (err) {
        document.getElementById('modal-body').innerHTML = '<div class="alert alert-danger">Error de conexión al cargar el detalle.</div>';
    }
}

function fmt(n) {
    return parseFloat(n || 0).toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
function esc(s) {
    const d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
}
function formatFecha(dt) {
    if (!dt) return '—';
    const [d] = dt.split(' ');
    const [y,m,dd] = d.split('-');
    return `${dd}/${m}/${y}`;
}
function formatFechaHora(dt) {
    if (!dt) return '—';
    const [d, t] = dt.split(' ');
    const [y,m,dd] = d.split('-');
    return `${dd}/${m}/${y} ${t?.substring(0,5)||''}`;
}
function formatHora(dt) {
    if (!dt) return '—';
    const parts = dt.split(' ');
    return parts[1]?.substring(0,5) || '—';
}
</script>

<?php include('includes/footer-admin.php'); ?>
