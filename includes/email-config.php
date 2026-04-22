<?php
/**
 * CONFIGURACIÓN DE EMAIL - PHPMailer con Gmail
 *
 * INSTRUCCIONES PARA CONFIGURAR GMAIL:
 *
 * 1. Crear cuenta de Gmail (si no tienes una para el proyecto)
 * 2. Activar "Verificación en 2 pasos":
 *    - Ir a: https://myaccount.google.com/security
 *    - Buscar "Verificación en 2 pasos" y activarla
 *
 * 3. Crear "Contraseña de aplicación":
 *    - Ir a: https://myaccount.google.com/apppasswords
 *    - Seleccionar app: "Correo"
 *    - Seleccionar dispositivo: "Otro (nombre personalizado)"
 *    - Escribir: "Mauro Calzado"
 *    - Copiar la contraseña de 16 caracteres generada
 *
 * 4. Pegar esa contraseña en EMAIL_PASSWORD abajo
 *
 * ALTERNATIVA: Usar mailtrap.io para testing (sin Gmail real)
 */

// ============================================================================
// CONFIGURACIÓN DE EMAIL
// ============================================================================

// Lee configuración desde BD (fallback a valores por defecto si la BD no tiene el valor)
define('EMAIL_HOST',         obtenerConfig('email_host',         'smtp.gmail.com'));
define('EMAIL_PORT',         (int)obtenerConfig('email_port',    587));
define('EMAIL_ENCRYPTION',   obtenerConfig('email_encryption',   'tls'));
define('EMAIL_USERNAME',     obtenerConfig('email_username',     ''));
define('EMAIL_PASSWORD',     obtenerConfig('email_password',     ''));
define('EMAIL_FROM_ADDRESS', obtenerConfig('email_from_address', 'noreply@maurocalzado.com'));
define('EMAIL_FROM_NAME',    obtenerConfig('email_from_name',    'Mauro Calzado'));
define('EMAIL_CHARSET', 'UTF-8');
define('EMAIL_DEBUG', 0);

/**
 * FUNCIÓN AUXILIAR: Enviar email usando PHPMailer
 *
 * @param string $destinatario Email del destinatario
 * @param string $nombre_destinatario Nombre del destinatario
 * @param string $asunto Asunto del email
 * @param string $cuerpo_html Contenido HTML del email
 * @param string $cuerpo_texto Contenido texto plano (opcional)
 * @return bool True si se envió correctamente, False si hubo error
 */
function enviarEmail($destinatario, $nombre_destinatario, $asunto, $cuerpo_html, $cuerpo_texto = '') {

    // Verificar si PHPMailer está disponible
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Intentar cargar PHPMailer
        $phpmailer_path = __DIR__ . '/../vendor/autoload.php';

        if (file_exists($phpmailer_path)) {
            require_once $phpmailer_path;
        } else {
            error_log("PHPMailer no está instalado. Ejecutar: composer require phpmailer/phpmailer");
            return false;
        }
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        // Configuración del servidor
        $mail->isSMTP();
        $mail->Host = EMAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = EMAIL_USERNAME;
        $mail->Password = EMAIL_PASSWORD;
        $mail->SMTPSecure = EMAIL_ENCRYPTION;
        $mail->Port = EMAIL_PORT;
        $mail->CharSet = EMAIL_CHARSET;

        // Debug
        $mail->SMTPDebug = EMAIL_DEBUG;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer [$level]: $str");
        };

        // Remitente
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);

        // Destinatario
        $mail->addAddress($destinatario, $nombre_destinatario);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $cuerpo_html;
        $mail->AltBody = $cuerpo_texto ?: strip_tags($cuerpo_html);

        // Enviar
        $resultado = $mail->send();

        if ($resultado) {
            error_log("Email enviado exitosamente a: $destinatario");
        }

        return $resultado;

    } catch (Exception $e) {
        error_log("Error al enviar email: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * PLANTILLA HTML PARA EMAILS
 *
 * @param string $titulo Título del email
 * @param string $contenido Contenido HTML principal
 * @return string HTML completo del email
 */
function plantillaEmail($titulo, $contenido) {
    return '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($titulo) . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                background-color: #f4f4f4;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 600px;
                margin: 20px auto;
                background: #ffffff;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #0047AB 0%, #003d96 100%);
                color: #ffffff;
                padding: 30px 20px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 28px;
            }
            .content {
                padding: 30px 20px;
            }
            .button {
                display: inline-block;
                padding: 12px 30px;
                background-color: #0047AB;
                color: #ffffff !important;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
                font-weight: bold;
            }
            .button:hover {
                background-color: #003d96;
            }
            .footer {
                background-color: #f8f9fa;
                padding: 20px;
                text-align: center;
                font-size: 12px;
                color: #666;
                border-top: 1px solid #dee2e6;
            }
            .footer a {
                color: #0047AB;
                text-decoration: none;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🥿 Mauro Calzado</h1>
            </div>
            <div class="content">
                ' . $contenido . '
            </div>
            <div class="footer">
                <p>
                    <strong>Mauro Calzado</strong><br>
                    San Fernando del Valle de Catamarca, Argentina<br>
                    Teléfono: (383) 123-4567<br>
                    <a href="mailto:info@maurocalzado.com">info@maurocalzado.com</a>
                </p>
                <p style="margin-top: 15px; color: #999;">
                    Este es un email automático, por favor no responder a esta dirección.
                </p>
            </div>
        </div>
    </body>
    </html>
    ';
}
?>
