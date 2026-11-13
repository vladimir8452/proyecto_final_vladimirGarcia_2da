-- Migración incremental para Product Backlog – Sistema Restaurante
-- Ejecutar UNA SOLA VEZ sobre la base existente, después de respaldarla. Compatible MySQL 8 / MariaDB 10.5+.
USE restaurante_rumbo_al_cielo;

ALTER TABLE usuarios
  ADD COLUMN apellidos VARCHAR(150) NULL AFTER nombre,
  ADD COLUMN ci VARCHAR(30) NULL AFTER codigo_empleado,
  ADD COLUMN departamento VARCHAR(100) NULL AFTER ci,
  ADD UNIQUE KEY uq_usuarios_ci (ci);

ALTER TABLE familiares
  ADD COLUMN parentesco ENUM('PADRE','MADRE','CONYUGE','HIJO') NULL AFTER nombre,
  ADD COLUMN ci VARCHAR(30) NULL AFTER parentesco,
  ADD UNIQUE KEY uq_familiar_ci (ci);

ALTER TABLE reservas DROP INDEX uq_reserva_titular, DROP INDEX uq_reserva_familiar;

ALTER TABLE reservas
  MODIFY COLUMN estado ENUM('RESERVADO','CANCELADO','ENTREGADO','INASISTENCIA') NOT NULL DEFAULT 'RESERVADO',
  ADD COLUMN modalidad ENUM('PENSIONADO','ESPORADICO') NOT NULL DEFAULT 'ESPORADICO' AFTER tipo_beneficiario,
  ADD COLUMN tarifa DECIMAL(8,2) NOT NULL DEFAULT 18.00 AFTER modalidad,
  ADD COLUMN confirmado_por_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER tarifa,
  ADD COLUMN entregado_en DATETIME NULL AFTER fecha_cancelacion;

ALTER TABLE menus
  ADD COLUMN sopa VARCHAR(180) NULL AFTER fecha,
  ADD COLUMN plato_principal VARCHAR(180) NULL AFTER sopa,
  ADD COLUMN postre VARCHAR(180) NULL AFTER plato_principal;


-- Conserva las tarifas históricas del sistema anterior en las reservas ya creadas.
UPDATE reservas r
JOIN usuarios u ON u.id = r.usuario_id
SET r.modalidad = IF(u.tipo_cuenta='PENSIONADO','PENSIONADO','ESPORADICO'),
    r.tarifa = IF(u.tipo_cuenta='PENSIONADO',15.00,18.00);

CREATE TABLE IF NOT EXISTS inscripciones_pension (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  anio SMALLINT UNSIGNED NOT NULL,
  mes TINYINT UNSIGNED NOT NULL,
  estado ENUM('ACTIVA','CANCELADA') NOT NULL DEFAULT 'ACTIVA',
  registrado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  beneficiario_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=titular; ID de familiar si aplica',
  UNIQUE KEY uq_pension_beneficiario_mes (usuario_id, beneficiario_id, anio, mes),
  CONSTRAINT fk_pension_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracion_sistema (
  clave VARCHAR(80) PRIMARY KEY,
  valor VARCHAR(255) NOT NULL,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO configuracion_sistema (clave, valor) VALUES
 ('hora_limite','09:00'), ('tarifa_pensionado','15.00'), ('tarifa_esporadico','18.00'), ('minimo_pension','10')
ON DUPLICATE KEY UPDATE valor=VALUES(valor);

CREATE TABLE IF NOT EXISTS solicitudes_recuperacion (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 identificador VARCHAR(150) NOT NULL,
 solicitado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 estado ENUM('PENDIENTE','ATENDIDA') NOT NULL DEFAULT 'PENDIENTE',
 INDEX idx_recuperacion_estado (estado, solicitado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
