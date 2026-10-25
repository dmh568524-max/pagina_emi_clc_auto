<?php
require_once 'db.php';

// Obtener filtros enviados
$marca = isset($_GET['marca']) ? trim($_GET['marca']) : '';
$modelo = isset($_GET['modelo']) ? trim($_GET['modelo']) : '';
$anio = isset($_GET['anio']) ? trim($_GET['anio']) : '';
$precio_min = isset($_GET['precio_min']) && $_GET['precio_min'] !== '' ? floatval($_GET['precio_min']) : null;
$precio_max = isset($_GET['precio_max']) && $_GET['precio_max'] !== '' ? floatval($_GET['precio_max']) : null;

// Construir consulta dinámica
$query = "SELECT * FROM vehiculos WHERE 1=1";
$params = [];

if ($marca !== '') {
    $query .= " AND marca = :marca";
    $params[':marca'] = $marca;
}
if ($modelo !== '') {
    $query .= " AND modelo = :modelo";
    $params[':modelo'] = $modelo;
}
if ($anio !== '') {
    $query .= " AND anio = :anio";
    $params[':anio'] = intval($anio);
}
if ($precio_min !== null) {
    $query .= " AND precio >= :precio_min";
    $params[':precio_min'] = $precio_min;
}
if ($precio_max !== null) {
    $query .= " AND precio <= :precio_max";
    $params[':precio_max'] = $precio_max;
}

$query .= " ORDER BY id DESC";

$vehiculos = [];
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $vehiculos = $stmt->fetchAll();
} catch (PDOException $e) {
    // Manejo de error si la base de datos no está inicializada o falla
}

// Obtener marcas únicas en la BD para el selector
$listado_marcas = [];
try {
    $listado_marcas = $pdo->query("SELECT DISTINCT marca FROM vehiculos ORDER BY marca ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {}

// Obtener años únicos en la BD para el selector
$listado_anios = [];
try {
    $listado_anios = $pdo->query("SELECT DISTINCT anio FROM vehiculos ORDER BY anio DESC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {}

// Obtener mapeo de marcas y modelos para el selector encadenado dinámico
$mapa_modelos = [];
try {
    $stmt_rel = $pdo->query("SELECT DISTINCT marca, modelo FROM vehiculos ORDER BY marca ASC, modelo ASC");
    while($row = $stmt_rel->fetch(PDO::FETCH_ASSOC)) {
        $mapa_modelos[$row['marca']][] = $row['modelo'];
    }
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario | EMILIANO CLC AUTO IMPORT</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- Header / Navbar -->
    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">EMILIANO <span>CLC AUTO IMPORT</span></a>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">Inicio</a></li>
                <li><a href="inventory.php" class="nav-link active">Inventario</a></li>
            </ul>
            <a href="https://wa.me/18098281404" target="_blank" class="btn-contact"><i class="fab fa-whatsapp"></i> Contáctanos</a>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="section" style="padding-top: 120px;">
        <div class="section-title">
            <h2>Nuestro <span>Inventario</span></h2>
            <p>Filtra y busca tu vehículo ideal en nuestro catálogo en tiempo real</p>
        </div>

        <!-- Caja de Filtros / Buscador -->
        <div class="search-filter-box">
            <form action="inventory.php" method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="marca">Marca</label>
                    <select name="marca" id="marca" class="filter-input">
                        <option value="">Todas las marcas</option>
                        <?php foreach ($listado_marcas as $m): ?>
                            <option value="<?php echo htmlspecialchars($m); ?>" <?php echo $marca === $m ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="modelo">Modelo</label>
                    <select name="modelo" id="modelo" class="filter-input">
                        <option value="">Todos los modelos</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="anio">Año</label>
                    <select name="anio" id="anio" class="filter-input">
                        <option value="">Todos los años</option>
                        <?php foreach ($listado_anios as $a): ?>
                            <option value="<?php echo htmlspecialchars($a); ?>" <?php echo $anio == $a ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($a); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="precio_min">Precio Mín ($)</label>
                    <input type="number" name="precio_min" id="precio_min" class="filter-input" placeholder="Min" value="<?php echo htmlspecialchars($precio_min ?? ''); ?>">
                </div>
                <div class="filter-group">
                    <label for="precio_max">Precio Máx ($)</label>
                    <input type="number" name="precio_max" id="precio_max" class="filter-input" placeholder="Max" value="<?php echo htmlspecialchars($precio_max ?? ''); ?>">
                </div>
                <div class="filter-group search-btn-container">
                    <button type="submit" class="filter-submit-btn"><i class="fa fa-search"></i> Buscar</button>
                </div>
            </form>
        </div>

        <!-- Grid de Vehículos -->
        <div class="vehicles-grid">
            <?php if (!empty($vehiculos)): ?>
                <?php foreach ($vehiculos as $car): ?>
                    <div class="vehicle-card">
                        <div class="vehicle-img-wrapper">
                            <?php if ($car['destacado']): ?>
                                <span class="vehicle-tag">Destacado</span>
                            <?php endif; ?>
                            <img src="<?php echo htmlspecialchars($car['imagen_principal']); ?>" alt="<?php echo htmlspecialchars($car['marca'] . ' ' . $car['modelo']); ?>">
                        </div>
                        <div class="vehicle-info">
                            <h3 class="vehicle-title"><?php echo htmlspecialchars($car['marca'] . ' ' . $car['modelo']); ?></h3>
                            <div class="vehicle-specs">
                                <div class="spec-item"><i class="fa fa-calendar-alt"></i> <?php echo htmlspecialchars($car['anio']); ?></div>
                                <div class="spec-item"><i class="fa fa-cogs"></i> <?php echo htmlspecialchars($car['transmision']); ?></div>
                                <div class="spec-item"><i class="fa fa-gas-pump"></i> <?php echo htmlspecialchars($car['combustible']); ?></div>
                                <div class="spec-item"><i class="fa fa-road"></i> <?php echo number_format($car['kilometraje']); ?> km</div>
                            </div>
                            <div class="vehicle-footer">
                                <span class="vehicle-price">$<?php echo number_format($car['precio'], 2); ?></span>
                                <a href="vehicle_details.php?id=<?php echo $car['id']; ?>" class="btn-card">Detalles</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Estado vacío interactivo -->
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px; border: 1px dashed var(--border-color); border-radius: 8px;">
                    <i class="fa fa-search-minus" style="font-size: 3rem; color: var(--red-primary); margin-bottom: 15px;"></i>
                    <h3>No se encontraron vehículos que coincidan con tu búsqueda</h3>
                    <p style="color: var(--text-gray); margin-top: 10px;">Prueba limpiando o modificando algunos filtros de búsqueda.</p>
                    <a href="inventory.php" class="btn-primary" style="display: inline-block; margin-top: 20px;">Restablecer Filtros</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-col">
                <h3>EMILIANO CLC</h3>
                <p>Importadores directos de vehículos nuevos y usados con la mayor calidad y garantía del mercado.</p>
            </div>
            <div class="footer-col">
                <h3>Enlaces Rápidos</h3>
                <ul>
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="inventory.php">Inventario</a></li>
                    <li><a href="admin/login.php">Acceso Administrativo</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Contacto</h3>
                <p><i class="fa fa-map-marker-alt"></i> Ubicados Sto. Dgo. Norte</p>
                <p style="margin-top: 10px;"><i class="fa fa-phone"></i> (809) 828-1404</p>
                <p style="margin-top: 10px;"><i class="fa fa-envelope"></i> ventas@emilianoclc.com</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> EMILIANO CLC AUTO IMPORT. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="js/main.js"></script>
    <script>
        // Mapeo dinámico de marcas y modelos desde PHP
        const brandModelsMap = <?php echo json_encode($mapa_modelos); ?>;
        const selectedBrand = <?php echo json_encode($marca); ?>;
        const selectedModel = <?php echo json_encode($modelo); ?>;

        const marcaSelect = document.getElementById('marca');
        const modeloSelect = document.getElementById('modelo');

        function actualizarModelos(marcaSeleccionada, modeloSeleccionado = '') {
            // Limpiar modelos
            modeloSelect.innerHTML = '<option value="">Todos los modelos</option>';
            
            if (marcaSeleccionada && brandModelsMap[marcaSeleccionada]) {
                const modelos = brandModelsMap[marcaSeleccionada];
                modelos.forEach(mod => {
                    const option = document.createElement('option');
                    option.value = mod;
                    option.textContent = mod;
                    if (mod === modeloSeleccionado) {
                        option.selected = true;
                    }
                    modeloSelect.appendChild(option);
                });
            }
        }

        // Listener para cuando cambia la marca
        marcaSelect.addEventListener('change', function() {
            actualizarModelos(this.value);
        });

        // Inicializar al cargar la página
        actualizarModelos(selectedBrand, selectedModel);
    </script>
</body>
</html>
