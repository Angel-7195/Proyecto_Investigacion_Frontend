<?php

declare(strict_types=1);

/** Rutas HTML de ac_linea, asociación de clave compuesta INT + VARCHAR(6). */
require_once __DIR__ . '/../vistas/ac_linea/lista.php';
require_once __DIR__ . '/../vistas/ac_linea/formulario.php';

/** La línea debe ser un ID INT válido; el área debe conservar su ID VARCHAR(6). */
function clavesAcLinea(array $fuente): ?array
{
    $id = $fuente['linea_investigacion'] ?? null;
    $area = $fuente['area_conocimiento'] ?? null;
    if (!is_scalar($id) || !is_string($area)) {
        return null;
    }
    $lineaId = filter_var((string) $id, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 2147483647],
    ]);
    $areaId = trim($area);
    $longitud = function_exists('mb_strlen')
        ? mb_strlen($areaId, 'UTF-8') : (preg_match_all('/./us', $areaId) ?: 0);
    if ($lineaId === false || $longitud < 1 || $longitud > 6) {
        return null;
    }
    return [(int) $lineaId, $areaId];
}

/** Consulta las tablas padre; 204 es un catálogo vacío, no un error de red. */
function opcionesAcLinea(): array
{
    $respuestaLineas = listarLineasInvestigacion();
    $respuestaAreas = listarAreasConocimientoAcLinea();
    $lineas = $respuestaLineas['datos']['datos'] ?? [];
    $areas = $respuestaAreas['datos']['datos'] ?? [];
    return [
        $respuestaLineas['correcta'] && is_array($lineas) ? $lineas : [],
        $respuestaAreas['correcta'] && is_array($areas) ? $areas : [],
        $respuestaLineas['correcta'],
        $respuestaAreas['correcta'],
    ];
}

function formularioConOpcionesAcLinea(array $valores = [], array $errores = []): void
{
    [$lineas, $areas, $lineasDisponibles, $areasDisponibles] = opcionesAcLinea();
    mostrarFormularioAcLinea(
        $valores, $errores, $lineas, $areas, $lineasDisponibles, $areasDisponibles
    );
}

/** Reutiliza los mensajes ya devueltos por el controlador backend. */
function erroresRespuestaAcLinea(array $respuesta): array
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

function redirigirAcLinea(string $mensaje): never
{
    $_SESSION['mensaje_ac_linea'] = $mensaje;
    header('Location: /asociaciones-ac-linea', true, 303);
    exit;
}

// LISTADO
if ($ruta === '/asociaciones-ac-linea' && $metodo === 'GET') {
    mostrarListaAcLinea();
    exit;
}

// FORMULARIO DE REGISTRO
if ($ruta === '/asociaciones-ac-linea/nuevo' && $metodo === 'GET') {
    formularioConOpcionesAcLinea();
    exit;
}

// CONSULTA DE UNA ASOCIACIÓN; GET con las dos partes de la PK.
if ($ruta === '/asociaciones-ac-linea/ver' && $metodo === 'GET') {
    $claves = clavesAcLinea($_GET);
    if ($claves === null) {
        http_response_code(400);
        mostrarNoEncontrada('Solicitud incorrecta', 'Indique una línea y un área válidas.');
        exit;
    }
    [$lineaId, $areaId] = $claves;
    $respuesta = obtenerAsociacionAcLinea($lineaId, $areaId);
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
    $valores = $respuesta['datos'];
    [$lineas, $areas] = opcionesAcLinea();
    mostrarDetalleAcLinea($valores, $lineas, $areas);
    exit;
}

// CREAR: usa únicamente los dos IDs. El backend valida ambas FK y duplicados.
if ($ruta === '/asociaciones-ac-linea/crear' && $metodo === 'POST') {
    $valores = $_POST;
    $claves = clavesAcLinea($_POST);
    if ($claves === null) {
        http_response_code(422);
        formularioConOpcionesAcLinea($valores, [
            'Seleccione una línea de investigación y un área de conocimiento válidas.',
        ]);
        exit;
    }
    [$lineaId, $areaId] = $claves;
    $respuesta = crearAsociacionAcLinea([
        'linea_investigacion' => $lineaId,
        'area_conocimiento' => $areaId,
    ]);
    if ($respuesta['correcta']) {
        redirigirAcLinea('Asociación registrada correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [400, 404, 409, 422], true)
        ? $respuesta['estado'] : 503);
    formularioConOpcionesAcLinea($valores, erroresRespuestaAcLinea($respuesta));
    exit;
}

// RETIRAR: DELETE lógico. No existe edición porque PUT/PATCH responden 405.
if ($ruta === '/asociaciones-ac-linea/retirar' && $metodo === 'POST') {
    $claves = clavesAcLinea($_POST);
    if ($claves === null) {
        http_response_code(400);
        mostrarListaAcLinea(['Línea de investigación o área de conocimiento no válida.']);
        exit;
    }
    [$lineaId, $areaId] = $claves;
    $respuesta = retirarAsociacionAcLinea($lineaId, $areaId);
    if ($respuesta['correcta']) {
        redirigirAcLinea('Asociación retirada correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [404, 409], true)
        ? $respuesta['estado'] : 503);
    mostrarListaAcLinea(erroresRespuestaAcLinea($respuesta));
    exit;
}
