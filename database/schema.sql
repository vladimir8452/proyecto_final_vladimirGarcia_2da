-- =============================================
-- RESTAURANTE INSTITUCIONAL "RUMBO AL CIELO"
-- Unión Boliviana — Schema v1.0
-- Compatible con MySQL 8 / MariaDB / phpMyAdmin
-- =============================================

CREATE DATABASE IF NOT EXISTS restaurante_rumbo_al_cielo
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE restaurante_rumbo_al_cielo;

-- -----------------------------------------------
-- 1. USUARIOS / EMPLEADOS
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre           VARCHAR(150)  NOT NULL,
  apellidos        VARCHAR(150)  NULL,
  ci               VARCHAR(30)   NULL UNIQUE,
  departamento     VARCHAR(100)  NULL,
  celular          VARCHAR(20)   NULL DEFAULT NULL,
  correo           VARCHAR(150)  NOT NULL UNIQUE,
  contrasena       VARCHAR(255)  NOT NULL,
  tipo_cuenta      ENUM('ADMIN','PENSIONADO','NO_PENSIONADO') NOT NULL,
  codigo_empleado  VARCHAR(50)   NULL DEFAULT NULL UNIQUE,
  activo           TINYINT(1)    NOT NULL DEFAULT 1,
  created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 2. FAMILIARES
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS familiares (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empleado_titular_id  INT UNSIGNED NOT NULL,
  nombre               VARCHAR(150) NOT NULL,
  parentesco           ENUM('PADRE','MADRE','CONYUGE','HIJO') NULL,
  ci                   VARCHAR(30) NULL UNIQUE,
  estado               ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (empleado_titular_id) REFERENCES usuarios(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_titular (empleado_titular_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 3. CALENDARIO OPERATIVO
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS calendario_operativo (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fecha                 DATE         NOT NULL UNIQUE,
  es_feriado            TINYINT(1)   NOT NULL DEFAULT 0,
  descripcion_feriado   VARCHAR(200) NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 4. MENÚS
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS menus (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fecha            DATE         NOT NULL UNIQUE,
  descripcion_menu TEXT         NOT NULL,
  sopa             VARCHAR(180) NULL,
  plato_principal  VARCHAR(180) NULL,
  postre           VARCHAR(180) NULL,
  publicado        TINYINT(1)   NOT NULL DEFAULT 1,
  created_by       INT UNSIGNED NOT NULL,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 5. RESERVAS
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS reservas (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id        INT UNSIGNED  NULL DEFAULT NULL,
  familiar_id       INT UNSIGNED  NULL DEFAULT NULL,
  tipo_beneficiario ENUM('TITULAR','FAMILIAR') NOT NULL,
  fecha_reserva     DATE          NOT NULL,
  estado            ENUM('RESERVADO','CANCELADO','ENTREGADO','INASISTENCIA') NOT NULL DEFAULT 'RESERVADO',
  modalidad         ENUM('PENSIONADO','ESPORADICO') NOT NULL DEFAULT 'ESPORADICO',
  tarifa            DECIMAL(8,2) NOT NULL DEFAULT 18.00,
  confirmado_por_admin TINYINT(1) NOT NULL DEFAULT 0,
  fecha_creacion    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_cancelacion DATETIME      NULL DEFAULT NULL,
  entregado_en      DATETIME      NULL,
  FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)  ON DELETE SET NULL,
  FOREIGN KEY (familiar_id) REFERENCES familiares(id) ON DELETE SET NULL,
  INDEX idx_fecha (fecha_reserva)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 6. REPORTES MENSUALES (tabla de cierre)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS reportes_mensuales (
  id                       INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  anio                     YEAR          NOT NULL,
  mes                      TINYINT       NOT NULL,
  codigo_empleado          VARCHAR(50)   NOT NULL,
  nombre_empleado          VARCHAR(150)  NOT NULL,
  almuerzos_titular        INT UNSIGNED  NOT NULL DEFAULT 0,
  monto_titular            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  detalle_familiares       JSON          NULL DEFAULT NULL,
  almuerzos_familiares     INT UNSIGNED  NOT NULL DEFAULT 0,
  penalizacion_familiares  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total_descuento          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  generado_en              DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_reporte (anio, mes, codigo_empleado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------

-- -----------------------------------------------
-- 7. INSCRIPCIÓN MENSUAL A PENSIÓN
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS inscripciones_pension (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  beneficiario_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=titular; ID de familiar si aplica',
  anio SMALLINT UNSIGNED NOT NULL,
  mes TINYINT UNSIGNED NOT NULL,
  estado ENUM('ACTIVA','CANCELADA') NOT NULL DEFAULT 'ACTIVA',
  registrado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pension_beneficiario_mes (usuario_id, beneficiario_id, anio, mes),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 8. CONFIGURACIÓN DE REGLAS DE NEGOCIO
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS configuracion_sistema (
  clave VARCHAR(80) PRIMARY KEY,
  valor VARCHAR(255) NOT NULL,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO configuracion_sistema (clave, valor) VALUES
 ('hora_limite','09:00'), ('tarifa_pensionado','15.00'), ('tarifa_esporadico','18.00'), ('minimo_pension','10')
ON DUPLICATE KEY UPDATE valor=VALUES(valor);

-- DATOS INICIALES: Usuario administrador por defecto
-- La contraseña inicial se entrega aparte; cámbiala en el primer inicio de sesión.
-- Hash bcrypt generado con password_hash('killith797', PASSWORD_BCRYPT)
-- -----------------------------------------------
INSERT INTO usuarios (nombre, correo, contrasena, tipo_cuenta, activo)
VALUES (
  'Administrador',
  'admin@rumboalcielo.bo',
  '$2y$10$LFuLaUg0/MqJMijgmjNKTOvJ5Jd9lXj3IMEm0c4kN2OR7mgH390Pu',
  'ADMIN',
  1
);

CREATE TABLE IF NOT EXISTS solicitudes_recuperacion (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 identificador VARCHAR(150) NOT NULL,
 solicitado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 estado ENUM('PENDIENTE','ATENDIDA') NOT NULL DEFAULT 'PENDIENTE',
 INDEX idx_recuperacion_estado (estado, solicitado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
