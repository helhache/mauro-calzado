<?php
/**
 * ADMIN/TRANSFERENCIAS.PHP
 * Gestión de transferencias de stock entre sucursales
 * El admin aprueba (pendiente->en_transito) y confirma recepción (en_transito->recibido)
 */

require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

$titulo_pagina = "Transferencias de Stock";

// Filtro de estado
$filtro_estado = isset($_GET['estado']) ? limpiarDato($_GET['estado']) : 'pendiente';
$estados_validos = ['pendiente', 'en_transito', 'recibido', 'cancelado', 'todas'];
if (!in_array($filtro_estado, $estados_validos, true)) {
    $filtro_estado = 'pendiente';
}

// Query
if ($filtro_estado === 'todas') {
    $stmt = mysqli_prepare($conn,
        "SELECT t.*,
                p.nombre AS producto_nombre, p.imagen AS producto_imagen,
                so.nombre AS sucursal_origen_nombre,
                sd.nombre AS sucursal_destino_nombre,
                u.nombre AS gerente_nombre,
                a.nombre AS admin_nombre
         FROM transferencias_stock t
         INNER JOIN productos p   ON t.producto_id          = p.id
         INNER JOIN sucursales so ON t.sucursal_origen_id   = so.id
         INNER JOIN sucursales sd ON t.sucursal_destino_id  = sd.id
         INNER JOIN usuarios u    ON t.solicitado_por       = u.id
         LEFT  JOIN usuarios a    ON t.autorizado_por       = a.id
         ORDER BY FIELD(t.estado,'pendiente','en_transito','recibido','cancelado'), t.fecha_solicitud DESC"
    );
    mysqli_stmt_execute($stmt);
} else {
    $stmt = mysqli_prepare($conn,
        "SELECT t.*,
                p.nombre AS producto_nombre, p.imagen AS producto_imagen,
                so.nombre AS sucursal_origen_nombre,
                sd.nombre AS sucursal_destino_nombre,
                u.nombre AS gerente_nombre,
                a.nombre AS admin_nombre
         FROM transferencias_stock t
         INNER JOIN productos p   ON t.producto_id          = p.id
         INNER JOIN sucursales so ON t.sucursal_origen_id   = so.id
         INNER JOIN sucursales sd ON t.sucursal_destino_id  = sd.id
         INNER JOIN usuarios u    ON t.solicitado_por       = u.id
         LEFT  JOIN usuarios a    ON t.autorizado_por       = a.id
         WHERE t.estado = ?
         ORDER BY t.fecha_solicitud DESC"
    );
    mysqli_stmt_bind_param($stmt, 's', $filtro_estado);
    mysqli_stmt_execute($stmt);
}

$transferencias = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Badge de pendientes
$stmt_pend = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM transferencias_stock WHERE estado = 'pendiente'");
mysqli_stmt_execute($stmt_pend);
$pendientes = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_pend))['total'];
mysqli_stmt_close($stmt_pend);

$badge_estados = [
    'pendiente'   => 'bg-warning text-dark',
    'en_transito' => 'bg-info text-dark',
    'recibido'    => 'bg-success',
    'cancelado'   => 'bg-secondary',
];
$label_estados = [
    'pendiente'   => 'Pendiente',
    'en_transito' => 'En tránsito',
    'recibido'    => 'Recibido',
    'cancelado'   => 'Cancelado',
];

require_once('includes/header-admin.php');
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light p-3 mb-4">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Transferencias de Stock</li>
    </ol>
</nav>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-arrow-left-right me-2"></i>Transferencias de Stock</h2>
            <p class="text-muted">Gestiona las solicitudes de transferencia de stock entre sucursales</p>
        </div>
    </div>

    <!-- Filtros de estado -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex gap-2 flex-wrap">
            <a href="transferencias.php?estado=pendiente"
               class="btn <?php echo $filtro_estado === 'pendiente' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                <i class="bi bi-clock me-1"></i>Pendientes
                <?php if ($pendientes > 0): ?>
                    <span class="badge bg-danger ms-1"><?php echo $pendientes; ?></span>
                <?php endif; ?>
            </a>
            <a href="transferencias.php?estado=en_transito"
               class="btn <?php echo $filtro_estado === 'en_transito' ? 'btn-info' : 'btn-outline-info'; ?>">
                <i class="bi bi-truck me-1"></i>En tránsito
            </a>
            <a href="transferencias.php?estado=recibido"
               class="btn <?php echo $filtro_estado === 'recibido' ? 'btn-success' : 'btn-outline-success'; ?>">
                <i class="bi bi-check2-circle me-1"></i>Recibidas
            </a>
            <a href="transferencias.php?estado=cancelado"
               class="btn <?php echo $filtro_estado === 'cancelado' ? 'btn-secondary' : 'btn-outline-secondary'; ?>">
                <i class="bi bi-x-circle me-1"></i>Canceladas
            </a>
            <a href="transferencias.php?estado=todas"
               class="btn <?php echo $filtro_estado === 'todas' ? 'btn-dark' : 'btn-outline-dark'; ?>">
                <i class="bi bi-list-ul me-1"></i>Todas
            </a>
        </div>
    </div>

    <?php if (empty($transferencias)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-arrow-left-right display-1 text-muted d-block mb-3"></i>
                <h5>No hay transferencias <?php echo $filtro_estado !== 'todas' ? $label_estados[$filtro_estado] ?? '' : ''; ?></h5>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Producto</th>
                                <th>Origen</th>
                                <th>Destino</th>
                                <th>Cantidad</th>
                                <th>Estado</th>
                                <th>Solicitado por</th>
                                <th>Fechas</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transferencias as $t): ?>
                                <tr>
                                    <td class="text-muted small"><?php echo $t['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if ($t['producto_imagen']): ?>
                                                <img src="../img/productos/<?php echo htmlspecialchars($t['producto_imagen']); ?>"
                                                     width="40" height="40" class="rounded object-fit-cover"
                                                     onerror="this.style.display='none'">
                                            <?php endif; ?>
                                            <div>
                                                <div><?php echo htmlspecialchars($t['producto_nombre']); ?></div>
                                                <?php if ($t['motivo']): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars(mb_strimwidth($t['motivo'], 0, 50, '...')); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($t['sucursal_origen_nombre']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($t['sucursal_destino_nombre']); ?></span>
                                    </td>
                                    <td><strong><?php echo $t['cantidad']; ?></strong> unid.</td>
                                    <td>
                                        <span class="badge <?php echo $badge_estados[$t['estado']] ?? 'bg-secondary'; ?>">
                                            <?php echo $label_estados[$t['estado']] ?? $t['estado']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($t['gerente_nombre']); ?></div>
                                        <?php if ($t['admin_nombre']): ?>
                                            <small class="text-muted">Autorizado: <?php echo htmlspecialchars($t['admin_nombre']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted">
                                        <div>Sol: <?php echo date('d/m/Y', strtotime($t['fecha_solicitud'])); ?></div>
                                        <?php if ($t['fecha_envio']): ?>
                                            <div>Env: <?php echo date('d/m/Y', strtotime($t['fecha_envio'])); ?></div>
                                        <?php endif; ?>
                                        <?php if ($t['fecha_recepcion']): ?>
                                            <div>Rec: <?php echo date('d/m/Y', strtotime($t['fecha_recepcion'])); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <?php if ($t['estado'] === 'pendiente'): ?>
                                            <button class="btn btn-sm btn-info me-1"
                                                    onclick="cambiarEstado(<?php echo $t['id']; ?>, 'en_transito', '<?php echo htmlspecialchars($t['producto_nombre'], ENT_QUOTES); ?>')">
                                                <i class="bi bi-truck me-1"></i>Enviar
                                            </button>
                                            <button class="btn btn-sm btn-danger"
                                                    onclick="cambiarEstado(<?php echo $t['id']; ?>, 'cancelado', '<?php echo htmlspecialchars($t['producto_nombre'], ENT_QUOTES); ?>')">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        <?php elseif ($t['estado'] === 'en_transito'): ?>
                                            <button class="btn btn-sm btn-success me-1"
                                                    onclick="cambiarEstado(<?php echo $t['id']; ?>, 'recibido', '<?php echo htmlspecialchars($t['producto_nombre'], ENT_QUOTES); ?>')">
                                                <i class="bi bi-check2 me-1"></i>Recibido
                                            </button>
                                            <button class="btn btn-sm btn-danger"
                                                    onclick="cambiarEstado(<?php echo $t['id']; ?>, 'cancelado', '<?php echo htmlspecialchars($t['producto_nombre'], ENT_QUOTES); ?>')">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
async function cambiarEstado(id, estado, nombreProducto) {
    const labels = { en_transito: 'En tránsito', recibido: 'Recibido', cancelado: 'Cancelado' };
    const confirmaciones = {
        en_transito: `¿Aprobar y marcar como enviada la transferencia de "${nombreProducto}"?\nEsto descontará el stock de la sucursal origen.`,
        recibido:    `¿Confirmar recepción de "${nombreProducto}"?\nEsto sumará el stock a la sucursal destino.`,
        cancelado:   `¿Cancelar la transferencia de "${nombreProducto}"?`
    };

    if (!confirm(confirmaciones[estado])) return;

    try {
        const resp = await fetch('ajax/actualizar-transferencia.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, estado })
        });
        const data = await resp.json();

        if (data.success) {
            alert(data.mensaje);
            location.reload();
        } else {
            alert('Error: ' + data.mensaje);
        }
    } catch (e) {
        alert('Error de conexión');
    }
}
</script>

<?php require_once('includes/footer-admin.php'); ?>
