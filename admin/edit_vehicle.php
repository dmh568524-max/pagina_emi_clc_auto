<?php
session_start();
require_once '../db.php';

// Validar inicio de sesión
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit;
}

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = intval($_GET['id']);
$car = null;

try {
    $stmt = $pdo->prepare("SELECT * FROM vehiculos WHERE id = ?");
    $stmt->execute([$id]);
    $car = $stmt->fetch();
} catch (PDOException $e) {
    // Error BD
}

if (!$car) {
    header("Location: index.php");
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

    // Obtener ruta absoluta de subidas y asegurar su existencia
    $upload_dir = dirname(__DIR__) . '/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $imagen_principal_db = $car['imagen_principal'];
    $imagenes_galeria_db = !empty($car['imagenes_galeria']) ? explode(',', $car['imagenes_galeria']) : [];

    // Si se sube una nueva imagen principal
    if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file_error = $_FILES['imagen_principal']['error'];
        if ($file_error === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['imagen_principal']['tmp_name'];
            $file_name = time() . '_' . basename($_FILES['imagen_principal']['name']);
            $target_file = $upload_dir . $file_name;

            $check = getimagesize($file_tmp);
            if ($check !== false) {
                if (move_uploaded_file($file_tmp, $target_file)) {
                    // Eliminar archivo físico anterior si existía
                    if (!empty($car['imagen_principal']) && file_exists(dirname(__DIR__) . '/' . $car['imagen_principal'])) {
                        unlink(dirname(__DIR__) . '/' . $car['imagen_principal']);
                    }
                    $imagen_principal_db = 'uploads/' . $file_name;
                } else {
                    $error = 'Error al guardar la nueva imagen principal. Revisa los permisos de la carpeta uploads.';
                }
            } else {
                $error = 'La nueva imagen principal seleccionada no es válida.';
            }
        } elseif ($file_error === UPLOAD_ERR_INI_SIZE || $file_error === UPLOAD_ERR_FORM_SIZE) {
            $error = 'La nueva imagen excede el límite de tamaño permitido por el servidor (máx: ' . ini_get('upload_max_filesize') . ').';
        } else {
            $error = 'Error del sistema al subir el archivo (Código de error: ' . $file_error . ').';
        }
    }

    // Si se suben nuevas imágenes de galería (reemplazarían o se añadirían. Vamos a hacer que reemplace la galería para simplicidad, o agregue si se seleccionan nuevas)
    if (empty($error) && isset($_FILES['imagenes_galeria']) && $_FILES['imagenes_galeria']['error'][0] !== UPLOAD_ERR_NO_FILE) {
        $total_files = count($_FILES['imagenes_galeria']['name']);
        $nuevas_imagenes = [];
        
        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['imagenes_galeria']['error'][$i] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['imagenes_galeria']['tmp_name'][$i];
                $file_name = time() . '_gallery_edit_' . $i . '_' . basename($_FILES['imagenes_galeria']['name'][$i]);
                $target_file = $upload_dir . $file_name;

                $check = getimagesize($file_tmp);
                if ($check !== false) {
                    if (move_uploaded_file($file_tmp, $target_file)) {
                        $nuevas_imagenes[] = 'uploads/' . $file_name;
                    }
                }
            }
        }
        
        if (!empty($nuevas_imagenes)) {
            // Eliminar imágenes de galería anteriores de forma física
            foreach ($imagenes_galeria_db as $old_img) {
                if (!empty($old_img) && file_exists('../' . $old_img)) {
                    unlink('../' . $old_img);
                }
            }
            $imagenes_galeria_db = $nuevas_imagenes;
        }
    }

    // Actualizar base de datos
    if (empty($error)) {
        try {
            $galeria_str = implode(',', $imagenes_galeria_db);

            $stmt = $pdo->prepare("UPDATE vehiculos SET marca=?, modelo=?, anio=?, precio=?, tipo=?, combustible=?, transmision=?, kilometraje=?, descripcion=?, imagen_principal=?, imagenes_galeria=?, video_url=?, destacado=? WHERE id=?");
            $stmt->execute([
                $marca, $modelo, $anio, $precio, $tipo, $combustible, 
                $transmision, $kilometraje, $descripcion, $imagen_principal_db, 
                $galeria_str, $video_url, $destacado, $id
            ]);

            $success = 'Vehículo actualizado exitosamente.';
            // Recargar datos actualizados
            header("refresh:1.5;url=index.php?tab=productos");
        } catch (PDOException $e) {
            $error = 'Error al actualizar el vehículo: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Vehículo | Admin EMILIANO CLC</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- Header / Navbar -->
    <header>
        <div class="nav-container">
            <a href="../index.php" class="logo">EMILIANO <span>CLC ADMIN</span></a>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">Panel Control</a></li>
                <li><a href="logout.php" class="nav-link" style="color: var(--red-primary);"><i class="fa fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </div>
    </header>

    <!-- Formulario de Edición -->
    <main class="section">
        <div class="admin-form">
            <h2>Editar <span>Vehículo</span></h2>

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

            <form action="edit_vehicle.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="filter-group">
                        <label for="marca">Marca *</label>
                        <input type="text" name="marca" id="marca" class="filter-input" value="<?php echo htmlspecialchars($car['marca']); ?>" required>
                    </div>

                    <div class="filter-group">
                        <label for="modelo">Modelo *</label>
                        <input type="text" name="modelo" id="modelo" class="filter-input" value="<?php echo htmlspecialchars($car['modelo']); ?>" required>
                    </div>

                    <div class="filter-group">
                        <label for="anio">Año *</label>
                        <input type="number" name="anio" id="anio" class="filter-input" min="1900" max="<?php echo date('Y') + 1; ?>" value="<?php echo htmlspecialchars($car['anio']); ?>" required>
                    </div>

                    <div class="filter-group">
                        <label for="precio">Precio ($ USD) *</label>
                        <input type="number" step="0.01" name="precio" id="precio" class="filter-input" value="<?php echo htmlspecialchars($car['precio']); ?>" required>
                    </div>

                    <div class="filter-group">
                        <label for="tipo">Tipo de Vehículo *</label>
                        <select name="tipo" id="tipo" class="filter-input" required>
                            <option value="Sedán" <?php echo $car['tipo'] === 'Sedán' ? 'selected' : ''; ?>>Sedán</option>
                            <option value="SUV" <?php echo $car['tipo'] === 'SUV' ? 'selected' : ''; ?>>SUV</option>
                            <option value="Deportivo" <?php echo $car['tipo'] === 'Deportivo' ? 'selected' : ''; ?>>Deportivo / Coupe</option>
                            <option value="Hatchback" <?php echo $car['tipo'] === 'Hatchback' ? 'selected' : ''; ?>>Hatchback</option>
                            <option value="Camioneta" <?php echo $car['tipo'] === 'Camioneta' ? 'selected' : ''; ?>>Camioneta / Pick-up</option>
                            <option value="Van" <?php echo $car['tipo'] === 'Van' ? 'selected' : ''; ?>>Van / Familiar</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="combustible">Combustible *</label>
                        <select name="combustible" id="combustible" class="filter-input" required>
                            <option value="Gasolina" <?php echo $car['combustible'] === 'Gasolina' ? 'selected' : ''; ?>>Gasolina</option>
                            <option value="Diésel" <?php echo $car['combustible'] === 'Diésel' ? 'selected' : ''; ?>>Diésel</option>
                            <option value="Híbrido" <?php echo $car['combustible'] === 'Híbrido' ? 'selected' : ''; ?>>Híbrido</option>
                            <option value="Eléctrico" <?php echo $car['combustible'] === 'Eléctrico' ? 'selected' : ''; ?>>Eléctrico</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="transmision">Transmisión *</label>
                        <select name="transmision" id="transmision" class="filter-input" required>
                            <option value="Automática" <?php echo $car['transmision'] === 'Automática' ? 'selected' : ''; ?>>Automática</option>
                            <option value="Manual" <?php echo $car['transmision'] === 'Manual' ? 'selected' : ''; ?>>Manual</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="kilometraje">Kilometraje (km) *</label>
                        <input type="number" name="kilometraje" id="kilometraje" class="filter-input" value="<?php echo htmlspecialchars($car['kilometraje']); ?>" required>
                    </div>

                    <div class="filter-group">
                        <label for="imagen_principal">Imagen Principal (Dejar vacío para conservar actual)</label>
                        <input type="file" name="imagen_principal" id="imagen_principal" class="filter-input" accept="image/*">
                        <?php if(!empty($car['imagen_principal'])): ?>
                            <span style="font-size: 0.85rem; color: var(--text-gray); margin-top: 5px;">Actual: <a href="../<?php echo $car['imagen_principal']; ?>" target="_blank" style="color: var(--red-primary); text-decoration: underline;">Ver Imagen</a></span>
                        <?php endif; ?>
                    </div>

                    <div class="filter-group">
                        <label for="imagenes_galeria">Galería de Imágenes (Dejar vacío para conservar actual)</label>
                        <input type="file" name="imagenes_galeria[]" id="imagenes_galeria" class="filter-input" accept="image/*" multiple>
                        <?php if(!empty($car['imagenes_galeria'])): ?>
                            <span style="font-size: 0.85rem; color: var(--text-gray); margin-top: 5px;">Tiene imágenes registradas en la galería</span>
                        <?php endif; ?>
                    </div>

                    <div class="filter-group full-width">
                        <label for="video_url">URL de Video del Anuncio</label>
                        <input type="text" name="video_url" id="video_url" class="filter-input" value="<?php echo htmlspecialchars($car['video_url'] ?? ''); ?>" placeholder="Ej: https://www.youtube.com/watch?v=...">
                    </div>

                    <div class="filter-group full-width" style="flex-direction: row; gap: 10px; align-items: center; margin-top: 10px;">
                        <input type="checkbox" name="destacado" id="destacado" style="width: 20px; height: 20px; accent-color: var(--red-primary);" <?php echo $car['destacado'] ? 'checked' : ''; ?>>
                        <label for="destacado" style="margin: 0; cursor: pointer;">Mostrar como Vehículo Destacado en la Página de Inicio</label>
                    </div>

                    <div class="filter-group full-width">
                        <label for="descripcion">Descripción / Notas Adicionales</label>
                        <textarea name="descripcion" id="descripcion" class="filter-input" rows="5"><?php echo htmlspecialchars($car['descripcion']); ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="index.php?tab=productos" class="btn-secondary" style="padding: 10px 20px; border-width: 1px;">Cancelar</a>
                    <button type="submit" class="btn-primary" style="border: none; cursor: pointer; padding: 10px 25px;">Actualizar Vehículo</button>
                </div>
            </form>
        </div>
    </main>

</body>
</html>
