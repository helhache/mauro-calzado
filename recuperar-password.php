<?php
/**
 * RECUPERACIÓN DE CONTRASEÑA - PASO 1
 *
 * Funcionalidad:
 * - Formulario para ingresar email
 * - Validar que el email existe en la BD
 * - Generar token único de recuperación
 * - Enviar email con link de reseteo
 * - Token válido por 1 hora
 */

require_once('includes/config.php');
require_once('includes/email-config.php');

$titulo_pagina = "Recuperar Contraseña";

$mensaje_exito = '';
$mensaje_error = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['recuperar'])) {

    $email = limpiarDato($_POST['email']);

    // Validar email
    if (empty($email)) {
        $mensaje_error = 'Por favor ingresa tu email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = 'Email no válido';
    } else {

        // Buscar usuario por email
        $stmt = mysqli_prepare($conn, "SELECT id, nombre, apellido, email FROM usuarios WHERE email = ? AND activo = 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $usuario = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($usuario) {
            // Usuario encontrado - Generar token
            $token = bin2hex(random_bytes(32)); // Token de 64 caracteres
            $usuario_id = $usuario['id'];
            $fecha_expiracion = date('Y-m-d H:i:s', strtotime('+1 hour')); // Expira en 1 hora
            $ip_solicitud = $_SERVER['REMOTE_ADDR'];

            // Invalidar tokens anteriores del usuario
            $stmt_invalidar = mysqli_prepare($conn, "UPDATE password_reset_tokens SET usado = 1 WHERE usuario_id = ? AND usado = 0");
            mysqli_stmt_bind_param($stmt_invalidar, "i", $usuario_id);
            mysqli_stmt_execute($stmt_invalidar);
            mysqli_stmt_close($stmt_invalidar);

            // Insertar nuevo token
            $stmt_token = mysqli_prepare($conn,
                "INSERT INTO password_reset_tokens (usuario_id, email, token, fecha_expiracion, ip_solicitud)
                 VALUES (?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt_token, "issss", $usuario_id, $email, $token, $fecha_expiracion, $ip_solicitud);

            if (mysqli_stmt_execute($stmt_token)) {
                mysqli_stmt_close($stmt_token);

                // Generar link de recuperación
                $link_recuperacion = SITE_URL . "restablecer-password.php?token=" . $token;

                // Preparar email
                $nombre_completo = $usuario['nombre'] . ' ' . $usuario['apellido'];
                $asunto = "Recuperación de Contraseña - Mauro Calzado";

                $contenido_email = '
                    <h2>Recuperación de Contraseña</h2>
                    <p>Hola <strong>' . htmlspecialchars($nombre_completo) . '</strong>,</p>
                    <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en Mauro Calzado.</p>
                    <p>Si fuiste tú quien solicitó este cambio, haz clic en el siguiente botón:</p>
                    <p style="text-align: center;">
                        <a href="' . $link_recuperacion . '" class="button">Restablecer mi Contraseña</a>
                    </p>
                    <p>O copia y pega este enlace en tu navegador:</p>
                    <p style="background: #f8f9fa; padding: 15px; border-radius: 5px; word-break: break-all;">
                        <a href="' . $link_recuperacion . '">' . $link_recuperacion . '</a>
                    </p>
                    <p><strong>⏰ Este enlace expirará en 1 hora.</strong></p>
                    <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;">
                        <strong>⚠️ ¿No solicitaste este cambio?</strong><br>
                        Si no fuiste tú, ignora este email. Tu contraseña permanecerá sin cambios.
                    </p>
                    <p style="margin-top: 20px; color: #666; font-size: 13px;">
                        <strong>Datos de seguridad:</strong><br>
                        IP de solicitud: ' . $ip_solicitud . '<br>
                        Fecha: ' . date('d/m/Y H:i:s') . '
                    </p>
                ';

                $html_email = plantillaEmail("Recuperación de Contraseña", $contenido_email);

                // Enviar email
                $email_enviado = enviarEmail(
                    $email,
                    $nombre_completo,
                    $asunto,
                    $html_email
                );

                if ($email_enviado) {
                    $mensaje_exito = '¡Perfecto! Hemos enviado un email a <strong>' . htmlspecialchars($email) . '</strong> con las instrucciones para restablecer tu contraseña. Revisa tu bandeja de entrada (y también la carpeta de spam).';
                } else {
                    $mensaje_error = 'Error al enviar el email. Por favor, intenta nuevamente o contacta con soporte.';
                }

            } else {
                $mensaje_error = 'Error al procesar la solicitud. Intenta nuevamente.';
            }

        } else {
            // IMPORTANTE: Por seguridad, NO revelamos si el email existe o no
            // Mostramos el mismo mensaje de éxito
            $mensaje_exito = '¡Perfecto! Si existe una cuenta con el email <strong>' . htmlspecialchars($email) . '</strong>, recibirás un email con las instrucciones para restablecer tu contraseña.';
        }
    }
}

require_once('includes/header.php');
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">

            <!-- Card principal -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">

                    <!-- Icono y título -->
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <i class="bi bi-key-fill text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h1 class="h3 fw-bold mb-2">¿Olvidaste tu contraseña?</h1>
                        <p class="text-muted">
                            No te preocupes, te enviaremos un email con las instrucciones para recuperarla.
                        </p>
                    </div>

                    <!-- Mensajes -->
                    <?php if ($mensaje_exito): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?php echo $mensaje_exito; ?>
                        </div>

                        <div class="alert alert-info" role="alert">
                            <h6 class="fw-bold mb-2">
                                <i class="bi bi-info-circle-fill me-2"></i>¿No recibiste el email?
                            </h6>
                            <ul class="mb-0 small">
                                <li>Revisa tu carpeta de <strong>SPAM</strong> o <strong>Correo no deseado</strong></li>
                                <li>Verifica que el email sea correcto</li>
                                <li>Espera unos minutos, puede tardar en llegar</li>
                                <li>Intenta solicitar un nuevo email</li>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($mensaje_error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo $mensaje_error; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Formulario -->
                    <form method="POST" action="" novalidate>

                        <div class="mb-4">
                            <label for="email" class="form-label fw-semibold">
                                <i class="bi bi-envelope-fill text-primary me-1"></i>
                                Email de tu cuenta
                            </label>
                            <input type="email"
                                   class="form-control form-control-lg"
                                   id="email"
                                   name="email"
                                   placeholder="tu-email@ejemplo.com"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   required
                                   autofocus>
                            <small class="text-muted">
                                Ingresa el email con el que te registraste
                            </small>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" name="recuperar" class="btn btn-primary btn-lg">
                                <i class="bi bi-send-fill me-2"></i>
                                Enviar Email de Recuperación
                            </button>
                        </div>

                    </form>

                    <hr class="my-4">

                    <!-- Links adicionales -->
                    <div class="text-center">
                        <p class="mb-2">
                            <a href="login.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left me-1"></i>
                                Volver al Login
                            </a>
                        </p>
                        <p class="mb-0">
                            <small class="text-muted">
                                ¿No tienes cuenta?
                                <a href="registro.php" class="text-primary">Regístrate aquí</a>
                            </small>
                        </p>
                    </div>

                </div>
            </div>

            <!-- Información de seguridad -->
            <div class="card border-0 bg-light mt-3">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-2">
                        <i class="bi bi-shield-check text-success me-1"></i>
                        Seguridad
                    </h6>
                    <small class="text-muted">
                        El link de recuperación expira en <strong>1 hora</strong> y solo puede usarse una vez.
                        Si no solicitaste este cambio, tu cuenta permanece segura.
                    </small>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once('includes/footer.php'); ?>
