<?php
session_start();
require_once '../db.php';

// Validar inicio de sesión
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit;
}

// Determinar pestaña activa
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'inicio';

$success = '';
$error = '';

// Procesar acciones POST (Agregar Cliente o Registrar Venta)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_cliente') {
            $nombre = trim($_POST['nombre']);
            $telefono = trim($_POST['telefono']);
            $email = trim($_POST['email']);

            if (!empty($nombre) && !empty($telefono)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO clientes (nombre, telefono, email) VALUES (?, ?, ?)");
                    $stmt->execute([$nombre, $telefono, $email]);
                    $success = "Cliente registrado con éxito.";
                } catch (PDOException $e) {
                    $error = "Error al registrar cliente: " . $e->getMessage();
                }
            } else {
                $error = "Por favor, completa los campos requeridos para el cliente.";
            }
        } elseif ($_POST['action'] === 'add_venta') {
            $vehiculo_id = intval($_POST['vehiculo_id']);
            $cliente_nombre = trim($_POST['cliente_nombre']);
            $precio_venta = floatval($_POST['precio_venta']);
            $costo_adquisicion = floatval($_POST['costo_adquisicion']);
            $fecha_venta = $_POST['fecha_venta'];

            if ($vehiculo_id > 0 && !empty($cliente_nombre) && $precio_venta > 0 && $costo_adquisicion > 0 && !empty($fecha_venta)) {
                try {
                    // Buscar si el cliente ya existe por nombre
                    $stmt_cli = $pdo->prepare("SELECT id FROM clientes WHERE nombre = ?");
                    $stmt_cli->execute([$cliente_nombre]);
                    $cliente_id = $stmt_cli->fetchColumn();

                    if (!$cliente_id) {
                        // Si no existe, crearlo automáticamente
                        $stmt_ins_cli = $pdo->prepare("INSERT INTO clientes (nombre, telefono, email) VALUES (?, ?, ?)");
                        $stmt_ins_cli->execute([$cliente_nombre, 'N/A', 'N/A']);
                        $cliente_id = $pdo->lastInsertId();
                    }

                    $stmt = $pdo->prepare("INSERT INTO ventas (vehiculo_id, cliente_id, precio_venta, costo_adquisicion, fecha_venta) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$vehiculo_id, $cliente_id, $precio_venta, $costo_adquisicion, $fecha_venta]);
                    $success = "Venta registrada con éxito.";
                } catch (PDOException $e) {
                    $error = "Error al registrar venta: " . $e->getMessage();
                }
            } else {
                $error = "Por favor, completa todos los campos del registro de venta.";
            }
        }
    }
}

// Variables para cálculos de estadísticas (Filtro por año/mes en base al año corriente 2026)
$anio_actual = date('Y');
$mes_actual = date('m');

// Inicializar estadísticas por defecto
$ventas_mes = 0;
$ganado_mes = 0;
$ganado_anio = 0;
$perdido_mes = 0;

try {
    // 1. Obtener ventas del mes actual
    $stmt = $pdo->prepare("SELECT SUM(precio_venta) as total, SUM(precio_venta - costo_adquisicion) as utilidad FROM ventas WHERE YEAR(fecha_venta) = ? AND MONTH(fecha_venta) = ?");
    $stmt->execute([$anio_actual, $mes_actual]);
    $stats_mes = $stmt->fetch();
    $ventas_mes = $stats_mes['total'] ?? 0;
    
    // Calcular ganancia/pérdida del mes actual
    $utilidad_mes = $stats_mes['utilidad'] ?? 0;
    if ($utilidad_mes >= 0) {
        $ganado_mes = $utilidad_mes;
        $perdido_mes = 0;
    } else {
        $ganado_mes = 0;
        $perdido_mes = abs($utilidad_mes);
    }

    // 2. Obtener ganancias del año actual
    $stmt_anio = $pdo->prepare("SELECT SUM(precio_venta - costo_adquisicion) as utilidad FROM ventas WHERE YEAR(fecha_venta) = ?");
    $stmt_anio->execute([$anio_actual]);
    $ganado_anio = $stmt_anio->fetchColumn() ?? 0;

    // 3. Contar vehículos en inventario (no vendidos)
    $stock_count = $pdo->query("SELECT COUNT(*) FROM vehiculos WHERE id NOT IN (SELECT vehiculo_id FROM ventas)")->fetchColumn();

    // 4. Contar clientes registrados
    $clientes_count = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();

} catch (PDOException $e) {
    // Manejo en caso de tabla ausente
}

// Cargar listas según el tab
$listado_vehiculos = [];
$listado_ventas = [];
$listado_clientes = [];
$vehiculos_disponibles = [];

try {
    if ($tab === 'productos') {
        $listado_vehiculos = $pdo->query("SELECT * FROM vehiculos ORDER BY id DESC")->fetchAll();
    } elseif ($tab === 'ventas') {
        $listado_ventas = $pdo->query("SELECT v.*, veh.marca, veh.modelo, cli.nombre as cliente_nombre 
                                       FROM ventas v 
                                       JOIN vehiculos veh ON v.vehiculo_id = veh.id 
                                       JOIN clientes cli ON v.cliente_id = cli.id 
                                       ORDER BY v.fecha_venta DESC")->fetchAll();
        
        // Cargar vehículos que NO han sido vendidos para el formulario de registrar venta
        $vehiculos_disponibles = $pdo->query("SELECT id, marca, modelo, precio FROM vehiculos 
                                              WHERE id NOT IN (SELECT vehiculo_id FROM ventas) 
                                              ORDER BY marca ASC")->fetchAll();
        // Cargar clientes para el selector
        $listado_clientes = $pdo->query("SELECT id, nombre FROM clientes ORDER BY nombre ASC")->fetchAll();
    } elseif ($tab === 'clientes') {
        $listado_clientes = $pdo->query("SELECT * FROM clientes ORDER BY id DESC")->fetchAll();
    }
} catch (PDOException $e) {
    // Manejo de error
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración | EMILIANO CLC</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- Header / Navbar -->
    <header>
        <div class="nav-container">
            <a href="../index.php" class="logo">EMILIANO <span>CLC ADMIN</span></a>
            <ul class="nav-menu">
                <li><a href="../index.php" target="_blank" class="nav-link"><i class="fa fa-external-link-alt"></i> Ver Web</a></li>
                <li><a href="logout.php" class="nav-link" style="color: var(--red-primary);"><i class="fa fa-sign-out-alt"></i> Salir</a></li>
            </ul>
            <div style="font-weight: 600; font-size: 0.9rem; color: var(--text-gray);">
                Hola, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
            </div>
        </div>
    </header>

    <!-- Contenedor del Layout Admin -->
    <div class="admin-layout">
        
        <!-- Sidebar Columna Izquierda -->
        <aside class="admin-sidebar">
            <ul class="sidebar-menu">
                <li>
                    <a href="index.php?tab=inicio" class="sidebar-link <?php echo $tab === 'inicio' ? 'active' : ''; ?>">
                        <i class="fa fa-chart-line"></i> Inicio
                    </a>
                </li>
                <li>
                    <a href="index.php?tab=productos" class="sidebar-link <?php echo $tab === 'productos' ? 'active' : ''; ?>">
                        <i class="fa fa-car"></i> Productos / Autos
                    </a>
                </li>
                <li>
                    <a href="index.php?tab=ventas" class="sidebar-link <?php echo $tab === 'ventas' ? 'active' : ''; ?>">
                        <i class="fa fa-wallet"></i> Ventas
                    </a>
                </li>
                <li>
                    <a href="index.php?tab=clientes" class="sidebar-link <?php echo $tab === 'clientes' ? 'active' : ''; ?>">
                        <i class="fa fa-users"></i> Clientes
                    </a>
                </li>
            </ul>
            
            <div style="text-align: center; color: var(--text-gray); font-size: 0.8rem; border-top: 1px solid var(--border-color); padding-top: 15px;">
                <p>EMILIANO CLC v1.1</p>
            </div>
        </aside>

        <!-- Contenedor Principal de Contenido (Lado Derecho) -->
        <main class="admin-main-content">
            
            <?php if (!empty($success)): ?>
                <div style="background: rgba(40, 167, 69, 0.15); border: 1px solid #28a745; color: white; padding: 15px; border-radius: 5px; margin-bottom: 25px;">
                    <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div style="background: rgba(229, 9, 20, 0.15); border: 1px solid var(--red-primary); color: white; padding: 15px; border-radius: 5px; margin-bottom: 25px;">
                    <i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- PESTAÑA: INICIO (ESTADÍSTICAS) -->
            <?php if ($tab === 'inicio'): ?>
                <div style="margin-bottom: 30px;">
                    <h2>Estadísticas <span>Financieras</span></h2>
                    <p style="color: var(--text-gray);">Reporte mensual y anual del dealer para el año <?php echo $anio_actual; ?></p>
                </div>

                <div class="stat-grid">
                    <!-- Ganancia Mensual -->
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span>Ganancia del Mes</span>
                            <i class="fa fa-money-bill-wave"></i>
                        </div>
                        <div class="value" style="color: #28a745;">+$<?php echo number_format($ganado_mes, 2); ?></div>
                        <div class="trend up"><i class="fa fa-arrow-up"></i> Utilidad actual (Neto)</div>
                    </div>

                    <!-- Pérdida Mensual -->
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span>Pérdidas del Mes</span>
                            <i class="fa fa-chart-bar"></i>
                        </div>
                        <div class="value" style="color: <?php echo $perdido_mes > 0 ? 'var(--red-primary)' : 'var(--text-gray)'; ?>;">
                            $<?php echo number_format($perdido_mes, 2); ?>
                        </div>
                        <div class="trend <?php echo $perdido_mes > 0 ? 'down' : ''; ?>"><i class="fa fa-info-circle"></i> Autos vendidos bajo costo</div>
                    </div>

                    <!-- Ventas del Mes -->
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span>Vendido este Mes</span>
                            <i class="fa fa-shopping-cart"></i>
                        </div>
                        <div class="value">$<?php echo number_format($ventas_mes, 2); ?></div>
                        <div class="trend"><i class="fa fa-calendar-day"></i> Volumen de ventas brutas</div>
                    </div>

                    <!-- Ganado al Año -->
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span>Ganancia del Año</span>
                            <i class="fa fa-calendar-alt"></i>
                        </div>
                        <div class="value" style="color: #00e676;">$<?php echo number_format($ganado_anio, 2); ?></div>
                        <div class="trend up"><i class="fa fa-chart-line"></i> Total acumulado en <?php echo $anio_actual; ?></div>
                    </div>
                </div>

                <div class="stat-grid" style="grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- Stock Informativo -->
                    <div style="background: var(--bg-card); padding: 30px; border-radius: 8px; border: 1px solid var(--border-color); text-align: center;">
                        <i class="fa fa-car-side" style="font-size: 3rem; color: var(--red-primary); margin-bottom: 15px;"></i>
                        <h3>Vehículos en Stock</h3>
                        <div style="font-size: 3.5rem; font-weight: 800; color: white; margin: 15px 0;"><?php echo $stock_count; ?></div>
                        <p style="color: var(--text-gray);">Unidades disponibles en el catálogo para la venta.</p>
                    </div>

                    <!-- Clientes Informativos -->
                    <div style="background: var(--bg-card); padding: 30px; border-radius: 8px; border: 1px solid var(--border-color); text-align: center;">
                        <i class="fa fa-users-cog" style="font-size: 3rem; color: var(--red-primary); margin-bottom: 15px;"></i>
                        <h3>Clientes Registrados</h3>
                        <div style="font-size: 3.5rem; font-weight: 800; color: white; margin: 15px 0;"><?php echo $clientes_count; ?></div>
                        <p style="color: var(--text-gray);">Clientes únicos asociados a compras o prospectos.</p>
                    </div>
                </div>

            <!-- PESTAÑA: PRODUCTOS (AUTOS) -->
            <?php elseif ($tab === 'productos'): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                    <div>
                        <h2>Inventario de <span>Vehículos</span></h2>
                        <p style="color: var(--text-gray);">Visualiza, edita o agrega autos a la vitrina virtual</p>
                    </div>
                    <a href="add_vehicle.php" class="btn-primary"><i class="fa fa-plus"></i> Agregar Auto</a>
                </div>

                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Vehículo</th>
                                <th>Año</th>
                                <th>Precio</th>
                                <th>Transmisión</th>
                                <th>Combustible</th>
                                <th>Kilometraje</th>
                                <th>Estado</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($listado_vehiculos)): ?>
                                <?php foreach ($listado_vehiculos as $car): ?>
                                    <tr>
                                        <td>
                                            <img src="../<?php echo htmlspecialchars($car['imagen_principal']); ?>" alt="Foto" style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border-color);">
                                        </td>
                                        <td>
                                            <strong style="text-transform: uppercase;"><?php echo htmlspecialchars($car['marca'] . ' ' . $car['modelo']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($car['anio']); ?></td>
                                        <td style="font-weight: 700; color: var(--red-primary);">$<?php echo number_format($car['precio'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($car['transmision']); ?></td>
                                        <td><?php echo htmlspecialchars($car['combustible']); ?></td>
                                        <td><?php echo number_format($car['kilometraje']); ?> km</td>
                                        <td>
                                            <?php if ($car['destacado']): ?>
                                                <span style="color: #ff9100; font-size: 0.8rem; font-weight: bold;"><i class="fa fa-star"></i> Destacado</span>
                                            <?php else: ?>
                                                <span style="color: var(--text-gray); font-size: 0.8rem;">Estándar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <a href="edit_vehicle.php?id=<?php echo $car['id']; ?>" class="action-btn btn-edit" style="margin-right: 5px;"><i class="fa fa-edit"></i></a>
                                            <a href="delete_vehicle.php?id=<?php echo $car['id']; ?>" class="action-btn btn-delete" onclick="return confirm('¿Eliminar vehículo?');"><i class="fa fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 30px; color: var(--text-gray);">No hay registros en el inventario.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <!-- PESTAÑA: VENTAS -->
            <?php elseif ($tab === 'ventas'): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                    <div>
                        <h2>Registro de <span>Ventas</span></h2>
                        <p style="color: var(--text-gray);">Historial de operaciones y ventas cerradas</p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                    <!-- Tabla de Historial -->
                    <div>
                        <h3>Historial de Operaciones</h3>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Vehículo</th>
                                        <th>Cliente</th>
                                        <th>Precio Venta</th>
                                        <th>Costo Adq.</th>
                                        <th>Utilidad</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($listado_ventas)): ?>
                                        <?php foreach ($listado_ventas as $sale): ?>
                                            <?php 
                                            $utilidad = $sale['precio_venta'] - $sale['costo_adquisicion'];
                                            $color_utilidad = $utilidad >= 0 ? '#28a745' : 'var(--red-primary)';
                                            ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($sale['marca'] . ' ' . $sale['modelo']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($sale['cliente_nombre']); ?></td>
                                                <td style="font-weight: bold;">$<?php echo number_format($sale['precio_venta'], 2); ?></td>
                                                <td style="color: var(--text-gray);">$<?php echo number_format($sale['costo_adquisicion'], 2); ?></td>
                                                <td style="color: <?php echo $color_utilidad; ?>; font-weight: bold;">
                                                    <?php echo $utilidad >= 0 ? '+' : ''; ?>$<?php echo number_format($utilidad, 2); ?>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($sale['fecha_venta'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-gray);">No se han registrado ventas aún.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Registrar Nueva Venta -->
                    <div style="background: var(--bg-card); padding: 25px; border-radius: 8px; border: 1px solid var(--border-color); height: fit-content;">
                        <h3 style="margin-bottom: 20px; text-transform: uppercase; color: var(--red-primary);"><i class="fa fa-plus"></i> Registrar Venta</h3>
                        <form action="index.php?tab=ventas" method="POST">
                            <input type="hidden" name="action" value="add_venta">
                            
                            <div class="filter-group" style="margin-bottom: 15px;">
                                <label for="vehiculo_id">Vehículo en Stock</label>
                                <select name="vehiculo_id" id="vehiculo_id" class="filter-input" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($vehiculos_disponibles as $v): ?>
                                        <option value="<?php echo $v['id']; ?>">
                                            <?php echo htmlspecialchars($v['marca'] . ' ' . $v['modelo'] . ' - $' . number_format($v['precio'])); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group" style="margin-bottom: 15px;">
                                <label for="cliente_nombre">Cliente Comprador</label>
                                <input type="text" name="cliente_nombre" id="cliente_nombre" class="filter-input" placeholder="Escribe el nombre del comprador..." list="lista_clientes" autocomplete="off" required>
                                <datalist id="lista_clientes">
                                    <?php foreach ($listado_clientes as $c): ?>
                                        <option value="<?php echo htmlspecialchars($c['nombre']); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>

                            <div class="filter-group" style="margin-bottom: 15px;">
                                <label for="precio_venta">Precio Real de Venta ($)</label>
                                <input type="number" step="0.01" name="precio_venta" id="precio_venta" class="filter-input" placeholder="0.00" required>
                            </div>

                            <div class="filter-group" style="margin-bottom: 15px;">
                                <label for="costo_adquisicion">Costo de Adquisición/Inversión ($)</label>
                                <input type="number" step="0.01" name="costo_adquisicion" id="costo_adquisicion" class="filter-input" placeholder="0.00" required>
                            </div>

                            <div class="filter-group" style="margin-bottom: 20px;">
                                <label for="fecha_venta">Fecha de Transacción</label>
                                <input type="date" name="fecha_venta" id="fecha_venta" class="filter-input" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <button type="submit" class="filter-submit-btn" style="width:100%; border:none; cursor:pointer;"><i class="fa fa-save"></i> Registrar Venta</button>
                        </form>
                    </div>
                </div>

            <!-- PESTAÑA: CLIENTES -->
            <?php elseif ($tab === 'clientes'): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                    <div>
                        <h2>Gestión de <span>Clientes</span></h2>
                        <p style="color: var(--text-gray);">Directorio de clientes del dealer</p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                    <!-- Tabla de Clientes -->
                    <div>
                        <h3>Listado de Clientes</h3>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Teléfono</th>
                                        <th>Email</th>
                                        <th>Fecha Registro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($listado_clientes)): ?>
                                        <?php foreach ($listado_clientes as $client): ?>
                                            <tr>
                                                <td>#<?php echo $client['id']; ?></td>
                                                <td><strong><?php echo htmlspecialchars($client['nombre']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($client['telefono']); ?></td>
                                                <td><?php echo htmlspecialchars($client['email']); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($client['fecha_registro'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" style="text-align: center; padding: 30px; color: var(--text-gray);">No hay clientes registrados.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Registrar Nuevo Cliente -->
                    <div style="background: var(--bg-card); padding: 25px; border-radius: 8px; border: 1px solid var(--border-color); height: fit-content;">
                        <h3 style="margin-bottom: 20px; text-transform: uppercase; color: var(--red-primary);"><i class="fa fa-user-plus"></i> Registrar Cliente</h3>
                        <form action="index.php?tab=clientes" method="POST">
                            <input type="hidden" name="action" value="add_cliente">
                            
                            <div class="filter-group" style="margin-bottom: 15px;">
                                <label for="nombre">Nombre Completo *</label>
                                <input type="text" name="nombre" id="nombre" class="filter-input" placeholder="Nombre y Apellido" required>
                            </div>

                            <div class="filter-group" style="margin-bottom: 15px;">
                                <label for="telefono">Teléfono *</label>
                                <input type="text" name="telefono" id="telefono" class="filter-input" placeholder="809-000-0000" required>
                            </div>

                            <div class="filter-group" style="margin-bottom: 20px;">
                                <label for="email">Correo Electrónico</label>
                                <input type="email" name="email" id="email" class="filter-input" placeholder="cliente@correo.com">
                            </div>

                            <button type="submit" class="filter-submit-btn" style="width:100%; border:none; cursor:pointer;"><i class="fa fa-save"></i> Guardar Cliente</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>

</body>
</html>
