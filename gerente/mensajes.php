<?php
/**
 * GERENTE/MENSAJES.PHP - MENSAJERÍA CON ADMIN
 * 
 * Funcionalidades:
 * - Enviar mensajes al admin con archivos adjuntos
 * - Ver respuestas del admin
 * - Marcar respuestas como leídas
 * - Tipos: pedido mercadería, transferencia, consulta, reporte
 * - Prioridades: baja, media, alta
 */

require_once('../includes/config.php');
require_once('../includes/verificar-gerente.php');

$titulo_pagina = "Mensajes";
$gerente_id = $_SESSION['usuario_id'];

// Obtener sucursal del gerente
$stmt_sucursal = mysqli_prepare($conn, "SELECT sucursal_id FROM usuarios WHERE id = ?");
mysqli_stmt_bind_param($stmt_sucursal, "i", $gerente_id);
mysqli_stmt_execute($stmt_sucursal);
$result_sucursal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_sucursal));
$sucursal_id = $result_sucursal['sucursal_id'];
mysqli_stmt_close($stmt_sucursal);

$errores = [];
$success = '';
$nuevo_mensaje = isset($_GET['nuevo']) && $_GET['nuevo'] == 1;
$mensaje_id_ver = isset($_GET['id']) ? intval($_GET['id']) : null;

// PROCESAR ENVÍO DE NUEVO MENSAJE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enviar_mensaje'])) {
    
    $tipo_mensaje = $_POST['tipo_mensaje'] ?? '';
    $asunto = limpiarDato($_POST['asunto'] ?? '');
    $mensaje = limpiarDato($_POST['mensaje'] ?? '');
    $prioridad = $_POST['prioridad'] ?? 'media';
    $pedido_relacionado = isset($_POST['pedido_relacionado']) && $_POST['pedido_relacionado'] != '' ? intval($_POST['pedido_relacionado']) : null;
    
    // Validaciones
    if (empty($tipo_mensaje)) {
        $errores['tipo'] = 'Debes seleccionar un tipo de mensaje';
    }
    
    if (empty($asunto)) {
        $errores['asunto'] = 'El asunto es obligatorio';
    }
    
    if (empty($mensaje) || strlen($mensaje) < 10) {
        $errores['mensaje'] = 'El mensaje debe tener al menos 10 caracteres';
    }
    
    // Manejo de archivo adjunto
    $archivo_adjunto = null;
    if (isset($_FILES['archivo_adjunto']) && $_FILES['archivo_adjunto']['error'] == 0) {
        $archivo = $_FILES['archivo_adjunto'];
        
        // Validar tamaño (5MB)
        if ($archivo['size'] <= 5 * 1024 * 1024) {
            $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
            $nombre_archivo = 'gerente_' . $gerente_id . '_' . time() . '.' . $extension;
            $ruta_destino = '../uploads/mensajes/' . $nombre_archivo;
            
            if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
                $archivo_adjunto = $nombre_archivo;
            } else {
                $errores['archivo'] = 'Error al subir el archivo';
            }
        } else {
            $errores['archivo'] = 'El archivo no debe superar 5MB';
        }
    }
    
    if (empty($errores)) {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO mensajes_gerente_admin 
             (gerente_id, sucursal_id, tipo_mensaje, asunto, mensaje, archivo_adjunto, pedido_relacionado_id, prioridad, estado, fecha_envio)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())"
        );
        
        mysqli_stmt_bind_param($stmt, "iissssis",
            $gerente_id, $sucursal_id, $tipo_mensaje, $asunto, $mensaje, $archivo_adjunto, $pedido_relacionado, $prioridad
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $success = '¡Mensaje enviado correctamente! El administrador lo recibirá pronto.';
            $nuevo_mensaje = false;
            // Limpiar variables
            $tipo_mensaje = $asunto = $mensaje = '';
            $pedido_relacionado = null;
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
        "UPDATE mensajes_gerente_admin SET leido_gerente = 1 WHERE id = ? AND gerente_id = ?"
    );
    mysqli_stmt_bind_param($stmt_leer, "ii", $mensaje_id, $gerente_id);
    mysqli_stmt_execute($stmt_leer);
    mysqli_stmt_close($stmt_leer);
    
    redirigir('mensajes.php');
}

// OBTENER MENSAJES DEL GERENTE
$query = "SELECT m.*, 
          a.nombre AS admin_nombre, a.apellido AS admin_apellido,
          p.numero_pedido
          FROM mensajes_gerente_admin m
          LEFT JOIN usuarios a ON m.admin_id = a.id
          LEFT JOIN pedidos p ON m.pedido_relacionado_id = p.id
          WHERE m.gerente_id = ?
          ORDER BY m.fecha_envio DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $gerente_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$mensajes = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// OBTENER PEDIDOS RECIENTES (para relacionar)
$stmt_pedidos = mysqli_prepare($conn,
    "SELECT id, numero_pedido, fecha_pedido, estado 
     FROM pedidos 
     WHERE sucursal_id = ? 
     ORDER BY fecha_pedido DESC 
     LIMIT 10"
);
mysqli_stmt_bind_param($stmt_pedidos, "i", $sucursal_id);
mysqli_stmt_execute($stmt_pedidos);
$result_pedidos = mysqli_stmt_get_result($stmt_pedidos);
$pedidos_sucursal = mysqli_fetch_all($result_pedidos, MYSQLI_ASSOC);
mysqli_stmt_close($stmt_pedidos);

require_once('includes/header-gerente.php');
?>

<div class="container-fluid py-4">
    
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-1">
                <i class="bi bi-chat-dots me-2"></i>Mensajes con Administración
            </h1>
            <p class="text-muted mb-0">Comunícate con el administrador</p>
        </div>
        <div>
            <?php if (!$nuevo_mensaje): ?>
                <a href="?nuevo=1" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Nuevo Mensaje
                </a>
            <?php endif; ?>
            <a href="dashboard.php" class="btn btn-outline-secondary">
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
                    <i class="bi bi-envelope me-2"></i>Nuevo Mensaje para Administración
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    
                    <div class="row">
                        <!-- Tipo de mensaje -->
                        <div class="col-md-6 mb-3">
                            <label for="tipo_mensaje" class="form-label fw-semibold">
                                Tipo de Mensaje <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="tipo_mensaje" name="tipo_mensaje" required>
                                <option value="">Selecciona el tipo...</option>
                                <option value="pedido_mercaderia" <?php echo (isset($tipo_mensaje) && $tipo_mensaje == 'pedido_mercaderia') ? 'selected' : ''; ?>>
                                    📦 Pedido de Mercadería
                                </option>
                                <option value="transferencia" <?php echo (isset($tipo_mensaje) && $tipo_mensaje == 'transferencia') ? 'selected' : ''; ?>>
                                    🔄 Solicitud de Transferencia entre Sucursales
                                </option>
                                <option value="consulta" <?php echo (isset($tipo_mensaje) && $tipo_mensaje == 'consulta') ? 'selected' : ''; ?>>
                                    ❓ Consulta General
                                </option>
                                <option value="reporte" <?php echo (isset($tipo_mensaje) && $tipo_mensaje == 'reporte') ? 'selected' : ''; ?>>
                                    ⚠️ Reporte de Problema
                                </option>
                                <option value="otro" <?php echo (isset($tipo_mensaje) && $tipo_mensaje == 'otro') ? 'selected' : ''; ?>>
                                    📋 Otro
                                </option>
                            </select>
                        </div>
                        
                        <!-- Prioridad -->
                        <div class="col-md-6 mb-3">
                            <label for="prioridad" class="form-label fw-semibold">
                                Prioridad <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="prioridad" name="prioridad" required>
                                <option value="baja">🟢 Baja - Puede esperar</option>
                                <option value="media" selected>🟡 Media - Normal</option>
                                <option value="alta">🔴 Alta - Urgente</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Pedido relacionado (opcional) -->
                    <?php if (!empty($pedidos_sucursal)): ?>
                        <div class="mb-3">
                            <label for="pedido_relacionado" class="form-label fw-semibold">
                                Relacionar con Pedido <span class="text-muted small">(Opcional)</span>
                            </label>
                            <select class="form-select" id="pedido_relacionado" name="pedido_relacionado">
                                <option value="">No relacionar con ningún pedido</option>
                                <?php foreach ($pedidos_sucursal as $pedido): ?>
                                    <option value="<?php echo $pedido['id']; ?>">
                                        Pedido #<?php echo $pedido['numero_pedido']; ?> - 
                                        <?php echo date('d/m/Y', strtotime($pedido['fecha_pedido'])); ?> - 
                                        <?php echo ucfirst($pedido['estado']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Asunto -->
                    <div class="mb-3">
                        <label for="asunto" class="form-label fw-semibold">
                            Asunto <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="asunto" 
                               name="asunto"
                               placeholder="Ej: Necesito stock de zapatillas talle 40"
                               value="<?php echo htmlspecialchars($asunto ?? ''); ?>"
                               required>
                    </div>
                    
                    <!-- Mensaje -->
                    <div class="mb-3">
                        <label for="mensaje" class="form-label fw-semibold">
                            Mensaje <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" 
                                  id="mensaje" 
                                  name="mensaje" 
                                  rows="6"
                                  placeholder="Escribe tu mensaje detallado aquí..."
                                  required><?php echo htmlspecialchars($mensaje ?? ''); ?></textarea>
                        <small class="text-muted">Mínimo 10 caracteres</small>
                    </div>
                    
                    <!-- Archivo adjunto -->
                    <div class="mb-4">
                        <label for="archivo_adjunto" class="form-label fw-semibold">
                            Adjuntar Archivo <span class="text-muted small">(Opcional)</span>
                        </label>
                        <input type="file" 
                               class="form-control" 
                               id="archivo_adjunto" 
                               name="archivo_adjunto">
                        <small class="text-muted">
                            Puedes adjuntar comprobantes, imágenes, PDFs, etc. - Máximo 5MB
                        </small>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Tiempo de respuesta:</strong> El administrador responderá tu mensaje en un plazo máximo de 24 horas hábiles.
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" name="enviar_mensaje" class="btn btn-primary">
                            <i class="bi bi-send me-2"></i>Enviar Mensaje
                        </button>
                        <a href="mensajes.php" class="btn btn-outline-secondary">
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
                    <p class="text-muted">Envía tu primer mensaje al administrador</p>
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
                                        
                                        <!-- Badge de tipo -->
                                        <?php
                                        $badge_tipo = [
                                            'pedido_mercaderia' => 'primary',
                                            'transferencia' => 'info',
                                            'consulta' => 'secondary',
                                            'reporte' => 'danger',
                                            'otro' => 'dark'
                                        ];
                                        $tipo_class = $badge_tipo[$mensaje['tipo_mensaje']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $tipo_class; ?> ms-2">
                                            <?php echo str_replace('_', ' ', ucfirst($mensaje['tipo_mensaje'])); ?>
                                        </span>
                                        
                                        <!-- Badge de estado -->
                                        <?php if ($mensaje['estado'] == 'pendiente'): ?>
                                            <span class="badge bg-warning text-dark ms-2">Pendiente</span>
                                        <?php elseif ($mensaje['estado'] == 'respondido'): ?>
                                            <?php if ($mensaje['leido_gerente'] == 0): ?>
                                                <span class="badge bg-danger ms-2">Nueva Respuesta</span>
                                            <?php else: ?>
                                                <span class="badge bg-success ms-2">Respondido</span>
                                            <?php endif; ?>
                                        <?php elseif ($mensaje['estado'] == 'cerrado'): ?>
                                            <span class="badge bg-secondary ms-2">Cerrado</span>
                                        <?php endif; ?>
                                        
                                        <!-- Badge de prioridad -->
                                        <?php if ($mensaje['prioridad'] == 'alta'): ?>
                                            <span class="badge bg-danger ms-2">
                                                <i class="bi bi-exclamation-triangle me-1"></i>URGENTE
                                            </span>
                                        <?php endif; ?>
                                        
                                        <!-- Badge de pedido relacionado -->
                                        <?php if ($mensaje['numero_pedido']): ?>
                                            <span class="badge bg-info ms-2">
                                                <i class="bi bi-tag me-1"></i>#<?php echo $mensaje['numero_pedido']; ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <!-- Archivo adjunto -->
                                        <?php if ($mensaje['archivo_adjunto']): ?>
                                            <span class="badge bg-dark ms-2">
                                                <i class="bi bi-paperclip"></i>
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
                                            
                                            <?php if ($mensaje['archivo_adjunto']): ?>
                                                <hr>
                                                <a href="../uploads/mensajes/<?php echo $mensaje['archivo_adjunto']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   download>
                                                    <i class="bi bi-download me-1"></i>Descargar tu archivo adjunto
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Respuesta del admin (si existe) -->
                                    <?php if ($mensaje['respuesta']): ?>
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-2">
                                                <strong>Respuesta del administrador:</strong>
                                                <?php if ($mensaje['fecha_respuesta']): ?>
                                                    - <?php echo date('d/m/Y H:i', strtotime($mensaje['fecha_respuesta'])); ?>
                                                <?php endif; ?>
                                            </small>
                                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                                <?php echo nl2br(htmlspecialchars($mensaje['respuesta'])); ?>
                                                
                                                <?php if ($mensaje['archivo_respuesta']): ?>
                                                    <hr>
                                                    <a href="../uploads/mensajes/<?php echo $mensaje['archivo_respuesta']; ?>" 
                                                       class="btn btn-sm btn-outline-success" 
                                                       download>
                                                        <i class="bi bi-download me-1"></i>Descargar archivo del admin
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Marcar como leído -->
                                        <?php if ($mensaje['leido_gerente'] == 0): ?>
                                            <a href="?marcar_leido=<?php echo $mensaje['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-check2 me-1"></i>Marcar como Leído
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="alert alert-warning mb-0">
                                            <i class="bi bi-clock-history me-2"></i>
                                            El administrador aún no ha respondido este mensaje. Te notificaremos cuando responda.
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

<?php require_once('includes/footer-gerente.php'); ?>

<script>
// Ayuda contextual según tipo de mensaje
document.getElementById('tipo_mensaje')?.addEventListener('change', function() {
    const asunto = document.getElementById('asunto');
    const mensaje = document.getElementById('mensaje');
    
    switch(this.value) {
        case 'pedido_mercaderia':
            asunto.placeholder = 'Ej: Necesito stock de zapatillas One Foot talle 40';
            mensaje.placeholder = 'Detalla qué productos necesitas, cantidades, talles, colores, etc.';
            break;
        case 'transferencia':
            asunto.placeholder = 'Ej: Solicito transferencia de productos desde sucursal Centro';
            mensaje.placeholder = 'Indica qué productos necesitas, de qué sucursal, y cuándo los necesitas.';
            break;
        case 'consulta':
            asunto.placeholder = 'Ej: Consulta sobre procedimiento de cierre de caja';
            mensaje.placeholder = 'Escribe tu consulta detalladamente...';
            break;
        case 'reporte':
            asunto.placeholder = 'Ej: Problema con sistema de punto de venta';
            mensaje.placeholder = 'Describe el problema que estás experimentando...';
            break;
        default:
            asunto.placeholder = 'Escribe el asunto de tu mensaje';
            mensaje.placeholder = 'Escribe tu mensaje aquí...';
    }
});
</script>
