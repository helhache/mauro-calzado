<?php
/**
 * ADMIN/AJAX/GUARDAR-SLIDE.PHP
 * Crea o edita una slide del carrusel banner.
 * Acepta multipart/form-data (tiene subida de imagen).
 */

require_once('../../includes/config.php');
require_once('../../includes/verificar-admin.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

$id         = isset($_POST['id'])          ? intval($_POST['id'])              : 0;
$titulo     = isset($_POST['titulo'])      ? limpiarDato($_POST['titulo'])     : '';
$subtitulo  = isset($_POST['subtitulo'])   ? limpiarDato($_POST['subtitulo'])  : '';
$txt_boton  = isset($_POST['texto_boton']) ? limpiarDato($_POST['texto_boton']) : '';
$url_boton  = isset($_POST['url_boton'])   ? limpiarDato($_POST['url_boton'])  : '';
$orden      = isset($_POST['orden'])       ? intval($_POST['orden'])           : 0;

// Imagen — solo obligatoria al crear
$imagen_nueva = '';

if (!empty($_FILES['imagen']['name'])) {
    $ext_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $ext_permitidas, true)) {
        echo json_encode(['success' => false, 'mensaje' => 'Formato de imagen no permitido. Usá JPG, PNG o WebP.']);
        exit;
    }

    if ($_FILES['imagen']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'mensaje' => 'La imagen no puede superar 5 MB.']);
        exit;
    }

    $nombre_archivo = 'banner_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $destino = __DIR__ . '/../../img/banners/' . $nombre_archivo;

    if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
        echo json_encode(['success' => false, 'mensaje' => 'Error al guardar la imagen en el servidor.']);
        exit;
    }

    $imagen_nueva = $nombre_archivo;
}

if ($id > 0) {
    // EDITAR
    if ($imagen_nueva) {
        // Eliminar imagen anterior
        $stmt = mysqli_prepare($conn, "SELECT imagen FROM banner_slides WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($row && $row['imagen']) {
            $ruta_anterior = __DIR__ . '/../../img/banners/' . $row['imagen'];
            if (file_exists($ruta_anterior)) {
                unlink($ruta_anterior);
            }
        }

        $stmt = mysqli_prepare($conn,
            "UPDATE banner_slides SET titulo=?, subtitulo=?, texto_boton=?, url_boton=?, imagen=?, orden=? WHERE id=?"
        );
        mysqli_stmt_bind_param($stmt, 'sssssii', $titulo, $subtitulo, $txt_boton, $url_boton, $imagen_nueva, $orden, $id);
    } else {
        $stmt = mysqli_prepare($conn,
            "UPDATE banner_slides SET titulo=?, subtitulo=?, texto_boton=?, url_boton=?, orden=? WHERE id=?"
        );
        mysqli_stmt_bind_param($stmt, 'ssssii', $titulo, $subtitulo, $txt_boton, $url_boton, $orden, $id);
    }

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'mensaje' => 'Slide actualizada correctamente']);
    } else {
        echo json_encode(['success' => false, 'mensaje' => 'Error al actualizar la slide']);
    }
    mysqli_stmt_close($stmt);

} else {
    // CREAR
    if (!$imagen_nueva) {
        echo json_encode(['success' => false, 'mensaje' => 'Debes subir una imagen para crear una slide.']);
        exit;
    }

    $stmt = mysqli_prepare($conn,
        "INSERT INTO banner_slides (titulo, subtitulo, texto_boton, url_boton, imagen, orden, activo)
         VALUES (?, ?, ?, ?, ?, ?, 1)"
    );
    mysqli_stmt_bind_param($stmt, 'sssssi', $titulo, $subtitulo, $txt_boton, $url_boton, $imagen_nueva, $orden);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'mensaje' => 'Slide creada correctamente', 'id' => mysqli_insert_id($conn)]);
    } else {
        echo json_encode(['success' => false, 'mensaje' => 'Error al crear la slide']);
    }
    mysqli_stmt_close($stmt);
}
