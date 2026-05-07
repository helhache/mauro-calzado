<?php
/**
 * OFERTAS.PHP - PRODUCTOS EN PROMOCIÓN
 */

require_once('../includes/config.php');
$titulo_pagina = "Ofertas y Promociones";

// PAGINACIÓN
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$productos_por_pagina = 10;
$offset = ($pagina_actual - 1) * $productos_por_pagina;

// CONTAR total de productos en oferta
$stmt_count = mysqli_prepare($conn,
    "SELECT COUNT(*) as total
     FROM productos p
     INNER JOIN categorias c ON p.categoria_id = c.id
     WHERE p.activo = 1 AND p.en_promocion = 1 AND p.stock > 0"
);
mysqli_stmt_execute($stmt_count);
$result_count = mysqli_stmt_get_result($stmt_count);
$total_ofertas = mysqli_fetch_assoc($result_count)['total'];
$total_paginas = ceil($total_ofertas / $productos_por_pagina);
mysqli_stmt_close($stmt_count);

// OBTENER productos en promoción paginados, ordenados A-Z
$stmt = mysqli_prepare($conn,
    "SELECT p.*, c.nombre as categoria_nombre,
     p.precio - (p.precio * p.descuento_porcentaje / 100) AS precio_final
     FROM productos p
     INNER JOIN categorias c ON p.categoria_id = c.id
     WHERE p.activo = 1 AND p.en_promocion = 1 AND p.stock > 0
     ORDER BY p.nombre ASC
     LIMIT ? OFFSET ?"
);
mysqli_stmt_bind_param($stmt, "ii", $productos_por_pagina, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

require_once('../includes/header.php');
?>

<?php
$banner_modo              = 'fondo';
$banner_altura            = '350px';
$banner_overlay_titulo    = 'OFERTAS Y PROMOCIONES';
$banner_overlay_subtitulo = 'Los mejores precios en calzado para toda la familia';
require_once('../includes/banner-carousel.php');
?>

<div class="container py-5">

    <!-- Banner de ofertas -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="bg-danger text-white p-5 rounded-3 text-center">
                <h1 class="display-4 fw-bold">
                    <i class="bi bi-tag-fill me-3"></i>
                    ¡OFERTAS INCREÍBLES!
                </h1>
                <p class="lead mb-0">
                    Aprovechá los mejores descuentos en calzado para toda la familia
                </p>
                <div class="mt-3">
                    <span class="badge bg-white text-danger fs-5 px-4 py-2">
                        <?php echo $total_ofertas; ?> productos en oferta
                    </span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($total_ofertas > 0): ?>

        <!-- Título -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0">Productos en Oferta</h3>
            </div>
        </div>

        <!-- Grid de productos -->
        <div class="row">
            <?php while ($producto = mysqli_fetch_assoc($result)): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <?php
                    $contexto = 'catalogo';
                    $mostrar_categoria = true;
                    include('../includes/componentes/tarjeta-producto.php');
                    ?>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- PAGINACIÓN -->
        <?php if ($total_paginas > 1): ?>
            <nav aria-label="Paginación de ofertas" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $pagina_actual <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php
                    $inicio = max(1, $pagina_actual - 2);
                    $fin = min($total_paginas, $pagina_actual + 2);
                    for ($i = $inicio; $i <= $fin; $i++): ?>
                        <li class="page-item <?php echo $i == $pagina_actual ? 'active' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $pagina_actual >= $total_paginas ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

    <?php else: ?>

        <!-- Sin ofertas -->
        <div class="text-center py-5">
            <i class="bi bi-tag display-1 text-muted"></i>
            <h3 class="mt-4">No hay ofertas disponibles en este momento</h3>
            <p class="text-muted">
                Volvé pronto para descubrir nuestras próximas promociones
            </p>
            <div class="mt-4">
                <a href="index.php" class="btn btn-primary">
                    <i class="bi bi-house me-2"></i>Volver al Inicio
                </a>
            </div>
        </div>

    <?php endif; ?>

    <!-- Beneficios -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="bg-light p-4 rounded-3">
                <h4 class="fw-bold mb-4 text-center">
                    <i class="bi bi-stars text-warning me-2"></i>
                    ¿Por qué comprar en nuestras ofertas?
                </h4>
                <div class="row">
                    <div class="col-md-3 text-center mb-3">
                        <i class="bi bi-percent display-4 text-danger"></i>
                        <h5 class="mt-2">Descuentos Reales</h5>
                        <p class="small text-muted">Hasta 50% de descuento en productos seleccionados</p>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <i class="bi bi-truck display-4 text-primary"></i>
                        <h5 class="mt-2">Envío Gratis</h5>
                        <p class="small text-muted">En compras superiores a $50.000</p>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <i class="bi bi-credit-card display-4 text-success"></i>
                        <h5 class="mt-2">Cuotas sin Interés</h5>
                        <p class="small text-muted">Hasta 6 cuotas en productos seleccionados</p>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <i class="bi bi-shield-check display-4 text-info"></i>
                        <h5 class="mt-2">Compra Segura</h5>
                        <p class="small text-muted">Garantía de devolución y cambio</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
mysqli_stmt_close($stmt);
require_once('../includes/footer.php');
?>
