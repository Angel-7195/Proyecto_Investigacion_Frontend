<?php

declare(strict_types=1);

/** Rutas HTML de grupo_linea: dos identificadores INT, no editables. */
require_once __DIR__ . '/../vistas/grupo_linea/lista.php';
require_once __DIR__ . '/../vistas/grupo_linea/formulario.php';

/** Valida el orden de la PK: grupo_investigacion / linea_investigacion. */
function clavesGrupoLinea(array $fuente): ?array
{
    $grupo = $fuente['grupo_investigacion'] ?? null;
    $linea = $fuente['linea_investigacion'] ?? null;
    if (!is_scalar($grupo) || !is_scalar($linea) || is_bool($grupo) || is_bool($linea)) {
        return null;
    }
    $reglas = ['options' => ['min_range' => 1, 'max_range' => 2147483647]];
    $grupoId = filter_var((string) $grupo, FILTER_VALIDATE_INT, $reglas);
    $lineaId = filter_var((string) $linea, FILTER_VALIDATE_INT, $reglas);
    if ($grupoId === false || $lineaId === false) {
        return null;
    }
    return [(int) $grupoId, (int) $lineaId];
}

/** Obtiene los dos catálogos activos por HTTP, sin conectar a MariaDB. */
function opcionesGrupoLinea(): array
{
    $respuestaGrupos = listarGruposInvestigacion();
    $respuestaLineas = listarLineasInvestigacion();
    $grupos = $respuestaGrupos['datos']['datos'] ?? [];
    $lineas = $respuestaLineas['datos']['datos'] ?? [];
    return [
        $respuestaGrupos['correcta'] && is_array($grupos) ? $grupos : [],
        $respuestaLineas['correcta'] && is_array($lineas) ? $lineas : [],
        $respuestaGrupos['correcta'],
        $respuestaLineas['correcta'],
    ];
}

function formularioConOpcionesGrupoLinea(array $valores = [], array $errores = []): void
{
    [$grupos, $lineas, $gruposDisponibles, $lineasDisponibles] = opcionesGrupoLinea();
    mostrarFormularioGrupoLinea($valores, $errores, $grupos, $lineas, $gruposDisponibles, $lineasDisponibles);
}

function erroresRespuestaGrupoLinea(array $respuesta): array
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

function redirigirGrupoLinea(string $mensaje): never
{
    $_SESSION['mensaje_grupo_linea'] = $mensaje;
    header('Location: /asociaciones-grupo-linea', true, 303);
    exit;
}

// LISTADO
if ($ruta === '/asociaciones-grupo-linea' && $metodo === 'GET') {
    mostrarListaGrupoLinea();
    exit;
}

// REGISTRO
if ($ruta === '/asociaciones-grupo-linea/nuevo' && $metodo === 'GET') {
    formularioConOpcionesGrupoLinea();
    exit;
}

// DETALLE por los dos identificadores de la PK
if ($ruta === '/asociaciones-grupo-linea/ver' && $metodo === 'GET') {
    $claves = clavesGrupoLinea($_GET);
    if ($claves === null) {
        http_response_code(400);
        mostrarNoEncontrada('Solicitud incorrecta', 'Indique un grupo de investigación y una línea válidos.');
        exit;
    }
    [$grupoId, $lineaId] = $claves;
    $respuesta = obtenerAsociacionGrupoLinea($grupoId, $lineaId);
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
    [$grupos, $lineas] = opcionesGrupoLinea();
    mostrarDetalleGrupoLinea($respuesta['datos'], $grupos, $lineas);
    exit;
}

// CREAR; el servicio de la API comprueba las dos FK activas y la PK compuesta.
if ($ruta === '/asociaciones-grupo-linea/crear' && $metodo === 'POST') {
    $valores = $_POST;
    $claves = clavesGrupoLinea($_POST);
    if ($claves === null) {
        http_response_code(422);
        formularioConOpcionesGrupoLinea($valores, [
            'Seleccione un grupo de investigación y una línea de investigación válidos.',
        ]);
        exit;
    }
    [$grupoId, $lineaId] = $claves;
    $respuesta = crearAsociacionGrupoLinea([
        'grupo_investigacion' => $grupoId,
        'linea_investigacion' => $lineaId,
    ]);
    if ($respuesta['correcta']) {
        redirigirGrupoLinea('Asociación registrada correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [400, 404, 409, 422], true)
        ? $respuesta['estado'] : 503);
    formularioConOpcionesGrupoLinea($valores, erroresRespuestaGrupoLinea($respuesta));
    exit;
}

// POST del formulario HTML -> DELETE lógico en la API
if ($ruta === '/asociaciones-grupo-linea/retirar' && $metodo === 'POST') {
    $claves = clavesGrupoLinea($_POST);
    if ($claves === null) {
        http_response_code(400);
        mostrarListaGrupoLinea(['Grupo de investigación o línea de investigación no válida.']);
        exit;
    }
    [$grupoId, $lineaId] = $claves;
    $respuesta = retirarAsociacionGrupoLinea($grupoId, $lineaId);
    if ($respuesta['correcta']) {
        redirigirGrupoLinea('Asociación retirada correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [404, 409], true)
        ? $respuesta['estado'] : 503);
    mostrarListaGrupoLinea(erroresRespuestaGrupoLinea($respuesta));
    exit;
}
