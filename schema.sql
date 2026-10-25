-- Esquema de Base de Datos para EMILIANO CLC AUTO IMPORT

CREATE DATABASE IF NOT EXISTS clc_auto_import DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clc_auto_import;

-- Tabla de vehículos
CREATE TABLE IF NOT EXISTS vehiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(50) NOT NULL,
    anio INT NOT NULL,
    precio DECIMAL(12, 2) NOT NULL,
    tipo VARCHAR(30) NOT NULL, -- Sedán, SUV, Coupe, etc.
    combustible VARCHAR(20) NOT NULL, -- Gasolina, Diésel, Híbrido, Eléctrico
    transmision VARCHAR(20) NOT NULL, -- Automática, Manual
    kilometraje INT NOT NULL,
    descripcion TEXT NULL,
    imagen_principal VARCHAR(255) NOT NULL,
    imagenes_galeria TEXT NULL, -- Rutas de imágenes adicionales separadas por coma
    destacado TINYINT(1) DEFAULT 0, -- 1 para mostrar en el Home
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de administradores
CREATE TABLE IF NOT EXISTS usuarios_admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insertar administrador por defecto (Usuario: admin, Contraseña: admin123)
-- La contraseña está hasheada usando bcrypt
INSERT INTO usuarios_admin (username, password, nombre)
VALUES ('admin', '$2y$10$56y.P4cJ1RifGy4zQffBx.NWJni3Ib9IE/B/Xsf7ZYRoZYhiVtmrm', 'Administrador Emiliano')
ON DUPLICATE KEY UPDATE username=username;
