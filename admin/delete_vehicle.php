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
    header("Location: index.php?tab=productos");
    exit;
}

$id = intval($_GET['id']);

try {
    // 1. Obtener las rutas de las imágenes asociadas al vehículo para borrarlas físicamente
    $stmt = $pdo->prepare("SELECT imagen_principal, imagenes_galeria FROM vehiculos WHERE id = ?");
    $stmt->execute([$id]);
    $car = $stmt->fetch();

    if ($car) {
        // Borrar imagen principal físicamente
        if (!empty($car['imagen_principal']) && file_exists(dirname(__DIR__) . '/' . $car['imagen_principal'])) {
            unlink(dirname(__DIR__) . '/' . $car['imagen_principal']);
        }

        // Borrar imágenes de galería físicamente
        if (!empty($car['imagenes_galeria'])) {
            $gallery_imgs = explode(',', $car['imagenes_galeria']);
            foreach ($gallery_imgs as $img) {
                $img = trim($img);
                if (!empty($img) && file_exists(dirname(__DIR__) . '/' . $img)) {
                    unlink(dirname(__DIR__) . '/' . $img);
                }
            }
        }

        // 2. Eliminar el registro de la base de datos
        $stmt_del = $pdo->prepare("DELETE FROM vehiculos WHERE id = ?");
        $stmt_del->execute([$id]);
    }
} catch (PDOException $e) {
    // Error BD
}

// Redirigir al panel de control
header("Location: index.php?tab=productos");
exit;
?>
