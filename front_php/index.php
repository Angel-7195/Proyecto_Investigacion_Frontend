<?php

/**
 * index.php — Front Controller del frontend de Investigación v1.
 */

declare(strict_types=1);

$metodo = $_SERVER['REQUEST_METHOD'];
$ruta = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

// Los archivos estáticos deben ser servidos directamente por PHP.
if (PHP_SAPI === 'cli-server') {
    $archivo = __DIR__ . $ruta;

    if ($ruta !== '/' && is_file($archivo)) {
        return false;
    }
}

session_start();

if ($ruta === '/' && $metodo === 'GET') {
    echo '<!DOCTYPE html>';
    echo '<html lang="es">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Investigación</title>';
    echo '</head>';
    echo '<body>';
    echo '<h1>Módulo de Investigación</h1>';
    echo '<p>Frontend v1 funcionando.</p>';
    echo '</body>';
    echo '</html>';
    exit;
}

http_response_code(404);

echo '<h1>404</h1>';
echo '<p>Página no encontrada.</p>';