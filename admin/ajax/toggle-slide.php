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

$stmt = mysqli_prepare($conn, "UPDATE banner_slides SET activo = NOT activo WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);

if (mysqli_stmt_execute($stmt)) {
    // Obtener nuevo estado
    $stmt2 = mysqli_prepare($conn, "SELECT activo FROM banner_slides WHERE id = ?");
    mysqli_stmt_bind_param($stmt2, 'i', $id);
    mysqli_stmt_execute($stmt2);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
    mysqli_stmt_close($stmt2);

    echo json_encode(['success' => true, 'activo' => (int)$row['activo']]);
} else {
    echo json_encode(['success' => false, 'mensaje' => 'Error al cambiar estado']);
}

mysqli_stmt_close($stmt);
