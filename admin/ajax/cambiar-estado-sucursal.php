<?php
require_once('../../includes/config.php');
require_once('../../includes/verificar-admin.php');
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)$data['id'];
$activo = (int)$data['activo'];

$sql = "UPDATE sucursales SET activo = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $activo, $id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al cambiar estado']);
}
mysqli_close($conn);
?>
