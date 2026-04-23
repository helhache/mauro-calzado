<?php
/**
 * REGISTRO.PHP - REGISTRO DE NUEVOS USUARIOS (CON SISTEMA DE ROLES)
 * 
 * MODIFICACIÓN FASE 2:
 * - Los nuevos usuarios se registran automáticamente como CLIENTES (rol_id = 1)
 * - sucursal_id queda en NULL para clientes
 */

require_once('includes/config.php');

// Si ya está logueado, redirigir
if (estaLogueado()) {
    redirigir('index.php');
}

$titulo_pagina = "Registro";
$errores = [];
$success = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Obtener y limpiar datos
    $nombre = limpiarDato($_POST['nombre'] ?? '');
    $apellido = limpiarDato($_POST['apellido'] ?? '');
    $email = limpiarDato($_POST['email'] ?? '');
    $dni = limpiarDato($_POST['dni'] ?? '');
    $telefono = limpiarDato($_POST['telefono'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $acepta_terminos = isset($_POST['acepta_terminos']);
    
    // VALIDACIONES
    
    // Nombre
    if (empty($nombre)) {
        $errores['nombre'] = 'El nombre es obligatorio';
    } elseif (strlen($nombre) < 2) {
        $errores['nombre'] = 'El nombre debe tener al menos 2 caracteres';
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/", $nombre)) {
        $errores['nombre'] = 'El nombre solo puede contener letras';
    }
    
    // Apellido
    if (empty($apellido)) {
        $errores['apellido'] = 'El apellido es obligatorio';
    } elseif (strlen($apellido) < 2) {
        $errores['apellido'] = 'El apellido debe tener al menos 2 caracteres';
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/", $apellido)) {
        $errores['apellido'] = 'El apellido solo puede contener letras';
    }
    
    // Email
    if (empty($email)) {
        $errores['email'] = 'El email es obligatorio';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'Formato de email inválido';
    } else {
        // Verificar si el email ya existe
        $stmt = mysqli_prepare($conn, "SELECT id FROM usuarios WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errores['email'] = 'Este email ya está registrado. <a href="login.php" class="text-danger fw-bold">Inicia sesión aquí</a>';
        }
        mysqli_stmt_close($stmt);
    }
    
    // DNI
    if (empty($dni)) {
        $errores['dni'] = 'El DNI es obligatorio';
    } elseif (!preg_match('/^[0-9]{7,8}$/', $dni)) {
        $errores['dni'] = 'El DNI debe tener entre 7 y 8 dígitos';
    } else {
        // Verificar si el DNI ya existe
        $stmt = mysqli_prepare($conn, "SELECT id FROM usuarios WHERE dni = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $dni);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errores['dni'] = 'Este DNI ya está registrado';
        }
        mysqli_stmt_close($stmt);
    }
    
    // Teléfono (opcional pero si se ingresa, validar)
    if (!empty($telefono)) {
        $telefono_limpio = preg_replace('/[\s\-\(\)]/', '', $telefono);
        if (!preg_match('/^(\+?54)?[0-9]{10,11}$/', $telefono_limpio)) {
            $errores['telefono'] = 'Formato de teléfono inválido (ej: 3834567890)';
        }
    }
    
    // Contraseña
    if (empty($password)) {
        $errores['password'] = 'La contraseña es obligatoria';
    } elseif (strlen($password) < 6) {
        $errores['password'] = 'La contraseña debe tener al menos 6 caracteres';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password)) {
        $errores['password'] = 'La contraseña debe contener mayúsculas, minúsculas y números';
    }
    
    // Confirmar contraseña
    if (empty($password_confirm)) {
        $errores['password_confirm'] = 'Debes confirmar la contraseña';
    } elseif ($password !== $password_confirm) {
        $errores['password_confirm'] = 'Las contraseñas no coinciden';
    }
    
    // Términos y condiciones
    if (!$acepta_terminos) {
        $errores['terminos'] = 'Debes aceptar los términos y condiciones';
    }
    
    // Si no hay errores, registrar usuario
    if (empty($errores)) {
        
        // Hashear contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // ============================================================================
        // MODIFICACIÓN: Ahora incluimos rol_id (por defecto 1 = Cliente) y sucursal_id (NULL)
        // ============================================================================
        $stmt = mysqli_prepare($conn, 
            "INSERT INTO usuarios (nombre, apellido, email, dni, telefono, password, rol_id, sucursal_id, fecha_registro, activo) 
             VALUES (?, ?, ?, ?, ?, ?, 1, NULL, NOW(), 1)"
        );
        
        mysqli_stmt_bind_param($stmt, "ssssss", 
            $nombre, 
            $apellido, 
            $email,
            $dni,
            $telefono, 
            $password_hash
        );
        
        if (mysqli_stmt_execute($stmt)) {
            // Registro exitoso
            mysqli_stmt_close($stmt);
            
            // Redirigir a login con mensaje de éxito
            redirigir('login.php?registro=exitoso');
            
        } else {
            // Error al insertar
            $errores['general'] = 'Error al registrar usuario. Intenta nuevamente.';
        }
    }
}

require_once('includes/header.php');
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    
                    <!-- Encabezado -->
                    <div class="text-center mb-4">
                        <img src="img/logo.jpg" alt="Mauro Calzado" height="60" class="mb-3">
                        <h2 class="fw-bold">Crear Cuenta</h2>
                        <p class="text-muted">Regístrate para comprar en línea</p>
                    </div>
                    
                    <!-- Error general -->
                    <?php if (isset($errores['general'])): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo $errores['general']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- FORMULARIO -->
                    <form method="POST" action="" id="form-registro" novalidate>
                        
                        <div class="row">
                            <!-- Nombre -->
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label fw-semibold">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control <?php echo isset($errores['nombre']) ? 'is-invalid' : ''; ?>"
                                       id="nombre" 
                                       name="nombre"
                                       value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>"
                                       required>
                                <?php if (isset($errores['nombre'])): ?>
                                    <div class="invalid-feedback d-block text-danger">
                                        <?php echo $errores['nombre']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Apellido -->
                            <div class="col-md-6 mb-3">
                                <label for="apellido" class="form-label fw-semibold">
                                    Apellido <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control <?php echo isset($errores['apellido']) ? 'is-invalid' : ''; ?>"
                                       id="apellido" 
                                       name="apellido"
                                       value="<?php echo isset($_POST['apellido']) ? htmlspecialchars($_POST['apellido']) : ''; ?>"
                                       required>
                                <?php if (isset($errores['apellido'])): ?>
                                    <div class="invalid-feedback d-block text-danger">
                                        <?php echo $errores['apellido']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">
                                Email <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" 
                                       class="form-control <?php echo isset($errores['email']) ? 'is-invalid' : ''; ?>"
                                       id="email" 
                                       name="email"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                       required>
                            </div>
                            <?php if (isset($errores['email'])): ?>
                                <div class="invalid-feedback d-block text-danger">
                                    <?php echo $errores['email']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row">
                            <!-- DNI -->
                            <div class="col-md-6 mb-3">
                                <label for="dni" class="form-label fw-semibold">
                                    DNI <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control <?php echo isset($errores['dni']) ? 'is-invalid' : ''; ?>"
                                       id="dni" 
                                       name="dni"
                                       value="<?php echo isset($_POST['dni']) ? htmlspecialchars($_POST['dni']) : ''; ?>"
                                       maxlength="8"
                                       placeholder="12345678"
                                       required>
                                <?php if (isset($errores['dni'])): ?>
                                    <div class="invalid-feedback d-block text-danger">
                                        <?php echo $errores['dni']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Teléfono -->
                            <div class="col-md-6 mb-3">
                                <label for="telefono" class="form-label fw-semibold">
                                    Teléfono
                                </label>
                                <input type="tel" 
                                       class="form-control <?php echo isset($errores['telefono']) ? 'is-invalid' : ''; ?>"
                                       id="telefono" 
                                       name="telefono"
                                       value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>"
                                       placeholder="3834567890">
                                <?php if (isset($errores['telefono'])): ?>
                                    <div class="invalid-feedback d-block text-danger">
                                        <?php echo $errores['telefono']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row">
                            <!-- Contraseña -->
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label fw-semibold">
                                    Contraseña <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control <?php echo isset($errores['password']) ? 'is-invalid' : ''; ?>"
                                           id="password" 
                                           name="password"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggle-password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <?php if (isset($errores['password'])): ?>
                                    <div class="invalid-feedback d-block text-danger">
                                        <?php echo $errores['password']; ?>
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted">Mínimo 6 caracteres, debe incluir mayúsculas, minúsculas y números</small>
                            </div>
                            
                            <!-- Confirmar Contraseña -->
                            <div class="col-md-6 mb-3">
                                <label for="password_confirm" class="form-label fw-semibold">
                                    Confirmar Contraseña <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock-fill"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control <?php echo isset($errores['password_confirm']) ? 'is-invalid' : ''; ?>"
                                           id="password_confirm" 
                                           name="password_confirm"
                                           required>
                                </div>
                                <?php if (isset($errores['password_confirm'])): ?>
                                    <div class="invalid-feedback d-block text-danger">
                                        <?php echo $errores['password_confirm']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Términos y Condiciones -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input <?php echo isset($errores['terminos']) ? 'is-invalid' : ''; ?>" 
                                       type="checkbox" 
                                       id="acepta_terminos" 
                                       name="acepta_terminos"
                                       required>
                                <label class="form-check-label" for="acepta_terminos">
                                    Acepto los 
                                    <a href="terminos.php" target="_blank">Términos y Condiciones</a> y la
                                    <a href="privacidad.php" target="_blank">Política de Privacidad</a>
                                </label>
                                <?php if (isset($errores['terminos'])): ?>
                                    <div class="invalid-feedback d-block text-danger">
                                        <?php echo $errores['terminos']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Botón Submit -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg" id="btn-submit">
                                <i class="bi bi-person-plus me-2"></i>
                                Crear Cuenta
                            </button>
                        </div>
                        
                        <!-- Link a Login -->
                        <div class="text-center">
                            <p class="mb-0">
                                ¿Ya tienes cuenta?
                                <a href="login.php" class="fw-bold text-decoration-none">
                                    Inicia sesión aquí
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
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
document.getElementById('form-registro')?.addEventListener('submit', function (e) {
    const resultado = FormularioValidator.registro(new FormData(this));
    if (!resultado.valido) {
        e.preventDefault();
        resultado.errores.forEach(error => MauroCalzado.mostrarAlerta(error, 'warning'));
    }
});
</script>
