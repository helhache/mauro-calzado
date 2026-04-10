<?php
/**
 * NEWSLETTER.PHP - Suscripción al newsletter
 * Recibe el POST del footer, guarda en BD y redirige con mensaje flash.
 */

require_once('includes/config.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$email = limpiarDato($_POST['email'] ?? '');
$redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';

// Validar email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['newsletter_mensaje'] = 'Por favor ingresá un email válido.';
    $_SESSION['newsletter_tipo']    = 'warning';
    header('Location: ' . $redirect);
    exit;
}

// Verificar si ya existe
$stmt_check = mysqli_prepare($conn, "SELECT id, activo FROM newsletter WHERE email = ?");
mysqli_stmt_bind_param($stmt_check, 's', $email);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);
$existente = mysqli_fetch_assoc($result_check);
mysqli_stmt_close($stmt_check);

if ($existente) {
    if ($existente['activo']) {
        $_SESSION['newsletter_mensaje'] = 'Este email ya está suscrito. ¡Gracias!';
        $_SESSION['newsletter_tipo']    = 'info';
    } else {
        $stmt_update = mysqli_prepare($conn, "UPDATE newsletter SET activo = 1, fecha_suscripcion = NOW() WHERE email = ?");
        mysqli_stmt_bind_param($stmt_update, 's', $email);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
        $_SESSION['newsletter_mensaje'] = '¡Bienvenido de vuelta! Tu suscripción fue reactivada.';
        $_SESSION['newsletter_tipo']    = 'success';
    }
} else {
    $stmt_insert = mysqli_prepare($conn, "INSERT INTO newsletter (email, activo, fecha_suscripcion) VALUES (?, 1, NOW())");
    mysqli_stmt_bind_param($stmt_insert, 's', $email);
    mysqli_stmt_execute($stmt_insert);
    mysqli_stmt_close($stmt_insert);
    $_SESSION['newsletter_mensaje'] = '¡Gracias por suscribirte! Te avisaremos de nuestras novedades y ofertas.';
    $_SESSION['newsletter_tipo']    = 'success';
}

header('Location: ' . $redirect);
exit;
