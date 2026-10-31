<?php

declare(strict_types=1);

/** Rutas HTML de ods_linea: dos identificadores INT y sin campos editables. */
require_once __DIR__ . '/../vistas/ods_linea/lista.php';
require_once __DIR__ . '/../vistas/ods_linea/formulario.php';

/** Retorna las dos claves como enteros positivos válidos de MariaDB (INT). */
function clavesOdsLinea(array $fuente): ?array
{
    $linea = $fuente['linea_investigacion'] ?? null;
    $ods = $fuente['ods'] ?? null;
    if (!is_scalar($linea) || !is_scalar($ods)
        || is_bool($linea) || is_bool($ods)) {
        return null;
    }
    $reglas = ['options' => ['min_range' => 1, 'max_range' => 2147483647]];
    $lineaId = filter_var((string) $linea, FILTER_VALIDATE_INT, $reglas);
    $odsId = filter_var((string) $ods, FILTER_VALIDATE_INT, $reglas);
    if ($lineaId === false || $odsId === false) {
        return null;
    }
    return [(int) $lineaId, (int) $odsId];
}

/** Los desplegables solo muestran padres activos recibidos desde la API. */
function opcionesOdsLinea(): array
{
    $respuestaLineas = listarLineasInvestigacion();
    $respuestaOds = listarObjetivosDesarrolloSostenibleOdsLinea();
    $lineas = $respuestaLineas['datos']['datos'] ?? [];
    $ods = $respuestaOds['datos']['datos'] ?? [];
    return [
        $respuestaLineas['correcta'] && is_array($lineas) ? $lineas : [],
        $respuestaOds['correcta'] && is_array($ods) ? $ods : [],
        $respuestaLineas['correcta'],
        $respuestaOds['correcta'],
    ];
}

function formularioConOpcionesOdsLinea(array $valores = [], array $errores = []): void
{
    [$lineas, $ods, $lineasDisponibles, $odsDisponibles] = opcionesOdsLinea();
    mostrarFormularioOdsLinea($valores, $errores, $lineas, $ods,
        $lineasDisponibles, $odsDisponibles);
}

function erroresRespuestaOdsLinea(array $respuesta): array
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

function redirigirOdsLinea(string $mensaje): never
{
    $_SESSION['mensaje_ods_linea'] = $mensaje;
    header('Location: /asociaciones-ods-linea', true, 303);
    exit;
}

// LISTADO
if ($ruta === '/asociaciones-ods-linea' && $metodo === 'GET') {
    mostrarListaOdsLinea();
    exit;
}

// FORMULARIO
if ($ruta === '/asociaciones-ods-linea/nuevo' && $metodo === 'GET') {
    formularioConOpcionesOdsLinea();
    exit;
}

// FICHA DE ASOCIACIÓN (GET de ambos segmentos de la PK en la API)
if ($ruta === '/asociaciones-ods-linea/ver' && $metodo === 'GET') {
    $claves = clavesOdsLinea($_GET);
    if ($claves === null) {
        http_response_code(400);
        mostrarNoEncontrada('Solicitud incorrecta', 'Indique una línea y un ODS válidos.');
        exit;
    }
    [$lineaId, $odsId] = $claves;
    $respuesta = obtenerAsociacionOdsLinea($lineaId, $odsId);
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
    [$lineas, $ods] = opcionesOdsLinea();
    mostrarDetalleOdsLinea($respuesta['datos'], $lineas, $ods);
    exit;
}

// CREACIÓN: la API comprueba FK activas y PK compuesta sin duplicados.
if ($ruta === '/asociaciones-ods-linea/crear' && $metodo === 'POST') {
    $valores = $_POST;
    $claves = clavesOdsLinea($_POST);
    if ($claves === null) {
        http_response_code(422);
        formularioConOpcionesOdsLinea($valores, [
            'Seleccione una línea de investigación y un ODS válidos.',
        ]);
        exit;
    }
    [$lineaId, $odsId] = $claves;
    $respuesta = crearAsociacionOdsLinea([
        'linea_investigacion' => $lineaId,
        'ods' => $odsId,
    ]);
    if ($respuesta['correcta']) {
        redirigirOdsLinea('Asociación registrada correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [400, 404, 409, 422], true)
        ? $respuesta['estado'] : 503);
    formularioConOpcionesOdsLinea($valores, erroresRespuestaOdsLinea($respuesta));
    exit;
}

// RETIRO: POST de formulario HTML -> DELETE lógico hacia la API.
if ($ruta === '/asociaciones-ods-linea/retirar' && $metodo === 'POST') {
    $claves = clavesOdsLinea($_POST);
    if ($claves === null) {
        http_response_code(400);
        mostrarListaOdsLinea(['Línea de investigación u ODS no válido.']);
        exit;
    }
    [$lineaId, $odsId] = $claves;
    $respuesta = retirarAsociacionOdsLinea($lineaId, $odsId);
    if ($respuesta['correcta']) {
        redirigirOdsLinea('Asociación retirada correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [404, 409], true)
        ? $respuesta['estado'] : 503);
    mostrarListaOdsLinea(erroresRespuestaOdsLinea($respuesta));
    exit;
}
