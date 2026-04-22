# Plan de Testing Completo — mauro-calzado
> Rol: Tester QA | Fecha: 2026-04-22 | Entorno: XAMPP local

---

## Entorno y prerequisitos

```
PHP 8.2 + MariaDB + Apache (XAMPP)
Base: mauro_calzado (importar mauro_calzado.sql)
URL base: http://localhost/mauro-calzado/
Credenciales admin: (las de tu BD)
Navegador: Chrome/Firefox con DevTools abierto (F12)
```

---

## MÓDULO 1 — Autenticación

### TC-01 Login admin válido
- Ir a `/admin/` → redirige a login
- Ingresar credenciales correctas
- **Esperado:** redirige a `admin/dashboard.php`

### TC-02 Login con credenciales incorrectas
- Ingresar usuario o contraseña mal
- **Esperado:** mensaje de error, NO redirige
- Repetir N veces (según config de Seguridad) → debe bloquear

### TC-03 Acceso sin sesión
- Abrir en incógnito `admin/dashboard.php` sin loguearse
- **Esperado:** redirige al login

### TC-04 Login cliente
- Registrar un usuario nuevo desde la tienda
- Iniciar sesión con ese usuario
- **Esperado:** accede al área de cliente, NO puede acceder a `/admin/`

---

## MÓDULO 2 — Catálogo (Frontend)

### TC-05 Página principal
- Ir a `/index.php`
- **Esperado:** carrusel banner visible y funcional (flechas, autoplay), productos destacados, footer

### TC-06 Páginas de categoría
- Ir a `/hombre.php`, `/mujer.php`, `/infantiles.php`, `/ofertas.php`
- **Esperado:** productos se listan, carrusel presente en todas

### TC-07 Filtros en catálogo
- En `/hombre.php`: filtrar por precio, talla, color
- **Esperado:** productos se filtran sin recargar la página (AJAX)

### TC-08 Ficha de producto
- Hacer clic en un producto → `/producto.php?id=X`
- **Esperado:**
  - Imagen principal grande
  - Miniaturas de galería clickeables (si tiene imágenes adicionales)
  - Descripción, precio, tallas disponibles
  - Selector de talla y cantidad
  - Botón "Agregar al carrito" funcional
  - Sección de reseñas con estrellas

### TC-09 Galería en producto.php
- Producto con galería cargada desde admin
- **Esperado:** miniaturas muestran las imágenes subidas, al hacer clic cambia la imagen principal

### TC-10 Reseñas
- Logueado como cliente, en ficha de producto
- Dejar una reseña con rating de estrellas
- **Esperado:** reseña aparece en la lista, rating promedio se actualiza

---

## MÓDULO 3 — Carrito y Checkout

### TC-11 Agregar al carrito
- Seleccionar talla y cantidad → "Agregar al carrito"
- **Esperado:** ícono de carrito en navbar actualiza el contador

### TC-12 Ver carrito
- Ir al carrito
- **Esperado:** lista de productos, cantidades, precios, subtotal y total correctos

### TC-13 Modificar cantidad en carrito
- Cambiar cantidad de un ítem
- **Esperado:** subtotal y total se actualizan

### TC-14 Eliminar del carrito
- Eliminar un producto
- **Esperado:** ítem desaparece, total recalcula

### TC-15 Checkout completo
- Completar formulario de envío
- **Esperado:** pedido se crea, stock se descuenta, email de confirmación (si SMTP configurado)

---

## MÓDULO 4 — Admin: Productos

### TC-16 Listado de productos
- Ir a `admin/productos.php`
- **Esperado:** tabla con todos los productos, paginación, búsqueda

### TC-17 Crear producto nuevo
- Hacer clic en "Nuevo producto"
- Completar nombre, precio, descripción, categoría, stock, imagen principal
- Guardar
- **Esperado:** producto aparece en el listado y en el frontend

### TC-18 Editar producto
- Hacer clic en editar de un producto existente
- Modificar precio
- Guardar
- **Esperado:** precio actualizado en admin y frontend

### TC-19 Galería — subir imágenes
- Clic en botón **"Galería"** (verde) de un producto
- Subir 3 imágenes JPG/PNG (< 5MB cada una)
- **Esperado:** imágenes aparecen en el modal con miniaturas

### TC-20 Galería — reordenar
- Arrastrar imágenes en distinto orden → guardar
- Ir a `producto.php?id=X` → miniaturas deben estar en el nuevo orden

### TC-21 Galería — eliminar imagen
- Eliminar una imagen desde el modal
- **Esperado:** imagen desaparece del modal y de `producto.php`

### TC-22 Dar de baja un producto
- Usar la opción de baja en un producto
- **Esperado:** producto desaparece del catálogo, aparece en `admin/bajas-productos.php`

---

## MÓDULO 5 — Admin: Configuración

### TC-23 Tab General — persistencia
- Guardar valores en tab General
- Cerrar sesión y volver a entrar → volver a configuración
- **Esperado:** los valores guardados persisten

### TC-24 Tab Email — valores guardados
- Guardar configuración SMTP
- **Esperado:** éxito. `includes/email-config.php` los lee desde BD

### TC-25 Tab Seguridad — validación de rangos
- Intentar guardar longitud de contraseña = 2 (fuera del rango 6-32)
- **Esperado:** validación lo rechaza

### TC-26 Tab Mantenimiento — toggle
- Activar modo mantenimiento
- En ventana incógnito, navegar al sitio
- **Esperado:** página de mantenimiento visible
- Desactivar → sitio vuelve a funcionar

### TC-27 Tab Mantenimiento — info sistema
- Ver panel Info del Sistema
- **Esperado:** muestra versión PHP, versión MariaDB, cantidad de tablas de la BD

---

## MÓDULO 6 — Admin: Cajas / Turnos

### TC-28 Carga inicial
- Ir a `admin/cajas.php`
- **Esperado:** carga sin errores JS en consola (F12), 4 KPI cards con valores, gráfico de barras visible

### TC-29 Filtros de cajas
- Filtrar por fecha específica → tabla muestra solo turnos de ese día
- Filtrar por sucursal → solo turnos de esa sucursal
- Filtrar por estado (Abierto / Cerrado)
- **Esperado:** cada filtro reduce la tabla correctamente

### TC-30 Modal detalle de turno
- Clic en "Ver detalle" de un turno
- **Esperado:** modal muestra: datos del turno (apertura/cierre, cajero), ventas del turno, gastos, cobros de cuotas, diferencia de caja con badge de color

### TC-31 Impresión / PDF
- Clic en "Imprimir / PDF" de un turno
- **Esperado:** nueva pestaña con `cierre-caja-pdf.php?id=X`, contenido completo, auto-print activa el diálogo de impresión del browser

### TC-32 PDF sin datos reales
- Si no hay turnos en BD: verificar que `cajas.php` muestra estado vacío sin errores

---

## MÓDULO 7 — Admin: Dashboards y Reportes

### TC-33 Dashboard admin — gráficos
- Ir a `admin/dashboard.php`
- **Esperado:** gráfico de ventas mensuales y gráfico por categoría renderizan correctamente (no en blanco)
- Sin errores en consola JS

### TC-34 Dashboard gerente
- Loguearse como gerente → `gerente/dashboard.php`
- **Esperado:** KPIs visibles, gráficos cargados
- Clic en link "Reportes" → **debe redirigir a `ventas.php`** (NO a `reportes.php`)

---

## MÓDULO 8 — Admin: Pedidos y Usuarios

### TC-35 Listado de pedidos
- `admin/pedidos.php` → tabla con todos los pedidos
- Cambiar estado de un pedido (Pendiente → En preparación → Enviado)
- **Esperado:** estado cambia, cliente recibe notificación (si SMTP activo)

### TC-36 Detalle de usuario
- `admin/usuarios.php` → ver detalle de un usuario
- **Esperado:** historial de pedidos del usuario visible en modal

---

## MÓDULO 9 — Admin: Transferencias y Reviews

### TC-37 Transferencias entre sucursales
- `admin/transferencias.php` → listado de transferencias
- Aprobar / rechazar una transferencia
- **Esperado:** estado cambia, stock se actualiza en sucursal origen/destino

### TC-38 Moderación de reseñas
- `admin/reviews.php` → listado de reseñas pendientes
- Aprobar una reseña
- **Esperado:** reseña aparece visible en `producto.php`
- Rechazar otra → no aparece en frontend

---

## MÓDULO 10 — Seguridad básica

### TC-39 Inyección SQL
- En buscador del frontend: ingresar `' OR 1=1 --`
- **Esperado:** no devuelve todos los productos, no rompe la página

### TC-40 XSS básico
- En campo de nombre al registrarse: `<script>alert(1)</script>`
- **Esperado:** se escapa como texto, no ejecuta el script

### TC-41 Acceso directo a AJAX sin sesión
- Abrir en incógnito `admin/ajax/guardar-configuracion.php`
- **Esperado:** devuelve error de autenticación (no ejecuta la acción)

---

## Bugs conocidos / Pendientes al iniciar testing

- [ ] Credenciales Gmail no configuradas → emails no se envían hasta completar tab Email
- [ ] Stored procedures `actualizar_stock`, `crear_pedido_desde_carrito`, `marcar_mensaje_leido` existen en BD pero no se usan desde PHP (no bloquean funcionalidad)
- [ ] Módulo de cajas requiere datos en tabla `turnos` para ver KPIs con valores reales

---

## Registro de bugs encontrados

| # | Módulo | Descripción | Severidad | Estado |
|---|--------|-------------|-----------|--------|
| - | - | (completar durante testing) | - | - |

---

## Criterio de aceptación

- **Crítico (bloqueante):** ningún TC de autenticación, carrito o checkout falla
- **Alto:** galería, cajas y configuración funcionan end-to-end
- **Medio:** dashboards muestran datos, filtros responden
- **Bajo:** estilos visuales, textos, responsive en mobile
