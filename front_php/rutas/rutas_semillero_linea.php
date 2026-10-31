<?php

declare(strict_types=1);

/** Rutas HTML de semillero_linea: dos identificadores INT, no editables. */
require_once __DIR__ . '/../vistas/semillero_linea/lista.php';
require_once __DIR__ . '/../vistas/semillero_linea/formulario.php';

/** Valida el orden de la PK: semillero / linea_investigacion. */
function clavesSemilleroLinea(array $fuente): ?array
{
    $semillero = $fuente['semillero'] ?? null;
    $linea = $fuente['linea_investigacion'] ?? null;
    if (!is_scalar($semillero) || !is_scalar($linea) || is_bool($semillero) || is_bool($linea)) {
        return null;
    }
    $reglas = ['options' => ['min_range' => 1, 'max_range' => 2147483647]];
    $semilleroId = filter_var((string) $semillero, FILTER_VALIDATE_INT, $reglas);
    $lineaId = filter_var((string) $linea, FILTER_VALIDATE_INT, $reglas);
    if ($semilleroId === false || $lineaId === false) {
        return null;
    }
    return [(int) $semilleroId, (int) $lineaId];
}

/** Obtiene los dos catálogos activos por HTTP, sin conectar a MariaDB. */
function opcionesSemilleroLinea(): array
{
    $respuestaSemilleros = listarSemilleros();
    $respuestaLineas = listarLineasInvestigacion();
    $semilleros = $respuestaSemilleros['datos']['datos'] ?? [];
    $lineas = $respuestaLineas['datos']['datos'] ?? [];
    return [
        $respuestaSemilleros['correcta'] && is_array($semilleros) ? $semilleros : [],
        $respuestaLineas['correcta'] && is_array($lineas) ? $lineas : [],
        $respuestaSemilleros['correcta'],
        $respuestaLineas['correcta'],
    ];
}

function formularioConOpcionesSemilleroLinea(array $valores = [], array $errores = []): void
{
    [$semilleros, $lineas, $semillerosDisponibles, $lineasDisponibles] = opcionesSemilleroLinea();
    mostrarFormularioSemilleroLinea($valores, $errores, $semilleros, $lineas, $semillerosDisponibles, $lineasDisponibles);
}

function erroresRespuestaSemilleroLinea(array $respuesta): array
{
    if (!empty($respuesta['error'])) {
        return [(string) $respuesta['error']];
    }
    $datos = $respuesta['datos'] ?? [];
    if (isset($datos['errores']) && is_array($datos['errores']) && $datos['errores'] !== []) {
        return array_map('strval', $datos['errores']);
    }
    $detalle = $datos['detalle'] ?? $datos['mensaje'] ?? null;
    return [is_string($detalle) && trim($detalle) !== ''
        ? $detalle : 'No fue posible completar la operación.'];
}

function redirigirSemilleroLinea(string $mensaje): never
{
    $_SESSION['mensaje_semillero_linea'] = $mensaje;
    header('Location: /asociaciones-semillero-linea', true, 303);
    exit;
}

// LISTADO
if ($ruta === '/asociaciones-semillero-linea' && $metodo === 'GET') {
    mostrarListaSemilleroLinea();
    exit;
}

// REGISTRO
if ($ruta === '/asociaciones-semillero-linea/nuevo' && $metodo === 'GET') {
    formularioConOpcionesSemilleroLinea();
    exit;
}

// DETALLE por los dos identificadores de la PK
if ($ruta === '/asociaciones-semillero-linea/ver' && $metodo === 'GET') {
    $claves = clavesSemilleroLinea($_GET);
    if ($claves === null) {
        http_response_code(400);
        mostrarNoEncontrada('Solicitud incorrecta', 'Indique un semillero y una línea válidos.');
        exit;
    }
    [$semilleroId, $lineaId] = $claves;
    $respuesta = obtenerAsociacionSemilleroLinea($semilleroId, $lineaId);
    if (!$respuesta['correcta']) {
        http_response_code($respuesta['estado'] === 404 ? 404 : 503);
        mostrarNoEncontrada(
            $respuesta['estado'] === 404 ? 'Asociación no encontrada' : 'Servicio no disponible',
            $respuesta['estado'] === 404
                ? 'La asociación no existe o ya fue retirada.'
                : 'No se pudo consultar la asociación en la API.'
        );
        exit;
    }
    [$semilleros, $lineas] = opcionesSemilleroLinea();
    mostrarDetalleSemilleroLinea($respuesta['datos'], $semilleros, $lineas);
    exit;
}

// CREAR; el servicio de la API comprueba las dos FK activas y la PK compuesta.
if ($ruta === '/asociaciones-semillero-linea/crear' && $metodo === 'POST') {
    $valores = $_POST;
    $claves = clavesSemilleroLinea($_POST);
    if ($claves === null) {
        http_response_code(422);
        formularioConOpcionesSemilleroLinea($valores, [
            'Seleccione un semillero y una línea de investigación válidos.',
        ]);
        exit;
    }
    [$semilleroId, $lineaId] = $claves;
    $respuesta = crearAsociacionSemilleroLinea([
        'semillero' => $semilleroId,
        'linea_investigacion' => $lineaId,
    ]);
    if ($respuesta['correcta']) {
        redirigirSemilleroLinea('Asociación registrada correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [400, 404, 409, 422], true)
        ? $respuesta['estado'] : 503);
    formularioConOpcionesSemilleroLinea($valores, erroresRespuestaSemilleroLinea($respuesta));
    exit;
}

// POST del formulario HTML -> DELETE lógico en la API
if ($ruta === '/asociaciones-semillero-linea/retirar' && $metodo === 'POST') {
    $claves = clavesSemilleroLinea($_POST);
    if ($claves === null) {
        http_response_code(400);
        mostrarListaSemilleroLinea(['Semillero o línea de investigación no válida.']);
        exit;
    }
    [$semilleroId, $lineaId] = $claves;
    $respuesta = retirarAsociacionSemilleroLinea($semilleroId, $lineaId);
    if ($respuesta['correcta']) {
        redirigirSemilleroLinea('Asociación retirada correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [404, 409], true)
        ? $respuesta['estado'] : 503);
    mostrarListaSemilleroLinea(erroresRespuestaSemilleroLinea($respuesta));
    exit;
}
