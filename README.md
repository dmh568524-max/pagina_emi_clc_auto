# EMILIANO CLC AUTO IMPORT 🚗💨

Sitio web moderno, ultra-rápido y responsive para **EMILIANO CLC AUTO IMPORT**, desarrollado en **JavaScript / HTML5 / CSS3** y optimizado para despliegue instantáneo en **Vercel**.

El inventario de vehículos se sincroniza en tiempo real consumiendo los endpoints públicos del sistema del dealer, sin necesidad de bases de datos locales ni servidores PHP.

---

## ⚡ Características Principales

- **Despliegue Serverless en Vercel**: 100% estático y basado en JavaScript (cero configuración de PHP o MySQL requerida).
- **Consumo de API en Tiempo Real**:
  - `GET /api/public/inventario?dealer={slug_o_id}`: Lista de vehículos, filtros disponibles, paginación y búsqueda.
  - `GET /api/public/inventario/{id}?dealer={slug_o_id}`: Galería en alta resolución, ficha técnica, WhatsApp directo y sugeridos.
- **Filtros Dinámicos**: Marca, modelo encadenado, tipo de carrocería, año mínimo, rango de precio y ordenamiento.
- **Buscador en Vivo**: Búsqueda por texto en marca, modelo y características.
- **Conversor de Moneda**: Alternancia instantánea entre **Dólares (USD $)** y **Pesos Dominicanos (RD$)** con la tasa configurada por el dealer.
- **Integración con WhatsApp**: Enlaces directos preconfigurados con el modelo y precio exacto del auto de interés.
- **Experiencia Visual**: Efecto holograma interactivo, video de fondo y diseño deportivo oscuro con toques rojos neón.

---

## 🔌 Conectar tu Endpoint de Inventario

La configuración principal se encuentra en el archivo [`js/config.js`](file:///home/dylan-herrnandez/Documentos/proyecto%20de%20dylan/pagina%20dealer%20emi/js/config.js):

```javascript
const APP_CONFIG = {
    // 1. Ingresa la URL donde está alojado tu backend
    API_BASE_URL: 'https://tudominio.com',
    
    // 2. Ingresa el slug o identificador de tu dealer
    DEALER_SLUG: 'emiliano-clc',

    // 3. (Opcional) Si tu API usa cabecera x-api-key
    API_KEY: ''
};
```

### Probar en Vivo con Parámetros de URL
Puedes probar con diferentes endpoints o dealers sin modificar el código agregando parámetros a la URL:
```
https://tu-pagina-en-vercel.app/inventory.html?dealer=demo&api_base=https://api.tudominio.com
```

---

## 🚀 Despliegue en Vercel

1. Ingresa a [Vercel](https://vercel.com/) e inicia sesión con tu cuenta de GitHub.
2. Haz clic en **Add New...** -> **Project**.
3. Selecciona el repositorio: `dmh568524-max/pagina_emi_clc_auto`.
4. Deja la configuración por defecto (Framework Preset: **Other**, Root Directory: `./`).
5. Presiona **Deploy**. ¡En pocos segundos tu sitio estará en línea a nivel mundial!

---

## 📁 Estructura del Proyecto

```text
├── index.html              # Página de inicio con marcas y vehículos destacados
├── inventory.html          # Catálogo completo con buscador, filtros y paginación
├── vehicle_details.html    # Ficha técnica, galería y contacto por vehículo
├── vercel.json             # Reglas de enrutamiento limpio y headers para Vercel
├── package.json            # Configuración para desarrollo local
├── css/
│   └── style.css           # Estilos completos
├── js/
│   ├── config.js           # URL base de API, slug de dealer y datos de contacto
│   ├── api.js              # Conexión con endpoints /api/public/inventario
│   ├── main.js             # Lógica del home y destacados
│   ├── inventory.js        # Lógica de filtros, búsqueda y catálogo
│   └── vehicle-details.js  # Lógica de ficha técnica y galería
├── imagenes/               # Logotipos y recursos visuales
└── uploads/                # Imágenes de respaldo
```
