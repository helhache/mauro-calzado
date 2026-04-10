<?php
/**
 * INCLUDES/BANNER-CAROUSEL.PHP
 * Componente de carrusel de banner configurable desde el admin.
 *
 * Variables PHP opcionales que el archivo incluyente puede definir antes de hacer include:
 *   $banner_modo          'completo' (default, index) | 'fondo' (páginas de categoría)
 *                         - 'completo': muestra título/subtítulo/botón de cada slide
 *                         - 'fondo': solo las imágenes rotan; se superpone el texto fijo de la página
 *   $banner_overlay_titulo    string — título fijo a mostrar sobre el fondo (modo 'fondo')
 *   $banner_overlay_subtitulo string — subtítulo fijo (modo 'fondo')
 *   $banner_altura            string — altura del carrusel, ej '400px' (default) o '300px'
 */

// Auto-crear tabla si no existe (primera vez)
mysqli_query($conn,
    "CREATE TABLE IF NOT EXISTS `banner_slides` (
      `id`           int(11)      NOT NULL AUTO_INCREMENT,
      `titulo`       varchar(200) DEFAULT NULL,
      `subtitulo`    varchar(300) DEFAULT NULL,
      `texto_boton`  varchar(100) DEFAULT NULL,
      `url_boton`    varchar(500) DEFAULT NULL,
      `imagen`       varchar(255) NOT NULL,
      `orden`        int(11)      DEFAULT 0,
      `activo`       tinyint(1)   DEFAULT 1,
      `fecha_creacion` timestamp  NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci"
);

// Cargar slides activas
$_stmt_banner = mysqli_prepare($conn,
    "SELECT id, titulo, subtitulo, texto_boton, url_boton, imagen
     FROM banner_slides WHERE activo = 1 ORDER BY orden ASC, id ASC"
);
mysqli_stmt_execute($_stmt_banner);
$_slides = mysqli_fetch_all(mysqli_stmt_get_result($_stmt_banner), MYSQLI_ASSOC);
mysqli_stmt_close($_stmt_banner);

// Opciones
$_modo     = $banner_modo             ?? 'completo';
$_altura   = $banner_altura           ?? '350px';
$_titulo   = $banner_overlay_titulo   ?? '';
$_subtitulo= $banner_overlay_subtitulo ?? '';

// Unset para no contaminar scope global
unset($banner_modo, $banner_altura, $banner_overlay_titulo, $banner_overlay_subtitulo);

// Fallback: si no hay slides, mostrar banner estático con imagen original
if (empty($_slides)) {
    ?>
    <section class="hero-banner" style="background-image: url('<?php echo (strpos($_SERVER['PHP_SELF'], '/admin') !== false ? '../' : ''); ?>img/banner-prueba.jpg'); height: <?php echo htmlspecialchars($_altura); ?>;">
        <div class="hero-content">
            <?php if ($_titulo): ?>
                <h1 class="display-3 fw-bold"><?php echo htmlspecialchars($_titulo); ?></h1>
                <?php if ($_subtitulo): ?>
                    <p class="lead"><?php echo htmlspecialchars($_subtitulo); ?></p>
                <?php endif; ?>
            <?php else: ?>
                <h1 class="display-1 fw-bold text-uppercase mb-4">Mauro Calzado</h1>
                <p class="lead fs-3 mb-4">Calidad y estilo para toda la familia</p>
                <a href="#promociones" class="btn btn-primary btn-lg">Ver Promociones</a>
            <?php endif; ?>
        </div>
    </section>
    <?php
    unset($_modo, $_altura, $_titulo, $_subtitulo, $_slides);
    return;
}

$_carousel_id = 'bannerCarrusel_' . substr(md5($_SERVER['PHP_SELF']), 0, 6);
$_img_prefix  = (strpos($_SERVER['REQUEST_URI'], '/admin') !== false || strpos($_SERVER['REQUEST_URI'], '/gerente') !== false) ? '../' : '';
?>

<!-- CARRUSEL BANNER -->
<div id="<?php echo $_carousel_id; ?>"
     class="carousel slide banner-carousel"
     data-bs-ride="carousel"
     data-bs-interval="5000"
     style="height: <?php echo htmlspecialchars($_altura); ?>;">

    <!-- Indicadores -->
    <?php if (count($_slides) > 1): ?>
    <div class="carousel-indicators">
        <?php foreach ($_slides as $i => $_s): ?>
            <button type="button"
                    data-bs-target="#<?php echo $_carousel_id; ?>"
                    data-bs-slide-to="<?php echo $i; ?>"
                    <?php echo $i === 0 ? 'class="active" aria-current="true"' : ''; ?>
                    aria-label="Slide <?php echo $i + 1; ?>"></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Slides -->
    <div class="carousel-inner h-100">
        <?php foreach ($_slides as $i => $_s): ?>
            <div class="carousel-item h-100 <?php echo $i === 0 ? 'active' : ''; ?>"
                 style="background-image: url('<?php echo $_img_prefix; ?>img/banners/<?php echo htmlspecialchars($_s['imagen']); ?>');
                        background-size: cover;
                        background-position: center;">

                <!-- Overlay oscuro para legibilidad -->
                <div class="carousel-overlay"></div>

                <div class="carousel-caption-custom">
                    <?php if ($_modo === 'fondo' && ($_titulo || $_subtitulo)): ?>
                        <!-- Modo fondo: texto fijo de la página -->
                        <?php if ($_titulo): ?>
                            <h1 class="display-3 fw-bold text-white"><?php echo htmlspecialchars($_titulo); ?></h1>
                        <?php endif; ?>
                        <?php if ($_subtitulo): ?>
                            <p class="lead text-white"><?php echo htmlspecialchars($_subtitulo); ?></p>
                        <?php endif; ?>
                    <?php elseif ($_modo === 'completo'): ?>
                        <!-- Modo completo: texto de la slide -->
                        <?php if (!empty($_s['titulo'])): ?>
                            <h1 class="display-2 fw-bold text-white mb-3"><?php echo htmlspecialchars($_s['titulo']); ?></h1>
                        <?php endif; ?>
                        <?php if (!empty($_s['subtitulo'])): ?>
                            <p class="lead fs-4 text-white mb-4"><?php echo htmlspecialchars($_s['subtitulo']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($_s['texto_boton']) && !empty($_s['url_boton'])): ?>
                            <a href="<?php echo htmlspecialchars($_s['url_boton']); ?>" class="btn btn-primary btn-lg px-4">
                                <?php echo htmlspecialchars($_s['texto_boton']); ?>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Controles anterior/siguiente -->
    <?php if (count($_slides) > 1): ?>
    <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo $_carousel_id; ?>" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Anterior</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#<?php echo $_carousel_id; ?>" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Siguiente</span>
    </button>
    <?php endif; ?>
</div>

<?php unset($_modo, $_altura, $_titulo, $_subtitulo, $_slides, $_carousel_id, $_img_prefix, $_stmt_banner, $i, $_s); ?>
