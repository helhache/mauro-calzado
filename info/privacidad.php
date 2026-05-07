<?php
/**
 * POLÍTICA DE PRIVACIDAD - MAURO CALZADO
 *
 * Página legal con información sobre protección de datos personales
 * conforme a la Ley 25.326 de Protección de Datos Personales de Argentina
 */

require_once('../includes/config.php');
$titulo_pagina = "Política de Privacidad";
require_once('../includes/header.php');
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <!-- Encabezado -->
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold mb-3">Política de Privacidad</h1>
                <p class="text-muted">Última actualización: <?php echo date('d/m/Y'); ?></p>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">

                    <!-- Introducción -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">1. Introducción</h2>
                        <p>
                            En <strong>Mauro Calzado</strong> nos comprometemos a proteger la privacidad y
                            seguridad de los datos personales de nuestros usuarios. Esta Política de Privacidad
                            describe cómo recopilamos, utilizamos, almacenamos y protegemos su información
                            personal de acuerdo con la <strong>Ley 25.326 de Protección de Datos Personales</strong>
                            de la República Argentina.
                        </p>
                        <div class="alert alert-primary" role="alert">
                            <i class="bi bi-shield-check me-2"></i>
                            Al utilizar nuestro sitio web y servicios, usted acepta las prácticas descritas
                            en esta política.
                        </div>
                    </section>

                    <!-- Responsable del Tratamiento -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">2. Responsable del Tratamiento de Datos</h2>
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <p class="mb-2"><strong>Razón Social:</strong> Mauro Calzado</p>
                                <p class="mb-2"><strong>Domicilio:</strong> San Fernando del Valle de Catamarca, Argentina</p>
                                <p class="mb-2"><strong>Email:</strong> info@maurocalzado.com</p>
                                <p class="mb-0"><strong>Teléfono:</strong> (383) 123-4567</p>
                            </div>
                        </div>
                    </section>

                    <!-- Datos que Recopilamos -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">3. Datos que Recopilamos</h2>

                        <h5 class="fw-semibold mb-3">3.1. Datos Proporcionados por el Usuario</h5>
                        <p>Cuando crea una cuenta o realiza una compra, recopilamos:</p>
                        <ul>
                            <li><strong>Datos de identificación:</strong> Nombre, apellido, DNI</li>
                            <li><strong>Datos de contacto:</strong> Email, teléfono, dirección postal</li>
                            <li><strong>Datos de facturación:</strong> Dirección de facturación</li>
                            <li><strong>Datos de envío:</strong> Dirección de entrega</li>
                            <li><strong>Contraseña:</strong> Almacenada de forma encriptada</li>
                        </ul>

                        <h5 class="fw-semibold mb-3 mt-4">3.2. Datos de Navegación</h5>
                        <p>Cuando navega por nuestro sitio, recopilamos automáticamente:</p>
                        <ul>
                            <li>Dirección IP</li>
                            <li>Tipo de navegador</li>
                            <li>Páginas visitadas</li>
                            <li>Fecha y hora de acceso</li>
                            <li>Productos visualizados</li>
                        </ul>

                        <h5 class="fw-semibold mb-3 mt-4">3.3. Datos de Transacciones</h5>
                        <p>Al realizar una compra, registramos:</p>
                        <ul>
                            <li>Historial de compras</li>
                            <li>Productos adquiridos</li>
                            <li>Método de pago utilizado (no almacenamos datos completos de tarjetas)</li>
                            <li>Estado de pedidos</li>
                        </ul>
                    </section>

                    <!-- Finalidad del Tratamiento -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">4. Finalidad del Tratamiento de Datos</h2>
                        <p>Utilizamos sus datos personales para los siguientes propósitos:</p>

                        <div class="row mt-4">
                            <div class="col-md-6 mb-3">
                                <div class="card border-primary h-100">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-primary">
                                            <i class="bi bi-cart-check me-2"></i>Gestión de Compras
                                        </h6>
                                        <ul class="small mb-0">
                                            <li>Procesar pedidos</li>
                                            <li>Gestionar pagos</li>
                                            <li>Coordinar envíos</li>
                                            <li>Emitir comprobantes</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="card border-success h-100">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-success">
                                            <i class="bi bi-headset me-2"></i>Atención al Cliente
                                        </h6>
                                        <ul class="small mb-0">
                                            <li>Responder consultas</li>
                                            <li>Gestionar cambios y devoluciones</li>
                                            <li>Brindar soporte técnico</li>
                                            <li>Enviar notificaciones de pedidos</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="card border-info h-100">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-info">
                                            <i class="bi bi-graph-up me-2"></i>Mejora de Servicios
                                        </h6>
                                        <ul class="small mb-0">
                                            <li>Análisis de preferencias</li>
                                            <li>Personalizar experiencia</li>
                                            <li>Mejorar funcionalidades</li>
                                            <li>Desarrollar nuevos productos</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="card border-warning h-100">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-warning">
                                            <i class="bi bi-megaphone me-2"></i>Marketing (Opcional)
                                        </h6>
                                        <ul class="small mb-0">
                                            <li>Enviar ofertas y promociones</li>
                                            <li>Newsletters (previa suscripción)</li>
                                            <li>Encuestas de satisfacción</li>
                                            <li>Programas de fidelización</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3" role="alert">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Importante:</strong> Puede optar por no recibir comunicaciones de marketing
                            en cualquier momento utilizando el enlace de "cancelar suscripción" en nuestros emails.
                        </div>
                    </section>

                    <!-- Base Legal -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">5. Base Legal del Tratamiento</h2>
                        <p>El tratamiento de sus datos personales se basa en:</p>
                        <ul>
                            <li><strong>Consentimiento:</strong> Al registrarse y aceptar esta política</li>
                            <li><strong>Ejecución de contrato:</strong> Para procesar sus pedidos</li>
                            <li><strong>Obligación legal:</strong> Para cumplir con normativas fiscales y de defensa del consumidor</li>
                            <li><strong>Interés legítimo:</strong> Para mejorar nuestros servicios y prevenir fraudes</li>
                        </ul>
                    </section>

                    <!-- Compartir Datos -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">6. Compartir Datos con Terceros</h2>
                        <p>
                            Podemos compartir sus datos personales únicamente con terceros de confianza
                            para los siguientes propósitos:
                        </p>
                        <ul>
                            <li><strong>Servicios de envío:</strong> Para entregar sus pedidos</li>
                            <li><strong>Procesadores de pago:</strong> Para gestionar transacciones (Mercado Pago, etc.)</li>
                            <li><strong>Servicios de hosting:</strong> Para alojar nuestro sitio web y bases de datos</li>
                            <li><strong>Autoridades competentes:</strong> Cuando sea requerido por ley</li>
                        </ul>
                        <p class="mt-3">
                            <strong>No vendemos ni alquilamos sus datos personales a terceros con fines comerciales.</strong>
                        </p>
                    </section>

                    <!-- Seguridad -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">7. Seguridad de los Datos</h2>
                        <p>Implementamos medidas de seguridad técnicas y organizativas para proteger sus datos:</p>
                        <div class="row mt-3">
                            <div class="col-md-4 mb-3">
                                <div class="text-center">
                                    <i class="bi bi-lock-fill text-success fs-1"></i>
                                    <p class="fw-bold mt-2">Encriptación</p>
                                    <small class="text-muted">Contraseñas y datos sensibles encriptados</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-center">
                                    <i class="bi bi-shield-check text-primary fs-1"></i>
                                    <p class="fw-bold mt-2">Acceso Restringido</p>
                                    <small class="text-muted">Solo personal autorizado accede a sus datos</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-center">
                                    <i class="bi bi-server text-warning fs-1"></i>
                                    <p class="fw-bold mt-2">Servidores Seguros</p>
                                    <small class="text-muted">Infraestructura protegida y monitoreada</small>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Conservación de Datos -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">8. Conservación de Datos</h2>
                        <p>Conservamos sus datos personales durante:</p>
                        <ul>
                            <li><strong>Cuentas activas:</strong> Mientras mantenga su cuenta abierta</li>
                            <li><strong>Datos de compra:</strong> 10 años (conforme a obligaciones fiscales argentinas)</li>
                            <li><strong>Datos de navegación:</strong> Hasta 2 años</li>
                            <li><strong>Marketing:</strong> Hasta que solicite la baja de comunicaciones</li>
                        </ul>
                    </section>

                    <!-- Derechos del Usuario -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">9. Sus Derechos (Ley 25.326)</h2>
                        <p>De acuerdo con la Ley de Protección de Datos Personales, usted tiene derecho a:</p>

                        <div class="accordion" id="accordionDerechos">
                            <!-- Acceso -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAcceso">
                                        <i class="bi bi-eye-fill me-2 text-primary"></i>
                                        <strong>Derecho de Acceso</strong>
                                    </button>
                                </h2>
                                <div id="collapseAcceso" class="accordion-collapse collapse show" data-bs-parent="#accordionDerechos">
                                    <div class="accordion-body">
                                        Solicitar información sobre qué datos personales tenemos almacenados sobre usted.
                                    </div>
                                </div>
                            </div>

                            <!-- Rectificación -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRectificacion">
                                        <i class="bi bi-pencil-fill me-2 text-success"></i>
                                        <strong>Derecho de Rectificación</strong>
                                    </button>
                                </h2>
                                <div id="collapseRectificacion" class="accordion-collapse collapse" data-bs-parent="#accordionDerechos">
                                    <div class="accordion-body">
                                        Corregir datos inexactos o desactualizados desde su perfil de usuario o contactándonos.
                                    </div>
                                </div>
                            </div>

                            <!-- Cancelación -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCancelacion">
                                        <i class="bi bi-x-circle-fill me-2 text-danger"></i>
                                        <strong>Derecho de Cancelación</strong>
                                    </button>
                                </h2>
                                <div id="collapseCancelacion" class="accordion-collapse collapse" data-bs-parent="#accordionDerechos">
                                    <div class="accordion-body">
                                        Solicitar la eliminación de sus datos personales (excepto cuando exista una obligación legal de conservarlos).
                                    </div>
                                </div>
                            </div>

                            <!-- Oposición -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOposicion">
                                        <i class="bi bi-hand-thumbs-down-fill me-2 text-warning"></i>
                                        <strong>Derecho de Oposición</strong>
                                    </button>
                                </h2>
                                <div id="collapseOposicion" class="accordion-collapse collapse" data-bs-parent="#accordionDerechos">
                                    <div class="accordion-body">
                                        Oponerse al tratamiento de sus datos para fines de marketing directo.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-success mt-4" role="alert">
                            <i class="bi bi-envelope-fill me-2"></i>
                            Para ejercer cualquiera de estos derechos, contáctenos en:
                            <strong>privacidad@maurocalzado.com</strong>
                        </div>
                    </section>

                    <!-- Cookies -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">10. Uso de Cookies</h2>
                        <p>
                            Nuestro sitio web utiliza cookies y tecnologías similares para:
                        </p>
                        <ul>
                            <li>Mantener su sesión activa</li>
                            <li>Recordar preferencias (carrito de compras)</li>
                            <li>Analizar el tráfico del sitio</li>
                            <li>Personalizar contenido</li>
                        </ul>
                        <p>
                            Puede configurar su navegador para rechazar cookies, aunque esto puede afectar
                            la funcionalidad del sitio.
                        </p>
                    </section>

                    <!-- Menores de Edad -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">11. Menores de Edad</h2>
                        <p>
                            Nuestros servicios están dirigidos a personas mayores de 18 años.
                            No recopilamos intencionalmente datos de menores de edad. Si detectamos que
                            hemos recopilado datos de un menor sin consentimiento parental, eliminaremos
                            dicha información.
                        </p>
                    </section>

                    <!-- Modificaciones -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">12. Modificaciones a esta Política</h2>
                        <p>
                            Nos reservamos el derecho de actualizar esta Política de Privacidad.
                            Las modificaciones se publicarán en esta página con la fecha de actualización.
                            Le recomendamos revisar periódicamente esta política.
                        </p>
                    </section>

                    <!-- Contacto -->
                    <section class="mb-4">
                        <h2 class="h4 fw-bold text-primary mb-3">13. Contacto</h2>
                        <p>Para consultas sobre privacidad y protección de datos:</p>
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <p class="mb-2">
                                    <i class="bi bi-envelope-fill text-primary me-2"></i>
                                    <strong>Email de Privacidad:</strong> privacidad@maurocalzado.com
                                </p>
                                <p class="mb-2">
                                    <i class="bi bi-envelope text-primary me-2"></i>
                                    <strong>Email General:</strong> info@maurocalzado.com
                                </p>
                                <p class="mb-2">
                                    <i class="bi bi-telephone-fill text-primary me-2"></i>
                                    <strong>Teléfono:</strong> (383) 123-4567
                                </p>
                                <p class="mb-0">
                                    <i class="bi bi-clock text-primary me-2"></i>
                                    <strong>Horario de atención:</strong> Lunes a Viernes 9:00 - 18:00 hs
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Footer Legal -->
                    <div class="alert alert-primary" role="alert">
                        <i class="bi bi-shield-check me-2"></i>
                        <strong>Esta política cumple con la Ley 25.326 de Protección de Datos Personales de Argentina.</strong>
                    </div>

                </div>
            </div>

            <!-- Botón Volver -->
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-arrow-left me-2"></i>Volver al Inicio
                </a>
            </div>

        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
