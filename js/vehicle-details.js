/**
 * LÓGICA DE DETALLE DE VEHÍCULO
 * EMILIANO CLC AUTO IMPORT
 */

document.addEventListener("DOMContentLoaded", async () => {
    const params = new URLSearchParams(window.location.search);
    const vehicleId = params.get('id');

    const loadingContainer = document.getElementById("loadingDetailsContainer");
    const errorContainer = document.getElementById("errorDetailsContainer");
    const contentContainer = document.getElementById("carDetailsContent");

    // Actualizar año en footer
    const yearEl = document.getElementById('currentYear');
    if (yearEl) yearEl.textContent = new Date().getFullYear();

    if (!vehicleId) {
        showError("No se proporcionó un ID de vehículo.");
        return;
    }

    try {
        const car = await DealerAPI.getVehiculo(vehicleId);

        if (!car) {
            showError("El vehículo solicitado no fue encontrado.");
            return;
        }

        renderVehicleDetails(car);

    } catch (err) {
        console.error("Error al cargar detalles del auto:", err);
        showError("Hubo un problema al conectar con el servidor.");
    }

    function showError(message) {
        if (loadingContainer) loadingContainer.style.display = "none";
        if (contentContainer) contentContainer.style.display = "none";
        if (errorContainer) {
            errorContainer.style.display = "block";
            const p = errorContainer.querySelector("p");
            if (p && message) p.textContent = message;
        }
    }

    function renderVehicleDetails(car) {
        if (loadingContainer) loadingContainer.style.display = "none";
        if (contentContainer) contentContainer.style.display = "grid";

        // Título de la pestaña
        document.title = `${car.marca} ${car.modelo} ${car.anio} | EMILIANO CLC AUTO IMPORT`;

        // Galería
        const mainImg = document.getElementById("main-view-image");
        const thumbsContainer = document.getElementById("galleryThumbs");

        let galeria = [];
        if (Array.isArray(car.imagenes) && car.imagenes.length > 0) {
            galeria = [...car.imagenes];
        }
        if (car.imagen_principal && !galeria.includes(car.imagen_principal)) {
            galeria.unshift(car.imagen_principal);
        }
        if (galeria.length === 0) {
            galeria = [car.imagen_principal || 'imagenes/bmw.jpg'];
        }

        mainImg.src = galeria[0];
        mainImg.alt = `${car.marca} ${car.modelo}`;

        if (thumbsContainer) {
            thumbsContainer.innerHTML = "";
            if (galeria.length > 1) {
                galeria.forEach((imgUrl, idx) => {
                    const thumb = document.createElement("img");
                    thumb.src = imgUrl;
                    thumb.alt = `Foto ${idx + 1}`;
                    if (idx === 0) thumb.className = "active";

                    thumb.addEventListener("click", () => {
                        thumbsContainer.querySelectorAll("img").forEach(t => t.classList.remove("active"));
                        thumb.classList.add("active");
                        mainImg.src = imgUrl;
                    });

                    thumbsContainer.appendChild(thumb);
                });
            } else {
                thumbsContainer.style.display = "none";
            }
        }

        // Ficha Técnica
        document.getElementById("detailMarca").textContent = car.marca || '-';
        document.getElementById("detailModelo").textContent = car.modelo || '-';
        document.getElementById("detailAnio").textContent = car.anio || '-';
        document.getElementById("detailColor").textContent = car.color || 'No especificado';
        document.getElementById("detailTransmision").textContent = car.transmision || 'Automática';
        document.getElementById("detailCombustible").textContent = car.combustible || 'Gasolina';
        document.getElementById("detailKilometraje").textContent = `${Number(car.kilometraje || 0).toLocaleString()} km`;
        document.getElementById("detailTipo").textContent = car.tipo || 'Vehículo';

        const estadoEl = document.getElementById("detailEstado");
        if (estadoEl) {
            estadoEl.textContent = car.estado || 'Disponible';
            if (car.estado && car.estado.toLowerCase() === 'vendido') {
                estadoEl.style.color = '#e74c3c';
            } else {
                estadoEl.style.color = '#2ecc71';
            }
        }

        // Descripción
        const descEl = document.getElementById("detailDescripcion");
        if (descEl) {
            descEl.textContent = car.descripcion || "Vehículo importado certificado con garantía mecánica y revisión en 150 puntos. Listo para traspaso inmediato.";
        }

        // Sidebar
        document.getElementById("detailTitle").textContent = `${car.marca} ${car.modelo}`;
        document.getElementById("detailSubtitle").textContent = `Año ${car.anio} | ${car.transmision || 'Automática'}`;

        // Precios en USD y RD$
        const usdPrice = DealerAPI.formatPrice(car, 'USD');
        const rdPrice = DealerAPI.formatPrice(car, 'RD');
        const tasa = car.precios_calculados?.tasa_cambio || car.dealer?.tasa_usd || 59.0;

        document.getElementById("detailPriceUSD").textContent = usdPrice;
        document.getElementById("detailPriceRD").textContent = rdPrice;
        document.getElementById("detailRateTag").textContent = `Tasa de cambio: 1 USD = RD$ ${Number(tasa).toFixed(2)}`;

        // WhatsApp directo
        const waBtn = document.getElementById("detailWaBtn");
        if (waBtn) {
            // Si la API devuelve un link pregenerado lo usamos, de lo contrario usamos nuestro builder
            if (car.whatsapp_url) {
                waBtn.href = car.whatsapp_url;
            } else {
                waBtn.href = DealerAPI.buildWhatsAppUrl(car);
            }
        }

        // Teléfono
        const phoneBtn = document.getElementById("detailPhoneBtn");
        const dealerPhone = car.dealer?.telefono || APP_CONFIG.DEFAULT_DEALER.telefono;
        if (phoneBtn) {
            phoneBtn.href = `tel:${dealerPhone.replace(/\D/g, '')}`;
            phoneBtn.innerHTML = `<i class="fa fa-phone"></i> O llámanos al ${dealerPhone}`;
        }

        // Vehículos Relacionados (si el endpoint los devuelve)
        renderRelatedCars(car.vehiculos_relacionados || car.relacionados || []);
    }

    function renderRelatedCars(relatedList) {
        const relatedSection = document.getElementById("relatedCarsSection");
        const relatedGrid = document.getElementById("relatedCarsGrid");

        if (!relatedList || relatedList.length === 0) {
            if (relatedSection) relatedSection.style.display = "none";
            return;
        }

        if (relatedSection) relatedSection.style.display = "block";
        if (relatedGrid) {
            relatedGrid.innerHTML = "";

            relatedList.slice(0, 3).forEach(rel => {
                const card = document.createElement("div");
                card.className = "vehicle-card";

                const imgSrc = rel.imagen_principal || (rel.imagenes && rel.imagenes[0]) || 'imagenes/bmw.jpg';
                const formattedPrice = DealerAPI.formatPrice(rel, 'USD');

                card.innerHTML = `
                    <div class="vehicle-img-wrapper">
                        <span class="vehicle-tag">Sugerido</span>
                        <img src="${imgSrc}" alt="${rel.marca} ${rel.modelo}" onerror="this.src='imagenes/bmw.jpg'">
                    </div>
                    <div class="vehicle-info">
                        <h3 class="vehicle-title">${rel.marca} ${rel.modelo}</h3>
                        <div class="vehicle-specs">
                            <div class="spec-item"><i class="fa fa-calendar-alt"></i> ${rel.anio}</div>
                            <div class="spec-item"><i class="fa fa-cogs"></i> ${rel.transmision || 'Auto'}</div>
                            <div class="spec-item"><i class="fa fa-gas-pump"></i> ${rel.combustible || 'Gasolina'}</div>
                        </div>
                        <div class="vehicle-footer">
                            <span class="vehicle-price">${formattedPrice}</span>
                            <a href="vehicle_details.html?id=${rel.id}" class="btn-card">Detalles</a>
                        </div>
                    </div>
                `;

                relatedGrid.appendChild(card);
            });
        }
    }
});
