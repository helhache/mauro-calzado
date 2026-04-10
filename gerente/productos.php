<?php
/**
 * GESTIÓN DE PRODUCTOS - Panel de Gerente
 * Ver y gestionar productos de su sucursal
 */

require_once('../includes/config.php');
require_once('../includes/verificar-gerente-admin.php');

$titulo_pagina = "Gestión de Productos";

// Obtener sucursal del gerente
$sucursal_id = obtenerSucursalGerente();

// Si es admin, mostrar selector de sucursal
if (esAdmin() && isset($_GET['sucursal'])) {
    $sucursal_id = intval($_GET['sucursal']);
}

// ============================================================================
// CONFIGURACIÓN DE PAGINACIÓN
// ============================================================================
$productos_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $productos_por_pagina;

// ============================================================================
// FILTROS Y BÚSQUEDA
// ============================================================================
$where_conditions = ["p.activo = 1", "ss.sucursal_id = ?"];
$params = [$sucursal_id];
$types = "i";

// Filtro por categoría (género)
if (isset($_GET['genero']) && !empty($_GET['genero'])) {
    $where_conditions[] = "p.genero = ?";
    $params[] = $_GET['genero'];
    $types .= "s";
}

// Filtro por stock bajo
if (isset($_GET['stock_bajo']) && $_GET['stock_bajo'] == '1') {
    $where_conditions[] = "ss.cantidad < ss.cantidad_minima";
}

// Búsqueda por nombre
if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $where_conditions[] = "(p.nombre LIKE ? OR p.marca LIKE ?)";
    $buscar = "%" . $_GET['buscar'] . "%";
    $params[] = $buscar;
    $params[] = $buscar;
    $types .= "ss";
}

$where_sql = implode(" AND ", $where_conditions);

// ============================================================================
// CONTAR TOTAL DE PRODUCTOS
// ============================================================================
$query_count = "SELECT COUNT(DISTINCT p.id) as total 
                FROM productos p
                INNER JOIN stock_sucursal ss ON p.id = ss.producto_id
                WHERE $where_sql";
$stmt_count = mysqli_prepare($conn, $query_count);
mysqli_stmt_bind_param($stmt_count, $types, ...$params);
mysqli_stmt_execute($stmt_count);
$result_count = mysqli_stmt_get_result($stmt_count);
$total_productos = mysqli_fetch_assoc($result_count)['total'];
$total_paginas = ceil($total_productos / $productos_por_pagina);

// ============================================================================
// OBTENER PRODUCTOS CON STOCK DE LA SUCURSAL
// ============================================================================
$query = "SELECT p.*, ss.cantidad as stock_sucursal, ss.cantidad_minima,
          c.nombre as categoria_nombre
          FROM productos p
          INNER JOIN stock_sucursal ss ON p.id = ss.producto_id
          LEFT JOIN categorias c ON p.categoria_id = c.id
          WHERE $where_sql
          ORDER BY p.nombre ASC
          LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $query);
$params[] = $productos_por_pagina;
$params[] = $offset;
$types .= "ii";
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result_productos = mysqli_stmt_get_result($stmt);

// ============================================================================
// ESTADÍSTICAS DE LA SUCURSAL
// ============================================================================
$stats = [];

// Total de productos en la sucursal
$query_stats = "SELECT COUNT(*) as total FROM stock_sucursal WHERE sucursal_id = ?";
$stmt_stats = mysqli_prepare($conn, $query_stats);
mysqli_stmt_bind_param($stmt_stats, 'i', $sucursal_id);
mysqli_stmt_execute($stmt_stats);
$stats['total'] = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_stats))['total'];

// Stock bajo
$query_stats = "SELECT COUNT(*) as total FROM stock_sucursal WHERE sucursal_id = ? AND cantidad < cantidad_minima";
$stmt_stats = mysqli_prepare($conn, $query_stats);
mysqli_stmt_bind_param($stmt_stats, 'i', $sucursal_id);
mysqli_stmt_execute($stmt_stats);
$stats['stock_bajo'] = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_stats))['total'];

// Promociones activas
$query_stats = "SELECT COUNT(DISTINCT p.id) as total 
                FROM productos p
                INNER JOIN stock_sucursal ss ON p.id = ss.producto_id
                WHERE ss.sucursal_id = ? AND p.en_promocion = 1 AND p.activo = 1";
$stmt_stats = mysqli_prepare($conn, $query_stats);
mysqli_stmt_bind_param($stmt_stats, 'i', $sucursal_id);
mysqli_stmt_execute($stmt_stats);
$stats['promocion'] = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_stats))['total'];

// Obtener nombre de la sucursal
$query_sucursal = "SELECT nombre, direccion FROM sucursales WHERE id = ?";
$stmt_sucursal = mysqli_prepare($conn, $query_sucursal);
mysqli_stmt_bind_param($stmt_sucursal, 'i', $sucursal_id);
mysqli_stmt_execute($stmt_sucursal);
$sucursal_info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_sucursal));

require_once('includes/header-gerente.php');
?>

<!-- Mensajes de éxito/error -->
<div id="mensaje-container"></div>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-2 fw-bold text-dark">
            <i class="bi bi-box-seam text-primary me-2"></i>Gestión de Productos
        </h1>
        <p class="text-muted mb-0">
            <i class="bi bi-shop me-1"></i>
            Sucursal: <strong><?php echo htmlspecialchars($sucursal_info['nombre']); ?></strong>
            <?php if ($sucursal_info['direccion']): ?>
                - <?php echo htmlspecialchars($sucursal_info['direccion']); ?>
            <?php endif; ?>
        </p>
    </div>
    
    <?php if (esAdmin()): ?>
        <div class="dropdown">
            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-shop me-2"></i>Cambiar Sucursal
            </button>
            <ul class="dropdown-menu">
                <?php
                $query_suc = "SELECT id, nombre FROM sucursales ORDER BY nombre";
                $result_suc = mysqli_query($conn, $query_suc);
                while ($suc = mysqli_fetch_assoc($result_suc)):
                ?>
                    <li>
                        <a class="dropdown-item <?php echo $suc['id'] == $sucursal_id ? 'active' : ''; ?>" 
                           href="?sucursal=<?php echo $suc['id']; ?>">
                            <?php echo htmlspecialchars($suc['nombre']); ?>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<!-- Cards de Estadísticas -->
<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="p-3 bg-primary bg-opacity-10 rounded">
                            <i class="bi bi-box-seam text-primary fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1 fw-normal small">Total Productos</h6>
                        <h3 class="mb-0 fw-bold"><?php echo number_format($stats['total']); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="p-3 bg-warning bg-opacity-10 rounded">
                            <i class="bi bi-exclamation-triangle text-warning fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1 fw-normal small">Stock Bajo</h6>
                        <h3 class="mb-0 fw-bold"><?php echo number_format($stats['stock_bajo']); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="p-3 bg-success bg-opacity-10 rounded">
                            <i class="bi bi-tag text-success fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1 fw-normal small">En Promoción</h6>
                        <h3 class="mb-0 fw-bold"><?php echo number_format($stats['promocion']); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros y Búsqueda -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" id="formFiltros" class="row g-3">
            <?php if (esAdmin() && isset($_GET['sucursal'])): ?>
                <input type="hidden" name="sucursal" value="<?php echo intval($_GET['sucursal']); ?>">
            <?php endif; ?>
            
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Buscar producto</label>
                <input type="text" class="form-control" name="buscar" placeholder="Nombre, marca..." value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Categoría</label>
                <select class="form-select" name="genero">
                    <option value="">Todas</option>
                    <option value="mujer" <?php echo (isset($_GET['genero']) && $_GET['genero'] == 'mujer') ? 'selected' : ''; ?>>Mujer</option>
                    <option value="hombre" <?php echo (isset($_GET['genero']) && $_GET['genero'] == 'hombre') ? 'selected' : ''; ?>>Hombre</option>
                    <option value="infantil" <?php echo (isset($_GET['genero']) && $_GET['genero'] == 'infantil') ? 'selected' : ''; ?>>Infantil</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Stock</label>
                <select class="form-select" name="stock_bajo">
                    <option value="">Todos</option>
                    <option value="1" <?php echo (isset($_GET['stock_bajo']) && $_GET['stock_bajo'] == '1') ? 'selected' : ''; ?>>Stock bajo</option>
                </select>
            </div>
            
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
                <a href="productos.php<?php echo esAdmin() && isset($_GET['sucursal']) ? '?sucursal='.$_GET['sucursal'] : ''; ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Productos -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if (mysqli_num_rows($result_productos) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">Imagen</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock Sucursal</th>
                            <th>Estado</th>
                            <th class="text-center" style="width: 200px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($producto = mysqli_fetch_assoc($result_productos)): ?>
                            <tr>
                                <td>
                                    <?php if ($producto['imagen']): ?>
                                        <img src="../img/productos/<?php echo htmlspecialchars($producto['imagen']); ?>" 
                                             alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                             class="img-thumbnail"
                                             style="width: 60px; height: 60px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center rounded" 
                                             style="width: 60px; height: 60px;">
                                            <i class="bi bi-image text-secondary"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($producto['marca']); ?></small>
                                    </div>
                                    <?php if ($producto['en_promocion']): ?>
                                        <span class="badge bg-danger mt-1">-<?php echo $producto['descuento_porcentaje']; ?>% OFF</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php
                                    $badge_genero = '';
                                    switch($producto['genero']) {
                                        case 'mujer': $badge_genero = 'bg-pink'; break;
                                        case 'hombre': $badge_genero = 'bg-info'; break;
                                        case 'infantil': $badge_genero = 'bg-purple'; break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_genero; ?>">
                                        <?php echo ucfirst($producto['genero']); ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <?php if ($producto['en_promocion']): ?>
                                        <div>
                                            <span class="text-decoration-line-through text-muted small">$<?php echo number_format($producto['precio'], 2); ?></span>
                                        </div>
                                        <div class="fw-bold text-danger">
                                            $<?php echo number_format($producto['precio'] * (1 - $producto['descuento_porcentaje']/100), 2); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="fw-bold">$<?php echo number_format($producto['precio'], 2); ?></div>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php
                                    $stock = $producto['stock_sucursal'];
                                    $stock_min = $producto['cantidad_minima'];
                                    $stock_class = 'text-success';
                                    $stock_icon = 'check-circle';
                                    
                                    if ($stock < $stock_min) {
                                        $stock_class = 'text-danger';
                                        $stock_icon = 'exclamation-triangle';
                                    } elseif ($stock < ($stock_min * 2)) {
                                        $stock_class = 'text-warning';
                                        $stock_icon = 'exclamation-circle';
                                    }
                                    ?>
                                    <div>
                                        <span class="fw-semibold <?php echo $stock_class; ?>">
                                            <i class="bi bi-<?php echo $stock_icon; ?> me-1"></i>
                                            <?php echo $stock; ?> unidades
                                        </span>
                                        <?php if ($stock < $stock_min): ?>
                                            <div><small class="text-danger">Mínimo: <?php echo $stock_min; ?></small></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" 
                                               <?php echo $producto['activo'] ? 'checked' : ''; ?>
                                               onchange="cambiarEstado(<?php echo $producto['id']; ?>, this.checked)">
                                    </div>
                                </td>
                                
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-outline-primary" 
                                                onclick="editarPrecio(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES); ?>', <?php echo $producto['precio']; ?>)" 
                                                title="Editar Precio">
                                            <i class="bi bi-currency-dollar"></i>
                                        </button>
                                        <button class="btn btn-outline-success" 
                                                onclick="actualizarStock(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES); ?>', <?php echo $stock; ?>)" 
                                                title="Actualizar Stock">
                                            <i class="bi bi-box"></i>
                                        </button>
                                        <button class="btn btn-outline-warning" 
                                                onclick="darBaja(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES); ?>', <?php echo $stock; ?>)" 
                                                title="Dar de Baja">
                                            <i class="bi bi-dash-circle"></i>
                                        </button>
                                        <button class="btn btn-outline-info" 
                                                onclick="activarPromocion(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES); ?>', <?php echo $producto['en_promocion']; ?>)" 
                                                title="Promoción">
                                            <i class="bi bi-tag"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <nav aria-label="Paginación" class="mt-4">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo isset($_GET['buscar']) ? '&buscar='.$_GET['buscar'] : ''; ?><?php echo isset($_GET['genero']) ? '&genero='.$_GET['genero'] : ''; ?><?php echo isset($_GET['stock_bajo']) ? '&stock_bajo='.$_GET['stock_bajo'] : ''; ?><?php echo esAdmin() && isset($_GET['sucursal']) ? '&sucursal='.$_GET['sucursal'] : ''; ?>">
                                Anterior
                            </a>
                        </li>
                        
                        <?php
                        $rango = 2;
                        $inicio = max(1, $pagina_actual - $rango);
                        $fin = min($total_paginas, $pagina_actual + $rango);
                        
                        for ($i = $inicio; $i <= $fin; $i++):
                        ?>
                            <li class="page-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo isset($_GET['buscar']) ? '&buscar='.$_GET['buscar'] : ''; ?><?php echo isset($_GET['genero']) ? '&genero='.$_GET['genero'] : ''; ?><?php echo isset($_GET['stock_bajo']) ? '&stock_bajo='.$_GET['stock_bajo'] : ''; ?><?php echo esAdmin() && isset($_GET['sucursal']) ? '&sucursal='.$_GET['sucursal'] : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo isset($_GET['buscar']) ? '&buscar='.$_GET['buscar'] : ''; ?><?php echo isset($_GET['genero']) ? '&genero='.$_GET['genero'] : ''; ?><?php echo isset($_GET['stock_bajo']) ? '&stock_bajo='.$_GET['stock_bajo'] : ''; ?><?php echo esAdmin() && isset($_GET['sucursal']) ? '&sucursal='.$_GET['sucursal'] : ''; ?>">
                                Siguiente
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted d-block mb-3"></i>
                <h5 class="text-muted">No se encontraron productos</h5>
                <p class="text-muted">Esta sucursal no tiene productos en stock actualmente</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Editar Precio -->
<div class="modal fade" id="modalEditarPrecio" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-currency-dollar me-2"></i>Editar Precio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarPrecio">
                <input type="hidden" id="precio_producto_id" name="producto_id">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        El administrador será notificado de este cambio de precio
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Producto</label>
                        <input type="text" class="form-control" id="precio_producto_nombre" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Precio actual</label>
                        <input type="text" class="form-control" id="precio_actual" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nuevo precio <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="precio_nuevo" name="precio_nuevo" step="0.01" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motivo del cambio</label>
                        <textarea class="form-control" id="precio_motivo" name="motivo" rows="2" placeholder="Opcional: explica por qué cambiaste el precio"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Actualizar Stock -->
<div class="modal fade" id="modalActualizarStock" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-box me-2"></i>Actualizar Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formActualizarStock">
                <input type="hidden" id="stock_producto_id" name="producto_id">
                <input type="hidden" id="stock_sucursal_id" name="sucursal_id" value="<?php echo $sucursal_id; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Producto</label>
                        <input type="text" class="form-control" id="stock_producto_nombre" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Stock actual</label>
                        <input type="text" class="form-control" id="stock_actual" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Operación</label>
                        <select class="form-select" id="stock_operacion" name="operacion" onchange="toggleStockOperacion()">
                            <option value="agregar">Agregar stock (recibí mercadería)</option>
                            <option value="reemplazar">Reemplazar stock (inventario)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" id="stock_label">Cantidad a agregar <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="stock_cantidad" name="cantidad" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nota</label>
                        <textarea class="form-control" id="stock_nota" name="nota" rows="2" placeholder="Opcional: notas sobre esta actualización"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Actualizar Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Dar de Baja -->
<div class="modal fade" id="modalDarBaja" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning bg-opacity-10">
                <h5 class="modal-title"><i class="bi bi-dash-circle me-2"></i>Dar de Baja Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formDarBaja">
                <input type="hidden" id="baja_producto_id" name="producto_id">
                <input type="hidden" id="baja_sucursal_id" name="sucursal_id" value="<?php echo $sucursal_id; ?>">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Esta acción restará stock y notificará al administrador
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Producto</label>
                        <input type="text" class="form-control" id="baja_producto_nombre" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Stock disponible</label>
                        <input type="text" class="form-control" id="baja_stock_actual" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cantidad a dar de baja <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="baja_cantidad" name="cantidad" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motivo <span class="text-danger">*</span></label>
                        <select class="form-select" id="baja_motivo" name="motivo" required>
                            <option value="">Seleccionar...</option>
                            <option value="mal_estado">Mal estado</option>
                            <option value="dañado">Dañado</option>
                            <option value="vencido">Vencido</option>
                            <option value="robo">Robo</option>
                            <option value="extravío">Extravío</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descripción detallada <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="baja_descripcion" name="descripcion" rows="3" placeholder="Explica en detalle por qué das de baja este producto" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Dar de Baja</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Activar Promoción -->
<div class="modal fade" id="modalPromocion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-tag me-2"></i>Gestionar Promoción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPromocion">
                <input type="hidden" id="promo_producto_id" name="producto_id">
                <input type="hidden" id="promo_estado_actual" name="estado_actual">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        El administrador será notificado de este cambio de promoción
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Producto</label>
                        <input type="text" class="form-control" id="promo_producto_nombre" readonly>
                    </div>
                    <div class="mb-3" id="promo_activar_section">
                        <label class="form-label fw-semibold">Descuento <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="promo_descuento" name="descuento_porcentaje" min="1" max="100">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motivo</label>
                        <textarea class="form-control" id="promo_motivo" name="motivo" rows="2" placeholder="Opcional: explica el motivo de la promoción"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnPromocion">Activar Promoción</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.bg-pink {
    background-color: #ec4899 !important;
    color: white;
}

.bg-purple {
    background-color: #8b5cf6 !important;
    color: white;
}
</style>

<?php require_once('includes/footer-gerente.php'); ?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../js/productos.js"></script>

<!-- Nota: El código JavaScript para productos gerente ya está en ../js/productos.js -->
<!--
<script>
// ============================================================================
// CAMBIAR ESTADO (ACTIVO/INACTIVO)
// ============================================================================
function cambiarEstado(id, activo) {
    $.ajax({
        url: 'ajax/cambiar-estado-producto.php',
        type: 'POST',
        data: { id: id, activo: activo ? 1 : 0 },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarMensaje(response.message, 'success');
            } else {
                mostrarMensaje(response.message, 'danger');
                location.reload();
            }
        },
        error: function() {
            mostrarMensaje('Error al cambiar el estado', 'danger');
            location.reload();
        }
    });
}

// ============================================================================
// EDITAR PRECIO
// ============================================================================
function editarPrecio(id, nombre, precio) {
    $('#precio_producto_id').val(id);
    $('#precio_producto_nombre').val(nombre);
    $('#precio_actual').val('$' + parseFloat(precio).toFixed(2));
    $('#precio_nuevo').val(precio);
    $('#precio_motivo').val('');
    $('#modalEditarPrecio').modal('show');
}

$('#formEditarPrecio').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: 'ajax/editar-precio-producto.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarMensaje(response.message, 'success');
                $('#modalEditarPrecio').modal('hide');
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarMensaje(response.message, 'danger');
            }
        },
        error: function() {
            mostrarMensaje('Error al actualizar el precio', 'danger');
        }
    });
});

// ============================================================================
// ACTUALIZAR STOCK
// ============================================================================
function actualizarStock(id, nombre, stock) {
    $('#stock_producto_id').val(id);
    $('#stock_producto_nombre').val(nombre);
    $('#stock_actual').val(stock + ' unidades');
    $('#stock_cantidad').val('');
    $('#stock_nota').val('');
    $('#stock_operacion').val('agregar');
    toggleStockOperacion();
    $('#modalActualizarStock').modal('show');
}

function toggleStockOperacion() {
    const operacion = $('#stock_operacion').val();
    if (operacion === 'agregar') {
        $('#stock_label').text('Cantidad a agregar *');
    } else {
        $('#stock_label').text('Nuevo stock total *');
    }
}

$('#formActualizarStock').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: 'ajax/actualizar-stock.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarMensaje(response.message, 'success');
                $('#modalActualizarStock').modal('hide');
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarMensaje(response.message, 'danger');
            }
        },
        error: function() {
            mostrarMensaje('Error al actualizar el stock', 'danger');
        }
    });
});

// ============================================================================
// DAR DE BAJA
// ============================================================================
function darBaja(id, nombre, stock) {
    $('#baja_producto_id').val(id);
    $('#baja_producto_nombre').val(nombre);
    $('#baja_stock_actual').val(stock + ' unidades');
    $('#baja_cantidad').val('');
    $('#baja_cantidad').attr('max', stock);
    $('#baja_motivo').val('');
    $('#baja_descripcion').val('');
    $('#modalDarBaja').modal('show');
}

$('#formDarBaja').on('submit', function(e) {
    e.preventDefault();
    
    const cantidad = parseInt($('#baja_cantidad').val());
    const stockActual = parseInt($('#baja_stock_actual').val());
    
    if (cantidad > stockActual) {
        mostrarMensaje('La cantidad no puede ser mayor al stock disponible', 'danger');
        return;
    }
    
    $.ajax({
        url: 'ajax/dar-baja-producto.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarMensaje(response.message, 'success');
                $('#modalDarBaja').modal('hide');
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarMensaje(response.message, 'danger');
            }
        },
        error: function() {
            mostrarMensaje('Error al dar de baja el producto', 'danger');
        }
    });
});

// ============================================================================
// ACTIVAR/DESACTIVAR PROMOCIÓN
// ============================================================================
function activarPromocion(id, nombre, enPromocion) {
    $('#promo_producto_id').val(id);
    $('#promo_producto_nombre').val(nombre);
    $('#promo_estado_actual').val(enPromocion);
    $('#promo_motivo').val('');
    
    if (enPromocion == 1) {
        // Ya está en promoción - desactivar
        $('#promo_activar_section').hide();
        $('#btnPromocion').text('Desactivar Promoción').removeClass('btn-primary').addClass('btn-secondary');
    } else {
        // Activar promoción
        $('#promo_activar_section').show();
        $('#promo_descuento').val('');
        $('#btnPromocion').text('Activar Promoción').removeClass('btn-secondary').addClass('btn-primary');
    }
    
    $('#modalPromocion').modal('show');
}

$('#formPromocion').on('submit', function(e) {
    e.preventDefault();
    
    const enPromocion = $('#promo_estado_actual').val();
    
    if (enPromocion == 0) {
        // Validar descuento
        const descuento = parseInt($('#promo_descuento').val());
        if (!descuento || descuento < 1 || descuento > 100) {
            mostrarMensaje('El descuento debe estar entre 1% y 100%', 'danger');
            return;
        }
    }
    
    $.ajax({
        url: 'ajax/activar-promocion.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarMensaje(response.message, 'success');
                $('#modalPromocion').modal('hide');
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarMensaje(response.message, 'danger');
            }
        },
        error: function() {
            mostrarMensaje('Error al gestionar la promoción', 'danger');
        }
    });
});

// ============================================================================
// FUNCIÓN PARA MOSTRAR MENSAJES
// ============================================================================
function mostrarMensaje(mensaje, tipo) {
    const iconos = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-circle',
        'info': 'info-circle'
    };
    
    const html = `
        <div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
            <i class="bi bi-${iconos[tipo]} me-2"></i>${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('#mensaje-container').html(html);
    $('html, body').animate({ scrollTop: 0 }, 300);
    
    setTimeout(() => {
        $('.alert').alert('close');
    }, 5000);
}
</script> -->
