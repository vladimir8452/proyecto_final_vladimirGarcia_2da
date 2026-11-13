-- Migración: módulo PLATOS (catálogo) y relación Menú -> Plato.
-- Ejecutar UNA SOLA VEZ sobre la base existente, después de respaldarla.
-- Compatible MySQL 8 / MariaDB 10.5+.
-- No borra nada: las columnas de texto anteriores (sopa, plato_principal,
-- postre) quedan como LEGACY hasta que el código use los platos.
USE restaurante_rumbo_al_cielo;

-- 1. Catálogo de platos
CREATE TABLE IF NOT EXISTS platos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(180) NOT NULL,
  tipo        ENUM('SOPA','PRINCIPAL','POSTRE') NOT NULL,
  descripcion VARCHAR(255) NULL,
  activo      TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_plato_nombre_tipo (nombre, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. El menú referencia tres platos del catálogo
ALTER TABLE menus
  ADD COLUMN sopa_id      INT UNSIGNED NULL AFTER descripcion_menu,
  ADD COLUMN principal_id INT UNSIGNED NULL AFTER sopa_id,
  ADD COLUMN postre_id    INT UNSIGNED NULL AFTER principal_id,
  ADD CONSTRAINT fk_menu_sopa      FOREIGN KEY (sopa_id)      REFERENCES platos(id),
  ADD CONSTRAINT fk_menu_principal FOREIGN KEY (principal_id) REFERENCES platos(id),
  ADD CONSTRAINT fk_menu_postre    FOREIGN KEY (postre_id)    REFERENCES platos(id);

-- 3. Convierte los textos existentes en platos del catálogo
INSERT IGNORE INTO platos (nombre, tipo)
  SELECT DISTINCT TRIM(sopa), 'SOPA' FROM menus
  WHERE sopa IS NOT NULL AND TRIM(sopa) <> '';
INSERT IGNORE INTO platos (nombre, tipo)
  SELECT DISTINCT TRIM(plato_principal), 'PRINCIPAL' FROM menus
  WHERE plato_principal IS NOT NULL AND TRIM(plato_principal) <> '';
INSERT IGNORE INTO platos (nombre, tipo)
  SELECT DISTINCT TRIM(postre), 'POSTRE' FROM menus
  WHERE postre IS NOT NULL AND TRIM(postre) <> '';

-- 4. Enlaza cada menú existente con sus platos
UPDATE menus m JOIN platos p ON p.nombre = TRIM(m.sopa) AND p.tipo = 'SOPA'
  SET m.sopa_id = p.id;
UPDATE menus m JOIN platos p ON p.nombre = TRIM(m.plato_principal) AND p.tipo = 'PRINCIPAL'
  SET m.principal_id = p.id;
UPDATE menus m JOIN platos p ON p.nombre = TRIM(m.postre) AND p.tipo = 'POSTRE'
  SET m.postre_id = p.id;
