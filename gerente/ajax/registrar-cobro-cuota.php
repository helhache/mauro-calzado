<?php
require_once('../../includes/config.php');
require_once('../../includes/verificar-gerente.php');
header('Content-Type: application/json');

$sucursal_id    = obtenerSucursalGerente();
$turno_id       = (int)($_POST['turno_id'] ?? 0);
$cliente_nombre = limpiarDato($_POST['cliente_nombre'] ?? '');
$cliente_dni    = !empty($_POST['cliente_dni']) ? limpiarDato($_POST['cliente_dni']) : null;
$monto_cobrado  = floatval($_POST['monto_cobrado'] ?? 0);
$numero_cuota   = !empty($_POST['numero_cuota']) ? (int)$_POST['numero_cuota'] : null;
$observaciones  = !empty($_POST['observaciones']) ? limpiarDato($_POST['observaciones']) : null;

$sql = "INSERT INTO cobro_cuotas_credito (
        turno_id, sucursal_id, cliente_nombre, cliente_dni, monto_cobrado, numero_cuota, observaciones
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iissdis", 
    $turno_id, $sucursal_id, $cliente_nombre, $cliente_dni, $monto_cobrado, $numero_cuota, $observaciones
);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
}
mysqli_close($conn);
?>
