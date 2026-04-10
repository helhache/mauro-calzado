<?php
/**
 * AJAX: Guardar sucursal (crear o editar)
 */

require_once('../../includes/config.php');
require_once('../../includes/verificar-admin.php');

header('Content-Type: application/json');

// Validar datos requeridos
if (empty($_POST['nombre']) || empty($_POST['direccion']) || empty($_POST['ciudad']) || empty($_POST['provincia'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
    exit;
}

$sucursal_id  = !empty($_POST['sucursal_id']) ? (int)$_POST['sucursal_id'] : null;
$nombre       = limpiarDato($_POST['nombre']);
$direccion    = limpiarDato($_POST['direccion']);
$ciudad       = limpiarDato($_POST['ciudad']);
$provincia    = limpiarDato($_POST['provincia']);
$codigo_postal = !empty($_POST['codigo_postal']) ? limpiarDato($_POST['codigo_postal']) : null;
$telefono      = !empty($_POST['telefono'])       ? limpiarDato($_POST['telefono'])       : null;
$email         = !empty($_POST['email'])          ? limpiarDato($_POST['email'])          : null;

$horario_apertura_manana = $_POST['horario_apertura_manana'] ?? '09:00:00';
$horario_cierre_manana = $_POST['horario_cierre_manana'] ?? '13:00:00';
$horario_apertura_tarde = $_POST['horario_apertura_tarde'] ?? '17:30:00';
$horario_cierre_tarde = $_POST['horario_cierre_tarde'] ?? '21:30:00';
$trabaja_sabado = isset($_POST['trabaja_sabado']) ? 1 : 0;
$trabaja_domingo = isset($_POST['trabaja_domingo']) ? 1 : 0;

if ($sucursal_id) {
    // EDITAR
    $sql = "UPDATE sucursales SET 
            nombre = ?,
            direccion = ?,
            ciudad = ?,
            provincia = ?,
            codigo_postal = ?,
            telefono = ?,
            email = ?,
            horario_apertura_manana = ?,
            horario_cierre_manana = ?,
            horario_apertura_tarde = ?,
            horario_cierre_tarde = ?,
            trabaja_sabado = ?,
            trabaja_domingo = ?
            WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssssssssiii", 
        $nombre, $direccion, $ciudad, $provincia, $codigo_postal, 
        $telefono, $email, 
        $horario_apertura_manana, $horario_cierre_manana,
        $horario_apertura_tarde, $horario_cierre_tarde,
        $trabaja_sabado, $trabaja_domingo, $sucursal_id
    );
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Sucursal actualizada exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . mysqli_error($conn)]);
    }
    
} else {
    // CREAR
    $sql = "INSERT INTO sucursales (
            nombre, direccion, ciudad, provincia, codigo_postal, telefono, email,
            horario_apertura_manana, horario_cierre_manana,
            horario_apertura_tarde, horario_cierre_tarde,
            trabaja_sabado, trabaja_domingo, activo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssssssssii",
        $nombre, $direccion, $ciudad, $provincia, $codigo_postal,
        $telefono, $email,
        $horario_apertura_manana, $horario_cierre_manana,
        $horario_apertura_tarde, $horario_cierre_tarde,
        $trabaja_sabado, $trabaja_domingo
    );
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Sucursal creada exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear: ' . mysqli_error($conn)]);
    }
}

mysqli_close($conn);
?>
