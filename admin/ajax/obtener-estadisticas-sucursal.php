<?php
require_once('../../includes/config.php');
require_once('../../includes/verificar-admin.php');
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$id = (int)$_GET['id'];

// Obtener datos de la sucursal
$sql = "SELECT * FROM sucursales WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$sucursal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Ventas del mes actual
$sql = "SELECT 
            COUNT(DISTINCT tc.id) as total_turnos,
            COALESCE(SUM(tc.venta_total_dia), 0) as ventas_mes,
            COALESCE(SUM(tc.pares_vendidos), 0) as pares_vendidos,
            COALESCE(SUM(tc.gastos_dia), 0) as gastos_mes
        FROM turnos_caja tc
        WHERE tc.sucursal_id = ? 
        AND MONTH(tc.fecha_apertura) = MONTH(CURDATE())
        AND YEAR(tc.fecha_apertura) = YEAR(CURDATE())";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$estadisticas = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Ventas por método de pago (mes actual) - CORREGIDO
$sql = "SELECT 
            COALESCE(SUM(CASE WHEN metodo_pago = 'efectivo' THEN subtotal ELSE 0 END), 0) as efectivo,
            COALESCE(SUM(CASE WHEN metodo_pago = 'tarjeta' THEN subtotal ELSE 0 END), 0) as tarjeta,
            COALESCE(SUM(CASE WHEN metodo_pago = 'transferencia' THEN subtotal ELSE 0 END), 0) as transferencia,
            COALESCE(SUM(CASE WHEN metodo_pago = 'go_cuotas' THEN subtotal ELSE 0 END), 0) as go_cuotas,
            COALESCE(SUM(CASE WHEN metodo_pago = 'credito' THEN subtotal ELSE 0 END), 0) as credito
        FROM ventas_diarias vd
        WHERE vd.sucursal_id = ?
        AND MONTH(vd.fecha_venta) = MONTH(CURDATE())
        AND YEAR(vd.fecha_venta) = YEAR(CURDATE())";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$metodos_pago = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Stock actual
$sql = "SELECT 
            COUNT(*) as productos,
            COALESCE(SUM(cantidad), 0) as total_pares
        FROM stock_sucursal 
        WHERE sucursal_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$stock = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Productos más vendidos (mes actual) - CORREGIDO
$sql = "SELECT 
            vd.producto_nombre as nombre,
            SUM(vd.cantidad) as total_vendido,
            SUM(vd.subtotal) as monto_total
        FROM ventas_diarias vd
        WHERE vd.sucursal_id = ?
        AND MONTH(vd.fecha_venta) = MONTH(CURDATE())
        AND YEAR(vd.fecha_venta) = YEAR(CURDATE())
        GROUP BY vd.producto_nombre
        ORDER BY total_vendido DESC
        LIMIT 5";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$top_productos = [];
while ($row = mysqli_fetch_assoc($resultado)) {
    $top_productos[] = $row;
}

// Generar HTML
ob_start();
?>
<div class="row">
    <div class="col-12 mb-4">
        <h4><?php echo htmlspecialchars($sucursal['nombre']); ?></h4>
        <p class="text-muted"><?php echo htmlspecialchars($sucursal['ciudad'] . ', ' . $sucursal['provincia']); ?></p>
    </div>
    
    <!-- Tarjetas de estadísticas -->
    <div class="col-md-3 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-currency-dollar fs-1 text-success mb-2"></i>
                <h3 class="mb-0">$<?php echo number_format($estadisticas['ventas_mes'], 0, ',', '.'); ?></h3>
                <p class="text-muted mb-0">Ventas del Mes</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-box-seam fs-1 text-primary mb-2"></i>
                <h3 class="mb-0"><?php echo number_format($estadisticas['pares_vendidos']); ?></h3>
                <p class="text-muted mb-0">Pares Vendidos</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-boxes fs-1 text-info mb-2"></i>
                <h3 class="mb-0"><?php echo number_format($stock['total_pares']); ?></h3>
                <p class="text-muted mb-0">Stock Actual</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-calendar-check fs-1 text-warning mb-2"></i>
                <h3 class="mb-0"><?php echo $estadisticas['total_turnos']; ?></h3>
                <p class="text-muted mb-0">Turnos del Mes</p>
            </div>
        </div>
    </div>
    
    <!-- Ventas por método de pago -->
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">Ventas por Método de Pago</h6>
            </div>
            <div class="card-body">
                <?php 
                $total_metodos = $metodos_pago['efectivo'] + $metodos_pago['tarjeta'] + $metodos_pago['transferencia'] + $metodos_pago['go_cuotas'] + $metodos_pago['credito'];
                if ($total_metodos > 0): 
                ?>
                <canvas id="chartMetodosPago" height="200"></canvas>
                <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-3"></i>
                    <p class="mb-0 mt-2">No hay ventas este mes</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Top productos -->
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">Productos Más Vendidos</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($top_productos)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($top_productos as $producto): ?>
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($producto['nombre']); ?></h6>
                                <small class="text-muted"><?php echo $producto['total_vendido']; ?> pares vendidos</small>
                            </div>
                            <strong class="text-success">$<?php echo number_format($producto['monto_total'], 0, ',', '.'); ?></strong>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-3"></i>
                    <p class="mb-0 mt-2">No hay ventas registradas este mes</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($total_metodos > 0): ?>
<script>
// Gráfico de métodos de pago
const ctx = document.getElementById('chartMetodosPago');
if (ctx) {
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Efectivo', 'Tarjeta', 'Transferencia', 'Go Cuotas', 'Crédito'],
            datasets: [{
                data: [
                    <?php echo $metodos_pago['efectivo']; ?>,
                    <?php echo $metodos_pago['tarjeta']; ?>,
                    <?php echo $metodos_pago['transferencia']; ?>,
                    <?php echo $metodos_pago['go_cuotas']; ?>,
                    <?php echo $metodos_pago['credito']; ?>
                ],
                backgroundColor: [
                    '#10B981',
                    '#3B82F6',
                    '#8B5CF6',
                    '#F59E0B',
                    '#EF4444'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}
</script>
<?php endif; ?>
<?php
$html = ob_get_clean();
echo json_encode(['success' => true, 'html' => $html]);
mysqli_close($conn);
?>
