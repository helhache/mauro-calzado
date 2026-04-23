<?php
/**
 * CHECKOUT.PHP - PROCESO DE COMPRA
 * 
 * Funcionalidades:
 * - Verificar carrito no vacío
 * - Mostrar resumen de productos
 * - Formulario de datos de envío
 * - Selección de método de pago
 * - Selección de sucursal
 * - Crear pedido en BD
 * - Vaciar carrito
 * - Confirmación de pedido
 */

require_once('includes/config.php');
require_once('includes/verificar-cliente.php'); // Solo clientes logueados

$titulo_pagina = "Finalizar Compra";

// Verificar que hay productos en el carrito
if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    $_SESSION['mensaje_error'] = 'Tu carrito está vacío';
    redirigir('carrito.php');
}

// Obtener datos del usuario
$usuario_id = $_SESSION['usuario_id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM usuarios WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $usuario_id);
mysqli_stmt_execute($stmt);
$usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Obtener sucursales activas
$sucursales = [];
$query = "SELECT * FROM sucursales WHERE activo = 1 ORDER BY nombre";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $sucursales[] = $row;
}

// Calcular totales del carrito
$subtotal = 0;
$cantidad_items = 0;

foreach ($_SESSION['carrito'] as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
    $cantidad_items += $item['cantidad'];
}

$errores = [];
$pedido_creado = false;
$numero_pedido = '';

// PROCESAR FORMULARIO DE CHECKOUT
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['finalizar_compra'])) {
    
    // Obtener datos del formulario
    $sucursal_id = intval($_POST['sucursal_id'] ?? 0);
    $tipo_entrega = $_POST['tipo_entrega'] ?? '';
    $metodo_pago = $_POST['metodo_pago'] ?? '';
    
    // Datos de envío
    $direccion = limpiarDato($_POST['direccion'] ?? '');
    $ciudad = limpiarDato($_POST['ciudad'] ?? '');
    $provincia = limpiarDato($_POST['provincia'] ?? '');
    $codigo_postal = limpiarDato($_POST['codigo_postal'] ?? '');
    $telefono = limpiarDato($_POST['telefono'] ?? '');
    $notas = limpiarDato($_POST['notas'] ?? '');
    
    // VALIDACIONES
    if ($sucursal_id <= 0) {
        $errores['sucursal'] = 'Debes seleccionar una sucursal';
    }
    
    if (empty($tipo_entrega) || !in_array($tipo_entrega, ['retiro', 'envio'])) {
        $errores['tipo_entrega'] = 'Debes seleccionar un tipo de entrega';
    }
    
    if (empty($metodo_pago) || !in_array($metodo_pago, ['efectivo', 'transferencia', 'tarjeta', 'mercadopago'])) {
        $errores['metodo_pago'] = 'Debes seleccionar un método de pago';
    }
    
    if ($tipo_entrega == 'envio') {
        if (empty($direccion)) {
            $errores['direccion'] = 'La dirección es obligatoria para envío';
        }
        if (empty($ciudad)) {
            $errores['ciudad'] = 'La ciudad es obligatoria';
        }
        if (empty($provincia)) {
            $errores['provincia'] = 'La provincia es obligatoria';
        }
    }
    
    if (empty($telefono)) {
        $errores['telefono'] = 'El teléfono de contacto es obligatorio';
    }
    
    // Si no hay errores, crear el pedido
    if (empty($errores)) {
        
        // Calcular costo de envío
        $costo_envio = 0;
        if ($tipo_entrega == 'envio') {
            $costo_envio = ($subtotal >= 50000) ? 0 : 5000;
        }
        
        $total = $subtotal + $costo_envio;
        
        mysqli_begin_transaction($conn);
        
        try {
            // Generar número de pedido
            $anio = date('Y');
            $mes = date('m');
            
            $stmt_count = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM pedidos WHERE YEAR(fecha_pedido) = ? AND MONTH(fecha_pedido) = ?");
            mysqli_stmt_bind_param($stmt_count, "ii", $anio, $mes);
            mysqli_stmt_execute($stmt_count);
            $result_count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_count));
            $contador = $result_count['total'] + 1;
            mysqli_stmt_close($stmt_count);
            
            $numero_pedido = 'MC-' . $anio . str_pad($mes, 2, '0', STR_PAD_LEFT) . '-' . str_pad($contador, 4, '0', STR_PAD_LEFT);
            
            // Insertar el pedido directamente
            $stmt = mysqli_prepare($conn,
                "INSERT INTO pedidos (
                    usuario_id, sucursal_id, numero_pedido, subtotal, costo_envio, total,
                    metodo_pago, tipo_entrega, direccion_envio, ciudad_envio, provincia_envio,
                    codigo_postal_envio, telefono_contacto, notas_cliente, estado, estado_pago, fecha_pedido
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', 'pendiente', NOW())"
            );
            
            mysqli_stmt_bind_param($stmt, "iisdddssssssss",
                $usuario_id,
                $sucursal_id,
                $numero_pedido,
                $subtotal,
                $costo_envio,
                $total,
                $metodo_pago,
                $tipo_entrega,
                $direccion,
                $ciudad,
                $provincia,
                $codigo_postal,
                $telefono,
                $notas
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error al crear el pedido");
            }
            
            $pedido_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            
            // Insertar los detalles del pedido
            $stmt_detalle = mysqli_prepare($conn,
                "INSERT INTO detalle_pedidos (pedido_id, producto_id, nombre_producto, precio_unitario, cantidad, talle, color, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            
            foreach ($_SESSION['carrito'] as $producto_id => $item) {
                $subtotal_item = $item['precio'] * $item['cantidad'];
                $talle = $item['talle'] ?? null;
                $color = $item['color'] ?? null;
                
                mysqli_stmt_bind_param($stmt_detalle, "iisdissd",
                    $pedido_id,
                    $producto_id,
                    $item['nombre'],
                    $item['precio'],
                    $item['cantidad'],
                    $talle,
                    $color,
                    $subtotal_item
                );
                
                mysqli_stmt_execute($stmt_detalle);
            }
            
            mysqli_stmt_close($stmt_detalle);
            
            // Vaciar el carrito
            $_SESSION['carrito'] = [];
            
            // Eliminar carrito de la BD
            $stmt_delete = mysqli_prepare($conn, "DELETE FROM carrito WHERE usuario_id = ?");
            mysqli_stmt_bind_param($stmt_delete, "i", $usuario_id);
            mysqli_stmt_execute($stmt_delete);
            mysqli_stmt_close($stmt_delete);
            
            mysqli_commit($conn);
            
            $pedido_creado = true;
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errores['general'] = 'Error al procesar el pedido. Por favor, intenta nuevamente.';
            error_log("Error en checkout: " . $e->getMessage());
        }
    }
}

require_once('includes/header.php');
?>

<?php if ($pedido_creado): ?>
    <!-- CONFIRMACIÓN DE PEDIDO -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-4">
                    <div class="mb-4">
                        <i class="bi bi-check-circle-fill text-success checkout-confirmation-icon"></i>
                    </div>
                    <h1 class="fw-bold mb-3">¡Pedido Realizado con Éxito!</h1>
                    <p class="lead text-muted">
                        Gracias por tu compra. Tu pedido ha sido recibido y está siendo procesado.
                    </p>
                </div>
                
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="row text-center">
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Número de Pedido</small>
                                <h4 class="fw-bold text-primary"><?php echo $numero_pedido; ?></h4>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Total Pagado</small>
                                <h4 class="fw-bold">$<?php echo number_format($subtotal + ($costo_envio ?? 0), 0, ',', '.'); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-info-circle me-2"></i>¿Qué sigue ahora?
                    </h5>
                    <ol class="mb-0">
                        <li>Recibirás una confirmación por email con los detalles de tu pedido</li>
                        <li>La sucursal seleccionada revisará y confirmará tu pedido</li>
                        <li>Te notificaremos cuando tu pedido esté listo para retiro o sea enviado</li>
                        <li>Puedes seguir el estado de tu pedido desde "Mis Pedidos"</li>
                    </ol>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                    <a href="mis-pedidos.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-bag-check me-2"></i>Ver Mis Pedidos
                    </a>
                    <a href="index.php" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-house me-2"></i>Volver al Inicio
                    </a>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- FORMULARIO DE CHECKOUT -->
    <div class="container py-5">
        
        <h1 class="fw-bold mb-4">
            <i class="bi bi-credit-card me-2"></i>Finalizar Compra
        </h1>
        
        <!-- Barra de progreso -->
        <div class="row mb-5">
            <div class="col">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-center flex-fill">
                        <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center checkout-progress-circle">
                            <i class="bi bi-check-lg fs-4"></i>
                        </div>
                        <p class="small mt-2 fw-bold text-success">Carrito</p>
                    </div>
                    <div class="flex-fill checkout-progress-line active"></div>
                    <div class="text-center flex-fill">
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center checkout-progress-circle">
                            <strong>2</strong>
                        </div>
                        <p class="small mt-2 fw-bold text-primary">Datos de Envío</p>
                    </div>
                    <div class="flex-fill checkout-progress-line"></div>
                    <div class="text-center flex-fill">
                        <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center checkout-progress-circle">
                            <strong>3</strong>
                        </div>
                        <p class="small mt-2 text-muted">Confirmación</p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <h6 class="fw-bold mb-2">Por favor corrige los siguientes errores:</h6>
                <ul class="mb-0">
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="form-checkout" novalidate>
            <div class="row">
                
                <!-- COLUMNA IZQUIERDA: FORMULARIO -->
                <div class="col-lg-8 mb-4">
                    
                    <!-- 1. SELECCIÓN DE SUCURSAL -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-shop me-2"></i>1. Selecciona la Sucursal
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Elige la sucursal desde donde se preparará tu pedido
                            </p>
                            
                            <div class="row">
                                <?php foreach ($sucursales as $sucursal): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="sucursal_id" 
                                                   id="sucursal_<?php echo $sucursal['id']; ?>" 
                                                   value="<?php echo $sucursal['id']; ?>"
                                                   <?php echo (isset($_POST['sucursal_id']) && $_POST['sucursal_id'] == $sucursal['id']) ? 'checked' : ''; ?>
                                                   required>
                                            <label class="form-check-label w-100" for="sucursal_<?php echo $sucursal['id']; ?>">
                                                <div class="d-flex align-items-start">
                                                    <i class="bi bi-geo-alt-fill text-danger me-2 mt-1"></i>
                                                    <div>
                                                        <strong><?php echo $sucursal['nombre']; ?></strong><br>
                                                        <small class="text-muted">
                                                            <?php echo $sucursal['direccion']; ?>, 
                                                            <?php echo $sucursal['ciudad']; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (isset($errores['sucursal'])): ?>
                                <div class="text-danger small"><?php echo $errores['sucursal']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- 2. TIPO DE ENTREGA -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-truck me-2"></i>2. Tipo de Entrega
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-check card h-100">
                                        <div class="card-body">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="tipo_entrega" 
                                                   id="retiro" 
                                                   value="retiro"
                                                   <?php echo (isset($_POST['tipo_entrega']) && $_POST['tipo_entrega'] == 'retiro') ? 'checked' : ''; ?>
                                                   required>
                                            <label class="form-check-label w-100" for="retiro">
                                                <h6 class="fw-bold">
                                                    <i class="bi bi-shop text-primary me-2"></i>
                                                    Retiro en Sucursal
                                                </h6>
                                                <p class="small text-muted mb-0">Retira tu pedido en la sucursal seleccionada. Sin costo adicional.</p>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-check card h-100">
                                        <div class="card-body">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="tipo_entrega" 
                                                   id="envio" 
                                                   value="envio"
                                                   <?php echo (isset($_POST['tipo_entrega']) && $_POST['tipo_entrega'] == 'envio') ? 'checked' : ''; ?>
                                                   required>
                                            <label class="form-check-label w-100" for="envio">
                                                <h6 class="fw-bold">
                                                    <i class="bi bi-box-seam text-success me-2"></i>
                                                    Envío a Domicilio
                                                </h6>
                                                <p class="small text-muted mb-0">
                                                    <?php if ($subtotal >= 50000): ?>
                                                        <span class="badge bg-success">¡GRATIS!</span> Envío sin cargo
                                                    <?php else: ?>
                                                        Costo: $5.000 (Gratis en compras +$50.000)
                                                    <?php endif; ?>
                                                </p>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 3. DATOS DE ENVÍO (se muestra solo si selecciona envío) -->
                    <div class="card border-0 shadow-sm mb-4" id="datos-envio" style="display: none;">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-geo-alt me-2"></i>3. Datos de Envío
                            </h5>
                        </div>
                        <div class="card-body">
                            
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="direccion" class="form-label fw-semibold">
                                        Dirección Completa <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="direccion" 
                                           name="direccion"
                                           placeholder="Calle, número, piso, depto"
                                           value="<?php echo htmlspecialchars($usuario['direccion'] ?? $_POST['direccion'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="ciudad" class="form-label fw-semibold">
                                        Ciudad <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="ciudad" 
                                           name="ciudad"
                                           value="<?php echo htmlspecialchars($usuario['ciudad'] ?? $_POST['ciudad'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="provincia" class="form-label fw-semibold">
                                        Provincia <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="provincia" name="provincia">
                                        <option value="">Selecciona...</option>
                                        <?php
                                        $provincias = ['Buenos Aires', 'Catamarca', 'Chaco', 'Chubut', 'Córdoba', 
                                                      'Corrientes', 'Entre Ríos', 'Formosa', 'Jujuy', 'La Pampa', 
                                                      'La Rioja', 'Mendoza', 'Misiones', 'Neuquén', 'Río Negro', 
                                                      'Salta', 'San Juan', 'San Luis', 'Santa Cruz', 'Santa Fe', 
                                                      'Santiago del Estero', 'Tierra del Fuego', 'Tucumán'];
                                        $provincia_usuario = $usuario['provincia'] ?? $_POST['provincia'] ?? '';
                                        foreach ($provincias as $prov):
                                        ?>
                                            <option value="<?php echo $prov; ?>" 
                                                    <?php echo ($prov == $provincia_usuario) ? 'selected' : ''; ?>>
                                                <?php echo $prov; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="codigo_postal" class="form-label fw-semibold">
                                        Código Postal
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="codigo_postal" 
                                           name="codigo_postal"
                                           value="<?php echo htmlspecialchars($usuario['codigo_postal'] ?? $_POST['codigo_postal'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="telefono" class="form-label fw-semibold">
                                        Teléfono de Contacto <span class="text-danger">*</span>
                                    </label>
                                    <input type="tel" 
                                           class="form-control" 
                                           id="telefono" 
                                           name="telefono"
                                           placeholder="3834567890"
                                           value="<?php echo htmlspecialchars($usuario['telefono'] ?? $_POST['telefono'] ?? ''); ?>"
                                           required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 4. MÉTODO DE PAGO -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-credit-card me-2"></i>4. Método de Pago
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-check card h-100">
                                        <div class="card-body">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="metodo_pago" 
                                                   id="pago_efectivo" 
                                                   value="efectivo"
                                                   <?php echo (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] == 'efectivo') ? 'checked' : ''; ?>
                                                   required>
                                            <label class="form-check-label" for="pago_efectivo">
                                                <i class="bi bi-cash-stack fs-4 text-success"></i>
                                                <strong class="d-block">Efectivo</strong>
                                                <small class="text-muted">Paga al recibir o retirar</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-check card h-100">
                                        <div class="card-body">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="metodo_pago" 
                                                   id="pago_transferencia" 
                                                   value="transferencia"
                                                   <?php echo (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] == 'transferencia') ? 'checked' : ''; ?>
                                                   required>
                                            <label class="form-check-label" for="pago_transferencia">
                                                <i class="bi bi-bank fs-4 text-info"></i>
                                                <strong class="d-block">Transferencia</strong>
                                                <small class="text-muted">Te enviamos los datos</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-check card h-100">
                                        <div class="card-body">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="metodo_pago" 
                                                   id="pago_tarjeta" 
                                                   value="tarjeta"
                                                   <?php echo (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] == 'tarjeta') ? 'checked' : ''; ?>
                                                   required>
                                            <label class="form-check-label" for="pago_tarjeta">
                                                <i class="bi bi-credit-card-2-front fs-4 text-primary"></i>
                                                <strong class="d-block">Tarjeta</strong>
                                                <small class="text-muted">Débito, crédito, etc.</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-check card h-100">
                                        <div class="card-body">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="metodo_pago" 
                                                   id="pago_mercadopago" 
                                                   value="mercadopago"
                                                   <?php echo (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] == 'mercadopago') ? 'checked' : ''; ?>
                                                   required>
                                            <label class="form-check-label" for="pago_mercadopago">
                                                <i class="bi bi-wallet2 fs-4 text-warning"></i>
                                                <strong class="d-block">Mercado Pago</strong>
                                                <small class="text-muted">Pago online seguro</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 5. NOTAS ADICIONALES -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="bi bi-chat-left-text me-2"></i>Notas Adicionales (Opcional)
                            </h6>
                        </div>
                        <div class="card-body">
                            <textarea class="form-control" 
                                      name="notas" 
                                      rows="3" 
                                      placeholder="¿Alguna indicación especial para tu pedido?"><?php echo htmlspecialchars($_POST['notas'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                </div>
                
                <!-- COLUMNA DERECHA: RESUMEN -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                        <div class="card-header bg-light">
                            <h5 class="mb-0 fw-bold">Resumen del Pedido</h5>
                        </div>
                        <div class="card-body">
                            
                            <!-- Productos -->
                            <h6 class="fw-bold mb-3">Productos (<?php echo $cantidad_items; ?>)</h6>
                            <div class="mb-3" style="max-height: 200px; overflow-y: auto;">
                                <?php foreach ($_SESSION['carrito'] as $item): ?>
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <small>
                                                <strong><?php echo htmlspecialchars($item['nombre']); ?></strong><br>
                                                <?php if (isset($item['talle']) || isset($item['color'])): ?>
                                                    <span class="text-muted">
                                                        <?php echo isset($item['talle']) ? 'Talle: ' . $item['talle'] : ''; ?>
                                                        <?php echo isset($item['color']) ? ' | Color: ' . $item['color'] : ''; ?>
                                                    </span><br>
                                                <?php endif; ?>
                                                <span class="text-muted">Cant: <?php echo $item['cantidad']; ?></span>
                                            </small>
                                        </div>
                                        <small class="fw-bold">
                                            $<?php echo number_format($item['precio'] * $item['cantidad'], 0, ',', '.'); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <hr>
                            
                            <!-- Totales -->
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <strong>$<?php echo number_format($subtotal, 0, ',', '.'); ?></strong>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-3">
                                <span>Envío:</span>
                                <strong id="costo-envio-display">
                                    <?php if ($subtotal >= 50000): ?>
                                        <span class="text-success">¡GRATIS!</span>
                                    <?php else: ?>
                                        $5.000
                                    <?php endif; ?>
                                </strong>
                            </div>
                            
                            <hr class="my-3">
                            
                            <div class="d-flex justify-content-between mb-4">
                                <h5 class="fw-bold">Total:</h5>
                                <h5 class="fw-bold text-primary" id="total-final">
                                    $<?php 
                                    $costo_envio_inicial = ($subtotal >= 50000) ? 0 : 5000;
                                    echo number_format($subtotal + $costo_envio_inicial, 0, ',', '.'); 
                                    ?>
                                </h5>
                            </div>
                            
                            <!-- Botón de compra -->
                            <button type="submit" 
                                    name="finalizar_compra" 
                                    class="btn btn-primary btn-lg w-100 mb-3">
                                <i class="bi bi-check-circle me-2"></i>Confirmar Pedido
                            </button>
                            
                            <a href="carrito.php" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-arrow-left me-2"></i>Volver al Carrito
                            </a>
                            
                            <!-- Seguridad -->
                            <div class="text-center mt-4 pt-3 border-top">
                                <small class="text-muted">
                                    <i class="bi bi-shield-check text-success me-1"></i>
                                    Compra 100% Segura
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </form>
    </div>

<?php endif; ?>

<?php require_once('includes/footer.php'); ?>

<!-- Scripts -->
<script src="js/main.js"></script>
<script src="js/validaciones.js"></script>

<script>
// Mostrar/ocultar formulario de envío según tipo de entrega
document.querySelectorAll('input[name="tipo_entrega"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const datosEnvio = document.getElementById('datos-envio');
        const costoEnvio = document.getElementById('costo-envio-display');
        const totalFinal = document.getElementById('total-final');
        
        const subtotal = <?php echo $subtotal; ?>;
        
        if (this.value === 'envio') {
            datosEnvio.style.display = 'block';
            
            // Calcular costo de envío
            if (subtotal >= 50000) {
                costoEnvio.innerHTML = '<span class="text-success">¡GRATIS!</span>';
                totalFinal.textContent = '$' + subtotal.toLocaleString('es-AR');
            } else {
                costoEnvio.textContent = '$5.000';
                totalFinal.textContent = '$' + (subtotal + 5000).toLocaleString('es-AR');
            }
            
            // Hacer campos obligatorios
            document.getElementById('direccion').required = true;
            document.getElementById('ciudad').required = true;
            document.getElementById('provincia').required = true;
        } else {
            datosEnvio.style.display = 'none';
            costoEnvio.innerHTML = '<span class="text-success">Sin cargo</span>';
            totalFinal.textContent = '$' + subtotal.toLocaleString('es-AR');
            
            // Quitar obligatoriedad
            document.getElementById('direccion').required = false;
            document.getElementById('ciudad').required = false;
            document.getElementById('provincia').required = false;
        }
    });
});

// Validación del formulario
document.getElementById('form-checkout').addEventListener('submit', function(e) {
    let isValid = true;
    
    // Verificar sucursal
    if (!document.querySelector('input[name="sucursal_id"]:checked')) {
        MauroCalzado.mostrarAlerta('Por favor selecciona una sucursal', 'warning');
        e.preventDefault();
        return false;
    }

    // Verificar tipo de entrega
    if (!document.querySelector('input[name="tipo_entrega"]:checked')) {
        MauroCalzado.mostrarAlerta('Por favor selecciona un tipo de entrega', 'warning');
        e.preventDefault();
        return false;
    }

    // Verificar método de pago
    if (!document.querySelector('input[name="metodo_pago"]:checked')) {
        MauroCalzado.mostrarAlerta('Por favor selecciona un método de pago', 'warning');
        e.preventDefault();
        return false;
    }

    // Si es envío, verificar datos
    const tipoEntrega = document.querySelector('input[name="tipo_entrega"]:checked');
    if (tipoEntrega && tipoEntrega.value === 'envio') {
        const direccion = document.getElementById('direccion').value.trim();
        const ciudad = document.getElementById('ciudad').value.trim();
        const provincia = document.getElementById('provincia').value;

        if (!direccion || !ciudad || !provincia) {
            MauroCalzado.mostrarAlerta('Por favor completa todos los datos de envío', 'warning');
            e.preventDefault();
            return false;
        }
    }

    // Verificar teléfono
    const telefono = document.getElementById('telefono').value.trim();
    if (!telefono) {
        MauroCalzado.mostrarAlerta('Por favor ingresa un teléfono de contacto', 'warning');
        e.preventDefault();
        return false;
    }
});

// Mostrar datos de envío si ya estaba seleccionado
window.addEventListener('DOMContentLoaded', function() {
    const envioRadio = document.getElementById('envio');
    if (envioRadio && envioRadio.checked) {
        document.getElementById('datos-envio').style.display = 'block';
    }
});
</script>
