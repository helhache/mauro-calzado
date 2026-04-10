<?php
/**
 * GERENTE/TRANSFERENCIAS.PHP
 * El gerente solicita transferencias de stock hacia otra sucursal
 * y ve el estado de sus solicitudes
 */

require_once '../includes/config.php';
require_once '../includes/verificar-gerente.php';

$titulo_pagina = 'Transferencias de Stock';
$sucursal_id   = obtenerSucursalGerente();

// Obtener productos con stock en esta sucursal
$stmt_productos = mysqli_prepare($conn,
    "SELECT p.id, p.nombre, p.imagen, ss.cantidad
     FROM stock_sucursal ss
     INNER JOIN productos p ON ss.producto_id = p.id
     WHERE ss.sucursal_id = ? AND ss.cantidad > 0 AND p.activo = 1
     ORDER BY p.nombre"
);
mysqli_stmt_bind_param($stmt_productos, 'i', $sucursal_id);
mysqli_stmt_execute($stmt_productos);
$productos = mysqli_fetch_all(mysqli_stmt_get_result($stmt_productos), MYSQLI_ASSOC);
mysqli_stmt_close($stmt_productos);

// Obtener otras sucursales activas
$stmt_sucs = mysqli_prepare($conn,
    "SELECT id, nombre FROM sucursales WHERE id != ? AND activo = 1 ORDER BY nombre"
);
mysqli_stmt_bind_param($stmt_sucs, 'i', $sucursal_id);
mysqli_stmt_execute($stmt_sucs);
$otras_sucursales = mysqli_fetch_all(mysqli_stmt_get_result($stmt_sucs), MYSQLI_ASSOC);
mysqli_stmt_close($stmt_sucs);

// Historial de transferencias de esta sucursal (como origen o destino)
$stmt_hist = mysqli_prepare($conn,
    "SELECT t.*,
            p.nombre AS producto_nombre,
            so.nombre AS origen_nombre,
            sd.nombre AS destino_nombre
     FROM transferencias_stock t
     INNER JOIN productos p   ON t.producto_id         = p.id
     INNER JOIN sucursales so ON t.sucursal_origen_id  = so.id
     INNER JOIN sucursales sd ON t.sucursal_destino_id = sd.id
     WHERE t.sucursal_origen_id = ? OR t.sucursal_destino_id = ?
     ORDER BY t.fecha_solicitud DESC
     LIMIT 50"
);
mysqli_stmt_bind_param($stmt_hist, 'ii', $sucursal_id, $sucursal_id);
mysqli_stmt_execute($stmt_hist);
$historial = mysqli_fetch_all(mysqli_stmt_get_result($stmt_hist), MYSQLI_ASSOC);
mysqli_stmt_close($stmt_hist);

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

include('includes/header-gerente.php');
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light p-3 mb-4">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Transferencias</li>
    </ol>
</nav>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-arrow-left-right me-2"></i>Transferencias de Stock</h2>
            <p class="text-muted">Solicita el envío de stock hacia otra sucursal</p>
        </div>
    </div>

    <div class="row g-4">

        <!-- Formulario nueva solicitud -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent fw-bold">
                    <i class="bi bi-plus-circle me-2 text-primary"></i>Nueva Solicitud
                </div>
                <div class="card-body">
                    <?php if (empty($otras_sucursales)): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            No hay otras sucursales activas disponibles.
                        </div>
                    <?php elseif (empty($productos)): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            No tienes productos con stock disponible para transferir.
                        </div>
                    <?php else: ?>
                        <div id="alerta-form"></div>
                        <form id="form-transferencia">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Producto</label>
                                <select class="form-select" id="producto_id" required>
                                    <option value="">Selecciona un producto</option>
                                    <?php foreach ($productos as $prod): ?>
                                        <option value="<?php echo $prod['id']; ?>"
                                                data-stock="<?php echo $prod['cantidad']; ?>">
                                            <?php echo htmlspecialchars($prod['nombre']); ?>
                                            (Stock: <?php echo $prod['cantidad']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Sucursal Destino</label>
                                <select class="form-select" id="sucursal_destino_id" required>
                                    <option value="">Selecciona destino</option>
                                    <?php foreach ($otras_sucursales as $suc): ?>
                                        <option value="<?php echo $suc['id']; ?>">
                                            <?php echo htmlspecialchars($suc['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Cantidad</label>
                                <input type="number" class="form-control" id="cantidad"
                                       min="1" max="9999" required placeholder="Ej: 5">
                                <small class="text-muted" id="stock-disponible"></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Motivo <span class="text-muted fw-normal">(opcional)</span></label>
                                <textarea class="form-control" id="motivo" rows="2"
                                          placeholder="Ej: Reposición por stock bajo"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100" id="btn-solicitar">
                                <i class="bi bi-send me-2"></i>Enviar Solicitud
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Historial -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent fw-bold">
                    <i class="bi bi-clock-history me-2 text-secondary"></i>Historial de Transferencias
                </div>
                <div class="card-body p-0">
                    <?php if (empty($historial)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-arrow-left-right display-1 text-muted d-block mb-3"></i>
                            <p class="text-muted">Aún no hay transferencias registradas para tu sucursal</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Origen</th>
                                        <th>Destino</th>
                                        <th>Cantidad</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historial as $h): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($h['producto_nombre']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $h['sucursal_origen_id'] == $sucursal_id ? 'bg-primary' : 'bg-secondary'; ?>">
                                                    <?php echo htmlspecialchars($h['origen_nombre']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $h['sucursal_destino_id'] == $sucursal_id ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo htmlspecialchars($h['destino_nombre']); ?>
                                                </span>
                                            </td>
                                            <td><strong><?php echo $h['cantidad']; ?></strong></td>
                                            <td>
                                                <span class="badge <?php echo $badge_estados[$h['estado']] ?? 'bg-secondary'; ?>">
                                                    <?php echo $label_estados[$h['estado']] ?? $h['estado']; ?>
                                                </span>
                                            </td>
                                            <td class="small text-muted">
                                                <?php echo date('d/m/Y', strtotime($h['fecha_solicitud'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Actualizar stock disponible al seleccionar producto
document.getElementById('producto_id')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const stock = opt.dataset.stock;
    const info = document.getElementById('stock-disponible');
    const input = document.getElementById('cantidad');
    if (stock) {
        info.textContent = `Stock disponible: ${stock} unidades`;
        input.max = stock;
    } else {
        info.textContent = '';
        input.removeAttribute('max');
    }
});

document.getElementById('form-transferencia')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn = document.getElementById('btn-solicitar');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

    const payload = {
        producto_id:        parseInt(document.getElementById('producto_id').value),
        sucursal_destino_id: parseInt(document.getElementById('sucursal_destino_id').value),
        cantidad:           parseInt(document.getElementById('cantidad').value),
        motivo:             document.getElementById('motivo').value.trim()
    };

    try {
        const resp = await fetch('ajax/solicitar-transferencia.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await resp.json();

        const alerta = document.getElementById('alerta-form');
        if (data.success) {
            alerta.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>${data.mensaje}</div>`;
            document.getElementById('form-transferencia').reset();
            document.getElementById('stock-disponible').textContent = '';
            setTimeout(() => location.reload(), 2000);
        } else {
            alerta.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${data.mensaje}</div>`;
        }
    } catch (err) {
        document.getElementById('alerta-form').innerHTML = '<div class="alert alert-danger">Error de conexión</div>';
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send me-2"></i>Enviar Solicitud';
});
</script>

<?php require_once('includes/footer-gerente.php'); ?>
