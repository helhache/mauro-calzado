# Guía de verificación — Cambios sesión 2026-04-22

Clonar o hacer pull del repo antes de empezar:
```
git pull origin master
```

---

## 1. Configuración del entorno

- Importar `base de datos/mauro_calzado.sql` en phpMyAdmin (base: `mauro_calzado`)
- Verificar que XAMPP tenga Apache + MySQL corriendo
- Acceder a `http://localhost/mauro-calzado/`

---

## 2. Tab Configuración — General

1. Ir a `http://localhost/mauro-calzado/admin/configuracion.php`
2. Tab **General**: completar nombre tienda, slogan, teléfono, email, dirección
3. Hacer clic en "Guardar"
4. **Esperado:** mensaje de éxito verde. Recargar la página → los valores deben persistir (se guardan en tabla `configuracion` de la BD)

---

## 3. Tab Configuración — Email (SMTP)

1. Tab **Email**: completar SMTP host, puerto, usuario, contraseña, nombre remitente
2. Guardar
3. **Esperado:** éxito. Los valores se guardan en BD, `includes/email-config.php` los lee automáticamente

> **Nota:** Si usás Gmail, necesitás habilitar "Contraseñas de aplicación" en tu cuenta Google y usar esa contraseña aquí.

---

## 4. Tab Configuración — Seguridad

1. Tab **Seguridad**: cambiar longitud mínima de contraseña y máx intentos de login
2. Guardar
3. **Esperado:** éxito. Valores entre (6-32) para contraseña y (3-20) para intentos

---

## 5. Tab Configuración — Mantenimiento

1. Tab **Mantenimiento**: activar el toggle de modo mantenimiento
2. Abrir una ventana de incógnito y entrar al sitio como usuario normal
3. **Esperado:** página de mantenimiento visible para no-admins
4. Desactivar el toggle → el sitio vuelve a funcionar normalmente
5. El panel **Info del Sistema** debe mostrar versión PHP, MariaDB y cantidad de tablas

---

## 6. Galería múltiple de imágenes de productos

1. Ir a `admin/productos.php`
2. En cualquier producto, hacer clic en el botón verde **"Galería"**
3. **Esperado:** abre modal con imágenes actuales del producto
4. Subir una imagen nueva (jpg/png)
5. **Esperado:** imagen aparece en la galería del modal
6. Reordenar arrastrando imágenes → guardar orden
7. Eliminar una imagen → confirmar que desaparece
8. Ir a `producto.php?id=X` (mismo producto) → las miniaturas deben reflejar la galería actualizada

---

## 7. Módulo Cajas / Turnos (admin)

1. Ir a `admin/cajas.php` (link en sidebar: "Cajas / Turnos")
2. **Esperado:** página carga con 4 KPI cards (Turnos Abiertos, Ventas del Día, Diferencia Total, Turnos del Mes), gráfico de barras por sucursal, tabla de turnos
3. Usar los filtros: fecha, sucursal, turno activo/cerrado → tabla se actualiza
4. Hacer clic en **"Ver detalle"** de un turno → modal con ventas, gastos, cobros
5. Hacer clic en **"Imprimir / PDF"** → nueva pestaña con `cierre-caja-pdf.php?id=X`
6. **Esperado:** página imprimible con logo institucional, resumen financiero, detalle de operaciones, espacio para firmas

---

## 8. Dashboards

### Admin
1. Ir a `admin/dashboard.php`
2. **Esperado:** los dos gráficos (ventas mensuales y por categoría) se inicializan correctamente al cargar. Si antes estaban en blanco, ahora deben renderizar.

### Gerente
1. Ir a `gerente/dashboard.php`
2. Hacer clic en link **"Reportes"** del panel
3. **Esperado:** redirige a `ventas.php` (antes apuntaba a `reportes.php` que no existe)

---

## 9. Sidebar admin

1. Revisar el menú lateral en cualquier página de `admin/`
2. **Esperado:** aparece ítem **"Cajas / Turnos"** con ícono de caja registradora, antes de la sección "OTROS"

---

## Checklist rápido

| # | Ítem | OK |
|---|------|----|
| 1 | BD importada sin errores | ☐ |
| 2 | Config General guarda y persiste | ☐ |
| 3 | Config Email guarda | ☐ |
| 4 | Config Seguridad guarda | ☐ |
| 5 | Modo mantenimiento activa/desactiva | ☐ |
| 6 | Galería: subir imagen | ☐ |
| 7 | Galería: reordenar | ☐ |
| 8 | Galería: eliminar | ☐ |
| 9 | Galería visible en producto.php | ☐ |
| 10 | Cajas: KPIs y gráfico cargan | ☐ |
| 11 | Cajas: filtros funcionan | ☐ |
| 12 | Cajas: modal detalle abre | ☐ |
| 13 | Cajas: PDF imprimible abre | ☐ |
| 14 | Dashboard admin: gráficos renderizan | ☐ |
| 15 | Dashboard gerente: link Reportes → ventas.php | ☐ |
| 16 | Sidebar: "Cajas / Turnos" visible | ☐ |
