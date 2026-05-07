<?php
/**
 * PANEL GERENTE - MANEJO DE CAJA
 * Permite abrir/cerrar turnos y registrar ventas
 */

require_once('../includes/config.php');
require_once('../includes/verificar-gerente.php');

$titulo_pagina = "Caja";
$sucursal_id = obtenerSucursalGerente();

// Verificar si hay un turno abierto
$sql = "SELECT * FROM turnos_caja WHERE sucursal_id = ? AND estado = 'abierto' ORDER BY fecha_apertura DESC LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $sucursal_id);
mysqli_stmt_execute($stmt);
$turno_actual = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Obtener datos de la sucursal
$sql = "SELECT * FROM sucursales WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $sucursal_id);
mysqli_stmt_execute($stmt);
$sucursal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Conteo de ventas por método de pago del turno actual
$conteo_metodos = [];
$total_cobros_cuotas = 0;
if ($turno_actual) {
    $sql = "SELECT metodo_pago, COUNT(*) as cantidad, SUM(subtotal) as total
            FROM ventas_diarias WHERE turno_id = ? GROUP BY metodo_pago";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $turno_actual['id']);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $conteo_metodos[$row['metodo_pago']] = $row;
    }

    try {
        $sql = "SELECT COUNT(*) as total FROM cobro_cuotas_credito WHERE turno_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $turno_actual['id']);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($res);
            $total_cobros_cuotas = $row['total'] ?? 0;
        }
    } catch (Exception $e) {
        $total_cobros_cuotas = 0;
    }
}

include('includes/header-gerente.php');
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1">Caja - <?php echo htmlspecialchars($sucursal['nombre']); ?></h2>
                <p class="text-muted mb-0"><?php echo date('d/m/Y'); ?></p>
            </div>
            <?php if ($turno_actual): ?>
                <span class="badge bg-success fs-6"><i class="bi bi-unlock me-2"></i>Turno Abierto</span>
            <?php else: ?>
                <span class="badge bg-secondary fs-6"><i class="bi bi-lock me-2"></i>Turno Cerrado</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!$turno_actual): ?>
    <!-- NO HAY TURNO ABIERTO - Mostrar botón para abrir -->
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card border-0 shadow-sm text-center py-5">
                <div class="card-body">
                    <i class="bi bi-lock fs-1 text-muted mb-3"></i>
                    <h4>No hay turno abierto</h4>
                    <p class="text-muted">Para comenzar a operar, debes abrir un turno de caja</p>
                    <button class="btn btn-success btn-lg mt-3" onclick="modalAbrirTurno()">
                        <i class="bi bi-unlock me-2"></i>Abrir Turno
                    </button>
                </div>
            </div>
        </div>
    </div>
    
<?php else: ?>
    <!-- HAY TURNO ABIERTO - Mostrar información y operaciones -->
    <div class="row">
        <!-- Columna izquierda: Información del turno -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información del Turno</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Turno</small>
                        <p class="mb-0"><strong><?php echo ucfirst($turno_actual['turno']); ?></strong></p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Apertura</small>
                        <p class="mb-0"><?php echo date('d/m/Y H:i', strtotime($turno_actual['fecha_apertura'])); ?></p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Monto Inicial</small>
                        <p class="mb-0 fs-5"><strong>$<?php echo number_format($turno_actual['monto_inicial'], 2, ',', '.'); ?></strong></p>
                    </div>
                    <?php if ($turno_actual['notas_apertura']): ?>
                    <div class="mb-0">
                        <small class="text-muted">Notas</small>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($turno_actual['notas_apertura'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Resumen del día -->
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Resumen del Día</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Efectivo:</span>
                        <strong>$<?php echo number_format($turno_actual['efectivo_ventas'], 2, ',', '.'); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Tarjeta:</span>
                        <strong>$<?php echo number_format($turno_actual['tarjeta_ventas'], 2, ',', '.'); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Transferencia:</span>
                        <strong>$<?php echo number_format($turno_actual['transferencia_ventas'], 2, ',', '.'); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Go Cuotas:</span>
                        <strong>$<?php echo number_format($turno_actual['go_cuotas_ventas'], 2, ',', '.'); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Crédito (1ra cuota):</span>
                        <strong>$<?php echo number_format($turno_actual['credito_ventas'], 2, ',', '.'); ?></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Cobro cuotas:</span>
                        <strong class="text-info">$<?php echo number_format($turno_actual['cobro_cuotas_credito'], 2, ',', '.'); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Gastos:</span>
                        <strong class="text-danger">-$<?php echo number_format($turno_actual['gastos_dia'], 2, ',', '.'); ?></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>TOTAL VENTAS:</strong>
                        <strong class="text-success fs-5">$<?php echo number_format($turno_actual['venta_total_dia'], 2, ',', '.'); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span>Pares vendidos:</span>
                        <strong><?php echo $turno_actual['pares_vendidos']; ?></strong>
                    </div>
                </div>
            </div>
            
            <button class="btn btn-danger btn-lg w-100 mt-3" onclick="modalCerrarTurno()">
                <i class="bi bi-lock me-2"></i>Cerrar Turno
            </button>
        </div>
        
        <!-- Columna derecha: Acciones rápidas -->
        <div class="col-lg-8">
            <!-- Botones de acción -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="cursor: pointer;" onclick="modalNuevaVenta()">
                        <div class="card-body text-center py-4">
                            <i class="bi bi-cart-plus fs-1 text-primary mb-2"></i>
                            <h5 class="mb-0">Registrar Venta</h5>
                            <small class="text-muted">Mostrador u online</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="cursor: pointer;" onclick="modalCobroCuota()">
                        <div class="card-body text-center py-4">
                            <i class="bi bi-cash-stack fs-1 text-info mb-2"></i>
                            <h5 class="mb-0">Cobro de Cuota</h5>
                            <small class="text-muted">Créditos financiera</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="cursor: pointer;" onclick="modalGasto()">
                        <div class="card-body text-center py-4">
                            <i class="bi bi-receipt fs-1 text-warning mb-2"></i>
                            <h5 class="mb-0">Registrar Gasto</h5>
                            <small class="text-muted">Servicios, proveedores</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="cursor: pointer;" onclick="verHistorial()">
                        <div class="card-body text-center py-4">
                            <i class="bi bi-clock-history fs-1 text-secondary mb-2"></i>
                            <h5 class="mb-0">Historial</h5>
                            <small class="text-muted">Ver operaciones</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Últimas ventas -->
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i>Últimas Ventas</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Hora</th>
                                    <th>Producto</th>
                                    <th>Cant.</th>
                                    <th>Método</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT * FROM ventas_diarias WHERE turno_id = ? ORDER BY fecha_venta DESC LIMIT 10";
                                $stmt = mysqli_prepare($conn, $sql);
                                mysqli_stmt_bind_param($stmt, "i", $turno_actual['id']);
                                mysqli_stmt_execute($stmt);
                                $ventas = mysqli_stmt_get_result($stmt);
                                
                                if (mysqli_num_rows($ventas) > 0):
                                    while ($venta = mysqli_fetch_assoc($ventas)):
                                ?>
                                <tr>
                                    <td><?php echo date('H:i', strtotime($venta['fecha_venta'])); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($venta['producto_nombre']); ?>
                                        <?php if ($venta['tipo_venta'] == 'online'): ?>
                                            <span class="badge bg-info">Online</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $venta['cantidad']; ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo ucfirst($venta['metodo_pago']); ?></span>
                                    </td>
                                    <td class="text-end"><strong>$<?php echo number_format($venta['subtotal'], 2, ',', '.'); ?></strong></td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-4"></i>
                                        <p class="mb-0">No hay ventas registradas aún</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Modal: Abrir Turno -->
<div class="modal fade" id="modalAbrirTurno" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-unlock me-2"></i>Abrir Turno</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAbrirTurno">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Turno <span class="text-danger">*</span></label>
                        <select class="form-select" name="turno" required>
                            <option value="">Seleccionar...</option>
                            <option value="manana">Mañana (<?php echo substr($sucursal['horario_apertura_manana'], 0, 5); ?> - <?php echo substr($sucursal['horario_cierre_manana'], 0, 5); ?>)</option>
                            <option value="tarde">Tarde (<?php echo substr($sucursal['horario_apertura_tarde'], 0, 5); ?> - <?php echo substr($sucursal['horario_cierre_tarde'], 0, 5); ?>)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto inicial en caja <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="monto_inicial" min="0" step="0.01" required value="0">
                        </div>
                        <small class="text-muted">Efectivo con el que inicia el turno</small>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notas (opcional)</label>
                        <textarea class="form-control" name="notas_apertura" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-unlock me-2"></i>Abrir Turno
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Cerrar Turno -->
<div class="modal fade" id="modalCerrarTurno" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-lock me-2"></i>Cerrar Turno</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCerrarTurno">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Importante:</strong> Verifica que todos los montos sean correctos antes de cerrar el turno.
                    </div>
                    
                    <!-- Resumen por método de pago con conteos -->
                    <div class="row mb-3">
                        <div class="col-md-7">
                            <h6 class="fw-bold mb-2">Cobros por método de pago:</h6>
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr><th>Método</th><th class="text-center">Cant.</th><th class="text-end">Total</th></tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $metodos_label = [
                                        'efectivo'     => 'Efectivo',
                                        'tarjeta'      => 'Tarjeta',
                                        'transferencia'=> 'Transferencia',
                                        'go_cuotas'    => 'Go Cuotas',
                                        'credito'      => 'Crédito (1ra cuota)',
                                    ];
                                    foreach ($metodos_label as $key => $label):
                                        $cant  = $conteo_metodos[$key]['cantidad'] ?? 0;
                                        $total = $conteo_metodos[$key]['total'] ?? 0;
                                    ?>
                                    <tr<?php echo $cant > 0 ? ' class="table-active"' : ''; ?>>
                                        <td><?php echo $label; ?></td>
                                        <td class="text-center"><?php echo $cant; ?></td>
                                        <td class="text-end"><strong>$<?php echo number_format($total, 2, ',', '.'); ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if ($total_cobros_cuotas > 0): ?>
                                    <tr class="table-info">
                                        <td>Cobro cuotas</td>
                                        <td class="text-center"><?php echo $total_cobros_cuotas; ?></td>
                                        <td class="text-end"><strong>$<?php echo number_format($turno_actual['cobro_cuotas_credito'] ?? 0, 2, ',', '.'); ?></strong></td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-5 text-center d-flex flex-column justify-content-center">
                            <p class="text-muted mb-1 small">Total ventas del día</p>
                            <h2 class="text-success fw-bold mb-1">$<?php echo number_format($turno_actual['venta_total_dia'] ?? 0, 2, ',', '.'); ?></h2>
                            <p class="text-muted small mb-0"><i class="bi bi-box-seam me-1"></i><?php echo $turno_actual['pares_vendidos'] ?? 0; ?> pares vendidos</p>
                        </div>
                    </div>

                    <hr>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Efectivo contado en caja <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="efectivo_cierre" name="efectivo_cierre" min="0" step="0.01" required>
                            </div>
                            <small class="text-muted">Contá el efectivo físico en caja</small>
                        </div>

                        <?php if (($turno_actual['tarjeta_ventas'] ?? 0) > 0 || ($conteo_metodos['tarjeta']['cantidad'] ?? 0) > 0): ?>
                        <div class="col-md-6">
                            <label class="form-label">Número de lote (ticket final tarjetas) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="numero_lote" id="numero_lote"
                                   placeholder="Ej: 001234" maxlength="50" required>
                            <small class="text-muted">Del cierre del posnet</small>
                        </div>
                        <?php else: ?>
                        <div class="col-md-6">
                            <label class="form-label">Número de lote (tarjetas)</label>
                            <input type="text" class="form-control" name="numero_lote" id="numero_lote"
                                   placeholder="N/A — no hubo ventas con tarjeta" maxlength="50" readonly>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div id="diferencia_caja" class="alert d-none mt-3"></div>

                    <div class="mt-3 mb-0">
                        <label class="form-label">Notas de cierre (opcional)</label>
                        <textarea class="form-control" name="notas_cierre" rows="2" placeholder="Observaciones, faltantes, sobrantes, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer flex-wrap gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-lock me-2"></i>Cerrar Turno
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Nueva Venta — Multi-ítem -->
<div class="modal fade" id="modalNuevaVenta" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-cart-plus me-2"></i>Registrar Venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevaVenta">
                <div class="modal-body">

                    <!-- Fila 1: tipo + método -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Tipo de venta</label>
                            <select class="form-select" name="tipo_venta">
                                <option value="mostrador">Mostrador</option>
                                <option value="online">Online (WhatsApp)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Método de pago <span class="text-danger">*</span></label>
                            <select class="form-select" name="metodo_pago" id="metodo_pago" required>
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="go_cuotas">Go Cuotas</option>
                                <option value="credito">Crédito (1ra cuota)</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="campoCupon" style="display:none">
                            <label class="form-label">N° cupón posnet <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="numero_cupon" id="numero_cupon" maxlength="50" placeholder="Ej: 000123456">
                        </div>
                    </div>

                    <!-- Datos transferencia -->
                    <div id="camposTransferencia" class="row g-2 mb-3" style="display:none">
                        <div class="col-12"><p class="small text-muted fw-semibold mb-1"><i class="bi bi-bank me-1"></i>Datos del cliente (transferencia)</p></div>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="transferencia_nombre" id="transferencia_nombre" placeholder="Nombre">
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="transferencia_apellido" id="transferencia_apellido" placeholder="Apellido">
                        </div>
                    </div>

                    <!-- Cliente -->
                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <label class="form-label">Cliente</label>
                            <div class="d-flex gap-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_cliente" id="rbSinCuenta" value="sin_cuenta" checked>
                                    <label class="form-check-label" for="rbSinCuenta">Sin cuenta</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_cliente" id="rbConCuenta" value="registrado">
                                    <label class="form-check-label" for="rbConCuenta">Con cuenta en la tienda</label>
                                </div>
                            </div>
                            <div id="seccionClienteSinCuenta">
                                <input type="text" class="form-control" name="nombre_cliente_mostrador" placeholder="Nombre del cliente (opcional)">
                            </div>
                            <div id="seccionClienteConCuenta" style="display:none" class="position-relative">
                                <input type="text" class="form-control" id="buscarClienteInput" placeholder="Buscar por nombre, apellido o DNI..." autocomplete="off">
                                <input type="hidden" name="cliente_id" id="cliente_id">
                                <div id="resultadosClientes" class="list-group position-absolute w-100" style="z-index:1050;display:none;max-height:200px;overflow-y:auto;"></div>
                                <small class="text-muted" id="clienteSeleccionadoInfo"></small>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Tabla de ítems -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong><i class="bi bi-list-ul me-1"></i>Ítems de la venta</strong>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="agregarItemVenta()">
                            <i class="bi bi-plus-circle me-1"></i>Agregar ítem
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0" id="tablaItems">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width:200px">Producto</th>
                                    <th style="min-width:100px">Talle</th>
                                    <th style="min-width:100px">Color</th>
                                    <th style="min-width:60px">Stock</th>
                                    <th style="min-width:70px">Cant.</th>
                                    <th style="min-width:110px">Precio unit.</th>
                                    <th style="min-width:110px">Subtotal</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <!-- Filas generadas por JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Total general -->
                    <div class="d-flex justify-content-end align-items-center mt-3 gap-3">
                        <span class="text-muted">Total de la venta:</span>
                        <span class="fs-4 fw-bold text-success" id="totalVenta">$0,00</span>
                    </div>

                    <!-- Observaciones -->
                    <div class="mt-3">
                        <label class="form-label">Observaciones (opcional)</label>
                        <textarea class="form-control" name="notas" rows="2"></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Registrar Venta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ============================================================================
// MODAL NUEVA VENTA — Sistema multi-ítem
// ============================================================================
var itemIdx = 0;
var timeoutsItems = {};
var timeoutCliente;

function agregarItemVenta(datosIniciales) {
    var idx = itemIdx++;
    var d = datosIniciales || {};
    var tr = document.createElement('tr');
    tr.id = 'item-row-' + idx;
    tr.innerHTML =
        '<td class="p-1">' +
            '<input type="text" class="form-control form-control-sm item-buscar" ' +
                   'placeholder="Buscar o escribir..." autocomplete="off" ' +
                   'data-idx="' + idx + '" value="' + (d.nombre || '') + '">' +
            '<input type="hidden" name="items[' + idx + '][producto_id]" class="item-pid" value="' + (d.id || '') + '">' +
            '<input type="hidden" name="items[' + idx + '][producto_nombre]" class="item-pnombre" value="' + (d.nombre || '') + '">' +
        '</td>' +
        '<td class="p-1">' +
            '<select name="items[' + idx + '][talle]" class="form-select form-select-sm item-talle" data-idx="' + idx + '">' +
                '<option value="">—</option>' +
            '</select>' +
        '</td>' +
        '<td class="p-1">' +
            '<select name="items[' + idx + '][color]" class="form-select form-select-sm item-color" data-idx="' + idx + '">' +
                '<option value="">—</option>' +
            '</select>' +
        '</td>' +
        '<td class="p-1 text-center">' +
            '<small class="item-stock text-muted">—</small>' +
        '</td>' +
        '<td class="p-1">' +
            '<input type="number" name="items[' + idx + '][cantidad]" class="form-control form-control-sm item-cantidad" ' +
                   'min="1" value="1" required data-idx="' + idx + '">' +
        '</td>' +
        '<td class="p-1">' +
            '<div class="input-group input-group-sm">' +
                '<span class="input-group-text">$</span>' +
                '<input type="number" name="items[' + idx + '][precio_unitario]" class="form-control form-control-sm item-precio" ' +
                       'min="0" step="0.01" required data-idx="' + idx + '" value="' + (d.precio || '') + '">' +
            '</div>' +
        '</td>' +
        '<td class="p-1">' +
            '<span class="fw-bold item-subtotal text-success">$0,00</span>' +
        '</td>' +
        '<td class="p-1 text-center">' +
            (idx > 0 ? '<button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarItem(' + idx + ')"><i class="bi bi-x"></i></button>' : '') +
        '</td>';
    document.getElementById('itemsBody').appendChild(tr);

    // Poblar talles/colores si hay datos iniciales
    if (d.talles || d.colores) {
        poblarSelectsTalleColor(idx, d);
    }

    // Event listeners del ítem
    var row = document.getElementById('item-row-' + idx);
    row.querySelector('.item-buscar').addEventListener('input', function() { onBuscarItem(idx, this.value); });
    row.querySelector('.item-cantidad').addEventListener('input', function() { recalcularItem(idx); });
    row.querySelector('.item-precio').addEventListener('input', function() { recalcularItem(idx); });
    row.querySelector('.item-talle').addEventListener('change', function() { consultarStockItem(idx); });
    row.querySelector('.item-color').addEventListener('change', function() { consultarStockItem(idx); });

    recalcularItem(idx);
}

function eliminarItem(idx) {
    var row = document.getElementById('item-row-' + idx);
    if (row) row.remove();
    recalcularTotal();
}

// --- PORTAL DROPDOWN (fuera de la tabla, sin romper el layout) ---
var _portalEl = null;
var _portalIdx = -1;

function getPortal() {
    if (!_portalEl) {
        _portalEl = document.createElement('div');
        _portalEl.className = 'list-group shadow';
        _portalEl.style.cssText = 'position:fixed;z-index:9999;max-height:220px;overflow-y:auto;min-width:280px;display:none;border-radius:6px;';
        document.body.appendChild(_portalEl);
    }
    return _portalEl;
}

function posicionarPortal(inputEl) {
    var rect = inputEl.getBoundingClientRect();
    var portal = getPortal();
    var portalH = Math.min(220, portal.scrollHeight || 220);
    // Siempre mostrar arriba; solo baja si no hay espacio suficiente
    if (rect.top >= portalH + 4) {
        portal.style.top = (rect.top - portalH - 4) + 'px';
    } else {
        portal.style.top = (rect.bottom + 2) + 'px';
    }
    portal.style.left  = rect.left + 'px';
    portal.style.width = Math.max(rect.width, 280) + 'px';
}

function cerrarPortal() {
    if (_portalEl) _portalEl.style.display = 'none';
    _portalIdx = -1;
}

function onBuscarItem(idx, q) {
    clearTimeout(timeoutsItems[idx]);
    var row = document.getElementById('item-row-' + idx);
    if (!row) return;
    row.querySelector('.item-pnombre').value = q;
    row.querySelector('.item-pid').value = '';
    var input = row.querySelector('.item-buscar');
    if (q.length < 2) { cerrarPortal(); return; }
    timeoutsItems[idx] = setTimeout(function() {
        fetch('ajax/buscar-productos.php?q=' + encodeURIComponent(q))
            .then(function(r) { return r.json(); })
            .then(function(productos) {
                var portal = getPortal();
                if (!productos.length) { portal.style.display = 'none'; return; }
                var html = '';
                productos.forEach(function(p) {
                    html += '<a href="#" class="list-group-item list-group-item-action py-2 px-3 small" ' +
                            'onmousedown=\'seleccionarItemProducto(' + idx + ', ' + JSON.stringify(p).replace(/"/g,'&quot;') + '); return false;\'>' +
                            '<strong>' + p.nombre + '</strong>' +
                            ' <span class="text-success float-end">$' + Number(p.precio).toLocaleString('es-AR') + '</span>' +
                            '<br><small class="text-muted">Stock total: ' + p.stock + ' pares</small></a>';
                });
                portal.innerHTML = html;
                _portalIdx = idx;
                posicionarPortal(input);
                portal.style.display = 'block';
            });
    }, 280);
}

function seleccionarItemProducto(idx, producto) {
    cerrarPortal();
    var row = document.getElementById('item-row-' + idx);
    if (!row) return;
    row.querySelector('.item-buscar').value   = producto.nombre;
    row.querySelector('.item-pid').value       = producto.id;
    row.querySelector('.item-pnombre').value   = producto.nombre;
    row.querySelector('.item-precio').value    = producto.precio;
    poblarSelectsTalleColor(idx, producto);
    recalcularItem(idx);
}

function poblarSelectsTalleColor(idx, producto) {
    var row = document.getElementById('item-row-' + idx);
    if (!row) return;
    var selTalle = row.querySelector('.item-talle');
    var selColor = row.querySelector('.item-color');
    selTalle.innerHTML = '<option value="">—</option>';
    selColor.innerHTML = '<option value="">—</option>';
    if (producto.talles) {
        producto.talles.split(',').forEach(function(t) {
            t = t.trim();
            selTalle.innerHTML += '<option value="' + t + '">' + t + '</option>';
        });
    }
    if (producto.colores && producto.colores.length) {
        producto.colores.forEach(function(c) {
            selColor.innerHTML += '<option value="' + c + '">' + c + '</option>';
        });
    }
    consultarStockItem(idx);
}

function consultarStockItem(idx) {
    var row = document.getElementById('item-row-' + idx);
    if (!row) return;
    var pid   = row.querySelector('.item-pid').value;
    var talle = row.querySelector('.item-talle').value;
    var color = row.querySelector('.item-color').value;
    var stockEl = row.querySelector('.item-stock');
    if (!pid) { stockEl.textContent = '—'; return; }
    var url = 'ajax/obtener-stock-detalle.php?producto_id=' + pid + '&talle=' + encodeURIComponent(talle) + '&color=' + encodeURIComponent(color);
    fetch(url)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var s = data.stock;
            stockEl.textContent = s;
            stockEl.className = 'item-stock fw-bold ' + (s === 0 ? 'text-danger' : s <= 3 ? 'text-warning' : 'text-success');
        });
}

function recalcularItem(idx) {
    var row = document.getElementById('item-row-' + idx);
    if (!row) return;
    var cant   = parseFloat(row.querySelector('.item-cantidad').value) || 0;
    var precio = parseFloat(row.querySelector('.item-precio').value) || 0;
    var sub    = cant * precio;
    row.querySelector('.item-subtotal').textContent = '$' + sub.toLocaleString('es-AR', {minimumFractionDigits: 2});
    recalcularTotal();
}

function recalcularTotal() {
    var total = 0;
    document.querySelectorAll('#itemsBody tr').forEach(function(tr) {
        var cant   = parseFloat(tr.querySelector('.item-cantidad')?.value) || 0;
        var precio = parseFloat(tr.querySelector('.item-precio')?.value)   || 0;
        total += cant * precio;
    });
    document.getElementById('totalVenta').textContent = '$' + total.toLocaleString('es-AR', {minimumFractionDigits: 2});
}

// Cerrar dropdowns al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.item-buscar') && _portalEl && !_portalEl.contains(e.target)) {
        cerrarPortal();
    }
    if (!e.target.closest('#buscarClienteInput') && !e.target.closest('#resultadosClientes')) {
        var rc = document.getElementById('resultadosClientes');
        if (rc) rc.style.display = 'none';
    }
});

// Método de pago: mostrar/ocultar campos condicionales
document.getElementById('metodo_pago')?.addEventListener('change', function() {
    var val = this.value;
    document.getElementById('campoCupon').style.display      = val === 'tarjeta'       ? 'block' : 'none';
    document.getElementById('camposTransferencia').style.display = val === 'transferencia' ? 'flex'  : 'none';
    document.getElementById('numero_cupon').required         = (val === 'tarjeta');
    document.getElementById('transferencia_nombre').required = (val === 'transferencia');
    document.getElementById('transferencia_apellido').required = (val === 'transferencia');
});

// Tipo de cliente
document.querySelectorAll('input[name="tipo_cliente"]').forEach(function(rb) {
    rb.addEventListener('change', function() {
        var con = this.value === 'registrado';
        document.getElementById('seccionClienteSinCuenta').style.display = con ? 'none'  : 'block';
        document.getElementById('seccionClienteConCuenta').style.display = con ? 'block' : 'none';
        if (!con) { document.getElementById('cliente_id').value = ''; document.getElementById('clienteSeleccionadoInfo').textContent = ''; }
    });
});

// Buscar cliente registrado
document.getElementById('buscarClienteInput')?.addEventListener('input', function() {
    clearTimeout(timeoutCliente);
    var q = this.value.trim();
    var res = document.getElementById('resultadosClientes');
    if (q.length < 2) { res.style.display = 'none'; return; }
    timeoutCliente = setTimeout(function() {
        fetch('ajax/buscar-clientes.php?q=' + encodeURIComponent(q))
            .then(function(r) { return r.json(); })
            .then(function(clientes) {
                if (!clientes.length) { res.style.display = 'none'; return; }
                var html = '';
                clientes.forEach(function(c) {
                    html += '<a href="#" class="list-group-item list-group-item-action py-2 px-3" ' +
                            'onclick="seleccionarCliente(' + c.id + ', \'' + c.nombre.replace(/'/g,"\\'") + '\', \'' + (c.dni||'') + '\'); return false;">' +
                            '<strong>' + c.nombre + '</strong>' +
                            (c.dni ? '<br><small class="text-muted">DNI: ' + c.dni + '</small>' : '') +
                            '<br><small class="text-muted">' + c.email + '</small></a>';
                });
                res.innerHTML = html;
                res.style.display = 'block';
            });
    }, 300);
});

function seleccionarCliente(id, nombre, dni) {
    document.getElementById('cliente_id').value = id;
    document.getElementById('buscarClienteInput').value = nombre;
    document.getElementById('resultadosClientes').style.display = 'none';
    document.getElementById('clienteSeleccionadoInfo').textContent = 'Vinculado: ' + nombre + (dni ? ' (DNI: ' + dni + ')' : '');
}

// Reset modal al abrir
document.getElementById('modalNuevaVenta')?.addEventListener('hide.bs.modal', function() {
    cerrarPortal();
});
document.getElementById('modalNuevaVenta')?.addEventListener('show.bs.modal', function() {
    cerrarPortal();
    document.getElementById('formNuevaVenta').reset();
    document.getElementById('itemsBody').innerHTML = '';
    itemIdx = 0;
    document.getElementById('totalVenta').textContent = '$0,00';
    document.getElementById('campoCupon').style.display = 'none';
    document.getElementById('camposTransferencia').style.display = 'none';
    document.getElementById('seccionClienteSinCuenta').style.display = 'block';
    document.getElementById('seccionClienteConCuenta').style.display = 'none';
    document.getElementById('cliente_id').value = '';
    document.getElementById('clienteSeleccionadoInfo').textContent = '';
    document.getElementById('numero_cupon').required = false;
    document.getElementById('transferencia_nombre').required = false;
    document.getElementById('transferencia_apellido').required = false;
    // Agregar primer ítem vacío
    agregarItemVenta();
});

// Cerrar portal al hacer scroll dentro del modal (evita que quede flotando desfasado)
document.querySelector('#modalNuevaVenta .modal-body')?.addEventListener('scroll', cerrarPortal, { passive: true });
// El querySelector puede ejecutarse antes de que el DOM esté listo, registrar al mostrar:
document.getElementById('modalNuevaVenta')?.addEventListener('shown.bs.modal', function() {
    var body = this.querySelector('.modal-body');
    if (body) body.addEventListener('scroll', cerrarPortal, { passive: true });
});
</script>

<!-- Modal: Cobro de Cuota -->
<div class="modal fade" id="modalCobroCuota" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-cash-stack me-2"></i>Cobro de Cuota</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCobroCuota">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Este dinero se separa para enviar a la financiera o cuenta de la empresa.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre del cliente <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="cliente_nombre" id="cuota_nombre"
                               pattern="[A-Za-záéíóúÁÉÍÓÚñÑüÜ\s]+"
                               title="Solo letras y espacios"
                               placeholder="Ej: Juan Carlos" required>
                        <div class="invalid-feedback">Solo se permiten letras y espacios.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">DNI del cliente</label>
                        <input type="text" class="form-control" name="cliente_dni" id="cuota_dni"
                               inputmode="numeric" pattern="[0-9]{1,8}" maxlength="8"
                               title="Solo números, máximo 8 dígitos"
                               placeholder="Ej: 30123456">
                        <div class="invalid-feedback">Solo números, máximo 8 dígitos.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto cobrado <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="monto_cobrado" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Número de cuota</label>
                        <input type="number" class="form-control" name="numero_cuota" min="1">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" name="observaciones" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-check-circle me-2"></i>Registrar Cobro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Gasto -->
<div class="modal fade" id="modalGasto" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Registrar Gasto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formGasto">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tipo de gasto <span class="text-danger">*</span></label>
                        <select class="form-select" name="tipo" required>
                            <option value="servicio">Servicio (luz, agua, etc.)</option>
                            <option value="proveedor">Pago a proveedor</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Concepto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="concepto" placeholder="Ej: Pago luz mes de noviembre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="monto" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Descripción detallada</label>
                        <textarea class="form-control" name="descripcion" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-2"></i>Registrar Gasto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function modalAbrirTurno() {
    new bootstrap.Modal(document.getElementById('modalAbrirTurno')).show();
}

function modalCerrarTurno() {
    new bootstrap.Modal(document.getElementById('modalCerrarTurno')).show();
}

function modalNuevaVenta() {
    new bootstrap.Modal(document.getElementById('modalNuevaVenta')).show();
}

function modalCobroCuota() {
    new bootstrap.Modal(document.getElementById('modalCobroCuota')).show();
}

function modalGasto() {
    new bootstrap.Modal(document.getElementById('modalGasto')).show();
}

// Calcular diferencia al cerrar turno
document.getElementById('efectivo_cierre')?.addEventListener('input', function() {
    const montoInicial = <?php echo $turno_actual['monto_inicial'] ?? 0; ?>;
    const efectivoVentas = <?php echo $turno_actual['efectivo_ventas'] ?? 0; ?>;
    const creditoVentas = <?php echo $turno_actual['credito_ventas'] ?? 0; ?>; // 1ra cuota es efectivo
    const cobroCuotas = <?php echo $turno_actual['cobro_cuotas_credito'] ?? 0; ?>;
    const gastos = <?php echo $turno_actual['gastos_dia'] ?? 0; ?>;
    
    const esperado = montoInicial + efectivoVentas + creditoVentas + cobroCuotas - gastos;
    const contado = parseFloat(this.value) || 0;
    const diferencia = contado - esperado;
    
    const divDiferencia = document.getElementById('diferencia_caja');
    divDiferencia.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning');
    
    if (Math.abs(diferencia) < 0.01) {
        divDiferencia.classList.add('alert-success');
        divDiferencia.innerHTML = '<i class="bi bi-check-circle me-2"></i><strong>Perfecto!</strong> La caja está cuadrada.';
    } else if (diferencia > 0) {
        divDiferencia.classList.add('alert-warning');
        divDiferencia.innerHTML = `<i class="bi bi-exclamation-triangle me-2"></i><strong>Sobrante:</strong> $${diferencia.toFixed(2)} más de lo esperado.`;
    } else {
        divDiferencia.classList.add('alert-danger');
        divDiferencia.innerHTML = `<i class="bi bi-x-circle me-2"></i><strong>Faltante:</strong> $${Math.abs(diferencia).toFixed(2)} menos de lo esperado.`;
    }
});

// Form handlers
document.getElementById('formAbrirTurno')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('ajax/abrir-turno.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            MC.alert('Turno abierto exitosamente', 'success');
            location.reload();
        } else {
            MC.alert(data.message || 'Error al abrir turno', 'danger');
        }
    });
});

document.getElementById('formCerrarTurno')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formEl = this;
    MC.confirm('¿Estás seguro de cerrar el turno? Esta acción no se puede deshacer.', function(ok) {
        if (!ok) return;
        const formData = new FormData(formEl);
        formData.append('turno_id', '<?php echo $turno_actual['id'] ?? ''; ?>');

        fetch('ajax/cerrar-turno.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalCerrarTurno')).hide();
                // Mostrar modal de exportación
                document.getElementById('turnoIdExport').value = data.turno_id || '<?php echo $turno_actual['id'] ?? ''; ?>';
                new bootstrap.Modal(document.getElementById('modalExportarCierre')).show();
            } else {
                MC.alert(data.message || 'Error al cerrar turno', 'danger');
            }
        });
    }, { tipo: 'danger', titulo: 'Cerrar turno', btnOk: 'Sí, cerrar' });
});

document.getElementById('formNuevaVenta')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('turno_id', '<?php echo $turno_actual['id'] ?? ''; ?>');

    fetch('ajax/registrar-venta.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalNuevaVenta')).hide();
            if (data.warning) {
                MC.alert('Venta registrada con advertencias:<br>' + data.warning, 'warning');
            } else {
                MC.alert('Venta registrada exitosamente', 'success');
            }
            location.reload();
        } else {
            MC.alert(data.message || 'Error al registrar venta', 'danger');
        }
    });
});

// Validaciones cobro cuota: nombre=solo letras, dni=solo números max 8
document.getElementById('cuota_nombre')?.addEventListener('input', function() {
    this.value = this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
    this.classList.toggle('is-invalid', !this.validity.valid && this.value.length > 0);
});
document.getElementById('cuota_dni')?.addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 8);
    this.classList.toggle('is-invalid', this.value.length > 8);
});

document.getElementById('formCobroCuota')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const nombre = document.getElementById('cuota_nombre');
    const dni    = document.getElementById('cuota_dni');
    if (nombre && !nombre.validity.valid) {
        nombre.classList.add('is-invalid');
        nombre.focus();
        return;
    }
    if (dni && dni.value && !/^[0-9]{1,8}$/.test(dni.value)) {
        dni.classList.add('is-invalid');
        dni.focus();
        return;
    }
    const formData = new FormData(this);
    formData.append('turno_id', '<?php echo $turno_actual['id'] ?? ''; ?>');

    fetch('ajax/registrar-cobro-cuota.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalCobroCuota')).hide();
            MC.alert('Cobro registrado exitosamente', 'success');
            location.reload();
        } else {
            MC.alert(data.message || 'Error al registrar cobro', 'danger');
        }
    });
});

document.getElementById('formGasto')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('turno_id', '<?php echo $turno_actual['id'] ?? ''; ?>');

    fetch('ajax/registrar-gasto.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalGasto')).hide();
            MC.alert('Gasto registrado exitosamente', 'success');
            location.reload();
        } else {
            MC.alert(data.message || 'Error al registrar gasto', 'danger');
        }
    });
});

function verHistorial() {
    window.location.href = 'historial-caja.php';
}
</script>

<!-- Modal: Exportar Cierre de Turno -->
<div class="modal fade" id="modalExportarCierre" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-check-circle me-2"></i>Turno Cerrado Exitosamente</h5>
            </div>
            <div class="modal-body text-center py-4">
                <input type="hidden" id="turnoIdExport" value="">
                <i class="bi bi-lock-fill fs-1 text-success mb-3 d-block"></i>
                <h5 class="mb-3">¿Deseas exportar el resumen de caja?</h5>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <button type="button" class="btn btn-outline-danger btn-lg"
                            onclick="exportarTurno('pdf')">
                        <i class="bi bi-file-pdf me-2"></i>Ver PDF (tira)
                    </button>
                    <button type="button" class="btn btn-outline-success btn-lg"
                            onclick="exportarTurno('excel')">
                        <i class="bi bi-file-earmark-excel me-2"></i>Exportar Excel (CSV)
                    </button>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" onclick="location.reload()">
                    <i class="bi bi-x-circle me-2"></i>Cerrar sin exportar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function exportarTurno(formato) {
    var turnoId = document.getElementById('turnoIdExport').value || '<?php echo $turno_actual['id'] ?? ''; ?>';
    if (formato === 'pdf') {
        window.open('cierre-turno-pdf.php?id=' + turnoId, '_blank');
    } else {
        window.location.href = 'ajax/exportar-turno-csv.php?id=' + turnoId;
    }
    setTimeout(function() { location.reload(); }, 500);
}
</script>

<?php include('includes/footer-gerente.php'); ?>
