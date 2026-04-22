<?php
/**
 * GESTIÓN DE PRODUCTOS - Panel de Administración
 * CRUD completo: Crear, Leer, Actualizar, Eliminar productos
 */

require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

$titulo_pagina = "Gestión de Productos";

// ============================================================================
// CONFIGURACIÓN DE PAGINACIÓN
// ============================================================================
$productos_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $productos_por_pagina;

// ============================================================================
// FILTROS Y BÚSQUEDA
// ============================================================================
$where_conditions = ["p.activo = 1"]; // Por defecto mostrar solo activos
$params = [];
$types = "";

// Filtro por categoría (género)
if (isset($_GET['genero']) && !empty($_GET['genero'])) {
    $where_conditions[] = "p.genero = ?";
    $params[] = $_GET['genero'];
    $types .= "s";
}

// Filtro por estado
if (isset($_GET['estado'])) {
    if ($_GET['estado'] === 'inactivo') {
        $where_conditions = ["p.activo = 0"]; // Mostrar solo inactivos
    } elseif ($_GET['estado'] === 'todos') {
        $where_conditions = ["1=1"]; // Mostrar todos
    }
}

// Filtro por stock bajo
if (isset($_GET['stock_bajo']) && $_GET['stock_bajo'] == '1') {
    $where_conditions[] = "p.stock < 5";
}

// Búsqueda por nombre
if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $where_conditions[] = "(p.nombre LIKE ? OR p.marca LIKE ? OR p.descripcion LIKE ?)";
    $buscar = "%" . $_GET['buscar'] . "%";
    $params[] = $buscar;
    $params[] = $buscar;
    $params[] = $buscar;
    $types .= "sss";
}

$where_sql = implode(" AND ", $where_conditions);

// ============================================================================
// CONTAR TOTAL DE PRODUCTOS (para paginación)
// ============================================================================
$query_count = "SELECT COUNT(*) as total FROM productos p WHERE $where_sql";
$stmt_count = mysqli_prepare($conn, $query_count);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_count, $types, ...$params);
}

mysqli_stmt_execute($stmt_count);
$result_count = mysqli_stmt_get_result($stmt_count);
$total_productos = mysqli_fetch_assoc($result_count)['total'];
$total_paginas = ceil($total_productos / $productos_por_pagina);

// ============================================================================
// OBTENER PRODUCTOS CON PAGINACIÓN
// ============================================================================
$query = "SELECT p.*, c.nombre as categoria_nombre
          FROM productos p
          LEFT JOIN categorias c ON p.categoria_id = c.id
          WHERE $where_sql
          ORDER BY p.fecha_creacion DESC
          LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $query);

// Agregar límite y offset a los parámetros
$params[] = $productos_por_pagina;
$params[] = $offset;
$types .= "ii";

mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result_productos = mysqli_stmt_get_result($stmt);

// ============================================================================
// ESTADÍSTICAS RÁPIDAS
// ============================================================================
$stats = [];
$stats['total'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM productos WHERE activo = 1"))['total'];
$stats['stock_bajo'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM productos WHERE stock < 5 AND activo = 1"))['total'];
$stats['promocion'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM productos WHERE en_promocion = 1 AND activo = 1"))['total'];
$stats['inactivos'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM productos WHERE activo = 0"))['total'];

require_once('includes/header-admin.php');
?>

<!-- Mensajes de éxito/error -->
<div id="mensaje-container"></div>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-2 fw-bold text-dark">
            <i class="bi bi-box-seam text-primary me-2"></i>Gestión de Productos
        </h1>
        <p class="text-muted mb-0">Administra el catálogo completo de productos</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProducto" onclick="abrirModalCrear()">
        <i class="bi bi-plus-circle me-2"></i>Nuevo Producto
    </button>
</div>

<!-- Cards de Estadísticas -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="p-3 bg-primary bg-opacity-10 rounded">
                            <i class="bi bi-box-seam text-primary fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1 fw-normal small">Total Activos</h6>
                        <h3 class="mb-0 fw-bold"><?php echo number_format($stats['total']); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
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
    
    <div class="col-xl-3 col-md-6">
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
    
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="p-3 bg-danger bg-opacity-10 rounded">
                            <i class="bi bi-x-circle text-danger fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1 fw-normal small">Inactivos</h6>
                        <h3 class="mb-0 fw-bold"><?php echo number_format($stats['inactivos']); ?></h3>
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
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Buscar producto</label>
                <input type="text" class="form-control" name="buscar" placeholder="Nombre, marca..." value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Categoría</label>
                <select class="form-select" name="genero">
                    <option value="">Todas</option>
                    <option value="mujer" <?php echo (isset($_GET['genero']) && $_GET['genero'] == 'mujer') ? 'selected' : ''; ?>>Mujer</option>
                    <option value="hombre" <?php echo (isset($_GET['genero']) && $_GET['genero'] == 'hombre') ? 'selected' : ''; ?>>Hombre</option>
                    <option value="infantil" <?php echo (isset($_GET['genero']) && $_GET['genero'] == 'infantil') ? 'selected' : ''; ?>>Infantil</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Estado</label>
                <select class="form-select" name="estado">
                    <option value="activo" <?php echo (!isset($_GET['estado']) || $_GET['estado'] == 'activo') ? 'selected' : ''; ?>>Activos</option>
                    <option value="inactivo" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'inactivo') ? 'selected' : ''; ?>>Inactivos</option>
                    <option value="todos" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'todos') ? 'selected' : ''; ?>>Todos</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Stock</label>
                <select class="form-select" name="stock_bajo">
                    <option value="">Todos</option>
                    <option value="1" <?php echo (isset($_GET['stock_bajo']) && $_GET['stock_bajo'] == '1') ? 'selected' : ''; ?>>Stock bajo (&lt;5)</option>
                </select>
            </div>
            
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
                <a href="productos.php" class="btn btn-outline-secondary">
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
                            <th>Stock</th>
                            <th>Colores</th>
                            <th>Estado</th>
                            <th class="text-center" style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($producto = mysqli_fetch_assoc($result_productos)): 
                            // Decodificar colores JSON
                            $colores_array = json_decode($producto['colores'], true);
                            if (!$colores_array) {
                                // Si no es JSON válido, intentar convertir formato antiguo
                                $colores_array = [];
                            }
                            $stock_total = array_sum($colores_array);
                        ?>
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
                                    <?php if ($producto['destacado']): ?>
                                        <span class="badge bg-warning mt-1"><i class="bi bi-star-fill"></i> Destacado</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php
                                    $badge_genero = '';
                                    switch($producto['genero']) {
                                        case 'mujer':
                                            $badge_genero = 'bg-pink';
                                            break;
                                        case 'hombre':
                                            $badge_genero = 'bg-info';
                                            break;
                                        case 'infantil':
                                            $badge_genero = 'bg-purple';
                                            break;
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
                                    $stock_class = 'text-success';
                                    if ($stock_total < 5) $stock_class = 'text-danger';
                                    elseif ($stock_total < 20) $stock_class = 'text-warning';
                                    ?>
                                    <span class="fw-semibold <?php echo $stock_class; ?>">
                                        <?php echo $stock_total; ?> unidades
                                    </span>
                                </td>
                                
                                <td>
                                    <?php if (!empty($colores_array)): ?>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <?php 
                                            $count = 0;
                                            foreach ($colores_array as $color => $cantidad):
                                                if ($count < 3): 
                                            ?>
                                                <span class="badge bg-secondary" title="<?php echo htmlspecialchars($color); ?>: <?php echo $cantidad; ?> unidades">
                                                    <?php echo htmlspecialchars($color); ?>
                                                </span>
                                            <?php 
                                                $count++;
                                                endif;
                                            endforeach; 
                                            if (count($colores_array) > 3):
                                            ?>
                                                <span class="badge bg-light text-dark">+<?php echo count($colores_array) - 3; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">Sin colores</span>
                                    <?php endif; ?>
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
                                        <button class="btn btn-outline-primary" onclick="editarProducto(<?php echo $producto['id']; ?>)"
                                                title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-success" onclick="abrirGaleria(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars(addslashes($producto['nombre'])); ?>')"
                                                title="Galería de imágenes">
                                            <i class="bi bi-images"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="eliminarProducto(<?php echo $producto['id']; ?>)"
                                                title="Eliminar">
                                            <i class="bi bi-trash"></i>
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
                        <!-- Botón Anterior -->
                        <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo isset($_GET['buscar']) ? '&buscar='.$_GET['buscar'] : ''; ?><?php echo isset($_GET['genero']) ? '&genero='.$_GET['genero'] : ''; ?><?php echo isset($_GET['estado']) ? '&estado='.$_GET['estado'] : ''; ?><?php echo isset($_GET['stock_bajo']) ? '&stock_bajo='.$_GET['stock_bajo'] : ''; ?>">
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
                                <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo isset($_GET['buscar']) ? '&buscar='.$_GET['buscar'] : ''; ?><?php echo isset($_GET['genero']) ? '&genero='.$_GET['genero'] : ''; ?><?php echo isset($_GET['estado']) ? '&estado='.$_GET['estado'] : ''; ?><?php echo isset($_GET['stock_bajo']) ? '&stock_bajo='.$_GET['stock_bajo'] : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- Botón Siguiente -->
                        <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo isset($_GET['buscar']) ? '&buscar='.$_GET['buscar'] : ''; ?><?php echo isset($_GET['genero']) ? '&genero='.$_GET['genero'] : ''; ?><?php echo isset($_GET['estado']) ? '&estado='.$_GET['estado'] : ''; ?><?php echo isset($_GET['stock_bajo']) ? '&stock_bajo='.$_GET['stock_bajo'] : ''; ?>">
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
                <p class="text-muted">Intenta cambiar los filtros o crear un nuevo producto</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Crear/Editar Producto -->
<div class="modal fade" id="modalProducto" tabindex="-1" aria-labelledby="modalProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalProductoLabel">
                    <i class="bi bi-box-seam me-2"></i><span id="tituloModal">Nuevo Producto</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="formProducto" enctype="multipart/form-data">
                <input type="hidden" id="producto_id" name="producto_id" value="">
                <input type="hidden" id="imagen_actual" name="imagen_actual" value="">
                
                <div class="modal-body">
                    <div class="row">
                        <!-- Columna izquierda: Formulario -->
                        <div class="col-md-8">
                            <div class="row g-3">
                                <!-- Nombre -->
                                <div class="col-12">
                                    <label for="nombre" class="form-label fw-semibold">Nombre del producto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                                </div>
                                
                                <!-- Descripción -->
                                <div class="col-12">
                                    <label for="descripcion" class="form-label fw-semibold">Descripción</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                                </div>
                                
                                <!-- Precio y Categoría -->
                                <div class="col-md-6">
                                    <label for="precio" class="form-label fw-semibold">Precio <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="precio" name="precio" step="0.01" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="genero" class="form-label fw-semibold">Categoría <span class="text-danger">*</span></label>
                                    <select class="form-select" id="genero" name="genero" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="mujer">Mujer</option>
                                        <option value="hombre">Hombre</option>
                                        <option value="infantil">Infantil</option>
                                    </select>
                                </div>
                                
                                <!-- Marca y Material -->
                                <div class="col-md-6">
                                    <label for="marca" class="form-label fw-semibold">Marca</label>
                                    <input type="text" class="form-control" id="marca" name="marca">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="material" class="form-label fw-semibold">Material</label>
                                    <input type="text" class="form-control" id="material" name="material">
                                </div>
                                
                                <!-- Colores con stock -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Colores y Stock</label>
                                    <div id="colores-container">
                                        <div class="alert alert-info small mb-3">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Selecciona los colores disponibles y asigna el stock de cada uno
                                        </div>
                                        <div id="colores-list" class="row g-2 mb-3"></div>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="agregarColorPersonalizado()">
                                            <i class="bi bi-plus-circle me-1"></i>Agregar color personalizado
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Talles -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Talles disponibles</label>
                                    <div id="talles-container" class="d-flex flex-wrap gap-2">
                                        <!-- Se generarán dinámicamente -->
                                    </div>
                                </div>
                                
                                <!-- Imagen -->
                                <div class="col-12">
                                    <label for="imagen" class="form-label fw-semibold">Imagen del producto</label>
                                    <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*" onchange="previsualizarImagen(this)">
                                    <small class="text-muted">Formatos: JPG, PNG, WEBP. Tamaño máximo: 2MB</small>
                                </div>
                                
                                <!-- Switches -->
                                <div class="col-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="destacado" name="destacado">
                                        <label class="form-check-label fw-semibold" for="destacado">
                                            <i class="bi bi-star text-warning me-1"></i>Producto destacado
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="en_promocion" name="en_promocion" onchange="toggleDescuento()">
                                        <label class="form-check-label fw-semibold" for="en_promocion">
                                            <i class="bi bi-tag text-danger me-1"></i>En promoción
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
                                        <label class="form-check-label fw-semibold" for="activo">
                                            <i class="bi bi-check-circle text-success me-1"></i>Producto activo
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Descuento (solo si está en promoción) -->
                                <div class="col-12" id="descuento-container" style="display: none;">
                                    <label for="descuento_porcentaje" class="form-label fw-semibold">Porcentaje de descuento</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="descuento_porcentaje" name="descuento_porcentaje" min="0" max="100" value="0">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Columna derecha: Preview -->
                        <div class="col-md-4">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-eye me-2"></i>Vista Previa</h6>
                                </div>
                                <div class="card-body">
                                    <div id="preview-producto" class="text-center">
                                        <!-- Preview de la tarjeta del producto -->
                                        <div class="card shadow-sm">
                                            <div id="preview-imagen" class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                                <i class="bi bi-image text-secondary fs-1"></i>
                                            </div>
                                            <div class="card-body">
                                                <h6 id="preview-nombre" class="card-title fw-bold mb-2">Nombre del producto</h6>
                                                <p id="preview-marca" class="text-muted small mb-2">Marca</p>
                                                <div id="preview-precio" class="mb-2">
                                                    <span class="h5 fw-bold text-primary">$0.00</span>
                                                </div>
                                                <div id="preview-badges" class="mb-2"></div>
                                                <div id="preview-colores" class="d-flex gap-1 flex-wrap mb-2"></div>
                                                <button class="btn btn-primary btn-sm w-100" disabled>Agregar al carrito</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        <i class="bi bi-save me-2"></i>Guardar Producto
                    </button>
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

/* Checkbox de colores */
.color-checkbox {
    display: none;
}

.color-checkbox + label {
    cursor: pointer;
    padding: 8px 12px;
    border: 2px solid #dee2e6;
    border-radius: 5px;
    transition: all 0.2s;
}

.color-checkbox:checked + label {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
}

/* Checkbox de talles */
.talle-checkbox {
    display: none;
}

.talle-checkbox + label {
    cursor: pointer;
    padding: 5px 10px;
    border: 1px solid #dee2e6;
    border-radius: 3px;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.talle-checkbox:checked + label {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
}
</style>

<?php require_once('includes/footer-admin.php'); ?>

<!-- Modal Galería de Imágenes -->
<div class="modal fade" id="modalGaleria" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-images me-2"></i>Galería de imágenes — <span id="galeria-nombre-producto"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="alerta-galeria"></div>

                <!-- Imágenes actuales -->
                <div class="mb-4">
                    <h6 class="fw-semibold mb-3">Imágenes adicionales actuales</h6>
                    <div id="galeria-imagenes" class="row g-3">
                        <div class="col-12 text-center text-muted py-3" id="galeria-vacia">
                            <i class="bi bi-images fs-2 d-block mb-2"></i>Sin imágenes adicionales
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Subir nueva imagen -->
                <h6 class="fw-semibold mb-3">Agregar imagen</h6>
                <form id="form-subir-imagen" enctype="multipart/form-data">
                    <input type="hidden" id="galeria-producto-id" name="producto_id">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label">Imagen <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" name="imagen" id="input-imagen-galeria"
                                   accept="image/jpeg,image/png,image/webp,image/gif" required>
                            <small class="text-muted">JPG, PNG o WebP — máx 5 MB</small>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-success w-100" id="btn-subir-imagen">
                                <i class="bi bi-upload me-1"></i>Subir imagen
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
// ── Galería de imágenes ───────────────────────────────────────────────────────
let galeriaProductoId = null;

function abrirGaleria(productoId, nombreProducto) {
    galeriaProductoId = productoId;
    document.getElementById('galeria-producto-id').value = productoId;
    document.getElementById('galeria-nombre-producto').textContent = nombreProducto;
    document.getElementById('alerta-galeria').innerHTML = '';
    document.getElementById('form-subir-imagen').reset();
    cargarGaleria(productoId);
    new bootstrap.Modal(document.getElementById('modalGaleria')).show();
}

async function cargarGaleria(productoId) {
    const contenedor = document.getElementById('galeria-imagenes');
    contenedor.innerHTML = '<div class="col-12 text-center py-3"><span class="spinner-border text-primary"></span></div>';

    try {
        const resp = await fetch(`../ajax/obtener-galeria-producto.php?producto_id=${productoId}`);
        const data = await resp.json();

        if (!data.success || data.imagenes.length === 0) {
            contenedor.innerHTML = '<div class="col-12 text-center text-muted py-3"><i class="bi bi-images fs-2 d-block mb-2"></i>Sin imágenes adicionales</div>';
            return;
        }

        contenedor.innerHTML = data.imagenes.map((img, idx) => `
            <div class="col-md-4 col-6 galeria-item" data-id="${img.id}">
                <div class="card border position-relative">
                    <img src="../img/productos/${img.imagen}" class="card-img-top"
                         style="height:140px;object-fit:cover;"
                         onerror="this.src='../img/banner-prueba.jpg'" alt="Imagen ${idx+1}">
                    <div class="card-body p-2 d-flex gap-1">
                        <button class="btn btn-sm btn-outline-secondary flex-grow-1 btn-orden-img" data-dir="up" title="Subir" ${idx===0?'disabled':''}>
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary flex-grow-1 btn-orden-img" data-dir="down" title="Bajar" ${idx===data.imagenes.length-1?'disabled':''}>
                            <i class="bi bi-chevron-right"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger flex-grow-1 btn-eliminar-img" data-id="${img.id}" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

        // Eventos eliminar
        contenedor.querySelectorAll('.btn-eliminar-img').forEach(btn => {
            btn.addEventListener('click', async function () {
                if (!confirm('¿Eliminar esta imagen?')) return;
                const id = parseInt(this.dataset.id);
                try {
                    const resp = await fetch('../ajax/eliminar-imagen-galeria.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id })
                    });
                    const data = await resp.json();
                    if (data.success) {
                        cargarGaleria(galeriaProductoId);
                    } else {
                        alert('Error: ' + data.mensaje);
                    }
                } catch { alert('Error de conexión'); }
            });
        });

        // Eventos reordenar
        contenedor.querySelectorAll('.btn-orden-img').forEach(btn => {
            btn.addEventListener('click', async function () {
                const item  = this.closest('.galeria-item');
                const items = [...contenedor.querySelectorAll('.galeria-item')];
                const idx   = items.indexOf(item);
                const dir   = this.dataset.dir;

                if (dir === 'up'   && idx === 0)              return;
                if (dir === 'down' && idx === items.length-1) return;

                if (dir === 'up') {
                    contenedor.insertBefore(item, items[idx-1]);
                } else {
                    contenedor.insertBefore(items[idx+1], item);
                }

                const nuevoOrden = [...contenedor.querySelectorAll('.galeria-item')].map(el => parseInt(el.dataset.id));
                try {
                    await fetch('../ajax/reordenar-imagenes-galeria.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ orden: nuevoOrden })
                    });
                } catch {}

                // Actualizar botones disabled
                const itemsAct = [...contenedor.querySelectorAll('.galeria-item')];
                itemsAct.forEach((el, i) => {
                    el.querySelector('[data-dir="up"]').disabled   = i === 0;
                    el.querySelector('[data-dir="down"]').disabled = i === itemsAct.length-1;
                });
            });
        });

    } catch {
        contenedor.innerHTML = '<div class="col-12"><div class="alert alert-danger">Error al cargar las imágenes</div></div>';
    }
}

// Subir imagen
document.getElementById('form-subir-imagen').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('btn-subir-imagen');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Subiendo...';

    const formData = new FormData(this);
    try {
        const resp = await fetch('../ajax/subir-imagen-galeria.php', { method: 'POST', body: formData });
        const data = await resp.json();
        const alerta = document.getElementById('alerta-galeria');

        if (data.success) {
            alerta.innerHTML = `<div class="alert alert-success alert-dismissible"><i class="bi bi-check-circle me-2"></i>${data.mensaje}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
            this.reset();
            cargarGaleria(galeriaProductoId);
        } else {
            alerta.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${data.mensaje}</div>`;
        }
    } catch {
        document.getElementById('alerta-galeria').innerHTML = '<div class="alert alert-danger">Error de conexión</div>';
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-upload me-1"></i>Subir imagen';
});
</script>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../js/productos.js"></script>

<!-- Nota: El código JavaScript específico de productos.php ya está en ../js/productos.js -->
<!--
<script>
// ============================================================================
// VARIABLES GLOBALES
// ============================================================================
const coloresPredefinidos = ['Negro', 'Blanco', 'Gris', 'Marrón', 'Beige', 'Azul', 'Verde', 'Rosa', 'Rojo', 'Amarillo', 'Naranja', 'Celeste'];
let coloresPersonalizados = [];

// ============================================================================
// INICIALIZACIÓN
// ============================================================================
$(document).ready(function() {
    generarColores();
    generarTalles();
    actualizarPreview();
    
    // Actualizar preview en tiempo real
    $('#nombre, #precio, #marca, #descripcion, #genero').on('input change', actualizarPreview);
    $('#en_promocion, #descuento_porcentaje, #destacado').on('change', actualizarPreview);
});

// ============================================================================
// GESTIÓN DE COLORES
// ============================================================================
function generarColores() {
    const container = $('#colores-list');
    container.empty();
    
    const todosColores = [...coloresPredefinidos, ...coloresPersonalizados];
    
    todosColores.forEach(color => {
        const colorId = color.replace(/\s+/g, '_').toLowerCase();
        const html = `
            <div class="col-md-6">
                <div class="card border">
                    <div class="card-body p-2">
                        <div class="form-check mb-2">
                            <input class="form-check-input color-checkbox" type="checkbox" id="color_${colorId}" 
                                   value="${color}" onchange="actualizarPreview()">
                            <label class="form-check-label fw-semibold" for="color_${colorId}">
                                ${color}
                            </label>
                        </div>
                        <input type="number" class="form-control form-control-sm stock-color" 
                               id="stock_${colorId}" placeholder="Stock" min="0" value="0" 
                               onchange="actualizarPreview()" disabled>
                    </div>
                </div>
            </div>
        `;
        container.append(html);
        
        // Habilitar/deshabilitar input de stock según checkbox
        $(`#color_${colorId}`).on('change', function() {
            $(`#stock_${colorId}`).prop('disabled', !this.checked);
            if (!this.checked) {
                $(`#stock_${colorId}`).val(0);
            }
        });
    });
}

function agregarColorPersonalizado() {
    const nuevoColor = prompt('Ingresa el nombre del nuevo color:');
    if (nuevoColor && nuevoColor.trim() !== '') {
        const colorFormateado = nuevoColor.trim();
        if (!coloresPredefinidos.includes(colorFormateado) && !coloresPersonalizados.includes(colorFormateado)) {
            coloresPersonalizados.push(colorFormateado);
            generarColores();
        } else {
            alert('Este color ya existe en la lista');
        }
    }
}

// ============================================================================
// GESTIÓN DE TALLES
// ============================================================================
function generarTalles() {
    const container = $('#talles-container');
    container.empty();
    
    for (let i = 20; i <= 46; i++) {
        const html = `
            <div>
                <input type="checkbox" class="talle-checkbox" id="talle_${i}" name="talles[]" value="${i}">
                <label for="talle_${i}">${i}</label>
            </div>
        `;
        container.append(html);
    }
}

// ============================================================================
// PREVIEW EN TIEMPO REAL
// ============================================================================
function actualizarPreview() {
    const nombre = $('#nombre').val() || 'Nombre del producto';
    const marca = $('#marca').val() || 'Marca';
    const precio = parseFloat($('#precio').val()) || 0;
    const enPromocion = $('#en_promocion').is(':checked');
    const descuento = parseInt($('#descuento_porcentaje').val()) || 0;
    const destacado = $('#destacado').is(':checked');
    
    // Actualizar nombre y marca
    $('#preview-nombre').text(nombre);
    $('#preview-marca').text(marca);
    
    // Actualizar precio
    let precioHtml = '';
    if (enPromocion && descuento > 0) {
        const precioFinal = precio * (1 - descuento/100);
        precioHtml = `
            <div>
                <small class="text-decoration-line-through text-muted">$${precio.toFixed(2)}</small>
            </div>
            <span class="h5 fw-bold text-danger">$${precioFinal.toFixed(2)}</span>
        `;
    } else {
        precioHtml = `<span class="h5 fw-bold text-primary">$${precio.toFixed(2)}</span>`;
    }
    $('#preview-precio').html(precioHtml);
    
    // Actualizar badges
    let badgesHtml = '';
    if (enPromocion && descuento > 0) {
        badgesHtml += `<span class="badge bg-danger me-1">-${descuento}% OFF</span>`;
    }
    if (destacado) {
        badgesHtml += `<span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> Destacado</span>`;
    }
    $('#preview-badges').html(badgesHtml);
    
    // Actualizar colores seleccionados
    let coloresHtml = '';
    $('.color-checkbox:checked').each(function() {
        const color = $(this).val();
        coloresHtml += `<span class="badge bg-secondary">${color}</span>`;
    });
    $('#preview-colores').html(coloresHtml || '<small class="text-muted">Sin colores</small>');
}

function previsualizarImagen(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#preview-imagen').html(`<img src="${e.target.result}" class="img-fluid" style="max-height: 200px; object-fit: cover;">`);
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleDescuento() {
    const enPromocion = $('#en_promocion').is(':checked');
    $('#descuento-container').toggle(enPromocion);
    if (!enPromocion) {
        $('#descuento_porcentaje').val(0);
    }
    actualizarPreview();
}

// ============================================================================
// ABRIR MODAL CREAR
// ============================================================================
function abrirModalCrear() {
    $('#tituloModal').text('Nuevo Producto');
    $('#formProducto')[0].reset();
    $('#producto_id').val('');
    $('#imagen_actual').val('');
    $('#preview-imagen').html('<i class="bi bi-image text-secondary fs-1"></i>');
    $('#descuento-container').hide();
    coloresPersonalizados = [];
    generarColores();
    actualizarPreview();
}

// ============================================================================
// EDITAR PRODUCTO
// ============================================================================
function editarProducto(id) {
    $('#tituloModal').text('Editar Producto');
    $('#producto_id').val(id);
    
    // Cargar datos del producto
    $.ajax({
        url: '../ajax/obtener-producto.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(producto) {
            $('#nombre').val(producto.nombre);
            $('#descripcion').val(producto.descripcion);
            $('#precio').val(producto.precio);
            $('#genero').val(producto.genero);
            $('#marca').val(producto.marca);
            $('#material').val(producto.material);
            $('#destacado').prop('checked', producto.destacado == 1);
            $('#activo').prop('checked', producto.activo == 1);
            $('#en_promocion').prop('checked', producto.en_promocion == 1);
            $('#descuento_porcentaje').val(producto.descuento_porcentaje);
            $('#imagen_actual').val(producto.imagen);
            
            // Mostrar imagen actual
            if (producto.imagen) {
                $('#preview-imagen').html(`<img src="../img/productos/${producto.imagen}" class="img-fluid" style="max-height: 200px; object-fit: cover;">`);
            }
            
            // Cargar colores con stock
            const colores = JSON.parse(producto.colores || '{}');
            Object.keys(colores).forEach(color => {
                const colorId = color.replace(/\s+/g, '_').toLowerCase();
                $(`#color_${colorId}`).prop('checked', true).trigger('change');
                $(`#stock_${colorId}`).val(colores[color]);
            });
            
            // Cargar talles
            if (producto.talles) {
                const talles = producto.talles.split(',');
                talles.forEach(talle => {
                    $(`#talle_${talle.trim()}`).prop('checked', true);
                });
            }
            
            toggleDescuento();
            actualizarPreview();
            $('#modalProducto').modal('show');
        },
        error: function() {
            mostrarMensaje('Error al cargar los datos del producto', 'danger');
        }
    });
}

// ============================================================================
// GUARDAR PRODUCTO (CREAR/EDITAR)
// ============================================================================
$('#formProducto').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Agregar colores con stock en formato JSON
    const colores = {};
    $('.color-checkbox:checked').each(function() {
        const color = $(this).val();
        const colorId = color.replace(/\s+/g, '_').toLowerCase();
        const stock = parseInt($(`#stock_${colorId}`).val()) || 0;
        colores[color] = stock;
    });
    formData.append('colores_json', JSON.stringify(colores));
    
    // Agregar talles seleccionados
    const talles = [];
    $('.talle-checkbox:checked').each(function() {
        talles.push($(this).val());
    });
    formData.append('talles', talles.join(','));
    
    const url = $('#producto_id').val() ? '../ajax/editar-producto.php' : '../ajax/crear-producto.php';
    
    $('#btnGuardar').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Guardando...');
    
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarMensaje(response.message, 'success');
                $('#modalProducto').modal('hide');
                setTimeout(() => location.reload(), 1500);
            } else {
                mostrarMensaje(response.message, 'danger');
                $('#btnGuardar').prop('disabled', false).html('<i class="bi bi-save me-2"></i>Guardar Producto');
            }
        },
        error: function() {
            mostrarMensaje('Error al guardar el producto', 'danger');
            $('#btnGuardar').prop('disabled', false).html('<i class="bi bi-save me-2"></i>Guardar Producto');
        }
    });
});

// ============================================================================
// CAMBIAR ESTADO (ACTIVO/INACTIVO)
// ============================================================================
function cambiarEstado(id, activo) {
    $.ajax({
        url: '../ajax/cambiar-estado-producto.php',
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
// ELIMINAR PRODUCTO
// ============================================================================
function eliminarProducto(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer.')) {
        $.ajax({
            url: '../ajax/eliminar-producto.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    mostrarMensaje(response.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    mostrarMensaje(response.message, 'danger');
                }
            },
            error: function() {
                mostrarMensaje('Error al eliminar el producto', 'danger');
            }
        });
    }
}

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
    
    // Scroll al mensaje
    $('html, body').animate({ scrollTop: 0 }, 300);
    
    // Auto-cerrar después de 5 segundos
    setTimeout(() => {
        $('.alert').alert('close');
    }, 5000);
}
</script> -->
