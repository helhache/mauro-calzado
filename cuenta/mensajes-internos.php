<?php
/**
 * MENSAJES-INTERNOS.PHP - MENSAJERÍA INTERNA CLIENTE-EMPRESA
 * 
 * Funcionalidades:
 * - Cliente puede enviar mensajes a la empresa
 * - Ver mensajes enviados y respuestas
 * - Relacionar mensaje con pedido (opcional)
 * - Marcar mensajes como leídos
 */

require_once('../includes/config.php');
require_once('../includes/verificar-cliente.php');

$titulo_pagina = "Mensajes";
$usuario_id = $_SESSION['usuario_id'];

$errores = [];
$success = '';
$nuevo_mensaje = isset($_GET['nuevo']) && $_GET['nuevo'] == 1;
$pedido_relacionado = isset($_GET['pedido_id']) ? intval($_GET['pedido_id']) : null;

// PROCESAR ENVÍO DE NUEVO MENSAJE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enviar_mensaje'])) {
    
    $asunto = limpiarDato($_POST['asunto'] ?? '');
    $mensaje = limpiarDato($_POST['mensaje'] ?? '');
    $pedido_id = isset($_POST['pedido_id']) && $_POST['pedido_id'] != '' ? intval($_POST['pedido_id']) : null;
    
    // Validaciones
    if (empty($asunto)) {
        $errores['asunto'] = 'El asunto es obligatorio';
    }
    
    if (empty($mensaje) || strlen($mensaje) < 10) {
        $errores['mensaje'] = 'El mensaje debe tener al menos 10 caracteres';
    }
    
    if (empty($errores)) {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO mensajes_internos (usuario_id, asunto, mensaje, pedido_id, estado, fecha_envio)
             VALUES (?, ?, ?, ?, 'pendiente', NOW())"
        );
        
        mysqli_stmt_bind_param($stmt, "issi", $usuario_id, $asunto, $mensaje, $pedido_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = '¡Mensaje enviado correctamente! Te responderemos a la brevedad.';
            $nuevo_mensaje = false;
            $asunto = $mensaje = '';
            $pedido_id = null;
        } else {
            $errores['general'] = 'Error al enviar el mensaje';
        }
        
        mysqli_stmt_close($stmt);
    }
}

// MARCAR MENSAJE COMO LEÍDO
if (isset($_GET['marcar_leido'])) {
    $mensaje_id = intval($_GET['marcar_leido']);
    
    $stmt_leer = mysqli_prepare($conn, 
        "UPDATE mensajes_internos SET leido_cliente = 1 WHERE id = ? AND usuario_id = ?"
    );
    mysqli_stmt_bind_param($stmt_leer, "ii", $mensaje_id, $usuario_id);
    mysqli_stmt_execute($stmt_leer);
    mysqli_stmt_close($stmt_leer);
    
    redirigir(SITE_URL . 'cuenta/mensajes-internos.php');
}

// OBTENER MENSAJES DEL CLIENTE
$query = "SELECT m.*, p.numero_pedido
          FROM mensajes_internos m
          LEFT JOIN pedidos p ON m.pedido_id = p.id
          WHERE m.usuario_id = ?
          ORDER BY m.fecha_envio DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $usuario_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$mensajes = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// OBTENER PEDIDOS DEL CLIENTE (para el selector)
$stmt_pedidos = mysqli_prepare($conn, 
    "SELECT id, numero_pedido, fecha_pedido, estado 
     FROM pedidos 
     WHERE usuario_id = ? 
     ORDER BY fecha_pedido DESC 
     LIMIT 10"
);
mysqli_stmt_bind_param($stmt_pedidos, "i", $usuario_id);
mysqli_stmt_execute($stmt_pedidos);
$result_pedidos = mysqli_stmt_get_result($stmt_pedidos);
$pedidos_usuario = mysqli_fetch_all($result_pedidos, MYSQLI_ASSOC);
mysqli_stmt_close($stmt_pedidos);

require_once('../includes/header.php');
?>

<div class="container py-5">
    
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-1">
                <i class="bi bi-chat-dots me-2"></i>Mensajes
            </h1>
            <p class="text-muted mb-0">Comunícate con nosotros</p>
        </div>
        <div>
            <?php if (!$nuevo_mensaje): ?>
                <a href="?nuevo=1" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Nuevo Mensaje
                </a>
            <?php endif; ?>
            <a href="mi-cuenta.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <h6 class="fw-bold mb-2">Por favor corrige los siguientes errores:</h6>
            <ul class="mb-0">
                <?php foreach ($errores as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($nuevo_mensaje): ?>
        <!-- FORMULARIO NUEVO MENSAJE -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-envelope me-2"></i>Nuevo Mensaje
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    
                    <!-- Relacionar con pedido (opcional) -->
                    <?php if (!empty($pedidos_usuario)): ?>
                        <div class="mb-3">
                            <label for="pedido_id" class="form-label fw-semibold">
                                Relacionar con Pedido <span class="text-muted small">(Opcional)</span>
                            </label>
                            <select class="form-select" id="pedido_id" name="pedido_id">
                                <option value="">No relacionar con ningún pedido</option>
                                <?php foreach ($pedidos_usuario as $pedido): ?>
                                    <option value="<?php echo $pedido['id']; ?>"
                                            <?php echo ($pedido_relacionado == $pedido['id']) ? 'selected' : ''; ?>>
                                        Pedido #<?php echo $pedido['numero_pedido']; ?> - 
                                        <?php echo date('d/m/Y', strtotime($pedido['fecha_pedido'])); ?> - 
                                        <?php echo ucfirst($pedido['estado']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Si tu consulta es sobre un pedido específico, selecciónalo aquí</small>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Asunto -->
                    <div class="mb-3">
                        <label for="asunto" class="form-label fw-semibold">
                            Asunto <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control <?php echo isset($errores['asunto']) ? 'is-invalid' : ''; ?>" 
                               id="asunto" 
                               name="asunto"
                               placeholder="Ej: Consulta sobre mi pedido, Cambio de producto, etc."
                               value="<?php echo htmlspecialchars($asunto ?? ''); ?>"
                               required>
                        <?php if (isset($errores['asunto'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['asunto']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Mensaje -->
                    <div class="mb-4">
                        <label for="mensaje" class="form-label fw-semibold">
                            Mensaje <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control <?php echo isset($errores['mensaje']) ? 'is-invalid' : ''; ?>" 
                                  id="mensaje" 
                                  name="mensaje" 
                                  rows="6"
                                  placeholder="Escribe tu consulta o mensaje aquí..."
                                  required><?php echo htmlspecialchars($mensaje ?? ''); ?></textarea>
                        <small class="text-muted">Mínimo 10 caracteres</small>
                        <?php if (isset($errores['mensaje'])): ?>
                            <div class="invalid-feedback"><?php echo $errores['mensaje']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Tiempo de respuesta:</strong> Respondemos todos los mensajes en un plazo máximo de 24 horas hábiles.
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" name="enviar_mensaje" class="btn btn-primary">
                            <i class="bi bi-send me-2"></i>Enviar Mensaje
                        </button>
                        <a href="mensajes-internos.php" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- LISTA DE MENSAJES -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0 fw-bold">Mis Mensajes</h5>
        </div>
        <div class="card-body p-0">
            
            <?php if (empty($mensajes)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <h5 class="mt-3">No tienes mensajes aún</h5>
                    <p class="text-muted">Envía tu primera consulta o mensaje</p>
                </div>
            <?php else: ?>
                
                <div class="list-group list-group-flush">
                    <?php foreach ($mensajes as $mensaje): ?>
                        <div class="list-group-item list-group-item-action" 
                             data-bs-toggle="collapse" 
                             data-bs-target="#mensaje<?php echo $mensaje['id']; ?>"
                             style="cursor: pointer;">
                            
                            <div class="d-flex w-100 justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <h6 class="mb-0 fw-bold">
                                            <?php echo htmlspecialchars($mensaje['asunto']); ?>
                                        </h6>
                                        
                                        <!-- Badge de estado -->
                                        <?php if ($mensaje['estado'] == 'pendiente'): ?>
                                            <span class="badge bg-warning text-dark ms-2">Pendiente</span>
                                        <?php elseif ($mensaje['estado'] == 'respondido'): ?>
                                            <?php if ($mensaje['leido_cliente'] == 0): ?>
                                                <span class="badge bg-danger ms-2">Nueva Respuesta</span>
                                            <?php else: ?>
                                                <span class="badge bg-success ms-2">Respondido</span>
                                            <?php endif; ?>
                                        <?php elseif ($mensaje['estado'] == 'cerrado'): ?>
                                            <span class="badge bg-secondary ms-2">Cerrado</span>
                                        <?php endif; ?>
                                        
                                        <!-- Badge de pedido relacionado -->
                                        <?php if ($mensaje['numero_pedido']): ?>
                                            <span class="badge bg-info ms-2">
                                                <i class="bi bi-tag me-1"></i>#<?php echo $mensaje['numero_pedido']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p class="mb-1 text-muted small">
                                        <?php echo substr(htmlspecialchars($mensaje['mensaje']), 0, 100); ?>...
                                    </p>
                                    
                                    <small class="text-muted">
                                        <i class="bi bi-calendar me-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($mensaje['fecha_envio'])); ?>
                                    </small>
                                </div>
                                
                                <div class="text-end">
                                    <i class="bi bi-chevron-down"></i>
                                </div>
                            </div>
                            
                            <!-- Detalle del mensaje (colapsable) -->
                            <div class="collapse mt-3" id="mensaje<?php echo $mensaje['id']; ?>">
                                <div class="border-top pt-3">
                                    
                                    <!-- Mensaje original -->
                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-2">
                                            <strong>Tu mensaje:</strong>
                                        </small>
                                        <div class="bg-light p-3 rounded">
                                            <?php echo nl2br(htmlspecialchars($mensaje['mensaje'])); ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Respuesta (si existe) -->
                                    <?php if ($mensaje['respuesta']): ?>
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-2">
                                                <strong>Nuestra respuesta:</strong>
                                                <?php if ($mensaje['fecha_respuesta']): ?>
                                                    - <?php echo date('d/m/Y H:i', strtotime($mensaje['fecha_respuesta'])); ?>
                                                <?php endif; ?>
                                            </small>
                                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                                <?php echo nl2br(htmlspecialchars($mensaje['respuesta'])); ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Marcar como leído -->
                                        <?php if ($mensaje['leido_cliente'] == 0): ?>
                                            <a href="?marcar_leido=<?php echo $mensaje['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-check2 me-1"></i>Marcar como Leído
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="alert alert-warning mb-0">
                                            <i class="bi bi-clock-history me-2"></i>
                                            Aún no hemos respondido este mensaje. Te responderemos pronto.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
            <?php endif; ?>
        </div>
    </div>
    
</div>

<?php require_once('../includes/footer.php'); ?>
