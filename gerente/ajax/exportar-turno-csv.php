<?php
/**
 * Exportar cierre de turno como CSV (Excel)
 */
require_once('../../includes/config.php');
require_once('../../includes/verificar-gerente.php');

$turno_id    = (int)($_GET['id'] ?? 0);
$sucursal_id = obtenerSucursalGerente();

if (!$turno_id) { die('ID requerido'); }

// Obtener turno
$sql = "SELECT tc.*, s.nombre as sucursal_nombre
        FROM turnos_caja tc
        INNER JOIN sucursales s ON tc.sucursal_id = s.id
        WHERE tc.id = ? AND tc.sucursal_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $turno_id, $sucursal_id);
mysqli_stmt_execute($stmt);
$turno = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$turno) { die('Turno no encontrado'); }

// Obtener ventas
$sql = "SELECT * FROM ventas_diarias WHERE turno_id = ? ORDER BY fecha_venta ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $turno_id);
mysqli_stmt_execute($stmt);
$ventas = [];
$res = mysqli_stmt_get_result($stmt);
while ($v = mysqli_fetch_assoc($res)) { $ventas[] = $v; }

$filename = 'cierre-turno-' . $turno_id . '-' . date('Ymd-His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache');

// BOM para Excel
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// Info del turno
fputcsv($out, ['RESUMEN DE CIERRE DE TURNO'], ';');
fputcsv($out, ['Sucursal', $turno['sucursal_nombre']], ';');
fputcsv($out, ['Turno #', $turno_id], ';');
fputcsv($out, ['Turno', ucfirst($turno['turno'])], ';');
fputcsv($out, ['Apertura', date('d/m/Y H:i', strtotime($turno['fecha_apertura']))], ';');
fputcsv($out, ['Cierre', $turno['fecha_cierre'] ? date('d/m/Y H:i', strtotime($turno['fecha_cierre'])) : date('d/m/Y H:i')], ';');
fputcsv($out, [], ';');

// Resumen financiero
fputcsv($out, ['--- RESUMEN FINANCIERO ---'], ';');
fputcsv($out, ['Monto inicial', number_format($turno['monto_inicial'], 2, ',', '.')], ';');
fputcsv($out, ['Efectivo ventas', number_format($turno['efectivo_ventas'], 2, ',', '.')], ';');
fputcsv($out, ['Tarjeta ventas', number_format($turno['tarjeta_ventas'], 2, ',', '.')], ';');
fputcsv($out, ['Transferencia ventas', number_format($turno['transferencia_ventas'], 2, ',', '.')], ';');
fputcsv($out, ['Go Cuotas ventas', number_format($turno['go_cuotas_ventas'], 2, ',', '.')], ';');
fputcsv($out, ['Crédito (1ra cuota)', number_format($turno['credito_ventas'], 2, ',', '.')], ';');
fputcsv($out, ['Cobro cuotas', number_format($turno['cobro_cuotas_credito'], 2, ',', '.')], ';');
fputcsv($out, ['Gastos del día', number_format($turno['gastos_dia'], 2, ',', '.')], ';');
fputcsv($out, ['TOTAL VENTAS', number_format($turno['venta_total_dia'], 2, ',', '.')], ';');
fputcsv($out, ['Pares vendidos', $turno['pares_vendidos']], ';');
fputcsv($out, [], ';');

// Efectivo
fputcsv($out, ['--- EFECTIVO ---'], ';');
fputcsv($out, ['Efectivo contado', number_format($turno['efectivo_cierre'], 2, ',', '.')], ';');
fputcsv($out, ['Diferencia caja', number_format($turno['diferencia_caja'], 2, ',', '.')], ';');
if (!empty($turno['numero_lote'])) {
    fputcsv($out, ['N° Lote tarjetas', $turno['numero_lote']], ';');
}
if (!empty($turno['notas_cierre'])) {
    fputcsv($out, ['Notas cierre', $turno['notas_cierre']], ';');
}
fputcsv($out, [], ';');

// Detalle de ventas
if (!empty($ventas)) {
    fputcsv($out, ['--- DETALLE DE VENTAS ---'], ';');
    fputcsv($out, ['Hora', 'Producto', 'Talle', 'Color', 'Cant.', 'Precio Unit.', 'Subtotal', 'Método', 'Tipo', 'N° Cupón', 'Cliente transf.', 'Cliente ID'], ';');
    foreach ($ventas as $v) {
        fputcsv($out, [
            date('H:i', strtotime($v['fecha_venta'])),
            $v['producto_nombre'],
            $v['talle'] ?? '',
            $v['color'] ?? '',
            $v['cantidad'],
            number_format($v['precio_unitario'], 2, ',', '.'),
            number_format($v['subtotal'], 2, ',', '.'),
            ucfirst($v['metodo_pago']),
            ucfirst($v['tipo_venta']),
            $v['numero_cupon'] ?? '',
            $v['transferencia_cliente'] ?? '',
            $v['cliente_id'] ?? '',
        ], ';');
    }
}

fclose($out);
mysqli_close($conn);
exit;
?>
