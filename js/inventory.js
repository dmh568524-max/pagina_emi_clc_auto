/**
 * LÓGICA DE LA PÁGINA DE INVENTARIO
 * EMILIANO CLC AUTO IMPORT
 */

document.addEventListener("DOMContentLoaded", () => {
    const InventoryApp = {
        state: {
            page: 1,
            limit: 9,
            currency: localStorage.getItem('preferred_currency') || 'USD',
            filters: {},
            modelosPorMarca: {},
            totalPaginas: 1,
            totalVehiculos: 0
        },

        init() {
            this.cacheDom();
            this.readUrlParams();
            this.bindEvents();
            this.setupCurrencyButtons();
            this.fetchData();
        },

        cacheDom() {
            this.form = document.getElementById("filterForm");
            this.marcaSelect = document.getElementById("marca");
            this.modeloSelect = document.getElementById("modelo");
            this.tipoSelect = document.getElementById("tipo");
            this.anioSelect = document.getElementById("anio_min");
            this.precioMinInput = document.getElementById("precio_min");
            this.precioMaxInput = document.getElementById("precio_max");
            this.ordenSelect = document.getElementById("orden");
            this.qInput = document.getElementById("q");
            this.resetBtn = document.getElementById("resetFiltersBtn");
            this.grid = document.getElementById("vehiclesGrid");
            this.resultsSummary = document.getElementById("resultsSummary");
            this.paginationContainer = document.getElementById("paginationContainer");
            this.btnCurrUSD = document.getElementById("btnCurrUSD");
            this.btnCurrRD = document.getElementById("btnCurrRD");
            
            // Footer & Navbar
            const yearEl = document.getElementById('currentYear');
            if (yearEl) yearEl.textContent = new Date().getFullYear();
        },

        readUrlParams() {
            const params = new URLSearchParams(window.location.search);
            if (params.has('marca')) this.state.filters.marca = params.get('marca');
            if (params.has('modelo')) this.state.filters.modelo = params.get('modelo');
            if (params.has('tipo')) this.state.filters.tipo = params.get('tipo');
            if (params.has('anio_min')) this.state.filters.anio_min = params.get('anio_min');
            if (params.has('precio_min')) this.state.filters.precio_min = params.get('precio_min');
            if (params.has('precio_max')) this.state.filters.precio_max = params.get('precio_max');
            if (params.has('orden')) this.state.filters.orden = params.get('orden');
            if (params.has('q')) this.state.filters.q = params.get('q');
            if (params.has('page')) this.state.page = parseInt(params.get('page')) || 1;

            // Sincronizar inputs
            if (this.qInput && this.state.filters.q) this.qInput.value = this.state.filters.q;
            if (this.precioMinInput && this.state.filters.precio_min) this.precioMinInput.value = this.state.filters.precio_min;
            if (this.precioMaxInput && this.state.filters.precio_max) this.precioMaxInput.value = this.state.filters.precio_max;
            if (this.ordenSelect && this.state.filters.orden) this.ordenSelect.value = this.state.filters.orden;
        },

        bindEvents() {
            // Envío del formulario de filtros
            this.form.addEventListener("submit", (e) => {
                e.preventDefault();
                this.state.page = 1;
                this.collectFiltersFromForm();
                this.fetchData();
            });

            // Botón de restablecer
            this.resetBtn.addEventListener("click", () => {
                this.form.reset();
                this.state.filters = {};
                this.state.page = 1;
                this.actualizarSelectModelos('');
                this.fetchData();
            });

            // Cambio en select de marca para actualizar modelos encadenados
            this.marcaSelect.addEventListener("change", (e) => {
                this.actualizarSelectModelos(e.target.value);
            });

            // Botones de alternancia de moneda
            this.btnCurrUSD.addEventListener("click", () => this.setCurrency('USD'));
            this.btnCurrRD.addEventListener("click", () => this.setCurrency('RD'));
        },

        setupCurrencyButtons() {
            if (this.state.currency === 'RD') {
                this.btnCurrRD.classList.add('active');
                this.btnCurrUSD.classList.remove('active');
            } else {
                this.btnCurrUSD.classList.add('active');
                this.btnCurrRD.classList.remove('active');
            }
        },

        setCurrency(curr) {
            this.state.currency = curr;
            localStorage.setItem('preferred_currency', curr);
            this.setupCurrencyButtons();
            // Re-renderizar precios de tarjetas ya existentes
            document.querySelectorAll('.vehicle-card').forEach(card => {
                const carData = card.carData;
                if (carData) {
                    const priceEl = card.querySelector('.vehicle-price');
                    if (priceEl) {
                        priceEl.textContent = DealerAPI.formatPrice(carData, curr);
                    }
                }
            });
        },

        collectFiltersFromForm() {
            this.state.filters = {};
            if (this.qInput.value.trim()) this.state.filters.q = this.qInput.value.trim();
            if (this.marcaSelect.value) this.state.filters.marca = this.marcaSelect.value;
            if (this.modeloSelect.value) this.state.filters.modelo = this.modeloSelect.value;
            if (this.tipoSelect.value) this.state.filters.tipo = this.tipoSelect.value;
            if (this.anioSelect.value) this.state.filters.anio_min = this.anioSelect.value;
            if (this.precioMinInput.value) this.state.filters.precio_min = this.precioMinInput.value;
            if (this.precioMaxInput.value) this.state.filters.precio_max = this.precioMaxInput.value;
            if (this.ordenSelect.value) this.state.filters.orden = this.ordenSelect.value;
        },

        async fetchData() {
            this.renderLoadingSkeletons();
            this.resultsSummary.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Consultando vehículos disponibles...';

            const requestParams = {
                ...this.state.filters,
                page: this.state.page,
                limit: this.state.limit
            };

            try {
                const response = await DealerAPI.getInventario(requestParams);
                
                this.state.totalVehiculos = response.total || (response.vehiculos ? response.vehiculos.length : 0);
                this.state.totalPaginas = response.total_paginas || Math.ceil(this.state.totalVehiculos / this.state.limit) || 1;

                // Rellenar filtros si aún no tienen opciones
                if (response.filtros_disponibles) {
                    this.populateFilterDropdowns(response.filtros_disponibles, response.vehiculos || []);
                }

                // Renderizar vehículos
                this.renderVehicles(response.vehiculos || []);

                // Renderizar paginación
                this.renderPagination();

                // Actualizar resumen
                const count = response.vehiculos ? response.vehiculos.length : 0;
                this.resultsSummary.innerHTML = `Mostrando <strong>${count}</strong> de <strong>${this.state.totalVehiculos}</strong> vehículos disponibles`;

            } catch (err) {
                console.error("Error al obtener catálogo:", err);
                this.grid.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 50px; border: 1px dashed var(--border-color); border-radius: 8px;">
                        <i class="fa fa-plug" style="font-size: 2.5rem; color: var(--red-primary); margin-bottom: 15px;"></i>
                        <h3>Error al conectar con el inventario</h3>
                        <p style="color: var(--text-gray); margin-top: 8px;">No se pudo sincronizar con el endpoint. Por favor verifica tu conexión.</p>
                        <button onclick="window.location.reload()" class="btn-primary" style="margin-top: 15px;">Reintentar</button>
                    </div>
                `;
            }
        },

        renderLoadingSkeletons() {
            this.grid.innerHTML = `
                <div class="skeleton-loader"></div>
                <div class="skeleton-loader"></div>
                <div class="skeleton-loader"></div>
            `;
        },

        populateFilterDropdowns(filtros, vehiculos) {
            // Guardar mapeo de modelos
            vehiculos.forEach(v => {
                if (v.marca && v.modelo) {
                    if (!this.state.modelosPorMarca[v.marca]) {
                        this.state.modelosPorMarca[v.marca] = new Set();
                    }
                    this.state.modelosPorMarca[v.marca].add(v.modelo);
                }
            });

            // Poblar Marcas (solo si está vacío)
            if (this.marcaSelect.options.length <= 1 && filtros.marcas) {
                filtros.marcas.forEach(marca => {
                    const opt = document.createElement("option");
                    opt.value = marca;
                    opt.textContent = marca;
                    if (this.state.filters.marca && this.state.filters.marca.toLowerCase() === marca.toLowerCase()) {
                        opt.selected = true;
                    }
                    this.marcaSelect.appendChild(opt);
                });
                if (this.state.filters.marca) {
                    this.actualizarSelectModelos(this.state.filters.marca, this.state.filters.modelo);
                }
            }

            // Poblar Tipos / Carrocerías
            if (this.tipoSelect.options.length <= 1 && filtros.tipos) {
                filtros.tipos.forEach(tipo => {
                    const opt = document.createElement("option");
                    opt.value = tipo;
                    opt.textContent = tipo;
                    if (this.state.filters.tipo && this.state.filters.tipo.toLowerCase() === tipo.toLowerCase()) {
                        opt.selected = true;
                    }
                    this.tipoSelect.appendChild(opt);
                });
            }

            // Poblar Años
            if (this.anioSelect.options.length <= 1 && filtros.anios) {
                filtros.anios.sort((a, b) => b - a).forEach(anio => {
                    const opt = document.createElement("option");
                    opt.value = anio;
                    opt.textContent = `${anio} o más reciente`;
                    if (this.state.filters.anio_min && parseInt(this.state.filters.anio_min) === anio) {
                        opt.selected = true;
                    }
                    this.anioSelect.appendChild(opt);
                });
            }
        },

        actualizarSelectModelos(marcaSeleccionada, modeloPreseleccionado = '') {
            this.modeloSelect.innerHTML = '<option value="">Todos los modelos</option>';
            if (!marcaSeleccionada) return;

            const modelosSet = this.state.modelosPorMarca[marcaSeleccionada];
            if (modelosSet) {
                Array.from(modelosSet).sort().forEach(mod => {
                    const opt = document.createElement("option");
                    opt.value = mod;
                    opt.textContent = mod;
                    if (modeloPreseleccionado && modeloPreseleccionado.toLowerCase() === mod.toLowerCase()) {
                        opt.selected = true;
                    }
                    this.modeloSelect.appendChild(opt);
                });
            }
        },

        renderVehicles(vehiculos) {
            if (vehiculos.length === 0) {
                this.grid.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 60px; border: 1px dashed var(--border-color); border-radius: 8px;">
                        <i class="fa fa-search-minus" style="font-size: 3rem; color: var(--red-primary); margin-bottom: 15px;"></i>
                        <h3>No se encontraron vehículos que coincidan</h3>
                        <p style="color: var(--text-gray); margin-top: 10px;">Prueba restableciendo los filtros para ver todos los autos en inventario.</p>
                        <button type="button" class="btn-primary" id="emptyResetBtn" style="display: inline-block; margin-top: 20px;">Restablecer Filtros</button>
                    </div>
                `;
                const emptyBtn = document.getElementById("emptyResetBtn");
                if (emptyBtn) emptyBtn.addEventListener("click", () => this.resetBtn.click());
                return;
            }

            this.grid.innerHTML = "";

            vehiculos.forEach(car => {
                const card = document.createElement("div");
                card.className = "vehicle-card";
                card.carData = car;

                const imgSrc = car.imagen_principal || (car.imagenes && car.imagenes[0]) || 'imagenes/bmw.jpg';
                const formattedPrice = DealerAPI.formatPrice(car, this.state.currency);
                const isDestacado = car.destacado || false;
                const waUrl = DealerAPI.buildWhatsAppUrl(car);

                card.innerHTML = `
                    <div class="vehicle-img-wrapper">
                        ${isDestacado ? '<span class="vehicle-tag">Destacado</span>' : '<span class="vehicle-tag" style="background:#2ecc71;">Disponible</span>'}
                        <img src="${imgSrc}" alt="${car.marca} ${car.modelo}" loading="lazy" onerror="this.src='imagenes/bmw.jpg'">
                    </div>
                    <div class="vehicle-info">
                        <h3 class="vehicle-title">${car.marca} ${car.modelo}</h3>
                        <div class="vehicle-specs">
                            <div class="spec-item"><i class="fa fa-calendar-alt"></i> ${car.anio}</div>
                            <div class="spec-item"><i class="fa fa-cogs"></i> ${car.transmision || 'Auto'}</div>
                            <div class="spec-item"><i class="fa fa-gas-pump"></i> ${car.combustible || 'Gasolina'}</div>
                            <div class="spec-item"><i class="fa fa-road"></i> ${Number(car.kilometraje || 0).toLocaleString()} km</div>
                        </div>
                        <div class="vehicle-footer">
                            <span class="vehicle-price">${formattedPrice}</span>
                            <div class="vehicle-footer-actions">
                                <a href="${waUrl}" target="_blank" class="btn-wa-icon" title="Consultar por WhatsApp"><i class="fab fa-whatsapp"></i></a>
                                <a href="vehicle_details.html?id=${car.id}" class="btn-card">Detalles</a>
                            </div>
                        </div>
                    </div>
                `;

                this.grid.appendChild(card);
            });
        },

        renderPagination() {
            if (this.state.totalPaginas <= 1) {
                this.paginationContainer.style.display = "none";
                return;
            }

            this.paginationContainer.style.display = "flex";
            this.paginationContainer.innerHTML = "";

            // Botón Anterior
            const prevBtn = document.createElement("button");
            prevBtn.className = "page-btn";
            prevBtn.innerHTML = '<i class="fa fa-chevron-left"></i> Anterior';
            prevBtn.disabled = this.state.page === 1;
            prevBtn.addEventListener("click", () => {
                if (this.state.page > 1) {
                    this.state.page--;
                    this.fetchData();
                    this.scrollToGrid();
                }
            });
            this.paginationContainer.appendChild(prevBtn);

            // Números de página
            for (let i = 1; i <= this.state.totalPaginas; i++) {
                const pageBtn = document.createElement("button");
                pageBtn.className = `page-btn ${i === this.state.page ? 'active' : ''}`;
                pageBtn.textContent = i;
                pageBtn.addEventListener("click", () => {
                    if (this.state.page !== i) {
                        this.state.page = i;
                        this.fetchData();
                        this.scrollToGrid();
                    }
                });
                this.paginationContainer.appendChild(pageBtn);
            }

            // Botón Siguiente
            const nextBtn = document.createElement("button");
            nextBtn.className = "page-btn";
            nextBtn.innerHTML = 'Siguiente <i class="fa fa-chevron-right"></i>';
            nextBtn.disabled = this.state.page === this.state.totalPaginas;
            nextBtn.addEventListener("click", () => {
                if (this.state.page < this.state.totalPaginas) {
                    this.state.page++;
                    this.fetchData();
                    this.scrollToGrid();
                }
            });
            this.paginationContainer.appendChild(nextBtn);
        },

        scrollToGrid() {
            this.grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    InventoryApp.init();
});
