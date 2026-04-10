# GUÍA DE IMPLEMENTACIÓN - ARCHIVOS JAVASCRIPT

Esta guía explica cómo implementar los archivos JavaScript organizados en los archivos PHP del proyecto.

---

## ESTRUCTURA DE ARCHIVOS JAVASCRIPT

```
js/
├── main.js                 # Funciones globales, carrito, favoritos
├── productos.js            # Gestión de productos (Admin/Gerente)
├── admin.js               # Panel de administración (usuarios, sucursales)
├── gerente.js             # Panel de gerente (caja, ventas)
├── graficos.js            # Gráficos con Chart.js
├── carrito.js             # Funcionalidad del carrito
├── producto-detalle.js    # Página de detalle de producto
└── validaciones.js        # Validación de formularios
```

---

## IMPLEMENTACIÓN POR TIPO DE PÁGINA

### 1. PÁGINAS PÚBLICAS (index.php, mujer.php, hombre.php, etc.)

**Agregar antes del `</body>`:**

```php
<!-- JavaScript Principal -->
<script src="js/main.js"></script>
```

**Ejemplo completo:**

```php
<?php require_once('includes/footer.php'); ?>

<!-- JavaScript Principal -->
<script src="js/main.js"></script>
</body>
</html>
```

**ELIMINAR:** Todo el código `<script>` inline que haya en estos archivos.

---

### 2. PÁGINA DE PRODUCTO (producto.php)

**Agregar antes del `</body>`:**

```php
<!-- JavaScript Principal -->
<script src="js/main.js"></script>
<script src="js/producto-detalle.js"></script>
```

**REEMPLAZAR:**

```javascript
// CÓDIGO VIEJO (ELIMINAR):
<script>
let colorSeleccionado = null;
let talleSeleccionado = null;

function cambiarImagenPrincipal(src) {
    // ... código inline
}
// ... resto del código inline
</script>
```

**POR:** (solo incluir los scripts, la funcionalidad ya está en producto-detalle.js)

```php
<script src="js/main.js"></script>
<script src="js/producto-detalle.js"></script>
```

---

### 3. CARRITO (carrito.php)

**Agregar antes del `</body>`:**

```php
<!-- JavaScript Principal -->
<script src="js/main.js"></script>
<script src="js/carrito.js"></script>
```

**ELIMINAR:**

```javascript
<script>
    // Auto-submit al cambiar cantidad con las flechas
    document.querySelectorAll('input[name="cantidad"]').forEach(input => {
        // ... código inline
    });
</script>
```

La funcionalidad ya está en `carrito.js`.

---

### 4. LOGIN Y REGISTRO (login.php, registro.php)

**Agregar antes del `</body>`:**

```php
<!-- JavaScript Principal -->
<script src="js/main.js"></script>
<script src="js/validaciones.js"></script>

<script>
// Validación del formulario específico
document.getElementById('formRegistro')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const resultado = FormularioValidator.registro(formData);

    if (!resultado.valido) {
        resultado.errores.forEach(error => {
            MauroCalzado.mostrarAlerta(error, 'warning');
        });
        return;
    }

    // Si es válido, enviar formulario
    this.submit();
});
</script>
```

---

### 5. ADMIN - DASHBOARD (admin/dashboard.php)

**Agregar antes del `</body>`:**

```php
<?php require_once('includes/footer-admin.php'); ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<!-- JavaScript Principal -->
<script src="../js/graficos.js"></script>

<script>
// Crear gráficos con datos de PHP
const ventasChart = GraficoVentas.crear(
    'ventasChart',
    <?php echo json_encode($meses); ?>,
    <?php echo json_encode($ventas_mensuales); ?>
);

const categoriasChart = GraficoCategorias.crear(
    'categoriasChart',
    <?php echo json_encode($categorias_nombres); ?>,
    <?php echo json_encode($categorias_valores); ?>,
    <?php echo json_encode($categorias_colores); ?>
);
</script>
</body>
</html>
```

**ELIMINAR:** Todo el código inline de Chart.js que esté en el archivo.

---

### 6. ADMIN - PRODUCTOS (admin/productos.php)

**Agregar antes del `</body>`:**

```php
<?php require_once('includes/footer-admin.php'); ?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- JavaScript Principal -->
<script src="../js/productos.js"></script>
</body>
</html>
```

**ELIMINAR de los botones:**

```html
<!-- ANTES (ELIMINAR onclick): -->
<button class="btn btn-primary" onclick="abrirModalCrear()">Nuevo Producto</button>
<button class="btn btn-sm btn-warning" onclick="abrirModalEditar(<?php echo $producto['id']; ?>)">Editar</button>

<!-- DESPUÉS (usar data-attributes): -->
<button class="btn btn-primary" id="btnCrearProducto">Nuevo Producto</button>
<button class="btn btn-sm btn-warning btn-editar-producto" data-id="<?php echo $producto['id']; ?>">Editar</button>
```

---

### 7. ADMIN - USUARIOS (admin/usuarios.php)

**Agregar antes del `</body>`:**

```php
<?php require_once('includes/footer-admin.php'); ?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- JavaScript Principal -->
<script src="../js/admin.js"></script>
</body>
</html>
```

**REEMPLAZAR botones:**

```html
<!-- ANTES: -->
<button onclick="abrirModalEditarUsuario(<?php echo $usuario['id']; ?>)">Editar</button>
<button onclick="cambiarEstadoUsuario(<?php echo $usuario['id']; ?>)">Cambiar Estado</button>

<!-- DESPUÉS: -->
<button class="btn-editar-usuario" data-id="<?php echo $usuario['id']; ?>">Editar</button>
<button class="btn-cambiar-estado-usuario" data-id="<?php echo $usuario['id']; ?>">Cambiar Estado</button>
```

---

### 8. ADMIN - SUCURSALES (admin/sucursales.php)

**Agregar antes del `</body>`:**

```php
<?php require_once('includes/footer-admin.php'); ?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- JavaScript Principal -->
<script src="../js/admin.js"></script>
</body>
</html>
```

---

### 9. GERENTE - DASHBOARD (gerente/dashboard.php)

**Agregar antes del `</body>`:**

```php
<?php require_once('includes/footer-gerente.php'); ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<!-- JavaScript Principal -->
<script src="../js/graficos.js"></script>

<script>
// Similar al admin dashboard
const ventasChart = GraficoVentas.crear(
    'ventasChart',
    <?php echo json_encode($meses); ?>,
    <?php echo json_encode($ventas_mensuales); ?>
);
</script>
</body>
</html>
```

---

### 10. GERENTE - PRODUCTOS (gerente/productos.php)

**Agregar antes del `</body>`:**

```php
<?php require_once('includes/footer-gerente.php'); ?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- JavaScript Principal -->
<script src="../js/productos.js"></script>
</body>
</html>
```

---

### 11. GERENTE - CAJA (gerente/caja.php)

**Agregar antes del `</body>`:**

```php
<?php require_once('includes/footer-gerente.php'); ?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- JavaScript Principal -->
<script src="../js/gerente.js"></script>
</body>
</html>
```

**ELIMINAR:** Todo el JavaScript inline relacionado con caja, turnos, ventas.

---

## CAMBIOS EN BOTONES Y EVENT HANDLERS

### PATRÓN ANTIGUO (ELIMINAR):

```html
<button onclick="funcionGlobal()">Acción</button>
<button onclick="abrirModal(<?php echo $id; ?>)">Editar</button>
<form onsubmit="return validarFormulario()">
```

### PATRÓN NUEVO (USAR):

```html
<!-- Usar data-attributes y event delegation -->
<button class="btn-accion" data-id="<?php echo $id; ?>">Acción</button>
<button id="btnEspecifico">Acción Única</button>

<!-- Los event listeners están en los archivos .js -->
```

---

## DEPENDENCIAS EXTERNAS REQUERIDAS

Agregar en `<head>` de los archivos que corresponda:

```html
<!-- SweetAlert2 (para modales y alertas elegantes) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Chart.js (para gráficos) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<!-- Animate.css (opcional - para animaciones) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
```

---

## ORDEN DE CARGA RECOMENDADO

```html
<!-- 1. Librerías externas (en <head> o antes de scripts) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<!-- 2. Main.js (siempre primero) -->
<script src="js/main.js"></script>

<!-- 3. Módulos específicos -->
<script src="js/graficos.js"></script>
<script src="js/productos.js"></script>

<!-- 4. Código inline específico de la página (si es necesario) -->
<script>
// Solo datos de PHP o inicialización específica
const datosProducto = <?php echo json_encode($producto); ?>;
</script>
```

---

## ARCHIVOS PHP A MODIFICAR

### Alta prioridad:
1. `index.php` - Agregar smooth scroll
2. `producto.php` - Reemplazar todo el JS inline
3. `carrito.php` - Reemplazar auto-submit
4. `admin/dashboard.php` - Implementar graficos.js
5. `admin/productos.php` - Implementar productos.js
6. `admin/usuarios.php` - Implementar admin.js
7. `gerente/dashboard.php` - Implementar graficos.js
8. `gerente/productos.php` - Implementar productos.js
9. `gerente/caja.php` - Implementar gerente.js

### Media prioridad:
- `login.php`, `registro.php` - Implementar validaciones.js
- `admin/sucursales.php` - Implementar admin.js
- `checkout.php` - Implementar validaciones.js

### Baja prioridad:
- Páginas de categorías (mujer.php, hombre.php, etc.)
- Páginas informativas (nosotros.php, contactanos.php)

---

## VERIFICACIÓN

Después de implementar, verificar en consola del navegador (F12):

1. No deben aparecer errores de JavaScript
2. Debe aparecer el mensaje: "¡Bienvenido a Mauro Calzado!"
3. Las funcionalidades deben seguir funcionando correctamente

---

## NOTAS IMPORTANTES

1. **NO eliminar** el código PHP, solo el JavaScript inline
2. **Mantener** la estructura HTML de botones y formularios
3. **Agregar** clases CSS según los patrones nuevos
4. **Probar** cada página después de modificarla
5. **Hacer commit** después de cada grupo de cambios

---

## EJEMPLO COMPLETO - index.php

**ANTES:**

```php
<?php require_once('includes/footer.php'); ?>

<script>
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            // ... código inline
        });
    });
</script>
</body>
</html>
```

**DESPUÉS:**

```php
<?php require_once('includes/footer.php'); ?>

<!-- JavaScript Principal -->
<script src="js/main.js"></script>
</body>
</html>
```

La funcionalidad de smooth scroll ya está incluida en `main.js`.

---

## SOPORTE

Si encuentras errores después de implementar:

1. Verifica la consola del navegador (F12)
2. Asegúrate de que las rutas de los archivos JS sean correctas
3. Verifica que las funciones globales estén correctamente expuestas
4. Revisa que los data-attributes tengan el formato correcto

---

**Última actualización:** 2026-01-25
**Versión:** 2.0
