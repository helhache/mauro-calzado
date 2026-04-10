<?php
/**
 * PANEL GERENTE - HISTORIAL DE CAJA
 * Muestra todos los turnos cerrados con sus detalles
 */

require_once('../includes/config.php');
require_once('../includes/verificar-gerente.php');

$titulo_pagina = "Historial de Caja";
$sucursal_id = obtenerSucursalGerente();

// Filtros
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01'); // Primer día del mes
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d'); // Hoy

// Obtener turnos cerrados
$sql = "SELECT 
            tc.*,
            u.nombre as gerente_nombre,
            u.apellido as gerente_apellido
        FROM turnos_caja tc
        INNER JOIN usuarios u ON tc.gerente_id = u.id
        WHERE tc.sucursal_id = ?
        AND DATE(tc.fecha_apertura) BETWEEN ? AND ?
        ORDER BY tc.fecha_apertura DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iss", $sucursal_id, $fecha_desde, $fecha_hasta);
mysqli_stmt_execute($stmt);
$turnos = mysqli_stmt_get_result($stmt);

include('includes/header-gerente.php');
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1">Historial de Caja</h2>
                <p class="text-muted mb-0">Consulta turnos anteriores</p>
            </div>
            <a href="caja.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-2"></i>Volver a Caja
            </a>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Desde</label>
                <input type="date" class="form-control" name="desde" value="<?php echo $fecha_desde; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Hasta</label>
                <input type="date" class="form-control" name="hasta" value="<?php echo $fecha_hasta; ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-2"></i>Buscar
                </button>
                <a href="historial-caja.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Resumen del período -->
<?php
$total_ventas = 0;
$total_pares = 0;
$total_gastos = 0;
$total_turnos = 0;

mysqli_data_seek($turnos, 0); // Reset pointer
while ($turno = mysqli_fetch_assoc($turnos)) {
    if ($turno['estado'] == 'cerrado') {
        $total_ventas += $turno['venta_total_dia'];
        $total_pares += $turno['pares_vendidos'];
        $total_gastos += $turno['gastos_dia'];
        $total_turnos++;
    }
}
mysqli_data_seek($turnos, 0); // Reset again
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-calendar-check fs-3 text-primary mb-2"></i>
                <h3 class="mb-0"><?php echo $total_turnos; ?></h3>
                <p class="text-muted mb-0">Turnos Cerrados</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-currency-dollar fs-3 text-success mb-2"></i>
                <h3 class="mb-0">$<?php echo number_format($total_ventas, 0, ',', '.'); ?></h3>
                <p class="text-muted mb-0">Total Ventas</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-box-seam fs-3 text-info mb-2"></i>
                <h3 class="mb-0"><?php echo number_format($total_pares); ?></h3>
                <p class="text-muted mb-0">Pares Vendidos</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-receipt fs-3 text-danger mb-2"></i>
                <h3 class="mb-0">$<?php echo number_format($total_gastos, 0, ',', '.'); ?></h3>
                <p class="text-muted mb-0">Total Gastos</p>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de turnos -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Turno</th>
                        <th>Gerente</th>
                        <th>Ventas</th>
                        <th>Pares</th>
                        <th>Gastos</th>
                        <th>Diferencia</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (mysqli_num_rows($turnos) > 0):
                        while ($turno = mysqli_fetch_assoc($turnos)): 
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo date('d/m/Y', strtotime($turno['fecha_apertura'])); ?></strong>
                            <br><small class="text-muted"><?php echo date('H:i', strtotime($turno['fecha_apertura'])); ?></small>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $turno['turno'] == 'manana' ? 'warning' : 'info'; ?>">
                                <?php echo ucfirst($turno['turno']); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($turno['gerente_nombre'] . ' ' . $turno['gerente_apellido']); ?>
                        </td>
                        <td>
                            <strong class="text-success">$<?php echo number_format($turno['venta_total_dia'], 0, ',', '.'); ?></strong>
                        </td>
                        <td><?php echo $turno['pares_vendidos']; ?></td>
                        <td>
                            <span class="text-danger">$<?php echo number_format($turno['gastos_dia'], 0, ',', '.'); ?></span>
                        </td>
                        <td>
                            <?php
                            $diferencia = $turno['diferencia_caja'];
                            $color = 'secondary';
                            $icono = 'dash-circle';
                            if ($diferencia > 0) {
                                $color = 'warning';
                                $icono = 'arrow-up-circle';
                            } elseif ($diferencia < 0) {
                                $color = 'danger';
                                $icono = 'arrow-down-circle';
                            } else {
                                $color = 'success';
                                $icono = 'check-circle';
                            }
                            ?>
                            <span class="badge bg-<?php echo $color; ?>">
                                <i class="bi bi-<?php echo $icono; ?> me-1"></i>
                                <?php echo $diferencia >= 0 ? '+' : ''; ?><?php echo number_format($diferencia, 2, ',', '.'); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($turno['estado'] == 'cerrado'): ?>
                                <span class="badge bg-secondary">Cerrado</span>
                            <?php else: ?>
                                <span class="badge bg-success">Abierto</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="verDetalle(<?php echo $turno['id']; ?>)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1"></i>
                            <p class="mb-0 mt-2">No hay turnos en este período</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Detalle de Turno -->
<div class="modal fade" id="modalDetalleTurno" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-text me-2"></i>Detalle del Turno</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenidoDetalleTurno">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function verDetalle(turnoId) {
    const modal = new bootstrap.Modal(document.getElementById('modalDetalleTurno'));
    modal.show();
    
    document.getElementById('contenidoDetalleTurno').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
    `;
    
    fetch(`ajax/obtener-detalle-turno.php?id=${turnoId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('contenidoDetalleTurno').innerHTML = data.html;
            } else {
                document.getElementById('contenidoDetalleTurno').innerHTML = 
                    '<div class="alert alert-danger">Error al cargar detalle</div>';
            }
        })
        .catch(err => {
            console.error(err);
            document.getElementById('contenidoDetalleTurno').innerHTML = 
                '<div class="alert alert-danger">Error de conexión</div>';
        });
}
</script>

<?php include('includes/footer-gerente.php'); ?>
