<?php
/**
 * PANEL ADMIN - GESTIÓN DE SUCURSALES
 * Permite al admin ver, crear, editar y gestionar todas las sucursales
 */

require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

$titulo_pagina = "Gestión de Sucursales";

// Obtener todas las sucursales con información de gerentes y turnos
$sql = "SELECT 
            s.*,
            u.nombre as gerente_nombre,
            u.apellido as gerente_apellido,
            u.email as gerente_email,
            u.telefono as gerente_telefono,
            (SELECT COUNT(*) FROM stock_sucursal ss WHERE ss.sucursal_id = s.id) as productos_stock,
            (SELECT SUM(ss.cantidad) FROM stock_sucursal ss WHERE ss.sucursal_id = s.id) as total_pares,
            (SELECT COUNT(*) FROM turnos_caja tc WHERE tc.sucursal_id = s.id AND tc.estado = 'abierto') as turnos_abiertos,
            (SELECT SUM(tc.venta_total_dia) FROM turnos_caja tc WHERE tc.sucursal_id = s.id AND DATE(tc.fecha_apertura) = CURDATE()) as ventas_hoy
        FROM sucursales s
        LEFT JOIN usuarios u ON u.sucursal_id = s.id AND u.rol_id = " . ROL_GERENTE . " AND u.activo = 1
        ORDER BY s.activo DESC, s.nombre ASC";

$resultado = mysqli_query($conn, $sql);
$sucursales = [];
while ($row = mysqli_fetch_assoc($resultado)) {
    $sucursales[] = $row;
}

include('includes/header-admin.php');
?>

<!-- Header de página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Gestión de Sucursales</h2>
        <p class="text-muted mb-0">Administra todas las sucursales de la red</p>
    </div>
    <button class="btn btn-success" onclick="modalNuevaSucursal()">
        <i class="bi bi-plus-circle me-2"></i>Nueva Sucursal
    </button>
</div>

<!-- Estadísticas rápidas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                            <i class="bi bi-shop fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Total Sucursales</h6>
                        <h3 class="mb-0"><?php echo count($sucursales); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-success bg-opacity-10 text-success rounded-3 p-3">
                            <i class="bi bi-check-circle fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Activas</h6>
                        <h3 class="mb-0"><?php echo count(array_filter($sucursales, fn($s) => $s['activo'] == 1)); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3">
                            <i class="bi bi-clock-history fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Turnos Abiertos</h6>
                        <h3 class="mb-0"><?php echo array_sum(array_column($sucursales, 'turnos_abiertos')); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-info bg-opacity-10 text-info rounded-3 p-3">
                            <i class="bi bi-currency-dollar fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Ventas Hoy</h6>
                        <h3 class="mb-0">$<?php echo number_format(array_sum(array_column($sucursales, 'ventas_hoy')), 0, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" id="buscarSucursal" class="form-control" placeholder="Buscar sucursal...">
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filtroEstado">
                    <option value="">Todos los estados</option>
                    <option value="1">Activas</option>
                    <option value="0">Inactivas</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filtroCiudad">
                    <option value="">Todas las ciudades</option>
                    <?php
                    $ciudades = array_unique(array_column($sucursales, 'ciudad'));
                    foreach ($ciudades as $ciudad) {
                        echo "<option value='$ciudad'>$ciudad</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100" onclick="limpiarFiltros()">
                    <i class="bi bi-x-circle me-2"></i>Limpiar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de sucursales -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tablaSucursales">
                <thead class="table-light">
                    <tr>
                        <th>Sucursal</th>
                        <th>Gerente</th>
                        <th>Ubicación</th>
                        <th>Horarios</th>
                        <th>Stock</th>
                        <th>Turno</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sucursales as $sucursal): ?>
                    <tr data-activo="<?php echo $sucursal['activo']; ?>" data-ciudad="<?php echo $sucursal['ciudad']; ?>">
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3">
                                    <i class="bi bi-shop"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($sucursal['nombre']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($sucursal['direccion']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($sucursal['gerente_nombre']): ?>
                                <div>
                                    <strong><?php echo htmlspecialchars($sucursal['gerente_nombre'] . ' ' . $sucursal['gerente_apellido']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($sucursal['gerente_email']); ?></small>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-warning">Sin gerente asignado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <i class="bi bi-geo-alt text-muted me-1"></i>
                            <?php echo htmlspecialchars($sucursal['ciudad'] . ', ' . $sucursal['provincia']); ?>
                            <br><small class="text-muted">CP: <?php echo htmlspecialchars($sucursal['codigo_postal']); ?></small>
                        </td>
                        <td>
                            <small>
                                <i class="bi bi-clock me-1"></i>Mañana: <?php echo substr($sucursal['horario_apertura_manana'], 0, 5); ?> - <?php echo substr($sucursal['horario_cierre_manana'], 0, 5); ?>
                                <br><i class="bi bi-clock me-1"></i>Tarde: <?php echo substr($sucursal['horario_apertura_tarde'], 0, 5); ?> - <?php echo substr($sucursal['horario_cierre_tarde'], 0, 5); ?>
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo number_format($sucursal['total_pares'] ?? 0); ?> pares</span>
                            <br><small class="text-muted"><?php echo $sucursal['productos_stock'] ?? 0; ?> productos</small>
                        </td>
                        <td>
                            <?php if ($sucursal['turnos_abiertos'] > 0): ?>
                                <span class="badge bg-success">
                                    <i class="bi bi-unlock me-1"></i>Abierto
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">
                                    <i class="bi bi-lock me-1"></i>Cerrado
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($sucursal['activo']): ?>
                                <span class="badge bg-success">Activa</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactiva</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="verEstadisticas(<?php echo $sucursal['id']; ?>)" title="Ver estadísticas">
                                    <i class="bi bi-graph-up"></i>
                                </button>
                                <button class="btn btn-outline-info" onclick="editarSucursal(<?php echo $sucursal['id']; ?>)" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-<?php echo $sucursal['activo'] ? 'danger' : 'success'; ?>" 
                                        onclick="cambiarEstado(<?php echo $sucursal['id']; ?>, <?php echo $sucursal['activo'] ? 0 : 1; ?>)" 
                                        title="<?php echo $sucursal['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                    <i class="bi bi-<?php echo $sucursal['activo'] ? 'x-circle' : 'check-circle'; ?>"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Nueva/Editar Sucursal -->
<div class="modal fade" id="modalSucursal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSucursalTitulo">Nueva Sucursal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSucursal">
                <input type="hidden" id="sucursal_id" name="sucursal_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Información básica -->
                        <div class="col-12">
                            <h6 class="text-muted mb-3"><i class="bi bi-info-circle me-2"></i>Información Básica</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Dirección <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="direccion" name="direccion" required>
                        </div>
                        
                        <!-- Ubicación -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-geo-alt me-2"></i>Ubicación</h6>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Ciudad <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ciudad" name="ciudad" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Provincia <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="provincia" name="provincia" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Código Postal</label>
                            <input type="text" class="form-control" id="codigo_postal" name="codigo_postal">
                        </div>
                        
                        <!-- Horarios -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-clock me-2"></i>Horarios de Atención</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Apertura Mañana</label>
                            <input type="time" class="form-control" id="horario_apertura_manana" name="horario_apertura_manana" value="09:00">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Cierre Mañana</label>
                            <input type="time" class="form-control" id="horario_cierre_manana" name="horario_cierre_manana" value="13:00">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Apertura Tarde</label>
                            <input type="time" class="form-control" id="horario_apertura_tarde" name="horario_apertura_tarde" value="17:30">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Cierre Tarde</label>
                            <input type="time" class="form-control" id="horario_cierre_tarde" name="horario_cierre_tarde" value="21:30">
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="trabaja_sabado" name="trabaja_sabado" value="1" checked>
                                <label class="form-check-label" for="trabaja_sabado">
                                    Trabaja los sábados
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="trabaja_domingo" name="trabaja_domingo" value="1">
                                <label class="form-check-label" for="trabaja_domingo">
                                    Trabaja los domingos
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-2"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Estadísticas de Sucursal -->
<div class="modal fade" id="modalEstadisticas" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Estadísticas de Sucursal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenidoEstadisticas">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Búsqueda en tiempo real
document.getElementById('buscarSucursal').addEventListener('input', function() {
    filtrarTabla();
});

document.getElementById('filtroEstado').addEventListener('change', function() {
    filtrarTabla();
});

document.getElementById('filtroCiudad').addEventListener('change', function() {
    filtrarTabla();
});

function filtrarTabla() {
    const busqueda = document.getElementById('buscarSucursal').value.toLowerCase();
    const estado = document.getElementById('filtroEstado').value;
    const ciudad = document.getElementById('filtroCiudad').value;
    const filas = document.querySelectorAll('#tablaSucursales tbody tr');
    
    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        const activoFila = fila.getAttribute('data-activo');
        const ciudadFila = fila.getAttribute('data-ciudad');
        
        let mostrar = true;
        
        if (busqueda && !texto.includes(busqueda)) {
            mostrar = false;
        }
        
        if (estado && activoFila !== estado) {
            mostrar = false;
        }
        
        if (ciudad && ciudadFila !== ciudad) {
            mostrar = false;
        }
        
        fila.style.display = mostrar ? '' : 'none';
    });
}

function limpiarFiltros() {
    document.getElementById('buscarSucursal').value = '';
    document.getElementById('filtroEstado').value = '';
    document.getElementById('filtroCiudad').value = '';
    filtrarTabla();
}

function modalNuevaSucursal() {
    document.getElementById('modalSucursalTitulo').textContent = 'Nueva Sucursal';
    document.getElementById('formSucursal').reset();
    document.getElementById('sucursal_id').value = '';
    new bootstrap.Modal(document.getElementById('modalSucursal')).show();
}

function editarSucursal(id) {
    fetch(`ajax/obtener-sucursal.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalSucursalTitulo').textContent = 'Editar Sucursal';
                document.getElementById('sucursal_id').value = data.sucursal.id;
                document.getElementById('nombre').value = data.sucursal.nombre;
                document.getElementById('telefono').value = data.sucursal.telefono || '';
                document.getElementById('email').value = data.sucursal.email || '';
                document.getElementById('direccion').value = data.sucursal.direccion;
                document.getElementById('ciudad').value = data.sucursal.ciudad;
                document.getElementById('provincia').value = data.sucursal.provincia;
                document.getElementById('codigo_postal').value = data.sucursal.codigo_postal || '';
                document.getElementById('horario_apertura_manana').value = data.sucursal.horario_apertura_manana;
                document.getElementById('horario_cierre_manana').value = data.sucursal.horario_cierre_manana;
                document.getElementById('horario_apertura_tarde').value = data.sucursal.horario_apertura_tarde;
                document.getElementById('horario_cierre_tarde').value = data.sucursal.horario_cierre_tarde;
                document.getElementById('trabaja_sabado').checked = data.sucursal.trabaja_sabado == 1;
                document.getElementById('trabaja_domingo').checked = data.sucursal.trabaja_domingo == 1;
                
                new bootstrap.Modal(document.getElementById('modalSucursal')).show();
            } else {
                MC.alert('Error al cargar datos de la sucursal', 'danger');
            }
        });
}

// Guardar sucursal
document.getElementById('formSucursal').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('ajax/guardar-sucursal.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalSucursal')).hide();
            MC.alert('Sucursal guardada exitosamente', 'success');
            location.reload();
        } else {
            MC.alert(data.message || 'Error al guardar la sucursal', 'danger');
        }
    })
    .catch(err => {
        console.error(err);
        MC.alert('Error al procesar la solicitud', 'danger');
    });
});

function cambiarEstado(id, nuevoEstado) {
    MC.confirm('¿Estás seguro de cambiar el estado de esta sucursal?', function(ok) {
        if (!ok) return;
        fetch('ajax/cambiar-estado-sucursal.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: id, activo: nuevoEstado})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                MC.alert(data.message || 'Error al cambiar estado', 'danger');
            }
        });
    }, { tipo: 'warning', titulo: 'Cambiar estado', btnOk: 'Sí, cambiar' });
}

function verEstadisticas(id) {
    const modal = new bootstrap.Modal(document.getElementById('modalEstadisticas'));
    modal.show();
    
    fetch(`ajax/obtener-estadisticas-sucursal.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('contenidoEstadisticas').innerHTML = data.html;
            } else {
                document.getElementById('contenidoEstadisticas').innerHTML = '<div class="alert alert-danger">Error al cargar estadísticas</div>';
            }
        });
}
</script>

<?php include('includes/footer-admin.php'); ?>
