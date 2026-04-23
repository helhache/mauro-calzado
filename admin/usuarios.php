<?php
/**
 * GESTIÓN DE USUARIOS - Panel de Administración
 * 
 * Funcionalidades:
 * - Listar usuarios con paginación
 * - Filtrar por rol
 * - Buscar por nombre/email
 * - Crear nuevo usuario
 * - Editar usuario existente
 * - Activar/Desactivar usuario
 * - Resetear contraseña
 * - Ver historial de pedidos
 */

require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

$titulo_pagina = "Gestión de Usuarios";

// ============================================================================
// CONFIGURACIÓN DE PAGINACIÓN
// ============================================================================

$registros_por_pagina = 15;
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// ============================================================================
// FILTROS Y BÚSQUEDA
// ============================================================================

$filtro_rol = isset($_GET['rol']) ? intval($_GET['rol']) : 0;
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// Construir query con filtros
$where_conditions = [];
$params = [];
$param_types = '';

if ($filtro_rol > 0) {
    $where_conditions[] = "u.rol_id = ?";
    $params[] = $filtro_rol;
    $param_types .= 'i';
}

if (!empty($busqueda)) {
    $where_conditions[] = "(u.nombre LIKE ? OR u.apellido LIKE ? OR u.email LIKE ? OR u.dni LIKE ?)";
    $busqueda_like = "%{$busqueda}%";
    $params[] = $busqueda_like;
    $params[] = $busqueda_like;
    $params[] = $busqueda_like;
    $params[] = $busqueda_like;
    $param_types .= 'ssss';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// ============================================================================
// OBTENER TOTAL DE REGISTROS (para paginación)
// ============================================================================

$query_count = "SELECT COUNT(*) as total FROM usuarios u {$where_clause}";
$stmt_count = mysqli_prepare($conn, $query_count);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_count, $param_types, ...$params);
}

mysqli_stmt_execute($stmt_count);
$result_count = mysqli_stmt_get_result($stmt_count);
$total_registros = mysqli_fetch_assoc($result_count)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);
mysqli_stmt_close($stmt_count);

// ============================================================================
// OBTENER USUARIOS
// ============================================================================

$query = "SELECT 
            u.id,
            u.rol_id,
            u.sucursal_id,
            u.nombre,
            u.apellido,
            u.email,
            u.telefono,
            u.dni,
            u.activo,
            u.fecha_registro,
            u.ultimo_acceso,
            r.nombre_rol,
            s.nombre as sucursal_nombre,
            s.ciudad as sucursal_ciudad
          FROM usuarios u
          INNER JOIN roles r ON u.rol_id = r.rol_id
          LEFT JOIN sucursales s ON u.sucursal_id = s.id
          {$where_clause}
          ORDER BY u.fecha_registro DESC
          LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $query);

// Preparar parámetros con LIMIT y OFFSET
$params_with_limit = $params;
$params_with_limit[] = $registros_por_pagina;
$params_with_limit[] = $offset;
$param_types_with_limit = $param_types . 'ii';

mysqli_stmt_bind_param($stmt, $param_types_with_limit, ...$params_with_limit);
mysqli_stmt_execute($stmt);
$result_usuarios = mysqli_stmt_get_result($stmt);

// ============================================================================
// OBTENER ESTADÍSTICAS
// ============================================================================

// Total por rol
$query_stats = "SELECT 
                  r.rol_id,
                  r.nombre_rol,
                  COUNT(u.id) as total,
                  SUM(CASE WHEN u.activo = 1 THEN 1 ELSE 0 END) as activos
                FROM roles r
                LEFT JOIN usuarios u ON r.rol_id = u.rol_id
                GROUP BY r.rol_id, r.nombre_rol
                ORDER BY r.rol_id";
$result_stats = mysqli_query($conn, $query_stats);
$stats = [];
while ($row = mysqli_fetch_assoc($result_stats)) {
    $stats[$row['rol_id']] = $row;
}

// ============================================================================
// OBTENER LISTA DE SUCURSALES (para select en modales)
// ============================================================================

$query_sucursales = "SELECT id, nombre, ciudad FROM sucursales WHERE activo = 1 ORDER BY nombre";
$result_sucursales = mysqli_query($conn, $query_sucursales);

require_once('includes/header-admin.php');
?>

<!-- Estilos específicos para esta página -->
<style>
    .user-avatar-table {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: white;
        font-size: 14px;
    }
    
    .badge-rol {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .stats-card {
        border: none;
        border-radius: 12px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .table-actions button {
        padding: 6px 10px;
        font-size: 13px;
        margin: 0 2px;
    }
    
    .search-box {
        max-width: 350px;
    }
    
    .filter-badge {
        background: #E0E7FF;
        color: #3C50E0;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 13px;
        margin-left: 8px;
    }
</style>

<!-- Contenido Principal -->
<div class="container-fluid py-4">
    
    <!-- ============================================== -->
    <!-- ESTADÍSTICAS -->
    <!-- ============================================== -->
    <div class="row g-3 mb-4">
        <?php 
        $colores_roles = [
            1 => ['bg' => '#3C50E0', 'icon' => 'bi-person'],
            2 => ['bg' => '#10B981', 'icon' => 'bi-person-badge'],
            3 => ['bg' => '#F59E0B', 'icon' => 'bi-shield-check']
        ];
        
        foreach ($stats as $rol_id => $stat): 
            $color = $colores_roles[$rol_id] ?? ['bg' => '#6B7280', 'icon' => 'bi-person'];
        ?>
        <div class="col-md-4">
            <div class="card stats-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small"><?php echo htmlspecialchars($stat['nombre_rol']); ?>s</p>
                            <h3 class="mb-0 fw-bold"><?php echo $stat['total']; ?></h3>
                            <p class="text-success mb-0 small mt-1">
                                <i class="bi bi-check-circle me-1"></i>
                                <?php echo $stat['activos']; ?> activos
                            </p>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" 
                             style="width:50px; height:50px; background:<?php echo $color['bg']; ?>20;">
                            <i class="bi <?php echo $color['icon']; ?> fs-4" style="color:<?php echo $color['bg']; ?>;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- ============================================== -->
    <!-- ENCABEZADO CON FILTROS Y BÚSQUEDA -->
    <!-- ============================================== -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 fw-bold">
                        Listado de Usuarios 
                        <span class="text-muted fw-normal">(<?php echo $total_registros; ?> total)</span>
                        <?php if ($filtro_rol > 0): ?>
                            <span class="filter-badge">
                                <i class="bi bi-funnel me-1"></i>
                                <?php echo $stats[$filtro_rol]['nombre_rol']; ?>
                            </span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario">
                        <i class="bi bi-plus-circle me-2"></i>Nuevo Usuario
                    </button>
                </div>
            </div>
            
            <!-- Filtros y Búsqueda -->
            <div class="row mt-3 g-2">
                <div class="col-md-4">
                    <div class="search-box">
                        <form method="GET" class="position-relative">
                            <input type="hidden" name="rol" value="<?php echo $filtro_rol; ?>">
                            <input type="text" 
                                   name="buscar" 
                                   class="form-control ps-5" 
                                   placeholder="Buscar por nombre, email o DNI..."
                                   value="<?php echo htmlspecialchars($busqueda); ?>">
                            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                        </form>
                    </div>
                </div>
                <div class="col-md-8 text-md-end">
                    <div class="btn-group" role="group">
                        <a href="usuarios.php" 
                           class="btn btn-sm <?php echo $filtro_rol == 0 ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                            Todos
                        </a>
                        <a href="usuarios.php?rol=1<?php echo !empty($busqueda) ? '&buscar='.urlencode($busqueda) : ''; ?>" 
                           class="btn btn-sm <?php echo $filtro_rol == 1 ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                            Clientes (<?php echo $stats[1]['total']; ?>)
                        </a>
                        <a href="usuarios.php?rol=2<?php echo !empty($busqueda) ? '&buscar='.urlencode($busqueda) : ''; ?>" 
                           class="btn btn-sm <?php echo $filtro_rol == 2 ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                            Gerentes (<?php echo $stats[2]['total']; ?>)
                        </a>
                        <a href="usuarios.php?rol=3<?php echo !empty($busqueda) ? '&buscar='.urlencode($busqueda) : ''; ?>" 
                           class="btn btn-sm <?php echo $filtro_rol == 3 ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                            Admins (<?php echo $stats[3]['total']; ?>)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ============================================== -->
    <!-- TABLA DE USUARIOS -->
    <!-- ============================================== -->
    <div class="card shadow-lg border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Usuario</th>
                            <th>Email / Teléfono</th>
                            <th>Rol</th>
                            <th>Sucursal</th>
                            <th>Registro</th>
                            <th>Estado</th>
                            <th class="text-center pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result_usuarios) > 0): ?>
                            <?php while ($usuario = mysqli_fetch_assoc($result_usuarios)): 
                                // Color del avatar según rol
                                $avatar_colors = [
                                    1 => '#3C50E0',
                                    2 => '#10B981',
                                    3 => '#F59E0B'
                                ];
                                $avatar_bg = $avatar_colors[$usuario['rol_id']] ?? '#6B7280';
                                $iniciales = strtoupper(substr($usuario['nombre'], 0, 1) . substr($usuario['apellido'], 0, 1));
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="user-avatar-table" style="background: <?php echo $avatar_bg; ?>;">
                                            <?php echo $iniciales; ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">
                                                <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>
                                            </div>
                                            <?php if ($usuario['dni']): ?>
                                                <small class="text-muted">DNI: <?php echo htmlspecialchars($usuario['dni']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($usuario['email']); ?></div>
                                    <?php if ($usuario['telefono']): ?>
                                        <small class="text-muted">
                                            <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($usuario['telefono']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $badge_classes = [
                                        1 => 'bg-primary',
                                        2 => 'bg-success',
                                        3 => 'bg-warning text-dark'
                                    ];
                                    $badge_class = $badge_classes[$usuario['rol_id']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?> badge-rol">
                                        <?php echo htmlspecialchars($usuario['nombre_rol']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($usuario['sucursal_nombre']): ?>
                                        <div class="small">
                                            <i class="bi bi-shop me-1 text-muted"></i>
                                            <?php echo htmlspecialchars($usuario['sucursal_nombre']); ?>
                                        </div>
                                        <small class="text-muted"><?php echo htmlspecialchars($usuario['sucursal_ciudad']); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small"><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></div>
                                    <?php if ($usuario['ultimo_acceso']): ?>
                                        <small class="text-muted">
                                            Último: <?php echo date('d/m/Y', strtotime($usuario['ultimo_acceso'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($usuario['activo'] == 1): ?>
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="bi bi-check-circle me-1"></i>Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger">
                                            <i class="bi bi-x-circle me-1"></i>Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="table-actions">
                                        <!-- Editar -->
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="abrirModalEditar(<?php echo $usuario['id']; ?>)"
                                                title="Editar usuario">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        
                                        <!-- Activar/Desactivar -->
                                        <?php if ($usuario['id'] != $_SESSION['usuario_id']): // No puede desactivarse a sí mismo ?>
                                            <button class="btn btn-sm btn-outline-<?php echo $usuario['activo'] == 1 ? 'warning' : 'success'; ?>" 
                                                    onclick="cambiarEstado(<?php echo $usuario['id']; ?>, <?php echo $usuario['activo'] == 1 ? 0 : 1; ?>, '<?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>')"
                                                    title="<?php echo $usuario['activo'] == 1 ? 'Desactivar' : 'Activar'; ?>">
                                                <i class="bi bi-<?php echo $usuario['activo'] == 1 ? 'slash-circle' : 'check-circle'; ?>"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <!-- Resetear Password -->
                                        <!-- <button class="btn btn-sm btn-outline-danger" 
                                                onclick="resetearPassword(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>')"
                                                title="Resetear contraseña">
                                            <i class="bi bi-key"></i>   
                                        </button> -->
                                        
                                        <!-- Ver Historial (solo si tiene pedidos) -->
                                        <button class="btn btn-sm btn-outline-info" 
                                                onclick="verHistorial(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>')"
                                                title="Ver historial de pedidos">
                                            <i class="bi bi-clock-history"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="bi bi-inbox display-4 text-muted d-block mb-3"></i>
                                    <p class="text-muted mb-0">No se encontraron usuarios</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- ============================================== -->
        <!-- PAGINACIÓN -->
        <!-- ============================================== -->
        <?php if ($total_paginas > 1): ?>
        <div class="card-footer bg-white border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Mostrando <?php echo $offset + 1; ?> - <?php echo min($offset + $registros_por_pagina, $total_registros); ?> 
                    de <?php echo $total_registros; ?> usuarios
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <!-- Anterior -->
                        <?php if ($pagina_actual > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo $filtro_rol > 0 ? '&rol='.$filtro_rol : ''; ?><?php echo !empty($busqueda) ? '&buscar='.urlencode($busqueda) : ''; ?>">
                                Anterior
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <!-- Números de página -->
                        <?php
                        $rango = 2;
                        $inicio = max(1, $pagina_actual - $rango);
                        $fin = min($total_paginas, $pagina_actual + $rango);
                        
                        for ($i = $inicio; $i <= $fin; $i++):
                        ?>
                        <li class="page-item <?php echo $i == $pagina_actual ? 'active' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo $filtro_rol > 0 ? '&rol='.$filtro_rol : ''; ?><?php echo !empty($busqueda) ? '&buscar='.urlencode($busqueda) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <!-- Siguiente -->
                        <?php if ($pagina_actual < $total_paginas): ?>
                        <li class="page-item">
                            <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo $filtro_rol > 0 ? '&rol='.$filtro_rol : ''; ?><?php echo !empty($busqueda) ? '&buscar='.urlencode($busqueda) : ''; ?>">
                                Siguiente
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
</div>

<!-- ============================================== -->
<!-- MODAL: CREAR USUARIO -->
<!-- ============================================== -->
<div class="modal fade" id="modalCrearUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus me-2"></i>Crear Nuevo Usuario
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCrearUsuario">
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Nombre -->
                        <div class="col-md-6">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        
                        <!-- Apellido -->
                        <div class="col-md-6">
                            <label class="form-label">Apellido <span class="text-danger">*</span></label>
                            <input type="text" name="apellido" class="form-control" required>
                        </div>
                        
                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        
                        <!-- Teléfono -->
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" placeholder="Ej: 3834123456">
                        </div>
                        
                        <!-- DNI -->
                        <div class="col-md-6">
                            <label class="form-label">DNI</label>
                            <input type="text" name="dni" class="form-control" placeholder="Sin puntos">
                        </div>
                        
                        <!-- Contraseña -->
                        <div class="col-md-6">
                            <label class="form-label">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                            <small class="text-muted">Mínimo 6 caracteres</small>
                        </div>
                        
                        <!-- Rol -->
                        <div class="col-md-6">
                            <label class="form-label">Rol <span class="text-danger">*</span></label>
                            <select name="rol_id" id="rol_crear" class="form-select" required>
                                <option value="1">Cliente</option>
                                <option value="2">Gerente</option>
                                <option value="3">Administrador</option>
                            </select>
                        </div>
                        
                        <!-- Sucursal (solo para gerentes) -->
                        <div class="col-md-6" id="div_sucursal_crear" style="display:none;">
                            <label class="form-label">Sucursal <span class="text-danger">*</span></label>
                            <select name="sucursal_id" id="sucursal_crear" class="form-select">
                                <option value="">Seleccionar sucursal...</option>
                                <?php 
                                mysqli_data_seek($result_sucursales, 0);
                                while ($suc = mysqli_fetch_assoc($result_sucursales)): 
                                ?>
                                    <option value="<?php echo $suc['id']; ?>">
                                        <?php echo htmlspecialchars($suc['nombre'] . ' - ' . $suc['ciudad']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Crear Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL: EDITAR USUARIO -->
<!-- ============================================== -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="bi bi-pencil me-2"></i>Editar Usuario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarUsuario">
                <input type="hidden" name="usuario_id" id="editar_usuario_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Nombre -->
                        <div class="col-md-6">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" id="editar_nombre" class="form-control" required>
                        </div>
                        
                        <!-- Apellido -->
                        <div class="col-md-6">
                            <label class="form-label">Apellido <span class="text-danger">*</span></label>
                            <input type="text" name="apellido" id="editar_apellido" class="form-control" required>
                        </div>
                        
                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="editar_email" class="form-control" required>
                        </div>
                        
                        <!-- Teléfono -->
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" id="editar_telefono" class="form-control">
                        </div>
                        
                        <!-- DNI -->
                        <div class="col-md-6">
                            <label class="form-label">DNI</label>
                            <input type="text" name="dni" id="editar_dni" class="form-control">
                        </div>
                        
                        <!-- Rol -->
                        <div class="col-md-6">
                            <label class="form-label">Rol <span class="text-danger">*</span></label>
                            <select name="rol_id" id="editar_rol" class="form-select" required>
                                <option value="1">Cliente</option>
                                <option value="2">Gerente</option>
                                <option value="3">Administrador</option>
                            </select>
                        </div>
                        
                        <!-- Sucursal (solo para gerentes) -->
                        <div class="col-md-12" id="div_sucursal_editar" style="display:none;">
                            <label class="form-label">Sucursal <span class="text-danger">*</span></label>
                            <select name="sucursal_id" id="editar_sucursal" class="form-select">
                                <option value="">Seleccionar sucursal...</option>
                                <?php 
                                mysqli_data_seek($result_sucursales, 0);
                                while ($suc = mysqli_fetch_assoc($result_sucursales)): 
                                ?>
                                    <option value="<?php echo $suc['id']; ?>">
                                        <?php echo htmlspecialchars($suc['nombre'] . ' - ' . $suc['ciudad']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <!-- Dirección -->
                        <div class="col-12">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="direccion" id="editar_direccion" class="form-control">
                        </div>
                        
                        <!-- Ciudad -->
                        <div class="col-md-4">
                            <label class="form-label">Ciudad</label>
                            <input type="text" name="ciudad" id="editar_ciudad" class="form-control">
                        </div>
                        
                        <!-- Provincia -->
                        <div class="col-md-4">
                            <label class="form-label">Provincia</label>
                            <input type="text" name="provincia" id="editar_provincia" class="form-control">
                        </div>
                        
                        <!-- Código Postal -->
                        <div class="col-md-4">
                            <label class="form-label">Código Postal</label>
                            <input type="text" name="codigo_postal" id="editar_codigo_postal" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save me-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL: VER HISTORIAL DE PEDIDOS -->
<!-- ============================================== -->
<div class="modal fade" id="modalHistorial" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="bi bi-clock-history me-2"></i>Historial de Pedidos
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="contenidoHistorial">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-3 text-muted">Cargando historial...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../js/admin.js"></script>

<!-- Nota: El código JavaScript de usuarios ya está en ../js/admin.js -->
<script>
// ============================================================================
// MOSTRAR/OCULTAR SUCURSAL SEGÚN ROL (CREAR)
// ============================================================================
document.getElementById('rol_crear').addEventListener('change', function() {
    const divSucursal = document.getElementById('div_sucursal_crear');
    const selectSucursal = document.getElementById('sucursal_crear');
    
    if (this.value == '2') { // Gerente
        divSucursal.style.display = 'block';
        selectSucursal.required = true;
    } else {
        divSucursal.style.display = 'none';
        selectSucursal.required = false;
        selectSucursal.value = '';
    }
});

// ============================================================================
// MOSTRAR/OCULTAR SUCURSAL SEGÚN ROL (EDITAR)
// ============================================================================
document.getElementById('editar_rol').addEventListener('change', function() {
    const divSucursal = document.getElementById('div_sucursal_editar');
    const selectSucursal = document.getElementById('editar_sucursal');
    
    if (this.value == '2') { // Gerente
        divSucursal.style.display = 'block';
        selectSucursal.required = true;
    } else {
        divSucursal.style.display = 'none';
        selectSucursal.required = false;
        selectSucursal.value = '';
    }
});

// ============================================================================
// CREAR USUARIO
// ============================================================================
document.getElementById('formCrearUsuario').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('ajax/crear-usuario.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Usuario creado!',
                text: data.message,
                confirmButtonColor: '#3C50E0'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                confirmButtonColor: '#3C50E0'
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al procesar la solicitud',
            confirmButtonColor: '#3C50E0'
        });
    }
});

// ============================================================================
// ABRIR MODAL EDITAR
// ============================================================================
async function abrirModalEditar(usuarioId) {
    try {
        const response = await fetch(`ajax/obtener-usuario.php?id=${usuarioId}`);
        const data = await response.json();
        
        if (data.success) {
            const u = data.usuario;
            
            document.getElementById('editar_usuario_id').value = u.id;
            document.getElementById('editar_nombre').value = u.nombre;
            document.getElementById('editar_apellido').value = u.apellido;
            document.getElementById('editar_email').value = u.email;
            document.getElementById('editar_telefono').value = u.telefono || '';
            document.getElementById('editar_dni').value = u.dni || '';
            document.getElementById('editar_rol').value = u.rol_id;
            document.getElementById('editar_direccion').value = u.direccion || '';
            document.getElementById('editar_ciudad').value = u.ciudad || '';
            document.getElementById('editar_provincia').value = u.provincia || '';
            document.getElementById('editar_codigo_postal').value = u.codigo_postal || '';
            
            // Manejar sucursal si es gerente
            if (u.rol_id == 2) {
                document.getElementById('div_sucursal_editar').style.display = 'block';
                document.getElementById('editar_sucursal').required = true;
                document.getElementById('editar_sucursal').value = u.sucursal_id || '';
            } else {
                document.getElementById('div_sucursal_editar').style.display = 'none';
                document.getElementById('editar_sucursal').required = false;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('modalEditarUsuario'));
            modal.show();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                confirmButtonColor: '#3C50E0'
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al cargar los datos del usuario',
            confirmButtonColor: '#3C50E0'
        });
    }
}

// ============================================================================
// EDITAR USUARIO
// ============================================================================
document.getElementById('formEditarUsuario').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('ajax/editar-usuario.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Usuario actualizado!',
                text: data.message,
                confirmButtonColor: '#3C50E0'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                confirmButtonColor: '#3C50E0'
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al procesar la solicitud',
            confirmButtonColor: '#3C50E0'
        });
    }
});

// ============================================================================
// CAMBIAR ESTADO (Activar/Desactivar)
// ============================================================================
async function cambiarEstado(usuarioId, nuevoEstado, nombreUsuario) {
    const accion = nuevoEstado == 1 ? 'activar' : 'desactivar';
    const accionTitulo = nuevoEstado == 1 ? 'Activar' : 'Desactivar';
    
    const result = await Swal.fire({
        title: `¿${accionTitulo} usuario?`,
        html: `¿Estás seguro de que deseas ${accion} a <strong>${nombreUsuario}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: nuevoEstado == 1 ? '#10B981' : '#F59E0B',
        cancelButtonColor: '#6B7280',
        confirmButtonText: `Sí, ${accion}`,
        cancelButtonText: 'Cancelar'
    });
    
    if (result.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('usuario_id', usuarioId);
            formData.append('nuevo_estado', nuevoEstado);
            
            const response = await fetch('ajax/cambiar-estado-usuario.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Listo!',
                    text: data.message,
                    confirmButtonColor: '#3C50E0'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                    confirmButtonColor: '#3C50E0'
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al procesar la solicitud',
                confirmButtonColor: '#3C50E0'
            });
        }
    }
}

// ============================================================================
// RESETEAR CONTRASEÑA
// ============================================================================
async function resetearPassword(usuarioId, nombreUsuario) {
    const result = await Swal.fire({
        title: '¿Resetear contraseña?',
        html: `Se generará una nueva contraseña aleatoria para <strong>${nombreUsuario}</strong>.<br><br>
               <small class="text-muted">La contraseña se mostrará UNA SOLA VEZ para que la copies y entregues al usuario.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Sí, resetear',
        cancelButtonText: 'Cancelar'
    });
    
    if (result.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('usuario_id', usuarioId);
            
            const response = await fetch('ajax/resetear-password.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Contraseña reseteada!',
                    html: `
                        <p class="mb-3">Nueva contraseña temporal generada:</p>
                        <div class="alert alert-warning mb-3">
                            <h4 class="mb-2"><code style="font-size: 24px; letter-spacing: 2px;">${data.nueva_password}</code></h4>
                            <button class="btn btn-sm btn-dark" onclick="copiarAlPortapapeles('${data.nueva_password}')">
                                <i class="bi bi-clipboard me-2"></i>Copiar al portapapeles
                            </button>
                        </div>
                        <p class="mb-0 small text-muted">
                            <strong>Usuario:</strong> ${data.usuario_nombre}<br>
                            <strong>Email:</strong> ${data.usuario_email}
                        </p>
                        <p class="mt-3 text-danger small">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>IMPORTANTE:</strong> Guarda esta contraseña ahora. No se volverá a mostrar.
                        </p>
                    `,
                    confirmButtonColor: '#3C50E0',
                    confirmButtonText: 'Entendido',
                    allowOutsideClick: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                    confirmButtonColor: '#3C50E0'
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al procesar la solicitud',
                confirmButtonColor: '#3C50E0'
            });
        }
    }
}

// ============================================================================
// COPIAR AL PORTAPAPELES
// ============================================================================
function copiarAlPortapapeles(texto) {
    navigator.clipboard.writeText(texto).then(() => {
        Swal.fire({
            icon: 'success',
            title: '¡Copiado!',
            text: 'Contraseña copiada al portapapeles',
            timer: 1500,
            showConfirmButton: false
        });
    });
}

// ============================================================================
// VER HISTORIAL DE PEDIDOS
// ============================================================================
async function verHistorial(usuarioId, nombreUsuario) {
    const modal = new bootstrap.Modal(document.getElementById('modalHistorial'));
    modal.show();
    
    // Actualizar título con nombre del usuario
    document.querySelector('#modalHistorial .modal-title').innerHTML = 
        `<i class="bi bi-clock-history me-2"></i>Historial de Pedidos - ${nombreUsuario}`;
    
    // Resetear contenido
    document.getElementById('contenidoHistorial').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-3 text-muted">Cargando historial...</p>
        </div>
    `;
    
    try {
        const resp = await fetch(`ajax/obtener-pedidos-usuario.php?usuario_id=${usuarioId}`);
        const data = await resp.json();

        if (!data.success) {
            throw new Error(data.mensaje || 'Error al obtener pedidos');
        }

        const pedidos = data.pedidos;

        if (pedidos.length === 0) {
            document.getElementById('contenidoHistorial').innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted d-block mb-3"></i>
                    <h5>Sin pedidos aún</h5>
                    <p class="text-muted">Este usuario no ha realizado ningún pedido todavía.</p>
                </div>
            `;
            return;
        }

        const estadoBadge = {
            pendiente:   'bg-warning text-dark',
            confirmado:  'bg-info text-dark',
            preparando:  'bg-primary',
            enviado:     'bg-info',
            entregado:   'bg-success',
            cancelado:   'bg-danger',
        };
        const pagoBadge = {
            pendiente: 'bg-warning text-dark',
            pagado:    'bg-success',
            rechazado: 'bg-danger',
        };

        let html = pedidos.map(p => {
            const detalle = p.detalle.map(d =>
                `<li class="small text-muted">${d.nombre_producto} × ${d.cantidad}
                    ${d.talle ? '— T: ' + d.talle : ''}
                    ${d.color ? '— C: ' + d.color : ''}
                    — $${Number(d.subtotal).toLocaleString('es-AR')}
                </li>`
            ).join('');

            return `
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong>${p.numero_pedido}</strong>
                                ${p.sucursal_nombre ? `<small class="text-muted ms-2">(${p.sucursal_nombre})</small>` : ''}
                            </div>
                            <small class="text-muted">${new Date(p.fecha_pedido).toLocaleDateString('es-AR')}</small>
                        </div>
                        <div class="d-flex gap-2 mb-2">
                            <span class="badge ${estadoBadge[p.estado] || 'bg-secondary'}">${p.estado}</span>
                            <span class="badge ${pagoBadge[p.estado_pago] || 'bg-secondary'}">${p.estado_pago}</span>
                            <span class="badge bg-light text-dark border">${p.metodo_pago}</span>
                        </div>
                        <ul class="mb-1 ps-3">${detalle}</ul>
                        <div class="text-end fw-bold">Total: $${Number(p.total).toLocaleString('es-AR')}</div>
                    </div>
                </div>
            `;
        }).join('');

        document.getElementById('contenidoHistorial').innerHTML = html;

    } catch (error) {
        document.getElementById('contenidoHistorial').innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                ${error.message || 'Error al cargar el historial'}
            </div>
        `;
    }
}

// ============================================================================
// TOGGLE SIDEBAR (responsive)
// ============================================================================
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('backdrop');
    
    sidebar.classList.toggle('show');
    backdrop.classList.toggle('show');
}
</script>

<?php
mysqli_stmt_close($stmt);
require_once('includes/footer-admin.php');
?>
