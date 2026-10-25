/**
 * CONFIGURACIÓN DE CONEXIÓN CON EL ENDPOINT DEL DEALER
 * ===================================================
 * Configura aquí la URL base de tu backend y el slug/identificador de tu dealer.
 * 
 * También puedes sobrescribir estos valores temporalmente desde la consola o URL:
 * Ej: inventory.html?dealer=tu_slug&api_base=https://tu-api.com
 */

const APP_CONFIG = {
    // URL base de tu backend/API (donde está alojado el endpoint de inventario)
    // Cambia 'https://tudominio.com' por el dominio real de tu API cuando esté en producción
    API_BASE_URL: localStorage.getItem('dealer_api_base') || window.API_BASE_URL || 'https://tudominio.com',
    
    // Slug o ID del dealer registrado en el sistema
    DEALER_SLUG: localStorage.getItem('dealer_slug') || window.DEALER_SLUG || 'emiliano-clc',
    
    // Clave de API opcional (para peticiones que requieran x-api-key en cabecera)
    API_KEY: localStorage.getItem('dealer_api_key') || window.DEALER_API_KEY || '',

    // Datos por defecto del negocio en caso de que la API tarde o esté configurándose
    DEFAULT_DEALER: {
        nombre: 'EMILIANO CLC AUTO IMPORT',
        telefono: '809-828-1404',
        whatsapp: '18098281404',
        email: 'ventas@emilianoclc.com',
        direccion: 'Ubicados Sto. Dgo. Norte',
        ciudad: 'Santo Domingo',
        tasa_usd: 59.0
    },

    // Logos de marcas locales conocidas para visualización rápida
    BRAND_LOGOS: {
        'BMW': 'imagenes/logo_bmw.jpg',
        'Mercedes-Benz': 'imagenes/logo_mercedes.jpg',
        'Mercedes': 'imagenes/logo_mercedes.jpg',
        'Nissan': 'imagenes/logo_nissan.png',
        'Jeep': 'imagenes/jeep.jpg'
    }
};

// Permitir sobreescribir vía parámetros de URL para pruebas rápidas
(function checkUrlOverrides() {
    try {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('api_base')) {
            APP_CONFIG.API_BASE_URL = urlParams.get('api_base');
        }
        if (urlParams.has('dealer')) {
            APP_CONFIG.DEALER_SLUG = urlParams.get('dealer');
        }
        if (urlParams.has('api_key')) {
            APP_CONFIG.API_KEY = urlParams.get('api_key');
        }
    } catch (e) {
        // Ignorar en entornos sin window
    }
})();

// Exportar globalmente
window.APP_CONFIG = APP_CONFIG;
