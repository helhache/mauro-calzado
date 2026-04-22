<?php
require_once '../../includes/config.php';
require_once '../../includes/verificar-admin.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

$tipo = limpiarDato($_POST['tipo'] ?? '');
$errores = [];

switch ($tipo) {
    case 'general':
        $campos = [
            'general_nombre_tienda'  => limpiarDato($_POST['nombre_tienda']  ?? ''),
            'general_slogan'         => limpiarDato($_POST['slogan']          ?? ''),
            'general_telefono'       => limpiarDato($_POST['telefono']        ?? ''),
            'general_email'          => limpiarDato($_POST['email']           ?? ''),
            'general_direccion'      => limpiarDato($_POST['direccion']       ?? ''),
            'general_ciudad'         => limpiarDato($_POST['ciudad']          ?? ''),
        ];
        foreach ($campos as $clave => $valor) {
            if (!guardarConfig($clave, $valor)) {
                $errores[] = $clave;
            }
        }
        break;

    case 'email':
        $campos = [
            'email_host'         => limpiarDato($_POST['host']         ?? ''),
            'email_port'         => limpiarDato($_POST['port']         ?? '587'),
            'email_encryption'   => limpiarDato($_POST['encryption']   ?? 'tls'),
            'email_username'     => limpiarDato($_POST['username']     ?? ''),
            'email_password'     => limpiarDato($_POST['password']     ?? ''),
            'email_from_address' => limpiarDato($_POST['from_address'] ?? ''),
            'email_from_name'    => limpiarDato($_POST['from_name']    ?? ''),
        ];
        // Validar encriptación
        if (!in_array($campos['email_encryption'], ['tls', 'ssl'])) {
            $campos['email_encryption'] = 'tls';
        }
        // Validar puerto
        $campos['email_port'] = (string)(int)$campos['email_port'];
        foreach ($campos as $clave => $valor) {
            if (!guardarConfig($clave, $valor)) {
                $errores[] = $clave;
            }
        }
        break;

    case 'seguridad':
        $min_password = max(6, min(32, (int)($_POST['min_password'] ?? 8)));
        $max_intentos = max(3, min(20, (int)($_POST['max_intentos'] ?? 5)));
        $campos = [
            'seguridad_min_password' => (string)$min_password,
            'seguridad_max_intentos' => (string)$max_intentos,
        ];
        foreach ($campos as $clave => $valor) {
            if (!guardarConfig($clave, $valor)) {
                $errores[] = $clave;
            }
        }
        break;

    case 'mantenimiento':
        $activo = isset($_POST['mantenimiento_activo']) && $_POST['mantenimiento_activo'] == '1' ? '1' : '0';
        if (!guardarConfig('mantenimiento_activo', $activo)) {
            $errores[] = 'mantenimiento_activo';
        }
        break;

    default:
        echo json_encode(['success' => false, 'mensaje' => 'Tipo de configuración inválido']);
        exit;
}

if (empty($errores)) {
    echo json_encode(['success' => true, 'mensaje' => 'Configuración guardada correctamente']);
} else {
    echo json_encode(['success' => false, 'mensaje' => 'Error al guardar algunos valores']);
}
