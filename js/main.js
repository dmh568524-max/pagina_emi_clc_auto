/**
 * LÓGICA DE LA PÁGINA DE INICIO (INDEX)
 * EMILIANO CLC AUTO IMPORT
 */

document.addEventListener("DOMContentLoaded", async () => {
    
    // 1. Actualizar año y datos de contacto en Navbar y Footer
    updateDealerContactInfo();

    // 2. Efecto de scroll en video del hero
    setupHeroVideoScroll();

    // 3. Cargar datos del inventario para Marcas y Vehículos Destacados
    await loadHomeInventoryData();
});

function updateDealerContactInfo(dealerData = null) {
    const dealer = dealerData || APP_CONFIG.DEFAULT_DEALER;

    // Año actual
    const yearEl = document.getElementById('currentYear');
    if (yearEl) yearEl.textContent = new Date().getFullYear();

    // Enlaces de WhatsApp en navbar y hero
    const navWaBtn = document.getElementById('navWaBtn');
    const heroChatBtn = document.getElementById('heroChatBtn');
    const cleanPhone = (dealer.whatsapp || dealer.telefono || '18098281404').replace(/\D/g, '');
    const defaultWaUrl = `https://wa.me/${cleanPhone}?text=${encodeURIComponent('Hola EMILIANO CLC AUTO IMPORT, quiero consultar sobre los vehículos disponibles.')}`;

    if (navWaBtn) navWaBtn.href = defaultWaUrl;
    if (heroChatBtn) heroChatBtn.href = defaultWaUrl;

    // Footer
    const footerPhone = document.getElementById('footerPhone');
    const footerEmail = document.getElementById('footerEmail');
    const footerAddress = document.getElementById('footerAddress');
    const footerDealerName = document.getElementById('footerDealerName');

    if (footerPhone) footerPhone.textContent = dealer.telefono;
    if (footerEmail) footerEmail.textContent = dealer.email;
    if (footerAddress) footerAddress.textContent = `${dealer.direccion}, ${dealer.ciudad}`;
    if (footerDealerName) footerDealerName.textContent = dealer.nombre;
}

function setupHeroVideoScroll() {
    const heroVideo = document.querySelector(".hero-video");
    if (heroVideo) {
        window.addEventListener("scroll", () => {
            const scrollVal = window.scrollY;
            if (scrollVal < 600) {
                heroVideo.style.transform = `translateY(${scrollVal * 0.15}px)`;
            }
        }, { passive: true });
    }
}

async function loadHomeInventoryData() {
    const tickerContainer = document.getElementById("brandsTickerContainer");
    const thumbnailsRow = document.getElementById("featuredThumbnailsRow");

    try {
        // Consultar el endpoint con límite suficiente para marcas y destacados
        const data = await DealerAPI.getInventario({ limit: 20 });
        
        if (data.dealer) {
            updateDealerContactInfo(data.dealer);
        }

        const vehiculos = data.vehiculos || [];
        const filtros = data.filtros_disponibles || {};

        // Renderizar sección de Marcas
        renderBrandsTicker(vehiculos, filtros.marcas || []);

        // Renderizar sección de Destacados
        if (vehiculos.length > 0) {
            renderFeaturedSection(vehiculos.slice(0, 4));
        } else {
            thumbnailsRow.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; border: 1px dashed var(--border-color); border-radius: 8px; width: 100%;">
                    <i class="fa fa-car-side" style="font-size: 3rem; color: var(--red-primary); margin-bottom: 15px;"></i>
                    <h3>No hay vehículos disponibles en este momento</h3>
                    <p style="color: var(--text-gray); margin-top: 10px;">Consulta pronto para nuevas llegadas a nuestro inventario.</p>
                </div>
            `;
        }

    } catch (err) {
        console.error("Error al cargar inventario para el home:", err);
    }
}

/**
 * Renderiza el carrusel/ticker de marcas y sus modelos en stock
 */
function renderBrandsTicker(vehiculos, marcasApi) {
    const tickerContainer = document.getElementById("brandsTickerContainer");
    if (!tickerContainer) return;

    // Agrupar modelos por marca
    const brandModelsMap = {};
    const defaultBrands = ['Ford', 'Chevrolet', 'Dodge', 'Jeep', 'Tesla', 'RAM', 'BMW', 'Mercedes-Benz', 'Nissan'];

    // Inicializar marcas por defecto
    defaultBrands.forEach(b => brandModelsMap[b] = []);

    // Agregar marcas provenientes de la API si las hay
    marcasApi.forEach(b => {
        if (!brandModelsMap[b]) brandModelsMap[b] = [];
    });

    // Rellenar con los vehículos reales disponibles
    vehiculos.forEach(v => {
        if (!v.marca) return;
        let marca = v.marca.trim();
        if (marca.toLowerCase() === 'mercedes' || marca.toLowerCase() === 'mercedes benz') {
            marca = 'Mercedes-Benz';
        }
        if (!brandModelsMap[marca]) brandModelsMap[marca] = [];
        if (v.modelo && !brandModelsMap[marca].includes(v.modelo)) {
            brandModelsMap[marca].push(v.modelo);
        }
    });

    tickerContainer.innerHTML = "";

    Object.keys(brandModelsMap).forEach(brandName => {
        const models = brandModelsMap[brandName];
        const card = document.createElement("div");
        card.className = "brand-ticker-card";
        card.style.display = "flex";
        card.style.flexDirection = "column";
        card.style.alignItems = "center";
        card.style.justifyContent = "center";
        card.style.minHeight = "80px";
        card.style.cursor = "pointer";

        card.addEventListener("click", () => {
            window.location.href = `inventory.html?marca=${encodeURIComponent(brandName)}`;
        });

        const logoSrc = APP_CONFIG.BRAND_LOGOS[brandName];
        let brandVisual = '';
        if (logoSrc) {
            brandVisual = `
                <div style="height: 45px; display: flex; align-items: center; justify-content: center;">
                    <img src="${logoSrc}" alt="${brandName}" style="max-height: 45px; max-width: 120px; object-fit: contain; border-radius: 4px;">
                </div>
            `;
        } else {
            brandVisual = `<h4>${brandName}</h4>`;
        }

        let modelsHtml = '';
        if (models.length > 0) {
            modelsHtml = `
                <ul>
                    ${models.map(m => `<li><i class="fa fa-check-circle"></i> ${m}</li>`).join('')}
                </ul>
            `;
        } else {
            modelsHtml = `
                <div class="no-stock">
                    Sin stock actualmente.<br>
                    <span style="font-size: 0.75rem; color: var(--red-primary); font-weight: 700;">¡Haz clic para encargar a pedido!</span>
                </div>
            `;
        }

        card.innerHTML = `
            ${brandVisual}
            <div class="brand-tooltip">
                <h5>Modelos Disponibles</h5>
                ${modelsHtml}
            </div>
        `;

        tickerContainer.appendChild(card);
    });
}

/**
 * Renderiza la sección interactiva de vehículos destacados
 */
function renderFeaturedSection(cars) {
    const thumbnailsRow = document.getElementById("featuredThumbnailsRow");
    const hologramBg = document.getElementById("hologramBg");
    const categories = ["Más Vendido", "Calidad - Precio", "Confort & Velocidad", "Estética & Diseño"];

    if (!thumbnailsRow || cars.length === 0) return;

    thumbnailsRow.innerHTML = "";

    cars.forEach((car, index) => {
        const catName = categories[index % categories.length];
        const card = document.createElement("div");
        card.className = `small-featured-card ${index === 0 ? 'active' : ''}`;

        const imgSrc = car.imagen_principal || (car.imagenes && car.imagenes[0]) || 'imagenes/bmw.jpg';
        const priceUsd = DealerAPI.formatPrice(car, 'USD');

        card.innerHTML = `
            <img src="${imgSrc}" alt="${car.marca} ${car.modelo}">
            <div class="small-featured-card-info">
                <span class="category">${catName}</span>
                <h4>${car.marca} ${car.modelo}</h4>
                <div class="price">${priceUsd}</div>
            </div>
        `;

        // Efecto holograma estilo La Velada al pasar el cursor
        card.addEventListener("mouseenter", () => {
            if (hologramBg) {
                hologramBg.style.backgroundImage = `url('${imgSrc}')`;
                hologramBg.classList.add("active");
            }
        });

        card.addEventListener("mouseleave", () => {
            if (hologramBg) {
                hologramBg.classList.remove("active");
            }
        });

        // Al hacer clic, actualizar la vista ampliada superior
        card.addEventListener("click", () => {
            thumbnailsRow.querySelectorAll(".small-featured-card").forEach(c => c.classList.remove("active"));
            card.classList.add("active");
            updateExpandedView(car, catName);
        });

        thumbnailsRow.appendChild(card);
    });

    // Inicializar la vista ampliada con el primer vehículo
    updateExpandedView(cars[0], categories[0]);
}

/**
 * Actualiza los elementos del cuadro ampliado superior
 */
function updateExpandedView(car, categoryName) {
    const expandedBox = document.getElementById("expandedViewBox");
    const modalMainImg = document.getElementById("modalMainImg");
    const modalThumbs = document.getElementById("modalThumbs");
    const modalCategory = document.getElementById("modalCategory");
    const modalTitle = document.getElementById("modalTitle");
    const modalSpecsShort = document.getElementById("modalSpecsShort");
    const modalPrice = document.getElementById("modalPrice");
    const modalTrans = document.getElementById("modalTrans");
    const modalGas = document.getElementById("modalGas");
    const modalKm = document.getElementById("modalKm");
    const modalType = document.getElementById("modalType");
    const modalDesc = document.getElementById("modalDesc");
    const modalWaBtn = document.getElementById("modalWaBtn");
    const modalDetailsBtn = document.getElementById("modalDetailsBtn");
    const modalVideoBox = document.getElementById("modalVideoBox");
    const modalVideoWrapper = document.getElementById("modalVideoWrapper");

    if (expandedBox) {
        expandedBox.style.opacity = "0.5";
        expandedBox.style.transform = "scale(0.98)";
    }

    setTimeout(() => {
        const mainImgSrc = car.imagen_principal || (car.imagenes && car.imagenes[0]) || 'imagenes/bmw.jpg';
        if (modalMainImg) modalMainImg.src = mainImgSrc;
        if (modalCategory) modalCategory.innerText = categoryName;
        if (modalTitle) modalTitle.innerText = `${car.marca} ${car.modelo}`;
        if (modalSpecsShort) modalSpecsShort.innerText = `Año ${car.anio} | ${car.transmision || 'Automática'}`;
        
        // Precio dual formateado
        const priceUsd = DealerAPI.formatPrice(car, 'USD');
        const priceRd = DealerAPI.formatPrice(car, 'RD');
        if (modalPrice) {
            modalPrice.innerHTML = `${priceUsd} <span style="font-size: 1.1rem; color: var(--text-gray); font-weight: 500;">(${priceRd})</span>`;
        }

        if (modalTrans) modalTrans.innerText = car.transmision || 'Automática';
        if (modalGas) modalGas.innerText = car.combustible || 'Gasolina';
        if (modalKm) modalKm.innerText = `${Number(car.kilometraje || 0).toLocaleString()} km`;
        if (modalType) modalType.innerText = car.tipo || 'Vehículo';
        if (modalDesc) modalDesc.innerText = car.descripcion || "Vehículo certificado con inspección técnica y garantía de entrega.";

        // WhatsApp preconfigurado
        if (modalWaBtn) {
            modalWaBtn.href = DealerAPI.buildWhatsAppUrl(car);
        }

        // Enlace a la página de detalles
        if (modalDetailsBtn) {
            modalDetailsBtn.href = `vehicle_details.html?id=${car.id}`;
        }

        // Miniaturas de galería
        if (modalThumbs) {
            modalThumbs.innerHTML = "";
            let galeria = Array.isArray(car.imagenes) ? [...car.imagenes] : [];
            if (car.imagen_principal && !galeria.includes(car.imagen_principal)) {
                galeria.unshift(car.imagen_principal);
            }
            if (galeria.length === 0) galeria = [mainImgSrc];

            galeria.forEach((imgSrc, idx) => {
                const thumb = document.createElement("img");
                thumb.src = imgSrc;
                thumb.alt = `Miniatura ${idx + 1}`;
                if (idx === 0) thumb.classList.add("active");

                thumb.addEventListener("click", () => {
                    modalThumbs.querySelectorAll("img").forEach(t => t.classList.remove("active"));
                    thumb.classList.add("active");
                    if (modalMainImg) modalMainImg.src = imgSrc;
                });

                modalThumbs.appendChild(thumb);
            });
        }

        // Video
        if (modalVideoWrapper && modalVideoBox) {
            if (car.video_url) {
                modalVideoBox.style.display = "block";
                const videoUrl = car.video_url;
                if (videoUrl.includes("youtube.com") || videoUrl.includes("youtu.be")) {
                    let videoId = "";
                    if (videoUrl.includes("v=")) {
                        videoId = videoUrl.split("v=")[1].split("&")[0];
                    } else if (videoUrl.includes("youtu.be/")) {
                        videoId = videoUrl.split("youtu.be/")[1].split("?")[0];
                    }
                    modalVideoWrapper.innerHTML = `<iframe src="https://www.youtube.com/embed/${videoId}?autoplay=0&mute=1" allow="encrypted-media" allowfullscreen></iframe>`;
                } else {
                    modalVideoWrapper.innerHTML = `<video src="${videoUrl}" loop muted controls></video>`;
                }
            } else {
                modalVideoBox.style.display = "none";
            }
        }

        if (expandedBox) {
            expandedBox.style.opacity = "1";
            expandedBox.style.transform = "scale(1)";
        }
    }, 180);
}
