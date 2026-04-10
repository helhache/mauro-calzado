<?php
require_once('../../includes/config.php');
require_once('../../includes/verificar-admin.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id   = isset($data['id']) ? intval($data['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'mensaje' => 'ID inválido']);
    exit;
}

// Obtener nombre de imagen antes de borrar
$stmt = mysqli_prepare($conn, "SELECT imagen FROM banner_slides WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$row) {
    echo json_encode(['success' => false, 'mensaje' => 'Slide no encontrada']);
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM banner_slides WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);

if (mysqli_stmt_execute($stmt)) {
    // Eliminar archivo de imagen
    if ($row['imagen']) {
        $ruta = __DIR__ . '/../../img/banners/' . $row['imagen'];
        if (file_exists($ruta)) {
            unlink($ruta);
        }
    }
    echo json_encode(['success' => true, 'mensaje' => 'Slide eliminada']);
} else {
    echo json_encode(['success' => false, 'mensaje' => 'Error al eliminar']);
}

mysqli_stmt_close($stmt);
