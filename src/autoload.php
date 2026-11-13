<?php
declare(strict_types=1);

/**
 * Autoload mínimo (PSR-4) para ejecutar el sistema sin Composer.
 * Composer (vendor/autoload.php) solo se usa para las pruebas.
 */
spl_autoload_register(function (string $clase): void {
    $prefijo = 'RumboAlCielo\\';
    if (strncmp($clase, $prefijo, strlen($prefijo)) !== 0) {
        return;
    }
    $ruta = __DIR__ . '/' . str_replace('\\', '/', substr($clase, strlen($prefijo))) . '.php';
    if (is_file($ruta)) {
        require $ruta;
    }
});
