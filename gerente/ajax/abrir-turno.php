<?php
require_once('../../includes/config.php');
require_once('../../includes/verificar-gerente.php');
header('Content-Type: application/json');

$sucursal_id = obtenerSucursalGerente();
$gerente_id = $_SESSION['usuario_id'];

// Verificar que no haya turno abierto
$sql = "SELECT id FROM turnos_caja WHERE sucursal_id = ? AND estado = 'abierto'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $sucursal_id);
mysqli_stmt_execute($stmt);
if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
    echo json_encode(['success' => false, 'message' => 'Ya hay un turno abierto']);
    exit;
}

$turno = $_POST['turno'];
$monto_inicial = floatval($_POST['monto_inicial']);
$notas = $_POST['notas_apertura'] ?? null;

$sql = "INSERT INTO turnos_caja (sucursal_id, gerente_id, fecha_apertura, turno, monto_inicial, notas_apertura, estado) 
        VALUES (?, ?, NOW(), ?, ?, ?, 'abierto')";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iisds", $sucursal_id, $gerente_id, $turno, $monto_inicial, $notas);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al abrir turno']);
}
mysqli_close($conn);
?>
