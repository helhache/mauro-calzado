<?php
/**
 * BUSCAR.PHP - RESULTADOS DE BÚSQUEDA (VERSIÓN CON TARJETA UNIFICADA)
 */

require_once('includes/config.php');

// Obtener término de búsqueda
$busqueda = isset($_GET['q']) ? limpiarDato($_GET['q']) : '';
$titulo_pagina = "Resultados de búsqueda: " . htmlspecialchars($busqueda);

// Si no hay término, redirigir
if (empty($busqueda)) {
    redirigir('index.php');
}

// Buscar productos CON STOCK
$query_param = "%{$busqueda}%";

$stmt = mysqli_prepare($conn,
    "SELECT p.*, c.nombre as categoria_nombre,
     CASE 
         WHEN p.en_promocion = 1 THEN p.precio - (p.precio * p.descuento_porcentaje / 100)
         ELSE p.precio
     END AS precio_final
     FROM productos p
     INNER JOIN categorias c ON p.categoria_id = c.id
     WHERE p.activo = 1 
     AND p.stock > 0
     AND (p.nombre LIKE ? OR p.descripcion LIKE ? OR p.marca LIKE ?)
     ORDER BY p.nombre ASC"
);

mysqli_stmt_bind_param($stmt, "sss", $query_param, $query_param, $query_param);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_resultados = mysqli_num_rows($result);

require_once('includes/header.php');
?>

<div class="container py-4">
    
    <!-- BREADCRUMB -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="index.php">
                    <i class="bi bi-house-door-fill"></i> Inicio
                </a>
            </li>
            <li class="breadcrumb-item">Búsqueda</li>
            <li class="breadcrumb-item active" aria-current="page">
                "<?php echo htmlspecialchars($busqueda); ?>"
            </li>
        </ol>
    </nav>
    
    <!-- Botón Volver Atrás -->
    <div class="mb-4">
        <button onclick="window.history.back()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-2"></i>Volver Atrás
        </button>
    </div>
    
    <!-- Título y resultados -->
    <div class="mb-4">
        <h1 class="fw-bold">
            <i class="bi bi-search me-2"></i>
            Resultados de búsqueda
        </h1>
        <p class="text-muted">
            Buscaste: <strong class="text-primary">"<?php echo htmlspecialchars($busqueda); ?>"</strong> 
            - <span class="badge bg-primary"><?php echo $total_resultados; ?></span> resultado<?php echo $total_resultados != 1 ? 's' : ''; ?> encontrado<?php echo $total_resultados != 1 ? 's' : ''; ?>
        </p>
    </div>
    
    <?php if ($total_resultados > 0): ?>
        <!-- Grid de resultados -->
        <div class="row">
            <?php 
            // Configurar contexto para la tarjeta
            $contexto = 'buscar';
            $mostrar_categoria = true; // Mostrar categoría en búsquedas
            
            while ($producto = mysqli_fetch_assoc($result)): 
            ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <?php include('includes/componentes/tarjeta-producto.php'); ?>
                </div>
            <?php endwhile; ?>
        </div>
        
    <?php else: ?>
        <!-- Sin resultados -->
        <div class="text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <h3 class="mt-4">No se encontraron resultados</h3>
            <p class="text-muted">
                Intenta con otros términos de búsqueda o explora nuestras categorías
            </p>
            
            <div class="mt-4">
                <a href="mujer.php" class="btn btn-outline-primary me-2">
                    <i class="bi bi-gender-female me-2"></i>Mujer
                </a>
                <a href="hombre.php" class="btn btn-outline-primary me-2">
                    <i class="bi bi-gender-male me-2"></i>Hombre
                </a>
                <a href="infantiles.php" class="btn btn-outline-primary">
                    <i class="bi bi-heart-fill me-2"></i>Infantil
                </a>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Consejos de búsqueda -->
    <div class="mt-5 p-4 bg-light rounded border">
        <h5 class="fw-bold mb-3">
            <i class="bi bi-lightbulb-fill text-warning me-2"></i>
            Consejos para mejorar tu búsqueda:
        </h5>
        <ul class="mb-0">
            <li>Verifica la ortografía de las palabras</li>
            <li>Usa términos más generales (ej: "zapato" en lugar de "zapato deportivo rojo")</li>
            <li>Prueba con sinónimos (ej: "zapatilla" o "calzado")</li>
            <li>Usa menos palabras clave</li>
        </ul>
    </div>
</div>

<?php
mysqli_stmt_close($stmt);
require_once('includes/footer.php');
?>
