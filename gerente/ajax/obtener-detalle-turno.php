<?php
require_once('../../includes/config.php');
require_once('../../includes/verificar-gerente.php');
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$turno_id = (int)$_GET['id'];
$sucursal_id = obtenerSucursalGerente();

// Obtener datos del turno
$sql = "SELECT tc.*, u.nombre, u.apellido 
        FROM turnos_caja tc
        INNER JOIN usuarios u ON tc.gerente_id = u.id
        WHERE tc.id = ? AND tc.sucursal_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $turno_id, $sucursal_id);
mysqli_stmt_execute($stmt);
$turno = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$turno) {
    echo json_encode(['success' => false]);
    exit;
}

// Obtener ventas del turno
$sql = "SELECT * FROM ventas_diarias WHERE turno_id = ? ORDER BY fecha_venta ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $turno_id);
mysqli_stmt_execute($stmt);
$ventas = mysqli_stmt_get_result($stmt);

// Obtener gastos del turno
$sql = "SELECT * FROM gastos_sucursal WHERE turno_id = ? ORDER BY fecha_gasto ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $turno_id);
mysqli_stmt_execute($stmt);
$gastos = mysqli_stmt_get_result($stmt);

// Obtener cobros de cuotas
$sql = "SELECT * FROM cobro_cuotas_credito WHERE turno_id = ? ORDER BY fecha_cobro ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $turno_id);
mysqli_stmt_execute($stmt);
$cobros = mysqli_stmt_get_result($stmt);

ob_start();
?>
<div class="row">
    <!-- Información del turno -->
    <div class="col-12 mb-4">
        <div class="card bg-light">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <small class="text-muted">Fecha</small>
                        <p class="mb-0"><strong><?php echo date('d/m/Y', strtotime($turno['fecha_apertura'])); ?></strong></p>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Turno</small>
                        <p class="mb-0"><strong><?php echo ucfirst($turno['turno']); ?></strong></p>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Gerente</small>
                        <p class="mb-0"><strong><?php echo $turno['nombre'] . ' ' . $turno['apellido']; ?></strong></p>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Estado</small>
                        <p class="mb-0">
                            <span class="badge bg-<?php echo $turno['estado'] == 'abierto' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($turno['estado']); ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Resumen financiero -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Resumen de Caja</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td>Monto Inicial:</td>
                        <td class="text-end"><strong>$<?php echo number_format($turno['monto_inicial'], 2, ',', '.'); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Efectivo Ventas:</td>
                        <td class="text-end">$<?php echo number_format($turno['efectivo_ventas'], 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>Tarjeta:</td>
                        <td class="text-end">$<?php echo number_format($turno['tarjeta_ventas'], 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>Transferencia:</td>
                        <td class="text-end">$<?php echo number_format($turno['transferencia_ventas'], 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>Go Cuotas:</td>
                        <td class="text-end">$<?php echo number_format($turno['go_cuotas_ventas'], 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>Crédito (1ra cuota):</td>
                        <td class="text-end">$<?php echo number_format($turno['credito_ventas'], 2, ',', '.'); ?></td>
                    </tr>
                    <tr class="table-info">
                        <td>Cobro Cuotas:</td>
                        <td class="text-end"><strong>$<?php echo number_format($turno['cobro_cuotas_credito'], 2, ',', '.'); ?></strong></td>
                    </tr>
                    <tr class="table-danger">
                        <td>Gastos:</td>
                        <td class="text-end"><strong>-$<?php echo number_format($turno['gastos_dia'], 2, ',', '.'); ?></strong></td>
                    </tr>
                    <tr class="table-success">
                        <td><strong>TOTAL VENTAS:</strong></td>
                        <td class="text-end"><strong class="fs-5">$<?php echo number_format($turno['venta_total_dia'], 2, ',', '.'); ?></strong></td>
                    </tr>
                </table>
                
                <?php if ($turno['estado'] == 'cerrado'): ?>
                <hr>
                <table class="table table-sm mb-0">
                    <tr>
                        <td>Efectivo Esperado:</td>
                        <td class="text-end">
                            <?php 
                            $esperado = $turno['monto_inicial'] + $turno['efectivo_ventas'] + $turno['credito_ventas'] + $turno['cobro_cuotas_credito'] - $turno['gastos_dia'];
                            echo '$' . number_format($esperado, 2, ',', '.');
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Efectivo Contado:</td>
                        <td class="text-end">$<?php echo number_format($turno['efectivo_cierre'], 2, ',', '.'); ?></td>
                    </tr>
                    <tr class="<?php 
                        $dif = $turno['diferencia_caja'];
                        echo $dif == 0 ? 'table-success' : ($dif > 0 ? 'table-warning' : 'table-danger');
                    ?>">
                        <td><strong>Diferencia:</strong></td>
                        <td class="text-end">
                            <strong><?php echo $dif >= 0 ? '+' : ''; ?>$<?php echo number_format($dif, 2, ',', '.'); ?></strong>
                        </td>
                    </tr>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Información adicional -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información Adicional</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Horario</small>
                    <p class="mb-0">
                        <i class="bi bi-clock me-1"></i>
                        Apertura: <?php echo date('H:i', strtotime($turno['fecha_apertura'])); ?>
                        <?php if ($turno['fecha_cierre']): ?>
                            <br><i class="bi bi-clock me-1"></i>
                            Cierre: <?php echo date('H:i', strtotime($turno['fecha_cierre'])); ?>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">Pares Vendidos</small>
                    <p class="mb-0"><strong><?php echo $turno['pares_vendidos']; ?></strong> pares</p>
                </div>
                
                <?php if ($turno['notas_apertura']): ?>
                <div class="mb-3">
                    <small class="text-muted">Notas de Apertura</small>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($turno['notas_apertura'])); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($turno['notas_cierre']): ?>
                <div class="mb-0">
                    <small class="text-muted">Notas de Cierre</small>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($turno['notas_cierre'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Ventas -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-cart me-2"></i>Ventas Realizadas (<?php echo mysqli_num_rows($ventas); ?>)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Hora</th>
                                <th>Producto</th>
                                <th>Talle/Color</th>
                                <th>Cant.</th>
                                <th>Precio</th>
                                <th>Subtotal</th>
                                <th>Método</th>
                                <th>Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($ventas) > 0): ?>
                                <?php while ($venta = mysqli_fetch_assoc($ventas)): ?>
                                <tr>
                                    <td><?php echo date('H:i', strtotime($venta['fecha_venta'])); ?></td>
                                    <td><?php echo htmlspecialchars($venta['producto_nombre']); ?></td>
                                    <td>
                                        <?php if ($venta['talle']): ?>
                                            T: <?php echo $venta['talle']; ?>
                                        <?php endif; ?>
                                        <?php if ($venta['color']): ?>
                                            <br>C: <?php echo $venta['color']; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $venta['cantidad']; ?></td>
                                    <td>$<?php echo number_format($venta['precio_unitario'], 2, ',', '.'); ?></td>
                                    <td><strong>$<?php echo number_format($venta['subtotal'], 2, ',', '.'); ?></strong></td>
                                    <td><span class="badge bg-secondary"><?php echo ucfirst($venta['metodo_pago']); ?></span></td>
                                    <td>
                                        <?php if ($venta['tipo_venta'] == 'online'): ?>
                                            <span class="badge bg-info">Online</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Mostrador</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center text-muted">Sin ventas registradas</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gastos -->
    <?php if (mysqli_num_rows($gastos) > 0): ?>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="bi bi-receipt me-2"></i>Gastos del Día</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <?php while ($gasto = mysqli_fetch_assoc($gastos)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($gasto['concepto']); ?></td>
                            <td class="text-end"><strong>$<?php echo number_format($gasto['monto'], 2, ',', '.'); ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Cobros de cuotas -->
    <?php if (mysqli_num_rows($cobros) > 0): ?>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Cobros de Cuotas</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <?php while ($cobro = mysqli_fetch_assoc($cobros)): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($cobro['cliente_nombre']); ?>
                                <?php if ($cobro['numero_cuota']): ?>
                                    <br><small class="text-muted">Cuota #<?php echo $cobro['numero_cuota']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><strong>$<?php echo number_format($cobro['monto_cobrado'], 2, ',', '.'); ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php
$html = ob_get_clean();
echo json_encode(['success' => true, 'html' => $html]);
mysqli_close($conn);
?>
