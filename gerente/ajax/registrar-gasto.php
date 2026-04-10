<?php
require_once('../../includes/config.php');
require_once('../../includes/verificar-gerente.php');
header('Content-Type: application/json');

$sucursal_id = obtenerSucursalGerente();
$turno_id    = (int)($_POST['turno_id'] ?? 0);
$tipo        = limpiarDato($_POST['tipo'] ?? '');
$concepto    = limpiarDato($_POST['concepto'] ?? '');
$monto       = floatval($_POST['monto'] ?? 0);
$descripcion = !empty($_POST['descripcion']) ? limpiarDato($_POST['descripcion']) : null;
$registrado_por = $_SESSION['usuario_id'];

$sql = "INSERT INTO gastos_sucursal (
        turno_id, sucursal_id, concepto, monto, tipo, descripcion, fecha_gasto, registrado_por
    ) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?)";
    
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iisdssi", 
    $turno_id, $sucursal_id, $concepto, $monto, $tipo, $descripcion, $registrado_por
);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
}
mysqli_close($conn);
?>
