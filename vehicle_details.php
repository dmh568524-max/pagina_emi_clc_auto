<?php
require_once 'db.php';

// Validar que se reciba un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: inventory.php");
    exit;
}

$id = intval($_GET['id']);
$car = null;

try {
    $stmt = $pdo->prepare("SELECT * FROM vehiculos WHERE id = ?");
    $stmt->execute([$id]);
    $car = $stmt->fetch();
} catch (PDOException $e) {
    // Error de BD
}

if (!$car) {
    // Si no se encuentra el vehículo, redirigir al inventario
    header("Location: inventory.php");
    exit;
}

// Procesar galería de imágenes (separada por comas)
$galeria = [];
if (!empty($car['imagenes_galeria'])) {
    $galeria = array_filter(array_map('trim', explode(',', $car['imagenes_galeria'])));
}
// Añadir la imagen principal como primera en la galería de miniaturas
array_unshift($galeria, $car['imagen_principal']);
$galeria = array_unique($galeria);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($car['marca'] . ' ' . $car['modelo'] . ' ' . $car['anio']); ?> | EMILIANO CLC AUTO IMPORT</title>
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

    <!-- Contenedor Principal de Detalles -->
    <main class="section">
        <div class="detail-container">
            
            <!-- Columna Izquierda: Galería e Información General -->
            <div>
                <div class="gallery-main">
                    <img src="<?php echo htmlspecialchars($car['imagen_principal']); ?>" alt="<?php echo htmlspecialchars($car['marca'] . ' ' . $car['modelo']); ?>" id="main-view-image">
                </div>

                <!-- Miniaturas de la Galería -->
                <?php if (count($galeria) > 1): ?>
                    <div class="gallery-thumbs">
                        <?php foreach ($galeria as $index => $img): ?>
                            <img src="<?php echo htmlspecialchars($img); ?>" alt="Miniatura <?php echo $index + 1; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Ficha Técnica -->
                <div style="margin-top: 40px;">
                    <h2 style="border-bottom: 2px solid var(--red-primary); padding-bottom: 10px; text-transform: uppercase;">Ficha Técnica</h2>
                    <table class="specs-table">
                        <tr>
                            <td>Marca</td>
                            <td><?php echo htmlspecialchars($car['marca']); ?></td>
                        </tr>
                        <tr>
                            <td>Modelo</td>
                            <td><?php echo htmlspecialchars($car['modelo']); ?></td>
                        </tr>
                        <tr>
                            <td>Año</td>
                            <td><?php echo htmlspecialchars($car['anio']); ?></td>
                        </tr>
                        <tr>
                            <td>Transmisión</td>
                            <td><?php echo htmlspecialchars($car['transmision']); ?></td>
                        </tr>
                        <tr>
                            <td>Combustible</td>
                            <td><?php echo htmlspecialchars($car['combustible']); ?></td>
                        </tr>
                        <tr>
                            <td>Kilometraje</td>
                            <td><?php echo number_format($car['kilometraje']); ?> km</td>
                        </tr>
                        <tr>
                            <td>Tipo</td>
                            <td><?php echo htmlspecialchars($car['tipo']); ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Descripción -->
                <?php if (!empty($car['descripcion'])): ?>
                    <div style="margin-top: 40px;">
                        <h2 style="border-bottom: 2px solid var(--red-primary); padding-bottom: 10px; text-transform: uppercase; margin-bottom: 15px;">Descripción del Vehículo</h2>
                        <p style="color: var(--text-gray); white-space: pre-line; line-height: 1.8;"><?php echo htmlspecialchars($car['descripcion']); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Columna Derecha: Sidebar de Compra y Contacto -->
            <div class="detail-sidebar">
                <h1 style="font-size: 2rem; font-weight: 800; text-transform: uppercase; margin-bottom: 10px;"><?php echo htmlspecialchars($car['marca'] . ' ' . $car['modelo']); ?></h1>
                <p style="color: var(--text-gray); font-size: 1.1rem; margin-bottom: 20px;">Año <?php echo htmlspecialchars($car['anio']); ?> | <?php echo htmlspecialchars($car['transmision']); ?></p>
                
                <div class="price">$<?php echo number_format($car['precio'], 2); ?></div>

                <div style="border-top: 1px solid var(--border-color); padding-top: 20px; margin-top: 20px;">
                    <h4 style="text-transform: uppercase; margin-bottom: 10px;">¿Te interesa este vehículo?</h4>
                    <p style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 20px;">Presiona el botón de abajo para comunicarte directamente por WhatsApp con uno de nuestros representantes e iniciar el proceso de compra o financiamiento.</p>
                    
                    <?php 
                    // Mensaje personalizado para WhatsApp
                    $wa_text = "Hola EMILIANO CLC AUTO IMPORT, estoy interesado en el vehículo " . $car['marca'] . " " . $car['modelo'] . " " . $car['anio'] . " anunciado por $" . number_format($car['precio'], 2) . ". ¿Sigue disponible?";
                    $wa_link = "https://wa.me/18098281404?text=" . urlencode($wa_text);
                    ?>
                    
                    <a href="<?php echo $wa_link; ?>" target="_blank" class="btn-whatsapp">
                        <i class="fab fa-whatsapp"></i> Preguntar por WhatsApp
                    </a>
                </div>
            </div>

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
</body>
</html>
