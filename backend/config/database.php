<?php
/**
 * Configuración de conexión a la base de datos
 * Restaurante "Rumbo al Cielo" - Unión Boliviana
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'restaurante_rumbo_al_cielo');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

date_default_timezone_set('America/La_Paz');

// Constantes del sistema
define('HORA_LIMITE_CANCELACION', '09:00'); // HH:MM
define('TARIFA_NO_PENSIONADO', 18.00);       // Bs
define('TARIFA_PENSIONADO', 15.00);          // Bs
define('MINIMO_ALMUERZOS_MES', 10);          // almuerzos mínimos mensuales

// ── BASE_URL ──────────────────────────────────────────────────────────────────
// Detecta automáticamente si el proyecto está en la raíz del servidor o en
// una subcarpeta (ej: http://localhost/Sistema%20Restaurante%20F/).
// Todas las rutas AJAX del frontend usan BASE_URL como prefijo.
if (!defined('BASE_URL')) {
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Normalizar rutas a barras forward para comparar en Windows
    $docRoot  = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    // database.php está en /backend/config/ → dirname(__DIR__, 2) = raíz del proyecto
    $projRoot = rtrim(str_replace('\\', '/', dirname(__DIR__, 2)), '/');
    // Subcarpeta relativa al document root (vacío si está en la raíz)
    $subPath  = ltrim(str_replace($docRoot, '', $projRoot), '/');
    $basePath = $subPath ? '/' . $subPath : '';
    define('BASE_URL', $scheme . '://' . $host . $basePath);
}
// ─────────────────────────────────────────────────────────────────────────────

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = 'mysql:host=' . DB_HOST
                 . ';dbname=' . DB_NAME
                 . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
        }
        return self::$instance;
    }

    // Evitar clonación del singleton
    private function __clone() {}
}
