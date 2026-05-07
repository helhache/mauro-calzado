<?php
/**
 * PRODUCTO.PHP - PÁGINA DE DETALLE DEL PRODUCTO
 * 
 * Funcionalidades:
 * - Galería de imágenes (zoom)
 * - Selector de talle y color
 * - Agregar al carrito con validaciones
 * - Productos relacionados
 * - Reviews/valoraciones (futuro)
 * 
 * Inspiración UX/UI:
 * - Amazon: Layout de 2 columnas (imagen + info)
 * - Nike: Selector visual de talle/color
 * - Zara: Diseño limpio y minimalista
 */

require_once('../includes/config.php');

// Obtener ID del producto
$producto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($producto_id <= 0) {
    // ID inválido, redirigir
    redirigir('index.php');
}

// Obtener datos del producto
$stmt = mysqli_prepare($conn,
    "SELECT p.*, c.nombre as categoria_nombre, c.slug as categoria_slug,
     CASE 
         WHEN p.en_promocion = 1 THEN p.precio - (p.precio * p.descuento_porcentaje / 100)
         ELSE p.precio
     END AS precio_final
     FROM productos p
     INNER JOIN categorias c ON p.categoria_id = c.id
     WHERE p.id = ? AND p.activo = 1"
);

mysqli_stmt_bind_param($stmt, "i", $producto_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$producto = mysqli_fetch_assoc($result)) {
    // Producto no encontrado
    redirigir('index.php');
}

// Obtener imágenes adicionales de galería
$stmt_galeria = mysqli_prepare($conn, "SELECT imagen FROM imagenes_productos WHERE producto_id = ? ORDER BY orden ASC, id ASC");
mysqli_stmt_bind_param($stmt_galeria, 'i', $producto_id);
mysqli_stmt_execute($stmt_galeria);
$result_galeria = mysqli_stmt_get_result($stmt_galeria);
$imagenes_galeria = mysqli_fetch_all($result_galeria, MYSQLI_ASSOC);

// Incrementar contador de vistas
$stmt_vistas = mysqli_prepare($conn, "UPDATE productos SET vistas = vistas + 1 WHERE id = ?");
mysqli_stmt_bind_param($stmt_vistas, "i", $producto_id);
mysqli_stmt_execute($stmt_vistas);

$titulo_pagina = $producto['nombre'];

// Obtener reviews aprobadas
$stmt_reviews = mysqli_prepare($conn,
    "SELECT r.calificacion, r.comentario, r.fecha_creacion, u.nombre AS nombre_usuario
     FROM reviews r
     INNER JOIN usuarios u ON r.usuario_id = u.id
     WHERE r.producto_id = ? AND r.aprobada = 1
     ORDER BY r.fecha_creacion DESC
     LIMIT 20"
);
mysqli_stmt_bind_param($stmt_reviews, "i", $producto_id);
mysqli_stmt_execute($stmt_reviews);
$reviews = mysqli_fetch_all(mysqli_stmt_get_result($stmt_reviews), MYSQLI_ASSOC);
mysqli_stmt_close($stmt_reviews);

$total_reviews = count($reviews);
$promedio_rating = 0;
if ($total_reviews > 0) {
    $promedio_rating = round(array_sum(array_column($reviews, 'calificacion')) / $total_reviews, 1);
}

// ¿El usuario ya dejó una reseña?
$ya_hizo_review = false;
if (estaLogueado() && esCliente()) {
    $uid = (int)$_SESSION['usuario_id'];
    $stmt_check = mysqli_prepare($conn, "SELECT id FROM reviews WHERE producto_id = ? AND usuario_id = ?");
    mysqli_stmt_bind_param($stmt_check, "ii", $producto_id, $uid);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    $ya_hizo_review = mysqli_stmt_num_rows($stmt_check) > 0;
    mysqli_stmt_close($stmt_check);
}

// Obtener productos relacionados (misma categoría)
$stmt_relacionados = mysqli_prepare($conn,
    "SELECT id, nombre, precio, imagen, en_promocion, descuento_porcentaje
     FROM productos 
     WHERE categoria_id = ? AND id != ? AND activo = 1
     ORDER BY RAND()
     LIMIT 4"
);
/**
 * Justificación ORDER BY RAND():
 * - Muestra productos aleatorios
 * - LIMIT 4: Solo 4 productos relacionados
 * - En producción con muchos productos, usar algoritmo más eficiente
 */

mysqli_stmt_bind_param($stmt_relacionados, "ii", $producto['categoria_id'], $producto_id);
mysqli_stmt_execute($stmt_relacionados);
$result_relacionados = mysqli_stmt_get_result($stmt_relacionados);

require_once('../includes/header.php');
?>

<!-- BREADCRUMB -->
<div class="container mt-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($producto['categoria_slug']); ?>.php">
                <?php echo htmlspecialchars($producto['categoria_nombre']); ?>
            </a></li>
            <li class="breadcrumb-item active" aria-current="page">
                <?php echo htmlspecialchars($producto['nombre']); ?>
            </li>
        </ol>
    </nav>
</div>

<!-- DETALLE DEL PRODUCTO -->
<section class="py-5">
    <div class="container">
        <div class="row">
            
            <!-- COLUMNA IZQUIERDA: IMÁGENES -->
            <div class="col-lg-6 mb-4">
                <!-- Imagen principal -->
                <div class="card border shadow-sm mb-3 position-relative bg-light" style="min-height:300px;">
                    <?php if ($producto['en_promocion'] == 1): ?>
                        <span class="badge-promo" style="font-size: 1.2rem;">
                            -<?php echo $producto['descuento_porcentaje']; ?>% OFF
                        </span>
                    <?php endif; ?>

                    <img src="img/productos/<?php echo $producto['imagen']; ?>"
                         class="img-fluid rounded"
                         id="imagen-principal"
                         alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                         style="max-height:480px;object-fit:contain;width:100%;padding:1rem;">
                </div>
                
                <!-- Miniaturas (si hay imágenes adicionales en galería) -->
                <?php if (!empty($imagenes_galeria)): ?>
                    <div class="row g-2 mt-2">
                        <!-- Miniatura de imagen principal -->
                        <div class="col-3">
                            <img src="img/productos/<?php echo htmlspecialchars($producto['imagen']); ?>"
                                 class="img-thumbnail miniatura-producto active cursor-pointer"
                                 style="cursor:pointer">
                        </div>
                        <!-- Miniaturas de galería -->
                        <?php foreach ($imagenes_galeria as $img_gal): ?>
                            <div class="col-3">
                                <img src="img/productos/<?php echo htmlspecialchars($img_gal['imagen']); ?>"
                                     class="img-thumbnail miniatura-producto cursor-pointer"
                                     style="cursor:pointer">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- COLUMNA DERECHA: INFORMACIÓN -->
            <div class="col-lg-6">
                <!-- Marca -->
                <?php if (!empty($producto['marca'])): ?>
                    <p class="text-muted mb-2">
                        <strong><?php echo htmlspecialchars($producto['marca']); ?></strong>
                    </p>
                <?php endif; ?>
                
                <!-- Nombre del producto -->
                <h1 class="h2 fw-bold mb-3">
                    <?php echo htmlspecialchars($producto['nombre']); ?>
                </h1>
                
                <!-- Rating dinámico -->
                <div class="mb-3">
                    <?php if ($total_reviews > 0): ?>
                        <span class="text-warning">
                            <?php for ($i = 1; $i <= 5; $i++):
                                if ($i <= floor($promedio_rating)): ?>
                                    <i class="bi bi-star-fill"></i>
                                <?php elseif ($i - $promedio_rating < 1): ?>
                                    <i class="bi bi-star-half"></i>
                                <?php else: ?>
                                    <i class="bi bi-star"></i>
                                <?php endif;
                            endfor; ?>
                        </span>
                        <span class="text-muted ms-2">
                            <?php echo $promedio_rating; ?> (<?php echo $total_reviews; ?> <?php echo $total_reviews === 1 ? 'reseña' : 'reseñas'; ?>)
                        </span>
                        <a href="#seccion-reviews" class="ms-2 small text-decoration-none">Ver reseñas</a>
                    <?php else: ?>
                        <span class="text-muted">
                            <i class="bi bi-star me-1"></i>Sin reseñas aún — sé el primero en opinar
                        </span>
                    <?php endif; ?>
                </div>
                
                <!-- Precio -->
                <div class="mb-4">
                    <?php if ($producto['en_promocion'] == 1): ?>
                        <div>
                            <span class="text-muted text-decoration-line-through fs-5 me-2">
                                $<?php echo number_format($producto['precio'], 0, ',', '.'); ?>
                            </span>
                            <span class="badge bg-danger fs-6">
                                <?php echo $producto['descuento_porcentaje']; ?>% OFF
                            </span>
                        </div>
                        <h2 class="text-danger fw-bold mb-0">
                            $<?php echo number_format($producto['precio_final'], 0, ',', '.'); ?>
                        </h2>
                    <?php else: ?>
                        <h2 class="text-dark fw-bold mb-0">
                            $<?php echo number_format($producto['precio'], 0, ',', '.'); ?>
                        </h2>
                    <?php endif; ?>
                    
                    <small class="text-success">
                        <i class="bi bi-truck me-1"></i>
                        Envío gratis en compras superiores a $50.000
                    </small>
                </div>
                
                <!-- Descripción corta -->
                <?php if (!empty($producto['descripcion'])): ?>
                    <div class="mb-4">
                        <p class="text-muted">
                            <?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <!-- FORMULARIO DE COMPRA -->
                <form id="form-agregar-carrito">
                    <input type="hidden" name="producto_id" id="producto_id" value="<?php echo $producto['id']; ?>">
                    
                    <!-- Selector de Color -->
                    <?php
                    // Colores guardados como JSON {"Negro":5,"Blanco":3,...}
                    $colores = [];
                    if (!empty($producto['colores'])) {
                        $decoded = json_decode($producto['colores'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $colores = array_keys($decoded);
                        } else {
                            // fallback CSV
                            $colores = array_filter(array_map('trim', explode(',', $producto['colores'])));
                        }
                    }
                    ?>
                    <?php if (!empty($colores)): ?>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Color: <span id="color-seleccionado" class="text-primary">Selecciona uno</span>
                            </label>
                            <div class="d-flex gap-2 flex-wrap" id="selector-colores">
                                <?php
                                $mapa_colores = [
                                    'Negro' => '#000000',
                                    'Blanco' => '#FFFFFF',
                                    'Rojo' => '#DC143C',
                                    'Azul' => '#0047AB',
                                    'Gris' => '#808080',
                                    'Marrón' => '#8B4513',
                                    'Beige' => '#F5F5DC',
                                    'Rosa' => '#FFC0CB',
                                    'Verde' => '#228B22',
                                    'Amarillo' => '#FFD700'
                                ];
                                
                                foreach ($colores as $color):
                                    $color = trim($color);
                                    $codigo_color = $mapa_colores[$color] ?? '#CCCCCC';
                                    ?>
                                    <div class="color-option"
                                         data-color="<?php echo htmlspecialchars($color); ?>"
                                         style="background-color: <?php echo htmlspecialchars($codigo_color); ?>;"
                                         title="<?php echo htmlspecialchars($color); ?>">
                                    </div>
                                    <!-- 
                                        Justificación del diseño:
                                        - Cuadrados grandes (50x50px) para mejor UX móvil
                                        - Borde que cambia al seleccionar
                                        - Tooltip con nombre del color
                                    -->
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="color" id="input-color" required>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Selector de Talle (selección múltiple) -->
                    <?php if (!empty($producto['talles'])):
                        $talles = explode(',', $producto['talles']);
                        ?>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Talle: <span id="talle-seleccionado" class="text-muted small">Podés seleccionar uno o varios</span>
                            </label>
                            <div class="d-flex gap-2 flex-wrap" id="selector-talles">
                                <?php foreach ($talles as $talle):
                                    $talle = trim($talle);
                                    ?>
                                    <button type="button"
                                            class="btn btn-outline-secondary talle-option"
                                            data-talle="<?php echo htmlspecialchars($talle); ?>">
                                        <?php echo htmlspecialchars($talle); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="talles_seleccionados" id="input-talles" value="">

                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-info-circle me-1"></i>
                                <a href="#guia-talles" data-bs-toggle="modal" data-bs-target="#modalGuiaTalles">
                                    Ver guía de talles
                                </a>
                            </small>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Cantidad -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Cantidad</label>
                        <div class="input-group cantidad-selector">
                            <button class="btn btn-outline-secondary" type="button" data-delta="-1">
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="number"
                                   class="form-control text-center"
                                   id="cantidad"
                                   name="cantidad"
                                   value="1"
                                   min="1"
                                   max="<?php echo $producto['stock']; ?>"
                                   readonly>
                            <button class="btn btn-outline-secondary" type="button" data-delta="1">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                        <small class="text-muted">
                            Stock disponible: <strong><?php echo $producto['stock']; ?></strong> unidades
                        </small>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-cart-plus me-2"></i>
                            Agregar al Carrito
                        </button>
                        
                        <button type="button" class="btn btn-outline-danger btn-favorito-detalle"
                                data-producto-id="<?php echo $producto['id']; ?>">
                            <i class="bi bi-heart me-2"></i>
                            Agregar a Favoritos
                        </button>
                    </div>
                </form>

                <script>
                // ============================================================
                // SCRIPT INLINE — se ejecuta aquí mismo, elementos ya en DOM
                // Sin dependencias externas, sin problemas de timing
                // ============================================================
                (function() {
                    var tallesSeleccionados = [];
                    var colorSeleccionado   = null;

                    // ── Colores ──────────────────────────────────────────────
                    document.querySelectorAll('.color-option').forEach(function(div) {
                        div.style.cursor = 'pointer';
                        div.addEventListener('click', function() {
                            document.querySelectorAll('.color-option').forEach(function(el) {
                                el.style.outline   = 'none';
                                el.style.transform = 'scale(1)';
                            });
                            this.style.outline   = '3px solid #0047AB';
                            this.style.transform = 'scale(1.15)';

                            colorSeleccionado = this.dataset.color;
                            var inp = document.getElementById('input-color');
                            if (inp) inp.value = this.dataset.color;
                            var txt = document.getElementById('color-seleccionado');
                            if (txt) { txt.textContent = this.dataset.color; txt.className = 'text-success fw-semibold'; }
                        });
                    });

                    // ── Talles ───────────────────────────────────────────────
                    document.querySelectorAll('.talle-option').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var talle = this.dataset.talle;
                            var idx   = tallesSeleccionados.indexOf(talle);
                            if (idx === -1) {
                                tallesSeleccionados.push(talle);
                                this.classList.remove('btn-outline-secondary');
                                this.classList.add('btn-primary');
                            } else {
                                tallesSeleccionados.splice(idx, 1);
                                this.classList.remove('btn-primary');
                                this.classList.add('btn-outline-secondary');
                            }
                            var inp = document.getElementById('input-talles');
                            if (inp) inp.value = tallesSeleccionados.join(',');
                            var txt = document.getElementById('talle-seleccionado');
                            if (txt) {
                                if (tallesSeleccionados.length === 0) {
                                    txt.textContent = 'Podés seleccionar uno o varios';
                                    txt.className   = 'text-muted small';
                                } else {
                                    txt.textContent = tallesSeleccionados.join(', ');
                                    txt.className   = 'text-success fw-semibold';
                                }
                            }
                        });
                    });

                    // ── Cantidad +/- ─────────────────────────────────────────
                    document.querySelectorAll('[data-delta]').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var inp = document.getElementById('cantidad');
                            if (!inp) return;
                            var val = parseInt(inp.value) + parseInt(this.dataset.delta);
                            var max = parseInt(inp.max) || 999;
                            if (val < 1) val = 1;
                            if (val > max) val = max;
                            inp.value = val;
                        });
                    });

                    // ── Galería de miniaturas ─────────────────────────────────
                    document.querySelectorAll('.miniatura-producto').forEach(function(img) {
                        img.style.cursor = 'pointer';
                        img.addEventListener('click', function() {
                            var principal = document.getElementById('imagen-principal');
                            if (!principal) return;
                            principal.src = this.src;
                            document.querySelectorAll('.miniatura-producto').forEach(function(m) {
                                m.classList.remove('active');
                            });
                            this.classList.add('active');
                        });
                    });

                    // ── Agregar al carrito (submit del formulario) ────────────
                    var form = document.getElementById('form-agregar-carrito');
                    if (form) {
                        form.addEventListener('submit', function(e) {
                            e.preventDefault();

                            var tieneColores = document.getElementById('selector-colores');
                            var tieneTalles  = document.getElementById('selector-talles');

                            if (tieneColores && !colorSeleccionado) {
                                MC.alert('Por favor seleccioná un color antes de agregar al carrito', 'warning');
                                tieneColores.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                return;
                            }
                            if (tieneTalles && tallesSeleccionados.length === 0) {
                                MC.alert('Por favor seleccioná al menos un talle antes de agregar al carrito', 'warning');
                                tieneTalles.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                return;
                            }

                            var productoId = document.getElementById('producto_id') ? document.getElementById('producto_id').value : null;
                            var cantidad   = parseInt((document.getElementById('cantidad') || {}).value) || 1;
                            if (!productoId) { MC.alert('Error: producto no válido', 'danger'); return; }

                            var talles  = tallesSeleccionados.length > 0 ? tallesSeleccionados.slice() : [null];
                            var btnSub  = form.querySelector('button[type="submit"]');
                            if (btnSub) { btnSub.disabled = true; btnSub.textContent = 'Agregando...'; }

                            var agregados = 0;
                            var errorMsg  = null;
                            var idx       = 0;

                            function agregarSiguiente() {
                                if (idx >= talles.length) {
                                    if (btnSub) { btnSub.disabled = false; btnSub.innerHTML = '<i class="bi bi-cart-plus me-2"></i>Agregar al Carrito'; }
                                    if (agregados > 0) {
                                        var msg = agregados > 1 ? (agregados + ' talles agregados al carrito') : 'Producto agregado al carrito';
                                        MC.confirm(msg + ' ¿Querés ir al carrito?', function(ok) { if (ok) window.location.href = window.BASE_URL + 'compras/carrito.php'; }, { tipo: 'success', titulo: '¡Listo!', btnOk: 'Ir al carrito', btnCancel: 'Seguir comprando' });
                                    } else if (errorMsg) {
                                        MC.alert(errorMsg, 'danger');
                                    }
                                    return;
                                }

                                var talle = talles[idx++];
                                var payload = JSON.stringify({ id: productoId, cantidad: cantidad, talle: talle, color: colorSeleccionado });

                                fetch(window.BASE_URL + 'ajax/agregar-carrito.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: payload
                                })
                                .then(function(r) { return r.json(); })
                                .then(function(data) {
                                    if (data.requiere_login) {
                                        MC.alert(data.mensaje || 'Debés iniciar sesión', 'warning');
                                        window.location.href = window.BASE_URL + 'login.php?redirect=' + encodeURIComponent(window.location.href);
                                        return;
                                    }
                                    if (data.success) {
                                        agregados++;
                                        if (typeof actualizarContadorCarrito === 'function') {
                                            actualizarContadorCarrito(data.cantidad_total);
                                        }
                                    } else {
                                        errorMsg = data.mensaje;
                                    }
                                    agregarSiguiente();
                                })
                                .catch(function() {
                                    errorMsg = 'Error de conexión. Intentá de nuevo.';
                                    agregarSiguiente();
                                });

                            }

                            agregarSiguiente();
                        });
                    }

                    // ── Favoritos ─────────────────────────────────────────────
                    var favBtn = document.querySelector('.btn-favorito-detalle');
                    if (favBtn) {
                        favBtn.addEventListener('click', function() {
                            var pid = this.dataset.productoId;
                            fetch(window.BASE_URL + 'ajax/agregar-favorito.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ producto_id: pid })
                            })
                            .then(function(r) { return r.json(); })
                            .then(function(data) {
                                if (data.requiere_login) {
                                    MC.alert('Debés iniciar sesión para usar favoritos', 'warning');
                                    window.location.href = window.BASE_URL + 'login.php?redirect=' + encodeURIComponent(window.location.href);
                                    return;
                                }
                                MC.alert(data.mensaje || (data.success ? 'Listo' : 'Error'), data.success ? 'success' : 'danger');
                            })
                            .catch(function() { MC.alert('Error de conexión', 'danger'); });
                        });
                    }

                })(); // fin IIFE
                </script>

                <!-- Información adicional -->
                <div class="card border-0 bg-light mt-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Información del Producto</h6>
                        <ul class="list-unstyled mb-0">
                            <?php if (!empty($producto['material'])): ?>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <strong>Material:</strong> <?php echo htmlspecialchars($producto['material']); ?>
                                </li>
                            <?php endif; ?>
                            
                            <?php if (!empty($producto['genero'])): ?>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <strong>Género:</strong> <?php echo ucfirst($producto['genero']); ?>
                                </li>
                            <?php endif; ?>
                            
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <strong>Cambios:</strong> 30 días para cambios y devoluciones
                            </li>
                            
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <strong>Garantía:</strong> 6 meses de garantía del fabricante
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Compartir en redes sociales -->
                <div class="mt-4">
                    <p class="fw-semibold mb-2">Compartir:</p>
                    <div class="d-flex gap-2">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                           target="_blank" 
                           class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-facebook"></i> Facebook
                        </a>
                        <a href="https://wa.me/?text=<?php echo urlencode($producto['nombre'] . ' - ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                           target="_blank" 
                           class="btn btn-outline-success btn-sm">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- PRODUCTOS RELACIONADOS -->
        <?php if (mysqli_num_rows($result_relacionados) > 0): ?>
            <section class="mt-5 pt-5 border-top">
                <h3 class="fw-bold mb-4">También te puede interesar</h3>
                <div class="row">
                    <?php while ($relacionado = mysqli_fetch_assoc($result_relacionados)): 
                        $precio_rel = $relacionado['precio'];
                        if ($relacionado['en_promocion'] == 1) {
                            $precio_rel = $precio_rel - ($precio_rel * $relacionado['descuento_porcentaje'] / 100);
                        }
                        ?>
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="card product-card h-100">
                                <!-- Contenedor de imagen con ratio fijo -->
                                <div class="position-relative overflow-hidden">
                                    <?php if ($relacionado['en_promocion'] == 1): ?>
                                        <span class="badge-promo">-<?php echo $relacionado['descuento_porcentaje']; ?>%</span>
                                    <?php endif; ?>
                                    <img src="img/productos/<?php echo $relacionado['imagen']; ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($relacionado['nombre']); ?>">
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="producto.php?id=<?php echo $relacionado['id']; ?>" 
                                           class="text-dark text-decoration-none">
                                            <?php echo htmlspecialchars($relacionado['nombre']); ?>
                                        </a>
                                    </h6>
                                    <p class="h5 fw-bold mb-0">
                                        $<?php echo number_format($precio_rel, 0, ',', '.'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>

<!-- MODAL: GUÍA DE TALLES -->
<div class="modal fade" id="modalGuiaTalles" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Guía de Talles</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Para elegir el talle correcto, mide tu pie y compara con la tabla:</p>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Talle</th>
                                <th>Largo del pie (cm)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>35</td><td>22.0 - 22.5</td></tr>
                            <tr><td>36</td><td>22.5 - 23.0</td></tr>
                            <tr><td>37</td><td>23.0 - 23.5</td></tr>
                            <tr><td>38</td><td>23.5 - 24.0</td></tr>
                            <tr><td>39</td><td>24.0 - 24.5</td></tr>
                            <tr><td>40</td><td>24.5 - 25.0</td></tr>
                            <tr><td>41</td><td>25.0 - 25.5</td></tr>
                            <tr><td>42</td><td>25.5 - 26.0</td></tr>
                            <tr><td>43</td><td>26.0 - 26.5</td></tr>
                            <tr><td>44</td><td>26.5 - 27.0</td></tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info">
                    <strong>Consejo:</strong> Si estás entre dos talles, te recomendamos elegir el mayor para mayor comodidad.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECCIÓN DE RESEÑAS -->
<section class="py-5 border-top" id="seccion-reviews">
    <div class="container">
        <h3 class="fw-bold mb-4">Reseñas del Producto</h3>

        <div class="row g-4">

            <!-- Resumen de calificación -->
            <div class="col-md-3 text-center">
                <div class="card border-0 bg-light h-100 d-flex align-items-center justify-content-center p-4">
                    <?php if ($total_reviews > 0): ?>
                        <div class="display-3 fw-bold text-primary"><?php echo $promedio_rating; ?></div>
                        <div class="text-warning my-2 fs-5">
                            <?php for ($i = 1; $i <= 5; $i++):
                                if ($i <= floor($promedio_rating)): ?>
                                    <i class="bi bi-star-fill"></i>
                                <?php elseif ($i - $promedio_rating < 1): ?>
                                    <i class="bi bi-star-half"></i>
                                <?php else: ?>
                                    <i class="bi bi-star"></i>
                                <?php endif;
                            endfor; ?>
                        </div>
                        <p class="text-muted mb-0"><?php echo $total_reviews; ?> <?php echo $total_reviews === 1 ? 'reseña' : 'reseñas'; ?></p>
                    <?php else: ?>
                        <i class="bi bi-star display-3 text-muted"></i>
                        <p class="text-muted mt-2 mb-0">Sin reseñas aún</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Lista de reseñas -->
            <div class="col-md-9">
                <?php if (empty($reviews)): ?>
                    <div class="alert alert-light border">
                        <i class="bi bi-chat-square-text me-2"></i>
                        Todavía no hay reseñas para este producto. ¡Sé el primero en opinar!
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong><?php echo htmlspecialchars($rev['nombre_usuario']); ?></strong>
                                        <div class="text-warning small">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star<?php echo $i <= $rev['calificacion'] ? '-fill' : ''; ?>"></i>
                                            <?php endfor; ?>
                                            <span class="text-muted ms-1"><?php echo $rev['calificacion']; ?>/5</span>
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($rev['fecha_creacion'])); ?>
                                    </small>
                                </div>
                                <?php if (!empty($rev['comentario'])): ?>
                                    <p class="mb-0 text-muted"><?php echo nl2br(htmlspecialchars($rev['comentario'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Formulario para dejar reseña -->
                <?php if (!estaLogueado()): ?>
                    <div class="card border-0 bg-light mt-4">
                        <div class="card-body text-center py-4">
                            <i class="bi bi-person-circle display-4 text-muted d-block mb-3"></i>
                            <p class="mb-3">Inicia sesión para dejar tu reseña</p>
                            <a href="login.php?redirect=producto.php?id=<?php echo $producto_id; ?>" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                            </a>
                        </div>
                    </div>
                <?php elseif (esCliente() && !$ya_hizo_review): ?>
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-header bg-transparent fw-bold">
                            <i class="bi bi-pencil-square me-2"></i>Dejar mi Reseña
                        </div>
                        <div class="card-body">
                            <div id="alerta-review"></div>
                            <form id="form-review">
                                <input type="hidden" id="review-producto-id" value="<?php echo $producto_id; ?>">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Calificación</label>
                                    <div class="d-flex gap-2" id="selector-estrellas">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star fs-3 text-warning estrella-select cursor-pointer"
                                               data-valor="<?php echo $i; ?>"
                                               style="cursor:pointer;"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <input type="hidden" id="review-calificacion" value="0">
                                    <div class="invalid-feedback d-block" id="error-calificacion" style="display:none!important;"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Comentario <span class="text-muted fw-normal">(opcional)</span></label>
                                    <textarea class="form-control" id="review-comentario" rows="3"
                                              placeholder="Contá tu experiencia con el producto..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary" id="btn-review">
                                    <i class="bi bi-send me-2"></i>Enviar Reseña
                                </button>
                            </form>
                        </div>
                    </div>
                <?php elseif (esCliente() && $ya_hizo_review): ?>
                    <div class="alert alert-info mt-4">
                        <i class="bi bi-info-circle me-2"></i>
                        Ya enviaste una reseña para este producto. ¡Gracias por tu opinión!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
// ── Scripts específicos de esta página ──────────────────────────────────────
// Se pasan a footer.php para que carguen DENTRO de </body>, después de main.js
ob_start();
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/producto-detalle.js"></script>
<script>
// ── Sistema de reseñas ──────────────────────────────────────────────────────
(function () {
    const estrellas = document.querySelectorAll('.estrella-select');
    const inputCal  = document.getElementById('review-calificacion');

    if (!estrellas.length) return;

    function pintarEstrellas(valor) {
        estrellas.forEach((s, idx) => {
            s.classList.toggle('bi-star-fill', idx < valor);
            s.classList.toggle('bi-star',      idx >= valor);
        });
    }

    estrellas.forEach(s => {
        s.addEventListener('mouseover', () => pintarEstrellas(parseInt(s.dataset.valor)));
        s.addEventListener('mouseleave', () => pintarEstrellas(parseInt(inputCal.value) || 0));
        s.addEventListener('click', () => {
            inputCal.value = s.dataset.valor;
            pintarEstrellas(parseInt(s.dataset.valor));
        });
    });

    document.getElementById('form-review')?.addEventListener('submit', async function (e) {
        e.preventDefault();

        const calificacion = parseInt(inputCal.value);
        if (!calificacion) {
            document.getElementById('alerta-review').innerHTML =
                '<div class="alert alert-warning">Seleccioná una calificación antes de enviar.</div>';
            return;
        }

        const btn = document.getElementById('btn-review');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

        try {
            const resp = await fetch(window.BASE_URL + 'ajax/guardar-review.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    producto_id:  parseInt(document.getElementById('review-producto-id').value),
                    calificacion: calificacion,
                    comentario:   document.getElementById('review-comentario').value.trim()
                })
            });
            const data = await resp.json();
            const alerta = document.getElementById('alerta-review');

            if (data.success) {
                alerta.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>${data.mensaje}</div>`;
                document.getElementById('form-review').style.opacity = '0.5';
                document.getElementById('form-review').style.pointerEvents = 'none';
            } else {
                alerta.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${data.mensaje}</div>`;
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send me-2"></i>Enviar Reseña';
            }
        } catch (err) {
            document.getElementById('alerta-review').innerHTML =
                '<div class="alert alert-danger">Error de conexión. Intentá de nuevo.</div>';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send me-2"></i>Enviar Reseña';
        }
    });
})();
</script>
<?php
$scripts_pagina = ob_get_clean();
require_once('../includes/footer.php');
?>