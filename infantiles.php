<?php
/**
 * MUJER.PHP - CATÁLOGO DE CALZADO FEMENINO (VERSIÓN CON TARJETA UNIFICADA)
 */

require_once('includes/config.php');
$titulo_pagina = "Calzado Infantil";

// PARÁMETROS DE FILTRADO Y ORDENAMIENTO
$orden = $_GET['orden'] ?? 'recientes';
$precio_min = isset($_GET['precio_min']) ? floatval($_GET['precio_min']) : 0;
$precio_max = isset($_GET['precio_max']) ? floatval($_GET['precio_max']) : 999999;
$busqueda = isset($_GET['q']) ? limpiarDato($_GET['q']) : '';

// PAGINACIÓN
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$productos_por_pagina = 12;
$offset = ($pagina_actual - 1) * $productos_por_pagina;

// CONSTRUIR QUERY
$where = ["p.activo = 1", "c.slug = 'infantil'", "p.stock > 0"]; // FILTRO: Solo productos con stock
$params = [];
$types = "";

// Filtro de precio
if ($precio_min > 0) {
    $where[] = "p.precio >= ?";
    $params[] = $precio_min;
    $types .= "d";
}
if ($precio_max < 999999) {
    $where[] = "p.precio <= ?";
    $params[] = $precio_max;
    $types .= "d";
}

// Filtro de búsqueda
if (!empty($busqueda)) {
    $where[] = "(p.nombre LIKE ? OR p.descripcion LIKE ? OR p.marca LIKE ?)";
    $busqueda_param = "%{$busqueda}%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $types .= "sss";
}

// Ordenamiento
$order_by = "p.fecha_creacion DESC";
switch ($orden) {
    case 'precio_asc': $order_by = "p.precio ASC"; break;
    case 'precio_desc': $order_by = "p.precio DESC"; break;
    case 'nombre': $order_by = "p.nombre ASC"; break;
    case 'populares': $order_by = "p.ventas DESC"; break;
}

$where_clause = implode(" AND ", $where);

// CONTAR TOTAL DE PRODUCTOS
$query_count = "SELECT COUNT(*) as total 
                FROM productos p 
                INNER JOIN categorias c ON p.categoria_id = c.id 
                WHERE {$where_clause}";

if (!empty($params)) {
    $stmt_count = mysqli_prepare($conn, $query_count);
    mysqli_stmt_bind_param($stmt_count, $types, ...$params);
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $total_productos = mysqli_fetch_assoc($result_count)['total'];
    mysqli_stmt_close($stmt_count);
} else {
    $result_count = mysqli_query($conn, $query_count);
    $total_productos = mysqli_fetch_assoc($result_count)['total'];
}

$total_paginas = ceil($total_productos / $productos_por_pagina);

// OBTENER PRODUCTOS
$query_productos = "SELECT p.*, c.nombre as categoria_nombre,
                    CASE 
                        WHEN p.en_promocion = 1 THEN p.precio - (p.precio * p.descuento_porcentaje / 100)
                        ELSE p.precio
                    END AS precio_final
                    FROM productos p
                    INNER JOIN categorias c ON p.categoria_id = c.id
                    WHERE {$where_clause}
                    ORDER BY {$order_by}
                    LIMIT ? OFFSET ?";

$params[] = $productos_por_pagina;
$params[] = $offset;
$types .= "ii";

$stmt_productos = mysqli_prepare($conn, $query_productos);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_productos, $types, ...$params);
}
mysqli_stmt_execute($stmt_productos);
$result_productos = mysqli_stmt_get_result($stmt_productos);

require_once('includes/header.php');
?>

<?php
$banner_modo              = 'fondo';
$banner_altura            = '350px';
$banner_overlay_titulo    = 'CALZADO INFANTIL';
$banner_overlay_subtitulo = 'Comodidad y diversión para los más pequeños';
require_once('includes/banner-carousel.php');
?>

<!-- BREADCRUMB -->
<div class="container mt-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
            <li class="breadcrumb-item active" aria-current="page">Infantil</li>
        </ol>
    </nav>
</div>

<!-- SECCIÓN PRINCIPAL -->
<section class="py-5">
    <div class="container">
        <div class="row">
            
            <!-- SIDEBAR - FILTROS -->
            <div class="col-lg-3 mb-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 80px; z-index: 100;">
                    <div class="card-body">
                        <h5 class="fw-bold mb-4">
                            <i class="bi bi-funnel me-2"></i>Filtros
                        </h5>
                        
                        <form method="GET" action="" id="form-filtros">
                            <!-- Buscar -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Buscar</label>
                                <input type="text" name="q" class="form-control" 
                                       placeholder="Buscar producto..." 
                                       value="<?php echo htmlspecialchars($busqueda); ?>">
                            </div>
                            
                            <!-- Rango de Precio -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Rango de Precio</label>
                                <div class="mb-2">
                                    <label for="precio_min" class="form-label small">Mínimo</label>
                                    <input type="number" class="form-control" id="precio_min" 
                                           name="precio_min" placeholder="0" 
                                           value="<?php echo $precio_min > 0 ? $precio_min : ''; ?>">
                                </div>
                                <div class="mb-2">
                                    <label for="precio_max" class="form-label small">Máximo</label>
                                    <input type="number" class="form-control" id="precio_max" 
                                           name="precio_max" placeholder="Sin límite" 
                                           value="<?php echo $precio_max < 999999 ? $precio_max : ''; ?>">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                <i class="bi bi-search me-2"></i>Aplicar Filtros
                            </button>
                            <a href="infantiles.php" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-x-circle me-2"></i>Limpiar Filtros
                            </a>
                        </form>
                    </div>
                </div>
            </div>

            <!-- GRID DE PRODUCTOS -->
            <div class="col-lg-9">
                
                <!-- Barra de ordenamiento -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="mb-0">
                            <span class="text-muted"><?php echo $total_productos; ?></span> 
                            producto<?php echo $total_productos != 1 ? 's' : ''; ?>
                        </h5>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label class="small text-muted mb-0">Ordenar por:</label>
                        <select class="form-select form-select-sm" id="select-orden" style="width: auto;">
                            <option value="recientes" <?php echo $orden == 'recientes' ? 'selected' : ''; ?>>Más recientes</option>
                            <option value="precio_asc" <?php echo $orden == 'precio_asc' ? 'selected' : ''; ?>>Precio: Menor a Mayor</option>
                            <option value="precio_desc" <?php echo $orden == 'precio_desc' ? 'selected' : ''; ?>>Precio: Mayor a Menor</option>
                            <option value="nombre" <?php echo $orden == 'nombre' ? 'selected' : ''; ?>>Nombre A-Z</option>
                            <option value="populares" <?php echo $orden == 'populares' ? 'selected' : ''; ?>>Más Populares</option>
                        </select>
                    </div>
                </div>

                <!-- GRID DE TARJETAS -->
                <div class="row">
                    <?php
                    if (mysqli_num_rows($result_productos) > 0) {
                        // Configurar contexto para la tarjeta
                        $contexto = 'catalogo';
                        
                        while ($producto = mysqli_fetch_assoc($result_productos)) {
                            ?>
                            <div class="col-lg-4 col-md-6 col-sm-6 mb-4">
                                <?php include('includes/componentes/tarjeta-producto.php'); ?>
                            </div>
                            <?php
                        }
                    } else {
                        ?>
                        <div class="col-12 text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                            <h4 class="mt-3">No se encontraron productos</h4>
                            <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                            <a href="infantiles.php" class="btn btn-primary">Ver todos los productos</a>
                        </div>
                        <?php
                    }
                    ?>
                </div>

                <!-- PAGINACIÓN -->
                <?php if ($total_paginas > 1): ?>
                    <nav aria-label="Paginación de productos" class="mt-5">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $pagina_actual <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" 
                                   href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo !empty($busqueda) ? '&q=' . urlencode($busqueda) : ''; ?><?php echo isset($_GET['orden']) ? '&orden=' . $_GET['orden'] : ''; ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php
                            $inicio = max(1, $pagina_actual - 2);
                            $fin = min($total_paginas, $pagina_actual + 2);
                            
                            for ($i = $inicio; $i <= $fin; $i++): ?>
                                <li class="page-item <?php echo $i == $pagina_actual ? 'active' : ''; ?>">
                                    <a class="page-link" 
                                       href="?pagina=<?php echo $i; ?><?php echo !empty($busqueda) ? '&q=' . urlencode($busqueda) : ''; ?><?php echo isset($_GET['orden']) ? '&orden=' . $_GET['orden'] : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $pagina_actual >= $total_paginas ? 'disabled' : ''; ?>">
                                <a class="page-link" 
                                   href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo !empty($busqueda) ? '&q=' . urlencode($busqueda) : ''; ?><?php echo isset($_GET['orden']) ? '&orden=' . $_GET['orden'] : ''; ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</section>

<?php
mysqli_stmt_close($stmt_productos);
require_once('includes/footer.php');
?>

<script>
document.getElementById('select-orden').addEventListener('change', function() {
    const url = new URL(window.location.href);
    url.searchParams.set('orden', this.value);
    url.searchParams.set('pagina', '1');
    window.location.href = url.toString();
});
</script>
