<?php

/**
 * INDEX.PHP - PÁGINA PRINCIPAL
 * 
 * Contenido:
 * 1. Banner hero con imagen destacada
 * 2. Sección de productos en promoción
 * 3. Categorías destacadas
 * 4. Banner de beneficios (envío, pagos, etc.)
 * 
 * Inspiración UX/UI:
 * - Nike: Hero banner impactante con CTA claro
 * - Adidas: Grid de productos con hover effects
 * - Zara: Diseño minimalista y limpio
 */

// Incluir configuración y conectar a BD
require_once('includes/config.php');

// Definir título de la página
$titulo_pagina = "Inicio";

// CONSULTA: Obtener productos en promoción
// Justificación: Mostrar solo productos activos y en promoción
$query_promos = "SELECT * FROM productos 
                 WHERE activo = 1 AND en_promocion = 1 
                 ORDER BY fecha_creacion DESC 
                 LIMIT 8";
$result_promos = mysqli_query($conn, $query_promos);

// Incluir header
require_once('includes/header.php');
?>

<!-- 
    HERO BANNER - IMAGEN PRINCIPAL
    
    Justificación del diseño:
    - Análisis de sitios exitosos (Nike, Adidas):
      * Imagen grande y llamativa
      * Texto mínimo pero impactante
      * CTA (Call To Action) claro
      * Overlay de color para legibilidad
-->
<?php require_once('includes/banner-carousel.php'); ?>

<!-- 
    SECCIÓN DE BENEFICIOS
    
    Justificación:
    - Genera confianza en el cliente
    - Destaca ventajas competitivas
    - Presente en todos los e-commerce exitosos
-->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row text-center">
            <!-- 
                Grid de 4 columnas en desktop, 1 en móvil
                text-center: Centrar contenido
            -->

            <!-- BENEFICIO 1: Envío Gratis -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="p-4">
                    <i class="bi bi-truck fs-1 text-primary mb-3"></i>
                    <!-- 
                        bi-truck: Icono de camión
                        fs-1: Font size extra grande
                        text-primary: Color azul
                    -->
                    <h5 class="fw-bold">Envío Gratis</h5>
                    <p class="text-muted">En compras superiores a $50.000</p>
                    <!-- text-muted: Texto gris (Bootstrap) -->
                </div>
            </div>

            <!-- BENEFICIO 2: Pagos Seguros -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="p-4">
                    <i class="bi bi-shield-check fs-1 text-success mb-3"></i>
                    <h5 class="fw-bold">Pagos Seguros</h5>
                    <p class="text-muted">Múltiples métodos de pago</p>
                </div>
            </div>

            <!-- BENEFICIO 3: Cambios Fáciles -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="p-4">
                    <i class="bi bi-arrow-repeat fs-1 text-danger mb-3"></i>
                    <h5 class="fw-bold">Cambios Fáciles</h5>
                    <p class="text-muted">30 días para cambios y devoluciones</p>
                </div>
            </div>

            <!-- BENEFICIO 4: Atención al Cliente -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="p-4">
                    <i class="bi bi-headset fs-1 text-warning mb-3"></i>
                    <h5 class="fw-bold">Atención 24/7</h5>
                    <p class="text-muted">Estamos para ayudarte siempre</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 
    SECCIÓN DE PROMOCIONES
    
    Justificación:
    - Muestra productos destacados
    - Incentiva compra con descuentos
    - Grid responsive con hover effects
-->
<br>
<section id="promociones" class="section-padding">
    <div class="container">
        <!-- Título de la sección -->
        <div class="text-center mb-5">
            <h2 class="section-title display-5 fw-bold">
                <i class="bi bi-tag-fill text-danger me-2"></i>
                Productos en Promoción
            </h2>
            <p class="text-muted">Aprovechá nuestras ofertas exclusivas</p>
        </div>

        <div class="row">
            <?php
            /**
             * LOOP DE PRODUCTOS
             * 
             * Justificación:
             * - Itera sobre resultados de la consulta SQL
             * - Genera una card por cada producto
             * - Responsive: 4 columnas en desktop, 2 en tablet, 1 en móvil
             */

            if (mysqli_num_rows($result_promos) > 0) {
                // Hay productos en promoción - Usando componente reutilizable
                while ($producto = mysqli_fetch_assoc($result_promos)) {
                    // Configurar contexto para el componente
                    $contexto = 'promociones';
                    $mostrar_stock = true;
            ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <?php include('includes/componentes/tarjeta-producto.php'); ?>
                    </div>
                <?php
                } // Fin while
            } else {
                // No hay productos en promoción
                ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-3">No hay productos en promoción en este momento.</p>
                    <a href="mujer.php" class="btn btn-primary">Ver Catálogo Completo</a>
                </div>
            <?php
            }
            ?>
        </div>
    </div>
</section>
<br>
<!-- 
    SECCIÓN DE CATEGORÍAS DESTACADAS
    
    Justificación:
    - Facilita navegación por categorías
    - Diseño visual atractivo con imágenes
    - Inspirado en Zara, H&M (categorías con imagen de fondo)
-->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title display-5 fw-bold">
                Comprar por Categoría
            </h2>
            <p class="text-muted">Encuentra el calzado perfecto para cada ocasión</p>
        </div>

        <div class="row g-4">
            <!-- 
                g-4: Gap (espaciado) de 4 entre columnas y filas
                Equivalente a gutters más grandes
            -->

            <!-- CATEGORÍA: MUJER -->
            <div class="col-lg-4 col-md-6">
                <div class="category-card position-relative overflow-hidden">

                    <img src="img/categoria-mujer.jpg"
                        class="w-100 h-100"
                        alt="Calzado Mujer">

                    <!-- Overlay oscuro para legibilidad -->
                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark"
                        style="opacity: 0.4;">
                    </div>

                    <!-- Contenido -->
                    <div class="position-absolute bottom-0 start-0 w-100 p-4 text-white">
                        <h3 class="fw-bold mb-2">MUJER</h3>
                        <p class="mb-3">Elegancia y comodidad en cada paso</p>
                        <a href="mujer.php" class="btn btn-light">
                            Ver Colección
                            <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- CATEGORÍA: HOMBRE -->
            <div class="col-lg-4 col-md-6">
                <div class="category-card position-relative overflow-hidden">
                    <img src="img/categoria-hombre.jpg"
                        class="w-100 h-100"
                        alt="Calzado Hombre">

                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark"
                        style="opacity: 0.4;">
                    </div>

                    <div class="position-absolute bottom-0 start-0 w-100 p-4 text-white">
                        <h3 class="fw-bold mb-2">HOMBRE</h3>
                        <p class="mb-3">Estilo y confort para cada ocasión</p>
                        <a href="hombre.php" class="btn btn-light">
                            Ver Colección
                            <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- CATEGORÍA: INFANTIL -->
            <div class="col-lg-4 col-md-6">
                <div class="category-card position-relative overflow-hidden">
                    <img src="img/categoria-infantil.jpg"
                        class="w-100 h-100"
                        alt="Calzado Infantil">

                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark"
                        style="opacity: 0.4;">
                    </div>

                    <div class="position-absolute bottom-0 start-0 w-100 p-4 text-white">
                        <h3 class="fw-bold mb-2">INFANTIL</h3>
                        <p class="mb-3">Calzado cómodo para los más pequeños</p>
                        <a href="infantiles.php" class="btn btn-light">
                            Ver Colección
                            <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Estilos de categorías ahora en css/styles.css -->

<?php
// Incluir footer
require_once('includes/footer.php');
?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/main.js"></script>