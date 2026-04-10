<?php
/**
 * AJAX: RESETEAR CONTRASEÑA DE USUARIO
 * 
 * Genera una contraseña aleatoria segura y la asigna al usuario
 * La contraseña se retorna para que el admin la copie y entregue al usuario
 * 
 * Solo accesible por administradores
 */

require_once('../../includes/config.php');
require_once('../../includes/verificar-admin.php');

header('Content-Type: application/json');

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

// Recibir datos
$usuario_id = intval($_POST['usuario_id'] ?? 0);

// ============================================================================
// VALIDACIONES
// ============================================================================

if ($usuario_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de usuario inválido'
    ]);
    exit;
}

// Verificar que el usuario existe y obtener sus datos
$stmt = mysqli_prepare($conn, "SELECT id, nombre, apellido, email, rol_id FROM usuarios WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $usuario_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no encontrado'
    ]);
    mysqli_stmt_close($stmt);
    exit;
}

$usuario = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// ============================================================================
// GENERAR CONTRASEÑA ALEATORIA SEGURA
// ============================================================================

/**
 * Genera una contraseña aleatoria segura
 * - Longitud: 10 caracteres
 * - Incluye: mayúsculas, minúsculas, números y un símbolo
 * - Fácil de leer (sin caracteres confusos como O/0, l/1/I)
 */
function generarPasswordSeguro($longitud = 10) {
    // Caracteres permitidos (sin confusos)
    $mayusculas = 'ABCDEFGHJKLMNPQRSTUVWXYZ'; // Sin I, O
    $minusculas = 'abcdefghjkmnpqrstuvwxyz'; // Sin i, l, o
    $numeros = '23456789'; // Sin 0, 1
    $simbolos = '@#$%&*';
    
    $password = '';
    
    // Asegurar al menos uno de cada tipo
    $password .= $mayusculas[random_int(0, strlen($mayusculas) - 1)];
    $password .= $minusculas[random_int(0, strlen($minusculas) - 1)];
    $password .= $numeros[random_int(0, strlen($numeros) - 1)];
    $password .= $simbolos[random_int(0, strlen($simbolos) - 1)];
    
    // Completar con caracteres aleatorios
    $todos = $mayusculas . $minusculas . $numeros . $simbolos;
    for ($i = strlen($password); $i < $longitud; $i++) {
        $password .= $todos[random_int(0, strlen($todos) - 1)];
    }
    
    // Mezclar los caracteres
    $password = str_shuffle($password);
    
    return $password;
}

$nueva_password = generarPasswordSeguro(10);
$password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);

// ============================================================================
// ACTUALIZAR CONTRASEÑA
// ============================================================================

$query = "UPDATE usuarios SET password = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "si", $password_hash, $usuario_id);

if (mysqli_stmt_execute($stmt)) {
    // Obtener nombre del rol
    $roles_nombres = [ROL_CLIENTE => 'Cliente', ROL_GERENTE => 'Gerente', ROL_ADMIN => 'Administrador'];
    $nombre_rol = $roles_nombres[$usuario['rol_id']] ?? 'Usuario';
    
    // Crear notificación
    $titulo_notif = "Contraseña reseteada";
    $mensaje_notif = "Se reseteó la contraseña del {$nombre_rol}: {$usuario['nombre']} {$usuario['apellido']}";
    $url_notif = "usuarios.php?id={$usuario_id}";
    
    $stmt_notif = mysqli_prepare($conn, 
        "INSERT INTO notificaciones (tipo, titulo, mensaje, url, visible_para) 
         VALUES ('sistema', ?, ?, ?, 'admin')"
    );
    mysqli_stmt_bind_param($stmt_notif, "sss", $titulo_notif, $mensaje_notif, $url_notif);
    mysqli_stmt_execute($stmt_notif);
    mysqli_stmt_close($stmt_notif);
    
    // IMPORTANTE: Retornar la contraseña temporal para que el admin la vea
    echo json_encode([
        'success' => true,
        'message' => 'Contraseña reseteada exitosamente',
        'nueva_password' => $nueva_password,
        'usuario_nombre' => $usuario['nombre'] . ' ' . $usuario['apellido'],
        'usuario_email' => $usuario['email']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al resetear la contraseña: ' . mysqli_error($conn)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
