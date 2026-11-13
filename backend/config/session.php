<?php
/**
 * Gestión centralizada de sesiones
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica que el usuario esté autenticado; si no, redirige al login.
 */
function requireAuth(): void {
    if (empty($_SESSION['usuario_id'])) {
        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        header('Location: ' . $base . '/index.php');
        exit;
    }
}

/**
 * Verifica que el usuario autenticado tenga el rol requerido.
 * @param string|array $roles  Ej: 'ADMIN' o ['PENSIONADO','NO_PENSIONADO']
 */
function requireRole(string|array $roles): void {
    requireAuth();
    $roles = (array) $roles;
    if (!in_array($_SESSION['tipo_cuenta'] ?? '', $roles, true)) {
        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        header('Location: ' . $base . '/index.php?error=acceso_denegado');
        exit;
    }
}

/**
 * Devuelve true si el usuario está logueado.
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['usuario_id']);
}

/**
 * Retorna el tipo de cuenta de la sesión activa.
 */
function rolActual(): string {
    return $_SESSION['tipo_cuenta'] ?? '';
}
