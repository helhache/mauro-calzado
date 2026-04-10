<?php
/**
 * ADMIN/PEDIDOS.PHP - GESTIÓN DE PEDIDOS (ADMIN)
 * 
 * Funcionalidades:
 * - Ver TODOS los pedidos del sistema
 * - Filtrar por estado, sucursal, fecha, cliente
 * - Ver detalle completo de cada pedido
 * - Cambiar estado manualmente
 * - Cancelar pedidos
 * - Asignar a sucursal
 * - Descargar reportes
 */

require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

$titulo_pagina = "Gestión de Pedidos";

// Filtros
$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_sucursal = isset($_GET['sucursal']) ? intval($_GET['sucursal']) : 0;
$filtro_busqueda = $_GET['busqueda'] ?? '';

// PROCESAR ACCIONES
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Cambiar estado del pedido
    if (isset($_POST['cambiar_estado'])) {
        $pedido_id = intval($_POST['pedido_id']);
        $nuevo_estado = $_POST['nuevo_estado'];
        
        $estados_validos = ['pendiente', 'confirmado', 'preparando', 'enviado', 'entregado', 'cancelado'];
        
        if (in_array($nuevo_estado, $estados_validos)) {
            $fecha_campo = null;
            
            switch ($nuevo_estado) {
                case 'confirmado':
                    $fecha_campo = 'fecha_confirmacion';
                    break;
                case 'enviado':
                    $fecha_campo = 'fecha_envio';
                    break;
                case 'entregado':
                    $fecha_campo = 'fecha_entrega';
                    $stmt_pago = mysqli_prepare($conn, "UPDATE pedidos SET estado_pago = 'pagado' WHERE id = ?");
                    mysqli_stmt_bind_param($stmt_pago, "i", $pedido_id);
                    mysqli_stmt_execute($stmt_pago);
                    mysqli_stmt_close($stmt_pago);
                    break;
                case 'cancelado':
                    $fecha_campo = 'fecha_cancelacion';
                    break;
            }
            
            if ($fecha_campo) {
                $query = "UPDATE pedidos SET estado = ?, $fecha_campo = NOW() WHERE id = ?";
            } else {
                $query = "UPDATE pedidos SET estado = ? WHERE id = ?";
            }
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "si", $nuevo_estado, $pedido_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['mensaje_exito'] = 'Estado del pedido actualizado correctamente';
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    // Cancelar pedido
    if (isset($_POST['cancelar_pedido'])) {
        $pedido_id = intval($_POST['pedido_id']);
        $motivo = limpiarDato($_POST['motivo_cancelacion'] ?? '');
        
        $stmt = mysqli_prepare($conn,
            "UPDATE pedidos 
             SET estado = 'cancelado', fecha_cancelacion = NOW(), motivo_cancelacion = ?
             WHERE id = ?"
        );
        
        mysqli_stmt_bind_param($stmt, "si", $motivo, $pedido_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['mensaje_exito'] = 'Pedido cancelado correctamente';
        }
        
        mysqli_stmt_close($stmt);
    }
    
    // Asignar sucursal
    if (isset($_POST['asignar_sucursal'])) {
        $pedido_id = intval($_POST['pedido_id']);
        $sucursal_id = intval($_POST['sucursal_id']);
        
        $stmt = mysqli_prepare($conn, "UPDATE pedidos SET sucursal_id = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $sucursal_id, $pedido_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['mensaje_exito'] = 'Sucursal asignada correctamente';
        }
        
        mysqli_stmt_close($stmt);
    }
    
    redirigir('pedidos.php?' . http_build_query($_GET));
}

// Construir consulta con filtros
$query = "SELECT p.*, 
          u.nombre AS cliente_nombre, u.apellido AS cliente_apellido, u.email AS cliente_email, u.telefono AS cliente_telefono,
          s.nombre AS sucursal_nombre, s.ciudad AS sucursal_ciudad,
          COUNT(dp.id) AS cantidad_productos,
          SUM(dp.cantidad) AS cantidad_items
          FROM pedidos p
          INNER JOIN usuarios u ON p.usuario_id = u.id
          LEFT JOIN sucursales s ON p.sucursal_id = s.id
          LEFT JOIN detalle_pedidos dp ON p.id = dp.pedido_id
          WHERE 1=1";

$params = [];
$types = "";

if ($filtro_estado != 'todos') {
    $query .= " AND p.estado = ?";
    $params[] = $filtro_estado;
    $types .= "s";
}

if ($filtro_sucursal > 0) {
    $query .= " AND p.sucursal_id = ?";
    $params[] = $filtro_sucursal;
    $types .= "i";
}

if (!empty($filtro_busqueda)) {
    $query .= " AND (p.numero_pedido LIKE ? OR u.nombre LIKE ? OR u.apellido LIKE ? OR u.email LIKE ?)";
    $busqueda_param = "%$filtro_busqueda%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $types .= "ssss";
}

$query .= " GROUP BY p.id ORDER BY p.fecha_pedido DESC";

$stmt = mysqli_prepare($conn, $query);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pedidos = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Obtener sucursales para filtros
$sucursales = [];
$query_suc = "SELECT * FROM sucursales WHERE activo = 1 ORDER BY nombre";
$result_suc = mysqli_query($conn, $query_suc);
while ($row = mysqli_fetch_assoc($result_suc)) {
    $sucursales[] = $row;
}

// Estadísticas rápidas
$stats = [
    'total' => 0,
    'pendientes' => 0,
    'confirmados' => 0,
    'en_preparacion' => 0,
    'enviados' => 0,
    'entregados' => 0,
    'cancelados' => 0,
    'monto_total' => 0
];

$query_stats = "SELECT estado, COUNT(*) as cantidad, SUM(total) as monto FROM pedidos GROUP BY estado";
$result_stats = mysqli_query($conn, $query_stats);
while ($row = mysqli_fetch_assoc($result_stats)) {
    $stats['total'] += $row['cantidad'];
    $stats['monto_total'] += $row['monto'];
    
    switch ($row['estado']) {
        case 'pendiente':
            $stats['pendientes'] = $row['cantidad'];
            break;
        case 'confirmado':
            $stats['confirmados'] = $row['cantidad'];
            break;
        case 'preparando':
            $stats['en_preparacion'] = $row['cantidad'];
            break;
        case 'enviado':
            $stats['enviados'] = $row['cantidad'];
            break;
        case 'entregado':
            $stats['entregados'] = $row['cantidad'];
            break;
        case 'cancelado':
            $stats['cancelados'] = $row['cantidad'];
            break;
    }
}

require_once('includes/header-admin.php');
?>

<div class="container-fluid py-4">
    
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-1">
                <i class="bi bi-box-seam me-2"></i>Gestión de Pedidos
            </h1>
            <p class="text-muted mb-0">Administra todos los pedidos del sistema</p>
        </div>
        <div>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Volver al Dashboard
            </a>
        </div>
    </div>
    
    <?php if (isset($_SESSION['mensaje_exito'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?php echo $_SESSION['mensaje_exito']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje_exito']); ?>
    <?php endif; ?>
    
    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-2 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-primary mb-0"><?php echo $stats['total']; ?></h3>
                    <small class="text-muted">Total Pedidos</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-warning mb-0"><?php echo $stats['pendientes']; ?></h3>
                    <small class="text-muted">Pendientes</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-info mb-0"><?php echo $stats['confirmados']; ?></h3>
                    <small class="text-muted">Confirmados</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-success mb-0"><?php echo $stats['enviados']; ?></h3>
                    <small class="text-muted">Enviados</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-success mb-0"><?php echo $stats['entregados']; ?></h3>
                    <small class="text-muted">Entregados</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h5 class="fw-bold text-primary mb-0">$<?php echo number_format($stats['monto_total'], 0, ',', '.'); ?></h5>
                    <small class="text-muted">Monto Total</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtros y búsqueda -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <!-- Búsqueda -->
                <div class="col-md-4">
                    <label class="form-label">Buscar</label>
                    <input type="text" 
                           class="form-control" 
                           name="busqueda" 
                           placeholder="Número de pedido, cliente, email..."
                           value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                </div>
                
                <!-- Filtro por estado -->
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="todos" <?php echo $filtro_estado == 'todos' ? 'selected' : ''; ?>>Todos los Estados</option>
                        <option value="pendiente" <?php echo $filtro_estado == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="confirmado" <?php echo $filtro_estado == 'confirmado' ? 'selected' : ''; ?>>Confirmado</option>
                        <option value="preparando" <?php echo $filtro_estado == 'preparando' ? 'selected' : ''; ?>>En Preparación</option>
                        <option value="enviado" <?php echo $filtro_estado == 'enviado' ? 'selected' : ''; ?>>Enviado</option>
                        <option value="entregado" <?php echo $filtro_estado == 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                        <option value="cancelado" <?php echo $filtro_estado == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                
                <!-- Filtro por sucursal -->
                <div class="col-md-3">
                    <label class="form-label">Sucursal</label>
                    <select name="sucursal" class="form-select">
                        <option value="0">Todas las Sucursales</option>
                        <?php foreach ($sucursales as $sucursal): ?>
                            <option value="<?php echo $sucursal['id']; ?>" 
                                    <?php echo $filtro_sucursal == $sucursal['id'] ? 'selected' : ''; ?>>
                                <?php echo $sucursal['nombre']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i>Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tabla de pedidos -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0 fw-bold">
                Pedidos (<?php echo count($pedidos); ?>)
            </h5>
        </div>
        <div class="card-body p-0">
            
            <?php if (empty($pedidos)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <h5 class="mt-3">No se encontraron pedidos</h5>
                </div>
            <?php else: ?>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th>Sucursal</th>
                                <th>Fecha</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Pago</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $pedido): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo $pedido['numero_pedido']; ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($pedido['cliente_nombre'] . ' ' . $pedido['cliente_apellido']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($pedido['cliente_email']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($pedido['sucursal_nombre']): ?>
                                            <small>
                                                <i class="bi bi-shop text-primary me-1"></i>
                                                <?php echo $pedido['sucursal_nombre']; ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo date('d/m/Y', strtotime($pedido['fecha_pedido'])); ?></small><br>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($pedido['fecha_pedido'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $pedido['cantidad_items']; ?> items</span>
                                    </td>
                                    <td>
                                        <strong>$<?php echo number_format($pedido['total'], 0, ',', '.'); ?></strong>
                                    </td>
                                    <td>
                                        <?php
                                        $badge_class = [
                                            'pendiente' => 'warning',
                                            'confirmado' => 'info',
                                            'preparando' => 'primary',
                                            'enviado' => 'success',
                                            'entregado' => 'success',
                                            'cancelado' => 'danger'
                                        ];
                                        $class = $badge_class[$pedido['estado']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $class; ?>">
                                            <?php echo ucfirst($pedido['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($pedido['estado_pago'] == 'pagado'): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle me-1"></i>Pagado
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="bi bi-clock me-1"></i>Pendiente
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" 
                                                class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalDetalle<?php echo $pedido['id']; ?>">
                                            <i class="bi bi-eye"></i> Ver
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modales de detalle para cada pedido -->
<?php foreach ($pedidos as $pedido): 
    // Obtener detalle del pedido
    $stmt_det = mysqli_prepare($conn, "SELECT * FROM detalle_pedidos WHERE pedido_id = ?");
    mysqli_stmt_bind_param($stmt_det, "i", $pedido['id']);
    mysqli_stmt_execute($stmt_det);
    $detalles = mysqli_fetch_all(mysqli_stmt_get_result($stmt_det), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_det);
?>
    <div class="modal fade" id="modalDetalle<?php echo $pedido['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Detalle del Pedido #<?php echo $pedido['numero_pedido']; ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Información del cliente -->
                        <div class="col-md-6 mb-3">
                            <h6 class="fw-bold">Información del Cliente</h6>
                            <p class="mb-1"><strong>Nombre:</strong> <?php echo htmlspecialchars($pedido['cliente_nombre'] . ' ' . $pedido['cliente_apellido']); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($pedido['cliente_email']); ?></p>
                            <p class="mb-1"><strong>Teléfono:</strong> <?php echo htmlspecialchars($pedido['cliente_telefono'] ?? $pedido['telefono_contacto'] ?? 'No especificado'); ?></p>
                        </div>
                        
                        <!-- Información de entrega -->
                        <div class="col-md-6 mb-3">
                            <h6 class="fw-bold">Información de Entrega</h6>
                            <p class="mb-1"><strong>Tipo:</strong> <?php echo $pedido['tipo_entrega'] == 'envio' ? 'Envío a domicilio' : 'Retiro en sucursal'; ?></p>
                            <p class="mb-1"><strong>Sucursal:</strong> <?php echo $pedido['sucursal_nombre'] ?? 'Sin asignar'; ?></p>
                            <?php if ($pedido['tipo_entrega'] == 'envio' && $pedido['direccion_envio']): ?>
                                <p class="mb-1"><strong>Dirección:</strong> <?php echo htmlspecialchars($pedido['direccion_envio']); ?></p>
                                <p class="mb-1"><?php echo $pedido['ciudad_envio']; ?>, <?php echo $pedido['provincia_envio']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Productos -->
                    <h6 class="fw-bold mb-3">Productos del Pedido</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Precio Unit.</th>
                                    <th>Cantidad</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalles as $detalle): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($detalle['nombre_producto']); ?>
                                            <?php if ($detalle['talle'] || $detalle['color']): ?>
                                                <br><small class="text-muted">
                                                    <?php echo $detalle['talle'] ? 'Talle: ' . $detalle['talle'] : ''; ?>
                                                    <?php echo ($detalle['talle'] && $detalle['color']) ? ' | ' : ''; ?>
                                                    <?php echo $detalle['color'] ? 'Color: ' . $detalle['color'] : ''; ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>$<?php echo number_format($detalle['precio_unitario'], 0, ',', '.'); ?></td>
                                        <td><?php echo $detalle['cantidad']; ?></td>
                                        <td class="text-end">$<?php echo number_format($detalle['subtotal'], 0, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end"><strong>$<?php echo number_format($pedido['subtotal'], 0, ',', '.'); ?></strong></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Envío:</strong></td>
                                    <td class="text-end"><strong>$<?php echo number_format($pedido['costo_envio'], 0, ',', '.'); ?></strong></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                                    <td class="text-end"><h5 class="fw-bold text-primary mb-0">$<?php echo number_format($pedido['total'], 0, ',', '.'); ?></h5></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <?php if ($pedido['notas_cliente']): ?>
                        <div class="alert alert-info">
                            <strong>Notas del cliente:</strong><br>
                            <?php echo nl2br(htmlspecialchars($pedido['notas_cliente'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Acciones de admin -->
                    <div class="row g-3">
                        <!-- Cambiar estado -->
                        <div class="col-md-6">
                            <form method="POST">
                                <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                <label class="form-label fw-semibold">Cambiar Estado</label>
                                <div class="input-group">
                                    <select name="nuevo_estado" class="form-select" required>
                                        <option value="pendiente" <?php echo $pedido['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="confirmado" <?php echo $pedido['estado'] == 'confirmado' ? 'selected' : ''; ?>>Confirmado</option>
                                        <option value="preparando" <?php echo $pedido['estado'] == 'preparando' ? 'selected' : ''; ?>>En Preparación</option>
                                        <option value="enviado" <?php echo $pedido['estado'] == 'enviado' ? 'selected' : ''; ?>>Enviado</option>
                                        <option value="entregado" <?php echo $pedido['estado'] == 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                                    </select>
                                    <button type="submit" name="cambiar_estado" class="btn btn-primary">Actualizar</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Asignar sucursal -->
                        <?php if (!$pedido['sucursal_id']): ?>
                            <div class="col-md-6">
                                <form method="POST">
                                    <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                    <label class="form-label fw-semibold">Asignar Sucursal</label>
                                    <div class="input-group">
                                        <select name="sucursal_id" class="form-select" required>
                                            <option value="">Seleccionar...</option>
                                            <?php foreach ($sucursales as $suc): ?>
                                                <option value="<?php echo $suc['id']; ?>"><?php echo $suc['nombre']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="asignar_sucursal" class="btn btn-success">Asignar</button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Cancelar pedido -->
                        <?php if (!in_array($pedido['estado'], ['entregado', 'cancelado'])): ?>
                            <div class="col-12">
                                <button type="button" class="btn btn-danger" data-bs-toggle="collapse" data-bs-target="#cancelar<?php echo $pedido['id']; ?>">
                                    <i class="bi bi-x-circle me-1"></i>Cancelar Pedido
                                </button>
                                
                                <div class="collapse mt-3" id="cancelar<?php echo $pedido['id']; ?>">
                                    <form method="POST">
                                        <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                        <label class="form-label fw-semibold">Motivo de Cancelación</label>
                                        <textarea name="motivo_cancelacion" class="form-control mb-2" rows="2" required></textarea>
                                        <button type="submit" name="cancelar_pedido" class="btn btn-danger">
                                            Confirmar Cancelación
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php require_once('includes/footer-admin.php'); ?>
