<?php
/**
 * RESTABLECER CONTRASEÑA - PASO 2
 *
 * Funcionalidad:
 * - Verificar token válido
 * - Validar que no haya expirado
 * - Formulario para nueva contraseña
 * - Actualizar contraseña en BD
 * - Marcar token como usado
 */

require_once('../includes/config.php');

$titulo_pagina = "Restablecer Contraseña";

$token = isset($_GET['token']) ? limpiarDato($_GET['token']) : '';
$token_valido = false;
$usuario_data = null;
$mensaje_error = '';
$mensaje_exito = '';

// Verificar token
if (!empty($token)) {

    // Buscar token en la BD
    $stmt = mysqli_prepare($conn,
        "SELECT t.id, t.usuario_id, t.email, t.fecha_expiracion, t.usado, u.nombre, u.apellido
         FROM password_reset_tokens t
         INNER JOIN usuarios u ON t.usuario_id = u.id
         WHERE t.token = ?
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $token_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($token_data) {

        // Verificar si el token ya fue usado
        if ($token_data['usado'] == 1) {
            $mensaje_error = 'Este enlace de recuperación ya fue utilizado. Si necesitas restablecer tu contraseña nuevamente, solicita un nuevo enlace.';
        }
        // Verificar si el token expiró
        elseif (strtotime($token_data['fecha_expiracion']) < time()) {
            $mensaje_error = 'Este enlace de recuperación ha expirado. Los enlaces son válidos solo por 1 hora. Por favor, solicita uno nuevo.';
        }
        // Token válido
        else {
            $token_valido = true;
            $usuario_data = $token_data;
        }

    } else {
        $mensaje_error = 'Enlace de recuperación inválido o no encontrado.';
    }

} else {
    $mensaje_error = 'No se proporcionó un token de recuperación.';
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['restablecer']) && $token_valido) {

    $nueva_password = $_POST['nueva_password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';

    // Validaciones
    if (empty($nueva_password)) {
        $mensaje_error = 'Ingresa una nueva contraseña';
    } elseif (strlen($nueva_password) < 6) {
        $mensaje_error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($nueva_password !== $confirmar_password) {
        $mensaje_error = 'Las contraseñas no coinciden';
    } else {

        // Hashear nueva contraseña
        $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);

        // Actualizar contraseña del usuario
        $stmt_update = mysqli_prepare($conn, "UPDATE usuarios SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_update, "si", $password_hash, $usuario_data['usuario_id']);

        if (mysqli_stmt_execute($stmt_update)) {
            mysqli_stmt_close($stmt_update);

            // Marcar token como usado
            $stmt_marcar = mysqli_prepare($conn, "UPDATE password_reset_tokens SET usado = 1 WHERE token = ?");
            mysqli_stmt_bind_param($stmt_marcar, "s", $token);
            mysqli_stmt_execute($stmt_marcar);
            mysqli_stmt_close($stmt_marcar);

            $mensaje_exito = true;
            $token_valido = false; // Ya no mostrar el formulario

        } else {
            $mensaje_error = 'Error al actualizar la contraseña. Intenta nuevamente.';
        }
    }
}

require_once('../includes/header.php');
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">

            <?php if ($mensaje_exito): ?>
                <!-- ÉXITO: Contraseña cambiada -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5 text-center">

                        <div class="mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        </div>

                        <h1 class="h3 fw-bold mb-3">¡Contraseña Restablecida!</h1>

                        <p class="text-muted mb-4">
                            Tu contraseña ha sido actualizada exitosamente.
                            Ahora puedes iniciar sesión con tu nueva contraseña.
                        </p>

                        <div class="alert alert-success" role="alert">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <strong>Por tu seguridad:</strong> Si no reconoces este cambio,
                            contacta inmediatamente con nuestro equipo de soporte.
                        </div>

                        <div class="d-grid gap-2">
                            <a href="<?php echo BASE_PATH; ?>login.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                Iniciar Sesión
                            </a>
                            <a href="<?php echo BASE_PATH; ?>index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-house me-2"></i>
                                Ir al Inicio
                            </a>
                        </div>

                    </div>
                </div>

            <?php elseif ($token_valido): ?>
                <!-- FORMULARIO: Nueva contraseña -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">

                        <!-- Icono y título -->
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="bi bi-shield-lock-fill text-success" style="font-size: 3rem;"></i>
                            </div>
                            <h1 class="h3 fw-bold mb-2">Crear Nueva Contraseña</h1>
                            <p class="text-muted">
                                Hola <strong><?php echo htmlspecialchars($usuario_data['nombre']); ?></strong>,
                                ingresa tu nueva contraseña.
                            </p>
                        </div>

                        <!-- Mensajes de error -->
                        <?php if ($mensaje_error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo $mensaje_error; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Formulario -->
                        <form method="POST" action="" id="formRestablecer" novalidate>

                            <div class="mb-4">
                                <label for="nueva_password" class="form-label fw-semibold">
                                    <i class="bi bi-lock-fill text-primary me-1"></i>
                                    Nueva Contraseña
                                </label>
                                <div class="input-group">
                                    <input type="password"
                                           class="form-control form-control-lg"
                                           id="nueva_password"
                                           name="nueva_password"
                                           placeholder="Mínimo 6 caracteres"
                                           required
                                           autofocus>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('nueva_password', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    La contraseña debe tener al menos 6 caracteres
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="confirmar_password" class="form-label fw-semibold">
                                    <i class="bi bi-lock-fill text-primary me-1"></i>
                                    Confirmar Contraseña
                                </label>
                                <div class="input-group">
                                    <input type="password"
                                           class="form-control form-control-lg"
                                           id="confirmar_password"
                                           name="confirmar_password"
                                           placeholder="Repite la contraseña"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmar_password', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Indicador de fortaleza -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold small">Fortaleza de la contraseña:</label>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted" id="passwordStrengthText">Ingresa una contraseña</small>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" name="restablecer" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    Restablecer Contraseña
                                </button>
                            </div>

                        </form>

                        <!-- Información de seguridad -->
                        <div class="alert alert-info small" role="alert">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            <strong>Recomendaciones:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Usa una combinación de letras, números y símbolos</li>
                                <li>Evita usar información personal (nombre, fecha de nacimiento)</li>
                                <li>No uses la misma contraseña en otros sitios</li>
                            </ul>
                        </div>

                    </div>
                </div>

            <?php else: ?>
                <!-- ERROR: Token inválido o expirado -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5 text-center">

                        <div class="mb-4">
                            <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 4rem;"></i>
                        </div>

                        <h1 class="h3 fw-bold mb-3">Enlace No Válido</h1>

                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-x-circle-fill me-2"></i>
                            <?php echo $mensaje_error; ?>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="recuperar-password.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                Solicitar Nuevo Enlace
                            </a>
                            <a href="<?php echo BASE_PATH; ?>login.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>
                                Volver al Login
                            </a>
                        </div>

                        <!-- Razones comunes -->
                        <div class="card bg-light border-0 mt-4">
                            <div class="card-body p-3 text-start">
                                <h6 class="fw-bold mb-2">¿Por qué puede fallar el enlace?</h6>
                                <ul class="small mb-0">
                                    <li>El enlace expiró (válido solo 1 hora)</li>
                                    <li>Ya fue utilizado anteriormente</li>
                                    <li>El enlace está incompleto o mal copiado</li>
                                    <li>Se solicitó un nuevo enlace más reciente</li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// Toggle mostrar/ocultar contraseña
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Validación de fortaleza de contraseña
document.getElementById('nueva_password')?.addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('passwordStrength');
    const strengthText = document.getElementById('passwordStrengthText');

    let strength = 0;
    let text = '';
    let color = '';

    if (password.length >= 6) strength += 25;
    if (password.length >= 10) strength += 25;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
    if (/[0-9]/.test(password)) strength += 12.5;
    if (/[^a-zA-Z0-9]/.test(password)) strength += 12.5;

    if (strength === 0) {
        text = 'Ingresa una contraseña';
        color = 'bg-secondary';
    } else if (strength <= 25) {
        text = 'Muy débil';
        color = 'bg-danger';
    } else if (strength <= 50) {
        text = 'Débil';
        color = 'bg-warning';
    } else if (strength <= 75) {
        text = 'Media';
        color = 'bg-info';
    } else {
        text = 'Fuerte';
        color = 'bg-success';
    }

    strengthBar.style.width = strength + '%';
    strengthBar.className = 'progress-bar ' + color;
    strengthText.textContent = text;
});

// Validación del formulario
document.getElementById('formRestablecer')?.addEventListener('submit', function(e) {
    const nueva = document.getElementById('nueva_password').value;
    const confirmar = document.getElementById('confirmar_password').value;

    if (nueva.length < 6) {
        e.preventDefault();
        MC.alert('La contraseña debe tener al menos 6 caracteres', 'warning');
        return false;
    }

    if (nueva !== confirmar) {
        e.preventDefault();
        MC.alert('Las contraseñas no coinciden', 'warning');
        return false;
    }
});
</script>

<?php require_once('../includes/footer.php'); ?>
