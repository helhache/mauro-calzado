<?php
require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'mensaje' => 'ID inválido']);
    exit;
}

// Obtener nombre de archivo antes de eliminar
$stmt = mysqli_prepare($conn, "SELECT imagen FROM imagenes_productos WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$row) {
    echo json_encode(['success' => false, 'mensaje' => 'Imagen no encontrada']);
    exit;
}

$stmt_del = mysqli_prepare($conn, "DELETE FROM imagenes_productos WHERE id = ?");
mysqli_stmt_bind_param($stmt_del, 'i', $id);

if (mysqli_stmt_execute($stmt_del)) {
    $ruta = __DIR__ . '/../img/productos/' . $row['imagen'];
    if (file_exists($ruta)) {
        unlink($ruta);
    }
    echo json_encode(['success' => true, 'mensaje' => 'Imagen eliminada correctamente']);
} else {
    echo json_encode(['success' => false, 'mensaje' => 'Error al eliminar de la base de datos']);
}
