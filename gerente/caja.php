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
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Ventas Registradas:</h6>
                            <ul class="list-unstyled">
                                <li>Efectivo: <strong>$<?php echo number_format($turno_actual['efectivo_ventas'] ?? 0, 2, ',', '.'); ?></strong></li>
                                <li>Tarjeta: <strong>$<?php echo number_format($turno_actual['tarjeta_ventas'] ?? 0, 2, ',', '.'); ?></strong></li>
                                <li>Transferencia: <strong>$<?php echo number_format($turno_actual['transferencia_ventas'] ?? 0, 2, ',', '.'); ?></strong></li>
                                <li>Go Cuotas: <strong>$<?php echo number_format($turno_actual['go_cuotas_ventas'] ?? 0, 2, ',', '.'); ?></strong></li>
                                <li>Crédito: <strong>$<?php echo number_format($turno_actual['credito_ventas'] ?? 0, 2, ',', '.'); ?></strong></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Total del Día:</h6>
                            <h3 class="text-success">$<?php echo number_format($turno_actual['venta_total_dia'] ?? 0, 2, ',', '.'); ?></h3>
                            <p class="text-muted mb-0">Pares vendidos: <?php echo $turno_actual['pares_vendidos'] ?? 0; ?></p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <label class="form-label">Efectivo contado en caja <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="efectivo_cierre" name="efectivo_cierre" min="0" step="0.01" required>
                        </div>
                        <small class="text-muted">Cuenta el efectivo físico en caja</small>
                    </div>
                    
                    <div id="diferencia_caja" class="alert d-none"></div>
                    
                    <div class="mb-0">
                        <label class="form-label">Notas de cierre (opcional)</label>
                        <textarea class="form-control" name="notas_cierre" rows="2" placeholder="Observaciones, faltantes, sobrantes, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-lock me-2"></i>Cerrar Turno
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Nueva Venta MEJORADO -->
<div class="modal fade" id="modalNuevaVenta" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-cart-plus me-2"></i>Registrar Venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevaVenta">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo de venta</label>
                            <select class="form-select" name="tipo_venta" required>
                                <option value="mostrador">Venta en Mostrador</option>
                                <option value="online">Venta Online (WhatsApp)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Método de pago <span class="text-danger">*</span></label>
                            <select class="form-select" name="metodo_pago" id="metodo_pago" required>
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="go_cuotas">Go Cuotas</option>
                                <option value="credito">Crédito (1ra cuota)</option>
                            </select>
                        </div>
                        
                        <!-- BUSCADOR DE PRODUCTOS MEJORADO -->
                        <div class="col-12">
                            <label class="form-label">Buscar Producto <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input type="text" 
                                       class="form-control" 
                                       id="buscarProducto" 
                                       placeholder="Empieza a escribir el nombre del producto..."
                                       autocomplete="off">
                                <input type="hidden" name="producto_id" id="producto_id">
                                <input type="hidden" name="producto_nombre" id="producto_nombre" required>
                                
                                <!-- Dropdown de resultados -->
                                <div id="resultadosProductos" class="list-group position-absolute w-100" style="z-index: 1000; max-height: 300px; overflow-y: auto; display: none;">
                                </div>
                            </div>
                            <small class="text-muted">O escribe manualmente si no está en el catálogo</small>
                        </div>
                        
                        <!-- Checkbox: Producto manual -->
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="productoManual">
                                <label class="form-check-label" for="productoManual">
                                    Producto no está en el catálogo (escribir manualmente)
                                </label>
                            </div>
                        </div>
                        
                        <!-- Campos que se muestran al seleccionar producto -->
                        <div id="camposProducto" style="display: none;" class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Talle</label>
                                            <select class="form-select" name="talle" id="talleProducto">
                                                <option value="">Seleccionar...</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Color</label>
                                            <select class="form-select" name="color" id="colorProducto">
                                                <option value="">Seleccionar...</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Stock Disponible</label>
                                            <input type="text" class="form-control" id="stockDisponible" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Cantidad <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="cantidad" id="cantidad" min="1" value="1" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Precio unitario <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="precio_unitario" id="precio_unitario" min="0" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Subtotal</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control fw-bold" id="subtotal" readonly>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" name="notas" rows="2"></textarea>
                        </div>
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
// AUTOCOMPLETADO DE PRODUCTOS
let timeoutBusqueda;
let productoSeleccionado = null;

document.getElementById('buscarProducto')?.addEventListener('input', function() {
    clearTimeout(timeoutBusqueda);
    const busqueda = this.value.trim();
    
    if (busqueda.length < 2) {
        document.getElementById('resultadosProductos').style.display = 'none';
        return;
    }
    
    timeoutBusqueda = setTimeout(() => {
        fetch(`ajax/buscar-productos.php?q=${encodeURIComponent(busqueda)}`)
            .then(r => r.json())
            .then(productos => {
                mostrarResultados(productos);
            });
    }, 300);
});

function mostrarResultados(productos) {
    const contenedor = document.getElementById('resultadosProductos');
    
    if (productos.length === 0) {
        contenedor.innerHTML = '<div class="list-group-item text-muted">No se encontraron productos</div>';
        contenedor.style.display = 'block';
        return;
    }
    
    let html = '';
    productos.forEach(p => {
        html += `
            <a href="#" class="list-group-item list-group-item-action" onclick="seleccionarProducto(${JSON.stringify(p).replace(/"/g, '&quot;')}); return false;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${p.nombre}</strong>
                        <br><small class="text-muted">Stock: ${p.stock} pares</small>
                    </div>
                    <strong class="text-success">$${Number(p.precio).toLocaleString('es-AR')}</strong>
                </div>
            </a>
        `;
    });
    
    contenedor.innerHTML = html;
    contenedor.style.display = 'block';
}

function seleccionarProducto(producto) {
    productoSeleccionado = producto;
    
    // Llenar campos
    document.getElementById('buscarProducto').value = producto.nombre;
    document.getElementById('producto_id').value = producto.id;
    document.getElementById('producto_nombre').value = producto.nombre;
    document.getElementById('precio_unitario').value = producto.precio;
    document.getElementById('stockDisponible').value = producto.stock + ' pares';
    
    // Llenar talles
    const selectTalle = document.getElementById('talleProducto');
    selectTalle.innerHTML = '<option value="">Seleccionar...</option>';
    if (producto.talles) {
        const talles = producto.talles.split(',');
        talles.forEach(t => {
            selectTalle.innerHTML += `<option value="${t.trim()}">${t.trim()}</option>`;
        });
    }
    
    // Llenar colores
    const selectColor = document.getElementById('colorProducto');
    selectColor.innerHTML = '<option value="">Seleccionar...</option>';
    if (producto.colores && producto.colores.length > 0) {
        producto.colores.forEach(c => {
            selectColor.innerHTML += `<option value="${c}">${c}</option>`;
        });
    }
    
    // Mostrar campos adicionales
    document.getElementById('camposProducto').style.display = 'block';
    document.getElementById('resultadosProductos').style.display = 'none';
    
    calcularSubtotal();
}

// Calcular subtotal automáticamente
document.getElementById('cantidad')?.addEventListener('input', calcularSubtotal);
document.getElementById('precio_unitario')?.addEventListener('input', calcularSubtotal);

function calcularSubtotal() {
    const cantidad = parseFloat(document.getElementById('cantidad').value) || 0;
    const precio = parseFloat(document.getElementById('precio_unitario').value) || 0;
    const subtotal = cantidad * precio;
    document.getElementById('subtotal').value = subtotal.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Producto manual
document.getElementById('productoManual')?.addEventListener('change', function() {
    if (this.checked) {
        // Habilitar escritura manual
        document.getElementById('buscarProducto').value = '';
        document.getElementById('buscarProducto').placeholder = 'Escribe el nombre del producto manualmente';
        document.getElementById('producto_id').value = '';
        document.getElementById('camposProducto').style.display = 'none';
        document.getElementById('precio_unitario').value = '';
        document.getElementById('precio_unitario').readOnly = false;
        
        // Remover el required del hidden y agregarlo al visible
        document.getElementById('producto_nombre').removeAttribute('required');
        document.getElementById('buscarProducto').setAttribute('required', 'required');
        
        // Al escribir, actualizar el hidden
        document.getElementById('buscarProducto').addEventListener('input', function() {
            document.getElementById('producto_nombre').value = this.value;
        });
    } else {
        document.getElementById('buscarProducto').placeholder = 'Empieza a escribir el nombre del producto...';
        document.getElementById('precio_unitario').readOnly = false;
        document.getElementById('buscarProducto').removeAttribute('required');
        document.getElementById('producto_nombre').setAttribute('required', 'required');
    }
});

// Cerrar resultados al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('#buscarProducto') && !e.target.closest('#resultadosProductos')) {
        document.getElementById('resultadosProductos').style.display = 'none';
    }
});

// Form handler actualizado
document.getElementById('formNuevaVenta')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('turno_id', '<?php echo $turno_actual['id'] ?? ''; ?>');
    
    // Validar stock si hay producto seleccionado
    if (productoSeleccionado && productoSeleccionado.stock > 0) {
        const cantidad = parseInt(formData.get('cantidad'));
        if (cantidad > productoSeleccionado.stock) {
            alert(`Stock insuficiente. Disponible: ${productoSeleccionado.stock} pares`);
            return;
        }
    }
    
    fetch('ajax/registrar-venta-mejorado.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalNuevaVenta')).hide();
            alert('Venta registrada exitosamente');
            location.reload();
        } else {
            alert(data.message || 'Error al registrar venta');
        }
    });
});

// Resetear al abrir modal
document.getElementById('modalNuevaVenta')?.addEventListener('show.bs.modal', function() {
    document.getElementById('formNuevaVenta').reset();
    document.getElementById('camposProducto').style.display = 'none';
    document.getElementById('resultadosProductos').style.display = 'none';
    document.getElementById('producto_id').value = '';
    document.getElementById('producto_nombre').value = '';
    document.getElementById('subtotal').value = '';
    productoSeleccionado = null;
    document.getElementById('productoManual').checked = false;
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
                        <input type="text" class="form-control" name="cliente_nombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">DNI del cliente</label>
                        <input type="text" class="form-control" name="cliente_dni">
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
            alert('Turno abierto exitosamente');
            location.reload();
        } else {
            alert(data.message || 'Error al abrir turno');
        }
    });
});

document.getElementById('formCerrarTurno')?.addEventListener('submit', function(e) {
    e.preventDefault();
    if (!confirm('¿Estás seguro de cerrar el turno? Esta acción no se puede deshacer.')) return;
    
    const formData = new FormData(this);
    formData.append('turno_id', '<?php echo $turno_actual['id'] ?? ''; ?>');
    
    fetch('ajax/cerrar-turno.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Turno cerrado exitosamente');
            location.reload();
        } else {
            alert(data.message || 'Error al cerrar turno');
        }
    });
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
            alert('Venta registrada exitosamente');
            location.reload();
        } else {
            alert(data.message || 'Error al registrar venta');
        }
    });
});

document.getElementById('formCobroCuota')?.addEventListener('submit', function(e) {
    e.preventDefault();
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
            alert('Cobro registrado exitosamente');
            location.reload();
        } else {
            alert(data.message || 'Error al registrar cobro');
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
            alert('Gasto registrado exitosamente');
            location.reload();
        } else {
            alert(data.message || 'Error al registrar gasto');
        }
    });
});

function verHistorial() {
    window.location.href = 'historial-caja.php';
}
</script>

<?php include('includes/footer-gerente.php'); ?>
