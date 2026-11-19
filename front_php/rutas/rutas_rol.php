<?php

declare(strict_types=1);

// El catálogo de roles únicamente permite consultar.
$metodosRol = [
    '/roles' => 'GET',
];

if (!isset($metodosRol[$ruta])) {
    return;
}

require_once __DIR__ . '/../vistas/rol/lista.php';

header('Cache-Control: no-store, private');

// El catálogo sigue siendo exclusivo del administrador.
$rolesSesion = $_SESSION['usuario']['roles'] ?? [];

if (!is_array($rolesSesion) || !in_array('admin', $rolesSesion, true)) {
    http_response_code(403);
    mostrarErrorRol('El catálogo de roles es exclusivo del administrador.');
    exit;
}

// Solo se permite el método GET.
if ($metodo !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    mostrarErrorRol('El catálogo de roles solo permite consultar.');
    exit;
}

// Consultar los roles activos desde la API.
$resultadoRol = adaptarRespuestaApi(llamarApi('GET', 'rol'));

if (!$resultadoRol['correcta']) {
    mostrarErrorApiRol($resultadoRol);
    exit;
}

// Mostrar el catálogo.
mostrarListaRoles($resultadoRol['datos']['datos'] ?? []);

exit;
