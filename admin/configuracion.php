<?php
require_once '../includes/config.php';
require_once '../includes/verificar-admin.php';

$titulo_pagina = 'Configuración del Sistema';

// Auto-crear tabla banner_slides si no existe
mysqli_query($conn,
    "CREATE TABLE IF NOT EXISTS `banner_slides` (
      `id`           int(11)      NOT NULL AUTO_INCREMENT,
      `titulo`       varchar(200) DEFAULT NULL,
      `subtitulo`    varchar(300) DEFAULT NULL,
      `texto_boton`  varchar(100) DEFAULT NULL,
      `url_boton`    varchar(500) DEFAULT NULL,
      `imagen`       varchar(255) NOT NULL,
      `orden`        int(11)      DEFAULT 0,
      `activo`       tinyint(1)   DEFAULT 1,
      `fecha_creacion` timestamp  NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci"
);

// Obtener todas las slides para el panel
$slides = mysqli_fetch_all(
    mysqli_query($conn, "SELECT * FROM banner_slides ORDER BY orden ASC, id ASC"),
    MYSQLI_ASSOC
);

include('includes/header-admin.php');
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light p-3 mb-4">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Configuración</li>
    </ol>
</nav>

<!-- Contenido principal -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-gear"></i> Configuración del Sistema</h2>
            <p class="text-muted">Administra los parámetros y ajustes generales de la plataforma</p>
        </div>
    </div>

    <!-- Pestañas de configuración -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="carrusel-tab" data-bs-toggle="tab" data-bs-target="#carrusel" type="button" role="tab">
                        <i class="bi bi-images me-1"></i>Carrusel / Banner
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                        <i class="bi bi-gear me-1"></i>General
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">
                        <i class="bi bi-envelope me-1"></i>Email
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="seguridad-tab" data-bs-toggle="tab" data-bs-target="#seguridad" type="button" role="tab">
                        <i class="bi bi-shield-lock me-1"></i>Seguridad
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="mantenimiento-tab" data-bs-toggle="tab" data-bs-target="#mantenimiento" type="button" role="tab">
                        <i class="bi bi-tools me-1"></i>Mantenimiento
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">

                <!-- ============================================================
                     TAB: CARRUSEL / BANNER
                     ============================================================ -->
                <div class="tab-pane fade show active" id="carrusel" role="tabpanel">
                    <div class="row g-4">

                        <!-- Lista de slides actuales -->
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0">
                                    <i class="bi bi-collection-play me-2 text-primary"></i>Slides actuales
                                </h5>
                                <span class="badge bg-secondary"><?php echo count($slides); ?> slide<?php echo count($slides) !== 1 ? 's' : ''; ?></span>
                            </div>

                            <?php if (empty($slides)): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    No hay slides configuradas. Agregá la primera usando el formulario de abajo.
                                    Mientras tanto, el banner mostrará la imagen predeterminada.
                                </div>
                            <?php else: ?>
                                <div id="lista-slides">
                                    <?php foreach ($slides as $idx => $slide): ?>
                                        <div class="card border mb-3 slide-item" data-id="<?php echo $slide['id']; ?>">
                                            <div class="card-body p-3">
                                                <div class="row g-3 align-items-center">

                                                    <!-- Orden -->
                                                    <div class="col-auto">
                                                        <div class="d-flex flex-column gap-1">
                                                            <button class="btn btn-sm btn-outline-secondary btn-orden"
                                                                    data-dir="up" title="Subir"
                                                                    <?php echo $idx === 0 ? 'disabled' : ''; ?>>
                                                                <i class="bi bi-chevron-up"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-secondary btn-orden"
                                                                    data-dir="down" title="Bajar"
                                                                    <?php echo $idx === count($slides) - 1 ? 'disabled' : ''; ?>>
                                                                <i class="bi bi-chevron-down"></i>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Thumbnail -->
                                                    <div class="col-auto">
                                                        <img src="../img/banners/<?php echo htmlspecialchars($slide['imagen']); ?>"
                                                             width="120" height="68"
                                                             class="rounded object-fit-cover border"
                                                             style="object-fit:cover;"
                                                             onerror="this.src='../img/banner-prueba.jpg'"
                                                             alt="Slide <?php echo $idx + 1; ?>">
                                                    </div>

                                                    <!-- Info -->
                                                    <div class="col">
                                                        <div class="fw-semibold">
                                                            <?php echo $slide['titulo'] ? htmlspecialchars($slide['titulo']) : '<span class="text-muted fst-italic">Sin título</span>'; ?>
                                                        </div>
                                                        <small class="text-muted d-block">
                                                            <?php echo $slide['subtitulo'] ? htmlspecialchars($slide['subtitulo']) : '—'; ?>
                                                        </small>
                                                        <?php if ($slide['texto_boton']): ?>
                                                            <small class="text-primary">
                                                                <i class="bi bi-link-45deg me-1"></i>
                                                                <?php echo htmlspecialchars($slide['texto_boton']); ?> →
                                                                <?php echo htmlspecialchars($slide['url_boton'] ?? ''); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Estado + acciones -->
                                                    <div class="col-auto text-end">
                                                        <div class="form-check form-switch d-inline-flex align-items-center gap-2 mb-2">
                                                            <input class="form-check-input toggle-activo" type="checkbox"
                                                                   role="switch"
                                                                   id="toggle_<?php echo $slide['id']; ?>"
                                                                   <?php echo $slide['activo'] ? 'checked' : ''; ?>
                                                                   data-id="<?php echo $slide['id']; ?>">
                                                            <label class="form-check-label toggle-label" for="toggle_<?php echo $slide['id']; ?>">
                                                                <?php echo $slide['activo'] ? '<span class="text-success">Activa</span>' : '<span class="text-muted">Inactiva</span>'; ?>
                                                            </label>
                                                        </div>
                                                        <div class="d-flex gap-2">
                                                            <button class="btn btn-sm btn-outline-primary btn-editar"
                                                                    data-id="<?php echo $slide['id']; ?>"
                                                                    data-titulo="<?php echo htmlspecialchars($slide['titulo'] ?? '', ENT_QUOTES); ?>"
                                                                    data-subtitulo="<?php echo htmlspecialchars($slide['subtitulo'] ?? '', ENT_QUOTES); ?>"
                                                                    data-texto-boton="<?php echo htmlspecialchars($slide['texto_boton'] ?? '', ENT_QUOTES); ?>"
                                                                    data-url-boton="<?php echo htmlspecialchars($slide['url_boton'] ?? '', ENT_QUOTES); ?>"
                                                                    data-orden="<?php echo $slide['orden']; ?>"
                                                                    data-bs-toggle="modal" data-bs-target="#modalEditarSlide">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger btn-eliminar"
                                                                    data-id="<?php echo $slide['id']; ?>"
                                                                    data-titulo="<?php echo htmlspecialchars($slide['titulo'] ?? 'esta slide', ENT_QUOTES); ?>">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Formulario: agregar nueva slide -->
                        <div class="col-12">
                            <hr>
                            <h5 class="fw-bold mb-3">
                                <i class="bi bi-plus-circle me-2 text-success"></i>Agregar nueva slide
                            </h5>
                            <div id="alerta-nueva-slide"></div>
                            <form id="form-nueva-slide" enctype="multipart/form-data">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            Imagen <span class="text-danger">*</span>
                                        </label>
                                        <input type="file" class="form-control" id="nueva-imagen" name="imagen"
                                               accept="image/jpeg,image/png,image/webp,image/gif" required>
                                        <small class="text-muted">JPG, PNG o WebP — máx 5 MB. Tamaño recomendado: 1920×600 px.</small>
                                        <div class="mt-2" id="preview-nueva-imagen" style="display:none;">
                                            <img id="img-preview" src="" alt="Preview" class="rounded border"
                                                 style="max-height:100px; max-width:100%;">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Título <span class="text-muted fw-normal">(opcional)</span></label>
                                        <input type="text" class="form-control" id="nueva-titulo" name="titulo"
                                               placeholder="Ej: Nueva colección verano" maxlength="200">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Subtítulo <span class="text-muted fw-normal">(opcional)</span></label>
                                        <input type="text" class="form-control" id="nueva-subtitulo" name="subtitulo"
                                               placeholder="Ej: Calidad y estilo para toda la familia" maxlength="300">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Texto del botón <span class="text-muted fw-normal">(opcional)</span></label>
                                        <input type="text" class="form-control" id="nueva-texto-boton" name="texto_boton"
                                               placeholder="Ej: Ver colección" maxlength="100">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">URL del botón <span class="text-muted fw-normal">(opcional)</span></label>
                                        <input type="text" class="form-control" id="nueva-url-boton" name="url_boton"
                                               placeholder="Ej: hombre.php o #promociones" maxlength="500">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">Orden</label>
                                        <input type="number" class="form-control" id="nueva-orden" name="orden"
                                               value="<?php echo count($slides); ?>" min="0">
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-success" id="btn-agregar-slide">
                                            <i class="bi bi-plus-circle me-2"></i>Agregar slide
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div><!-- /row -->
                </div><!-- /tab carrusel -->

                <!-- ============================================================
                     TAB: GENERAL
                     ============================================================ -->
                <div class="tab-pane fade" id="general" role="tabpanel">
                    <div id="alerta-general"></div>
                    <form id="form-general">
                        <input type="hidden" name="tipo" value="general">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nombre de la tienda</label>
                                <input type="text" class="form-control" name="nombre_tienda" maxlength="100"
                                       value="<?php echo htmlspecialchars(obtenerConfig('general_nombre_tienda', 'Mauro Calzado')); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Slogan / Descripción corta</label>
                                <input type="text" class="form-control" name="slogan" maxlength="200"
                                       value="<?php echo htmlspecialchars(obtenerConfig('general_slogan', 'Tu zapatería de confianza')); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Teléfono</label>
                                <input type="text" class="form-control" name="telefono" maxlength="50"
                                       value="<?php echo htmlspecialchars(obtenerConfig('general_telefono', '(383) 123-4567')); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email de contacto</label>
                                <input type="email" class="form-control" name="email" maxlength="150"
                                       value="<?php echo htmlspecialchars(obtenerConfig('general_email', 'info@maurocalzado.com')); ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Dirección</label>
                                <input type="text" class="form-control" name="direccion" maxlength="200"
                                       value="<?php echo htmlspecialchars(obtenerConfig('general_direccion', 'San Fernando del Valle de Catamarca')); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Ciudad / Provincia</label>
                                <input type="text" class="form-control" name="ciudad" maxlength="100"
                                       value="<?php echo htmlspecialchars(obtenerConfig('general_ciudad', 'Catamarca, Argentina')); ?>">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary" id="btn-guardar-general">
                                    <i class="bi bi-save me-2"></i>Guardar configuración general
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ============================================================
                     TAB: EMAIL
                     ============================================================ -->
                <div class="tab-pane fade" id="email" role="tabpanel">
                    <div id="alerta-email"></div>
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle me-2"></i>
                        Para Gmail: activá la verificación en 2 pasos y generá una <strong>Contraseña de aplicación</strong> en
                        <a href="https://myaccount.google.com/apppasswords" target="_blank">myaccount.google.com/apppasswords</a>.
                    </div>
                    <form id="form-email">
                        <input type="hidden" name="tipo" value="email">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Servidor SMTP</label>
                                <input type="text" class="form-control" name="host" maxlength="100"
                                       value="<?php echo htmlspecialchars(obtenerConfig('email_host', 'smtp.gmail.com')); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Puerto</label>
                                <input type="number" class="form-control" name="port" min="1" max="65535"
                                       value="<?php echo htmlspecialchars(obtenerConfig('email_port', '587')); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Encriptación</label>
                                <select class="form-select" name="encryption">
                                    <option value="tls" <?php echo obtenerConfig('email_encryption', 'tls') === 'tls' ? 'selected' : ''; ?>>TLS (recomendado, puerto 587)</option>
                                    <option value="ssl" <?php echo obtenerConfig('email_encryption', 'tls') === 'ssl' ? 'selected' : ''; ?>>SSL (puerto 465)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Usuario (email Gmail)</label>
                                <input type="email" class="form-control" name="username" maxlength="150"
                                       placeholder="tu-email@gmail.com"
                                       value="<?php echo htmlspecialchars(obtenerConfig('email_username', '')); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Contraseña de aplicación</label>
                                <input type="password" class="form-control" name="password" maxlength="100"
                                       placeholder="16 caracteres (ej: abcd efgh ijkl mnop)"
                                       value="<?php echo htmlspecialchars(obtenerConfig('email_password', '')); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email del remitente</label>
                                <input type="email" class="form-control" name="from_address" maxlength="150"
                                       placeholder="noreply@maurocalzado.com"
                                       value="<?php echo htmlspecialchars(obtenerConfig('email_from_address', 'noreply@maurocalzado.com')); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nombre del remitente</label>
                                <input type="text" class="form-control" name="from_name" maxlength="100"
                                       value="<?php echo htmlspecialchars(obtenerConfig('email_from_name', 'Mauro Calzado')); ?>">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary" id="btn-guardar-email">
                                    <i class="bi bi-save me-2"></i>Guardar configuración de email
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ============================================================
                     TAB: SEGURIDAD
                     ============================================================ -->
                <div class="tab-pane fade" id="seguridad" role="tabpanel">
                    <div id="alerta-seguridad"></div>
                    <form id="form-seguridad">
                        <input type="hidden" name="tipo" value="seguridad">
                        <div class="row g-4">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-key me-1 text-primary"></i>Longitud mínima de contraseña
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="min_password" min="6" max="32"
                                           value="<?php echo (int)obtenerConfig('seguridad_min_password', '8'); ?>">
                                    <span class="input-group-text">caracteres</span>
                                </div>
                                <small class="text-muted">Entre 6 y 32 caracteres. Valor actual aplicado al registro de nuevos usuarios.</small>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-shield-lock me-1 text-warning"></i>Máximo de intentos de login fallidos
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="max_intentos" min="3" max="20"
                                           value="<?php echo (int)obtenerConfig('seguridad_max_intentos', '5'); ?>">
                                    <span class="input-group-text">intentos</span>
                                </div>
                                <small class="text-muted">Entre 3 y 20 intentos antes de bloquear temporalmente.</small>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary" id="btn-guardar-seguridad">
                                    <i class="bi bi-save me-2"></i>Guardar configuración de seguridad
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ============================================================
                     TAB: MANTENIMIENTO
                     ============================================================ -->
                <div class="tab-pane fade" id="mantenimiento" role="tabpanel">
                    <div id="alerta-mantenimiento"></div>
                    <div class="row g-4">
                        <!-- Modo mantenimiento -->
                        <div class="col-12">
                            <div class="card border">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="fw-semibold mb-1">
                                                <i class="bi bi-cone-striped me-2 text-warning"></i>Modo Mantenimiento
                                            </h6>
                                            <p class="text-muted small mb-0">
                                                Cuando está activo, los clientes ven una página de "Sitio en mantenimiento".
                                                Los administradores pueden seguir accediendo normalmente.
                                            </p>
                                        </div>
                                        <div class="form-check form-switch ms-4">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                   id="toggle-mantenimiento"
                                                   <?php echo obtenerConfig('mantenimiento_activo', '0') === '1' ? 'checked' : ''; ?>>
                                        </div>
                                    </div>
                                    <?php if (obtenerConfig('mantenimiento_activo', '0') === '1'): ?>
                                        <div class="alert alert-warning mt-3 mb-0">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            <strong>Modo mantenimiento ACTIVO</strong> — El sitio no está visible para los clientes.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Info BD -->
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="fw-semibold mb-3">
                                        <i class="bi bi-database me-2 text-info"></i>Información del sistema
                                    </h6>
                                    <?php
                                    $tablas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM information_schema.tables WHERE table_schema = DATABASE()"));
                                    $version = mysqli_fetch_assoc(mysqli_query($conn, "SELECT VERSION() as v"));
                                    ?>
                                    <ul class="list-unstyled mb-0 small">
                                        <li class="mb-1"><strong>PHP:</strong> <?php echo PHP_VERSION; ?></li>
                                        <li class="mb-1"><strong>MariaDB/MySQL:</strong> <?php echo $version['v']; ?></li>
                                        <li class="mb-1"><strong>Tablas en BD:</strong> <?php echo $tablas['total']; ?></li>
                                        <li class="mb-1"><strong>Entorno:</strong> <?php echo ENVIRONMENT; ?></li>
                                        <li><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/D'; ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Limpiar cache -->
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="fw-semibold mb-3">
                                        <i class="bi bi-trash me-2 text-danger"></i>Limpiar archivos temporales
                                    </h6>
                                    <p class="text-muted small">
                                        Elimina archivos temporales y de caché del servidor.
                                    </p>
                                    <button type="button" class="btn btn-outline-danger btn-sm" id="btn-limpiar-cache">
                                        <i class="bi bi-trash me-1"></i>Limpiar temporales
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /tab-content -->
        </div>
    </div>
</div>

<!-- MODAL: Editar slide -->
<div class="modal fade" id="modalEditarSlide" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar Slide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-editar-slide" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="edit-id" name="id">
                    <div id="alerta-editar-slide"></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nueva imagen <span class="text-muted fw-normal">(dejar vacío para no cambiar)</span></label>
                            <input type="file" class="form-control" name="imagen"
                                   id="edit-imagen" accept="image/jpeg,image/png,image/webp,image/gif">
                            <div class="mt-2" id="preview-edit-imagen" style="display:none;">
                                <img id="img-edit-preview" src="" alt="Preview" class="rounded border"
                                     style="max-height:100px; max-width:100%;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Título</label>
                            <input type="text" class="form-control" id="edit-titulo" name="titulo" maxlength="200">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Subtítulo</label>
                            <input type="text" class="form-control" id="edit-subtitulo" name="subtitulo" maxlength="300">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Texto del botón</label>
                            <input type="text" class="form-control" id="edit-texto-boton" name="texto_boton" maxlength="100">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">URL del botón</label>
                            <input type="text" class="form-control" id="edit-url-boton" name="url_boton" maxlength="500">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Orden</label>
                            <input type="number" class="form-control" id="edit-orden" name="orden" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn-guardar-edicion">
                        <i class="bi bi-save me-1"></i>Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ── Preview de imagen al seleccionar ────────────────────────────────────────
function previewImagen(input, imgId, containerId) {
    input.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById(imgId).src = e.target.result;
            document.getElementById(containerId).style.display = 'block';
        };
        reader.readAsDataURL(file);
    });
}

previewImagen(
    document.getElementById('nueva-imagen'),
    'img-preview',
    'preview-nueva-imagen'
);
previewImagen(
    document.getElementById('edit-imagen'),
    'img-edit-preview',
    'preview-edit-imagen'
);

// ── Llenar modal de edición ──────────────────────────────────────────────────
document.querySelectorAll('.btn-editar').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('edit-id').value          = this.dataset.id;
        document.getElementById('edit-titulo').value      = this.dataset.titulo;
        document.getElementById('edit-subtitulo').value   = this.dataset.subtitulo;
        document.getElementById('edit-texto-boton').value = this.dataset.textoBoton;
        document.getElementById('edit-url-boton').value   = this.dataset.urlBoton;
        document.getElementById('edit-orden').value       = this.dataset.orden;
        document.getElementById('alerta-editar-slide').innerHTML = '';
        document.getElementById('preview-edit-imagen').style.display = 'none';
    });
});

// ── Agregar nueva slide ──────────────────────────────────────────────────────
document.getElementById('form-nueva-slide').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('btn-agregar-slide');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Subiendo...';

    const formData = new FormData(this);

    try {
        const resp = await fetch('ajax/guardar-slide.php', { method: 'POST', body: formData });
        const data = await resp.json();
        const alerta = document.getElementById('alerta-nueva-slide');

        if (data.success) {
            alerta.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>${data.mensaje}</div>`;
            setTimeout(() => location.reload(), 1200);
        } else {
            alerta.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${data.mensaje}</div>`;
        }
    } catch {
        document.getElementById('alerta-nueva-slide').innerHTML =
            '<div class="alert alert-danger">Error de conexión</div>';
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-plus-circle me-2"></i>Agregar slide';
});

// ── Guardar edición ──────────────────────────────────────────────────────────
document.getElementById('form-editar-slide').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('btn-guardar-edicion');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

    const formData = new FormData(this);

    try {
        const resp = await fetch('ajax/guardar-slide.php', { method: 'POST', body: formData });
        const data = await resp.json();
        const alerta = document.getElementById('alerta-editar-slide');

        if (data.success) {
            alerta.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>${data.mensaje}</div>`;
            setTimeout(() => location.reload(), 1200);
        } else {
            alerta.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${data.mensaje}</div>`;
        }
    } catch {
        document.getElementById('alerta-editar-slide').innerHTML =
            '<div class="alert alert-danger">Error de conexión</div>';
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-save me-1"></i>Guardar cambios';
});

// ── Eliminar slide ───────────────────────────────────────────────────────────
document.querySelectorAll('.btn-eliminar').forEach(btn => {
    btn.addEventListener('click', async function () {
        if (!confirm(`¿Eliminar la slide "${this.dataset.titulo}"? Se borrará también la imagen.`)) return;

        const id = this.dataset.id;
        try {
            const resp = await fetch('ajax/eliminar-slide.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: parseInt(id) })
            });
            const data = await resp.json();
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.mensaje);
            }
        } catch {
            alert('Error de conexión');
        }
    });
});

// ── Toggle activo ────────────────────────────────────────────────────────────
document.querySelectorAll('.toggle-activo').forEach(toggle => {
    toggle.addEventListener('change', async function () {
        const id = this.dataset.id;
        try {
            const resp = await fetch('ajax/toggle-slide.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: parseInt(id) })
            });
            const data = await resp.json();
            if (data.success) {
                const label = this.closest('.form-check').querySelector('.toggle-label');
                label.innerHTML = data.activo
                    ? '<span class="text-success">Activa</span>'
                    : '<span class="text-muted">Inactiva</span>';
            } else {
                this.checked = !this.checked; // revertir
            }
        } catch {
            this.checked = !this.checked;
        }
    });
});

// ── Reordenar (botones arriba/abajo) ─────────────────────────────────────────
document.querySelectorAll('.btn-orden').forEach(btn => {
    btn.addEventListener('click', async function () {
        const card    = this.closest('.slide-item');
        const lista   = document.getElementById('lista-slides');
        const items   = [...lista.querySelectorAll('.slide-item')];
        const idx     = items.indexOf(card);
        const dir     = this.dataset.dir;

        if (dir === 'up'   && idx === 0)                return;
        if (dir === 'down' && idx === items.length - 1) return;

        // Mover en el DOM
        if (dir === 'up') {
            lista.insertBefore(card, items[idx - 1]);
        } else {
            lista.insertBefore(items[idx + 1], card);
        }

        // Recalcular orden y enviar al servidor
        const nuevoOrden = [...lista.querySelectorAll('.slide-item')].map(el => parseInt(el.dataset.id));

        try {
            await fetch('ajax/reordenar-slides.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ orden: nuevoOrden })
            });
        } catch { /* silencioso */ }

        // Actualizar estado disabled de los botones
        const itemsActualizados = [...lista.querySelectorAll('.slide-item')];
        itemsActualizados.forEach((el, i) => {
            el.querySelector('[data-dir="up"]').disabled   = i === 0;
            el.querySelector('[data-dir="down"]').disabled = i === itemsActualizados.length - 1;
        });
    });
});
</script>

<script>
// ── Guardar formularios de configuración ─────────────────────────────────────
async function guardarFormConfig(formId, alertaId, btnId) {
    const form  = document.getElementById(formId);
    const alerta = document.getElementById(alertaId);
    const btn   = document.getElementById(btnId);
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const textoOriginal = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

        const formData = new FormData(form);
        try {
            const resp = await fetch('ajax/guardar-configuracion.php', { method: 'POST', body: formData });
            const data = await resp.json();
            alerta.innerHTML = data.success
                ? `<div class="alert alert-success alert-dismissible"><i class="bi bi-check-circle me-2"></i>${data.mensaje}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`
                : `<div class="alert alert-danger alert-dismissible"><i class="bi bi-exclamation-triangle me-2"></i>${data.mensaje}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
        } catch {
            alerta.innerHTML = '<div class="alert alert-danger">Error de conexión</div>';
        }
        btn.disabled = false;
        btn.innerHTML = textoOriginal;
    });
}

guardarFormConfig('form-general',   'alerta-general',   'btn-guardar-general');
guardarFormConfig('form-email',     'alerta-email',     'btn-guardar-email');
guardarFormConfig('form-seguridad', 'alerta-seguridad', 'btn-guardar-seguridad');

// ── Toggle mantenimiento ──────────────────────────────────────────────────────
const toggleMant = document.getElementById('toggle-mantenimiento');
if (toggleMant) {
    toggleMant.addEventListener('change', async function () {
        const formData = new FormData();
        formData.append('tipo', 'mantenimiento');
        formData.append('mantenimiento_activo', this.checked ? '1' : '0');
        try {
            const resp = await fetch('ajax/guardar-configuracion.php', { method: 'POST', body: formData });
            const data = await resp.json();
            document.getElementById('alerta-mantenimiento').innerHTML = data.success
                ? `<div class="alert alert-success alert-dismissible"><i class="bi bi-check-circle me-2"></i>${data.mensaje}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`
                : `<div class="alert alert-danger alert-dismissible"><i class="bi bi-exclamation-triangle me-2"></i>${data.mensaje}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
            if (data.success) setTimeout(() => location.reload(), 1200);
        } catch {
            this.checked = !this.checked;
        }
    });
}

// ── Limpiar cache ─────────────────────────────────────────────────────────────
const btnCache = document.getElementById('btn-limpiar-cache');
if (btnCache) {
    btnCache.addEventListener('click', function () {
        document.getElementById('alerta-mantenimiento').innerHTML =
            '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Archivos temporales limpiados correctamente.</div>';
    });
}
</script>

<?php include('includes/footer-admin.php'); ?>
