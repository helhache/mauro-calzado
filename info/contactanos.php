<?php
/**
 * CONTACTANOS.PHP - FORMULARIO DE CONTACTO
 * 
 * Funcionalidades:
 * - Formulario de contacto completo
 * - Validaciones del lado del servidor
 * - Guardado en base de datos
 * - Envío de email (opcional)
 * - Opciones para consultas, reservas y comentarios
 */

require_once('../includes/config.php');
$titulo_pagina = "Contáctanos";

$errores = [];
$success = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Obtener y limpiar datos
    $nombre = limpiarDato($_POST['nombre'] ?? '');
    $email = limpiarDato($_POST['email'] ?? '');
    $telefono = limpiarDato($_POST['telefono'] ?? '');
    $asunto = limpiarDato($_POST['asunto'] ?? '');
    $mensaje = limpiarDato($_POST['mensaje'] ?? '');
    
    // Validaciones
    if (empty($nombre)) {
        $errores['nombre'] = 'El nombre es obligatorio';
    }
    
    if (empty($email)) {
        $errores['email'] = 'El email es obligatorio';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'Email inválido';
    }
    
    if (empty($asunto)) {
        $errores['asunto'] = 'El asunto es obligatorio';
    }
    
    if (empty($mensaje)) {
        $errores['mensaje'] = 'El mensaje es obligatorio';
    } elseif (strlen($mensaje) < 10) {
        $errores['mensaje'] = 'El mensaje debe tener al menos 10 caracteres';
    }
    
    // Si no hay errores, guardar en BD
    if (empty($errores)) {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO contacto (nombre, email, telefono, asunto, mensaje, fecha_envio) 
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        
        mysqli_stmt_bind_param($stmt, "sssss", $nombre, $email, $telefono, $asunto, $mensaje);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = '¡Mensaje enviado exitosamente! Te responderemos a la brevedad.';
            
            // Limpiar variables para vaciar el formulario
            $nombre = $email = $telefono = $asunto = $mensaje = '';
            
        } else {
            $errores['general'] = 'Error al enviar el mensaje. Intenta nuevamente.';
        }
        
        mysqli_stmt_close($stmt);
    }
}

require_once('../includes/header.php');
?>

<?php
$banner_modo              = 'fondo';
$banner_altura            = '350px';
$banner_overlay_titulo    = 'CONTÁCTANOS';
$banner_overlay_subtitulo = 'Estamos para ayudarte';
require_once('../includes/banner-carousel.php');
?>

<div class="container py-5">
    <div class="row">
        
        <!-- COLUMNA IZQUIERDA: INFORMACIÓN DE CONTACTO -->
        <div class="col-lg-4 mb-4">
            
            <!-- Información de contacto -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-4">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Información de Contacto
                    </h5>
                    
                    <!-- Dirección -->
                    <div class="mb-4">
                        <h6 class="fw-bold">
                            <i class="bi bi-geo-alt text-danger me-2"></i>
                            Dirección Principal
                        </h6>
                        <p class="text-muted mb-0">
                            En el Apartado Nosotros<br>
                            encontraras La sucursal mas secana para consulta<br>
                            personalizada.
                        </p>
                    </div>
                    
                    <!-- Teléfono -->
                    <div class="mb-4">
                        <h6 class="fw-bold">
                            <i class="bi bi-telephone text-success me-2"></i>
                            Teléfono
                        </h6>
                        <p class="text-muted mb-0">
                            <a href="tel:+543834431234">(0383) 443-1234</a>
                        </p>
                    </div>
                    
                    <!-- WhatsApp -->
                    <div class="mb-4">
                        <h6 class="fw-bold">
                            <i class="bi bi-whatsapp text-success me-2"></i>
                            WhatsApp
                        </h6>
                        <p class="text-muted mb-0">
                            <a href="https://wa.me/543....." target="_blank">
                                +54 3.....
                            </a>
                        </p>
                    </div>
                    
                    <!-- Email -->
                    <div class="mb-4">
                        <h6 class="fw-bold">
                            <i class="bi bi-envelope text-info me-2"></i>
                            Email
                        </h6>
                        <p class="text-muted mb-0">
                            <a href="mailto:info@maurocalzado.com">
                                info@maurocalzado.com
                            </a>
                        </p>
                    </div>
                    
                    <!-- Horarios -->
                    <div>
                        <h6 class="fw-bold">
                            <i class="bi bi-clock text-warning me-2"></i>
                            Horarios de Atención
                        </h6>
                        <p class="text-muted mb-1">
                            <strong>Lun - Vie:</strong> 9:00 - 13:00 / 17:00 - 21:00
                        </p>
                        <p class="text-muted mb-1">
                            <strong>Sábados:</strong> 9:00 - 13:00 / 17:00 - 21:00
                        </p>
                        <p class="text-muted mb-0">
                            <strong>Domingos:</strong> Cerrado
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Redes sociales -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-share text-primary me-2"></i>
                        Síguenos
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="https://www.facebook.com/calzados.mauro/?locale=es_LA" target="_blank" class="btn btn-outline-primary flex-fill">
                            <i class="bi bi-facebook fs-5"></i>
                        </a>
                        <a href="https://www.instagram.com/maurocalzados/?hl=es-la" target="_blank" class="btn btn-outline-danger flex-fill">
                            <i class="bi bi-instagram fs-5"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- COLUMNA DERECHA: FORMULARIO -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="fw-bold mb-4">
                        <i class="bi bi-envelope-paper text-primary me-2"></i>
                        Envíanos un Mensaje
                    </h2>
                    
                    <p class="text-muted mb-4">
                        Completá el formulario y nos pondremos en contacto contigo a la brevedad. 
                        También podés consultarnos por productos específicos, hacer reservas o 
                        dejarnos tus comentarios. Si no tenes cuenta en la paguina dejamos tus datos para contactarnos
                    </p>
                    
                    <!-- Mensaje de éxito -->
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Error general -->
                    <?php if (isset($errores['general'])): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo $errores['general']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- FORMULARIO -->
                    <form method="POST" action="" id="form-contacto">
                        
                        <div class="row">
                            <!-- Nombre -->
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label fw-semibold">
                                    Nombre completo <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control <?php echo isset($errores['nombre']) ? 'is-invalid' : ''; ?>" 
                                       id="nombre" 
                                       name="nombre"
                                       value="<?php echo htmlspecialchars($nombre ?? ''); ?>"
                                       required>
                                <?php if (isset($errores['nombre'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <?php echo $errores['nombre']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Email -->
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label fw-semibold">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" 
                                       class="form-control <?php echo isset($errores['email']) ? 'is-invalid' : ''; ?>" 
                                       id="email" 
                                       name="email"
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                       required>
                                <?php if (isset($errores['email'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <?php echo $errores['email']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row">
                            <!-- Teléfono -->
                            <div class="col-md-6 mb-3">
                                <label for="telefono" class="form-label fw-semibold">
                                    Teléfono <span class="text-muted small">(Opcional)</span>
                                </label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="telefono" 
                                       name="telefono"
                                       placeholder="(0383) 456-7890"
                                       value="<?php echo htmlspecialchars($telefono ?? ''); ?>">
                            </div>
                            
                            <!-- Asunto -->
                            <div class="col-md-6 mb-3">
                                <label for="asunto" class="form-label fw-semibold">
                                    Asunto <span class="text-danger">*</span>
                                </label>
                                <select class="form-select <?php echo isset($errores['asunto']) ? 'is-invalid' : ''; ?>" 
                                        id="asunto" 
                                        name="asunto"
                                        required>
                                    <option value="">Selecciona un asunto</option>
                                    <option value="Consulta general" <?php echo (isset($asunto) && $asunto == 'Consulta general') ? 'selected' : ''; ?>>
                                        Consulta general
                                    </option>
                                    <option value="Consulta sobre producto" <?php echo (isset($asunto) && $asunto == 'Consulta sobre producto') ? 'selected' : ''; ?>>
                                        Consulta sobre producto
                                    </option>
                                    <option value="Reserva de producto" <?php echo (isset($asunto) && $asunto == 'Reserva de producto') ? 'selected' : ''; ?>>
                                        Reserva de producto
                                    </option>
                                    <option value="Cambios y devoluciones" <?php echo (isset($asunto) && $asunto == 'Cambios y devoluciones') ? 'selected' : ''; ?>>
                                        Cambios y devoluciones
                                    </option>
                                    <option value="Estado de pedido" <?php echo (isset($asunto) && $asunto == 'Estado de pedido') ? 'selected' : ''; ?>>
                                        Estado de pedido
                                    </option>
                                    <option value="Sugerencias" <?php echo (isset($asunto) && $asunto == 'Sugerencias') ? 'selected' : ''; ?>>
                                        Sugerencias
                                    </option>
                                    <option value="Reclamo" <?php echo (isset($asunto) && $asunto == 'Reclamo') ? 'selected' : ''; ?>>
                                        Reclamo
                                    </option>
                                    <option value="Otro" <?php echo (isset($asunto) && $asunto == 'Otro') ? 'selected' : ''; ?>>
                                        Otro
                                    </option>
                                </select>
                                <?php if (isset($errores['asunto'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <?php echo $errores['asunto']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Mensaje -->
                        <div class="mb-4">
                            <label for="mensaje" class="form-label fw-semibold">
                                Mensaje <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control <?php echo isset($errores['mensaje']) ? 'is-invalid' : ''; ?>" 
                                      id="mensaje" 
                                      name="mensaje" 
                                      rows="6"
                                      placeholder="Escribe tu mensaje aquí..."
                                      required><?php echo htmlspecialchars($mensaje ?? ''); ?></textarea>
                            <small class="form-text text-muted">
                                Mínimo 10 caracteres
                            </small>
                            <?php if (isset($errores['mensaje'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?php echo $errores['mensaje']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Información adicional -->
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Tiempo de respuesta:</strong> Respondemos todos los mensajes 
                            en un plazo máximo de 24 horas hábiles al correo que proporciones.
                        </div>
                        
                        <!-- Botón enviar -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-send me-2"></i>
                                Enviar Mensaje
                            </button>
                        </div>
                    </form>
                </div>
            </div><!-- Llamadas a la acción -->
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>

<script>
// Validación del formulario
document.getElementById('form-contacto').addEventListener('submit', function(e) {
    let isValid = true;
    
    // Limpiar errores anteriores
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    
    const nombre = document.getElementById('nombre');
    const email = document.getElementById('email');
    const asunto = document.getElementById('asunto');
    const mensaje = document.getElementById('mensaje');
    
    // Validar nombre
    if (!nombre.value.trim() || nombre.value.length < 2) {
        nombre.classList.add('is-invalid');
        isValid = false;
    }
    
    // Validar email
    if (!MauroCalzado.validarEmail(email.value)) {
        email.classList.add('is-invalid');
        isValid = false;
    }
    
    // Validar asunto
    if (!asunto.value) {
        asunto.classList.add('is-invalid');
        isValid = false;
    }
    
    // Validar mensaje
    if (!mensaje.value.trim() || mensaje.value.length < 10) {
        mensaje.classList.add('is-invalid');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
        MauroCalzado.mostrarAlerta('Por favor completa todos los campos correctamente', 'warning');
    }
});

// Contador de caracteres para el mensaje
document.getElementById('mensaje').addEventListener('input', function() {
    const length = this.value.length;
    const minLength = 10;
    
    if (length < minLength) {
        this.classList.add('is-invalid');
    } else {
        this.classList.remove('is-invalid');
    }
});
</script>