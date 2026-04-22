<?php
/**
 * GERENTE DASHBOARD - VERSIÓN CORREGIDA
 * Panel del gerente con estadísticas solo de SU sucursal
 */

require_once('../includes/config.php');
require_once('../includes/verificar-gerente.php');

$titulo_pagina = "Dashboard Gerente";

// Obtener ID de la sucursal del gerente
$sucursal_id = obtenerSucursalGerente();

// ============================================================================
// OBTENER INFORMACIÓN DE LA SUCURSAL
// ============================================================================
$query_sucursal = "SELECT * FROM sucursales WHERE id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $query_sucursal);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $sucursal_id);
    mysqli_stmt_execute($stmt);
    $result_sucursal = mysqli_stmt_get_result($stmt);
    $sucursal = mysqli_fetch_assoc($result_sucursal);
    mysqli_stmt_close($stmt);
} else {
    $sucursal = null;
}

// ============================================================================
// ESTADÍSTICAS DE LA SUCURSAL
// ============================================================================
$stats = [];

// Total de productos activos (catálogo global)
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM productos WHERE activo = 1");
$stats['productos'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Stock en esta sucursal (de la tabla stock_sucursal)
$query_stock = "SELECT COALESCE(SUM(cantidad), 0) as total 
                FROM stock_sucursal 
                WHERE sucursal_id = ?";
$stmt = mysqli_prepare($conn, $query_stock);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $sucursal_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['stock'] = mysqli_fetch_assoc($result)['total'] ?? 0;
    mysqli_stmt_close($stmt);
} else {
    // Si falla, usar el total de productos
    $stats['stock'] = $stats['productos'];
}

// Pedidos de esta sucursal (verificar primero que existe la columna)
$query_check = "SHOW COLUMNS FROM pedidos LIKE 'sucursal_id'";
$result_check = mysqli_query($conn, $query_check);

if ($result_check && mysqli_num_rows($result_check) > 0) {
    // La tabla pedidos tiene la columna sucursal_id
    $query_pedidos = "SELECT COUNT(*) as total FROM pedidos WHERE sucursal_id = ?";
    $stmt = mysqli_prepare($conn, $query_pedidos);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $sucursal_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $stats['pedidos'] = mysqli_fetch_assoc($result)['total'] ?? 0;
        mysqli_stmt_close($stmt);
    } else {
        $stats['pedidos'] = 0;
    }
} else {
    // La tabla pedidos no tiene sucursal_id, contar todos los pedidos
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM pedidos");
    $stats['pedidos'] = mysqli_fetch_assoc($result)['total'] ?? 0;
}

// Total de clientes (global)
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 1 AND activo = 1");
$stats['clientes'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// ============================================================================
// OBTENER PRODUCTOS CON STOCK BAJO EN ESTA SUCURSAL
// ============================================================================
$query_bajo_stock = "SELECT 
                        p.id,
                        p.nombre,
                        p.imagen,
                        ss.cantidad,
                        ss.cantidad_minima
                     FROM productos p
                     INNER JOIN stock_sucursal ss ON p.id = ss.producto_id
                     WHERE ss.sucursal_id = ? 
                     AND ss.cantidad <= ss.cantidad_minima
                     AND p.activo = 1
                     ORDER BY ss.cantidad ASC
                     LIMIT 5";

$stmt = mysqli_prepare($conn, $query_bajo_stock);
$productos_bajo_stock = [];

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $sucursal_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $productos_bajo_stock[] = $row;
    }
    mysqli_stmt_close($stmt);
}

require_once('includes/header-gerente.php');
?>

<!-- Estilos de dashboard gerente ahora en css/styles.css -->

<!-- Contenido Principal -->
<div class="container-fluid py-4">
    
    <!-- ============================================== -->
    <!-- ENCABEZADO -->
    <!-- ============================================== -->
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h1 class="h2 fw-bold mb-2">
                <i class="bi bi-shop text-success me-2"></i>
                Dashboard de Sucursal
            </h1>
            <p class="text-muted mb-0">
                Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['nombre']); ?></strong>
            </p>
        </div>
        <div class="col-auto">
            <span class="badge bg-success fs-6 px-3 py-2">
                <i class="bi bi-person-badge me-2"></i>Gerente
            </span>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- INFORMACIÓN DE LA SUCURSAL -->
    <!-- ============================================== -->
    <?php if ($sucursal): ?>
    <div class="row mb-4">
        <div class="col">
            <div class="sucursal-info shadow-sm">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="fw-bold mb-3">
                            <i class="bi bi-geo-alt-fill me-2"></i>
                            <?php echo htmlspecialchars($sucursal['nombre']); ?>
                        </h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-pin-map mt-1"></i>
                                    <div>
                                        <strong>Dirección:</strong><br>
                                        <?php echo htmlspecialchars($sucursal['direccion']); ?><br>
                                        <?php echo htmlspecialchars($sucursal['ciudad'] . ', ' . $sucursal['provincia']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-telephone mt-1"></i>
                                    <div>
                                        <strong>Contacto:</strong><br>
                                        <?php echo htmlspecialchars($sucursal['telefono']); ?><br>
                                        <?php echo htmlspecialchars($sucursal['email']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="bg-white bg-opacity-25 rounded-3 p-3">
                            <i class="bi bi-shop-window display-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- ============================================== -->
    <!-- TARJETAS DE ESTADÍSTICAS -->
    <!-- ============================================== -->
    <div class="row g-3 mb-4">
        <!-- Productos en Catálogo -->
        <div class="col-md-3">
            <div class="card stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Productos en Catálogo</p>
                            <h2 class="fw-bold mb-0"><?php echo $stats['productos']; ?></h2>
                            <p class="text-success small mb-0 mt-2">
                                <i class="bi bi-box-seam me-1"></i>Total disponible
                            </p>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stock en Sucursal -->
        <div class="col-md-3">
            <div class="card stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Stock en Sucursal</p>
                            <h2 class="fw-bold mb-0"><?php echo $stats['stock']; ?></h2>
                            <p class="text-info small mb-0 mt-2">
                                <i class="bi bi-boxes me-1"></i>Unidades totales
                            </p>
                        </div>
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-boxes"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pedidos -->
        <div class="col-md-3">
            <div class="card stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Pedidos Procesados</p>
                            <h2 class="fw-bold mb-0"><?php echo $stats['pedidos']; ?></h2>
                            <p class="text-warning small mb-0 mt-2">
                                <i class="bi bi-bag-check me-1"></i>Total histórico
                            </p>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-bag-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Clientes -->
        <div class="col-md-3">
            <div class="card stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Clientes Activos</p>
                            <h2 class="fw-bold mb-0"><?php echo $stats['clientes']; ?></h2>
                            <p class="text-success small mb-0 mt-2">
                                <i class="bi bi-people me-1"></i>Usuarios totales
                            </p>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ============================================== -->
    <!-- PRODUCTOS CON STOCK BAJO -->
    <!-- ============================================== -->
    <?php if (!empty($productos_bajo_stock)): ?>
    <div class="row mb-4">
        <div class="col">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                        Productos con Stock Bajo
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Producto</th>
                                    <th>Stock Actual</th>
                                    <th>Stock Mínimo</th>
                                    <th>Estado</th>
                                    <th class="text-end pe-4">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos_bajo_stock as $prod): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <?php if ($prod['imagen']): ?>
                                            <img src="../img/productos/<?php echo htmlspecialchars($prod['imagen']); ?>" 
                                                 alt="<?php echo htmlspecialchars($prod['nombre']); ?>"
                                                 class="rounded"
                                                 style="width: 50px; height: 50px; object-fit: cover;"
                                                 onerror="this.src='../img/default-product.jpg'">
                                            <?php endif; ?>
                                            <span class="fw-semibold"><?php echo htmlspecialchars($prod['nombre']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger">
                                            <?php echo $prod['cantidad']; ?> unidades
                                        </span>
                                    </td>
                                    <td><?php echo $prod['cantidad_minima']; ?> unidades</td>
                                    <td>
                                        <span class="badge bg-warning">
                                            <i class="bi bi-exclamation-circle me-1"></i>Stock Bajo
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="productos.php?id=<?php echo $prod['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-plus-circle me-1"></i>Reponer
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- ============================================== -->
    <!-- ACCESOS RÁPIDOS -->
    <!-- ============================================== -->
    <div class="row g-3">
        <div class="col-md-4">
            <a href="productos.php" class="text-decoration-none">
                <div class="card stat-card shadow-sm border-0 h-100">
                    <div class="card-body text-center py-4">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary mx-auto mb-3">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Gestión de Productos</h5>
                        <p class="text-muted small mb-0">
                            Ver catálogo, actualizar stock y precios
                        </p>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-md-4">
            <a href="pedidos.php" class="text-decoration-none">
                <div class="card stat-card shadow-sm border-0 h-100">
                    <div class="card-body text-center py-4">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning mx-auto mb-3">
                            <i class="bi bi-bag-check"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Pedidos</h5>
                        <p class="text-muted small mb-0">
                            Ver y gestionar pedidos de la sucursal
                        </p>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-md-4">
            <a href="ventas.php" class="text-decoration-none">
                <div class="card stat-card shadow-sm border-0 h-100">
                    <div class="card-body text-center py-4">
                        <div class="stat-icon bg-info bg-opacity-10 text-info mx-auto mb-3">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Ventas</h5>
                        <p class="text-muted small mb-0">
                            Estadísticas y análisis de ventas
                        </p>
                    </div>
                </div>
            </a>
        </div>
    </div>
    
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
require_once('includes/footer-gerente.php');
?>
