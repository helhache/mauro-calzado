<?php
/**
 * LOGIN.PHP - INICIO DE SESIÓN (CON SISTEMA DE ROLES)
 * 
 * MODIFICACIONES FASE 2 y 3:
 * - Carga rol_id y sucursal_id en la sesión
 * - Redirección automática según rol después del login
 * - Admin → admin/dashboard.php
 * - Gerente → gerente/dashboard.php  
 * - Cliente → index.php
 */

require_once('includes/config.php');

// Si ya está logueado, redirigir según su rol
if (estaLogueado()) {
    redirigirSegunRol();
}

$titulo_pagina = "Iniciar Sesión";
$error = '';
$success = '';

// Procesar formulario al enviar
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Obtener y limpiar datos del formulario
    $email = limpiarDato($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $recordar = isset($_POST['recordar']);
    
    // Validaciones básicas
    if (empty($email) || empty($password)) {
        $error = 'Por favor completa todos los campos';
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido';
    }
    else {
        // ============================================================================
        // MODIFICACIÓN: Ahora también obtenemos rol_id y sucursal_id
        // ============================================================================
        $stmt = mysqli_prepare($conn, 
            "SELECT id, nombre, apellido, email, password, activo, rol_id, sucursal_id 
             FROM usuarios 
             WHERE email = ? 
             LIMIT 1"
        );
        
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        
        if ($usuario = mysqli_fetch_assoc($resultado)) {
            // Usuario encontrado
            
            // Verificar si está activo
            if ($usuario['activo'] != 1) {
                $error = 'Tu cuenta está desactivada. Contacta a soporte.';
            }
            // Verificar contraseña
            elseif (password_verify($password, $usuario['password'])) {
                
                // Contraseña correcta - Iniciar sesión
                
                // Regenerar ID de sesión (previene session fixation)
                session_regenerate_id(true);
                
                // ============================================================================
                // MODIFICACIÓN: Guardar datos en sesión INCLUYENDO rol_id y sucursal_id
                // ============================================================================
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['apellido'] = $usuario['apellido'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['rol_id'] = $usuario['rol_id'];           // NUEVO
                $_SESSION['sucursal_id'] = $usuario['sucursal_id']; // NUEVO
                $_SESSION['ultimo_acceso'] = time();
                
                // Actualizar último acceso en BD
                $update_stmt = mysqli_prepare($conn, "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
                mysqli_stmt_bind_param($update_stmt, "i", $usuario['id']);
                mysqli_stmt_execute($update_stmt);
                
                // Si marcó "recordar", extender tiempo de sesión
                if ($recordar) {
                    // Cookie de sesión por 30 días
                    setcookie(session_name(), session_id(), time() + (30 * 24 * 60 * 60), '/');
                }
                
                // ============================================================================
                // MODIFICACIÓN: Redirigir según el ROL del usuario
                // ============================================================================
                redirigirSegunRol();
                
            } else {
                // Contraseña incorrecta
                $error = 'Email o contraseña incorrectos';
            }
            
        } else {
            // Usuario no encontrado
            $error = 'Email o contraseña incorrectos';
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Incluir header
require_once('includes/header.php');
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            
            <!-- Card del formulario -->
            <div class="card shadow-lg border-0">
                
                <div class="card-body p-5">
                    
                    <!-- Logo y título -->
                    <div class="text-center mb-4">
                        <img src="img/logo.jpg" alt="Mauro Calzado" height="60" class="mb-3">
                        <h2 class="fw-bold">Iniciar Sesión</h2>
                        <p class="text-muted">Ingresa a tu cuenta</p>
                    </div>
                    
                    <!-- Mensaje de error -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Mensaje de registro exitoso -->
                    <?php if (isset($_GET['registro']) && $_GET['registro'] == 'exitoso'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            ¡Registro exitoso! Ahora puedes iniciar sesión.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- FORMULARIO -->
                    <form method="POST" action="" id="form-login" novalidate>
                        
                        <!-- Campo Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">
                                Email
                                <span class="text-danger">*</span>
                            </label>
                            
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" 
                                       class="form-control form-control-lg" 
                                       id="email" 
                                       name="email"
                                       placeholder="tu@email.com"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                       required
                                       autofocus>
                            </div>
                            <div class="invalid-feedback">
                                Por favor ingresa un email válido
                            </div>
                        </div>
                        
                        <!-- Campo Contraseña -->
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">
                                Contraseña
                                <span class="text-danger">*</span>
                            </label>
                            
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control form-control-lg" 
                                       id="password" 
                                       name="password"
                                       placeholder="Tu contraseña"
                                       required>
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        id="toggle-password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">
                                Por favor ingresa tu contraseña
                            </div>
                        </div>
                        
                        <!-- Recordar sesión -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="recordar" 
                                   name="recordar">
                            <label class="form-check-label" for="recordar">
                                Recordar mi sesión
                            </label>
                        </div>
                        
                        <!-- Botón de envío -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                Iniciar Sesión
                            </button>
                        </div>
                        
                        <!-- Link de contraseña olvidada -->
                        <div class="text-center mb-3">
                            <a href="recuperar-password.php" class="text-decoration-none">
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>
                        
                        <!-- Separador -->
                        <div class="text-center my-4">
                            <span class="text-muted">o</span>
                        </div>
                        
                        <!-- Link a registro -->
                        <div class="text-center">
                            <p class="mb-0">
                                ¿No tienes cuenta?
                                <a href="registro.php" class="fw-bold text-decoration-none">
                                    Regístrate aquí
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Información adicional -->
            <div class="text-center mt-4 text-muted small">
                <p>Al iniciar sesión aceptas nuestros 
                    <a href="terminos.php">Términos y Condiciones</a> y 
                    <a href="privacidad.php">Política de Privacidad</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once('includes/footer.php'); ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/main.js"></script>
<script src="js/validaciones.js"></script>
<script>
// Toggle password
document.getElementById('toggle-password')?.addEventListener('click', function () {
    const input = document.getElementById('password');
    const icon = this.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
});

// Validación submit
document.getElementById('form-login')?.addEventListener('submit', function (e) {
    const resultado = FormularioValidator.login(new FormData(this));
    if (!resultado.valido) {
        e.preventDefault();
        resultado.errores.forEach(error => MauroCalzado.mostrarAlerta(error, 'warning'));
    }
});
</script>
