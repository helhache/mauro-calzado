<?php

/**
 * CARRITO.PHP - CARRITO DE COMPRAS
 * 
 * Funcionalidades:
 * - Mostrar productos en carrito
 * - Modificar cantidades
 * - Eliminar productos
 * - Calcular totales
 * - Proceder al checkout
 * 
 * Inspiración UX/UI:
 * - Amazon: Tabla clara con acciones
 * - Mercado Libre: Resumen de compra lateral
 * - Checkout simple y directo
 */

require_once('includes/config.php');
$titulo_pagina = "Carrito de Compras";

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// PROCESAR ACCIONES
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Eliminar producto
    if (isset($_POST['eliminar'])) {
        $producto_id = intval($_POST['producto_id']);
        unset($_SESSION['carrito'][$producto_id]);

        // También eliminar de BD si está logueado
        if (estaLogueado()) {
            $stmt = mysqli_prepare($conn, "DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $_SESSION['usuario_id'], $producto_id);
            mysqli_stmt_execute($stmt);
        }
    }

    // Actualizar cantidad
    if (isset($_POST['actualizar_cantidad'])) {
        $producto_id = intval($_POST['producto_id']);
        $nueva_cantidad = intval($_POST['cantidad']);

        if ($nueva_cantidad > 0 && isset($_SESSION['carrito'][$producto_id])) {
            $_SESSION['carrito'][$producto_id]['cantidad'] = $nueva_cantidad;

            // Actualizar en BD si está logueado
            if (estaLogueado()) {
                $stmt = mysqli_prepare($conn, "UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?");
                mysqli_stmt_bind_param($stmt, "iii", $nueva_cantidad, $_SESSION['usuario_id'], $producto_id);
                mysqli_stmt_execute($stmt);
            }
        }
    }

    // Vaciar carrito
    if (isset($_POST['vaciar_carrito'])) {
        $_SESSION['carrito'] = [];

        if (estaLogueado()) {
            $stmt = mysqli_prepare($conn, "DELETE FROM carrito WHERE usuario_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $_SESSION['usuario_id']);
            mysqli_stmt_execute($stmt);
        }
    }
}

// Calcular totales
$subtotal = 0;
$cantidad_items = 0;

foreach ($_SESSION['carrito'] as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
    $cantidad_items += $item['cantidad'];
}

// Costo de envío (gratis si supera $50.000)
$costo_envio = $subtotal >= 50000 ? 0 : 5000;
$total = $subtotal + $costo_envio;

require_once('includes/header.php');
?>

<div class="container py-5">

    <!-- Título -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold">
            <i class="bi bi-cart3 me-2"></i>Carrito de Compras
        </h1>
        <span class="badge bg-primary fs-6">
            <?php echo $cantidad_items; ?> producto<?php echo $cantidad_items != 1 ? 's' : ''; ?>
        </span>
    </div>

    <?php if (empty($_SESSION['carrito'])): ?>
        <!-- CARRITO VACÍO -->
        <div class="text-center py-5">
            <i class="bi bi-cart-x display-1 text-muted"></i>
            <h3 class="mt-4">Tu carrito está vacío</h3>
            <p class="text-muted">¡Empieza a agregar productos!</p>
            <a href="index.php" class="btn btn-primary btn-lg mt-3">
                <i class="bi bi-shop me-2"></i>Continuar Comprando
            </a>
        </div>

    <?php else: ?>
        <!-- CARRITO CON PRODUCTOS -->
        <div class="row">

            <!-- COLUMNA IZQUIERDA: LISTA DE PRODUCTOS -->
            <div class="col-lg-8 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">

                        <!-- Tabla en desktop -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table mb-0 tabla-carrito">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Precio</th>
                                        <th class="text-center">Cantidad</th>
                                        <th>Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($_SESSION['carrito'] as $producto_id => $item): ?>
                                        <tr class="fila-producto" data-precio="<?php echo $item['precio']; ?>">
                                            <!-- Producto -->
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="img/productos/<?php echo $item['imagen']; ?>"
                                                        alt="<?php echo htmlspecialchars($item['nombre']); ?>"
                                                        width="80"
                                                        class="rounded me-3">
                                                    <div>
                                                        <h6 class="mb-1">
                                                            <?php echo htmlspecialchars($item['nombre']); ?>
                                                        </h6>
                                                        <?php if (isset($item['color']) && $item['color']): ?>
                                                            <small class="text-muted">Color: <?php echo $item['color']; ?></small><br>
                                                        <?php endif; ?>
                                                        <?php if (isset($item['talle']) && $item['talle']): ?>
                                                            <small class="text-muted">Talle: <?php echo $item['talle']; ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Precio unitario -->
                                            <td class="align-middle">
                                                $<?php echo number_format($item['precio'], 0, ',', '.'); ?>
                                            </td>

                                            <!-- Cantidad -->
                                            <td class="align-middle">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="producto_id" value="<?php echo $producto_id; ?>">
                                                    <div class="input-group input-group-sm" style="width: 120px;">
                                                        <button class="btn btn-outline-secondary btn-decrementar"
                                                            type="button">
                                                            <i class="bi bi-dash"></i>
                                                        </button>
                                                        <input type="number"
                                                            class="form-control text-center"
                                                            name="cantidad"
                                                            value="<?php echo $item['cantidad']; ?>"
                                                            min="1"
                                                            readonly>
                                                        <button class="btn btn-outline-secondary btn-incrementar"
                                                            type="button">
                                                            <i class="bi bi-plus"></i>
                                                        </button>
                                                    </div>
                                                </form>
                                            </td>

                                            <!-- Subtotal -->
                                            <td class="align-middle fw-bold subtotal-producto">
                                                $<?php echo number_format($item['precio'] * $item['cantidad'], 0, ',', '.'); ?>
                                            </td>

                                            <!-- Eliminar -->
                                            <td class="align-middle">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="producto_id" value="<?php echo $producto_id; ?>">
                                                    <button type="submit"
                                                        name="eliminar"
                                                        class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Cards en móvil -->
                        <div class="d-md-none p-3">
                            <?php foreach ($_SESSION['carrito'] as $producto_id => $item): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex">
                                            <img src="img/productos/<?php echo $item['imagen']; ?>"
                                                alt="<?php echo htmlspecialchars($item['nombre']); ?>"
                                                width="80"
                                                class="rounded me-3">
                                            <div class="flex-grow-1">
                                                <h6><?php echo htmlspecialchars($item['nombre']); ?></h6>
                                                <?php if (isset($item['color'])): ?>
                                                    <small class="text-muted d-block">Color: <?php echo $item['color']; ?></small>
                                                <?php endif; ?>
                                                <?php if (isset($item['talle'])): ?>
                                                    <small class="text-muted d-block">Talle: <?php echo $item['talle']; ?></small>
                                                <?php endif; ?>
                                                <p class="mb-2 mt-2">
                                                    <strong>$<?php echo number_format($item['precio'], 0, ',', '.'); ?></strong>
                                                    <span class="text-muted">x <?php echo $item['cantidad']; ?></span>
                                                </p>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="producto_id" value="<?php echo $producto_id; ?>">
                                                    <button type="submit" name="eliminar" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i> Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="d-flex justify-content-between mt-3">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Seguir Comprando
                    </a>

                    <form method="POST" class="d-inline">
                        <button type="submit"
                            name="vaciar_carrito"
                            class="btn btn-outline-danger">
                            <i class="bi bi-trash me-2"></i>Vaciar Carrito
                        </button>
                    </form>
                </div>
            </div>

            <!-- COLUMNA DERECHA: RESUMEN -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top resumen-compra">
                    <div class="card-body">
                        <h5 class="fw-bold mb-4">Resumen de Compra</h5>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal (<?php echo $cantidad_items; ?> productos):</span>
                            <strong id="subtotal-carrito">$<?php echo number_format($subtotal, 0, ',', '.'); ?></strong>
                        </div>

                        <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                            <span>Envío:</span>
                            <strong id="costo-envio" class="<?php echo $costo_envio == 0 ? 'text-success' : ''; ?>">
                                <?php if ($costo_envio == 0): ?>
                                    ¡GRATIS!
                                <?php else: ?>
                                    $<?php echo number_format($costo_envio, 0, ',', '.'); ?>
                                <?php endif; ?>
                            </strong>
                        </div>

                        <div class="alert alert-info small<?php echo ($subtotal < 50000 && $costo_envio > 0) ? '' : ' d-none'; ?>" id="mensaje-envio-gratis">
                            <i class="bi bi-info-circle me-1"></i>
                            Te faltan <strong>$<?php echo number_format(50000 - $subtotal, 0, ',', '.'); ?></strong>
                            para envío gratis
                        </div>

                        <div class="d-flex justify-content-between mb-4">
                            <h5 class="fw-bold">Total:</h5>
                            <h5 class="fw-bold text-primary" id="total-carrito">$<?php echo number_format($total, 0, ',', '.'); ?></h5>
                        </div>

                        <!-- Botón de checkout -->
                        <?php if (estaLogueado()): ?>
                            <a href="checkout.php" class="btn btn-primary w-100 btn-lg mb-3">
                                <i class="bi bi-credit-card me-2"></i>Proceder al Pago
                            </a>
                        <?php else: ?>
                            <a href="login.php?redirect=checkout.php" class="btn btn-primary w-100 btn-lg mb-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Inicia Sesión para Continuar
                            </a>
                            <p class="small text-muted text-center mb-3">
                                ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
                            </p>
                        <?php endif; ?>

                        <!-- Métodos de pago -->
                        <div class="text-center mt-4 pt-3 border-top">
                            <p class="small text-muted mb-2">Aceptamos:</p>
                            <?php if (file_exists('img/visa.png')): ?>
                                <img src="img/visa.png" alt="Visa" height="25" class="me-2">
                            <?php else: ?>
                                <span class="badge bg-primary me-2">VISA</span>
                            <?php endif; ?>

                            <?php if (file_exists('img/mastercard.png')): ?>
                                <img src="img/mastercard.png" alt="Mastercard" height="25" class="me-2">
                            <?php else: ?>
                                <span class="badge bg-danger me-2">MASTERCARD</span>
                            <?php endif; ?>

                            <?php if (file_exists('img/mercadopago.png')): ?>
                                <img src="img/mercadopago.png" alt="Mercado Pago" height="25">
                            <?php else: ?>
                                <span class="badge bg-info">MERCADO PAGO</span>
                            <?php endif; ?>
                        </div>

                        <!-- Garantías -->
                        <div class="mt-4 pt-3 border-top">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-shield-check text-success fs-4 me-3"></i>
                                <small>Compra 100% segura</small>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-truck text-primary fs-4 me-3"></i>
                                <small>Envíos a todo el país</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-arrow-repeat text-info fs-4 me-3"></i>
                                <small>30 días para cambios</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

<?php require_once('includes/footer.php'); ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/main.js"></script>
<script src="js/carrito.js"></script>