<?php
/**
 * CIERRE DE TURNO - Resumen imprimible (tira de caja)
 */
require_once('../includes/config.php');
require_once('../includes/verificar-gerente.php');

$turno_id    = (int)($_GET['id'] ?? 0);
$sucursal_id = obtenerSucursalGerente();

if (!$turno_id) { die('ID de turno requerido'); }

// Obtener turno
$sql = "SELECT tc.*, s.nombre as sucursal_nombre, s.direccion, s.ciudad, s.telefono
        FROM turnos_caja tc
        INNER JOIN sucursales s ON tc.sucursal_id = s.id
        WHERE tc.id = ? AND tc.sucursal_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $turno_id, $sucursal_id);
mysqli_stmt_execute($stmt);
$turno = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$turno) { die('Turno no encontrado'); }

// Obtener ventas del turno
$sql = "SELECT * FROM ventas_diarias WHERE turno_id = ? ORDER BY fecha_venta ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $turno_id);
mysqli_stmt_execute($stmt);
$ventas = [];
$res = mysqli_stmt_get_result($stmt);
while ($v = mysqli_fetch_assoc($res)) { $ventas[] = $v; }

// Conteo por método
$por_metodo = [];
foreach ($ventas as $v) {
    $m = $v['metodo_pago'];
    if (!isset($por_metodo[$m])) $por_metodo[$m] = ['cantidad' => 0, 'total' => 0];
    $por_metodo[$m]['cantidad']++;
    $por_metodo[$m]['total'] += $v['subtotal'];
}

// Configuración tienda
$tienda_nombre = 'Mauro Calzado';
$r = mysqli_query($conn, "SELECT valor FROM configuracion WHERE clave = 'nombre_tienda' LIMIT 1");
if ($r && $row = mysqli_fetch_assoc($r)) $tienda_nombre = $row['valor'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cierre Turno #<?php echo $turno_id; ?></title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    width: 80mm;
    margin: 0 auto;
    padding: 8px;
    background: #fff;
    color: #000;
  }
  .center  { text-align: center; }
  .right   { text-align: right; }
  .bold    { font-weight: bold; }
  .big     { font-size: 14px; }
  .xl      { font-size: 16px; }
  hr       { border: none; border-top: 1px dashed #000; margin: 6px 0; }
  .double  { border-top: 2px solid #000; }
  .row     { display: flex; justify-content: space-between; margin: 2px 0; }
  .row-indent { display: flex; justify-content: space-between; margin: 2px 0; padding-left: 8px; }
  table    { width: 100%; border-collapse: collapse; font-size: 11px; }
  td       { padding: 2px 0; vertical-align: top; }
  td.r     { text-align: right; }
  .highlight { background: #eee; padding: 2px 4px; }
  @media print {
    body { width: 80mm; }
    .no-print { display: none; }
  }
</style>
</head>
<body>

<!-- ENCABEZADO -->
<div class="center">
  <div class="xl bold"><?php echo htmlspecialchars($tienda_nombre); ?></div>
  <div><?php echo htmlspecialchars($turno['sucursal_nombre']); ?></div>
  <div><?php echo htmlspecialchars($turno['direccion']); ?></div>
  <?php if ($turno['ciudad']): ?><div><?php echo htmlspecialchars($turno['ciudad']); ?></div><?php endif; ?>
  <?php if ($turno['telefono']): ?><div>Tel: <?php echo htmlspecialchars($turno['telefono']); ?></div><?php endif; ?>
</div>

<hr>

<div class="center bold big">RESUMEN DE CIERRE DE TURNO</div>
<div class="center">Turno #<?php echo $turno_id; ?></div>

<hr>

<!-- DATOS DEL TURNO -->
<div class="row"><span>Turno:</span><span class="bold"><?php echo ucfirst($turno['turno']); ?></span></div>
<div class="row"><span>Apertura:</span><span><?php echo date('d/m/Y H:i', strtotime($turno['fecha_apertura'])); ?></span></div>
<div class="row"><span>Cierre:</span><span><?php echo $turno['fecha_cierre'] ? date('d/m/Y H:i', strtotime($turno['fecha_cierre'])) : date('d/m/Y H:i'); ?></span></div>
<div class="row"><span>Monto inicial:</span><span>$<?php echo number_format($turno['monto_inicial'], 2, ',', '.'); ?></span></div>

<hr>

<!-- VENTAS POR MÉTODO -->
<div class="center bold">--- VENTAS POR MÉTODO ---</div>

<?php
$metodos_label = [
    'efectivo'      => 'Efectivo',
    'tarjeta'       => 'Tarjeta',
    'transferencia' => 'Transf.',
    'go_cuotas'     => 'Go Cuotas',
    'credito'       => 'Crédito (1ra)',
];
foreach ($metodos_label as $key => $label):
    $cant  = $por_metodo[$key]['cantidad'] ?? 0;
    $tot   = $por_metodo[$key]['total'] ?? 0;
    if ($cant == 0) continue;
?>
<div class="row">
  <span><?php echo $label; ?> (<?php echo $cant; ?> op.)</span>
  <span class="bold">$<?php echo number_format($tot, 2, ',', '.'); ?></span>
</div>
<?php endforeach; ?>

<?php if ($turno['cobro_cuotas_credito'] > 0): ?>
<div class="row">
  <span>Cobro cuotas</span>
  <span class="bold">$<?php echo number_format($turno['cobro_cuotas_credito'], 2, ',', '.'); ?></span>
</div>
<?php endif; ?>

<?php if ($turno['gastos_dia'] > 0): ?>
<div class="row">
  <span>Gastos del día</span>
  <span>-$<?php echo number_format($turno['gastos_dia'], 2, ',', '.'); ?></span>
</div>
<?php endif; ?>

<hr class="double">

<div class="row big bold">
  <span>TOTAL VENTAS:</span>
  <span>$<?php echo number_format($turno['venta_total_dia'], 2, ',', '.'); ?></span>
</div>
<div class="row"><span>Pares vendidos:</span><span class="bold"><?php echo $turno['pares_vendidos']; ?></span></div>

<hr>

<!-- EFECTIVO -->
<div class="center bold">--- EFECTIVO ---</div>
<?php
$efectivo_esperado = $turno['monto_inicial'] + ($por_metodo['efectivo']['total'] ?? 0) + ($por_metodo['credito']['total'] ?? 0) + $turno['cobro_cuotas_credito'] - $turno['gastos_dia'];
$diferencia = $turno['efectivo_cierre'] - $efectivo_esperado;
?>
<div class="row"><span>Efectivo esperado:</span><span>$<?php echo number_format($efectivo_esperado, 2, ',', '.'); ?></span></div>
<div class="row"><span>Efectivo contado:</span><span class="bold">$<?php echo number_format($turno['efectivo_cierre'], 2, ',', '.'); ?></span></div>
<div class="row bold <?php echo $diferencia >= 0 ? '' : ''; ?>">
  <span>Diferencia:</span>
  <span><?php echo $diferencia >= 0 ? '+' : ''; ?>$<?php echo number_format($diferencia, 2, ',', '.'); ?></span>
</div>

<?php if (!empty($turno['numero_lote'])): ?>
<hr>
<div class="row"><span>N° Lote tarjetas:</span><span class="bold"><?php echo htmlspecialchars($turno['numero_lote']); ?></span></div>
<?php endif; ?>

<?php if ($turno['notas_cierre']): ?>
<hr>
<div class="bold">Notas:</div>
<div><?php echo nl2br(htmlspecialchars($turno['notas_cierre'])); ?></div>
<?php endif; ?>

<hr>

<!-- DETALLE DE VENTAS -->
<?php if (!empty($ventas)): ?>
<div class="center bold">--- DETALLE DE VENTAS ---</div>
<?php foreach ($ventas as $v): ?>
<div style="margin: 3px 0; border-bottom: 1px dotted #ccc; padding-bottom: 3px;">
  <div class="bold"><?php echo htmlspecialchars(mb_substr($v['producto_nombre'], 0, 28)); ?></div>
  <div class="row-indent">
    <span>
      <?php echo $v['cantidad']; ?>u x $<?php echo number_format($v['precio_unitario'], 2, ',', '.'); ?>
      <?php echo ($v['talle'] ? ' T:' . $v['talle'] : ''); ?>
      <?php echo ($v['color'] ? ' C:' . $v['color'] : ''); ?>
    </span>
    <span class="bold">$<?php echo number_format($v['subtotal'], 2, ',', '.'); ?></span>
  </div>
  <div class="right" style="font-size:10px; color:#555;">
    <?php echo date('H:i', strtotime($v['fecha_venta'])); ?> |
    <?php echo ucfirst($v['metodo_pago']); ?>
    <?php if (!empty($v['numero_cupon'])): ?> | Cup: <?php echo htmlspecialchars($v['numero_cupon']); ?><?php endif; ?>
    <?php if (!empty($v['transferencia_cliente'])): ?> | <?php echo htmlspecialchars($v['transferencia_cliente']); ?><?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<hr>

<!-- FIRMA -->
<div style="margin-top: 20px;">
  <div>Firma responsable:</div>
  <div style="border-bottom: 1px solid #000; margin-top: 30px; margin-bottom: 5px;"></div>
  <div class="center" style="font-size:10px;">Gerente / Cajero</div>
</div>

<hr>
<div class="center" style="font-size: 10px;">
  Impreso: <?php echo date('d/m/Y H:i:s'); ?>
</div>

<!-- Botón imprimir (no se imprime) -->
<div class="no-print" style="margin-top: 16px; text-align: center;">
  <button onclick="window.print()" style="padding: 8px 20px; font-size: 14px; cursor: pointer; background: #198754; color: white; border: none; border-radius: 4px;">
    🖨 Imprimir
  </button>
</div>

<script>
// Auto-print al cargar
window.addEventListener('load', function() {
    setTimeout(function() { window.print(); }, 400);
});
</script>
</body>
</html>
