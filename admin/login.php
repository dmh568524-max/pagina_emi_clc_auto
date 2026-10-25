<?php
session_start();
require_once '../db.php';

// Si ya está logueado, redirigir al panel
if (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios_admin WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['admin_logged'] = true;
                $_SESSION['admin_user'] = $user['username'];
                $_SESSION['admin_name'] = $user['nombre'];
                header("Location: index.php");
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            $error = 'Error en el sistema de base de datos.';
        }
    } else {
        $error = 'Por favor complete todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Admin | EMILIANO CLC</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: radial-gradient(circle at center, #1b0204 0%, #030303 100%);">

    <div class="admin-login-box">
        <div style="text-align: center; margin-bottom: 35px;">
            <a href="../index.php" class="login-logo">
                EMILIANO <span>CLC</span>
            </a>
            <p class="login-subtitle">Módulo de Administración</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background: rgba(229, 9, 20, 0.15); border: 1px solid var(--red-primary); color: white; padding: 12px; border-radius: 5px; margin-bottom: 20px; font-size: 0.9rem;">
                <i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="filter-group" style="margin-bottom: 20px;">
                <label for="username">Usuario</label>
                <input type="text" name="username" id="username" class="filter-input" placeholder="Nombre de usuario" required autocomplete="off">
            </div>
            
            <div class="filter-group" style="margin-bottom: 30px;">
                <label for="password">Contraseña</label>
                <input type="password" name="password" id="password" class="filter-input" placeholder="Contraseña de acceso" required>
            </div>

            <button type="submit" class="filter-submit-btn" style="width: 100%;"><i class="fa fa-sign-in-alt"></i> Acceder</button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <a href="../index.php" style="font-size: 0.9rem; color: var(--text-gray);"><i class="fa fa-arrow-left"></i> Volver al sitio principal</a>
        </div>
    </div>

</body>
</html>
