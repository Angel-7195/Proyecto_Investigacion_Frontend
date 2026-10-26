<?php

declare(strict_types=1);

/** Rutas de la séptima entidad (primera de V2): grupo_investigacion. */
require_once __DIR__ . '/../vistas/grupo_investigacion/lista.php';
require_once __DIR__ . '/../vistas/grupo_investigacion/formulario.php';

function idValidoGrupoInvestigacion(mixed $valor): ?int
{
    if (!is_scalar($valor)) {
        return null;
    }
    $id = filter_var((string) $valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}

/** Convierte los campos del formulario en los tipos requeridos por la API JSON. */
function datosDesdeFormularioGrupoInvestigacion(array $formulario): array
{
    $opcionales = ['url_gruplac', 'categoria', 'convocatoria'];
    $datos = [];
    foreach ($opcionales as $campo) {
        $valor = trim((string) ($formulario[$campo] ?? ''));
        $datos[$campo] = $valor === '' ? null : $valor;
    }

    $universidad = trim((string) ($formulario['universidsad'] ?? ''));
    $universidadId = $universidad === '' ? null : idValidoGrupoInvestigacion($universidad);
    $datos['universidsad'] = $universidad === '' ? null : ($universidadId ?? $universidad);

    $interno = (string) ($formulario['interno'] ?? '');
    $datos['interno'] = in_array($interno, ['0', '1'], true) ? (int) $interno : $interno;
    $datos['nombre'] = trim((string) ($formulario['nombre'] ?? ''));
    $datos['fecha_fundacion'] = trim((string) ($formulario['fecha_fundacion'] ?? ''));
    $datos['ambito'] = trim((string) ($formulario['ambito'] ?? ''));
    return $datos;
}

/** Devuelve las universidades que la API considera activas. */
function opcionesUniversidadesGrupoInvestigacion(): array
{
    $respuesta = listarUniversidades();
    if (!$respuesta['correcta']) {
        return [[], false];
    }
    $universidades = $respuesta['datos']['datos'] ?? [];
    if (!is_array($universidades)) {
        return [[], false];
    }
    return [$universidades, true];
}

function formularioConUniversidadesGrupoInvestigacion(
    string $modo,
    array $valores = [],
    array $errores = []
): void {
    [$universidades, $disponibles] = opcionesUniversidadesGrupoInvestigacion();
    mostrarFormularioGrupoInvestigacion($modo, $valores, $errores, $universidades, $disponibles);
}

function erroresRespuestaGrupoInvestigacion(array $respuesta): array
{
    $datos = $respuesta['datos'] ?? [];
    if (!empty($respuesta['error'])) {
        return [(string) $respuesta['error']];
    }
    if (isset($datos['errores']) && is_array($datos['errores']) && $datos['errores'] !== []) {
        return array_map('strval', $datos['errores']);
    }
    $mensaje = $datos['detalle'] ?? $datos['mensaje'] ?? null;
    return [is_string($mensaje) && $mensaje !== '' ? $mensaje : 'No fue posible completar la operación.'];
}

function redirigirListadoGrupoInvestigacion(string $mensaje): never
{
    $_SESSION['mensaje_grupo_investigacion'] = $mensaje;
    header('Location: /grupos-investigacion', true, 303);
    exit;
}

// LISTAR
if ($ruta === '/grupos-investigacion' && $metodo === 'GET') {
    mostrarListaGruposInvestigacion();
    exit;
}

// NUEVO
if ($ruta === '/grupos-investigacion/nuevo' && $metodo === 'GET') {
    formularioConUniversidadesGrupoInvestigacion('crear');
    exit;
}

// EDITAR (cargar datos de API)
if ($ruta === '/grupos-investigacion/editar' && $metodo === 'GET') {
    $id = idValidoGrupoInvestigacion($_GET['id'] ?? null);
    if ($id === null) {
        http_response_code(400);
        mostrarNoEncontrada('Solicitud incorrecta', 'Indique un identificador válido de grupo.');
        exit;
    }

    $respuesta = obtenerGrupoInvestigacion($id);
    if (!$respuesta['correcta']) {
        http_response_code($respuesta['estado'] === 404 ? 404 : 503);
        mostrarNoEncontrada(
            $respuesta['estado'] === 404 ? 'Grupo no encontrado' : 'Servicio no disponible',
            $respuesta['estado'] === 404
                ? 'El grupo solicitado no existe o está retirado.'
                : 'No fue posible consultar los datos del grupo en la API.'
        );
        exit;
    }
    formularioConUniversidadesGrupoInvestigacion('editar', $respuesta['datos']);
    exit;
}

// CREAR
if ($ruta === '/grupos-investigacion/crear' && $metodo === 'POST') {
    $valores = $_POST;
    $id = idValidoGrupoInvestigacion($_POST['id'] ?? null);
    if ($id === null) {
        http_response_code(422);
        formularioConUniversidadesGrupoInvestigacion('crear', $valores, ['El ID debe ser un entero positivo.']);
        exit;
    }

    $datos = datosDesdeFormularioGrupoInvestigacion($_POST);
    $datos['id'] = $id;
    $respuesta = crearGrupoInvestigacion($datos);
    if ($respuesta['correcta']) {
        redirigirListadoGrupoInvestigacion('Grupo de investigación registrado correctamente.');
    }

    http_response_code(in_array($respuesta['estado'], [409, 422], true)
        ? $respuesta['estado'] : 503);
    formularioConUniversidadesGrupoInvestigacion('crear', $valores, erroresRespuestaGrupoInvestigacion($respuesta));
    exit;
}

// ACTUALIZAR: PUT completo o PATCH de los campos realmente diferentes.
if ($ruta === '/grupos-investigacion/actualizar' && $metodo === 'POST') {
    $id = idValidoGrupoInvestigacion($_POST['id'] ?? null);
    if ($id === null) {
        http_response_code(400);
        mostrarNoEncontrada('Solicitud incorrecta', 'Indique un grupo válido para actualizar.');
        exit;
    }

    $valores = $_POST;
    $datos = datosDesdeFormularioGrupoInvestigacion($_POST);
    $accion = (string) ($_POST['accion'] ?? '');

    if ($accion === 'completo') {
        $respuesta = reemplazarGrupoInvestigacion($id, $datos);
    } elseif ($accion === 'parcial') {
        $respuestaActual = obtenerGrupoInvestigacion($id);
        if (!$respuestaActual['correcta']) {
            http_response_code($respuestaActual['estado'] === 404 ? 404 : 503);
            formularioConUniversidadesGrupoInvestigacion('editar', $valores, erroresRespuestaGrupoInvestigacion($respuestaActual));
            exit;
        }
        $actual = $respuestaActual['datos'];
        $diferencias = [];
        foreach ($datos as $campo => $valor) {
            $anterior = $actual[$campo] ?? null;
            if (in_array($campo, ['interno', 'universidsad'], true) && $anterior !== null) {
                $anterior = (int) $anterior;
            }
            if ($valor !== $anterior) {
                $diferencias[$campo] = $valor;
            }
        }
        if ($diferencias === []) {
            formularioConUniversidadesGrupoInvestigacion('editar', $valores, ['No realizó ningún cambio.']);
            exit;
        }
        $respuesta = actualizarGrupoInvestigacion($id, $diferencias);
    } else {
        http_response_code(400);
        formularioConUniversidadesGrupoInvestigacion('editar', $valores, ['No se reconoció la operación solicitada.']);
        exit;
    }

    if ($respuesta['correcta']) {
        redirigirListadoGrupoInvestigacion('Grupo de investigación actualizado correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [400, 404, 409, 422], true)
        ? $respuesta['estado'] : 503);
    formularioConUniversidadesGrupoInvestigacion('editar', $valores, erroresRespuestaGrupoInvestigacion($respuesta));
    exit;
}

// RETIRAR (DELETE lógico en la API, POST desde el formulario HTML)
if ($ruta === '/grupos-investigacion/retirar' && $metodo === 'POST') {
    $id = idValidoGrupoInvestigacion($_POST['id'] ?? null);
    if ($id === null) {
        http_response_code(400);
        mostrarListaGruposInvestigacion(['El identificador recibido no es válido.']);
        exit;
    }
    $respuesta = retirarGrupoInvestigacion($id);
    if ($respuesta['correcta']) {
        redirigirListadoGrupoInvestigacion('Grupo de investigación retirado correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [404, 409], true)
        ? $respuesta['estado'] : 503);
    mostrarListaGruposInvestigacion(erroresRespuestaGrupoInvestigacion($respuesta));
    exit;
}
