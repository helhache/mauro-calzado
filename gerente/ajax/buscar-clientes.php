<?php
require_once('../../includes/config.php');
require_once('../../includes/verificar-gerente.php');
header('Content-Type: application/json');

$q = limpiarDato($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$like = '%' . $q . '%';
$sql = "SELECT id, nombre, apellido, email, dni
        FROM usuarios
        WHERE activo = 1 AND rol_id = 1
        AND (nombre LIKE ? OR apellido LIKE ? OR email LIKE ? OR dni LIKE ?)
        ORDER BY nombre, apellido
        LIMIT 8";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssss", $like, $like, $like, $like);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$clientes = [];
while ($row = mysqli_fetch_assoc($result)) {
    $clientes[] = [
        'id'     => $row['id'],
        'nombre' => $row['nombre'] . ' ' . $row['apellido'],
        'email'  => $row['email'],
        'dni'    => $row['dni'] ?? ''
    ];
}

echo json_encode($clientes);
mysqli_close($conn);
?>
