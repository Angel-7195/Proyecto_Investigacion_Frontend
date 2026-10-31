<?php

declare(strict_types=1);

/** Rutas HTML de aa_linea: dos identificadores INT, no editables. */
require_once __DIR__ . '/../vistas/aa_linea/lista.php';
require_once __DIR__ . '/../vistas/aa_linea/formulario.php';

/** Valida el orden de la PK: area_aplicacion / linea_investigacion. */
function clavesAaLinea(array $fuente): ?array
{
    $area = $fuente['area_aplicacion'] ?? null;
    $linea = $fuente['linea_investigacion'] ?? null;
    if (!is_scalar($area) || !is_scalar($linea) || is_bool($area) || is_bool($linea)) {
        return null;
    }
    $reglas = ['options' => ['min_range' => 1, 'max_range' => 2147483647]];
    $areaId = filter_var((string) $area, FILTER_VALIDATE_INT, $reglas);
    $lineaId = filter_var((string) $linea, FILTER_VALIDATE_INT, $reglas);
    if ($areaId === false || $lineaId === false) {
        return null;
    }
    return [(int) $areaId, (int) $lineaId];
}

/** Obtiene los dos catálogos activos por HTTP, sin conectar a MariaDB. */
function opcionesAaLinea(): array
{
    $respuestaAreas = listarAreasAplicacionAaLinea();
    $respuestaLineas = listarLineasInvestigacion();
    $areas = $respuestaAreas['datos']['datos'] ?? [];
    $lineas = $respuestaLineas['datos']['datos'] ?? [];
    return [
        $respuestaAreas['correcta'] && is_array($areas) ? $areas : [],
        $respuestaLineas['correcta'] && is_array($lineas) ? $lineas : [],
        $respuestaAreas['correcta'],
        $respuestaLineas['correcta'],
    ];
}

function formularioConOpcionesAaLinea(array $valores = [], array $errores = []): void
{
    [$areas, $lineas, $areasDisponibles, $lineasDisponibles] = opcionesAaLinea();
    mostrarFormularioAaLinea($valores, $errores, $areas, $lineas, $areasDisponibles, $lineasDisponibles);
}

function erroresRespuestaAaLinea(array $respuesta): array
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

function redirigirAaLinea(string $mensaje): never
{
    $_SESSION['mensaje_aa_linea'] = $mensaje;
    header('Location: /asociaciones-aa-linea', true, 303);
    exit;
}

// LISTADO
if ($ruta === '/asociaciones-aa-linea' && $metodo === 'GET') {
    mostrarListaAaLinea();
    exit;
}

// REGISTRO
if ($ruta === '/asociaciones-aa-linea/nuevo' && $metodo === 'GET') {
    formularioConOpcionesAaLinea();
    exit;
}

// DETALLE por los dos identificadores de la PK
if ($ruta === '/asociaciones-aa-linea/ver' && $metodo === 'GET') {
    $claves = clavesAaLinea($_GET);
    if ($claves === null) {
        http_response_code(400);
        mostrarNoEncontrada('Solicitud incorrecta', 'Indique un área y una línea válidas.');
        exit;
    }
    [$areaId, $lineaId] = $claves;
    $respuesta = obtenerAsociacionAaLinea($areaId, $lineaId);
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
    [$areas, $lineas] = opcionesAaLinea();
    mostrarDetalleAaLinea($respuesta['datos'], $areas, $lineas);
    exit;
}

// CREAR; el servicio de la API comprueba las dos FK activas y la PK compuesta.
if ($ruta === '/asociaciones-aa-linea/crear' && $metodo === 'POST') {
    $valores = $_POST;
    $claves = clavesAaLinea($_POST);
    if ($claves === null) {
        http_response_code(422);
        formularioConOpcionesAaLinea($valores, [
            'Seleccione un área de aplicación y una línea de investigación válidas.',
        ]);
        exit;
    }
    [$areaId, $lineaId] = $claves;
    $respuesta = crearAsociacionAaLinea([
        'area_aplicacion' => $areaId,
        'linea_investigacion' => $lineaId,
    ]);
    if ($respuesta['correcta']) {
        redirigirAaLinea('Asociación registrada correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [400, 404, 409, 422], true)
        ? $respuesta['estado'] : 503);
    formularioConOpcionesAaLinea($valores, erroresRespuestaAaLinea($respuesta));
    exit;
}

// POST del formulario HTML -> DELETE lógico en la API
if ($ruta === '/asociaciones-aa-linea/retirar' && $metodo === 'POST') {
    $claves = clavesAaLinea($_POST);
    if ($claves === null) {
        http_response_code(400);
        mostrarListaAaLinea(['Área de aplicación o línea de investigación no válida.']);
        exit;
    }
    [$areaId, $lineaId] = $claves;
    $respuesta = retirarAsociacionAaLinea($areaId, $lineaId);
    if ($respuesta['correcta']) {
        redirigirAaLinea('Asociación retirada correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [404, 409], true)
        ? $respuesta['estado'] : 503);
    mostrarListaAaLinea(erroresRespuestaAaLinea($respuesta));
    exit;
}
