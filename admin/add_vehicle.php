<?php
session_start();
require_once '../db.php';

// Validar inicio de sesión
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marca = trim($_POST['marca']);
    $modelo = trim($_POST['modelo']);
    $anio = intval($_POST['anio']);
    $precio = floatval($_POST['precio']);
    $tipo = trim($_POST['tipo']);
    $combustible = trim($_POST['combustible']);
    $transmision = trim($_POST['transmision']);
    $kilometraje = intval($_POST['kilometraje']);
    $descripcion = trim($_POST['descripcion']);
    $video_url = trim($_POST['video_url']);
    $destacado = isset($_POST['destacado']) ? 1 : 0;

    // Crear carpeta uploads si no existe en la raíz usando ruta absoluta
    $upload_dir = dirname(__DIR__) . '/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $imagen_principal_db = '';
    $imagenes_galeria_db = [];

    // Procesar Imagen Principal con control de errores detallado
    if (isset($_FILES['imagen_principal'])) {
        $file_error = $_FILES['imagen_principal']['error'];
        if ($file_error === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['imagen_principal']['tmp_name'];
            $file_name = time() . '_' . basename($_FILES['imagen_principal']['name']);
            $target_file = $upload_dir . $file_name;

            // Validar tipo de imagen
            $check = getimagesize($file_tmp);
            if ($check !== false) {
                if (move_uploaded_file($file_tmp, $target_file)) {
                    $imagen_principal_db = 'uploads/' . $file_name; // Ruta relativa para el frontend
                } else {
                    $error = 'Error al guardar la imagen. Revisa los permisos de escritura de la carpeta "uploads".';
                }
            } else {
                $error = 'El archivo principal seleccionado no es una imagen válida.';
            }
        } elseif ($file_error === UPLOAD_ERR_NO_FILE) {
            $error = 'La imagen principal es obligatoria para registrar el vehículo.';
        } elseif ($file_error === UPLOAD_ERR_INI_SIZE || $file_error === UPLOAD_ERR_FORM_SIZE) {
            $error = 'La imagen excede el límite de tamaño permitido por el servidor (máx: ' . ini_get('upload_max_filesize') . ').';
        } else {
            $error = 'Error del sistema al subir el archivo (Código de error: ' . $file_error . ').';
        }
    } else {
        $error = 'La imagen principal es obligatoria.';
    }

    // Procesar Galería (Opcional - múltiples archivos)
    if (empty($error) && isset($_FILES['imagenes_galeria'])) {
        $total_files = count($_FILES['imagenes_galeria']['name']);
        
        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['imagenes_galeria']['error'][$i] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['imagenes_galeria']['tmp_name'][$i];
                $file_name = time() . '_gallery_' . $i . '_' . basename($_FILES['imagenes_galeria']['name'][$i]);
                $target_file = $upload_dir . $file_name;

                $check = getimagesize($file_tmp);
                if ($check !== false) {
                    if (move_uploaded_file($file_tmp, $target_file)) {
                        $imagenes_galeria_db[] = 'uploads/' . $file_name;
                    }
                }
            }
        }
    }

    // Insertar en la base de datos si no hay errores
    if (empty($error)) {
        try {
            $galeria_str = implode(',', $imagenes_galeria_db);

            $stmt = $pdo->prepare("INSERT INTO vehiculos (marca, modelo, anio, precio, tipo, combustible, transmision, kilometraje, descripcion, imagen_principal, imagenes_galeria, video_url, destacado) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $marca, $modelo, $anio, $precio, $tipo, $combustible, 
                $transmision, $kilometraje, $descripcion, $imagen_principal_db, 
                $galeria_str, $video_url, $destacado
            ]);

            $success = 'Vehículo agregado exitosamente.';
            // Redirigir después de 1.5s
            header("refresh:1.5;url=index.php?tab=productos");
        } catch (PDOException $e) {
            $error = 'Error al registrar el vehículo en la base de datos: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Vehículo | Admin EMILIANO CLC</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- Header / Navbar -->
    <header>
        <div class="nav-container">
            <a href="../index.php" class="logo">EMILIANO <span>CLC ADMIN</span></a>
            <ul class="nav-menu">
                <li><a href="index.php?tab=productos" class="nav-link">Productos</a></li>
                <li><a href="logout.php" class="nav-link" style="color: var(--red-primary);"><i class="fa fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </div>
    </header>

    <!-- Formulario de Adición -->
    <main class="section">
        <div class="admin-form">
            <h2>Agregar <span>Nuevo Vehículo</span></h2>

            <?php if (!empty($error)): ?>
                <div style="background: rgba(229, 9, 20, 0.15); border: 1px solid var(--red-primary); color: white; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
                    <i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div style="background: rgba(40, 167, 69, 0.15); border: 1px solid #28a745; color: white; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
                    <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form action="add_vehicle.php" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="filter-group">
                        <label for="marca">Marca *</label>
                        <input type="text" name="marca" id="marca" class="filter-input" placeholder="Ej: Honda, Porsche" required>
                    </div>

                    <div class="filter-group">
                        <label for="modelo">Modelo *</label>
                        <input type="text" name="modelo" id="modelo" class="filter-input" placeholder="Ej: Civic, 911" required>
                    </div>

                    <div class="filter-group">
                        <label for="anio">Año *</label>
                        <input type="number" name="anio" id="anio" class="filter-input" min="1900" max="<?php echo date('Y') + 1; ?>" placeholder="Ej: 2021" required>
                    </div>

                    <div class="filter-group">
                        <label for="precio">Precio ($ USD) *</label>
                        <input type="number" step="0.01" name="precio" id="precio" class="filter-input" placeholder="Ej: 25500.00" required>
                    </div>

                    <div class="filter-group">
                        <label for="tipo">Tipo de Vehículo *</label>
                        <select name="tipo" id="tipo" class="filter-input" required>
                            <option value="">Seleccione...</option>
                            <option value="Sedán">Sedán</option>
                            <option value="SUV">SUV</option>
                            <option value="Deportivo">Deportivo / Coupe</option>
                            <option value="Hatchback">Hatchback</option>
                            <option value="Camioneta">Camioneta / Pick-up</option>
                            <option value="Van">Van / Familiar</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="combustible">Combustible *</label>
                        <select name="combustible" id="combustible" class="filter-input" required>
                            <option value="">Seleccione...</option>
                            <option value="Gasolina">Gasolina</option>
                            <option value="Diésel">Diésel</option>
                            <option value="Híbrido">Híbrido</option>
                            <option value="Eléctrico">Eléctrico</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="transmision">Transmisión *</label>
                        <select name="transmision" id="transmision" class="filter-input" required>
                            <option value="">Seleccione...</option>
                            <option value="Automática">Automática</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="kilometraje">Kilometraje (km) *</label>
                        <input type="number" name="kilometraje" id="kilometraje" class="filter-input" placeholder="Ej: 45000" required>
                    </div>

                    <div class="filter-group">
                        <label for="imagen_principal">Imagen Principal *</label>
                        <input type="file" name="imagen_principal" id="imagen_principal" class="filter-input" accept="image/*" required>
                    </div>

                    <div class="filter-group">
                        <label for="imagenes_galeria">Galería de Imágenes (Opcional - Múltiple)</label>
                        <input type="file" name="imagenes_galeria[]" id="imagenes_galeria" class="filter-input" accept="image/*" multiple>
                    </div>

                    <div class="filter-group full-width">
                        <label for="video_url">URL de Video del Anuncio (Youtube o Directo)</label>
                        <input type="text" name="video_url" id="video_url" class="filter-input" placeholder="Ej: https://www.youtube.com/watch?v=...">
                    </div>

                    <div class="filter-group full-width" style="flex-direction: row; gap: 10px; align-items: center; margin-top: 10px;">
                        <input type="checkbox" name="destacado" id="destacado" style="width: 20px; height: 20px; accent-color: var(--red-primary);">
                        <label for="destacado" style="margin: 0; cursor: pointer;">Mostrar como Vehículo Destacado en la Página de Inicio</label>
                    </div>

                    <div class="filter-group full-width">
                        <label for="descripcion">Descripción / Notas Adicionales</label>
                        <textarea name="descripcion" id="descripcion" class="filter-input" rows="5" placeholder="Escribe especificaciones adicionales, estado de la pintura, equipamiento, etc."></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="index.php?tab=productos" class="btn-secondary" style="padding: 10px 20px; border-width: 1px;">Cancelar</a>
                    <button type="submit" class="btn-primary" style="border: none; cursor: pointer; padding: 10px 25px;">Guardar Vehículo</button>
                </div>
            </form>
        </div>
    </main>

</body>
</html>
