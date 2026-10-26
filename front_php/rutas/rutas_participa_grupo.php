<?php

declare(strict_types=1);

/** Rutas HTML de participa_grupo, identificada por dos claves enteras. */
require_once __DIR__ . '/../vistas/participa_grupo/lista.php';
require_once __DIR__ . '/../vistas/participa_grupo/formulario.php';

function idValidoParticipaGrupo(mixed $valor): ?int
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
function clavesParticipaGrupo(array $fuente): ?array
{
    $cedula = idValidoParticipaGrupo($fuente['docente_cedula'] ?? $fuente['cedula'] ?? null);
    $grupoId = idValidoParticipaGrupo($fuente['grupo_investigacion_id'] ?? $fuente['grupo'] ?? null);
    return $cedula !== null && $grupoId !== null ? [$cedula, $grupoId] : null;
}

/** El cliente HTTP recibirá enteros y NULL, no los textos que envía HTML. */
function datosParticipaGrupoFormulario(array $formulario): array
{
    $fechaFin = trim((string) ($formulario['fecha_fin'] ?? ''));
    return [
        'rol' => trim((string) ($formulario['rol'] ?? '')),
        'fecha_inicio' => trim((string) ($formulario['fecha_inicio'] ?? '')),
        'fecha_fin' => $fechaFin === '' ? null : $fechaFin,
    ];
}

/** Validación de presentación; la decisión definitiva corresponde a la API. */
function validarCamposParticipaGrupo(array $datos): array
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
function opcionesParticipaGrupo(): array
{
    $rDocentes = listarDocentes();
    $rGrupos = listarGruposInvestigacion();
    $docentes = $rDocentes['datos']['datos'] ?? [];
    $grupos = $rGrupos['datos']['datos'] ?? [];
    return [
        $rDocentes['correcta'] && is_array($docentes) ? $docentes : [],
        $rGrupos['correcta'] && is_array($grupos) ? $grupos : [],
        $rDocentes['correcta'] && is_array($docentes),
        $rGrupos['correcta'] && is_array($grupos),
    ];
}

function formularioConOpcionesParticipaGrupo(
    string $modo,
    array $valores = [],
    array $errores = []
): void {
    [$docentes, $grupos, $docentesDisponibles, $gruposDisponibles] = opcionesParticipaGrupo();
    mostrarFormularioParticipaGrupo(
        $modo, $valores, $errores, $docentes, $grupos, $docentesDisponibles, $gruposDisponibles
    );
}

function erroresRespuestaParticipaGrupo(array $respuesta): array
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

function redirigirParticipaGrupo(string $mensaje): never
{
    $_SESSION['mensaje_participa_grupo'] = $mensaje;
    header('Location: /participaciones-grupo', true, 303);
    exit;
}

// LISTADO
if ($ruta === '/participaciones-grupo' && $metodo === 'GET') {
    mostrarListaParticipacionesGrupo();
    exit;
}

// FORMULARIO NUEVO
if ($ruta === '/participaciones-grupo/nuevo' && $metodo === 'GET') {
    formularioConOpcionesParticipaGrupo('crear');
    exit;
}

// FORMULARIO EDITAR: ambos identificadores obligatorios en la consulta.
if ($ruta === '/participaciones-grupo/editar' && $metodo === 'GET') {
    $claves = clavesParticipaGrupo($_GET);
    if ($claves === null) {
        http_response_code(400);
        mostrarNoEncontrada('Solicitud incorrecta', 'Indique una cédula y un grupo válidos.');
        exit;
    }
    [$cedula, $grupoId] = $claves;
    $respuesta = obtenerParticipacionGrupo($cedula, $grupoId);
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
    formularioConOpcionesParticipaGrupo('editar', $respuesta['datos']);
    exit;
}

// CREAR: selecciona ambas claves foráneas y envía los cinco campos.
if ($ruta === '/participaciones-grupo/crear' && $metodo === 'POST') {
    $valores = $_POST;
    $claves = clavesParticipaGrupo($_POST);
    if ($claves === null) {
        http_response_code(422);
        formularioConOpcionesParticipaGrupo('crear', $valores, [
            'Seleccione un docente y un grupo de investigación válidos.',
        ]);
        exit;
    }
    $datos = datosParticipaGrupoFormulario($_POST);
    $errores = validarCamposParticipaGrupo($datos);
    if ($errores !== []) {
        http_response_code(422);
        formularioConOpcionesParticipaGrupo('crear', $valores, $errores);
        exit;
    }
    [$datos['docente_cedula'], $datos['grupo_investigacion_id']] = $claves;
    $respuesta = crearParticipacionGrupo($datos);
    if ($respuesta['correcta']) {
        redirigirParticipaGrupo('Participación registrada correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [400, 409, 422], true)
        ? $respuesta['estado'] : 503);
    formularioConOpcionesParticipaGrupo('crear', $valores, erroresRespuestaParticipaGrupo($respuesta));
    exit;
}

// ACTUALIZAR: la pareja de claves forma la URL y nunca se envía en PUT/PATCH.
if ($ruta === '/participaciones-grupo/actualizar' && $metodo === 'POST') {
    $claves = clavesParticipaGrupo($_POST);
    if ($claves === null) {
        http_response_code(400);
        mostrarNoEncontrada('Solicitud incorrecta', 'Indique docente y grupo para editar.');
        exit;
    }
    [$cedula, $grupoId] = $claves;
    $valores = $_POST;
    $datos = datosParticipaGrupoFormulario($_POST);
    $errores = validarCamposParticipaGrupo($datos);
    if ($errores !== []) {
        http_response_code(422);
        formularioConOpcionesParticipaGrupo('editar', $valores, $errores);
        exit;
    }
    $accion = (string) ($_POST['accion'] ?? '');
    if ($accion === 'completo') {
        $respuesta = reemplazarParticipacionGrupo($cedula, $grupoId, $datos);
    } elseif ($accion === 'parcial') {
        $actualRespuesta = obtenerParticipacionGrupo($cedula, $grupoId);
        if (!$actualRespuesta['correcta']) {
            http_response_code($actualRespuesta['estado'] === 404 ? 404 : 503);
            formularioConOpcionesParticipaGrupo(
                'editar', $valores, erroresRespuestaParticipaGrupo($actualRespuesta)
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
            formularioConOpcionesParticipaGrupo('editar', $valores, ['No realizó ningún cambio.']);
            exit;
        }
        $respuesta = actualizarParticipacionGrupo($cedula, $grupoId, $diferencias);
    } else {
        http_response_code(400);
        formularioConOpcionesParticipaGrupo('editar', $valores, [
            'No se reconoció la operación solicitada.',
        ]);
        exit;
    }
    if ($respuesta['correcta']) {
        redirigirParticipaGrupo('Participación actualizada correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [400, 404, 409, 422], true)
        ? $respuesta['estado'] : 503);
    formularioConOpcionesParticipaGrupo('editar', $valores, erroresRespuestaParticipaGrupo($respuesta));
    exit;
}

// RETIRAR (DELETE lógico) usando ambos elementos de la clave compuesta.
if ($ruta === '/participaciones-grupo/retirar' && $metodo === 'POST') {
    $claves = clavesParticipaGrupo($_POST);
    if ($claves === null) {
        http_response_code(400);
        mostrarListaParticipacionesGrupo(['Cédula o grupo no válido.']);
        exit;
    }
    [$cedula, $grupoId] = $claves;
    $respuesta = retirarParticipacionGrupo($cedula, $grupoId);
    if ($respuesta['correcta']) {
        redirigirParticipaGrupo('Participación retirada correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [404, 409], true)
        ? $respuesta['estado'] : 503);
    mostrarListaParticipacionesGrupo(erroresRespuestaParticipaGrupo($respuesta));
    exit;
}
