<?php
/**
 * GERENTE/PEDIDOS.PHP - GESTIÓN DE PEDIDOS DE SUCURSAL (GERENTE)
 * 
 * Funcionalidades:
 * - Ver pedidos de SU sucursal únicamente
 * - Confirmar sucursal correcta
 * - Aprobar pedidos
 * - Cambiar estado (preparando, enviado, entregado)
 * - Ver detalle completo
 */

require_once('../includes/config.php');
require_once('../includes/verificar-gerente.php');

$titulo_pagina = "Pedidos de Mi Sucursal";
$gerente_id = $_SESSION['usuario_id'];

// Obtener sucursal del gerente
$stmt_sucursal = mysqli_prepare($conn, "SELECT sucursal_id FROM usuarios WHERE id = ?");
mysqli_stmt_bind_param($stmt_sucursal, "i", $gerente_id);
mysqli_stmt_execute($stmt_sucursal);
$result_sucursal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_sucursal));
$sucursal_id = $result_sucursal['sucursal_id'];
mysqli_stmt_close($stmt_sucursal);

if (!$sucursal_id) {
    die("Error: Gerente sin sucursal asignada");
}

// Filtro por estado
$filtro_estado = $_GET['estado'] ?? 'todos';

// PROCESAR ACCIONES
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Cambiar estado del pedido
    if (isset($_POST['cambiar_estado'])) {
        $pedido_id = intval($_POST['pedido_id']);
        $nuevo_estado = $_POST['nuevo_estado'];
        
        $estados_validos = ['confirmado', 'preparando', 'enviado', 'entregado'];
        
        if (in_array($nuevo_estado, $estados_validos)) {
            $fecha_campo = null;
            
            switch ($nuevo_estado) {
                case 'confirmado':
                    $fecha_campo = 'fecha_confirmacion';
                    $query = "UPDATE pedidos SET estado = ?, $fecha_campo = NOW(), confirmado_por = ? WHERE id = ? AND sucursal_id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "siii", $nuevo_estado, $gerente_id, $pedido_id, $sucursal_id);
                    break;
                case 'enviado':
                    $fecha_campo = 'fecha_envio';
                    $query = "UPDATE pedidos SET estado = ?, $fecha_campo = NOW() WHERE id = ? AND sucursal_id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "sii", $nuevo_estado, $pedido_id, $sucursal_id);
                    break;
                case 'entregado':
                    $fecha_campo = 'fecha_entrega';
                    // Marcar como pagado automáticamente
                    $query = "UPDATE pedidos SET estado = ?, $fecha_campo = NOW(), estado_pago = 'pagado' WHERE id = ? AND sucursal_id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "sii", $nuevo_estado, $pedido_id, $sucursal_id);
                    break;
                default:
                    $query = "UPDATE pedidos SET estado = ? WHERE id = ? AND sucursal_id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "sii", $nuevo_estado, $pedido_id, $sucursal_id);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['mensaje_exito'] = 'Estado del pedido actualizado correctamente';
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    redirigir('pedidos.php?' . http_build_query($_GET));
}

// Obtener pedidos de la sucursal
$query = "SELECT p.*, 
          u.nombre AS cliente_nombre, u.apellido AS cliente_apellido, u.email AS cliente_email, u.telefono AS cliente_telefono,
          COUNT(dp.id) AS cantidad_productos,
          SUM(dp.cantidad) AS cantidad_items
          FROM pedidos p
          INNER JOIN usuarios u ON p.usuario_id = u.id
          LEFT JOIN detalle_pedidos dp ON p.id = dp.pedido_id
          WHERE p.sucursal_id = ?";

if ($filtro_estado != 'todos') {
    $query .= " AND p.estado = ?";
}

$query .= " GROUP BY p.id ORDER BY p.fecha_pedido DESC";

$stmt = mysqli_prepare($conn, $query);

if ($filtro_estado != 'todos') {
    mysqli_stmt_bind_param($stmt, "is", $sucursal_id, $filtro_estado);
} else {
    mysqli_stmt_bind_param($stmt, "i", $sucursal_id);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pedidos = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Estadísticas
$stats = ['pendientes' => 0, 'confirmados' => 0, 'en_preparacion' => 0, 'enviados' => 0, 'entregados' => 0];

foreach ($pedidos as $pedido) {
    switch ($pedido['estado']) {
        case 'pendiente':
            $stats['pendientes']++;
            break;
        case 'confirmado':
            $stats['confirmados']++;
            break;
        case 'preparando':
            $stats['en_preparacion']++;
            break;
        case 'enviado':
            $stats['enviados']++;
            break;
        case 'entregado':
            $stats['entregados']++;
            break;
    }
}

require_once('includes/header-gerente.php');
?>

<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-1">
                <i class="bi bi-box-seam me-2"></i>Pedidos de Mi Sucursal
            </h1>
            <p class="text-muted mb-0">Gestiona los pedidos asignados a tu sucursal</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver
        </a>
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
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-warning mb-0"><?php echo $stats['pendientes']; ?></h3>
                    <small class="text-muted">Pendientes</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-info mb-0"><?php echo $stats['confirmados']; ?></h3>
                    <small class="text-muted">Confirmados</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-primary mb-0"><?php echo $stats['en_preparacion']; ?></h3>
                    <small class="text-muted">En Preparación</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="fw-bold text-success mb-0"><?php echo $stats['enviados'] + $stats['entregados']; ?></h3>
                    <small class="text-muted">Enviados/Entregados</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="?estado=todos" class="btn btn-outline-primary <?php echo $filtro_estado == 'todos' ? 'active' : ''; ?>">
                    Todos (<?php echo count($pedidos); ?>)
                </a>
                <a href="?estado=pendiente" class="btn btn-outline-warning <?php echo $filtro_estado == 'pendiente' ? 'active' : ''; ?>">
                    Pendientes (<?php echo $stats['pendientes']; ?>)
                </a>
                <a href="?estado=confirmado" class="btn btn-outline-info <?php echo $filtro_estado == 'confirmado' ? 'active' : ''; ?>">
                    Confirmados (<?php echo $stats['confirmados']; ?>)
                </a>
                <a href="?estado=preparando" class="btn btn-outline-primary <?php echo $filtro_estado == 'preparando' ? 'active' : ''; ?>">
                    En Preparación (<?php echo $stats['en_preparacion']; ?>)
                </a>
                <a href="?estado=enviado" class="btn btn-outline-success <?php echo $filtro_estado == 'enviado' ? 'active' : ''; ?>">
                    Enviados (<?php echo $stats['enviados']; ?>)
                </a>
                <a href="?estado=entregado" class="btn btn-outline-success <?php echo $filtro_estado == 'entregado' ? 'active' : ''; ?>">
                    Entregados (<?php echo $stats['entregados']; ?>)
                </a>
            </div>
        </div>
    </div>
    
    <!-- Lista de pedidos -->
    <?php if (empty($pedidos)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <h5 class="mt-3">No hay pedidos</h5>
        </div>
    <?php else: ?>
        
        <div class="row">
            <?php foreach ($pedidos as $pedido): 
                // Obtener detalle
                $stmt_det = mysqli_prepare($conn, "SELECT * FROM detalle_pedidos WHERE pedido_id = ?");
                mysqli_stmt_bind_param($stmt_det, "i", $pedido['id']);
                mysqli_stmt_execute($stmt_det);
                $detalles = mysqli_fetch_all(mysqli_stmt_get_result($stmt_det), MYSQLI_ASSOC);
                mysqli_stmt_close($stmt_det);
                
                $badge_class = [
                    'pendiente' => 'warning',
                    'confirmado' => 'info',
                    'preparando' => 'primary',
                    'enviado' => 'success',
                    'entregado' => 'success'
                ];
                $class = $badge_class[$pedido['estado']] ?? 'secondary';
            ?>
                <div class="col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1">#<?php echo $pedido['numero_pedido']; ?></h5>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar me-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?>
                                    </small>
                                </div>
                                <span class="badge bg-<?php echo $class; ?>">
                                    <?php echo ucfirst($pedido['estado']); ?>
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['cliente_nombre'] . ' ' . $pedido['cliente_apellido']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($pedido['cliente_email']); ?></small>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Entrega:</strong> 
                                <span class="badge bg-secondary">
                                    <?php echo $pedido['tipo_entrega'] == 'envio' ? 'Envío' : 'Retiro'; ?>
                                </span>
                                <br>
                                <?php if ($pedido['tipo_entrega'] == 'envio' && $pedido['direccion_envio']): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($pedido['direccion_envio']); ?></small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Total:</strong> 
                                <span class="text-primary fs-5 fw-bold">$<?php echo number_format($pedido['total'], 0, ',', '.'); ?></span>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="button" 
                                        class="btn btn-outline-primary btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalPedido<?php echo $pedido['id']; ?>">
                                    <i class="bi bi-eye me-1"></i>Ver Detalle y Gestionar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal del pedido -->
                <div class="modal fade" id="modalPedido<?php echo $pedido['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title">Pedido #<?php echo $pedido['numero_pedido']; ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                
                                <!-- Info cliente y entrega -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6 class="fw-bold">Cliente</h6>
                                        <p class="mb-1"><?php echo htmlspecialchars($pedido['cliente_nombre'] . ' ' . $pedido['cliente_apellido']); ?></p>
                                        <p class="mb-1"><small><?php echo htmlspecialchars($pedido['cliente_email']); ?></small></p>
                                        <p class="mb-0"><small><?php echo htmlspecialchars($pedido['cliente_telefono'] ?? $pedido['telefono_contacto'] ?? 'N/A'); ?></small></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="fw-bold">Entrega</h6>
                                        <p class="mb-1"><strong>Tipo:</strong> <?php echo $pedido['tipo_entrega'] == 'envio' ? 'Envío' : 'Retiro'; ?></p>
                                        <?php if ($pedido['tipo_entrega'] == 'envio'): ?>
                                            <p class="mb-1"><small><?php echo htmlspecialchars($pedido['direccion_envio']); ?></small></p>
                                            <p class="mb-0"><small><?php echo $pedido['ciudad_envio']; ?>, <?php echo $pedido['provincia_envio']; ?></small></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Productos -->
                                <h6 class="fw-bold mb-2">Productos</h6>
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th>Cant.</th>
                                                <th class="text-end">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($detalles as $det): ?>
                                                <tr>
                                                    <td>
                                                        <?php echo htmlspecialchars($det['nombre_producto']); ?>
                                                        <?php if ($det['talle'] || $det['color']): ?>
                                                            <br><small class="text-muted">
                                                                <?php echo $det['talle'] ? 'T:' . $det['talle'] : ''; ?>
                                                                <?php echo ($det['talle'] && $det['color']) ? ' | ' : ''; ?>
                                                                <?php echo $det['color'] ? 'C:' . $det['color'] : ''; ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $det['cantidad']; ?></td>
                                                    <td class="text-end">$<?php echo number_format($det['subtotal'], 0, ',', '.'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="2" class="text-end"><strong>TOTAL:</strong></td>
                                                <td class="text-end"><h5 class="fw-bold text-primary mb-0">$<?php echo number_format($pedido['total'], 0, ',', '.'); ?></h5></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                
                                <!-- Cambiar estado -->
                                <?php if ($pedido['estado'] != 'entregado' && $pedido['estado'] != 'cancelado'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Cambiar Estado del Pedido</label>
                                            <div class="input-group">
                                                <select name="nuevo_estado" class="form-select" required>
                                                    <?php if ($pedido['estado'] == 'pendiente'): ?>
                                                        <option value="confirmado">✅ Confirmar Pedido</option>
                                                    <?php endif; ?>
                                                    <?php if (in_array($pedido['estado'], ['confirmado', 'preparando'])): ?>
                                                        <option value="preparando" <?php echo $pedido['estado'] == 'preparando' ? 'selected' : ''; ?>>📦 En Preparación</option>
                                                    <?php endif; ?>
                                                    <?php if (in_array($pedido['estado'], ['preparando', 'enviado'])): ?>
                                                        <option value="enviado" <?php echo $pedido['estado'] == 'enviado' ? 'selected' : ''; ?>>🚚 Marcar como Enviado/Listo</option>
                                                    <?php endif; ?>
                                                    <?php if ($pedido['estado'] == 'enviado'): ?>
                                                        <option value="entregado">✅ Marcar como Entregado</option>
                                                    <?php endif; ?>
                                                </select>
                                                <button type="submit" name="cambiar_estado" class="btn btn-primary">Actualizar</button>
                                            </div>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-success">
                                        <i class="bi bi-check-circle me-2"></i>
                                        Este pedido está <?php echo $pedido['estado'] == 'entregado' ? 'entregado' : 'cancelado'; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
    <?php endif; ?>
</div>

<?php require_once('includes/footer-gerente.php'); ?>
