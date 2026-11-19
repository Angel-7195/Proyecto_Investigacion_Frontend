<?php

declare(strict_types=1);

$metodosRol = [
    '/roles' => 'GET',
    '/roles/nuevo' => 'GET',
    '/roles/editar' => 'GET',
    '/roles/confirmar-retiro' => 'GET',
    '/roles/crear' => 'POST',
    '/roles/actualizar' => 'POST',
    '/roles/retirar' => 'POST',
];

if (!isset($metodosRol[$ruta])) {
    return;
}

require_once __DIR__ . '/../vistas/rol/lista.php';
require_once __DIR__ . '/../vistas/rol/formulario.php';

header('Cache-Control: no-store, private');
$rolesSesion = $_SESSION['usuario']['roles'] ?? [];
if (!is_array($rolesSesion) || !in_array('admin', $rolesSesion, true)) {
    http_response_code(403);
    mostrarErrorRol('El catálogo de roles es exclusivo del administrador.');
    exit;
}
if ($metodo !== $metodosRol[$ruta]) {
    header('Allow: ' . $metodosRol[$ruta]);
    http_response_code(405);
    exit;
}

$_SESSION['csrf_catalogo_rol'] ??= bin2hex(random_bytes(32));
$csrfRol = $_SESSION['csrf_catalogo_rol'];

if ($ruta === '/roles') {
    $resultadoRol = adaptarRespuestaApi(llamarApi('GET', 'rol'));
    if (!$resultadoRol['correcta']) {
        mostrarErrorApiRol($resultadoRol);
        exit;
    }

    $mensajeRol = $_SESSION['mensaje_catalogo_rol'] ?? '';
    unset($_SESSION['mensaje_catalogo_rol']);
    mostrarListaRoles($resultadoRol['datos']['datos'] ?? [], $mensajeRol);
    exit;
}

if ($ruta === '/roles/nuevo') {
    mostrarFormularioRol([], $csrfRol);
    exit;
}

if ($metodo === 'POST') {
    $tokenRol = $_POST['csrf_token'] ?? null;
    if (!is_string($tokenRol) || !hash_equals($csrfRol, $tokenRol)) {
        http_response_code(403);
        mostrarErrorRol('El formulario venció. Vuelve al listado y abre el formulario.');
        exit;
    }
}

$idRol = '';
if ($ruta !== '/roles/crear') {
    $entradaRol = $metodo === 'GET' ? $_GET : $_POST;
    $idRol = filter_var($entradaRol['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!is_int($idRol)) {
        http_response_code(400);
        mostrarErrorRol('El identificador del rol no es válido.');
        exit;
    }
}

if ($metodo === 'GET') {
    $resultadoRol = adaptarRespuestaApi(llamarApi('GET', 'rol', (string) $idRol));
    $registroRol = $resultadoRol['datos']['datos'] ?? null;
    if (!$resultadoRol['correcta'] || !is_array($registroRol)) {
        mostrarErrorApiRol($resultadoRol);
        exit;
    }

    if ($ruta === '/roles/confirmar-retiro') {
        mostrarConfirmacionRetiroRol($registroRol, $csrfRol);
    } else {
        mostrarFormularioRol($registroRol, $csrfRol);
    }
    exit;
}

if ($ruta === '/roles/retirar') {
    $resultadoRol = adaptarRespuestaApi(llamarApi('DELETE', 'rol', (string) $idRol));
    $mensajeRol = 'Rol retirado correctamente.';
} else {
    $nombreRol = $_POST['nombre'] ?? '';
    $descripcionRol = $_POST['descripcion'] ?? '';
    if (!is_string($nombreRol) || !is_string($descripcionRol)) {
        http_response_code(422);
        mostrarErrorRol('El nombre y la descripción deben ser texto.');
        exit;
    }

    $datosRol = [
        'nombre' => trim($nombreRol),
        'descripcion' => $descripcionRol === '' ? null : $descripcionRol,
    ];
    $creandoRol = $ruta === '/roles/crear';
    $resultadoRol = adaptarRespuestaApi(llamarApi(
        $creandoRol ? 'POST' : 'PUT',
        'rol',
        (string) $idRol,
        $datosRol
    ));
    $mensajeRol = $creandoRol ? 'Rol creado correctamente.' : 'Rol actualizado correctamente.';

    if (in_array($resultadoRol['estado'], [409, 422], true)) {
        http_response_code($resultadoRol['estado']);
        mostrarFormularioRol(
            $creandoRol ? $datosRol : $datosRol + ['id' => $idRol],
            $csrfRol,
            $resultadoRol['datos']['errores'] ?? [$resultadoRol['datos']['mensaje'] ?? 'Revisa los datos.']
        );
        exit;
    }
}

if (!$resultadoRol['correcta']) {
    mostrarErrorApiRol($resultadoRol);
    exit;
}

$_SESSION['mensaje_catalogo_rol'] = $mensajeRol;
header('Location: /roles', true, 303);
exit;
