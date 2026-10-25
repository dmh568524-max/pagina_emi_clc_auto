/**
 * CLIENTE API DE INVENTARIO - EMILIANO CLC AUTO IMPORT
 * ====================================================
 * Consume los endpoints públicos del dealer según la especificación:
 * - GET /api/public/inventario?dealer={slug}
 * - GET /api/public/inventario/{id}?dealer={slug}
 */

// Datos de demostración y respaldo por si la URL configurada aún está en desarrollo
const MOCK_DATA = {
    success: true,
    dealer: {
        nombre: "EMILIANO CLC AUTO IMPORT",
        telefono: "809-828-1404",
        whatsapp: "18098281404",
        email: "ventas@emilianoclc.com",
        direccion: "Ubicados Sto. Dgo. Norte",
        ciudad: "Santo Domingo",
        logo: "imagenes/logo.png",
        tasa_usd: 59.0
    },
    total: 4,
    pagina: 1,
    total_paginas: 1,
    filtros_disponibles: {
        marcas: ["BMW", "Mercedes-Benz", "Jeep", "Nissan"],
        tipos: ["Jeepeta / SUV", "Sedán", "Coupé"],
        anios: [2024, 2023, 2022, 2021]
    },
    vehiculos: [
        {
            id: 1,
            marca: "BMW",
            modelo: "Serie 4 M-Sport",
            anio: 2023,
            color: "Gris Nardo",
            tipo: "Coupé",
            transmision: "Automática Secuencial",
            combustible: "Gasolina Turbo",
            kilometraje: 18500,
            precio_venta: 2750000,
            moneda: "RD$",
            precios_calculados: {
                precio_rd: 2750000,
                precio_usd: 46610.17,
                tasa_cambio: 59.0
            },
            estado: "disponible",
            destacado: true,
            imagen_principal: "imagenes/bmw.jpg",
            total_imagenes: 3,
            imagenes: ["imagenes/bmw.jpg", "uploads/1787285373_images.jpg", "imagenes/coche-rojo-negro-logotipo-compania_636116-436.avif"],
            descripcion: "Impecable BMW Serie 4 con paquete aerodinámico M-Sport, interior en piel roja, sistema de audio Harman Kardon y tecnología de conducción asistida.",
            video_url: ""
        },
        {
            id: 2,
            marca: "Mercedes-Benz",
            modelo: "GLE 450 AMG Line",
            anio: 2024,
            color: "Negro Obsidiana",
            tipo: "Jeepeta / SUV",
            transmision: "Automática 9G-Tronic",
            combustible: "Gasolina / Mild-Hybrid",
            kilometraje: 12000,
            precio_venta: 4890000,
            moneda: "RD$",
            precios_calculados: {
                precio_rd: 4890000,
                precio_usd: 82881.36,
                tasa_cambio: 59.0
            },
            estado: "disponible",
            destacado: true,
            imagen_principal: "imagenes/mercedez.jpg",
            total_imagenes: 3,
            imagenes: ["imagenes/mercedez.jpg", "imagenes/mercedezs.jpg", "uploads/1787884383_gallery_edit_1_images.jpg"],
            descripcion: "Lujosa SUV GLE 450 con suspensión neumática AirMatic, techo panorámico corredizo, doble pantalla digital MBUX y tracción 4MATIC integral.",
            video_url: ""
        },
        {
            id: 3,
            marca: "Jeep",
            modelo: "Wrangler Rubicon 4x4",
            anio: 2022,
            color: "Rojo Fuego",
            tipo: "Jeepeta / SUV",
            transmision: "Automática 8 Velocidades",
            combustible: "Gasolina",
            kilometraje: 28000,
            precio_venta: 2450000,
            moneda: "RD$",
            precios_calculados: {
                precio_rd: 2450000,
                precio_usd: 41525.42,
                tasa_cambio: 59.0
            },
            estado: "disponible",
            destacado: true,
            imagen_principal: "imagenes/jeep.jpg",
            total_imagenes: 2,
            imagenes: ["imagenes/jeep.jpg", "uploads/1787884383_gallery_edit_2_imaghgf.jpg"],
            descripcion: "La máxima experiencia todoterreno. Jeep Wrangler Rubicon con bloqueo de diferencial Tru-Lok, barra estabilizadora desconectable y neumáticos off-road nuevos.",
            video_url: ""
        },
        {
            id: 4,
            marca: "Nissan",
            modelo: "Patrol Platinum V8",
            anio: 2023,
            color: "Blanco Perla",
            tipo: "Jeepeta / SUV",
            transmision: "Automática",
            combustible: "Gasolina",
            kilometraje: 22000,
            precio_venta: 3950000,
            moneda: "RD$",
            precios_calculados: {
                precio_rd: 3950000,
                precio_usd: 66949.15,
                tasa_cambio: 59.0
            },
            estado: "disponible",
            destacado: true,
            imagen_principal: "imagenes/nissan.png",
            total_imagenes: 2,
            imagenes: ["imagenes/nissan.png", "uploads/1787884383_gallery_edit_0_coche-rojo-negro-logotipo-compania_636116-436.avif"],
            descripcion: "Potencia, prestigio y espacio para 7 pasajeros. Nissan Patrol Platinum con motor V8 5.6L, asientos de cuero ventilados y cámaras 360 grados.",
            video_url: ""
        }
    ]
};

const DealerAPI = {
    /**
     * Construye la URL base respetando parámetros y slash final
     */
    getBaseEndpoint(path) {
        let base = APP_CONFIG.API_BASE_URL.trim();
        if (base.endsWith('/')) {
            base = base.slice(0, -1);
        }
        return `${base}${path}`;
    },

    /**
     * Obtiene el listado de vehículos disponibles con filtros y paginación
     * Endpoint: GET /api/public/inventario?dealer={slug}
     */
    async getInventario(filters = {}) {
        const queryParams = new URLSearchParams();
        
        // Identificador del dealer
        queryParams.set('dealer', APP_CONFIG.DEALER_SLUG);
        
        // Filtros soportados según documentación
        if (filters.estado) queryParams.set('estado', filters.estado);
        if (filters.marca) queryParams.set('marca', filters.marca);
        if (filters.modelo) queryParams.set('modelo', filters.modelo);
        if (filters.tipo) queryParams.set('tipo', filters.tipo);
        if (filters.anio_min) queryParams.set('anio_min', filters.anio_min);
        if (filters.anio_max) queryParams.set('anio_max', filters.anio_max);
        if (filters.precio_min) queryParams.set('precio_min', filters.precio_min);
        if (filters.precio_max) queryParams.set('precio_max', filters.precio_max);
        if (filters.orden) queryParams.set('orden', filters.orden);
        if (filters.limit) queryParams.set('limit', filters.limit);
        if (filters.page) queryParams.set('page', filters.page);
        if (filters.q || filters.buscar) queryParams.set('q', filters.q || filters.buscar);

        const url = `${this.getBaseEndpoint('/api/public/inventario')}?${queryParams.toString()}`;

        const headers = { 'Accept': 'application/json' };
        if (APP_CONFIG.API_KEY) {
            headers['x-api-key'] = APP_CONFIG.API_KEY;
        }

        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 8000);

            const response = await fetch(url, {
                method: 'GET',
                headers: headers,
                signal: controller.signal
            });
            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error(`Error en servidor (${response.status})`);
            }

            const data = await response.json();
            if (data && data.success) {
                return data;
            } else {
                throw new Error(data.message || 'Respuesta sin éxito');
            }
        } catch (error) {
            console.warn(`[DealerAPI] Conexión en vivo con "${url}" no disponible (${error.message}). Utilizando catálogo local de respaldo:`, error);
            return this.getMockFilteredInventario(filters);
        }
    },

    /**
     * Obtiene el detalle de un vehículo individual
     * Endpoint: GET /api/public/inventario/{id}?dealer={slug}
     */
    async getVehiculo(id) {
        if (!id) return null;

        const queryParams = new URLSearchParams();
        queryParams.set('dealer', APP_CONFIG.DEALER_SLUG);

        const url = `${this.getBaseEndpoint(`/api/public/inventario/${id}`)}?${queryParams.toString()}`;

        const headers = { 'Accept': 'application/json' };
        if (APP_CONFIG.API_KEY) {
            headers['x-api-key'] = APP_CONFIG.API_KEY;
        }

        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 8000);

            const response = await fetch(url, {
                method: 'GET',
                headers: headers,
                signal: controller.signal
            });
            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error(`Error en servidor (${response.status})`);
            }

            const data = await response.json();
            if (data && (data.success || data.vehiculo)) {
                return data.vehiculo || data;
            } else {
                throw new Error(data.message || 'No se encontró el vehículo');
            }
        } catch (error) {
            console.warn(`[DealerAPI] Consulta en vivo de vehículo #${id} no disponible. Utilizando datos locales:`, error);
            const found = MOCK_DATA.vehiculos.find(v => String(v.id) === String(id));
            if (found) {
                return {
                    ...found,
                    dealer: MOCK_DATA.dealer,
                    vehiculos_relacionados: MOCK_DATA.vehiculos.filter(v => String(v.id) !== String(id)).slice(0, 3)
                };
            }
            return null;
        }
    },

    /**
     * Filtra el catálogo local de respaldo si la API externa aún no está conectada
     */
    getMockFilteredInventario(filters = {}) {
        let list = [...MOCK_DATA.vehiculos];

        if (filters.marca) {
            list = list.filter(v => v.marca.toLowerCase() === filters.marca.toLowerCase());
        }
        if (filters.modelo) {
            list = list.filter(v => v.modelo.toLowerCase().includes(filters.modelo.toLowerCase()));
        }
        if (filters.tipo) {
            list = list.filter(v => v.tipo.toLowerCase() === filters.tipo.toLowerCase());
        }
        if (filters.anio_min) {
            list = list.filter(v => v.anio >= parseInt(filters.anio_min));
        }
        if (filters.anio_max) {
            list = list.filter(v => v.anio <= parseInt(filters.anio_max));
        }
        if (filters.precio_min) {
            list = list.filter(v => (v.precios_calculados?.precio_usd || v.precio_venta) >= parseFloat(filters.precio_min));
        }
        if (filters.precio_max) {
            list = list.filter(v => (v.precios_calculados?.precio_usd || v.precio_venta) <= parseFloat(filters.precio_max));
        }
        if (filters.q) {
            const term = filters.q.toLowerCase();
            list = list.filter(v => 
                v.marca.toLowerCase().includes(term) ||
                v.modelo.toLowerCase().includes(term) ||
                (v.descripcion && v.descripcion.toLowerCase().includes(term))
            );
        }

        // Ordenamiento
        if (filters.orden === 'precio_asc') {
            list.sort((a, b) => (a.precios_calculados?.precio_usd || a.precio_venta) - (b.precios_calculados?.precio_usd || b.precio_venta));
        } else if (filters.orden === 'precio_desc') {
            list.sort((a, b) => (b.precios_calculados?.precio_usd || b.precio_venta) - (a.precios_calculados?.precio_usd || a.precio_venta));
        } else if (filters.orden === 'anio_desc') {
            list.sort((a, b) => b.anio - a.anio);
        } else if (filters.orden === 'kilometraje_asc') {
            list.sort((a, b) => a.kilometraje - b.kilometraje);
        }

        const limit = filters.limit ? parseInt(filters.limit) : 12;
        const page = filters.page ? parseInt(filters.page) : 1;
        const total = list.length;
        const total_paginas = Math.ceil(total / limit) || 1;
        const paginated = list.slice((page - 1) * limit, page * limit);

        return {
            success: true,
            dealer: MOCK_DATA.dealer,
            total: total,
            pagina: page,
            total_paginas: total_paginas,
            filtros_disponibles: MOCK_DATA.filtros_disponibles,
            vehiculos: paginated,
            es_mock: true
        };
    },

    /**
     * Formateador de precios en USD y RD$
     */
    formatPrice(car, preferredCurrency = 'USD') {
        if (!car) return '$0.00';
        
        const calc = car.precios_calculados;
        if (preferredCurrency === 'RD' || preferredCurrency === 'RD$') {
            const rd = calc?.precio_rd || car.precio_venta || 0;
            return `RD$ ${Number(rd).toLocaleString('es-DO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
        }
        
        // USD por defecto
        const usd = calc?.precio_usd || (car.moneda === 'USD' ? car.precio_venta : Math.round((car.precio_venta || 0) / 59));
        return `$${Number(usd).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    },

    /**
     * Construye el enlace de WhatsApp preconfigurado
     */
    buildWhatsAppUrl(car, phone) {
        const tel = (phone || APP_CONFIG.DEFAULT_DEALER.whatsapp || '18098281404').replace(/\D/g, '');
        let text = `Hola EMILIANO CLC AUTO IMPORT, estoy interesado en recibir información sobre su inventario.`;
        
        if (car) {
            const precio = this.formatPrice(car, 'USD');
            text = `Hola EMILIANO CLC AUTO IMPORT, estoy interesado en el vehículo ${car.marca} ${car.modelo} ${car.anio} anunciado por ${precio}. ¿Sigue disponible?`;
        }

        return `https://wa.me/${tel}?text=${encodeURIComponent(text)}`;
    }
};

window.DealerAPI = DealerAPI;
