# IMÁGENES FALTANTES - LOGOS DE MEDIOS DE PAGO

## ⚠️ IMÁGENES REQUERIDAS

El sistema necesita los siguientes logos de medios de pago. **Debes descargarlos y colocarlos en la carpeta `img/`**:

### 1. **visa.png**
- Tamaño recomendado: 60x40px
- Formato: PNG con fondo transparente
- Descargar de: https://brand.visa.com/en_US/downloads.html
- O buscar en Google Images: "visa logo PNG transparent"

### 2. **mastercard.png**
- Tamaño recomendado: 60x40px
- Formato: PNG con fondo transparente
- Descargar de: https://brand.mastercard.com/brandcenter
- O buscar en Google Images: "mastercard logo PNG transparent"

### 3. **mercadopago.png**
- Tamaño recomendado: 100x40px
- Formato: PNG con fondo transparente
- Descargar de: https://www.mercadopago.com.ar/developers/es/guides/resources/branding
- O buscar en Google Images: "mercadopago logo PNG transparent"

### 4. **default-product.jpg** (Opcional)
- Tamaño: 300x300px
- Imagen genérica para productos sin foto
- Puede ser una imagen de zapato genérico o icono de "Sin imagen"
- Formato: JPG o PNG

---

## 📂 UBICACIÓN FINAL

Después de descargar, la estructura debe quedar así:

```
C:\xampp\htdocs\mauro-calzado\img\
├── visa.png ✅
├── mastercard.png ✅
├── mercadopago.png ✅
├── default-product.jpg ✅ (opcional)
├── logo.png (existente)
└── banner.jpg (existente)
```

---

## 🔄 SOLUCIÓN TEMPORAL

Por ahora, el sistema NO fallará si faltan estas imágenes, pero se verá mejor con los logos correctos.

Si quieres crear placeholders temporales en texto, puedes usar este CSS:

```css
/* Agregar a css/styles.css */
.payment-logo {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 40px;
    background: #f0f0f0;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 10px;
    font-weight: bold;
    color: #666;
    text-align: center;
}
```

Y en el HTML usar:

```html
<!-- Temporal hasta tener las imágenes -->
<span class="payment-logo">VISA</span>
<span class="payment-logo">MC</span>
<span class="payment-logo">MP</span>
```

---

## ⚡ DESCARGA RÁPIDA

**Enlaces directos (Abril 2023):**

1. **Visa**: https://usa.visa.com/dam/VCOM/download/visa-logo.png
2. **Mastercard**: https://www.mastercard.us/content/dam/mccom/brandcenter/images/mastercard-logo.png
3. **Mercado Pago**: https://http2.mlstatic.com/frontend-assets/ui-navigation/5.18.5/mercadopago/logo__large.png

*Nota: Los enlaces pueden cambiar. Si no funcionan, busca en Google Images.*

---

## ✅ VERIFICACIÓN

Una vez descargadas, verifica que se vean correctamente abriendo en el navegador:
- http://localhost/mauro-calzado/img/visa.png
- http://localhost/mauro-calzado/img/mastercard.png
- http://localhost/mauro-calzado/img/mercadopago.png

Si cargan correctamente, ¡listo!
