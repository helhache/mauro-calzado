<?php
/**
 * ADMIN/MENSAJES.PHP - GESTIÓN COMPLETA DE MENSAJES
 * 
 * Funcionalidades:
 * - Ver mensajes de CLIENTES (tabla mensajes_internos)
 * - Ver mensajes de GERENTES (tabla mensajes_gerente_admin)
 * - Responder con texto y archivos adjuntos
 * - Filtrar por estado, tipo, prioridad
 * - Marcar como cerrado
 * - Descargar archivos adjuntos
 */

require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

$titulo_pagina = "Mensajes";
$admin_id = $_SESSION['usuario_id'];

// Tab activo
$tab = $_GET['tab'] ?? 'clientes';
$mensaje_id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Filtros — valores seguros por whitelist
$estados_validos = ['todos', 'pendiente', 'respondido', 'cerrado'];
$tipos_validos   = ['todos', 'consulta', 'pedido', 'reclamo', 'sugerencia', 'otro'];
$filtro_estado = in_array($_GET['estado'] ?? '', $estados_validos) ? $_GET['estado'] : 'todos';
$filtro_tipo   = in_array($_GET['tipo']   ?? '', $tipos_validos)   ? $_GET['tipo']   : 'todos';

$errores = [];
$success = '';

// PROCESAR RESPUESTA A CLIENTE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['responder_cliente'])) {
    
    $mensaje_id = intval($_POST['mensaje_id']);
    $respuesta = limpiarDato($_POST['respuesta']);
    $cerrar = isset($_POST['cerrar_mensaje']);
    
    // Manejo de archivo adjunto
    $archivo_respuesta = null;
    if (isset($_FILES['archivo_respuesta']) && $_FILES['archivo_respuesta']['error'] == 0) {
        $archivo = $_FILES['archivo_respuesta'];
        
        // Validar tamaño (5MB)
        if ($archivo['size'] <= 5 * 1024 * 1024) {
            $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
            $nombre_archivo = 'resp_cliente_' . $mensaje_id . '_' . time() . '.' . $extension;
            $ruta_destino = '../uploads/mensajes/' . $nombre_archivo;
            
            if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
                $archivo_respuesta = $nombre_archivo;
            }
        } else {
            $errores[] = 'El archivo no debe superar 5MB';
        }
    }
    
    if (empty($errores) && !empty($respuesta)) {
        $nuevo_estado = $cerrar ? 'cerrado' : 'respondido';
        
        $stmt = mysqli_prepare($conn,
            "UPDATE mensajes_internos 
             SET respuesta = ?, archivo_respuesta = ?, estado = ?, respondido_por = ?, fecha_respuesta = NOW()
             WHERE id = ?"
        );
        
        mysqli_stmt_bind_param($stmt, "sssii", $respuesta, $archivo_respuesta, $nuevo_estado, $admin_id, $mensaje_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Respuesta enviada correctamente';
        }
        
        mysqli_stmt_close($stmt);
    }
}

// PROCESAR RESPUESTA A GERENTE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['responder_gerente'])) {
    
    $mensaje_id = intval($_POST['mensaje_id']);
    $respuesta = limpiarDato($_POST['respuesta']);
    $cerrar = isset($_POST['cerrar_mensaje']);
    
    // Manejo de archivo adjunto
    $archivo_respuesta = null;
    if (isset($_FILES['archivo_respuesta']) && $_FILES['archivo_respuesta']['error'] == 0) {
        $archivo = $_FILES['archivo_respuesta'];
        
        if ($archivo['size'] <= 5 * 1024 * 1024) {
            $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
            $nombre_archivo = 'resp_gerente_' . $mensaje_id . '_' . time() . '.' . $extension;
            $ruta_destino = '../uploads/mensajes/' . $nombre_archivo;
            
            if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
                $archivo_respuesta = $nombre_archivo;
            }
        }
    }
    
    if (!empty($respuesta)) {
        $nuevo_estado = $cerrar ? 'cerrado' : 'respondido';
        
        $stmt = mysqli_prepare($conn,
            "UPDATE mensajes_gerente_admin 
             SET respuesta = ?, archivo_respuesta = ?, estado = ?, admin_id = ?, fecha_respuesta = NOW()
             WHERE id = ?"
        );
        
        mysqli_stmt_bind_param($stmt, "sssii", $respuesta, $archivo_respuesta, $nuevo_estado, $admin_id, $mensaje_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Respuesta enviada correctamente al gerente';
        }
        
        mysqli_stmt_close($stmt);
    }
}

// OBTENER MENSAJES DE CLIENTES
$query_clientes = "SELECT m.*, 
                   u.nombre AS cliente_nombre, u.apellido AS cliente_apellido, u.email AS cliente_email,
                   p.numero_pedido
                   FROM mensajes_internos m
                   INNER JOIN usuarios u ON m.usuario_id = u.id
                   LEFT JOIN pedidos p ON m.pedido_id = p.id
                   WHERE 1=1";

if ($filtro_estado != 'todos') {
    $query_clientes .= " AND m.estado = '{$filtro_estado}'";
}

$query_clientes .= " ORDER BY 
    CASE m.estado 
        WHEN 'pendiente' THEN 1 
        WHEN 'respondido' THEN 2 
        WHEN 'cerrado' THEN 3 
    END,
    m.fecha_envio DESC";

$result_clientes = mysqli_query($conn, $query_clientes);
$mensajes_clientes = mysqli_fetch_all($result_clientes, MYSQLI_ASSOC);

// OBTENER MENSAJES DE GERENTES
$query_gerentes = "SELECT m.*, 
                   u.nombre AS gerente_nombre, u.apellido AS gerente_apellido, u.email AS gerente_email,
                   s.nombre AS sucursal_nombre, s.ciudad AS sucursal_ciudad,
                   p.numero_pedido
                   FROM mensajes_gerente_admin m
                   INNER JOIN usuarios u ON m.gerente_id = u.id
                   INNER JOIN sucursales s ON m.sucursal_id = s.id
                   LEFT JOIN pedidos p ON m.pedido_relacionado_id = p.id
                   WHERE 1=1";

if ($filtro_estado != 'todos') {
    $query_gerentes .= " AND m.estado = '{$filtro_estado}'";
}

if ($filtro_tipo != 'todos') {
    $query_gerentes .= " AND m.tipo_mensaje = '{$filtro_tipo}'";
}

$query_gerentes .= " ORDER BY 
    CASE m.prioridad 
        WHEN 'alta' THEN 1 
        WHEN 'media' THEN 2 
        WHEN 'baja' THEN 3 
    END,
    CASE m.estado 
        WHEN 'pendiente' THEN 1 
        WHEN 'respondido' THEN 2 
        WHEN 'cerrado' THEN 3 
    END,
    m.fecha_envio DESC";

$result_gerentes = mysqli_query($conn, $query_gerentes);
$mensajes_gerentes = mysqli_fetch_all($result_gerentes, MYSQLI_ASSOC);

// Estadísticas
$stats_clientes = [
    'total' => count($mensajes_clientes),
    'pendientes' => 0,
    'respondidos' => 0
];

foreach ($mensajes_clientes as $msg) {
    if ($msg['estado'] == 'pendiente') $stats_clientes['pendientes']++;
    if ($msg['estado'] == 'respondido') $stats_clientes['respondidos']++;
}

$stats_gerentes = [
    'total' => count($mensajes_gerentes),
    'pendientes' => 0,
    'respondidos' => 0,
    'alta_prioridad' => 0
];

foreach ($mensajes_gerentes as $msg) {
    if ($msg['estado'] == 'pendiente') $stats_gerentes['pendientes']++;
    if ($msg['estado'] == 'respondido') $stats_gerentes['respondidos']++;
    if ($msg['prioridad'] == 'alta') $stats_gerentes['alta_prioridad']++;
}

require_once('includes/header-admin.php');
?>

<div class="container-fluid py-4">
    
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-1">
                <i class="bi bi-chat-dots me-2"></i>Centro de Mensajes
            </h1>
            <p class="text-muted mb-0">Gestiona mensajes de clientes y gerentes</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver
        </a>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                <?php foreach ($errores as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link <?php echo $tab == 'clientes' ? 'active' : ''; ?>" 
               href="?tab=clientes"
               role="tab">
                <i class="bi bi-people me-2"></i>Mensajes de Clientes
                <?php if ($stats_clientes['pendientes'] > 0): ?>
                    <span class="badge bg-danger ms-1"><?php echo $stats_clientes['pendientes']; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?php echo $tab == 'gerentes' ? 'active' : ''; ?>" 
               href="?tab=gerentes"
               role="tab">
                <i class="bi bi-person-badge me-2"></i>Mensajes de Gerentes
                <?php if ($stats_gerentes['pendientes'] > 0): ?>
                    <span class="badge bg-warning text-dark ms-1"><?php echo $stats_gerentes['pendientes']; ?></span>
                <?php endif; ?>
                <?php if ($stats_gerentes['alta_prioridad'] > 0): ?>
                    <span class="badge bg-danger ms-1"><i class="bi bi-exclamation-triangle"></i> <?php echo $stats_gerentes['alta_prioridad']; ?></span>
                <?php endif; ?>
            </a>
        </li>
    </ul>
    
    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="<?php echo $tab; ?>">
                
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="todos" <?php echo $filtro_estado == 'todos' ? 'selected' : ''; ?>>Todos</option>
                        <option value="pendiente" <?php echo $filtro_estado == 'pendiente' ? 'selected' : ''; ?>>Pendientes</option>
                        <option value="respondido" <?php echo $filtro_estado == 'respondido' ? 'selected' : ''; ?>>Respondidos</option>
                        <option value="cerrado" <?php echo $filtro_estado == 'cerrado' ? 'selected' : ''; ?>>Cerrados</option>
                    </select>
                </div>
                
                <?php if ($tab == 'gerentes'): ?>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tipo de Mensaje</label>
                        <select name="tipo" class="form-select">
                            <option value="todos">Todos los tipos</option>
                            <option value="pedido_mercaderia" <?php echo $filtro_tipo == 'pedido_mercaderia' ? 'selected' : ''; ?>>Pedido Mercadería</option>
                            <option value="transferencia" <?php echo $filtro_tipo == 'transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                            <option value="consulta" <?php echo $filtro_tipo == 'consulta' ? 'selected' : ''; ?>>Consulta</option>
                            <option value="reporte" <?php echo $filtro_tipo == 'reporte' ? 'selected' : ''; ?>>Reporte</option>
                            <option value="otro" <?php echo $filtro_tipo == 'otro' ? 'selected' : ''; ?>>Otro</option>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- CONTENIDO DE TABS -->
    <div class="tab-content">
        
        <!-- TAB: MENSAJES DE CLIENTES -->
        <div class="tab-pane fade <?php echo $tab == 'clientes' ? 'show active' : ''; ?>">
            
            <?php if (empty($mensajes_clientes)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <h5 class="mt-3">No hay mensajes de clientes</h5>
                </div>
            <?php else: ?>
                
                <div class="row">
                    <?php foreach ($mensajes_clientes as $mensaje): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($mensaje['asunto']); ?></h6>
                                            <small class="text-muted">
                                                De: <?php echo htmlspecialchars($mensaje['cliente_nombre'] . ' ' . $mensaje['cliente_apellido']); ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php
                                            $badge_class = ['pendiente' => 'warning', 'respondido' => 'success', 'cerrado' => 'secondary'];
                                            $class = $badge_class[$mensaje['estado']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $class; ?>">
                                                <?php echo ucfirst($mensaje['estado']); ?>
                                            </span>
                                            <?php if ($mensaje['prioridad'] == 'alta'): ?>
                                                <span class="badge bg-danger ms-1">
                                                    <i class="bi bi-exclamation-triangle"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <p class="mb-2">
                                        <?php echo nl2br(htmlspecialchars(substr($mensaje['mensaje'], 0, 150))); ?>
                                        <?php if (strlen($mensaje['mensaje']) > 150): ?>...<?php endif; ?>
                                    </p>
                                    
                                    <div class="d-flex gap-2 mb-3 small text-muted">
                                        <span><i class="bi bi-calendar me-1"></i><?php echo date('d/m/Y H:i', strtotime($mensaje['fecha_envio'])); ?></span>
                                        <?php if ($mensaje['numero_pedido']): ?>
                                            <span><i class="bi bi-tag me-1"></i>Pedido #<?php echo $mensaje['numero_pedido']; ?></span>
                                        <?php endif; ?>
                                        <?php if ($mensaje['archivo_adjunto']): ?>
                                            <span><i class="bi bi-paperclip me-1"></i>Archivo</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button type="button" 
                                            class="btn btn-primary btn-sm w-100" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalCliente<?php echo $mensaje['id']; ?>">
                                        <i class="bi bi-eye me-1"></i>Ver y Responder
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modal mensaje cliente -->
                        <div class="modal fade" id="modalCliente<?php echo $mensaje['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title"><?php echo htmlspecialchars($mensaje['asunto']); ?></h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        
                                        <!-- Info del cliente -->
                                        <div class="mb-3">
                                            <strong>De:</strong> <?php echo htmlspecialchars($mensaje['cliente_nombre'] . ' ' . $mensaje['cliente_apellido']); ?><br>
                                            <strong>Email:</strong> <?php echo htmlspecialchars($mensaje['cliente_email']); ?><br>
                                            <strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($mensaje['fecha_envio'])); ?>
                                            <?php if ($mensaje['numero_pedido']): ?>
                                                <br><strong>Pedido relacionado:</strong> #<?php echo $mensaje['numero_pedido']; ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Mensaje original -->
                                        <div class="alert alert-light">
                                            <strong>Mensaje del cliente:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($mensaje['mensaje'])); ?>
                                            
                                            <?php if ($mensaje['archivo_adjunto']): ?>
                                                <hr>
                                                <a href="../uploads/mensajes/<?php echo $mensaje['archivo_adjunto']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   download>
                                                    <i class="bi bi-download me-1"></i>Descargar archivo adjunto
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Respuesta (si existe) -->
                                        <?php if ($mensaje['respuesta']): ?>
                                            <div class="alert alert-success">
                                                <strong>Tu respuesta:</strong><br>
                                                <?php echo nl2br(htmlspecialchars($mensaje['respuesta'])); ?>
                                                <br><small class="text-muted">Respondido el: <?php echo date('d/m/Y H:i', strtotime($mensaje['fecha_respuesta'])); ?></small>
                                                
                                                <?php if ($mensaje['archivo_respuesta']): ?>
                                                    <hr>
                                                    <a href="../uploads/mensajes/<?php echo $mensaje['archivo_respuesta']; ?>" 
                                                       class="btn btn-sm btn-outline-success" 
                                                       download>
                                                        <i class="bi bi-download me-1"></i>Tu archivo adjunto
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Formulario de respuesta -->
                                        <?php if ($mensaje['estado'] != 'cerrado'): ?>
                                            <form method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="mensaje_id" value="<?php echo $mensaje['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Tu Respuesta</label>
                                                    <textarea name="respuesta" 
                                                              class="form-control" 
                                                              rows="4" 
                                                              required 
                                                              placeholder="Escribe tu respuesta al cliente..."><?php echo $mensaje['respuesta'] ?? ''; ?></textarea>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Adjuntar Archivo (Opcional)</label>
                                                    <input type="file" name="archivo_respuesta" class="form-control">
                                                    <small class="text-muted">Máximo 5MB - Cualquier formato</small>
                                                </div>
                                                
                                                <div class="form-check mb-3">
                                                    <input type="checkbox" class="form-check-input" id="cerrar<?php echo $mensaje['id']; ?>" name="cerrar_mensaje">
                                                    <label class="form-check-label" for="cerrar<?php echo $mensaje['id']; ?>">
                                                        Cerrar este mensaje después de responder
                                                    </label>
                                                </div>
                                                
                                                <button type="submit" name="responder_cliente" class="btn btn-primary">
                                                    <i class="bi bi-send me-1"></i>Enviar Respuesta
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <div class="alert alert-secondary">
                                                <i class="bi bi-lock me-2"></i>Este mensaje está cerrado
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    <?php endforeach; ?>
                </div>
                
            <?php endif; ?>
        </div>
        
        <!-- TAB: MENSAJES DE GERENTES -->
        <div class="tab-pane fade <?php echo $tab == 'gerentes' ? 'show active' : ''; ?>">
            
            <?php if (empty($mensajes_gerentes)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <h5 class="mt-3">No hay mensajes de gerentes</h5>
                </div>
            <?php else: ?>
                
                <div class="row">
                    <?php foreach ($mensajes_gerentes as $mensaje): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card border-0 shadow-sm h-100 <?php echo $mensaje['prioridad'] == 'alta' ? 'border-start border-danger border-3' : ''; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($mensaje['asunto']); ?></h6>
                                            <small class="text-muted">
                                                De: <?php echo htmlspecialchars($mensaje['gerente_nombre'] . ' ' . $mensaje['gerente_apellido']); ?><br>
                                                Sucursal: <?php echo $mensaje['sucursal_nombre']; ?>
                                            </small>
                                        </div>
                                        <div>
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
                                            <span class="badge bg-<?php echo $tipo_class; ?> mb-1">
                                                <?php echo str_replace('_', ' ', ucfirst($mensaje['tipo_mensaje'])); ?>
                                            </span><br>
                                            
                                            <?php
                                            $badge_class = ['pendiente' => 'warning', 'respondido' => 'success', 'cerrado' => 'secondary'];
                                            $class = $badge_class[$mensaje['estado']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $class; ?>">
                                                <?php echo ucfirst($mensaje['estado']); ?>
                                            </span>
                                            
                                            <?php if ($mensaje['prioridad'] == 'alta'): ?>
                                                <span class="badge bg-danger ms-1">
                                                    <i class="bi bi-exclamation-triangle"></i> URGENTE
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <p class="mb-2">
                                        <?php echo nl2br(htmlspecialchars(substr($mensaje['mensaje'], 0, 150))); ?>
                                        <?php if (strlen($mensaje['mensaje']) > 150): ?>...<?php endif; ?>
                                    </p>
                                    
                                    <div class="d-flex gap-2 mb-3 small text-muted">
                                        <span><i class="bi bi-calendar me-1"></i><?php echo date('d/m/Y H:i', strtotime($mensaje['fecha_envio'])); ?></span>
                                        <?php if ($mensaje['numero_pedido']): ?>
                                            <span><i class="bi bi-tag me-1"></i>Pedido #<?php echo $mensaje['numero_pedido']; ?></span>
                                        <?php endif; ?>
                                        <?php if ($mensaje['archivo_adjunto']): ?>
                                            <span><i class="bi bi-paperclip me-1"></i>Archivo</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button type="button" 
                                            class="btn btn-primary btn-sm w-100" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalGerente<?php echo $mensaje['id']; ?>">
                                        <i class="bi bi-eye me-1"></i>Ver y Responder
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modal mensaje gerente -->
                        <div class="modal fade" id="modalGerente<?php echo $mensaje['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title"><?php echo htmlspecialchars($mensaje['asunto']); ?></h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        
                                        <!-- Info del gerente -->
                                        <div class="mb-3">
                                            <strong>De:</strong> <?php echo htmlspecialchars($mensaje['gerente_nombre'] . ' ' . $mensaje['gerente_apellido']); ?><br>
                                            <strong>Email:</strong> <?php echo htmlspecialchars($mensaje['gerente_email']); ?><br>
                                            <strong>Sucursal:</strong> <?php echo $mensaje['sucursal_nombre']; ?>, <?php echo $mensaje['sucursal_ciudad']; ?><br>
                                            <strong>Tipo:</strong> <?php echo str_replace('_', ' ', ucfirst($mensaje['tipo_mensaje'])); ?><br>
                                            <strong>Prioridad:</strong> 
                                            <span class="badge bg-<?php echo $mensaje['prioridad'] == 'alta' ? 'danger' : ($mensaje['prioridad'] == 'media' ? 'warning' : 'secondary'); ?>">
                                                <?php echo ucfirst($mensaje['prioridad']); ?>
                                            </span><br>
                                            <strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($mensaje['fecha_envio'])); ?>
                                            <?php if ($mensaje['numero_pedido']): ?>
                                                <br><strong>Pedido relacionado:</strong> #<?php echo $mensaje['numero_pedido']; ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Mensaje original -->
                                        <div class="alert alert-light">
                                            <strong>Mensaje del gerente:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($mensaje['mensaje'])); ?>
                                            
                                            <?php if ($mensaje['archivo_adjunto']): ?>
                                                <hr>
                                                <a href="../uploads/mensajes/<?php echo $mensaje['archivo_adjunto']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   download>
                                                    <i class="bi bi-download me-1"></i>Descargar archivo adjunto
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Respuesta (si existe) -->
                                        <?php if ($mensaje['respuesta']): ?>
                                            <div class="alert alert-success">
                                                <strong>Tu respuesta:</strong><br>
                                                <?php echo nl2br(htmlspecialchars($mensaje['respuesta'])); ?>
                                                <br><small class="text-muted">Respondido el: <?php echo date('d/m/Y H:i', strtotime($mensaje['fecha_respuesta'])); ?></small>
                                                
                                                <?php if ($mensaje['archivo_respuesta']): ?>
                                                    <hr>
                                                    <a href="../uploads/mensajes/<?php echo $mensaje['archivo_respuesta']; ?>" 
                                                       class="btn btn-sm btn-outline-success" 
                                                       download>
                                                        <i class="bi bi-download me-1"></i>Tu archivo adjunto
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Formulario de respuesta -->
                                        <?php if ($mensaje['estado'] != 'cerrado'): ?>
                                            <form method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="mensaje_id" value="<?php echo $mensaje['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Tu Respuesta</label>
                                                    <textarea name="respuesta" 
                                                              class="form-control" 
                                                              rows="4" 
                                                              required 
                                                              placeholder="Escribe tu respuesta al gerente..."><?php echo $mensaje['respuesta'] ?? ''; ?></textarea>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Adjuntar Archivo (Opcional)</label>
                                                    <input type="file" name="archivo_respuesta" class="form-control">
                                                    <small class="text-muted">Máximo 5MB - Cualquier formato</small>
                                                </div>
                                                
                                                <div class="form-check mb-3">
                                                    <input type="checkbox" class="form-check-input" id="cerrarGer<?php echo $mensaje['id']; ?>" name="cerrar_mensaje">
                                                    <label class="form-check-label" for="cerrarGer<?php echo $mensaje['id']; ?>">
                                                        Cerrar este mensaje después de responder
                                                    </label>
                                                </div>
                                                
                                                <button type="submit" name="responder_gerente" class="btn btn-primary">
                                                    <i class="bi bi-send me-1"></i>Enviar Respuesta
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <div class="alert alert-secondary">
                                                <i class="bi bi-lock me-2"></i>Este mensaje está cerrado
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    <?php endforeach; ?>
                </div>
                
            <?php endif; ?>
        </div>
        
    </div>
    
</div>

<?php require_once('includes/footer-admin.php'); ?>
