<?php
require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('ID inválido');

$stmt = mysqli_prepare($conn,
    "SELECT tc.*,
            s.nombre AS sucursal_nombre, s.direccion, s.ciudad, s.provincia, s.telefono AS sucursal_tel,
            CONCAT(u.nombre, ' ', u.apellido) AS gerente_nombre
     FROM turnos_caja tc
     LEFT JOIN sucursales s ON tc.sucursal_id = s.id
     LEFT JOIN usuarios   u ON tc.gerente_id  = u.id
     WHERE tc.id = ? AND tc.estado = 'cerrado'");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$t = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$t) die('Turno no encontrado o está abierto.');

$stmt_v = mysqli_prepare($conn, "SELECT * FROM ventas_diarias WHERE turno_id = ? ORDER BY fecha_venta ASC");
mysqli_stmt_bind_param($stmt_v, 'i', $id);
mysqli_stmt_execute($stmt_v);
$ventas = mysqli_fetch_all(mysqli_stmt_get_result($stmt_v), MYSQLI_ASSOC);

$stmt_g = mysqli_prepare($conn, "SELECT * FROM gastos_sucursal WHERE turno_id = ? ORDER BY fecha_gasto ASC");
mysqli_stmt_bind_param($stmt_g, 'i', $id);
mysqli_stmt_execute($stmt_g);
$gastos = mysqli_fetch_all(mysqli_stmt_get_result($stmt_g), MYSQLI_ASSOC);

$stmt_c = mysqli_prepare($conn, "SELECT * FROM cobro_cuotas_credito WHERE turno_id = ? ORDER BY fecha_cobro ASC");
mysqli_stmt_bind_param($stmt_c, 'i', $id);
mysqli_stmt_execute($stmt_c);
$cobros = mysqli_fetch_all(mysqli_stmt_get_result($stmt_c), MYSQLI_ASSOC);

$dif = floatval($t['diferencia_caja']);
$ef_esperado = floatval($t['monto_inicial']) + floatval($t['efectivo_ventas']) + floatval($t['credito_ventas']) + floatval($t['cobro_cuotas_credito']) - floatval($t['gastos_dia']);

function pesos($n) { return '$' . number_format(floatval($n), 2, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cierre de Caja — <?php echo htmlspecialchars($t['sucursal_nombre']); ?> — <?php echo date('d/m/Y', strtotime($t['fecha_apertura'])); ?></title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: Arial, sans-serif; font-size: 11px; color: #333; padding: 20px; }
h1 { font-size: 18px; }
h2 { font-size: 13px; margin-bottom: 6px; border-bottom: 1px solid #ccc; padding-bottom: 3px; }
.header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; border-bottom: 2px solid #003;  padding-bottom: 12px; }
.header-left h1 { color: #003; }
.header-right { text-align: right; font-size: 10px; color: #555; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-danger  { background: #fee2e2; color: #991b1b; }
.badge-warning { background: #fef3c7; color: #92400e; }
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px; }
.box { border: 1px solid #ddd; border-radius: 4px; padding: 10px; }
.box h2 { font-size: 11px; margin-bottom: 8px; }
.row-item { display: flex; justify-content: space-between; margin-bottom: 4px; }
.row-item.total { font-weight: bold; border-top: 1px solid #ccc; padding-top: 4px; margin-top: 4px; }
.highlight { font-size: 16px; font-weight: bold; text-align: center; margin: 10px 0; }
table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
th { background: #f3f4f6; text-align: left; padding: 4px 6px; font-size: 10px; border: 1px solid #ddd; }
td { padding: 3px 6px; border: 1px solid #eee; font-size: 10px; }
.text-right { text-align: right; }
.text-center { text-align: center; }
.text-danger { color: #dc2626; }
.text-success { color: #059669; }
.text-info { color: #0891b2; }
.section { margin-bottom: 14px; }
.firma { margin-top: 30px; display: flex; justify-content: space-around; }
.firma div { text-align: center; width: 180px; border-top: 1px solid #555; padding-top: 6px; font-size: 10px; }
.footer-print { margin-top: 20px; font-size: 9px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 8px; }
@media print {
    body { padding: 0; }
    .no-print { display: none; }
}
</style>
</head>
<body onload="window.print()">

<div class="no-print" style="margin-bottom:16px;">
    <button onclick="window.print()" style="padding:6px 16px;background:#0047AB;color:white;border:none;border-radius:4px;cursor:pointer;margin-right:8px;">
        Imprimir / Guardar PDF
    </button>
    <button onclick="window.close()" style="padding:6px 16px;background:#6b7280;color:white;border:none;border-radius:4px;cursor:pointer;">
        Cerrar
    </button>
</div>

<!-- Encabezado -->
<div class="header">
    <div class="header-left">
        <h1>MAURO CALZADO</h1>
        <div><strong>CIERRE DE CAJA — TURNO <?php echo strtoupper($t['turno'] === 'manana' ? 'MAÑANA' : 'TARDE'); ?></strong></div>
        <div style="margin-top:4px;"><?php echo htmlspecialchars($t['sucursal_nombre']); ?> — <?php echo htmlspecialchars($t['ciudad'] ?? ''); ?></div>
        <div><?php echo htmlspecialchars($t['direccion'] ?? ''); ?></div>
    </div>
    <div class="header-right">
        <div><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($t['fecha_apertura'])); ?></div>
        <div><strong>Apertura:</strong> <?php echo date('H:i', strtotime($t['fecha_apertura'])); ?></div>
        <div><strong>Cierre:</strong> <?php echo date('H:i', strtotime($t['fecha_cierre'])); ?></div>
        <div><strong>Gerente:</strong> <?php echo htmlspecialchars($t['gerente_nombre']); ?></div>
        <div style="margin-top:6px;">
            <?php
            if (abs($dif) < 0.01) echo '<span class="badge badge-success">CAJA CUADRADA</span>';
            elseif ($dif > 0)     echo '<span class="badge badge-warning">SOBRANTE ' . pesos($dif) . '</span>';
            else                  echo '<span class="badge badge-danger">FALTANTE ' . pesos(abs($dif)) . '</span>';
            ?>
        </div>
    </div>
</div>

<!-- Resumen central -->
<div class="highlight text-success">TOTAL VENTAS DEL TURNO: <?php echo pesos($t['venta_total_dia']); ?> — <?php echo $t['pares_vendidos']; ?> pares</div>

<!-- Ingresos y control -->
<div class="grid2">
    <div class="box">
        <h2>INGRESOS</h2>
        <div class="row-item"><span>Monto inicial caja</span><span><?php echo pesos($t['monto_inicial']); ?></span></div>
        <div class="row-item"><span>Ventas efectivo</span><span><?php echo pesos($t['efectivo_ventas']); ?></span></div>
        <div class="row-item"><span>Ventas tarjeta</span><span><?php echo pesos($t['tarjeta_ventas']); ?></span></div>
        <div class="row-item"><span>Ventas transferencia</span><span><?php echo pesos($t['transferencia_ventas']); ?></span></div>
        <div class="row-item"><span>Ventas Go Cuotas</span><span><?php echo pesos($t['go_cuotas_ventas']); ?></span></div>
        <div class="row-item"><span>Crédito (1ra cuota)</span><span><?php echo pesos($t['credito_ventas']); ?></span></div>
        <div class="row-item"><span class="text-info">Cobro cuotas crédito</span><span class="text-info"><?php echo pesos($t['cobro_cuotas_credito']); ?></span></div>
        <div class="row-item total"><span>TOTAL VENTAS</span><span class="text-success"><?php echo pesos($t['venta_total_dia']); ?></span></div>
    </div>
    <div class="box">
        <h2>CONTROL DE EFECTIVO</h2>
        <div class="row-item"><span class="text-danger">Gastos del día</span><span class="text-danger">-<?php echo pesos($t['gastos_dia']); ?></span></div>
        <div class="row-item"><span>Retiros</span><span><?php echo pesos($t['retiros']); ?></span></div>
        <div class="row-item"><span>Depósitos banco</span><span><?php echo pesos($t['depositos_banco']); ?></span></div>
        <div class="row-item" style="margin-top:8px;"><span>Efectivo esperado</span><span><?php echo pesos($ef_esperado); ?></span></div>
        <div class="row-item"><span>Efectivo contado</span><span><?php echo pesos($t['efectivo_cierre']); ?></span></div>
        <div class="row-item total <?php echo abs($dif)<0.01 ? 'text-success' : ($dif>0 ? '' : 'text-danger'); ?>">
            <span>DIFERENCIA</span>
            <span><?php echo ($dif >= 0 ? '+' : '') . pesos($dif); ?></span>
        </div>
    </div>
</div>

<!-- Ventas -->
<div class="section">
    <h2>DETALLE DE VENTAS (<?php echo count($ventas); ?>)</h2>
    <?php if ($ventas): ?>
    <table>
        <thead><tr><th>Hora</th><th>Producto</th><th>Talle</th><th>Color</th><th class="text-center">Cant.</th><th>Método</th><th class="text-right">Precio unit.</th><th class="text-right">Subtotal</th></tr></thead>
        <tbody>
            <?php foreach ($ventas as $vd): ?>
            <tr>
                <td><?php echo date('H:i', strtotime($vd['fecha_venta'])); ?></td>
                <td><?php echo htmlspecialchars($vd['producto_nombre']); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($vd['talle'] ?? '—'); ?></td>
                <td><?php echo htmlspecialchars($vd['color'] ?? '—'); ?></td>
                <td class="text-center"><?php echo $vd['cantidad']; ?></td>
                <td><?php echo ucfirst($vd['metodo_pago']); ?></td>
                <td class="text-right"><?php echo pesos($vd['precio_unitario']); ?></td>
                <td class="text-right"><strong><?php echo pesos($vd['subtotal']); ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="color:#999;font-size:10px;">Sin ventas registradas en este turno.</p>
    <?php endif; ?>
</div>

<!-- Gastos y Cobros -->
<div class="grid2">
    <div class="section">
        <h2>GASTOS (<?php echo count($gastos); ?>)</h2>
        <?php if ($gastos): ?>
        <table>
            <thead><tr><th>Concepto</th><th>Tipo</th><th class="text-right">Monto</th></tr></thead>
            <tbody>
                <?php foreach ($gastos as $gd): ?>
                <tr>
                    <td><?php echo htmlspecialchars($gd['concepto']); ?></td>
                    <td><?php echo ucfirst($gd['tipo']); ?></td>
                    <td class="text-right text-danger"><?php echo pesos($gd['monto']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color:#999;font-size:10px;">Sin gastos.</p>
        <?php endif; ?>
    </div>
    <div class="section">
        <h2>COBROS DE CUOTAS (<?php echo count($cobros); ?>)</h2>
        <?php if ($cobros): ?>
        <table>
            <thead><tr><th>Cliente</th><th class="text-center">Cuota</th><th class="text-right">Monto</th></tr></thead>
            <tbody>
                <?php foreach ($cobros as $cc): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cc['cliente_nombre']); ?><?php echo $cc['cliente_dni'] ? ' ('.$cc['cliente_dni'].')' : ''; ?></td>
                    <td class="text-center"><?php echo $cc['numero_cuota'] ? '#'.$cc['numero_cuota'] : '—'; ?></td>
                    <td class="text-right text-info"><?php echo pesos($cc['monto_cobrado']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color:#999;font-size:10px;">Sin cobros de cuotas.</p>
        <?php endif; ?>
    </div>
</div>

<?php if ($t['notas_cierre']): ?>
<div class="section">
    <h2>NOTAS DE CIERRE</h2>
    <p><?php echo nl2br(htmlspecialchars($t['notas_cierre'])); ?></p>
</div>
<?php endif; ?>

<!-- Firmas -->
<div class="firma">
    <div>Firma Gerente<br><small><?php echo htmlspecialchars($t['gerente_nombre']); ?></small></div>
    <div>Firma Administración</div>
</div>

<div class="footer-print">
    Documento generado el <?php echo date('d/m/Y H:i'); ?> — Mauro Calzado — Sistema interno
</div>
</body>
</html>
