<?php
require_once('../includes/config.php');
require_once('../includes/verificar-admin.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

$producto_id = (int)($_POST['producto_id'] ?? 0);
if ($producto_id <= 0) {
    echo json_encode(['success' => false, 'mensaje' => 'ID de producto inválido']);
    exit;
}

if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'mensaje' => 'No se recibió ningún archivo']);
    exit;
}

$file = $_FILES['imagen'];
$tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $tipos_permitidos)) {
    echo json_encode(['success' => false, 'mensaje' => 'Tipo de archivo no permitido. Solo JPG, PNG, WEBP o GIF.']);
    exit;
}

if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'mensaje' => 'El archivo supera el máximo de 5 MB']);
    exit;
}

$extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
$extension = $extensiones[$mime];
$nombre_archivo = 'prod_' . $producto_id . '_' . uniqid() . '.' . $extension;
$directorio = __DIR__ . '/../img/productos/';
$ruta_destino = $directorio . $nombre_archivo;

if (!move_uploaded_file($file['tmp_name'], $ruta_destino)) {
    echo json_encode(['success' => false, 'mensaje' => 'Error al guardar la imagen en el servidor']);
    exit;
}

// Obtener el siguiente orden
$stmt_orden = mysqli_prepare($conn, "SELECT COALESCE(MAX(orden), 0) + 1 as siguiente FROM imagenes_productos WHERE producto_id = ?");
mysqli_stmt_bind_param($stmt_orden, 'i', $producto_id);
mysqli_stmt_execute($stmt_orden);
$orden = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_orden))['siguiente'];

$stmt = mysqli_prepare($conn, "INSERT INTO imagenes_productos (producto_id, imagen, orden) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, 'isi', $producto_id, $nombre_archivo, $orden);

if (mysqli_stmt_execute($stmt)) {
    $nuevo_id = mysqli_insert_id($conn);
    echo json_encode([
        'success' => true,
        'mensaje' => 'Imagen subida correctamente',
        'imagen'  => ['id' => $nuevo_id, 'imagen' => $nombre_archivo, 'orden' => $orden]
    ]);
} else {
    unlink($ruta_destino);
    echo json_encode(['success' => false, 'mensaje' => 'Error al guardar en la base de datos']);
}
