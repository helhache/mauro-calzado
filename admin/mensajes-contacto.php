<?php
/**
 * MENSAJES DE CONTACTO - Panel de administración
 * Permite ver, filtrar y gestionar los mensajes enviados desde contactanos.php
 */

require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

$titulo_pagina = "Mensajes de Contacto";

// Marcar como leído
if (isset($_GET['marcar'])) {
    $id = intval($_GET['marcar']);
    $stmt = mysqli_prepare($conn, "UPDATE contacto SET leido = 1 WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: mensajes-contacto.php');
    exit;
}

// Marcar como respondido
if (isset($_GET['respondido'])) {
    $id = intval($_GET['respondido']);
    $stmt = mysqli_prepare($conn, "UPDATE contacto SET respondido = 1, leido = 1 WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: mensajes-contacto.php');
    exit;
}

// Eliminar mensaje
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $stmt = mysqli_prepare($conn, "DELETE FROM contacto WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: mensajes-contacto.php');
    exit;
}

// Marcar todos como leídos
if (isset($_GET['marcar_todos'])) {
    mysqli_query($conn, "UPDATE contacto SET leido = 1 WHERE leido = 0");
    header('Location: mensajes-contacto.php');
    exit;
}

// ============================================================================
// FILTROS
// ============================================================================
$filtros_validos = ['todos', 'no_leidos', 'sin_responder', 'respondidos'];
$filtro = in_array($_GET['filtro'] ?? '', $filtros_validos) ? $_GET['filtro'] : 'todos';

$where = '1=1';
if ($filtro === 'no_leidos') {
    $where = 'leido = 0';
} elseif ($filtro === 'sin_responder') {
    $where = 'respondido = 0';
} elseif ($filtro === 'respondidos') {
    $where = 'respondido = 1';
}

$query = "SELECT * FROM contacto WHERE {$where} ORDER BY fecha_envio DESC";
$result = mysqli_query($conn, $query);

// Contadores para los badges
$total_no_leidos  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM contacto WHERE leido = 0"))['c'] ?? 0;
$total_sin_resp   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM contacto WHERE respondido = 0"))['c'] ?? 0;

require_once('includes/header-admin.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-2 fw-bold text-dark">Mensajes de Contacto</h1>
        <p class="text-muted mb-0">Mensajes recibidos desde el formulario de contacto</p>
    </div>
    <?php if ($total_no_leidos > 0): ?>
    <a href="?marcar_todos=1" class="btn btn-outline-primary">
        <i class="bi bi-check-all me-2"></i>Marcar todos como leídos
    </a>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="btn-group" role="group">
            <a href="?filtro=todos" class="btn btn-<?php echo $filtro === 'todos' ? 'primary' : 'outline-secondary'; ?>">
                Todos
            </a>
            <a href="?filtro=no_leidos" class="btn btn-<?php echo $filtro === 'no_leidos' ? 'primary' : 'outline-secondary'; ?>">
                No leídos
                <?php if ($total_no_leidos > 0): ?>
                    <span class="badge bg-danger ms-1"><?php echo $total_no_leidos; ?></span>
                <?php endif; ?>
            </a>
            <a href="?filtro=sin_responder" class="btn btn-<?php echo $filtro === 'sin_responder' ? 'primary' : 'outline-secondary'; ?>">
                Sin responder
                <?php if ($total_sin_resp > 0): ?>
                    <span class="badge bg-warning text-dark ms-1"><?php echo $total_sin_resp; ?></span>
                <?php endif; ?>
            </a>
            <a href="?filtro=respondidos" class="btn btn-<?php echo $filtro === 'respondidos' ? 'primary' : 'outline-secondary'; ?>">
                Respondidos
            </a>
        </div>
    </div>
</div>

<!-- Lista de mensajes -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="list-group list-group-flush">
                <?php while ($msg = mysqli_fetch_assoc($result)): ?>
                    <div class="list-group-item <?php echo $msg['leido'] == 0 ? 'bg-light' : ''; ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <strong><?php echo htmlspecialchars($msg['nombre']); ?></strong>
                                    <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" class="text-muted small">
                                        <?php echo htmlspecialchars($msg['email']); ?>
                                    </a>
                                    <?php if (!empty($msg['telefono'])): ?>
                                        <span class="text-muted small">| <?php echo htmlspecialchars($msg['telefono']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($msg['leido'] == 0): ?>
                                        <span class="badge bg-danger">Nuevo</span>
                                    <?php endif; ?>
                                    <?php if ($msg['respondido'] == 1): ?>
                                        <span class="badge bg-success">Respondido</span>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-1">
                                    <span class="badge bg-secondary me-2"><?php echo htmlspecialchars($msg['asunto']); ?></span>
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($msg['fecha_envio'])); ?>
                                    </small>
                                </div>
                                <p class="mb-0 text-muted small"><?php echo nl2br(htmlspecialchars($msg['mensaje'])); ?></p>
                            </div>
                            <div class="d-flex gap-1 ms-3 flex-shrink-0">
                                <?php if ($msg['leido'] == 0): ?>
                                    <a href="?marcar=<?php echo $msg['id']; ?>" class="btn btn-sm btn-outline-primary" title="Marcar como leído">
                                        <i class="bi bi-check"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($msg['respondido'] == 0): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>?subject=Re: <?php echo htmlspecialchars($msg['asunto']); ?>"
                                       onclick="setTimeout(() => window.location='?respondido=<?php echo $msg['id']; ?>', 500)"
                                       class="btn btn-sm btn-outline-success" title="Responder por email">
                                        <i class="bi bi-reply"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="?eliminar=<?php echo $msg['id']; ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('¿Eliminar este mensaje?')"
                                   title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-envelope-slash fs-1 d-block mb-3"></i>
                <h5>No hay mensajes</h5>
                <p>Cuando los clientes envíen mensajes desde el formulario de contacto, aparecerán aquí</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once('includes/footer-admin.php'); ?>
