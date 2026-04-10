<?php
/**
 * ADMIN DASHBOARD - Panel de Administración (FUNCIONAL)
 * Dashboard completo con estadísticas reales y gráficos dinámicos
 */

require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

$titulo_pagina = "Dashboard Admin";

// ============================================================================
// OBTENER ESTADÍSTICAS REALES
// ============================================================================

$stats = [];

// Total de productos activos
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM productos WHERE activo = 1");
$stats['productos'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total de usuarios activos
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
$stats['usuarios'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total de sucursales
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM sucursales");
$stats['sucursales'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total de pedidos
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM pedidos");
$stats['pedidos'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// ============================================================================
// DATOS PARA GRÁFICO DE VENTAS MENSUALES
// ============================================================================

$ventas_mensuales = [];
$meses = [];

// Obtener ventas de los últimos 12 meses
for ($i = 11; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i months"));
    $mes_nombre = date('M', strtotime("-$i months"));
    
    $query = "SELECT COALESCE(SUM(total), 0) as total 
              FROM pedidos 
              WHERE DATE_FORMAT(fecha_pedido, '%Y-%m') = '$mes'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    
    $meses[] = ucfirst($mes_nombre);
    $ventas_mensuales[] = floatval($row['total']);
}

// ============================================================================
// PRODUCTOS POR CATEGORÍA
// ============================================================================

$query_categorias = "SELECT c.nombre, COUNT(p.id) as total, c.id
                     FROM categorias c
                     LEFT JOIN productos p ON c.id = p.categoria_id AND p.activo = 1
                     GROUP BY c.id, c.nombre
                     ORDER BY total DESC
                     LIMIT 5";
$result_categorias = mysqli_query($conn, $query_categorias);

$categorias_nombres = [];
$categorias_valores = [];
$categorias_colores = ['#3C50E0', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];

$i = 0;
while ($cat = mysqli_fetch_assoc($result_categorias)) {
    $categorias_nombres[] = $cat['nombre'];
    $categorias_valores[] = intval($cat['total']);
    $i++;
}

// ============================================================================
// ÚLTIMOS 5 USUARIOS REGISTRADOS
// ============================================================================

$roles_nombres = [ROL_CLIENTE => 'Cliente', ROL_GERENTE => 'Gerente', ROL_ADMIN => 'Admin'];

$query_usuarios = "SELECT u.id, u.nombre, u.apellido, u.email, u.fecha_registro, u.rol_id,
                          COALESCE(r.nombre_rol, '') AS nombre_rol
                   FROM usuarios u
                   LEFT JOIN roles r ON u.rol_id = r.rol_id
                   ORDER BY u.fecha_registro DESC
                   LIMIT 5";
$result_usuarios = mysqli_query($conn, $query_usuarios);

// ============================================================================
// NOTIFICACIONES NO LEÍDAS
// ============================================================================

$query_notif = "SELECT COUNT(*) as total 
                FROM notificaciones 
                WHERE leida = 0 AND visible_para IN ('admin', 'ambos')";
$result_notif = mysqli_query($conn, $query_notif);
$notif_count = mysqli_fetch_assoc($result_notif)['total'] ?? 0;

require_once('includes/header-admin.php');
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-2 fw-bold text-dark">Dashboard</h1>
        <p class="text-muted mb-0">Bienvenido, <?php echo $_SESSION['nombre']; ?> - Vista general del sistema</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm" onclick="window.location.reload()">
            <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
        </button>
        <span class="badge bg-primary px-3 py-2">Administrador</span>
    </div>
</div>

<!-- Cards de Estadísticas -->
<div class="row g-4 mb-4">
    <!-- Productos -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="text-muted mb-2 fw-normal">Total Productos</h6>
                        <h2 class="mb-0 fw-bold"><?php echo number_format($stats['productos']); ?></h2>
                    </div>
                    <div class="p-3 bg-primary bg-opacity-10 rounded">
                        <i class="bi bi-box-seam text-primary fs-3"></i>
                    </div>
                </div>
                <a href="productos.php" class="text-primary text-decoration-none small">
                    Ver todos <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Usuarios -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="text-muted mb-2 fw-normal">Total Usuarios</h6>
                        <h2 class="mb-0 fw-bold"><?php echo number_format($stats['usuarios']); ?></h2>
                    </div>
                    <div class="p-3 bg-success bg-opacity-10 rounded">
                        <i class="bi bi-people text-success fs-3"></i>
                    </div>
                </div>
                <a href="usuarios.php" class="text-success text-decoration-none small">
                    Gestionar <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Sucursales -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="text-muted mb-2 fw-normal">Sucursales</h6>
                        <h2 class="mb-0 fw-bold"><?php echo number_format($stats['sucursales']); ?></h2>
                    </div>
                    <div class="p-3 bg-warning bg-opacity-10 rounded">
                        <i class="bi bi-shop text-warning fs-3"></i>
                    </div>
                </div>
                <a href="sucursales.php" class="text-warning text-decoration-none small">
                    Ver ubicaciones <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Pedidos -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="text-muted mb-2 fw-normal">Total Pedidos</h6>
                        <h2 class="mb-0 fw-bold"><?php echo number_format($stats['pedidos']); ?></h2>
                    </div>
                    <div class="p-3 bg-danger bg-opacity-10 rounded">
                        <i class="bi bi-bag-check text-danger fs-3"></i>
                    </div>
                </div>
                <a href="pedidos.php" class="text-danger text-decoration-none small">
                    Ver pedidos <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos -->
<div class="row g-4 mb-4">
    <!-- Ventas Mensuales -->
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="card-title mb-1 fw-bold">Ventas de los Últimos 12 Meses</h5>
                        <p class="text-muted small mb-0">Evolución de ingresos</p>
                    </div>
                </div>
                <canvas id="ventasChart" height="80"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Productos por Categoría -->
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3 fw-bold">Productos por Categoría</h5>
                <canvas id="categoriasChart"></canvas>
                <div class="mt-4">
                    <?php 
                    mysqli_data_seek($result_categorias, 0);
                    $j = 0;
                    while ($cat = mysqli_fetch_assoc($result_categorias)): 
                    ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small">
                                <i class="bi bi-circle-fill me-2" style="color: <?php echo $categorias_colores[$j]; ?>"></i>
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </span>
                            <span class="fw-semibold"><?php echo $cat['total']; ?></span>
                        </div>
                    <?php 
                    $j++;
                    endwhile; 
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Últimos Usuarios -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="card-title mb-0 fw-bold">
                <i class="bi bi-person-check text-success me-2"></i>
                Últimos Usuarios Registrados
            </h5>
            <a href="usuarios.php" class="btn btn-sm btn-outline-primary">Ver todos</a>
        </div>
        
        <?php if (mysqli_num_rows($result_usuarios) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Fecha Registro</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($usuario = mysqli_fetch_assoc($result_usuarios)): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width:40px;height:40px;">
                                            <strong><?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?></strong>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-muted"><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td>
                                    <?php
                                    $nombre_rol_display = !empty($usuario['nombre_rol'])
                                        ? $usuario['nombre_rol']
                                        : ($roles_nombres[$usuario['rol_id']] ?? 'Desconocido');
                                    $badge_class = match($nombre_rol_display) {
                                        'Cliente' => 'bg-secondary',
                                        'Gerente' => 'bg-success',
                                        'Admin', 'Administrador' => 'bg-primary',
                                        default   => 'bg-secondary',
                                    };
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($nombre_rol_display); ?></span>
                                </td>
                                <td class="text-muted"><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></td>
                                <td class="text-center">
                                    <a href="usuarios.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                <p>No hay usuarios registrados aún</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once('includes/footer-admin.php'); ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../js/graficos.js"></script>

<!-- Datos PHP para gráficos -->
<script>
// Pasar datos PHP a JavaScript para los gráficos
window.dashboardData = {
    ventasMensuales: {
        meses: <?php echo json_encode($meses); ?>,
        ventas: <?php echo json_encode($ventas_mensuales); ?>
    },
    categorias: {
        nombres: <?php echo json_encode($categorias_nombres); ?>,
        valores: <?php echo json_encode($categorias_valores); ?>,
        colores: <?php echo json_encode($categorias_colores); ?>
    }
};
</script>

<!-- Nota: El código JavaScript para gráficos ya está en ../js/graficos.js -->
<!--
<script>
// Gráfico de Ventas Mensuales (Líneas)
const ventasCtx = document.getElementById('ventasChart').getContext('2d');
new Chart(ventasCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($meses); ?>,
        datasets: [{
            label: 'Ventas ($)',
            data: <?php echo json_encode($ventas_mensuales); ?>,
            borderColor: '#3C50E0',
            backgroundColor: 'rgba(60, 80, 224, 0.1)',
            borderWidth: 2,
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#3C50E0'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: '#1C2434',
                padding: 12,
                bodySpacing: 4,
                titleFont: {
                    size: 14
                },
                bodyFont: {
                    size: 13
                },
                callbacks: {
                    label: function(context) {
                        return 'Ventas: $' + context.parsed.y.toLocaleString('es-AR', {minimumFractionDigits: 2});
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    borderDash: [5, 5]
                },
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString('es-AR');
                    }
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Gráfico de Categorías (Dona)
const categoriasCtx = document.getElementById('categoriasChart').getContext('2d');
new Chart(categoriasCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($categorias_nombres); ?>,
        datasets: [{
            data: <?php echo json_encode($categorias_valores); ?>,
            backgroundColor: <?php echo json_encode($categorias_colores); ?>,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: '#1C2434',
                padding: 12,
                bodyFont: {
                    size: 13
                },
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.parsed + ' productos';
                    }
                }
            }
        },
        cutout: '70%'
    }
});
</script> -->
