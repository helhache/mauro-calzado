<?php
/**
 * ============================================================================
 * COMPONENTE: TARJETA DE PRODUCTO - VERSIÓN ÚNICA Y REUTILIZABLE
 * ============================================================================
 *
 * Este componente se usa en TODAS las páginas del sitio que muestran productos.
 * Ventajas de usar un solo componente:
 * - Mantener consistencia visual en todo el sitio
 * - Actualizar diseño en un solo lugar
 * - Facilitar el mantenimiento
 * - Reducir código duplicado
 * - Implementar cambios más rápido
 *
 * ============================================================================
 * VARIABLES REQUERIDAS:
 * ============================================================================
 * @param array $producto - Datos del producto desde la BD
 *   Campos esperados:
 *   - id (int): ID del producto
 *   - nombre (string): Nombre del producto
 *   - precio (decimal): Precio base
 *   - imagen (string): Nombre del archivo de imagen
 *   - stock (int): Stock disponible
 *   - marca (string): Marca del producto (opcional)
 *   - categoria_nombre (string): Nombre de categoría (opcional)
 *   - en_promocion (bool): Si está en promoción (opcional)
 *   - descuento_porcentaje (int): % de descuento (opcional)
 *
 * ============================================================================
 * VARIABLES OPCIONALES:
 * ============================================================================
 * @param string $vista - Tipo de vista ('grid', 'lista', 'destacado')
 *   - 'grid': Vista de cuadrícula (default)
 *   - 'lista': Vista de lista horizontal
 *   - 'destacado': Vista destacada con más info
 *
 * @param string $contexto - Contexto donde se muestra
 *   - 'catalogo': Catálogo general (default)
 *   - 'promociones': Sección de promociones
 *   - 'favoritos': Página de favoritos
 *   - 'busqueda': Resultados de búsqueda
 *   - 'admin': Panel administrativo
 *
 * @param bool $mostrar_categoria - Mostrar badge de categoría (default: false)
 * @param bool $mostrar_stock - Mostrar stock disponible (default: true)
 * @param bool $botones_admin - Mostrar botones de administración (default: false)
 * @param string $clase_adicional - Clases CSS adicionales (default: '')
 *
 * ============================================================================
 * EJEMPLO DE USO:
 * ============================================================================
 *
 * // Uso básico (catálogo)
 * $producto = mysqli_fetch_assoc($resultado);
 * include('includes/componentes/tarjeta-producto.php');
 *
 * // Uso con opciones
 * $producto = mysqli_fetch_assoc($resultado);
 * $contexto = 'promociones';
 * $mostrar_categoria = true;
 * include('includes/componentes/tarjeta-producto.php');
 *
 * ============================================================================
 */

// Validar que existe la variable $producto
if (!isset($producto) || !is_array($producto)) {
    echo '<div class="alert alert-danger">Error: Variable $producto no definida</div>';
    return;
}

// ============================================================================
// CONFIGURACIÓN POR DEFECTO
// ============================================================================
$vista = $vista ?? 'grid';
$contexto = $contexto ?? 'catalogo';
$mostrar_categoria = $mostrar_categoria ?? false;
$mostrar_stock = $mostrar_stock ?? true;
$botones_admin = $botones_admin ?? false;
$clase_adicional = $clase_adicional ?? '';

// ============================================================================
// CALCULAR PRECIOS Y DESCUENTOS
// ============================================================================
$precio_original = floatval($producto['precio']);
$precio_final = $precio_original;
$tiene_descuento = false;
$descuento_porcentaje = 0;
$ahorro = 0;

// Verificar si está en promoción
if (isset($producto['en_promocion']) && $producto['en_promocion'] == 1) {
    $tiene_descuento = true;
    $descuento_porcentaje = intval($producto['descuento_porcentaje'] ?? 0);

    if ($descuento_porcentaje > 0) {
        $precio_final = $precio_original * (1 - $descuento_porcentaje / 100);
        $ahorro = $precio_original - $precio_final;
    }
}

// Si ya viene calculado el precio_final, usarlo
if (isset($producto['precio_final'])) {
    $precio_final = floatval($producto['precio_final']);
    if ($tiene_descuento) {
        $ahorro = $precio_original - $precio_final;
    }
}

// ============================================================================
// DATOS DEL PRODUCTO
// ============================================================================
$id = intval($producto['id']);
$nombre = htmlspecialchars($producto['nombre'] ?? 'Producto sin nombre');
$imagen = htmlspecialchars($producto['imagen'] ?? '');
$marca = htmlspecialchars($producto['marca'] ?? '');
$stock = intval($producto['stock'] ?? 0);
$categoria_nombre = htmlspecialchars($producto['categoria_nombre'] ?? '');

// Estado de stock
$hay_stock = $stock > 0;
$stock_bajo = $stock > 0 && $stock <= 5;

// ============================================================================
// CLASES CSS DINÁMICAS
// ============================================================================
$card_classes = "card product-card h-100 shadow-sm position-relative {$clase_adicional}";

if ($contexto === 'promociones') {
    $card_classes .= " border-danger";
}

?>

<!-- ========================================================================
     TARJETA DE PRODUCTO
     ======================================================================== -->
<div class="<?php echo $card_classes; ?>">

    <!-- ====================================================================
         IMAGEN DEL PRODUCTO
         ==================================================================== -->
    <div class="position-relative overflow-hidden" style="background-color: #f8f9fa;">

        <!-- Badge de descuento (esquina superior derecha) -->
        <?php if ($tiene_descuento && $descuento_porcentaje > 0): ?>
            <span class="badge bg-danger position-absolute top-0 end-0 m-2 fs-6 fw-bold shadow"
                  style="z-index: 10;">
                <i class="bi bi-percent me-1"></i>
                -<?php echo $descuento_porcentaje; ?>% OFF
            </span>
        <?php endif; ?>

        <!-- Badge de categoría (esquina superior izquierda) -->
        <?php if ($mostrar_categoria && !empty($categoria_nombre)): ?>
            <span class="badge bg-primary position-absolute top-0 start-0 m-2"
                  style="z-index: 10;">
                <i class="bi bi-tag-fill me-1"></i>
                <?php echo $categoria_nombre; ?>
            </span>
        <?php endif; ?>

        <!-- Badge de stock bajo (warning) -->
        <?php if ($stock_bajo && $contexto !== 'admin'): ?>
            <span class="badge bg-warning text-dark position-absolute bottom-0 start-0 m-2"
                  style="z-index: 10;">
                <i class="bi bi-exclamation-triangle me-1"></i>
                ¡Últimas <?php echo $stock; ?> unidades!
            </span>
        <?php endif; ?>

        <!-- Sin stock (badge) -->
        <?php if (!$hay_stock): ?>
            <span class="badge bg-secondary position-absolute bottom-0 start-0 m-2"
                  style="z-index: 10;">
                <i class="bi bi-x-circle me-1"></i>
                Sin stock
            </span>
        <?php endif; ?>

        <!-- Botón favorito (solo catálogo) -->
        <?php if ($contexto === 'catalogo' || $contexto === 'promociones'): ?>
            <button class="btn btn-sm btn-light position-absolute top-0 start-0 m-2 btn-add-favorite shadow-sm"
                    data-id="<?php echo $id; ?>"
                    style="z-index: 10;"
                    title="Agregar a favoritos">
                <i class="bi bi-heart"></i>
            </button>
        <?php endif; ?>

        <!-- Botón eliminar de favoritos (solo favoritos) -->
        <?php if ($contexto === 'favoritos'): ?>
            <form method="POST" class="position-absolute top-0 start-0 m-2" style="z-index: 10;">
                <input type="hidden" name="producto_id" value="<?php echo $id; ?>">
                <button type="submit"
                        name="eliminar_favorito"
                        class="btn btn-sm btn-danger shadow-sm"
                        data-confirm="¿Eliminar de favoritos?" data-confirm-tipo="danger" data-confirm-ok="Sí, eliminar" data-confirm-titulo="Eliminar favorito"
                        title="Eliminar de favoritos">
                    <i class="bi bi-heart-fill"></i>
                </button>
            </form>
        <?php endif; ?>

        <!-- IMAGEN -->
        <a href="<?php echo BASE_PATH; ?>catalogo/producto.php?id=<?php echo $id; ?>" class="d-block">
            <?php if (!empty($imagen) && file_exists(ABSPATH . "/img/productos/{$imagen}")): ?>
                <!-- Imagen real del producto -->
                <img src="<?php echo BASE_PATH; ?>img/productos/<?php echo $imagen; ?>"
                     class="card-img-top"
                     alt="<?php echo $nombre; ?>"
                     style="height: 280px; object-fit: cover; transition: transform 0.3s ease;"
                     loading="lazy"
                     onmouseover="this.style.transform='scale(1.05)'"
                     onmouseout="this.style.transform='scale(1)'">
            <?php else: ?>
                <!-- Placeholder cuando no hay imagen -->
                <div class="d-flex align-items-center justify-content-center flex-column"
                     style="height: 280px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="bi bi-image text-white display-3 mb-2"></i>
                    <?php if (!empty($marca)): ?>
                        <span class="text-white fw-bold fs-5"><?php echo $marca; ?></span>
                    <?php else: ?>
                        <span class="text-white-50">Sin imagen</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </a>
    </div>

    <!-- ====================================================================
         CUERPO DE LA TARJETA
         ==================================================================== -->
    <div class="card-body d-flex flex-column">

        <!-- Marca -->
        <?php if (!empty($marca)): ?>
            <p class="text-muted small mb-1">
                <i class="bi bi-award-fill me-1 text-warning"></i>
                <?php echo $marca; ?>
            </p>
        <?php endif; ?>

        <!-- Nombre del producto (h6 para SEO) -->
        <h6 class="card-title mb-2">
            <a href="<?php echo BASE_PATH; ?>catalogo/producto.php?id=<?php echo $id; ?>"
               class="text-dark text-decoration-none hover-primary">
                <?php echo $nombre; ?>
            </a>
        </h6>

        <!-- Stock disponible -->
        <?php if ($mostrar_stock && $hay_stock && !$stock_bajo): ?>
            <p class="small text-success mb-2">
                <i class="bi bi-check-circle-fill me-1"></i>
                Stock disponible (<?php echo $stock; ?> unidades)
            </p>
        <?php endif; ?>

        <!-- Fecha agregado (solo favoritos) -->
        <?php if ($contexto === 'favoritos' && isset($producto['fecha_agregado'])): ?>
            <p class="small text-muted mb-2">
                <i class="bi bi-calendar-event me-1"></i>
                Agregado: <?php echo date('d/m/Y', strtotime($producto['fecha_agregado'])); ?>
            </p>
        <?php endif; ?>

        <!-- Espaciador flexible (empuja precios y botones hacia abajo) -->
        <div class="mt-auto">

            <!-- ============================================================
                 PRECIOS
                 ============================================================ -->
            <div class="mb-3">
                <?php if ($tiene_descuento && $ahorro > 0): ?>
                    <!-- Precio original tachado -->
                    <div class="mb-1">
                        <span class="text-muted text-decoration-line-through small">
                            $<?php echo number_format($precio_original, 0, ',', '.'); ?>
                        </span>
                    </div>
                    <!-- Precio con descuento (destacado) -->
                    <div class="mb-1">
                        <span class="h4 text-danger fw-bold mb-0">
                            $<?php echo number_format($precio_final, 0, ',', '.'); ?>
                        </span>
                    </div>
                    <!-- Ahorro en verde -->
                    <div>
                        <small class="text-success fw-semibold">
                            <i class="bi bi-piggy-bank-fill me-1"></i>
                            Ahorrás $<?php echo number_format($ahorro, 0, ',', '.'); ?>
                        </small>
                    </div>
                <?php else: ?>
                    <!-- Precio normal sin descuento -->
                    <div>
                        <span class="h4 text-dark fw-bold mb-0">
                            $<?php echo number_format($precio_final, 0, ',', '.'); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ============================================================
                 BOTONES DE ACCIÓN
                 ============================================================ -->
            <div class="d-grid gap-2">

                <!-- CONTEXTO: CATÁLOGO / PROMOCIONES -->
                <?php if ($contexto === 'catalogo' || $contexto === 'promociones'): ?>

                    <!-- Botón Agregar al Carrito -->
                    <?php if ($hay_stock): ?>
                        <button class="btn <?php echo $contexto === 'promociones' ? 'btn-danger' : 'btn-primary'; ?> btn-sm btn-add-cart"
                                data-id="<?php echo $id; ?>"
                                data-nombre="<?php echo htmlspecialchars($nombre, ENT_QUOTES); ?>"
                                data-precio="<?php echo $precio_final; ?>"
                                data-imagen="<?php echo $imagen; ?>">
                            <i class="bi bi-cart-plus-fill me-2"></i>Agregar al Carrito
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-sm" disabled>
                            <i class="bi bi-x-circle me-2"></i>Sin Stock
                        </button>
                    <?php endif; ?>

                    <!-- Botón Ver Detalles -->
                    <a href="<?php echo BASE_PATH; ?>catalogo/producto.php?id=<?php echo $id; ?>"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-eye-fill me-2"></i>Ver Detalles
                    </a>

                <?php endif; ?>

                <!-- CONTEXTO: BÚSQUEDA / FAVORITOS -->
                <?php if ($contexto === 'busqueda' || $contexto === 'favoritos'): ?>

                    <!-- Botón Agregar al Carrito -->
                    <?php if ($hay_stock): ?>
                        <button class="btn btn-primary btn-sm btn-add-cart"
                                data-id="<?php echo $id; ?>"
                                data-nombre="<?php echo htmlspecialchars($nombre, ENT_QUOTES); ?>"
                                data-precio="<?php echo $precio_final; ?>"
                                data-imagen="<?php echo $imagen; ?>">
                            <i class="bi bi-cart-plus-fill me-2"></i>Agregar al Carrito
                        </button>
                    <?php endif; ?>

                    <!-- Botón Ver Producto -->
                    <a href="<?php echo BASE_PATH; ?>catalogo/producto.php?id=<?php echo $id; ?>"
                       class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye-fill me-2"></i>Ver Producto
                    </a>

                <?php endif; ?>

                <!-- CONTEXTO: ADMIN (panel administrativo) -->
                <?php if ($botones_admin): ?>
                    <a href="productos-editar.php?id=<?php echo $id; ?>"
                       class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil-fill me-2"></i>Editar
                    </a>
                    <button class="btn btn-danger btn-sm"
                            onclick="eliminarProducto(<?php echo $id; ?>)">
                        <i class="bi bi-trash-fill me-2"></i>Eliminar
                    </button>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php
// ============================================================================
// LIMPIAR VARIABLES
// ============================================================================
// Importante: Evitar que las variables afecten al siguiente producto en el loop
unset(
    $vista, $contexto, $mostrar_categoria, $mostrar_stock, $botones_admin,
    $clase_adicional, $precio_original, $precio_final, $tiene_descuento,
    $descuento_porcentaje, $ahorro, $id, $nombre, $imagen, $marca, $stock,
    $categoria_nombre, $hay_stock, $stock_bajo, $card_classes
);
?>
