<?php
require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$orden = $input['orden'] ?? [];

if (!is_array($orden) || empty($orden)) {
    echo json_encode(['success' => false, 'mensaje' => 'Orden inválido']);
    exit;
}

$stmt = mysqli_prepare($conn, "UPDATE imagenes_productos SET orden = ? WHERE id = ?");
foreach ($orden as $pos => $id) {
    $id  = (int)$id;
    $pos = (int)$pos;
    mysqli_stmt_bind_param($stmt, 'ii', $pos, $id);
    mysqli_stmt_execute($stmt);
}

echo json_encode(['success' => true, 'mensaje' => 'Orden actualizado']);
