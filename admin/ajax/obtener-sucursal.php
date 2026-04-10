<?php
/**
 * AJAX: Obtener datos de una sucursal para editar
 */

require_once('../../includes/config.php');
require_once('../../includes/verificar-admin.php');

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

$id = (int)$_GET['id'];

$sql = "SELECT * FROM sucursales WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if ($sucursal = mysqli_fetch_assoc($resultado)) {
    echo json_encode([
        'success' => true,
        'sucursal' => $sucursal
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Sucursal no encontrada'
    ]);
}

mysqli_close($conn);
?>
