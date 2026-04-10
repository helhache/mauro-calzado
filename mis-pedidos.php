<?php
/**
 * MIS-PEDIDOS.PHP - HISTORIAL DE PEDIDOS DEL CLIENTE
 * 
 * Funcionalidades:
 * - Ver todos los pedidos del cliente
 * - Filtrar por estado
 * - Ver detalle de cada pedido
 * - Seguimiento de estado
 * - Descargar comprobante (futuro)
 */

require_once('includes/config.php');
require_once('includes/verificar-cliente.php');

$titulo_pagina = "Mis Pedidos";
$usuario_id = $_SESSION['usuario_id'];

// Filtros
$filtro_estado = $_GET['estado'] ?? 'todos';

// Consulta de pedidos
$query = "SELECT p.*, s.nombre AS sucursal_nombre, s.ciudad AS sucursal_ciudad
          FROM pedidos p
          LEFT JOIN sucursales s ON p.sucursal_id = s.id
          WHERE p.usuario_id = ?";

if ($filtro_estado != 'todos') {
    $query .= " AND p.estado = ?";
}

$query .= " ORDER BY p.fecha_pedido DESC";

$stmt = mysqli_prepare($conn, $query);

if ($filtro_estado != 'todos') {
    mysqli_stmt_bind_param($stmt, "is", $usuario_id, $filtro_estado);
} else {
    mysqli_stmt_bind_param($stmt, "i", $usuario_id);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pedidos = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Función para obtener icono y clase según estado
function obtenerEstiloEstado($estado) {
    $estilos = [
        'pendiente' => ['icono' => 'clock-history', 'clase' => 'warning', 'texto' => 'Pendiente'],
        'confirmado' => ['icono' => 'check-circle', 'clase' => 'info', 'texto' => 'Confirmado'],
        'preparando' => ['icono' => 'box-seam', 'clase' => 'primary', 'texto' => 'En Preparación'],
        'enviado' => ['icono' => 'truck', 'clase' => 'success', 'texto' => 'Enviado'],
        'entregado' => ['icono' => 'check-circle-fill', 'clase' => 'success', 'texto' => 'Entregado'],
        'cancelado' => ['icono' => 'x-circle', 'clase' => 'danger', 'texto' => 'Cancelado']
    ];
    
    return $estilos[$estado] ?? ['icono' => 'circle', 'clase' => 'secondary', 'texto' => $estado];
}

require_once('includes/header.php');
?>

<div class="container py-5">
    
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-1">
                <i class="bi bi-bag-check me-2"></i>Mis Pedidos
            </h1>
            <p class="text-muted mb-0">Historial completo de tus compras</p>
        </div>
        <a href="mi-cuenta.php" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left me-2"></i>Volver a Mi Perfil
        </a>
    </div>
    
    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="btn-group" role="group">
                        <a href="?estado=todos" 
                           class="btn btn-outline-primary <?php echo $filtro_estado == 'todos' ? 'active' : ''; ?>">
                            Todos
                        </a>
                        <a href="?estado=pendiente" 
                           class="btn btn-outline-warning <?php echo $filtro_estado == 'pendiente' ? 'active' : ''; ?>">
                            Pendientes
                        </a>
                        <a href="?estado=confirmado" 
                           class="btn btn-outline-info <?php echo $filtro_estado == 'confirmado' ? 'active' : ''; ?>">
                            Confirmados
                        </a>
                        <a href="?estado=enviado" 
                           class="btn btn-outline-success <?php echo $filtro_estado == 'enviado' ? 'active' : ''; ?>">
                            Enviados
                        </a>
                        <a href="?estado=entregado" 
                           class="btn btn-outline-success <?php echo $filtro_estado == 'entregado' ? 'active' : ''; ?>">
                            Entregados
                        </a>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <span class="text-muted">
                        <strong><?php echo count($pedidos); ?></strong> pedido<?php echo count($pedidos) != 1 ? 's' : ''; ?> encontrado<?php echo count($pedidos) != 1 ? 's' : ''; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (empty($pedidos)): ?>
        <!-- Sin pedidos -->
        <div class="text-center py-5">
            <i class="bi bi-bag-x display-1 text-muted"></i>
            <h3 class="mt-4">No tienes pedidos aún</h3>
            <p class="text-muted">Comienza a explorar nuestro catálogo</p>
            <a href="index.php" class="btn btn-primary btn-lg mt-3">
                <i class="bi bi-shop me-2"></i>Ver Productos
            </a>
        </div>
    <?php else: ?>
        
        <!-- Lista de pedidos -->
        <?php foreach ($pedidos as $pedido): 
            $estilo = obtenerEstiloEstado($pedido['estado']);
            
            // Obtener detalle del pedido
            $stmt_detalle = mysqli_prepare($conn, 
                "SELECT dp.*, p.imagen AS producto_imagen
                 FROM detalle_pedidos dp
                 LEFT JOIN productos p ON dp.producto_id = p.id
                 WHERE dp.pedido_id = ?");
            mysqli_stmt_bind_param($stmt_detalle, "i", $pedido['id']);
            mysqli_stmt_execute($stmt_detalle);
            $result_detalle = mysqli_stmt_get_result($stmt_detalle);
            $detalles = mysqli_fetch_all($result_detalle, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt_detalle);
        ?>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="row">
                        
                        <!-- Columna izquierda: Info del pedido -->
                        <div class="col-lg-8">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1">
                                        Pedido #<?php echo $pedido['numero_pedido']; ?>
                                    </h5>
                                    <p class="text-muted mb-0">
                                        <i class="bi bi-calendar me-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?>
                                    </p>
                                </div>
                                <span class="badge bg-<?php echo $estilo['clase']; ?> fs-6">
                                    <i class="bi bi-<?php echo $estilo['icono']; ?> me-1"></i>
                                    <?php echo $estilo['texto']; ?>
                                </span>
                            </div>
                            
                            <!-- Productos del pedido -->
                            <div class="mb-3">
                                <h6 class="fw-bold mb-2">Productos:</h6>
                                <?php foreach ($detalles as $index => $detalle): ?>
                                    <?php if ($index < 3): // Mostrar máximo 3 productos ?>
                                        <div class="d-flex align-items-center mb-2">
                                            <?php if ($detalle['producto_imagen']): ?>
                                                <img src="img/productos/<?php echo $detalle['producto_imagen']; ?>" 
                                                     alt="<?php echo htmlspecialchars($detalle['nombre_producto']); ?>"
                                                     width="50" 
                                                     class="rounded me-2">
                                            <?php else: ?>
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center me-2" 
                                                     style="width: 50px; height: 50px;">
                                                    <i class="bi bi-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="flex-grow-1">
                                                <small>
                                                    <strong><?php echo htmlspecialchars($detalle['nombre_producto']); ?></strong>
                                                    <?php if ($detalle['talle'] || $detalle['color']): ?>
                                                        <span class="text-muted">
                                                            (<?php echo $detalle['talle'] ? 'Talle: ' . $detalle['talle'] : ''; ?>
                                                            <?php echo ($detalle['talle'] && $detalle['color']) ? ' | ' : ''; ?>
                                                            <?php echo $detalle['color'] ? 'Color: ' . $detalle['color'] : ''; ?>)
                                                        </span>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            
                                            <span class="text-muted">
                                                <small>x<?php echo $detalle['cantidad']; ?></small>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                                <?php if (count($detalles) > 3): ?>
                                    <small class="text-muted">
                                        + <?php echo count($detalles) - 3; ?> producto<?php echo (count($detalles) - 3) != 1 ? 's' : ''; ?> más
                                    </small>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Información de entrega -->
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Sucursal:</small>
                                    <small class="fw-semibold">
                                        <i class="bi bi-shop text-primary me-1"></i>
                                        <?php echo $pedido['sucursal_nombre']; ?>, <?php echo $pedido['sucursal_ciudad']; ?>
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Tipo de entrega:</small>
                                    <small class="fw-semibold">
                                        <i class="bi bi-<?php echo $pedido['tipo_entrega'] == 'envio' ? 'truck' : 'shop'; ?> text-success me-1"></i>
                                        <?php echo $pedido['tipo_entrega'] == 'envio' ? 'Envío a domicilio' : 'Retiro en sucursal'; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Columna derecha: Total y acciones -->
                        <div class="col-lg-4 border-start mt-3 mt-lg-0">
                            <div class="text-center mb-3">
                                <small class="text-muted d-block">Total del Pedido</small>
                                <h3 class="fw-bold text-primary mb-0">
                                    $<?php echo number_format($pedido['total'], 0, ',', '.'); ?>
                                </h3>
                                <small class="text-muted">
                                    <?php 
                                    $metodos = [
                                        'efectivo' => 'Efectivo',
                                        'transferencia' => 'Transferencia',
                                        'tarjeta' => 'Tarjeta',
                                        'mercadopago' => 'Mercado Pago'
                                    ];
                                    echo $metodos[$pedido['metodo_pago']] ?? $pedido['metodo_pago'];
                                    ?>
                                </small>
                            </div>
                            
                            <!-- Estado de pago -->
                            <div class="text-center mb-3">
                                <?php if ($pedido['estado_pago'] == 'pagado'): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Pagado
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-clock me-1"></i>Pago Pendiente
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Botones de acción -->
                            <div class="d-grid gap-2">
                                <button type="button" 
                                        class="btn btn-outline-primary btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalDetalle<?php echo $pedido['id']; ?>">
                                    <i class="bi bi-eye me-1"></i>Ver Detalle
                                </button>
                                
                                <?php if ($pedido['estado'] == 'pendiente' || $pedido['estado'] == 'confirmado'): ?>
                                    <a href="mensajes-internos.php?nuevo=1&pedido_id=<?php echo $pedido['id']; ?>" 
                                       class="btn btn-outline-warning btn-sm">
                                        <i class="bi bi-chat-dots me-1"></i>Consultar
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($pedido['estado'] == 'entregado'): ?>
                                    <a href="producto.php?id=<?php echo $detalles[0]['producto_id'] ?? 0; ?>" 
                                       class="btn btn-outline-success btn-sm">
                                        <i class="bi bi-star me-1"></i>Calificar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Barra de progreso del pedido -->
                <?php if ($pedido['estado'] != 'cancelado'): ?>
                    <div class="card-footer bg-light">
                        <div class="row text-center">
                            <?php
                            $estados_flujo = ['pendiente', 'confirmado', 'preparando', 'enviado', 'entregado'];
                            $estado_actual_index = array_search($pedido['estado'], $estados_flujo);
                            
                            foreach ($estados_flujo as $index => $estado_flujo):
                                $completado = ($index <= $estado_actual_index);
                                $activo = ($index == $estado_actual_index);
                            ?>
                                <div class="col">
                                    <div class="position-relative">
                                        <?php if ($index < count($estados_flujo) - 1): ?>
                                            <div class="position-absolute top-50 start-50 w-100 translate-middle-y" 
                                                 style="height: 2px; background: <?php echo $completado ? '#0d6efd' : '#dee2e6'; ?>; z-index: 0;"></div>
                                        <?php endif; ?>
                                        
                                        <div class="position-relative" style="z-index: 1;">
                                            <div class="rounded-circle mx-auto mb-1 d-flex align-items-center justify-content-center" 
                                                 style="width: 30px; height: 30px; background: <?php echo $completado ? '#0d6efd' : '#dee2e6'; ?>;">
                                                <?php if ($completado): ?>
                                                    <i class="bi bi-check text-white"></i>
                                                <?php endif; ?>
                                            </div>
                                            <small class="<?php echo $activo ? 'fw-bold text-primary' : 'text-muted'; ?>">
                                                <?php echo ucfirst($estado_flujo); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Modal detalle del pedido -->
            <div class="modal fade" id="modalDetalle<?php echo $pedido['id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Detalle del Pedido #<?php echo $pedido['numero_pedido']; ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            
                            <!-- Información general -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Información del Pedido</h6>
                                    <p class="mb-1"><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></p>
                                    <p class="mb-1"><strong>Estado:</strong> 
                                        <span class="badge bg-<?php echo $estilo['clase']; ?>">
                                            <?php echo $estilo['texto']; ?>
                                        </span>
                                    </p>
                                    <p class="mb-1"><strong>Método de pago:</strong> <?php echo $metodos[$pedido['metodo_pago']] ?? $pedido['metodo_pago']; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Entrega</h6>
                                    <p class="mb-1"><strong>Tipo:</strong> <?php echo $pedido['tipo_entrega'] == 'envio' ? 'Envío a domicilio' : 'Retiro en sucursal'; ?></p>
                                    <p class="mb-1"><strong>Sucursal:</strong> <?php echo $pedido['sucursal_nombre']; ?></p>
                                    <?php if ($pedido['tipo_entrega'] == 'envio' && $pedido['direccion_envio']): ?>
                                        <p class="mb-1"><strong>Dirección:</strong> <?php echo htmlspecialchars($pedido['direccion_envio']); ?></p>
                                        <p class="mb-1"><?php echo $pedido['ciudad_envio']; ?>, <?php echo $pedido['provincia_envio']; ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Productos -->
                            <h6 class="fw-bold mb-3">Productos</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Precio</th>
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
                                            <td colspan="3" class="text-end"><strong>Costo de Envío:</strong></td>
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
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php endforeach; ?>
        
    <?php endif; ?>
    
</div>

<?php require_once('includes/footer.php'); ?>
