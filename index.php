<?php
require_once 'db.php';

// Obtener vehículos destacados - rota mensualmente de forma determinista usando el mes y el año como semilla
$destacados = [];
try {
    $semilla = intval(date('Y') * 12 + date('n'));
    $stmt = $pdo->prepare("SELECT * FROM vehiculos ORDER BY RAND(:semilla) LIMIT 4");
    $stmt->bindValue(':semilla', $semilla, PDO::PARAM_INT);
    $stmt->execute();
    $destacados = $stmt->fetchAll();
} catch (PDOException $e) {
    // Si la tabla no existe o falla, se maneja en el HTML
}

// Definir las marcas que tienen logo
$brands_with_logos = [
    'BMW' => 'imagenes/logo_bmw.jpg',
    'Mercedes-Benz' => 'imagenes/logo_mercedes.jpg',
    'Mercedes' => 'imagenes/logo_mercedes.jpg',
    'Nissan' => 'imagenes/logo_nissan.png'
];

// Obtener todas las marcas en inventario y sus modelos disponibles
$all_brands_inventory = [];
try {
    // Primero agregamos marcas populares o de logos para mantener consistencia
    $default_brands = ['Ford', 'Chevrolet', 'Dodge', 'Jeep', 'Tesla', 'RAM', 'BMW', 'Mercedes-Benz', 'Nissan'];
    foreach ($default_brands as $b) {
        $all_brands_inventory[$b] = [];
    }
    
    // Consultar todos los modelos disponibles en la base de datos (no vendidos)
    $stmt_br = $pdo->query("SELECT DISTINCT marca, modelo FROM vehiculos WHERE id NOT IN (SELECT vehiculo_id FROM ventas) ORDER BY marca ASC, modelo ASC");
    while ($row = $stmt_br->fetch(PDO::FETCH_ASSOC)) {
        $marca_db = trim($row['marca']);
        if ($marca_db === '') continue;
        
        // Normalizar nombre de marca para agrupar
        $brand_key = $marca_db;
        if (strcasecmp($marca_db, 'Mercedes') === 0 || strcasecmp($marca_db, 'Mercedes-Benz') === 0 || strcasecmp($marca_db, 'Mercedes Benz') === 0) {
            $brand_key = 'Mercedes-Benz';
        }
        
        if (!isset($all_brands_inventory[$brand_key])) {
            $all_brands_inventory[$brand_key] = [];
        }
        
        $all_brands_inventory[$brand_key][] = $row['modelo'];
    }
    
    // Filtrar marcas vacías que no estén en la lista por defecto
    foreach ($all_brands_inventory as $b => $models) {
        if (empty($models) && !in_array($b, $default_brands)) {
            unset($all_brands_inventory[$b]);
        }
    }
} catch (PDOException $e) {
    // Silenciar fallas si la base de datos no está migrada
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMILIANO CLC AUTO IMPORT | Dealer de Vehículos</title>
    <meta name="description" content="Encuentra el auto de tus sueños en EMILIANO CLC AUTO IMPORT. Concesionario premium con los mejores precios del mercado.">
    <link rel="stylesheet" href="css/style.css">
    <!-- FontAwesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- Header / Navbar -->
    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">EMILIANO <span>CLC AUTO IMPORT</span></a>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link active">Inicio</a></li>
                <li><a href="inventory.php" class="nav-link">Inventario</a></li>
            </ul>
            <a href="https://wa.me/18098281404" target="_blank" class="btn-contact"><i class="fab fa-whatsapp"></i> Contáctanos</a>
        </div>
    </header>

    <!-- Sección Hero con Video de Fondo -->
    <section class="hero" id="inicio">
        <div class="hero-video-container">
            <video class="hero-video" autoplay loop muted playsinline>
                <source src="imagenes/video_bg.mp4" type="video/mp4">
                Tu navegador no soporta videos en HTML5.
            </video>
            <div class="hero-overlay"></div>
        </div>
        <div class="hero-content">
            <h1>ENCUENTRA TU VEHÍCULO <span>PREMIUM</span></h1>
            <p>El estándar de calidad y exclusividad en importación de vehículos. Rendimiento, lujo y confianza garantizados.</p>
            <div class="hero-buttons">
                <a href="inventory.php" class="btn-primary"><i class="fa fa-car"></i> Ver Inventario</a>
                <a href="https://wa.me/18098281404" target="_blank" class="btn-secondary"><i class="fa fa-comments"></i> Chatear ahora</a>
            </div>
        </div>
    </section>

    <!-- Fila de Marcas con Hover (Estilo Ticker) -->
    <section class="american-brands-section">
        <div class="section-title" style="margin-bottom: 20px;">
            <h3>Nuestras <span>Marcas</span></h3>
            <p>Pasa el cursor sobre la marca para ver los modelos disponibles en stock</p>
        </div>
        <div class="brands-ticker-container">
            <?php foreach ($all_brands_inventory as $brandName => $modelsList): ?>
                <div class="brand-ticker-card" onclick="window.location.href='inventory.php?marca=<?php echo urlencode($brandName); ?>'" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 80px;">
                    <?php if (isset($brands_with_logos[$brandName])): ?>
                        <div style="height: 45px; display: flex; align-items: center; justify-content: center;">
                            <img src="<?php echo $brands_with_logos[$brandName]; ?>" alt="<?php echo htmlspecialchars($brandName); ?>" style="max-height: 45px; max-width: 120px; object-fit: contain; border-radius: 4px;">
                        </div>
                    <?php else: ?>
                        <h4><?php echo htmlspecialchars($brandName); ?></h4>
                    <?php endif; ?>
                    
                    <!-- Tooltip flotante con modelos disponibles -->
                    <div class="brand-tooltip">
                        <h5>Modelos Disponibles</h5>
                        <?php if (!empty($modelsList)): ?>
                            <ul>
                                <?php foreach ($modelsList as $m): ?>
                                    <li><i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($m); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="no-stock">
                                Sin stock actualmente.<br>
                                <span style="font-size: 0.75rem; color: var(--red-primary); font-weight: 700;">¡Haz clic para encargar a pedido!</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Vehículos Destacados -->
    <section class="section" id="destacados">
        <!-- Holograma de fondo interactivo estilo La Velada -->
        <div class="hologram-bg-container" id="hologramBg"></div>
        <div class="section-title">
            <h2>Vehículos <span>Destacados</span></h2>
            <p>Los autos más codiciados del momento elegidos especialmente para ti</p>
        </div>

        <!-- Vista Ampliada Superior (Se actualiza dinámicamente) -->
        <?php if (!empty($destacados)): 
            $first_car = $destacados[0];
            $first_gal = !empty($first_car['imagenes_galeria']) ? explode(',', $first_car['imagenes_galeria']) : [];
            array_unshift($first_gal, $first_car['imagen_principal']);
            $first_gal = array_unique(array_filter(array_map('trim', $first_gal)));
        ?>
            <div class="featured-expanded-view" id="expandedViewBox">
                <div class="modal-grid">
                    <!-- Lado Izquierdo: Galería y Video -->
                    <div class="modal-left">
                        <div class="modal-gallery-main">
                            <img id="modalMainImg" src="<?php echo htmlspecialchars($first_car['imagen_principal']); ?>" alt="Vehículo">
                        </div>
                        <div class="modal-gallery-thumbs" id="modalThumbs">
                            <?php foreach ($first_gal as $idx => $imgSrc): ?>
                                <img src="<?php echo htmlspecialchars($imgSrc); ?>" class="<?php echo $idx === 0 ? 'active' : ''; ?>" alt="Miniatura">
                            <?php endforeach; ?>
                        </div>
                        <div class="modal-video-box" id="modalVideoBox">
                            <h4>Video del Anuncio</h4>
                            <div class="modal-video-wrapper" id="modalVideoWrapper">
                                <?php if (!empty($first_car['video_url'])): ?>
                                    <?php 
                                    $videoUrl = $first_car['video_url'];
                                    if (strpos($videoUrl, 'youtube.com') !== false || strpos($videoUrl, 'youtu.be') !== false): 
                                        $videoId = '';
                                        if (strpos($videoUrl, 'v=') !== false) {
                                            $parts = explode('v=', $videoUrl);
                                            $videoId = explode('&', $parts[1])[0];
                                        } else {
                                            $parts = explode('youtu.be/', $videoUrl);
                                            $videoId = explode('?', $parts[1])[0];
                                        }
                                    ?>
                                        <iframe src="https://www.youtube.com/embed/<?php echo $videoId; ?>?autoplay=0&mute=1" allow="encrypted-media" allowfullscreen></iframe>
                                    <?php else: ?>
                                        <video src="<?php echo htmlspecialchars($videoUrl); ?>" loop muted controls></video>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <video src="https://player.vimeo.com/external/371433846.sd.mp4?s=236da2f3c0227e36f111867c29fb1e5b38ed6a94&profile_id=139&oauth2_token_id=57447761" loop muted controls></video>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- Lado Derecho: Ficha Técnica -->
                    <div class="modal-right">
                        <div>
                            <span id="modalCategory" style="background:var(--red-primary); color:white; padding:5px 12px; font-size:0.75rem; font-weight:800; border-radius:3px; text-transform:uppercase; display:inline-block; margin-bottom:15px;">MÁS VENDIDO</span>
                            <h2 id="modalTitle" style="text-transform:uppercase; font-size:1.8rem; font-weight:800; line-height:1.2; margin-bottom:5px;"><?php echo htmlspecialchars($first_car['marca'] . ' ' . $first_car['modelo']); ?></h2>
                            <p id="modalSpecsShort" style="color:var(--text-gray); font-size:0.95rem; margin-bottom:20px;">Año <?php echo htmlspecialchars($first_car['anio']); ?> | <?php echo htmlspecialchars($first_car['transmision']); ?></p>
                            <div id="modalPrice" style="font-size:2.2rem; font-weight:800; color:var(--red-primary); margin-bottom:25px;">$<?php echo number_format($first_car['precio'], 2); ?></div>
                            
                            <h4 style="text-transform:uppercase; margin-bottom:10px; border-bottom:1px solid var(--border-color); padding-bottom:5px; font-size:0.95rem;">Ficha Técnica</h4>
                            <table class="specs-table" style="margin-bottom:20px;">
                                <tr>
                                    <td>Transmisión</td>
                                    <td id="modalTrans"><?php echo htmlspecialchars($first_car['transmision']); ?></td>
                                </tr>
                                <tr>
                                    <td>Combustible</td>
                                    <td id="modalGas"><?php echo htmlspecialchars($first_car['combustible']); ?></td>
                                </tr>
                                <tr>
                                    <td>Kilometraje</td>
                                    <td id="modalKm"><?php echo number_format($first_car['kilometraje']); ?> km</td>
                                </tr>
                                <tr>
                                    <td>Tipo</td>
                                    <td id="modalType"><?php echo htmlspecialchars($first_car['tipo']); ?></td>
                                </tr>
                            </table>
                            
                            <h4 style="text-transform:uppercase; margin-bottom:8px; font-size:0.95rem;">Descripción</h4>
                            <p id="modalDesc" style="color:var(--text-gray); font-size:0.9rem; line-height:1.6; max-height:120px; overflow-y:auto; padding-right:5px;"><?php echo htmlspecialchars($first_car['descripcion'] ?? 'No hay especificaciones adicionales escritas por el administrador.'); ?></p>
                        </div>
                        
                        <?php 
                        $first_wa_text = "Hola EMILIANO CLC AUTO IMPORT, estoy interesado en el vehículo destacado [" . $first_car['marca'] . " " . $first_car['modelo'] . " " . $first_car['anio'] . "] por valor de $" . number_format($first_car['precio'], 2) . ". ¿Podrían darme más detalles?";
                        ?>
                        <a href="https://wa.me/18098281404?text=<?php echo urlencode($first_wa_text); ?>" id="modalWaBtn" target="_blank" class="btn-whatsapp" style="margin-top:20px;">
                            <i class="fab fa-whatsapp"></i> Preguntar por WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Fila de Miniaturas Inferior (Los 4 vehículos más pequeños alineados en una sola fila) -->
        <div class="featured-thumbnails-row">
            <?php if (!empty($destacados)): ?>
                <?php 
                $categorias = ["Más Vendido", "Calidad - Precio", "Confort & Velocidad", "Estética & Diseño"];
                foreach ($destacados as $index => $car): 
                    // Galería
                    $galeria = !empty($car['imagenes_galeria']) ? explode(',', $car['imagenes_galeria']) : [];
                    array_unshift($galeria, $car['imagen_principal']);
                    $galeria = array_unique(array_filter(array_map('trim', $galeria)));
                    
                    // JSON para interactividad
                    $car_json = json_encode([
                        'marca' => $car['marca'],
                        'modelo' => $car['modelo'],
                        'anio' => $car['anio'],
                        'precio' => number_format($car['precio'], 2),
                        'raw_precio' => $car['precio'],
                        'tipo' => $car['tipo'],
                        'combustible' => $car['combustible'],
                        'transmision' => $car['transmision'],
                        'kilometraje' => number_format($car['kilometraje']),
                        'descripcion' => $car['descripcion'] ?? '',
                        'imagen_principal' => $car['imagen_principal'],
                        'galeria' => $galeria,
                        'video_url' => $car['video_url'] ?? '',
                        'categoria' => $categorias[$index % 4]
                    ], JSON_HEX_APOS | JSON_HEX_QUOT);
                ?>
                    <div class="small-featured-card <?php echo $index === 0 ? 'active' : ''; ?>" data-car='<?php echo htmlspecialchars($car_json, ENT_QUOTES, 'UTF-8'); ?>'>
                        <img src="<?php echo htmlspecialchars($car['imagen_principal']); ?>" alt="<?php echo htmlspecialchars($car['marca'] . ' ' . $car['modelo']); ?>">
                        <div class="small-featured-card-info">
                            <span class="category"><?php echo $categorias[$index % 4]; ?></span>
                            <h4><?php echo htmlspecialchars($car['marca'] . ' ' . $car['modelo']); ?></h4>
                            <div class="price">$<?php echo number_format($car['precio'], 2); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Estado vacío interactivo -->
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; border: 1px dashed var(--border-color); border-radius: 8px; width: 100%;">
                    <i class="fa fa-car-side" style="font-size: 3rem; color: var(--red-primary); margin-bottom: 15px;"></i>
                    <h3>No hay vehículos destacados de momento</h3>
                    <p style="color: var(--text-gray); margin-top: 10px;">Visita nuestro <a href="inventory.php" style="color: var(--red-primary); text-decoration: underline;">Inventario Completo</a> para ver todos los autos disponibles.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Por qué elegirnos -->
    <section style="background: #111; padding: 80px 20px;">
        <div class="section" style="padding: 0;">
            <div class="section-title">
                <h2>¿Por qué elegir <span>Emiliano CLC</span>?</h2>
                <p>Ofrecemos la mejor experiencia de compra del país</p>
            </div>
            <div class="vehicles-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                <div style="background: var(--bg-card); padding: 35px; border-radius: 10px; border: 1px solid var(--border-color); text-align: center;">
                    <i class="fa fa-award" style="font-size: 2.5rem; color: var(--red-primary); margin-bottom: 20px;"></i>
                    <h3 style="margin-bottom: 10px; text-transform: uppercase;">Calidad Garantizada</h3>
                    <p style="color: var(--text-gray);">Todos nuestros vehículos pasan por una inspección rigurosa antes de ser importados y entregados.</p>
                </div>
                <div style="background: var(--bg-card); padding: 35px; border-radius: 10px; border: 1px solid var(--border-color); text-align: center;">
                    <i class="fa fa-handshake" style="font-size: 2.5rem; color: var(--red-primary); margin-bottom: 20px;"></i>
                    <h3 style="margin-bottom: 10px; text-transform: uppercase;">Trato Transparente</h3>
                    <p style="color: var(--text-gray);">Asesoramiento personalizado, sin costos ocultos, guiándote en todo el proceso de matriculación.</p>
                </div>
                <div style="background: var(--bg-card); padding: 35px; border-radius: 10px; border: 1px solid var(--border-color); text-align: center;">
                    <i class="fa fa-shipping-fast" style="font-size: 2.5rem; color: var(--red-primary); margin-bottom: 20px;"></i>
                    <h3 style="margin-bottom: 10px; text-transform: uppercase;">Importación Directa</h3>
                    <p style="color: var(--text-gray);">Traemos vehículos a pedido directamente desde EE. UU., Japón y Europa con los mejores estándares.</p>
                </div>
            </div>
        </div>
    </section>



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
                    <li><a href="admin/index.php">Acceso Administrativo</a></li>
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
</body>
</html>
