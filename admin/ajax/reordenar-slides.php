<?php
require_once('../../includes/config.php');
require_once('../../includes/verificar-admin.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

$data  = json_decode(file_get_contents('php://input'), true);
$orden = isset($data['orden']) ? $data['orden'] : [];

// $orden es un array de IDs en el nuevo orden, ej: [3, 1, 5, 2]
if (!is_array($orden) || empty($orden)) {
    echo json_encode(['success' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}

$stmt = mysqli_prepare($conn, "UPDATE banner_slides SET orden = ? WHERE id = ?");

foreach ($orden as $posicion => $slide_id) {
    $slide_id  = intval($slide_id);
    $posicion  = intval($posicion);
    mysqli_stmt_bind_param($stmt, 'ii', $posicion, $slide_id);
    mysqli_stmt_execute($stmt);
}

mysqli_stmt_close($stmt);
echo json_encode(['success' => true]);
