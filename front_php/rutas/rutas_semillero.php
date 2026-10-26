<?php

declare(strict_types=1);

/** Rutas de semillero. Las operaciones de base de datos corresponden a la API. */
require_once __DIR__ . '/../vistas/semillero/lista.php';
require_once __DIR__ . '/../vistas/semillero/formulario.php';

function idValidoSemillero(mixed $valor): ?int
{
    if (!is_scalar($valor)) {
        return null;
    }
    $id = filter_var((string) $valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}

/** Los valores HTML son texto; las claves foráneas deben viajar como enteros JSON. */
function datosDesdeFormularioSemillero(array $formulario): array
{
    $idGrupo = trim((string) ($formulario['grupo_investigacion'] ?? ''));
    $grupoValidado = idValidoSemillero($idGrupo);
    return [
        'nombre' => trim((string) ($formulario['nombre'] ?? '')),
        'fecha_fundacion' => trim((string) ($formulario['fecha_fundacion'] ?? '')),
        'grupo_investigacion' => $grupoValidado ?? $idGrupo,
    ];
}

/** Devuelve grupos activos según GET /api/grupo_investigacion; HTTP 204 equivale a lista vacía. */
function opcionesGruposSemillero(): array
{
    $respuesta = listarGruposInvestigacion();
    if (!$respuesta['correcta']) {
        return [[], false];
    }
    $grupos = $respuesta['datos']['datos'] ?? [];
    if (!is_array($grupos)) {
        return [[], false];
    }
    return [$grupos, true];
}

function formularioConGruposSemillero(
    string $modo,
    array $valores = [],
    array $errores = []
): void {
    [$grupos, $disponibles] = opcionesGruposSemillero();
    mostrarFormularioSemillero($modo, $valores, $errores, $grupos, $disponibles);
}

function erroresRespuestaSemillero(array $respuesta): array
{
    if (!empty($respuesta['error'])) {
        return [(string) $respuesta['error']];
    }
    $datos = $respuesta['datos'] ?? [];
    if (isset($datos['errores']) && is_array($datos['errores']) && $datos['errores'] !== []) {
        return array_map('strval', $datos['errores']);
    }
    $mensaje = $datos['detalle'] ?? $datos['mensaje'] ?? null;
    return [is_string($mensaje) && $mensaje !== '' ? $mensaje : 'No fue posible completar la operación.'];
}

function redirigirListadoSemillero(string $mensaje): never
{
    $_SESSION['mensaje_semillero'] = $mensaje;
    header('Location: /semilleros', true, 303);
    exit;
}

// LISTAR
if ($ruta === '/semilleros' && $metodo === 'GET') {
    mostrarListaSemilleros();
    exit;
}

// REGISTRAR
if ($ruta === '/semilleros/nuevo' && $metodo === 'GET') {
    formularioConGruposSemillero('crear');
    exit;
}

// EDITAR
if ($ruta === '/semilleros/editar' && $metodo === 'GET') {
    $id = idValidoSemillero($_GET['id'] ?? null);
    if ($id === null) {
        http_response_code(400);
        mostrarNoEncontrada('Solicitud incorrecta', 'Indique un identificador válido de semillero.');
        exit;
    }
    $respuesta = obtenerSemillero($id);
    if (!$respuesta['correcta']) {
        http_response_code($respuesta['estado'] === 404 ? 404 : 503);
        mostrarNoEncontrada(
            $respuesta['estado'] === 404 ? 'Semillero no encontrado' : 'Servicio no disponible',
            $respuesta['estado'] === 404
                ? 'El semillero solicitado no existe o está retirado.'
                : 'No fue posible consultar los datos del semillero en la API.'
        );
        exit;
    }
    formularioConGruposSemillero('editar', $respuesta['datos']);
    exit;
}

// CREAR
if ($ruta === '/semilleros/crear' && $metodo === 'POST') {
    $valores = $_POST;
    $id = idValidoSemillero($_POST['id'] ?? null);
    if ($id === null) {
        http_response_code(422);
        formularioConGruposSemillero('crear', $valores, ['El ID debe ser un entero positivo.']);
        exit;
    }
    if (idValidoSemillero($_POST['grupo_investigacion'] ?? null) === null) {
        http_response_code(422);
        formularioConGruposSemillero('crear', $valores, ['Seleccione un grupo de investigación válido.']);
        exit;
    }
    $datos = datosDesdeFormularioSemillero($_POST);
    $datos['id'] = $id;
    $respuesta = crearSemillero($datos);
    if ($respuesta['correcta']) {
        redirigirListadoSemillero('Semillero registrado correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [409, 422], true) ? $respuesta['estado'] : 503);
    formularioConGruposSemillero('crear', $valores, erroresRespuestaSemillero($respuesta));
    exit;
}

// GUARDAR: PUT completo o PATCH de campos modificados.
if ($ruta === '/semilleros/actualizar' && $metodo === 'POST') {
    $id = idValidoSemillero($_POST['id'] ?? null);
    if ($id === null) {
        http_response_code(400);
        mostrarNoEncontrada('Solicitud incorrecta', 'Indique un semillero válido para actualizar.');
        exit;
    }
    $valores = $_POST;
    if (idValidoSemillero($_POST['grupo_investigacion'] ?? null) === null) {
        http_response_code(422);
        formularioConGruposSemillero('editar', $valores, ['Seleccione un grupo de investigación válido.']);
        exit;
    }
    $datos = datosDesdeFormularioSemillero($_POST);
    $accion = (string) ($_POST['accion'] ?? '');
    if ($accion === 'completo') {
        $respuesta = reemplazarSemillero($id, $datos);
    } elseif ($accion === 'parcial') {
        $respuestaActual = obtenerSemillero($id);
        if (!$respuestaActual['correcta']) {
            http_response_code($respuestaActual['estado'] === 404 ? 404 : 503);
            formularioConGruposSemillero('editar', $valores, erroresRespuestaSemillero($respuestaActual));
            exit;
        }
        $actual = $respuestaActual['datos'];
        $diferencias = [];
        foreach ($datos as $campo => $valor) {
            $anterior = $actual[$campo] ?? null;
            if ($campo === 'grupo_investigacion' && $anterior !== null) {
                $anterior = (int) $anterior;
            }
            if ($valor !== $anterior) {
                $diferencias[$campo] = $valor;
            }
        }
        if ($diferencias === []) {
            formularioConGruposSemillero('editar', $valores, ['No realizó ningún cambio.']);
            exit;
        }
        $respuesta = actualizarSemillero($id, $diferencias);
    } else {
        http_response_code(400);
        formularioConGruposSemillero('editar', $valores, ['No se reconoció la operación solicitada.']);
        exit;
    }
    if ($respuesta['correcta']) {
        redirigirListadoSemillero('Semillero actualizado correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [400, 404, 409, 422], true)
        ? $respuesta['estado'] : 503);
    formularioConGruposSemillero('editar', $valores, erroresRespuestaSemillero($respuesta));
    exit;
}

// RETIRAR: POST HTML a DELETE /api/semillero/{id}.
if ($ruta === '/semilleros/retirar' && $metodo === 'POST') {
    $id = idValidoSemillero($_POST['id'] ?? null);
    if ($id === null) {
        http_response_code(400);
        mostrarListaSemilleros(['El identificador recibido no es válido.']);
        exit;
    }
    $respuesta = retirarSemillero($id);
    if ($respuesta['correcta']) {
        redirigirListadoSemillero('Semillero retirado correctamente.');
    }
    http_response_code(in_array($respuesta['estado'], [404, 409], true)
        ? $respuesta['estado'] : 503);
    mostrarListaSemilleros(erroresRespuestaSemillero($respuesta));
    exit;
}
