<?php
require_once('../../includes/config.php');
require_once('../../includes/verificar-gerente.php');
header('Content-Type: application/json');

$turno_id        = (int)($_POST['turno_id'] ?? 0);
$efectivo_cierre = floatval($_POST['efectivo_cierre'] ?? 0);
$notas_cierre    = !empty($_POST['notas_cierre']) ? limpiarDato($_POST['notas_cierre']) : null;
$numero_lote     = !empty($_POST['numero_lote']) ? limpiarDato($_POST['numero_lote']) : null;

// Obtener datos del turno
$sql = "SELECT * FROM turnos_caja WHERE id = ? AND estado = 'abierto'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $turno_id);
mysqli_stmt_execute($stmt);
$turno = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$turno) {
    echo json_encode(['success' => false, 'message' => 'Turno no encontrado o ya cerrado']);
    exit;
}

// Calcular diferencia de efectivo
$efectivo_esperado = $turno['monto_inicial'] + $turno['efectivo_ventas'] + $turno['credito_ventas'] + $turno['cobro_cuotas_credito'] - $turno['gastos_dia'];
$diferencia = $efectivo_cierre - $efectivo_esperado;

// Intentar con numero_lote (columna puede no existir aún)
$sql = "UPDATE turnos_caja SET
        fecha_cierre = NOW(),
        efectivo_cierre = ?,
        diferencia_caja = ?,
        notas_cierre = ?,
        numero_lote = ?,
        estado = 'cerrado'
        WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ddssi", $efectivo_cierre, $diferencia, $notas_cierre, $numero_lote, $turno_id);
    $ok = mysqli_stmt_execute($stmt);
} else {
    // Fallback sin numero_lote
    $sql = "UPDATE turnos_caja SET
            fecha_cierre = NOW(),
            efectivo_cierre = ?,
            diferencia_caja = ?,
            notas_cierre = ?,
            estado = 'cerrado'
            WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ddsi", $efectivo_cierre, $diferencia, $notas_cierre, $turno_id);
    $ok = mysqli_stmt_execute($stmt);
}

if ($ok) {
    echo json_encode(['success' => true, 'turno_id' => $turno_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al cerrar turno: ' . mysqli_error($conn)]);
}
mysqli_close($conn);
?>
