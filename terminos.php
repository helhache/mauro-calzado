<?php
/**
 * TÉRMINOS Y CONDICIONES - MAURO CALZADO
 *
 * Página legal con términos de uso del sitio web y servicios
 */

require_once('includes/config.php');
$titulo_pagina = "Términos y Condiciones";
require_once('includes/header.php');
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <!-- Encabezado -->
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold mb-3">Términos y Condiciones</h1>
                <p class="text-muted">Última actualización: <?php echo date('d/m/Y'); ?></p>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">

                    <!-- Introducción -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">1. Introducción</h2>
                        <p>
                            Bienvenido a <strong>Mauro Calzado</strong>. Al acceder y utilizar este sitio web,
                            usted acepta cumplir y estar sujeto a los siguientes términos y condiciones de uso.
                            Si no está de acuerdo con alguna parte de estos términos, le pedimos que no utilice
                            nuestro sitio web.
                        </p>
                    </section>

                    <!-- Definiciones -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">2. Definiciones</h2>
                        <ul class="list-unstyled ms-3">
                            <li class="mb-2"><strong>"Sitio"</strong>: se refiere al sitio web de Mauro Calzado.</li>
                            <li class="mb-2"><strong>"Usuario"</strong>: cualquier persona que acceda o utilice el sitio.</li>
                            <li class="mb-2"><strong>"Servicios"</strong>: todos los servicios ofrecidos a través del sitio, incluyendo la venta de calzado.</li>
                            <li class="mb-2"><strong>"Productos"</strong>: todo el calzado y artículos relacionados disponibles en el sitio.</li>
                        </ul>
                    </section>

                    <!-- Uso del Sitio -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">3. Uso del Sitio</h2>
                        <h5 class="fw-semibold mb-2">3.1. Registro de Cuenta</h5>
                        <p>
                            Para realizar compras, es necesario crear una cuenta proporcionando información veraz,
                            completa y actualizada. Usted es responsable de mantener la confidencialidad de su
                            contraseña y de todas las actividades que ocurran bajo su cuenta.
                        </p>

                        <h5 class="fw-semibold mb-2 mt-4">3.2. Uso Permitido</h5>
                        <p>El usuario se compromete a utilizar el sitio únicamente para:</p>
                        <ul>
                            <li>Consultar información sobre productos</li>
                            <li>Realizar compras legítimas</li>
                            <li>Comunicarse con el servicio de atención al cliente</li>
                        </ul>

                        <h5 class="fw-semibold mb-2 mt-4">3.3. Uso Prohibido</h5>
                        <p>Está prohibido:</p>
                        <ul>
                            <li>Realizar actividades fraudulentas o ilegales</li>
                            <li>Intentar acceder a áreas restringidas del sitio</li>
                            <li>Usar el sitio para distribuir malware o virus</li>
                            <li>Copiar, reproducir o distribuir contenido del sitio sin autorización</li>
                            <li>Utilizar datos de terceros sin su consentimiento</li>
                        </ul>
                    </section>

                    <!-- Productos y Precios -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">4. Productos y Precios</h2>
                        <h5 class="fw-semibold mb-2">4.1. Información de Productos</h5>
                        <p>
                            Nos esforzamos por mostrar con precisión los colores, características y detalles de
                            nuestros productos. Sin embargo, no garantizamos que las imágenes y descripciones
                            sean 100% exactas debido a variaciones en monitores y condiciones de fotografía.
                        </p>

                        <h5 class="fw-semibold mb-2 mt-4">4.2. Disponibilidad</h5>
                        <p>
                            Todos los productos están sujetos a disponibilidad. Nos reservamos el derecho de
                            limitar las cantidades de productos ofrecidos y de cancelar pedidos en caso de
                            errores en precios o descripciones.
                        </p>

                        <h5 class="fw-semibold mb-2 mt-4">4.3. Precios</h5>
                        <p>
                            Los precios mostrados están expresados en pesos argentinos e incluyen IVA.
                            Nos reservamos el derecho de modificar precios sin previo aviso. El precio aplicable
                            será el vigente al momento de confirmar el pedido.
                        </p>
                    </section>

                    <!-- Proceso de Compra -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">5. Proceso de Compra</h2>
                        <h5 class="fw-semibold mb-2">5.1. Confirmación de Pedido</h5>
                        <p>
                            Al realizar un pedido, recibirá un correo electrónico de confirmación. Esta confirmación
                            NO constituye la aceptación del pedido. Nos reservamos el derecho de rechazar pedidos
                            por diversas razones, incluyendo disponibilidad de stock o errores en el precio.
                        </p>

                        <h5 class="fw-semibold mb-2 mt-4">5.2. Métodos de Pago</h5>
                        <p>Aceptamos los siguientes métodos de pago:</p>
                        <ul>
                            <li>Efectivo (en retiro en sucursal)</li>
                            <li>Tarjetas de débito y crédito</li>
                            <li>Transferencia bancaria</li>
                            <li>Mercado Pago</li>
                            <li>Sistema de crédito "TodoCredito" (sujeto a aprobación)</li>
                        </ul>

                        <h5 class="fw-semibold mb-2 mt-4">5.3. Envíos</h5>
                        <p>
                            Los costos y tiempos de envío se informan al momento de realizar la compra.
                            Ofrecemos envío gratuito en compras superiores a $50.000. Los plazos de entrega
                            son estimados y pueden variar según la ubicación y disponibilidad.
                        </p>
                    </section>

                    <!-- Cambios y Devoluciones -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">6. Cambios y Devoluciones</h2>
                        <h5 class="fw-semibold mb-2">6.1. Política de Cambios</h5>
                        <p>
                            Aceptamos cambios dentro de los <strong>30 días</strong> posteriores a la compra,
                            siempre que el producto esté en perfectas condiciones, sin uso y con su embalaje original.
                        </p>

                        <h5 class="fw-semibold mb-2 mt-4">6.2. Procedimiento</h5>
                        <p>Para solicitar un cambio:</p>
                        <ol>
                            <li>Contacte a nuestro servicio de atención al cliente</li>
                            <li>Presente el comprobante de compra</li>
                            <li>El producto será verificado antes de procesar el cambio</li>
                        </ol>

                        <h5 class="fw-semibold mb-2 mt-4">6.3. Productos en Oferta</h5>
                        <p>
                            Los productos adquiridos en promoción o con descuento están sujetos a las mismas
                            políticas de cambio, excepto aquellos que se indiquen expresamente como
                            "venta final".
                        </p>
                    </section>

                    <!-- Propiedad Intelectual -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">7. Propiedad Intelectual</h2>
                        <p>
                            Todo el contenido del sitio, incluyendo pero no limitado a textos, gráficos,
                            logotipos, imágenes y software, es propiedad de Mauro Calzado y está protegido
                            por las leyes de propiedad intelectual. Queda prohibida su reproducción,
                            distribución o modificación sin autorización expresa.
                        </p>
                    </section>

                    <!-- Limitación de Responsabilidad -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">8. Limitación de Responsabilidad</h2>
                        <p>
                            Mauro Calzado no será responsable por daños indirectos, incidentales,
                            especiales o consecuentes derivados del uso o la imposibilidad de uso del sitio.
                            No garantizamos que el sitio esté libre de errores o que el acceso sea
                            ininterrumpido.
                        </p>
                    </section>

                    <!-- Modificaciones -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">9. Modificaciones de los Términos</h2>
                        <p>
                            Nos reservamos el derecho de modificar estos términos y condiciones en cualquier
                            momento. Las modificaciones entrarán en vigencia al ser publicadas en el sitio.
                            Es responsabilidad del usuario revisar periódicamente estos términos.
                        </p>
                    </section>

                    <!-- Legislación Aplicable -->
                    <section class="mb-5">
                        <h2 class="h4 fw-bold text-primary mb-3">10. Legislación Aplicable</h2>
                        <p>
                            Estos términos se rigen por las leyes de la República Argentina.
                            Cualquier controversia será sometida a los tribunales competentes de la
                            provincia de Catamarca.
                        </p>
                    </section>

                    <!-- Contacto -->
                    <section class="mb-4">
                        <h2 class="h4 fw-bold text-primary mb-3">11. Contacto</h2>
                        <p>Para cualquier consulta sobre estos términos y condiciones, puede contactarnos a través de:</p>
                        <ul class="list-unstyled ms-3">
                            <li class="mb-2"><i class="bi bi-envelope-fill text-primary me-2"></i> Email: info@maurocalzado.com</li>
                            <li class="mb-2"><i class="bi bi-telephone-fill text-primary me-2"></i> Teléfono: (383) 123-4567</li>
                            <li class="mb-2"><i class="bi bi-geo-alt-fill text-primary me-2"></i> Dirección: San Fernando del Valle de Catamarca</li>
                        </ul>
                    </section>

                    <!-- Aceptación -->
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <strong>Al utilizar este sitio web, usted acepta estos términos y condiciones en su totalidad.</strong>
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

<?php require_once('includes/footer.php'); ?>
