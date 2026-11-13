-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 13-11-2026 a las 06:16:07
-- Versión del servidor: 10.11.16-MariaDB
-- Versión de PHP: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `restaurante_rumbo_al_cielo`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calendario_operativo`
--

CREATE TABLE `calendario_operativo` (
  `id` int(10) UNSIGNED NOT NULL,
  `fecha` date NOT NULL,
  `es_feriado` tinyint(1) NOT NULL DEFAULT 0,
  `descripcion_feriado` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `calendario_operativo`
--

INSERT INTO `calendario_operativo` (`id`, `fecha`, `es_feriado`, `descripcion_feriado`) VALUES
(1, '2026-11-02', 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_sistema`
--

CREATE TABLE `configuracion_sistema` (
  `clave` varchar(80) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion_sistema`
--

INSERT INTO `configuracion_sistema` (`clave`, `valor`, `actualizado_en`) VALUES
('hora_limite', '09:00', '2026-11-05 20:20:49'),
('minimo_pension', '10', '2026-11-05 20:20:49'),
('tarifa_esporadico', '18.00', '2026-11-05 20:20:49'),
('tarifa_pensionado', '15.00', '2026-11-05 20:20:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `familiares`
--

CREATE TABLE `familiares` (
  `id` int(10) UNSIGNED NOT NULL,
  `empleado_titular_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `parentesco` enum('PADRE','MADRE','CONYUGE','HIJO') DEFAULT NULL,
  `ci` varchar(30) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `familiares`
--

INSERT INTO `familiares` (`id`, `empleado_titular_id`, `nombre`, `parentesco`, `ci`, `estado`, `created_at`) VALUES
(1, 2, 'ana montaño', NULL, NULL, 'ACTIVO', '2026-11-05 22:05:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones_pension`
--

CREATE TABLE `inscripciones_pension` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `anio` smallint(5) UNSIGNED NOT NULL,
  `mes` tinyint(3) UNSIGNED NOT NULL,
  `estado` enum('ACTIVA','CANCELADA') NOT NULL DEFAULT 'ACTIVA',
  `registrado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `beneficiario_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=titular; ID de familiar si aplica'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inscripciones_pension`
--

INSERT INTO `inscripciones_pension` (`id`, `usuario_id`, `anio`, `mes`, `estado`, `registrado_en`, `beneficiario_id`) VALUES
(1, 2, 2026, 12, 'ACTIVA', '2026-11-11 00:41:03', 0),
(3, 3, 2026, 12, 'ACTIVA', '2026-11-11 19:05:25', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menus`
--

CREATE TABLE `menus` (
  `id` int(10) UNSIGNED NOT NULL,
  `fecha` date NOT NULL,
  `sopa` varchar(180) DEFAULT NULL,
  `plato_principal` varchar(180) DEFAULT NULL,
  `postre` varchar(180) DEFAULT NULL,
  `descripcion_menu` text NOT NULL,
  `sopa_id` int(10) UNSIGNED DEFAULT NULL,
  `principal_id` int(10) UNSIGNED DEFAULT NULL,
  `postre_id` int(10) UNSIGNED DEFAULT NULL,
  `publicado` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `menus`
--

INSERT INTO `menus` (`id`, `fecha`, `sopa`, `plato_principal`, `postre`, `descripcion_menu`, `sopa_id`, `principal_id`, `postre_id`, `publicado`, `created_by`, `created_at`) VALUES
(1, '2026-11-02', 'sopa mani', 'picante de pollo', 'gelatina', 'Sopa: sopa mani\nPlato principal: picante de pollo\nPostre: gelatina', 1, 16, 31, 1, 1, '2026-11-05 22:19:42'),
(2, '2026-11-03', 'sopa de verduras', 'asado', 'platano', 'Sopa: sopa de verduras\nPlato principal: asado\nPostre: platano', 2, 17, 32, 1, 1, '2026-11-05 22:19:42'),
(3, '2026-11-04', 'sopa fiedo', 'silpancho', 'yogur', 'Sopa: sopa fiedo\nPlato principal: silpancho\nPostre: yogur', 3, 18, 33, 1, 1, '2026-11-05 22:19:42'),
(4, '2026-11-05', 'sopa de pollo', 'milanesa', 'flan', 'Sopa: sopa de pollo\nPlato principal: milanesa\nPostre: flan', 4, 19, 34, 1, 1, '2026-11-05 22:19:42'),
(5, '2026-11-09', 'sopa verdura', 'majadito', 'gelatina', 'Sopa: sopa verdura\nPlato principal: majadito\nPostre: gelatina', 5, 20, 31, 1, 1, '2026-11-05 22:55:18'),
(6, '2026-11-10', 'sopa de pata', 'pollo dorado', 'platano', 'Sopa: sopa de pata\nPlato principal: pollo dorado\nPostre: platano', 6, 21, 32, 1, 1, '2026-11-05 22:55:18'),
(7, '2026-11-11', 'sopa de pescado', 'filete de pescado', 'naranja', 'Sopa: sopa de pescado\nPlato principal: filete de pescado\nPostre: naranja', 7, 22, 35, 1, 1, '2026-11-05 22:55:18'),
(8, '2026-11-12', 'sopa de pata', 'chuleta de cerdo', 'gelatina', 'Sopa: sopa de pata\nPlato principal: chuleta de cerdo\nPostre: gelatina', 6, 23, 31, 1, 1, '2026-11-05 22:55:18'),
(13, '2026-11-16', 'sopa de mani', 'asado', 'gelatina', 'Sopa: sopa de mani\nPlato principal: asado\nPostre: gelatina', 8, 17, 31, 1, 1, '2026-11-11 18:58:09'),
(14, '2026-11-17', 'sopa de verduras', 'majadito', 'platano', 'Sopa: sopa de verduras\nPlato principal: majadito\nPostre: platano', 2, 20, 32, 1, 1, '2026-11-11 18:58:09'),
(15, '2026-11-18', 'sopa de trigo', 'silpancho', 'flan', 'Sopa: sopa de trigo\nPlato principal: silpancho\nPostre: flan', 9, 18, 34, 1, 1, '2026-11-11 18:58:09'),
(16, '2026-11-19', 'sopa de fideo', 'pescado frito', 'naranja', 'Sopa: sopa de fideo\nPlato principal: pescado frito\nPostre: naranja', 10, 24, 35, 1, 1, '2026-11-11 18:58:09'),
(17, '2026-11-23', 'sopa de quinua', 'milanesa', 'yogurt', 'Sopa: sopa de quinua\nPlato principal: milanesa\nPostre: yogurt', 11, 19, 36, 1, 1, '2026-11-11 19:00:15'),
(18, '2026-11-24', 'sopa de lenteja', 'pipocas de pollo', 'naranja', 'Sopa: sopa de lenteja\nPlato principal: pipocas de pollo\nPostre: naranja', 12, 25, 35, 1, 1, '2026-11-11 19:00:15'),
(19, '2026-11-25', 'sopa de cabello de angel', 'albondiga', 'platano', 'Sopa: sopa de cabello de angel\nPlato principal: albondiga\nPostre: platano', 13, 26, 32, 1, 1, '2026-11-11 19:00:15'),
(20, '2026-11-26', 'sopa de verdura', 'pollo broaster', 'gelatina', 'Sopa: sopa de verdura\nPlato principal: pollo broaster\nPostre: gelatina', 14, 27, 31, 1, 1, '2026-11-11 19:00:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `platos`
--

CREATE TABLE `platos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(180) NOT NULL,
  `tipo` enum('SOPA','PRINCIPAL','POSTRE') NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `platos`
--

INSERT INTO `platos` (`id`, `nombre`, `tipo`, `descripcion`, `activo`, `created_at`) VALUES
(1, 'sopa mani', 'SOPA', NULL, 1, '2026-11-13 00:02:20'),
(2, 'sopa de verduras', 'SOPA', NULL, 1, '2026-11-13 00:02:20'),
(3, 'sopa fiedo', 'SOPA', NULL, 1, '2026-11-13 00:02:20'),
(4, 'sopa de pollo', 'SOPA', NULL, 1, '2026-11-13 00:02:20'),
(5, 'sopa verdura', 'SOPA', NULL, 1, '2026-11-13 00:02:20'),
(6, 'sopa de pata', 'SOPA', NULL, 1, '2026-11-13 00:02:20'),
(7, 'sopa de pescado', 'SOPA', NULL, 1, '2026-11-13 00:02:20'),
(8, 'sopa de mani', 'SOPA', NULL, 1, '2026-11-13 00:02:20'),
(9, 'sopa de trigo', 'SOPA', NULL, 1, '2026-11-13 00:02:20'),
(10, 'sopa de fideo', 'SOPA', NULL, 1, '2026-11-13 00:02:20'),
(11, 'sopa de quinua', 'SOPA', NULL, 1, '2026-11-13 00:02:20'),
(12, 'sopa de lenteja', 'SOPA', NULL, 1, '2026-11-13 00:02:20'),
(13, 'sopa de cabello de angel', 'SOPA', NULL, 1, '2026-11-13 00:02:20'),
(14, 'sopa de verdura', 'SOPA', NULL, 1, '2026-11-13 00:02:20'),
(16, 'picante de pollo', 'PRINCIPAL', NULL, 1, '2026-11-13 00:02:41'),
(17, 'asado', 'PRINCIPAL', NULL, 1, '2026-11-13 00:02:41'),
(18, 'silpancho', 'PRINCIPAL', NULL, 1, '2026-11-13 00:02:41'),
(19, 'milanesa', 'PRINCIPAL', NULL, 1, '2026-11-13 00:02:41'),
(20, 'majadito', 'PRINCIPAL', NULL, 1, '2026-11-13 00:02:41'),
(21, 'pollo dorado', 'PRINCIPAL', NULL, 1, '2026-11-13 00:02:41'),
(22, 'filete de pescado', 'PRINCIPAL', NULL, 1, '2026-11-13 00:02:41'),
(23, 'chuleta de cerdo', 'PRINCIPAL', NULL, 1, '2026-11-13 00:02:41'),
(24, 'pescado frito', 'PRINCIPAL', NULL, 1, '2026-11-13 00:02:41'),
(25, 'pipocas de pollo', 'PRINCIPAL', NULL, 1, '2026-11-13 00:02:41'),
(26, 'albondiga', 'PRINCIPAL', NULL, 1, '2026-11-13 00:02:41'),
(27, 'pollo broaster', 'PRINCIPAL', NULL, 1, '2026-11-13 00:02:41'),
(31, 'gelatina', 'POSTRE', NULL, 1, '2026-11-13 00:02:59'),
(32, 'platano', 'POSTRE', NULL, 1, '2026-11-13 00:02:59'),
(33, 'yogur', 'POSTRE', NULL, 1, '2026-11-13 00:02:59'),
(34, 'flan', 'POSTRE', NULL, 1, '2026-11-13 00:02:59'),
(35, 'naranja', 'POSTRE', NULL, 1, '2026-11-13 00:02:59'),
(36, 'yogurt', 'POSTRE', NULL, 1, '2026-11-13 00:02:59'),
(38, 'trucha', 'PRINCIPAL', 'asada', 1, '2026-11-13 00:10:42'),
(39, 'pata', 'SOPA', NULL, 1, '2026-11-13 00:10:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes_mensuales`
--

CREATE TABLE `reportes_mensuales` (
  `id` int(10) UNSIGNED NOT NULL,
  `anio` year(4) NOT NULL,
  `mes` tinyint(4) NOT NULL,
  `codigo_empleado` varchar(50) NOT NULL,
  `nombre_empleado` varchar(150) NOT NULL,
  `almuerzos_titular` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `monto_titular` decimal(10,2) NOT NULL DEFAULT 0.00,
  `detalle_familiares` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`detalle_familiares`)),
  `almuerzos_familiares` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `penalizacion_familiares` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_descuento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `generado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reportes_mensuales`
--

INSERT INTO `reportes_mensuales` (`id`, `anio`, `mes`, `codigo_empleado`, `nombre_empleado`, `almuerzos_titular`, `monto_titular`, `detalle_familiares`, `almuerzos_familiares`, `penalizacion_familiares`, `total_descuento`, `generado_en`) VALUES
(1, '2026', 11, '001', 'vladimir', 3, 54.00, '[{\"nombre\":\"ana montaño\",\"almuerzos\":4,\"monto\":72,\"penalizacion\":0,\"total_familiar\":72},{\"nombre\":\"Invitados\",\"almuerzos\":7,\"monto\":126,\"penalizacion\":0,\"total_familiar\":126}]', 11, 0.00, 252.00, '2026-11-11 19:04:45'),
(3, '2026', 11, '002', 'manuel', 0, 0.00, '[]', 0, 0.00, 0.00, '2026-11-11 19:04:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

CREATE TABLE `reservas` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `familiar_id` int(10) UNSIGNED DEFAULT NULL,
  `tipo_beneficiario` enum('TITULAR','FAMILIAR') NOT NULL,
  `modalidad` enum('PENSIONADO','ESPORADICO') NOT NULL DEFAULT 'ESPORADICO',
  `tarifa` decimal(8,2) NOT NULL DEFAULT 18.00,
  `confirmado_por_admin` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_reserva` date NOT NULL,
  `estado` enum('RESERVADO','CANCELADO','ENTREGADO','INASISTENCIA') NOT NULL DEFAULT 'RESERVADO',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_cancelacion` datetime DEFAULT NULL,
  `entregado_en` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reservas`
--

INSERT INTO `reservas` (`id`, `usuario_id`, `familiar_id`, `tipo_beneficiario`, `modalidad`, `tarifa`, `confirmado_por_admin`, `fecha_reserva`, `estado`, `fecha_creacion`, `fecha_cancelacion`, `entregado_en`) VALUES
(1, 2, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-01', 'RESERVADO', '2026-11-05 22:05:26', NULL, NULL),
(2, 2, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-02', 'RESERVADO', '2026-11-05 22:05:26', NULL, NULL),
(3, 2, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-03', 'RESERVADO', '2026-11-05 22:05:26', NULL, NULL),
(4, 2, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-07', 'RESERVADO', '2026-11-05 22:05:26', NULL, NULL),
(5, 2, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-08', 'RESERVADO', '2026-11-05 22:05:26', NULL, NULL),
(6, 2, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-09', 'RESERVADO', '2026-11-05 22:05:26', NULL, NULL),
(7, 2, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-10', 'RESERVADO', '2026-11-05 22:05:26', NULL, NULL),
(8, 2, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-14', 'RESERVADO', '2026-11-05 22:05:26', NULL, NULL),
(9, 2, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-15', 'RESERVADO', '2026-11-05 22:05:26', NULL, NULL),
(10, 2, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-16', 'RESERVADO', '2026-11-05 22:05:26', NULL, NULL),
(11, 2, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-17', 'RESERVADO', '2026-11-05 22:05:26', NULL, NULL),
(12, 2, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-21', 'RESERVADO', '2026-11-05 22:05:26', NULL, NULL),
(13, 2, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-22', 'RESERVADO', '2026-11-05 22:05:26', NULL, NULL),
(14, 2, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-23', 'RESERVADO', '2026-11-05 22:05:26', NULL, NULL),
(15, 2, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-24', 'RESERVADO', '2026-11-05 22:05:26', NULL, NULL),
(16, 2, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-28', 'RESERVADO', '2026-11-05 22:05:26', NULL, NULL),
(17, 2, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-29', 'RESERVADO', '2026-11-05 22:05:26', NULL, NULL),
(18, 2, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-30', 'RESERVADO', '2026-11-05 22:05:26', NULL, NULL),
(19, 2, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-31', 'RESERVADO', '2026-11-05 22:05:26', NULL, NULL),
(20, 2, 1, 'FAMILIAR', 'ESPORADICO', 18.00, 1, '2026-11-09', 'RESERVADO', '2026-11-05 22:58:45', NULL, NULL),
(21, 2, 1, 'FAMILIAR', 'ESPORADICO', 18.00, 1, '2026-11-10', 'RESERVADO', '2026-11-05 22:58:58', NULL, NULL),
(22, 2, 1, 'FAMILIAR', 'ESPORADICO', 18.00, 1, '2026-11-11', 'ENTREGADO', '2026-11-05 22:59:06', NULL, '2026-11-11 00:15:30'),
(23, 2, NULL, 'TITULAR', 'ESPORADICO', 18.00, 1, '2026-11-09', 'RESERVADO', '2026-11-05 22:59:56', NULL, NULL),
(24, 2, NULL, 'TITULAR', 'ESPORADICO', 18.00, 0, '2026-11-11', 'ENTREGADO', '2026-11-11 08:19:20', NULL, '2026-11-11 18:55:35'),
(25, 2, NULL, 'TITULAR', 'ESPORADICO', 18.00, 0, '2026-11-12', 'RESERVADO', '2026-11-11 08:19:29', NULL, NULL),
(26, 2, NULL, 'FAMILIAR', 'ESPORADICO', 18.00, 0, '2026-11-12', 'RESERVADO', '2026-11-11 08:20:35', NULL, NULL),
(27, 2, NULL, 'FAMILIAR', 'ESPORADICO', 18.00, 0, '2026-11-12', 'RESERVADO', '2026-11-11 08:23:54', NULL, NULL),
(28, 2, 1, 'FAMILIAR', 'ESPORADICO', 18.00, 0, '2026-11-12', 'RESERVADO', '2026-11-11 13:57:38', NULL, NULL),
(29, 2, NULL, 'FAMILIAR', 'ESPORADICO', 18.00, 0, '2026-11-12', 'RESERVADO', '2026-11-11 13:57:51', NULL, NULL),
(30, 2, NULL, 'FAMILIAR', 'ESPORADICO', 18.00, 0, '2026-11-12', 'RESERVADO', '2026-11-11 13:58:02', NULL, NULL),
(31, 2, NULL, 'FAMILIAR', 'ESPORADICO', 18.00, 0, '2026-11-12', 'RESERVADO', '2026-11-11 13:58:32', NULL, NULL),
(32, 2, NULL, 'FAMILIAR', 'ESPORADICO', 18.00, 0, '2026-11-12', 'RESERVADO', '2026-11-11 14:05:58', NULL, NULL),
(33, 2, NULL, 'FAMILIAR', 'ESPORADICO', 18.00, 0, '2026-11-12', 'RESERVADO', '2026-11-11 14:06:06', NULL, NULL),
(34, 3, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-01', 'RESERVADO', '2026-11-11 19:05:25', NULL, NULL),
(35, 3, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-02', 'RESERVADO', '2026-11-11 19:05:25', NULL, NULL),
(36, 3, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-03', 'RESERVADO', '2026-11-11 19:05:25', NULL, NULL),
(37, 3, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-07', 'RESERVADO', '2026-11-11 19:05:25', NULL, NULL),
(38, 3, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-08', 'RESERVADO', '2026-11-11 19:05:25', NULL, NULL),
(39, 3, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-09', 'RESERVADO', '2026-11-11 19:05:25', NULL, NULL),
(40, 3, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-10', 'RESERVADO', '2026-11-11 19:05:25', NULL, NULL),
(41, 3, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-14', 'RESERVADO', '2026-11-11 19:05:25', NULL, NULL),
(42, 3, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-15', 'RESERVADO', '2026-11-11 19:05:25', NULL, NULL),
(43, 3, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-16', 'RESERVADO', '2026-11-11 19:05:25', NULL, NULL),
(44, 3, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-17', 'RESERVADO', '2026-11-11 19:05:25', NULL, NULL),
(45, 3, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-21', 'RESERVADO', '2026-11-11 19:05:25', NULL, NULL),
(46, 3, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-22', 'RESERVADO', '2026-11-11 19:05:25', NULL, NULL),
(47, 3, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-23', 'RESERVADO', '2026-11-11 19:05:25', NULL, NULL),
(48, 3, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-24', 'RESERVADO', '2026-11-11 19:05:25', NULL, NULL),
(49, 3, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-28', 'RESERVADO', '2026-11-11 19:05:25', NULL, NULL),
(50, 3, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-29', 'RESERVADO', '2026-11-11 19:05:25', NULL, NULL),
(51, 3, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-30', 'RESERVADO', '2026-11-11 19:05:25', NULL, NULL),
(52, 3, NULL, 'TITULAR', 'PENSIONADO', 15.00, 0, '2026-12-31', 'RESERVADO', '2026-11-11 19:05:25', NULL, NULL),
(53, 3, NULL, 'FAMILIAR', 'ESPORADICO', 18.00, 0, '2026-11-12', 'RESERVADO', '2026-11-11 19:05:56', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_recuperacion`
--

CREATE TABLE `solicitudes_recuperacion` (
  `id` int(10) UNSIGNED NOT NULL,
  `identificador` varchar(150) NOT NULL,
  `solicitado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` enum('PENDIENTE','ATENDIDA') NOT NULL DEFAULT 'PENDIENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `solicitudes_recuperacion`
--

INSERT INTO `solicitudes_recuperacion` (`id`, `identificador`, `solicitado_en`, `estado`) VALUES
(1, 'manuelgarcia@gmail.com', '2026-11-11 19:10:58', 'ATENDIDA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `apellidos` varchar(150) DEFAULT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `correo` varchar(150) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `tipo_cuenta` enum('ADMIN','PENSIONADO','NO_PENSIONADO') NOT NULL,
  `codigo_empleado` varchar(50) DEFAULT NULL,
  `ci` varchar(30) DEFAULT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellidos`, `celular`, `correo`, `contrasena`, `tipo_cuenta`, `codigo_empleado`, `ci`, `departamento`, `activo`, `created_at`) VALUES
(1, 'Administrador', NULL, NULL, 'admin@rumboalcielo.bo', '$2y$10$LFuLaUg0/MqJMijgmjNKTOvJ5Jd9lXj3IMEm0c4kN2OR7mgH390Pu', 'ADMIN', NULL, NULL, NULL, 1, '2026-11-01 02:18:27'),
(2, 'vladimir', 'garcia', '60713091', 'vladimir.garcia@uab.edu.bo', '$2y$10$xeAVyfaiIRjR.8k4waI97eM5IgSkY0MUM0fwZGXdaNq8frK92nrPi', 'PENSIONADO', '001', '7938452', 'recursos humanos', 1, '2026-11-05 22:03:54'),
(3, 'manuel', 'garcia', '77777776', 'manuelgarcia@gmail.com', '$2y$10$SI6tMEapqxB7eK72A61bkOj1B/3Pixuwl.nVYhMxqM7xvetPlsBKK', 'PENSIONADO', '002', '123456', 'cochabamba', 1, '2026-11-11 19:01:57');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `calendario_operativo`
--
ALTER TABLE `calendario_operativo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fecha` (`fecha`);

--
-- Indices de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  ADD PRIMARY KEY (`clave`);

--
-- Indices de la tabla `familiares`
--
ALTER TABLE `familiares`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_familiar_ci` (`ci`),
  ADD KEY `idx_titular` (`empleado_titular_id`);

--
-- Indices de la tabla `inscripciones_pension`
--
ALTER TABLE `inscripciones_pension`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pension_beneficiario_mes` (`usuario_id`,`beneficiario_id`,`anio`,`mes`);

--
-- Indices de la tabla `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fecha` (`fecha`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `fk_menu_sopa` (`sopa_id`),
  ADD KEY `fk_menu_principal` (`principal_id`),
  ADD KEY `fk_menu_postre` (`postre_id`);

--
-- Indices de la tabla `platos`
--
ALTER TABLE `platos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_plato_nombre_tipo` (`nombre`,`tipo`);

--
-- Indices de la tabla `reportes_mensuales`
--
ALTER TABLE `reportes_mensuales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reporte` (`anio`,`mes`,`codigo_empleado`);

--
-- Indices de la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fecha` (`fecha_reserva`),
  ADD KEY `idx_usuario_id` (`usuario_id`),
  ADD KEY `idx_familiar_id` (`familiar_id`);

--
-- Indices de la tabla `solicitudes_recuperacion`
--
ALTER TABLE `solicitudes_recuperacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recuperacion_estado` (`estado`,`solicitado_en`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD UNIQUE KEY `codigo_empleado` (`codigo_empleado`),
  ADD UNIQUE KEY `uq_usuarios_ci` (`ci`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `calendario_operativo`
--
ALTER TABLE `calendario_operativo`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `familiares`
--
ALTER TABLE `familiares`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `inscripciones_pension`
--
ALTER TABLE `inscripciones_pension`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `platos`
--
ALTER TABLE `platos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT de la tabla `reportes_mensuales`
--
ALTER TABLE `reportes_mensuales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT de la tabla `solicitudes_recuperacion`
--
ALTER TABLE `solicitudes_recuperacion`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `familiares`
--
ALTER TABLE `familiares`
  ADD CONSTRAINT `familiares_ibfk_1` FOREIGN KEY (`empleado_titular_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `inscripciones_pension`
--
ALTER TABLE `inscripciones_pension`
  ADD CONSTRAINT `fk_pension_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `menus`
--
ALTER TABLE `menus`
  ADD CONSTRAINT `fk_menu_postre` FOREIGN KEY (`postre_id`) REFERENCES `platos` (`id`),
  ADD CONSTRAINT `fk_menu_principal` FOREIGN KEY (`principal_id`) REFERENCES `platos` (`id`),
  ADD CONSTRAINT `fk_menu_sopa` FOREIGN KEY (`sopa_id`) REFERENCES `platos` (`id`),
  ADD CONSTRAINT `menus_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reservas_ibfk_2` FOREIGN KEY (`familiar_id`) REFERENCES `familiares` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
