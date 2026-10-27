<?php

declare(strict_types=1);

/** Rutas HTML de participa_semillero, identificada por dos claves enteras. */
require_once __DIR__ . '/../vistas/participa_semillero/lista.php';
require_once __DIR__ . '/../vistas/participa_semillero/formulario.php';

function idValidoParticipaSemillero(mixed $valor): ?int
{
    if (!is_scalar($valor)) {
        return null;
    }
    $id = filter_var((string) $valor, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 2147483647],
    ]);
    return $id === false ? null : $id;
}

/** Extrae la clave compuesta; ambos campos son obligatorios. */
function clavesParticipaSemillero(array $fuente): ?array
{
    $cedula = idValidoParticipaSemillero($fuente['docente'] ?? $fuente['cedula'] ?? null);
    $semilleroId = idValidoParticipaSemillero($fuente['semillero'] ?? null);
    return $cedula !== null && $semilleroId !== null ? [$cedula, $semilleroId] : null;
}

/** El cliente HTTP recibirá enteros y NULL, no los textos que envía HTML. */
function datosParticipaSemilleroFormulario(array $formulario): array
{
    $fechaFin = trim((string) ($formulario['fecha_fin'] ?? ''));
    return [
        'rol' => trim((string) ($formulario['rol'] ?? '')),
        'fecha_inicio' => trim((string) ($formulario['fecha_inicio'] ?? '')),
        'fecha_fin' => $fechaFin === '' ? null : $fechaFin,
    ];
}

/** Validación de presentación; la decisión definitiva corresponde a la API. */
function validarCamposParticipaSemillero(array $datos): array
{
    $errores = [];
    $rol = $datos['rol'];
    $longitudRol = function_exists('mb_strlen')
        ? mb_strlen($rol, 'UTF-8') : (preg_match_all('/./us', $rol) ?: 0);
    if ($longitudRol < 1 || $longitudRol > 15) {
        $errores[] = 'El rol debe tener entre 1 y 15 caracteres.';
    }
    $inicio = $datos['fecha_inicio'];
    $fin = $datos['fecha_fin'];
    $fechaValida = static function (mixed $fecha): bool {
        if (!is_string($fecha)) {
            return false;
        }
        $valor = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        return $valor !== false && $valor->format('Y-m-d') === $fecha;
    };
    if (!$fechaValida($inicio)) {
        $errores[] = 'La fecha de inicio debe ser una fecha válida.';
    }
    if ($fin !== null && !$fechaValida($fin)) {
        $errores[] = 'La fecha de finalización debe ser válida o quedar vacía.';
    }
    if ($fechaValida($inicio) && $fin !== null && $fechaValida($fin) && $fin < $inicio) {
        $errores[] = 'La fecha de finalización no puede ser anterior a la fecha de inicio.';
    }
    return $errores;
}

/** Obtener catálogos activos, de manera independiente a la API de participación. */
function opcionesParticipaSemillero(): array
{
    $rDocentes = listarDocentes();
    $rSemilleros = listarSemilleros();
    $docentes = $rDocentes['datos']['datos'] ?? [];
    $semilleros = $rSemilleros['datos']['datos'] ?? [];
    return [
        $rDocentes['correcta'] && is_array($docentes) ? $docentes : [],
        $rSemilleros['correcta'] && is_array($semilleros) ? $semilleros : [],
        $rDocentes['correcta'] && is_array($docentes),
        $rSemilleros['correcta'] && is_array($semilleros),
    ];
}

function formularioConOpcionesParticipaSemillero(
    string $modo,
    array $valores = [],
    array $errores = []
): void {
    [$docentes, $semilleros, $docentesDisponibles, $semillerosDisponibles] = opcionesParticipaSemillero();
    mostrarFormularioParticipaSemillero(
        $modo, $valores, $errores, $docentes, $semilleros, $docentesDisponibles, $semillerosDisponibles
    );
}

function erroresRespuestaParticipaSemillero(array $respuesta): array
{
    if (!empty($respuesta['error'])) {
        return [(string) $respuesta['error']];
    }
    $datos = $respuesta['datos'] ?? [];
    if (isset($datos['errores']) && is_array($datos['errores']) && $datos['errores'] !== []) {
        return array_map('strval', $datos['errores']);
    }
    $mensaje = $datos['detalle'] ?? $datos['mensaje'] ?? null;
    return [is_string($mensaje) && $mensaje !== ''
        ? $mensaje : 'No fue posible completar la operación.'];
}

function redirigirParticipaSemillero(string $mensaje): never
{
    $_SESSION['mensaje_participa_semillero'] = $mensaje;
    header('Location: /participaciones-semillero', true, 303);
    exit;
}

// LISTADO
if ($ruta === '/participaciones-semillero' && $metodo === 'GET') {
    mostrarListaParticipacionesSemillero();
    exit;
}

// FORMULARIO NUEVO
if ($ruta === '/participaciones-semillero/nuevo' && $metodo === 'GET') {
    formularioConOpcionesParticipaSemillero('crear');
    exit;
}

// FORMULARIO EDITAR: ambos identificadores obligatorios en la consulta.
if ($ruta === '/participaciones-semillero/editar' && $metodo === 'GET') {
    $claves = clavesParticipaSemillero($_GET);
    if ($claves === null) {
        http_response_code(400);
        mostrarNoEncontrada('Solicitud incorrecta', 'Indique una cédula y un semillero válidos.');
        exit;
    }
    [$cedula, $semilleroId] = $claves;
    $respuesta = obtenerParticipacionSemillero($cedula, $semilleroId);
    if (!$respuesta['correcta']) {
        http_response_code($respuesta['estado'] === 404 ? 404 : 503);
        mostrarNoEncontrada(
            $respuesta['estado'] === 404 ? 'Participación no encontrada' : 'Servicio no disponible',
            $respuesta['estado'] === 404
                ? 'Esta participación no existe o ya fue retirada.'
                : 'No se pudo consultar la participación en la API.'
        );
        exit;
    }
    formularioConOpcionesParticipaSemillero('editar', $respuesta['datos']);
    exit;
}

// CREAR: selecciona ambas claves foráneas y envía los cinco campos.
if ($ruta === '/participaciones-semillero/crear' && $metodo === 'POST') {
    $valores = $_POST;
    $claves = clavesParticipaSemillero($_POST);
    if ($claves === null) {
        http_response_code(422);
        formularioConOpcionesParticipaSemillero('crear', $valores, [
            'Seleccione un docente y un semillero de investigación válidos.',
        ]);
        exit;
    }
    $datos = datosParticipaSemilleroFormulario($_POST);
    $errores = validarCamposParticipaSemillero($datos);
    if ($errores !== []) {
        http_response_code(422);
        formularioConOpcionesParticipaSemillero('crear', $valores, $errores);
        exit;
    }
    [$datos['docente'], $datos['semillero']] = $claves;
    $respuesta = crearParticipacionSemillero($datos);
    if ($respuesta['correcta']) {
        redirigirParticipaSemillero('Participación registrada correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [400, 409, 422], true)
        ? $respuesta['estado'] : 503);
    formularioConOpcionesParticipaSemillero('crear', $valores, erroresRespuestaParticipaSemillero($respuesta));
    exit;
}

// ACTUALIZAR: la pareja de claves forma la URL y nunca se envía en PUT/PATCH.
if ($ruta === '/participaciones-semillero/actualizar' && $metodo === 'POST') {
    $claves = clavesParticipaSemillero($_POST);
    if ($claves === null) {
        http_response_code(400);
        mostrarNoEncontrada('Solicitud incorrecta', 'Indique docente y semillero para editar.');
        exit;
    }
    [$cedula, $semilleroId] = $claves;
    $valores = $_POST;
    $datos = datosParticipaSemilleroFormulario($_POST);
    $errores = validarCamposParticipaSemillero($datos);
    if ($errores !== []) {
        http_response_code(422);
        formularioConOpcionesParticipaSemillero('editar', $valores, $errores);
        exit;
    }
    $accion = (string) ($_POST['accion'] ?? '');
    if ($accion === 'completo') {
        $respuesta = reemplazarParticipacionSemillero($cedula, $semilleroId, $datos);
    } elseif ($accion === 'parcial') {
        $actualRespuesta = obtenerParticipacionSemillero($cedula, $semilleroId);
        if (!$actualRespuesta['correcta']) {
            http_response_code($actualRespuesta['estado'] === 404 ? 404 : 503);
            formularioConOpcionesParticipaSemillero(
                'editar', $valores, erroresRespuestaParticipaSemillero($actualRespuesta)
            );
            exit;
        }
        $diferencias = [];
        foreach ($datos as $campo => $valor) {
            if ($valor !== ($actualRespuesta['datos'][$campo] ?? null)) {
                $diferencias[$campo] = $valor;
            }
        }
        if ($diferencias === []) {
            formularioConOpcionesParticipaSemillero('editar', $valores, ['No realizó ningún cambio.']);
            exit;
        }
        $respuesta = actualizarParticipacionSemillero($cedula, $semilleroId, $diferencias);
    } else {
        http_response_code(400);
        formularioConOpcionesParticipaSemillero('editar', $valores, [
            'No se reconoció la operación solicitada.',
        ]);
        exit;
    }
    if ($respuesta['correcta']) {
        redirigirParticipaSemillero('Participación actualizada correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [400, 404, 409, 422], true)
        ? $respuesta['estado'] : 503);
    formularioConOpcionesParticipaSemillero('editar', $valores, erroresRespuestaParticipaSemillero($respuesta));
    exit;
}

// RETIRAR (DELETE lógico) usando ambos elementos de la clave compuesta.
if ($ruta === '/participaciones-semillero/retirar' && $metodo === 'POST') {
    $claves = clavesParticipaSemillero($_POST);
    if ($claves === null) {
        http_response_code(400);
        mostrarListaParticipacionesSemillero(['Docente o semillero no válido.']);
        exit;
    }
    [$cedula, $semilleroId] = $claves;
    $respuesta = retirarParticipacionSemillero($cedula, $semilleroId);
    if ($respuesta['correcta']) {
        redirigirParticipaSemillero('Participación retirada correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [404, 409], true)
        ? $respuesta['estado'] : 503);
    mostrarListaParticipacionesSemillero(erroresRespuestaParticipaSemillero($respuesta));
    exit;
}
