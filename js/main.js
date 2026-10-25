// Funcionalidad interactiva de EMILIANO CLC AUTO IMPORT

document.addEventListener("DOMContentLoaded", () => {
    
    // 1. Cambio de imágenes en la galería de detalles de vehículo
    const thumbImages = document.querySelectorAll(".gallery-thumbs img");
    const mainImage = document.querySelector(".gallery-main img");

    if (thumbImages.length > 0 && mainImage) {
        thumbImages.forEach(thumb => {
            thumb.addEventListener("click", function() {
                // Quitar clase activa de todas las miniaturas
                thumbImages.forEach(t => t.classList.remove("active"));
                // Añadir clase activa a la actual
                this.classList.add("active");
                // Cambiar el src del banner principal
                mainImage.src = this.src;
            });
        });
    }

    // 2. Efecto de hover interactivo en tarjetas de vehículos
    const cards = document.querySelectorAll(".vehicle-card");
    cards.forEach(card => {
        card.addEventListener("mouseenter", () => {
            card.style.boxShadow = "0 15px 30px rgba(229, 9, 20, 0.25)";
        });
        card.addEventListener("mouseleave", () => {
            card.style.boxShadow = "none";
        });
    });

    // 3. Modificación interactiva del video de fondo en el home si el usuario hace scroll
    const heroVideo = document.querySelector(".hero-video");
    if (heroVideo) {
        window.addEventListener("scroll", () => {
            let scrollVal = window.scrollY;
            if (scrollVal < 600) {
                heroVideo.style.transform = `translateY(${scrollVal * 0.15}px)`;
            }
        });
    }

    // 4. Interactividad de la sección "Destacados" (Fila inferior controla vista ampliada superior)
    const smallCards = document.querySelectorAll(".small-featured-card");
    const hologramBg = document.getElementById("hologramBg");

    if (smallCards.length > 0) {
        // Elementos de la vista ampliada (que actualizaremos dinámicamente)
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
        const modalVideoBox = document.getElementById("modalVideoBox");
        const modalVideoWrapper = document.getElementById("modalVideoWrapper");

        smallCards.forEach(card => {
            const carData = JSON.parse(card.getAttribute("data-car"));

            // EFECTO HOLOGRAMA ESTILO LA VELADA AL PASAR EL MOUSE (Hover)
            card.addEventListener("mouseenter", () => {
                if (hologramBg) {
                    hologramBg.style.backgroundImage = `url('${carData.imagen_principal}')`;
                    hologramBg.classList.add("active");
                }
            });

            card.addEventListener("mouseleave", () => {
                if (hologramBg) {
                    hologramBg.classList.remove("active");
                }
            });

            // ACCIÓN AL SELECCIONAR (Hacer clic en la tarjeta pequeña)
            card.addEventListener("click", () => {
                // Cambiar estado activo en la fila de tarjetas pequeñas
                smallCards.forEach(c => c.classList.remove("active"));
                card.classList.add("active");

                // Animación de salida-entrada para la vista ampliada superior
                const expandedBox = document.getElementById("expandedViewBox");
                if (expandedBox) {
                    expandedBox.style.opacity = "0.5";
                    expandedBox.style.transform = "scale(0.98)";
                }

                setTimeout(() => {
                    // Actualizar datos de texto
                    modalMainImg.src = carData.imagen_principal;
                    modalCategory.innerText = carData.categoria;
                    modalTitle.innerText = `${carData.marca} ${carData.modelo}`;
                    modalSpecsShort.innerText = `Año ${carData.anio} | ${carData.transmision}`;
                    modalPrice.innerText = `$${carData.precio}`;
                    
                    modalTrans.innerText = carData.transmision;
                    modalGas.innerText = carData.combustible;
                    modalKm.innerText = `${carData.kilometraje} km`;
                    modalType.innerText = carData.tipo;
                    modalDesc.innerText = carData.descripcion ? carData.descripcion : "No hay especificaciones adicionales escritas por el administrador.";

                    // Actualizar miniaturas de la galería
                    modalThumbs.innerHTML = "";
                    if (carData.galeria && carData.galeria.length > 0) {
                        carData.galeria.forEach((imgSrc, index) => {
                            const thumb = document.createElement("img");
                            thumb.src = imgSrc;
                            thumb.alt = `Miniatura ${index + 1}`;
                            if (index === 0) thumb.classList.add("active");
                            
                            thumb.addEventListener("click", () => {
                                modalThumbs.querySelectorAll("img").forEach(t => t.classList.remove("active"));
                                thumb.classList.add("active");
                                modalMainImg.src = imgSrc;
                            });
                            
                            modalThumbs.appendChild(thumb);
                        });
                    }

                    // Actualizar reproductor de video
                    modalVideoWrapper.innerHTML = "";
                    if (carData.video_url) {
                        modalVideoBox.style.display = "block";
                        let videoUrl = carData.video_url;

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
                        modalVideoBox.style.display = "block";
                        modalVideoWrapper.innerHTML = `<video src="https://player.vimeo.com/external/371433846.sd.mp4?s=236da2f3c0227e36f111867c29fb1e5b38ed6a94&profile_id=139&oauth2_token_id=57447761" loop muted controls></video>`;
                    }

                    // Actualizar enlace WhatsApp
                    const waText = `Hola EMILIANO CLC AUTO IMPORT, estoy interesado en el vehículo destacado [${carData.marca} ${carData.modelo} ${carData.anio}] por valor de $${carData.precio}. ¿Podrían darme más detalles?`;
                    modalWaBtn.href = `https://wa.me/18290000000?text=${encodeURIComponent(waText)}`;

                    if (expandedBox) {
                        expandedBox.style.opacity = "1";
                        expandedBox.style.transform = "scale(1)";
                    }
                }, 200);
            });
        });
    }
});
