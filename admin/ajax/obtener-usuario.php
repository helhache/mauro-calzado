<?php
/**
 * AJAX: OBTENER DATOS DE USUARIO
 * 
 * Retorna los datos de un usuario específico en formato JSON
 * Para usar en el modal de edición
 */

require_once('../../includes/config.php');
require_once('../../includes/verificar-admin.php');

header('Content-Type: application/json');

// Verificar que se envió el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de usuario no proporcionado'
    ]);
    exit;
}

$usuario_id = intval($_GET['id']);

// Consulta preparada para obtener usuario con información de sucursal
$query = "SELECT
            u.id,
            u.rol_id,
            u.sucursal_id,
            u.nombre,
            u.apellido,
            u.email,
            u.telefono,
            u.dni,
            u.direccion,
            u.ciudad,
            u.provincia,
            u.codigo_postal,
            u.activo,
            u.verificado,
            u.fecha_registro,
            u.ultimo_acceso,
            s.nombre as sucursal_nombre
          FROM usuarios u
          LEFT JOIN sucursales s ON u.sucursal_id = s.id
          WHERE u.id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $usuario_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($usuario = mysqli_fetch_assoc($result)) {
    // Mapear nombre del rol (no existe tabla roles)
    $roles_nombres = [ROL_CLIENTE => 'Cliente', ROL_GERENTE => 'Gerente', ROL_ADMIN => 'Administrador'];
    $usuario['nombre_rol'] = $roles_nombres[$usuario['rol_id']] ?? 'Desconocido';

    // Formatear fechas
    $usuario['fecha_registro_formateada'] = date('d/m/Y H:i', strtotime($usuario['fecha_registro']));
    
    if ($usuario['ultimo_acceso']) {
        $usuario['ultimo_acceso_formateado'] = date('d/m/Y H:i', strtotime($usuario['ultimo_acceso']));
    } else {
        $usuario['ultimo_acceso_formateado'] = 'Nunca';
    }
    
    echo json_encode([
        'success' => true,
        'usuario' => $usuario
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no encontrado'
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
